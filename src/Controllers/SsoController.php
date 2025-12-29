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

    /**
     * Retorna o tier efetivo do usuário, verificando se a assinatura expirou
     * Se expirou, retorna 'FREE' ao invés do tier armazenado
     * @param string $tier Tier armazenado no banco
     * @param string|null $expiresAt Data de expiração (null = sem expiração)
     * @return string Tier efetivo (FREE, PLUS ou PRO)
     */
    private function getEffectiveTier(string $tier, ?string $expiresAt): string
    {
        $tier = strtoupper($tier ?: 'FREE');
        
        // FREE nunca expira
        if ($tier === 'FREE') {
            return 'FREE';
        }
        
        // Se não tem data de expiração, é vitalício
        if ($expiresAt === null || $expiresAt === '') {
            return $tier;
        }
        
        // Verifica se expirou
        $expirationTime = strtotime($expiresAt);
        if ($expirationTime !== false && $expirationTime < time()) {
            // Expirou! Retorna FREE
            return 'FREE';
        }
        
        return $tier;
    }

    /**
     * Gera um token JWT SSO para o usuário autenticado
     * @param int $userId ID do usuário
     * @param string $email Email do usuário
     * @param string $tier Tier do usuário (FREE, PLUS, PRO)
     * @param string $secret Chave secreta compartilhada
     * @param string $iss Issuer (Terminal)
     * @param string $aud Audience (sistema destino)
     * @param int $ttl Tempo de vida do token em segundos
     * @return string Token JWT
     */
    private function generateToken(int $userId, string $email, string $tier, string $secret, string $iss, string $aud, int $ttl): string
    {
        $now = time();
        $payload = [
            'iss' => $iss,
            'aud' => $aud,
            'sub' => $userId,
            'email' => $email,
            'tier' => $tier,
            'iat' => $now,
            'exp' => $now + $ttl,
            'jti' => bin2hex(random_bytes(16))
        ];
        $header = ['alg' => 'HS256', 'typ' => 'JWT'];
        $h64 = $this->b64urlEncode(json_encode($header, JSON_UNESCAPED_SLASHES));
        $p64 = $this->b64urlEncode(json_encode($payload, JSON_UNESCAPED_SLASHES));
        $sig = hash_hmac('sha256', $h64 . '.' . $p64, $secret, true);
        $s64 = $this->b64urlEncode($sig);
        return $h64 . '.' . $p64 . '.' . $s64;
    }

    /**
     * SSO Start para Portal do Aluno (comportamento original)
     * GET /sso/start
     */
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

        $user = Database::fetch('SELECT id, email, tier, subscription_expires_at FROM users WHERE id = ? AND deleted_at IS NULL', [$userId]);
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

        $userTier = $this->getEffectiveTier($user['tier'] ?? 'FREE', $user['subscription_expires_at'] ?? null);
        $jwt = $this->generateToken((int)$user['id'], (string)$user['email'], $userTier, $secret, $iss, $aud, $ttl);

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

    /**
     * SSO Start para Diário Operebem
     * GET /sso/diario/start
     * Usa variáveis SSO_DIARIO_* ou fallback para SSO_* com audience do Diário
     */
    public function diarioStart(): void
    {
        $userId = (int)($_SESSION['user_id'] ?? 0);
        $return = (string)($_GET['return'] ?? '');
        
        if ($userId <= 0) {
            $qs = '/sso/diario/start';
            if ($return !== '') { $qs .= '?return=' . urlencode($return); }
            $_SESSION['next_url'] = $qs;
            $this->redirect('/?modal=login');
        }

        $user = Database::fetch('SELECT id, email, tier, subscription_expires_at FROM users WHERE id = ? AND deleted_at IS NULL', [$userId]);
        if (!$user) {
            $this->redirect('/logout');
        }

        // Configuração específica do Diário (com fallback para configuração padrão)
        $secret = trim((string)($_ENV['SSO_DIARIO_SECRET'] ?? $_ENV['SSO_SHARED_SECRET'] ?? ''));
        if ($secret === '') {
            http_response_code(500);
            echo 'SSO Diário não configurado';
            exit;
        }

        $iss = (string)($_ENV['SSO_DIARIO_ISSUER'] ?? $_ENV['SSO_ISSUER'] ?? Application::getInstance()->config('app.url') ?? 'https://terminal.operebem.com.br');
        $aud = (string)($_ENV['SSO_DIARIO_AUDIENCE'] ?? 'https://diario.operebem.com.br');
        $ttl = (int)($_ENV['SSO_DIARIO_TTL'] ?? $_ENV['SSO_TTL'] ?? 60);
        $ttl = max(10, min(600, $ttl));

        $userTier = $this->getEffectiveTier($user['tier'] ?? 'FREE', $user['subscription_expires_at'] ?? null);
        $jwt = $this->generateToken((int)$user['id'], (string)$user['email'], $userTier, $secret, $iss, $aud, $ttl);

        $audBase = rtrim($aud, '/');
        $callback = $audBase . '/sso/callback?token=' . urlencode($jwt);
        if ($return !== '') {
            $retHost = parse_url($return, PHP_URL_HOST);
            $audHost = parse_url($audBase, PHP_URL_HOST);
            if ($retHost === null || ($audHost && strcasecmp($retHost, $audHost) === 0)) {
                $callback .= '&return=' . urlencode($return);
            }
        }

        // Log da operação SSO para auditoria
        try {
            Application::getInstance()->logger()->info('SSO Diário start', [
                'user_id' => $userId,
                'audience' => $aud,
                'return' => $return
            ]);
        } catch (\Throwable $t) { /* ignore */ }

        header('Location: ' . $callback, true, 302);
        exit;
    }
}
