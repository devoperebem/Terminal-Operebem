<?php

namespace App\Services;

class HttpProxyService
{
    private array $allowedHosts;
    private string $cookieDir;
    private int $timeout;

    public function __construct()
    {
        $this->allowedHosts = $this->parseAllowedHosts($_ENV['PROXY_ALLOWED_HOSTS'] ?? 'www.cmegroup.com,cmegroup.com');
        $this->cookieDir = dirname(__DIR__, 1) . '/../storage/cache/proxy_cookies';
        if (!is_dir($this->cookieDir)) { @mkdir($this->cookieDir, 0775, true); }
        $this->timeout = (int)($_ENV['PROXY_TIMEOUT'] ?? 12);
    }

    public function isAllowed(string $url): bool
    {
        $p = parse_url($url);
        $host = strtolower($p['host'] ?? '');
        if ($host === '') return false;
        foreach ($this->allowedHosts as $rule) {
            if ($rule[0] === '*') {
                $suf = substr($rule, 1); // '*.domain.com' => '.domain.com'
                if ($suf !== '' && str_ends_with($host, $suf)) return true;
            } else if ($host === $rule) {
                return true;
            }
        }
        return false;
    }

    public function fetch(string $url, array $headers = [], ?string $cookieKey = null): array
    {
        if (!$this->isAllowed($url)) {
            return [403, '', []];
        }
        $cj = $this->cookieJar($cookieKey ?: $url);
        if (!function_exists('curl_init')) {
            $ctx = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'header' => $this->formatHeaders($headers),
                    'timeout' => $this->timeout,
                ]
            ]);
            $body = @file_get_contents($url, false, $ctx);
            return [ $body ? 200 : 500, $body ?: '', [] ];
        }
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_HTTPHEADER => $this->normalizeHeaders($headers),
            CURLOPT_COOKIEJAR => $cj,
            CURLOPT_COOKIEFILE => $cj,
            CURLOPT_HEADER => true,
        ]);
        $resp = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $hdrSize = (int)curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $rawHeaders = substr((string)$resp, 0, $hdrSize);
        $body = substr((string)$resp, $hdrSize);
        curl_close($ch);
        $hdrs = $this->parseHeaders($rawHeaders);
        return [$status, $body, $hdrs];
    }

    public function absolutizeUrl(string $base, string $ref): string
    {
        // If ref already absolute
        if (preg_match('#^https?://#i', $ref)) return $ref;
        $bp = parse_url($base);
        $scheme = $bp['scheme'] ?? 'https';
        $host = $bp['host'] ?? '';
        $port = isset($bp['port']) ? (':' . (int)$bp['port']) : '';
        $path = $bp['path'] ?? '/';
        $path = preg_replace('#/[^/]*$#', '/', $path);
        if (str_starts_with($ref, '//')) return $scheme . ':' . $ref;
        if (str_starts_with($ref, '/')) return $scheme . '://' . $host . $port . $ref;
        return $scheme . '://' . $host . $port . $path . $ref;
    }

    public function rewriteHtmlForProxy(string $html, string $baseUrl, string $assetEndpoint): string
    {
        $cb = function($m) use ($baseUrl, $assetEndpoint){
            $url = html_entity_decode($m[2], ENT_QUOTES | ENT_HTML5);
            if ($url === '' || str_starts_with($url, 'data:') || str_starts_with($url, 'about:') || str_starts_with($url, 'javascript:')) return $m[0];
            $abs = $this->absolutizeUrl($baseUrl, $url);
            if (!$this->isAllowed($abs)) return $m[0];
            $prox = $assetEndpoint . '?u=' . rawurlencode($abs);
            return $m[1] . $prox . $m[3];
        };
        // Replace href/src attributes
        $html = preg_replace_callback('#(href\s*=\s*["\"])\s*([^"\"]+)(["\"])#i', $cb, $html);
        $html = preg_replace_callback('#(src\s*=\s*["\"])\s*([^"\"]+)(["\"])#i', $cb, $html);
        // Remove framebusters basic
        $html = preg_replace('#window\.top\s*!=\s*window#i', 'false', $html);
        return $html;
    }

    private function cookieJar(string $key): string
    {
        $h = parse_url($key, PHP_URL_HOST) ?: sha1($key);
        return rtrim($this->cookieDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . preg_replace('/[^a-z0-9.-]+/i', '_', $h) . '.txt';
    }

    private function formatHeaders(array $headers): string
    {
        $lines = [];
        foreach ($this->normalizeHeaders($headers) as $h) { $lines[] = $h; }
        return implode("\r\n", $lines);
    }

    private function normalizeHeaders(array $headers): array
    {
        $ua = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/118 Safari/537.36';
        $base = [
            'User-Agent: ' . $ua,
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language: pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
            'Connection: keep-alive'
        ];
        foreach ($headers as $k => $v) {
            if (is_int($k)) { $base[] = (string)$v; }
            else { $base[] = $k . ': ' . $v; }
        }
        return $base;
    }

    private function parseHeaders(string $raw): array
    {
        $lines = preg_split('/\r?\n/', $raw, -1, PREG_SPLIT_NO_EMPTY);
        $hdrs = [];
        foreach ($lines as $ln) {
            $p = strpos($ln, ':');
            if ($p !== false) {
                $name = strtolower(trim(substr($ln, 0, $p)));
                $val = trim(substr($ln, $p+1));
                $hdrs[$name] = $val;
            }
        }
        return $hdrs;
    }

    private function parseAllowedHosts(string $csv): array
    {
        $out = [];
        foreach (explode(',', $csv) as $h) {
            $h = trim($h);
            if ($h === '') continue;
            if (str_starts_with($h, '*')) { $out[] = $h; }
            else { $out[] = strtolower($h); }
        }
        return $out;
    }
}
