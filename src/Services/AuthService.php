<?php

namespace App\Services;

use App\Core\Database;
use App\Core\Application;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Carbon\Carbon;

class AuthService
{
    private array $config;

    public function __construct()
    {
        $this->config = Application::getInstance()->config('app.jwt');
    }

    public function register(array $data): array
    {
        try {
            // Validar dados
            $validation = $this->validateRegistration($data);
            if (!$validation['valid']) {
                return ['success' => false, 'message' => $validation['message']];
            }

            // Verificar se email já existe
            $existingUser = Database::fetch(
                "SELECT id FROM users WHERE email = ?",
                [$data['email']]
            );

            if ($existingUser) {
                return ['success' => false, 'message' => 'Email já está em uso'];
            }

            // Criar usuário
            $userId = Database::insert('users', [
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => password_hash($data['password'], PASSWORD_ARGON2ID),
                'theme' => 'light',
                'media_card' => false,
                'email_verified_at' => null,
                'created_at' => Carbon::now()->toDateTimeString(),
                'updated_at' => Carbon::now()->toDateTimeString()
            ]);

            // Gerar token de verificação de email
            $verificationToken = bin2hex(random_bytes(32));
            Database::insert('email_verifications', [
                'user_id' => $userId,
                'token' => $verificationToken,
                'expires_at' => Carbon::now()->addHours(24)->toDateTimeString(),
                'created_at' => Carbon::now()->toDateTimeString()
            ]);

            // Enviar email de verificação (implementar depois)
            // $this->sendVerificationEmail($data['email'], $verificationToken);

            return [
                'success' => true,
                'message' => 'Conta criada com sucesso! Verifique seu email para ativar a conta.',
                'user_id' => $userId
            ];

        } catch (\Exception $e) {
            Application::getInstance()->logger()->error('Erro no registro: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erro interno. Tente novamente.'];
        }
    }

    public function login(string $email, string $password, bool $remember = false): array
    {
        try {
            // Buscar usuário
            $user = Database::fetch(
                "SELECT * FROM users WHERE email = ? AND deleted_at IS NULL",
                [$email]
            );

            if (!$user || !password_verify($password, $user['password'])) {
                return ['success' => false, 'message' => 'Email ou senha incorretos'];
            }

            // Verificar se email foi verificado (para MVP, vamos permitir login sem verificação)
            // if (!$user['email_verified_at']) {
            //     return ['success' => false, 'message' => 'Email não verificado. Verifique sua caixa de entrada.'];
            // }

            Database::update('users', [
                'updated_at' => Carbon::now()->toDateTimeString()
            ], ['id' => $user['id']]);

            // Criar sessão
            $this->createSession($user, $remember);

            // Gerar JWT
            $token = $this->generateJWT($user);

            // Definir cookie httpOnly para o JWT com flags seguras (Secure condicional, SameSite=Lax)
            setcookie(
                '__Host-access_token',
                $token,
                [
                    'expires' => time() + $this->config['expiration'],
                    'path' => '/',
                    'secure' => Application::getInstance()->isHttps(),
                    'httponly' => true,
                    'samesite' => 'Strict'
                ]
            );

            return [
                'success' => true,
                'message' => 'Login realizado com sucesso',
                'user' => $this->formatUserData($user),
                'token' => $token
            ];

        } catch (\Exception $e) {
            Application::getInstance()->logger()->error('Erro no login: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Erro interno. Tente novamente.'];
        }
    }

