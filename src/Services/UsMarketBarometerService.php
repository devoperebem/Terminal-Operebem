<?php

namespace App\Services;

use App\Core\Application;

class UsMarketBarometerService
{
    private string $baseUrl;
    private string $apiKey;
    private int $cacheTtl;
    private int $timeout;
    private int $connectTimeout;
    private string $cacheDir;

    public function __construct(array $options = [])
    {
        $this->baseUrl = rtrim($options['base_url'] ?? ($_ENV['USMB_BASE_URL'] ?? 'https://api.operebem.com.br/v1/us-market-barometer'), '/');
        $this->apiKey = $options['api_key'] ?? ($_ENV['USMB_API_KEY'] ?? '');
        $this->cacheTtl = (int)($options['cache_ttl'] ?? ($_ENV['USMB_CACHE_TTL'] ?? 300));
        $this->timeout = (int)($options['timeout'] ?? ($_ENV['USMB_TIMEOUT'] ?? 12));
        $this->connectTimeout = (int)($options['connect_timeout'] ?? ($_ENV['USMB_CONNECT_TIMEOUT'] ?? 8));
        $basePath = Application::getInstance()->getBasePath();
        $this->cacheDir = $options['cache_dir'] ?? ($basePath . '/storage/cache/us_market_barometer');

        if (!is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0755, true);
        }
    }

    public function getData(): array
    {
        // Preserve existing shape by proxying the legacy '/data' endpoint for now
        $path = $this->isV1() ? 'current' : 'data';
        $key = $this->isV1() ? 'usmb_v1_current' : 'usmb_legacy_data';
        $cached = $this->readCache($key);
        if ($cached !== null) {
            // If cached is an error wrapper, ignore and refetch
            if (is_array($cached) && isset($cached['success']) && $cached['success'] === false) {
                // fallthrough to refetch
            } else {
                return $cached;
            }
        }

        if ($this->isV1()) {
            // For v1, return current payload. Frontend grid still expects legacy shape; a mapping layer can be added later.
            $res = $this->requestJson($path);
            if (is_array($res) && ($res['success'] ?? false) === true) {
                $this->writeCache($key, $res);
            }
            return $res;
        }

        $url = $this->baseUrl . '/' . $path;
        $headers = [ 'X-API-KEY: ' . $this->apiKey, 'Accept: application/json' ];
        [$status, $body] = $this->doHttpRequest($url, $headers, $this->timeout, $this->connectTimeout);
        $logger = Application::getInstance()->logger();
        $logger->info('[USMB] Request: GET ' . $url);
        $logger->info('[USMB] Response: HTTP ' . $status . ', bytes=' . strlen((string)$body));
        if ($status < 200 || $status >= 300) {
            return [ 'success' => false, 'message' => 'Erro na API USMB (HTTP ' . $status . ')' ];
        }
        $data = json_decode((string)$body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [ 'success' => false, 'message' => 'Erro ao decodificar resposta JSON' ];
        }
        // Return raw payload to keep compatibility
        $this->writeCache($key, $data);
        return $data;
    }

    private function isV1(): bool
    {
        return str_contains($this->baseUrl, '/v1/us-market-barometer');
    }

    private function requestJson(string $path, string $queryString = ''): array
    {
        $url = $this->baseUrl . '/' . ltrim($path, '/');
        if ($queryString !== '') {
            $url .= (str_contains($url, '?') ? '&' : '?') . $queryString;
        }
        $headers = [ 'X-API-KEY: ' . $this->apiKey, 'Accept: application/json' ];
        [$status, $body] = $this->doHttpRequest($url, $headers, $this->timeout, $this->connectTimeout);
        $logger = Application::getInstance()->logger();
        $logger->info('[USMB] Request: GET ' . $url);
        $logger->info('[USMB] Response: HTTP ' . $status . ', bytes=' . strlen((string)$body));
        if ($status < 200 || $status >= 300) {
            return [ 'success' => false, 'message' => 'Erro na API USMB (HTTP ' . $status . ')' ];
        }
        $data = json_decode((string)$body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [ 'success' => false, 'message' => 'Erro ao decodificar resposta JSON' ];
        }
        return is_array($data) ? $data : [ 'success' => false, 'message' => 'Resposta inválida' ];
    }

    private function doHttpRequest(string $url, array $headers, int $timeout, int $connectTimeout): array
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => $connectTimeout,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_USERAGENT => 'Terminal-Operebem/1.0 (+usmb) PHP/' . PHP_VERSION,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
        ]);
        $body = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE) ?: 0;
        curl_close($ch);
        return [$status, $body ?: ''];
    }

    private function cacheFile(string $key): string
    {
        return rtrim($this->cacheDir, '/\\') . DIRECTORY_SEPARATOR . $key . '.json';
    }

    private function readCache(string $key): ?array
    {
        $file = $this->cacheFile($key);
        if (!is_file($file)) return null;
        $mtime = @filemtime($file) ?: 0;
        if ((time() - $mtime) > $this->cacheTtl) return null;
        $raw = @file_get_contents($file);
        if ($raw === false) return null;
        $data = json_decode($raw, true);
        return (json_last_error() === JSON_ERROR_NONE) ? $data : null;
    }

    private function writeCache(string $key, array $payload): void
    {
        $file = $this->cacheFile($key);
        $tmp = $file . '.tmp';
        @file_put_contents($tmp, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        @rename($tmp, $file);
    }
}
