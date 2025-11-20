<?php

namespace App\Controllers;

use App\Services\UsMarketBarometerService;

class UsMarketBarometerController extends BaseController
{
    private UsMarketBarometerService $service;

    public function __construct(?UsMarketBarometerService $service = null)
    {
        parent::__construct();
        $this->service = $service ?? new UsMarketBarometerService();
    }

    public function data(): void
    {
        header('Content-Type: application/json');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: no-referrer');
        header('X-Frame-Options: DENY');
        header('X-Robots-Tag: noindex, nofollow');
        // CORS restrito ao mesmo host
        $origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
        $host = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? '');
        if ($origin && stripos($origin, $host) === 0) {
            header('Access-Control-Allow-Origin: ' . $origin);
            header('Vary: Origin');
        }

        // Requer requisição AJAX do mesmo site (mínimo de proteção contra scraping cross-site)
        $xhr = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
        $fetchSite = $_SERVER['HTTP_SEC_FETCH_SITE'] ?? '';
        if ($xhr !== 'XMLHttpRequest' || ($fetchSite && !in_array(strtolower($fetchSite), ['same-origin','same-site']))) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Forbidden']);
            return;
        }

        if (!$this->rateLimitCheck()) {
            http_response_code(429);
            echo json_encode(['success' => false, 'message' => 'Too Many Requests']);
            return;
        }
        $res = $this->service->getData();
        // If v1 shape { success: true, data: { date, scraped_at, data: {...}, metadata: {...} } }
        if (is_array($res) && isset($res['success']) && isset($res['data']) && is_array($res['data'])) {
            $d = $res['data'];
            $categories = $d['data'] ?? [];
            $last = $d['metadata']['last_updated'] ?? ($d['scraped_at'] ?? ($d['date'] ?? null));
            echo json_encode([
                'data' => $categories,
                'last_updated' => $last,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            return;
        }
        // Otherwise, pass-through (legacy /data already matches expected)
        if (!is_array($res) || !isset($res['data'])) {
            http_response_code(502);
            echo json_encode(['success' => false, 'message' => 'USMB upstream error'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            return;
        }
        echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function rateLimitCheck(): bool
    {
        $uid = isset($_SESSION['user_id']) ? (string)$_SESSION['user_id'] : 'guest';
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $base = dirname(__DIR__, 2) . '/storage/cache/usmb_rl';
        if (!is_dir($base)) { @mkdir($base, 0755, true); }
        $fileUser = $base . '/' . sha1('u:' . $uid) . '.json';
        $fileIp = $base . '/' . sha1('i:' . $ip) . '.json';
        $now = time();
        $userData = ['t' => $now, 'c' => 0];
        if (is_file($fileUser)) {
            $raw = @file_get_contents($fileUser);
            if ($raw !== false) {
                $tmp = json_decode($raw, true);
                if (is_array($tmp) && isset($tmp['t']) && isset($tmp['c'])) { $userData = $tmp; }
            }
        }
        if (($now - (int)$userData['t']) >= 60) { $userData['t'] = $now; $userData['c'] = 0; }
        $userData['c'] = (int)$userData['c'] + 1;
        @file_put_contents($fileUser, json_encode($userData));

        $ipData = ['t' => $now, 'c' => 0];
        if (is_file($fileIp)) {
            $raw = @file_get_contents($fileIp);
            if ($raw !== false) {
                $tmp = json_decode($raw, true);
                if (is_array($tmp) && isset($tmp['t']) && isset($tmp['c'])) { $ipData = $tmp; }
            }
        }
        if (($now - (int)$ipData['t']) >= 60) { $ipData['t'] = $now; $ipData['c'] = 0; }
        $ipData['c'] = (int)$ipData['c'] + 1;
        @file_put_contents($fileIp, json_encode($ipData));

        $userLimit = 120; // por usuário/sessão
        $ipLimit = 240;   // por IP
        return ($userData['c'] <= $userLimit) && ($ipData['c'] <= $ipLimit);
    }
}
