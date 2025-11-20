<?php

namespace App\Middleware;

class SameOriginAjaxMiddleware
{
    public function handle(): bool
    {
        $xhr = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
        $site = strtolower($_SERVER['HTTP_SEC_FETCH_SITE'] ?? '');
        if ($xhr !== 'XMLHttpRequest' || ($site && !in_array($site, ['same-origin','same-site'], true))) {
            http_response_code(403);
            echo 'Forbidden';
            exit;
        }

        $ref = $_SERVER['HTTP_REFERER'] ?? '';
        if ($ref) {
            $refHost = parse_url($ref, PHP_URL_HOST) ?: '';
            $curHost = $_SERVER['HTTP_HOST'] ?? '';
            if ($refHost && $curHost && strcasecmp($refHost, $curHost) !== 0) {
                http_response_code(403);
                echo 'Forbidden';
                exit;
            }
        }

        $uid = isset($_SESSION['user_id']) ? (string)$_SESSION['user_id'] : 'guest';
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $base = dirname(__DIR__, 2) . '/storage/cache/quotes_public_rl';
        if (!is_dir($base)) { @mkdir($base, 0755, true); }
        $fu = $base . '/' . sha1('u:' . $uid) . '.json';
        $fi = $base . '/' . sha1('i:' . $ip) . '.json';
        $now = time();

        $u = ['t' => $now, 'c' => 0];
        if (is_file($fu)) {
            $raw = @file_get_contents($fu);
            if ($raw !== false) {
                $tmp = json_decode($raw, true);
                if (is_array($tmp) && isset($tmp['t']) && isset($tmp['c'])) { $u = $tmp; }
            }
        }
        if (($now - (int)$u['t']) >= 60) { $u['t'] = $now; $u['c'] = 0; }
        $u['c'] = (int)$u['c'] + 1;
        @file_put_contents($fu, json_encode($u));

        $i = ['t' => $now, 'c' => 0];
        if (is_file($fi)) {
            $raw = @file_get_contents($fi);
            if ($raw !== false) {
                $tmp = json_decode($raw, true);
                if (is_array($tmp) && isset($tmp['t']) && isset($tmp['c'])) { $i = $tmp; }
            }
        }
        if (($now - (int)$i['t']) >= 60) { $i['t'] = $now; $i['c'] = 0; }
        $i['c'] = (int)$i['c'] + 1;
        @file_put_contents($fi, json_encode($i));

        $ul = 180;
        $il = 360;
        if (!($u['c'] <= $ul && $i['c'] <= $il)) {
            http_response_code(429);
            echo 'Too Many Requests';
            exit;
        }

        return true;
    }
}
