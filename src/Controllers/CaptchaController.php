<?php

namespace App\Controllers;

use OpereBem\Captcha\Config;
use OpereBem\Captcha\Generator;
use OpereBem\Captcha\Validator;
use OpereBem\Captcha\Security;

class CaptchaController extends BaseController
{
    private function ensureLibLoaded(): void
    {
        if (!class_exists('OpereBem\\Captcha\\Security')) {
            $libBase = dirname(__DIR__, 2) . '/vendor/operebem/captcha/src/Captcha';
            @require_once $libBase . '/Config.php';
            @require_once $libBase . '/Security.php';
            @require_once $libBase . '/Generator.php';
            @require_once $libBase . '/Validator.php';
        }
    }
    private function ensureSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
            if (PHP_VERSION_ID >= 70300) {
                session_set_cookie_params([
                    'httponly' => true,
                    'secure' => $secure,
                    'samesite' => 'Lax',
                ]);
            } else {
                session_set_cookie_params(0, '/; samesite=Lax', '', $secure, true);
            }
            session_start();
        }
    }

    public function embed(): void
    {
        $base = __DIR__ . '/../../vendor/operebem/captcha';
        $file = $base . '/public/embed.html';
        if (is_file($file)) {
            header('X-Frame-Options: SAMEORIGIN');
            header("Content-Security-Policy: frame-ancestors 'self'");
            header('Content-Type: text/html; charset=utf-8');
            header('Cache-Control: public, max-age=600');
            readfile($file);
            return;
        }
        http_response_code(404); echo 'Not found';
    }

    public function generate(): void
    {
        $this->ensureLibLoaded();
        $this->ensureSession();
        // Headers de segurança
        Security::applySecurityHeaders();
        // CORS
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        if (Security::validateDomain($origin)) {
            header("Access-Control-Allow-Origin: $origin");
        }
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: 86400');
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') { http_response_code(200); return; }
        header('Content-Type: application/json; charset=utf-8');

        if (Security::isSuspiciousRequest()) {
            Security::logSecurityEvent('suspicious_request');
            http_response_code(403);
            echo json_encode(['error' => 'Requisição suspeita detectada']);
            return;
        }

        $clientIP = Security::getClientIP();
        if (Security::isIPBlacklisted($clientIP)) {
            Security::logSecurityEvent('blacklisted_ip', ['ip' => $clientIP]);
            http_response_code(403);
            echo json_encode(['error' => 'Acesso negado']);
            return;
        }

        $isDevelopment = in_array(($_SERVER['SERVER_NAME'] ?? ''), ['localhost', '127.0.0.1', '::1'], true);
        if (!$isDevelopment) {
            $ipRateLimit = Security::checkIPRateLimit($clientIP);
            if (!$ipRateLimit['allowed']) { http_response_code(429); echo json_encode(['error' => $ipRateLimit['message'], 'ip_attempts' => ($ipRateLimit['attempts'] ?? null), 'seconds_remaining' => ($ipRateLimit['seconds_remaining'] ?? null)]); return; }
            $globalRateLimit = Security::checkGlobalRateLimit();
            if (!$globalRateLimit['allowed']) { http_response_code(429); echo json_encode(['error' => $globalRateLimit['message'], 'seconds_remaining' => ($globalRateLimit['seconds_remaining'] ?? null)]); return; }
        }

        try {
            $config = Config::load();
            $validation = Config::validate();
            if (!empty($validation['errors'])) { http_response_code(500); echo json_encode(['error' => 'Configuração inválida', 'details' => $validation['errors']]); return; }
            $theme = $_GET['theme'] ?? $_POST['theme'] ?? $config['ui']['theme'];
            if (!in_array($theme, Config::THEMES, true)) { $theme = Config::DEFAULT_THEME; }
            // Preflight: retornar somente ok sem gerar puzzle e sem incrementar rate
            $preflight = isset($_GET['preflight']) || isset($_POST['preflight']);
            if ($preflight) {
                echo json_encode(['ok' => true, 'preflight' => true, 'theme' => $theme]);
                return;
            }
            $generator = new Generator();
            $result = $generator->generate($theme);
            Security::incrementRateLimitCounters($clientIP);
            Security::logSecurityEvent('captcha_generated', [ 'theme' => $theme, 'token' => $result['token'] ]);
            echo json_encode($result);
        } catch (\Throwable $e) {
            Security::logSecurityEvent('captcha_generation_error', [ 'error' => $e->getMessage() ]);
            http_response_code(500);
            echo json_encode(['error' => 'Erro interno do servidor']);
        }
    }

    public function verify(): void
    {
        $this->ensureLibLoaded();
        $this->ensureSession();
        Security::applySecurityHeaders();
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        if (Security::validateDomain($origin)) { header("Access-Control-Allow-Origin: $origin"); }
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: 86400');
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') { http_response_code(200); return; }
        header('Content-Type: application/json; charset=utf-8');

        if (Security::isSuspiciousRequest()) { Security::logSecurityEvent('suspicious_request'); http_response_code(403); echo json_encode(['ok' => false, 'msg' => 'Requisição suspeita detectada']); return; }
        $clientIP = Security::getClientIP();
        if (Security::isIPBlacklisted($clientIP)) { Security::logSecurityEvent('blacklisted_ip', ['ip' => $clientIP]); http_response_code(403); echo json_encode(['ok' => false, 'msg' => 'Acesso negado']); return; }
        $isDevelopment = in_array(($_SERVER['SERVER_NAME'] ?? ''), ['localhost', '127.0.0.1', '::1'], true);
        if (!$isDevelopment) {
            $ipRateLimit = Security::checkIPRateLimit($clientIP);
            if (!$ipRateLimit['allowed']) { http_response_code(429); echo json_encode(['ok' => false, 'msg' => $ipRateLimit['message'], 'ip_attempts' => $ipRateLimit['attempts']]); return; }
            $globalRateLimit = Security::checkGlobalRateLimit();
            if (!$globalRateLimit['allowed']) { http_response_code(429); echo json_encode(['ok' => false, 'msg' => $globalRateLimit['message']]); return; }
        }

        try {
            $raw = file_get_contents('php://input');
            $data = json_decode($raw, true);
            if (!$data || !isset($data['token']) || !isset($data['x'])) { http_response_code(400); echo json_encode(['ok' => false, 'msg' => 'Dados inválidos']); return; }
            $token = preg_replace('/[^a-f0-9]/', '', $data['token']);
            $x = (int)$data['x'];
            $dragDuration = (int)($data['dt'] ?? $data['time'] ?? 0);
            // Honeypot invisível: se preenchido, rejeitar
            $hp = isset($data['hp']) ? trim((string)$data['hp']) : '';
            if ($hp !== '') {
                Security::logSecurityEvent('captcha_honeypot_triggered', [ 'ip' => $clientIP ]);
                http_response_code(400);
                echo json_encode(['ok' => false, 'msg' => 'Requisição inválida']);
                return;
            }
            $validator = new Validator();
            $result = $validator->validate($token, $x, $dragDuration);
            Security::incrementRateLimitCounters($clientIP);
            Security::logSecurityEvent('captcha_validation', [ 'token' => $token, 'success' => $result['ok'], 'attempts' => $result['attempts'] ?? 0, 'ip' => $clientIP ]);
            if ($result['ok']) {
                if (!isset($_SESSION['captcha_ok_tokens']) || !is_array($_SESSION['captcha_ok_tokens'])) { $_SESSION['captcha_ok_tokens'] = []; }
                $_SESSION['captcha_ok_tokens'][$token] = time();
                $_SESSION['captcha_passed'] = true;
                $_SESSION['captcha_passed_time'] = time();
            }
            echo json_encode($result);
        } catch (\Throwable $e) {
            Security::logSecurityEvent('captcha_validation_error', [ 'error' => $e->getMessage() ]);
            http_response_code(500);
            echo json_encode(['ok' => false, 'msg' => 'Erro interno do servidor']);
        }
    }

    public function config(): void
    {
        $this->ensureLibLoaded();
        Security::applySecurityHeaders();
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        if (Security::validateDomain($origin)) { header("Access-Control-Allow-Origin: $origin"); }
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: 86400');
        header('Content-Type: application/json; charset=utf-8');
        try {
            $config = Config::load();
            echo json_encode([
                'ok' => true,
                'canvas_width' => $config['canvas']['width'],
                'canvas_height' => $config['canvas']['height'],
                'piece_width' => $config['piece']['width'],
                'piece_height' => $config['piece']['height'],
                'max_attempts' => $config['validation']['max_attempts'],
                'tolerance' => $config['validation']['base_tolerance'],
                'shapes' => $config['shapes'],
                'themes' => array_keys($config['themes']),
                'version' => '2.0.0'
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            Security::logSecurityEvent('config_error', [ 'error' => $e->getMessage() ]);
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => 'Erro interno do servidor']);
        }
    }
}
