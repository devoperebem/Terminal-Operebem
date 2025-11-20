<?php

namespace App\Controllers;

use App\Services\HttpProxyService;

class FedProxyController extends BaseController
{
    private HttpProxyService $proxy;

    public function __construct()
    {
        $this->proxy = new HttpProxyService();
    }

    // Proxied HTML entry; rewrites asset links to asset endpoint
    public function html(): void
    {
        $u = isset($_GET['u']) ? (string)$_GET['u'] : '';
        if ($u === '') { http_response_code(400); echo 'Missing url'; return; }
        [$code, $body] = $this->proxy->fetch($u);
        if ($code !== 200 || !$body) { http_response_code(502); echo 'Bad upstream'; return; }
        $assetEndpoint = '/proxy/fedwatch/asset';
        $html = $this->proxy->rewriteHtmlForProxy($body, $u, $assetEndpoint);
        header('Content-Type: text/html; charset=utf-8');
        // Allow this endpoint to be framed by same-origin (Apache .htaccess also whitelists this path)
        header('X-Frame-Options: SAMEORIGIN');
        header("Content-Security-Policy: frame-ancestors 'self'");
        echo $html;
    }

    // Proxied assets (css/js/img); streams with basic content type sniffing
    public function asset(): void
    {
        $u = isset($_GET['u']) ? (string)$_GET['u'] : '';
        if ($u === '') { http_response_code(400); echo 'Missing url'; return; }
        [$code, $body, $hdrs] = $this->proxy->fetch($u, [ 'Accept' => '*/*' ]);
        if ($code !== 200 || $body === '') { http_response_code(502); echo 'Bad upstream'; return; }
        $ct = $hdrs['content-type'] ?? '';
        if ($ct === '') {
            // naive sniff
            if (preg_match('/\.css($|\?)/i', $u)) $ct = 'text/css';
            elseif (preg_match('/\.js($|\?)/i', $u)) $ct = 'application/javascript';
            elseif (preg_match('/\.(png|jpg|jpeg|gif|webp|svg)($|\?)/i', $u, $m)) {
                $ext = strtolower($m[1]);
                $map = [ 'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'gif' => 'image/gif', 'webp' => 'image/webp', 'svg' => 'image/svg+xml' ];
                $ct = $map[$ext] ?? 'application/octet-stream';
            } else { $ct = 'application/octet-stream'; }
        }
        header('Content-Type: ' . $ct);
        echo $body;
    }
}
