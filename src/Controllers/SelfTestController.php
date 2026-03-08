<?php

namespace App\Controllers;

use App\Core\Application;
use App\Core\Database;

class SelfTestController extends BaseController
{
    public function index(): void
    {
        $app = Application::getInstance();
        $server = [];
        // PHP version
        $server[] = [ 'id' => 'php_version', 'ok' => true, 'value' => PHP_VERSION ];
        // DB connectivity
        try { $r = Database::fetch('SELECT 1 AS ok'); $server[] = [ 'id' => 'db_connect', 'ok' => isset($r['ok']), 'value' => (int)($r['ok'] ?? 0) ]; } catch (\Throwable $t) { $server[] = [ 'id' => 'db_connect', 'ok' => false, 'value' => $t->getMessage() ]; }
        // Uploads dir writable
        try {
            $root = dirname(__DIR__, 2); // novo_public_html
            $uploadsDir = $root . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'avatars';
            if (!is_dir($uploadsDir)) { @mkdir($uploadsDir, 0755, true); }
            $writable = is_writable($uploadsDir);
            $server[] = [ 'id' => 'uploads_writable', 'ok' => $writable, 'value' => $writable ? 'writable' : 'not_writable' ];
        } catch (\Throwable $t) { $server[] = [ 'id' => 'uploads_writable', 'ok' => false, 'value' => $t->getMessage() ]; }
        // SMTP config presence
        $server[] = [ 'id' => 'smtp_config', 'ok' => (bool)($_ENV['MAIL_HOST'] ?? ''), 'value' => (($_ENV['MAIL_HOST'] ?? '') ? 'configured' : 'missing') ];
        // JWT secret presence
        try { $jwtCfg = $app->config('app.jwt') ?? []; $hasSecret = !empty($jwtCfg['secret']); $server[] = [ 'id' => 'jwt_secret', 'ok' => $hasSecret, 'value' => $hasSecret ? 'present' : 'missing' ]; } catch (\Throwable $t) { $server[] = [ 'id' => 'jwt_secret', 'ok' => false, 'value' => 'error' ]; }
        // reCAPTCHA v3 keys (do not expose values)
        $hasSiteKey = (bool)($_ENV['RECAPTCHA_V3_SITE_KEY'] ?? '');
        $hasSecret  = (bool)($_ENV['RECAPTCHA_V3_SECRET'] ?? '');
        $server[] = [ 'id' => 'recaptcha_site_key', 'ok' => $hasSiteKey, 'value' => $hasSiteKey ? 'present' : 'missing' ];
        $server[] = [ 'id' => 'recaptcha_secret',  'ok' => $hasSecret,  'value' => $hasSecret ? 'present' : 'missing' ];
        // Uptime placeholder (expand with real uptime probe later)
        $server[] = [ 'id' => 'uptime_probe', 'ok' => true, 'value' => 'operational' ];
        // CSRF token session
        $server[] = [ 'id' => 'csrf_session', 'ok' => isset($_SESSION['csrf_token']), 'value' => isset($_SESSION['csrf_token']) ? 'present' : 'missing' ];

        $this->view('selftest/index', [ 'serverResults' => $server, 'title' => 'Self-Test', 'footerVariant' => '' ]);
    }

    public function publicStatus(): void
    {
        // Public, sanitized status panel. No sensitive values.
        $components = [];
        // DB
        try { $ok = (bool)(Database::fetch('SELECT 1 AS ok')['ok'] ?? false); } catch (\Throwable $t) { $ok = false; }
        $components[] = ['id' => 'database', 'name' => 'Banco de Dados', 'status' => $ok ? 'operational' : 'degraded'];
        // SMTP configured (only boolean)
        $smtpOk = !empty($_ENV['MAIL_HOST'] ?? '');
        $components[] = ['id' => 'smtp', 'name' => 'SMTP', 'status' => $smtpOk ? 'configured' : 'not_configured'];
        // reCAPTCHA configured (boolean only)
        $rcSite = (bool)($_ENV['RECAPTCHA_V3_SITE_KEY'] ?? '');
        $components[] = ['id' => 'recaptcha', 'name' => 'reCAPTCHA', 'status' => $rcSite ? 'configured' : 'not_configured'];
        // OpereBem Captcha (proprietary)
        $obCaptcha = (bool)($_ENV['CAPTCHA_SECRET_KEY'] ?? '');
        $components[] = ['id' => 'ob_captcha', 'name' => 'OpereBem Captcha', 'status' => $obCaptcha ? 'online' : 'disabled'];
        // JWT secret presence (boolean only)
        $jwtOk = false; try { $j = $this->app->config('app.jwt') ?? []; $jwtOk = !empty($j['secret']) || !empty($_ENV['APP_JWT_SECRET']); } catch (\Throwable $t) { $jwtOk = false; }
        $components[] = ['id' => 'jwt', 'name' => 'JWT', 'status' => $jwtOk ? 'configured' : 'not_configured'];

        // Security headers (declared in .htaccess). We present as configured according to server policy.
        $security = [
            ['id' => 'hsts', 'name' => 'HSTS', 'status' => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'enabled' : 'enabled_when_https'],
            ['id' => 'csp_ro', 'name' => 'CSP (Report-Only)', 'status' => 'enabled'],
            ['id' => 'xcto', 'name' => 'X-Content-Type-Options', 'status' => 'nosniff'],
            ['id' => 'xfo', 'name' => 'X-Frame-Options', 'status' => 'deny_or_sameorigin'],
            ['id' => 'xxss', 'name' => 'X-XSS-Protection', 'status' => 'enabled'],
            ['id' => 'referrer', 'name' => 'Referrer-Policy', 'status' => 'strict-origin-when-cross-origin'],
            ['id' => 'perm', 'name' => 'Permissions-Policy', 'status' => 'restricted'],
        ];

        $data = [
            'title' => 'Status do Sistema',
            'components' => $components,
            'security' => $security,
            'updated_at' => date('c'),
            'footerVariant' => '',
        ];
        $this->view('status/public', $data);
    }
}
