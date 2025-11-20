<?php

namespace App\Services;

use App\Core\Application;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class UserJwtService
{
    private string $secret;
    private string $issuer;
    private string $audience;
    private int $accessTtl;
    private int $refreshTtl;

    public function __construct()
    {
        $app = Application::getInstance();
        $cfg = $app->config('app.jwt');
        $this->secret = (string)($cfg['secret'] ?? '');
        if ($this->secret === '') {
            throw new \RuntimeException('JWT secret not configured. Set JWT_SECRET in your environment.');
        }
        $this->issuer = (string)($cfg['issuer'] ?? ($app->config('app.url') ?? 'https://terminal.operebem.com.br'));
        $this->audience = (string)($cfg['audience'] ?? $this->issuer);
        $this->accessTtl = (int)($cfg['expiration'] ?? 600);
        $this->refreshTtl = (int)($_ENV['USER_JWT_REFRESH_TTL'] ?? 2592000); // 30 dias
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
