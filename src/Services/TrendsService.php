<?php
namespace App\Services;

use App\Core\Application;
use Monolog\Logger;

class TrendsService
{
    private string $base = 'https://trends.google.com/trends/api';
    private string $hl = 'pt-BR';
    private int $tz = 180;
    private string $cacheDir;
    private Logger $logger;
    private string $cookieFile;

    public function __construct()
    {
        $this->cacheDir = dirname(__DIR__, 1) . '/../storage/cache/trends';
        if (!is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0775, true);
        }
        $this->cookieFile = $this->cacheDir . '/cookies.txt';
        // tz dinâmico (minutos) baseado no offset local; se 0 (UTC), assume 180 (Brasil)
        try {
            $tzGuess = (int) round(- (int)date('Z') / 60);
            $tzGuess = ($tzGuess === 0 ? 180 : $tzGuess);
            // Google Trends espera minutos com sinal. Brasil UTC-3 => -180
            $this->tz = -abs($tzGuess);
        } catch (\Throwable $t) { /* keep default */ }
        $this->logger = Application::getInstance()->logger();
    }

    private function curlGet(string $url, bool $withCookies = true): array
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_HTTPHEADER => [
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/118 Safari/537.36',
                'Accept: */*',
                'Accept-Language: pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
                // Referer mais específico, alinhado ao fluxo do Trends (travado para a nossa feature)
                'Referer: https://trends.google.com/trends/explore?hl=pt-BR&date=today%205-y&geo=BR&q=Ibovespa',
                'Origin: https://trends.google.com',
                'Sec-Fetch-Site: same-origin',
                'Sec-Fetch-Mode: cors',
                'Sec-Fetch-Dest: empty',
                'X-Requested-With: XMLHttpRequest',
                'X-Client-Data: CJW2yQEIpbbJAQjBtskB'
            ]
        ]);
        if ($withCookies) {
            curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookieFile);
            curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookieFile);
        }
        $body = curl_exec($ch);
        $err = curl_error($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return [$code, $body, $err];
    }

    private function ensureCookies(): void
    {
        $fresh = false;
        if (!is_file($this->cookieFile)) {
            $fresh = true;
        } else {
            $age = time() - (int)@filemtime($this->cookieFile);
            if ($age > 3600 * 12) $fresh = true; // 12h
        }
        if ($fresh) {
            $url = 'https://trends.google.com/trends/explore?hl=' . rawurlencode($this->hl) . '&geo=BR&date=today%205-y&q=' . rawurlencode('Ibovespa');
            $this->logger->info('[Trends] cookie preflight', ['url' => $url]);
            // Chamada simples para receber cookies no jar
            [$code, $body, $err] = $this->curlGet($url, true);
            $this->logger->info('[Trends] cookie preflight resp', ['code' => $code, 'len' => strlen((string)$body)]);
        }
    }

    private function stripPreamble(?string $s): string
    {
        if ($s === null) return '';
        // Remove prefix )]}', (com ou sem vírgula e/ou quebra de linha)
        if (strpos($s, ")]}'") === 0) {
            $s = substr($s, 4);
            if ($s !== '' && ($s[0] === ',' || $s[0] === "\n")) { $s = substr($s, 1); }
            return $s;
        }
        if (strpos($s, ")]}',") === 0) {
            return substr($s, 5);
        }
        return $s;
    }

    private function cacheGet(string $key, int $ttlSec): mixed
    {
        $file = $this->cacheDir . '/' . $key . '.json';
        if (!is_file($file)) return null;
        $age = time() - (int)@filemtime($file);
        if ($age > $ttlSec) return null;
        $raw = @file_get_contents($file);
        if ($raw === false) return null;
        $j = json_decode($raw, true);
        return $j;
    }

    private function cachePut(string $key, mixed $value): void
    {
        $file = $this->cacheDir . '/' . $key . '.json';
        @file_put_contents($file, json_encode($value, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
    }

    public function rateLimitCheck(string $ip, int $limit = 40, int $windowSec = 600): bool
    {
        $file = $this->cacheDir . '/rl_' . preg_replace('/[^0-9A-Fa-f:.]/', '_', $ip) . '.json';
        $now = time();
        $data = [];
        if (is_file($file)) {
            $raw = @file_get_contents($file);
            $data = json_decode($raw ?: '[]', true) ?: [];
            $data = array_filter($data, fn($t) => ($now - (int)$t) <= $windowSec);
        }
        if (count($data) >= $limit) return false;
        $data[] = $now;
        @file_put_contents($file, json_encode(array_values($data)));
        return true;
    }

    private function buildExploreReq(array $keywords, string $geo = 'BR', string $time = 'now 5-y'): array
    {
        $items = [];
        foreach ($keywords as $kw) {
            $kw = trim((string)$kw);
            if ($kw === '') continue;
            $items[] = [ 'keyword' => $kw, 'time' => $time, 'geo' => $geo ];
        }
        return [
            'comparisonItem' => $items,
            'category' => 0,
            'property' => ''
        ];
    }

    public function explore(array $keywords, string $geo = 'BR', string $time = 'now 5-y'): array
    {
        // Garante cookies válidos antes da API
        $this->ensureCookies();
        $req = $this->buildExploreReq($keywords, $geo, $time);
        $cacheKey = 'explore_' . sha1(json_encode([$this->hl, $this->tz, $req]));
        $cached = $this->cacheGet($cacheKey, 43200); // 12h
        if ($cached) return $cached;

        $url = $this->base . '/explore?hl=' . rawurlencode($this->hl) . '&gl=BR&tz=' . $this->tz . '&req=' . rawurlencode(json_encode($req));
        $this->logger->info('[Trends] explore request', ['url' => $url, 'kw' => $keywords, 'geo' => $geo, 'time' => $time]);
        $start = microtime(true);
        [$code, $body, $err] = $this->curlGet($url);
        $ms = (int)round((microtime(true) - $start) * 1000);
        $this->logger->info('[Trends] explore response', ['code' => $code, 'ms' => $ms, 'len' => strlen((string)$body)]);
        if ($code !== 200 || !$body) {
            $snippet = substr((string)$this->stripPreamble($body), 0, 256);
            $this->logger->error('[Trends] explore HTTP error', ['code' => $code, 'err' => $err, 'body' => $snippet]);
            // fallback: tentar domínio .com.br
            $alt = 'https://trends.google.com.br/trends/api/explore?hl=' . rawurlencode($this->hl) . '&gl=BR&tz=' . $this->tz . '&req=' . rawurlencode(json_encode($req));
            $this->logger->info('[Trends] explore retry', ['url' => $alt]);
            $start2 = microtime(true);
            [$code2, $body2, $err2] = $this->curlGet($alt);
            $ms2 = (int)round((microtime(true) - $start2) * 1000);
            $this->logger->info('[Trends] explore retry response', ['code' => $code2, 'ms' => $ms2, 'len' => strlen((string)$body2)]);
            if ($code2 !== 200 || !$body2) {
                $sn2 = substr((string)$this->stripPreamble($body2), 0, 256);
                $this->logger->error('[Trends] explore retry failed', ['code' => $code2, 'err' => $err2, 'body' => $sn2]);
                throw new \RuntimeException('Explore failed: HTTP ' . $code . ' ' . $err);
            }
            $json = json_decode($this->stripPreamble($body2), true);
            if (!$json || empty($json['widgets'])) throw new \RuntimeException('Invalid explore response');
            $this->cachePut($cacheKey, $json);
            return $json;
        }
        $json = json_decode($this->stripPreamble($body), true);
        if (!$json || empty($json['widgets'])) throw new \RuntimeException('Invalid explore response');
        $this->cachePut($cacheKey, $json);
        return $json;
    }

    private function findWidget(array $widgets, array $ids): ?array
    {
        foreach ($widgets as $w) {
            $id = (string)($w['id'] ?? '');
            $title = (string)($w['title'] ?? '');
            foreach ($ids as $needle) {
                if (stripos($id, $needle) !== false || stripos($title, $needle) !== false) {
                    return $w;
                }
            }
        }
        return null;
    }

    private function widgetData(string $path, array $widget, int $ttl = 3600): array
    {
        $reqObj = $widget['request'] ?? [];
        $token = $widget['token'] ?? '';
        if (!$reqObj || !$token) throw new \RuntimeException('Invalid widget: missing token or request');
        $cacheKey = 'wd_' . $path . '_' . sha1(json_encode([$reqObj, $token, $this->hl, $this->tz]));
        $cached = $this->cacheGet($cacheKey, $ttl);
        if ($cached) return $cached;
        $mkUrl = function(string $p) use ($reqObj, $token): string {
            return $this->base . '/widgetdata/' . $p . '?hl=' . rawurlencode($this->hl) . '&tz=' . $this->tz
                . '&req=' . rawurlencode(json_encode($reqObj)) . '&token=' . rawurlencode($token);
        };
        $attempts = [$path];
        // Fallbacks conhecidos
        if ($path === 'multiline') { $attempts[] = 'timeseries'; }
        if ($path === 'relatedsearches') { $attempts[] = 'relatedqueries'; }
        if ($path === 'comparedgeo') { $attempts[] = 'geomap'; }
        $lastErr = '';
        foreach ($attempts as $p) {
            $url = $mkUrl($p);
            $this->logger->info('[Trends] widgetdata request', ['path' => $p, 'ms_token' => strlen($token)]);
            $start = microtime(true);
            [$code, $body, $err] = $this->curlGet($url);
            $ms = (int)round((microtime(true) - $start) * 1000);
            $this->logger->info('[Trends] widgetdata response', ['path' => $p, 'code' => $code, 'ms' => $ms, 'len' => strlen((string)$body)]);
            if ($code === 200 && $body) {
                $json = json_decode($this->stripPreamble($body), true);
                if ($json) {
                    $this->cachePut($cacheKey, $json);
                    return $json;
                }
                $lastErr = 'Invalid JSON';
            } else {
                $lastErr = 'HTTP ' . $code . ' ' . $err;
            }
        }
        throw new \RuntimeException('Widget ' . $path . ' failed: ' . $lastErr);
    }

    public function timeseries(array $keywords, string $geo = 'BR', string $time = 'now 5-y'): array
    {
        $explore = $this->explore($keywords, $geo, $time);
        $w = $this->findWidget($explore['widgets'] ?? [], ['TIMESERIES','TIME_SERIES','MULTILINE']);
        if (!$w) throw new \RuntimeException('Timeseries widget not found');
        return $this->widgetData('multiline', $w, 1800); // 30min
    }

    public function comparedGeo(array $keywords, string $geo = 'BR', string $time = 'now 5-y'): array
    {
        $explore = $this->explore($keywords, $geo, $time);
        $w = $this->findWidget($explore['widgets'] ?? [], ['GEO_MAP','GEO_CHART','REGION']);
        if (!$w) throw new \RuntimeException('Geo widget not found');
        return $this->widgetData('comparedgeo', $w, 1800);
    }

    public function related(array $keywords, string $geo = 'BR', string $time = 'now 5-y'): array
    {
        $explore = $this->explore($keywords, $geo, $time);
        $w = $this->findWidget($explore['widgets'] ?? [], ['RELATED_QUERIES','RELATED_TOPICS']);
        if (!$w) throw new \RuntimeException('Related widget not found');
        return $this->widgetData('relatedsearches', $w, 1800);
    }
}
