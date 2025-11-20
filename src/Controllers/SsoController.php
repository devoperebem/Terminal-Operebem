<?php
namespace App\Controllers;

use App\Core\Application;
use App\Core\Database;

class SsoController extends BaseController
{
    private function b64urlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    public function start(): void
    {
        // Pode ser acessado por guest; se não autenticado, guardar next_url e enviar para modal de login
        $userId = (int)($_SESSION['user_id'] ?? 0);
        $return = (string)($_GET['return'] ?? '');
        if ($userId <= 0) {
            $qs = '/sso/start';
            if ($return !== '') { $qs .= '?return=' . urlencode($return); }
            $_SESSION['next_url'] = $qs;
            $this->redirect('/?modal=login');
        }

        $user = Database::fetch('SELECT id, email FROM users WHERE id = ? AND deleted_at IS NULL', [$userId]);
        if (!$user) {
            $this->redirect('/logout');
        }

        $secret = trim((string)($_ENV['SSO_SHARED_SECRET'] ?? ''));
        if ($secret === '') {
            http_response_code(500);
            echo 'SSO não configurado';
            exit;
        }

        $iss = (string)($_ENV['SSO_ISSUER'] ?? Application::getInstance()->config('app.url') ?? 'https://terminal.operebem.com.br');
        $aud = (string)($_ENV['SSO_AUDIENCE'] ?? 'https://aluno.operebem.com.br');
        $ttl = (int)($_ENV['SSO_TTL'] ?? 60);
        $ttl = max(10, min(600, $ttl));
        $now = time();

        $payload = [
            'iss' => $iss,
            'aud' => $aud,
            'sub' => (int)$user['id'],
            'email' => (string)$user['email'],
            'iat' => $now,
            'exp' => $now + $ttl,
            'jti' => bin2hex(random_bytes(16))
        ];
        $header = ['alg' => 'HS256', 'typ' => 'JWT'];
        $h64 = $this->b64urlEncode(json_encode($header, JSON_UNESCAPED_SLASHES));
        $p64 = $this->b64urlEncode(json_encode($payload, JSON_UNESCAPED_SLASHES));
        $sig = hash_hmac('sha256', $h64 . '.' . $p64, $secret, true);
        $s64 = $this->b64urlEncode($sig);
        $jwt = $h64 . '.' . $p64 . '.' . $s64;

        $audBase = rtrim($aud, '/');
        $callback = $audBase . '/sso/callback?token=' . urlencode($jwt);
        if ($return !== '') {
            $retHost = parse_url($return, PHP_URL_HOST);
            $audHost = parse_url($audBase, PHP_URL_HOST);
            if ($retHost === null || ($audHost && strcasecmp($retHost, $audHost) === 0)) {
                $callback .= '&return=' . urlencode($return);
            }
        }

        header('Location: ' . $callback, true, 302);
        exit;
    }
}
