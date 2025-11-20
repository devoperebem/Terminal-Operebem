<?php

namespace App\Services;

use App\Core\Application;

class FedWatchService
{
    private string $baseUrl;
    private string $cacheDir;
    private int $ttl;

    public function __construct()
    {
        $this->baseUrl = $_ENV['FEDWATCH_URL'] ?? 'https://www.cmegroup.com/CmeWS/mvc/RateWatch/FedFundsProbabilityCalc';
        $this->cacheDir = dirname(__DIR__, 1) . '/../storage/cache/fedwatch';
        if (!is_dir($this->cacheDir)) { @mkdir($this->cacheDir, 0775, true); }
        $this->ttl = (int)($_ENV['FEDWATCH_TTL'] ?? 600); // 10 min padrão
    }

    public function getProbabilities(?string $month = null): array
    {
        $params = [
            'exchangeCode' => 'CBT',
            'commodityId' => '536',
            'currSession' => 'GLOBAL',
            'unit' => '0',
        ];
        if ($month) { $params['selectedMonth'] = $month; }
        $url = $this->baseUrl . '?' . http_build_query($params);
        $cacheKey = sha1($url);
        $cacheFile = $this->cacheDir . '/' . $cacheKey . '.json';
        if (is_file($cacheFile) && (time() - (int)@filemtime($cacheFile) < $this->ttl)) {
            $body = (string)@file_get_contents($cacheFile);
            $json = json_decode($body, true);
            if (is_array($json)) return $json;
        }
        [$code, $body] = $this->httpGet($url);
        if ($code !== 200 || !$body) throw new \RuntimeException('Upstream error');
        $json = json_decode($body, true);
        if (!is_array($json)) throw new \RuntimeException('Invalid upstream');
        @file_put_contents($cacheFile, json_encode($json));
        return $json;
    }

    private function httpGet(string $url): array
    {
        if (!function_exists('curl_init')) {
            $ctx = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'header' => "User-Agent: Mozilla/5.0\r\nAccept: application/json\r\n",
                    'timeout' => 12,
                ]
            ]);
            $body = @file_get_contents($url, false, $ctx);
            return [ $body ? 200 : 500, $body ?: '' ];
        }
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 12,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_HTTPHEADER => [
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/118 Safari/537.36',
                'Accept: application/json, text/plain, */*',
                'Referer: https://www.cmegroup.com/',
                'Origin: https://www.cmegroup.com'
            ]
        ]);
        $body = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return [$code, (string)$body];
    }
}