    public function logout(): void
    {
        // Limpar sessão
        session_unset();
        session_destroy();

        // Limpar cookies (mesmos atributos)
        $secure = Application::getInstance()->isHttps();
        if (isset($_COOKIE['__Host-remember_token'])) {
            setcookie('__Host-remember_token', '', [
                'expires' => time() - 3600,
                'path' => '/',
                'secure' => $secure,
                'httponly' => true,
                'samesite' => 'Strict'
            ]);
        }
        if (isset($_COOKIE['__Host-access_token'])) {
            setcookie('__Host-access_token', '', [
                'expires' => time() - 3600,
                'path' => '/',
                'secure' => $secure,
                'httponly' => true,
                'samesite' => 'Strict'
            ]);
        }
        // Legacy cookies cleanup
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', [
                'expires' => time() - 3600,
                'path' => '/',
                'secure' => $secure,
                'httponly' => true,
                'samesite' => 'Strict'
            ]);
        }
        if (isset($_COOKIE['access_token'])) {
            setcookie('access_token', '', [
                'expires' => time() - 3600,
                'path' => '/',
                'secure' => $secure,
                'httponly' => true,
                'samesite' => 'Strict'
            ]);
        }
    }

    public function isAuthenticated(): bool
    {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }

    public function getCurrentUser(): ?array
    {
        if (!$this->isAuthenticated()) {
            return null;
        }

        $user = Database::fetch(
            "SELECT * FROM users WHERE id = ? AND deleted_at IS NULL",
            [$_SESSION['user_id']]
        );

        return $user ? $this->formatUserData($user) : null;
    }

    public function generateJWT(array $user): string
    {
        $payload = [
            'iss' => Application::getInstance()->config('app.url'),
            'aud' => Application::getInstance()->config('app.url'),
            'iat' => time(),
            'exp' => time() + $this->config['expiration'],
            'user_id' => $user['id'],
            'email' => $user['email']
        ];

        return JWT::encode($payload, $this->config['secret'], $this->config['algorithm']);
    }

    public function validateJWT(string $token): ?array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->config['secret'], $this->config['algorithm']));
            return (array) $decoded;
        } catch (\Exception $e) {
            return null;
        }
    }

    private function createSession(array $user, bool $remember): void
    {
        session_regenerate_id(true);
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_theme'] = $user['theme'];
        $_SESSION['user_timezone'] = $user['timezone'] ?? 'America/Sao_Paulo';
        $_SESSION['user_media_card'] = (bool) $user['media_card'];
        $_SESSION['user_advanced_snapshot'] = isset($user['advanced_snapshot']) ? (bool) $user['advanced_snapshot'] : true;
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['login_time'] = time();

        if ($remember) {
            $rememberToken = bin2hex(random_bytes(32));
            
            // Salvar token no banco
            Database::insert('remember_tokens', [
                'user_id' => $user['id'],
                'token' => hash('sha256', $rememberToken),
                'expires_at' => Carbon::now()->addDays(30)->toDateTimeString(),
                'created_at' => Carbon::now()->toDateTimeString()
            ]);

            // Definir cookie com flags seguras
            setcookie('__Host-remember_token', $rememberToken, [
                'expires' => time() + (30 * 24 * 60 * 60),
                'path' => '/',
                'secure' => Application::getInstance()->isHttps(),
                'httponly' => true,
                'samesite' => 'Strict'
            ]);
        }
    }

    private function validateRegistration(array $data): array
    {
        if (empty($data['name']) || strlen($data['name']) < 2) {
            return ['valid' => false, 'message' => 'Nome deve ter pelo menos 2 caracteres'];
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return ['valid' => false, 'message' => 'Email inválido'];
        }

        if (strlen($data['password']) < 8) {
            return ['valid' => false, 'message' => 'Senha deve ter pelo menos 8 caracteres'];
        }

        if ($data['password'] !== $data['password_confirmation']) {
            return ['valid' => false, 'message' => 'Confirmação de senha não confere'];
        }

        return ['valid' => true];
    }

    private function formatUserData(array $user): array
    {
        return [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'theme' => $user['theme'],
            'timezone' => $user['timezone'] ?? 'America/Sao_Paulo',
            'media_card' => (bool) $user['media_card'],
            'advanced_snapshot' => array_key_exists('advanced_snapshot', $user) ? (bool) $user['advanced_snapshot'] : true,
            'email_verified' => !empty($user['email_verified_at']),
            'created_at' => $user['created_at'],
            // Subscription data
            'tier' => $user['tier'] ?? 'FREE',
            'subscription_expires_at' => $user['subscription_expires_at'] ?? null,
            // Gamification data
            'xp' => (int)($user['xp'] ?? 0),
            'streak' => (int)($user['streak'] ?? 0),
            'level' => (int)($user['level'] ?? 1),
            'last_login_at' => $user['last_login_at'] ?? null,
            'last_xp_earned_at' => $user['last_xp_earned_at'] ?? null
        ];
    }

    public function updateUserPreferences(int $userId, array $preferences): bool
    {
        try {
            $updateData = [];
            
            if (isset($preferences['theme']) && in_array($preferences['theme'], ['light', 'dark-blue', 'all-black'])) {
                $updateData['theme'] = $preferences['theme'];
                $_SESSION['user_theme'] = $preferences['theme'];
            }
            
            if (isset($preferences['timezone'])) {
                $updateData['timezone'] = $preferences['timezone'];
                $_SESSION['user_timezone'] = $preferences['timezone'];
            }
            
            if (isset($preferences['media_card'])) {
                $updateData['media_card'] = (bool) $preferences['media_card'];
                $_SESSION['user_media_card'] = (bool) $preferences['media_card'];
            }
            
            if (isset($preferences['advanced_snapshot'])) {
                $updateData['advanced_snapshot'] = (bool) $preferences['advanced_snapshot'];
                $_SESSION['user_advanced_snapshot'] = (bool) $preferences['advanced_snapshot'];
            }
            
            if (!empty($updateData)) {
                $updateData['updated_at'] = Carbon::now()->toDateTimeString();
                Database::update('users', $updateData, ['id' => $userId]);
            }
            
            return true;
        } catch (\Exception $e) {
            Application::getInstance()->logger()->error('Erro ao atualizar preferências: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Criar sessão a partir de dados do usuário
     */
    public function createSessionFromUser(array $user): void
    {
        $this->createSession($user, false);
    }
}
