<?php

namespace App\Services;

use App\Core\Application;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtService
{
    private string $secret;
    private string $issuer;
    private string $audience;
    private int $accessTtl;
    private int $refreshTtl;

    public function __construct()
    {
        $app = Application::getInstance();
        // Secret prioritiza ENV; caso ausente, persistir em storage/keys/admin_jwt.key
        $secret = $_ENV['ADMIN_JWT_SECRET'] ?? ($_SERVER['ADMIN_JWT_SECRET'] ?? '');
        if (!$secret) {
            try {
                $root = dirname(__DIR__, 2); // .../novo_public_html
                $keyDir = $root . '/storage/keys';
                $keyFile = $keyDir . '/admin_jwt.key';
                if (!is_dir($keyDir)) { @mkdir($keyDir, 0700, true); }
                if (is_file($keyFile) && is_readable($keyFile)) {
                    $secret = trim((string)@file_get_contents($keyFile));
                }
                if (!$secret) {
                    $secret = bin2hex(random_bytes(32));
                    @file_put_contents($keyFile, $secret, LOCK_EX);
                    @chmod($keyFile, 0600);
                }
            } catch (\Throwable $t) {
                // fallback não persistente
                $secret = bin2hex(random_bytes(32));
            }
        }
        $this->secret = $secret;

        // Definir issuer dinamicamente se APP_URL não estiver setado
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'terminal.operebem.com.br';
        $baseUrl = $scheme . '://' . $host;
        $this->issuer = $_ENV['APP_URL'] ?? $baseUrl;
        $this->audience = $this->issuer . '/secure/adm';
        $this->accessTtl = (int)($_ENV['ADMIN_JWT_ACCESS_TTL'] ?? 600); // 10 min
        $this->refreshTtl = (int)($_ENV['ADMIN_JWT_REFRESH_TTL'] ?? 2592000); // 30 dias
    }

    public function issueAccessToken(array $claims): string
    {
        $now = time();
        $payload = array_merge([
            'iss' => $this->issuer,
            'aud' => $this->audience,
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + $this->accessTtl,
            'typ' => 'access'
        ], $claims);
        return JWT::encode($payload, $this->secret, 'HS256');
    }

    public function issueRefreshToken(array $claims): array
    {
        $now = time();
        $jti = bin2hex(random_bytes(16));
        $payload = array_merge([
            'iss' => $this->issuer,
            'aud' => $this->audience,
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + $this->refreshTtl,
            'jti' => $jti,
            'typ' => 'refresh'
        ], $claims);
        $token = JWT::encode($payload, $this->secret, 'HS256');
        return ['token' => $token, 'jti' => $jti, 'exp' => $payload['exp']];
    }

    public function decode(string $jwt): array
    {
        $decoded = JWT::decode($jwt, new Key($this->secret, 'HS256'));
        return (array)$decoded;
    }
}
