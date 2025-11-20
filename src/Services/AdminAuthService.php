<?php

namespace App\Services;

use App\Core\Database;
use App\Core\Application;
use Carbon\Carbon;

class AdminAuthService
{
    public function ensureTableAndSeed(): void
    {
        $this->ensureTable();
        $this->seedDefaultAdminFromEnv();
    }

    private function ensureTable(): void
    {
        // Tabela admin_users agora é gerenciada via migration (PostgreSQL) ou já existe (MySQL)
        // Não tentar criar tabela automaticamente para evitar conflitos de sintaxe
        return;
    }

    private function seedDefaultAdminFromEnv(): void
    {
        $app = Application::getInstance();
        // Criar admin padrão diretamente no banco se não existir
        $candidateUser = 'mateus@operebem.com.br';
        try {
            $password = 'admin1016@';
            $exists = Database::fetch('SELECT id, password_hash FROM admin_users WHERE username = ?', [$candidateUser]);
            $now = Carbon::now()->toDateTimeString();
            if ($exists) {
                $needsSync = empty($exists['password_hash']) || !password_verify($password, (string)$exists['password_hash']);
                if ($needsSync) {
                    $hash = password_hash($password, PASSWORD_ARGON2ID);
                    Database::update('admin_users', [
                        'password_hash' => $hash,
                        'updated_at' => $now,
                    ], ['id' => (int)$exists['id']]);
                    $app->logger()->warning('Admin padrão sincronizado no banco.', ['username' => $candidateUser]);
                }
                return;
            }
            $hash = password_hash($password, PASSWORD_ARGON2ID);
            Database::insert('admin_users', [
                'username' => $candidateUser,
                'password_hash' => $hash,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $app->logger()->warning('Admin padrão criado automaticamente no banco.', ['username' => $candidateUser]);
        } catch (\Throwable $e) {
            $app->logger()->error('Falha ao semear admin padrão: ' . $e->getMessage());
        }
    }

    public function login(string $username, string $password): array
    {
        $this->ensureTableAndSeed();
        $app = Application::getInstance();
        try {
            $app->logger()->info('Admin login attempt', [
                'username' => $username,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? ''
            ]);
        } catch (\Throwable $t) { /* ignore */ }

        $uname = strtolower(trim($username));
        $admin = Database::fetch('SELECT * FROM admin_users WHERE username = ?', [$uname]);
        if (!$admin || !password_verify($password, $admin['password_hash'])) {
            try { $app->logger()->warning('Admin login failed', ['username' => $username]); } catch (\Throwable $t) {}
            return ['success' => false, 'message' => 'Credenciais inválidas'];
        }
        // Regerar sessão
        if (session_status() === PHP_SESSION_ACTIVE) session_regenerate_id(true);
        $_SESSION['admin_id'] = (int)$admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        $_SESSION['admin_login_time'] = time();
        try { $app->logger()->info('Admin login success', ['id' => (int)$admin['id']]); } catch (\Throwable $t) {}
        return ['success' => true];
    }

    public function isAuthenticated(): bool
    {
        return !empty($_SESSION['admin_id']);
    }

    public function logout(): void
    {
        unset($_SESSION['admin_id'], $_SESSION['admin_username'], $_SESSION['admin_login_time']);
    }

    public function listAdmins(bool $hideDefault = true): array
    {
        $this->ensureTableAndSeed();
        $rows = Database::fetchAll('SELECT id, username, created_at FROM admin_users ORDER BY id ASC');
        if ($hideDefault) {
            $def = $_ENV['ADMIN_DEFAULT_USERNAME'] ?? '';
            if ($def !== '') {
                $rows = array_values(array_filter($rows, fn($r) => $r['username'] !== $def));
            }
        }
        return $rows;
    }

    public function createAdmin(string $username, string $password): array
    {
        $this->ensureTableAndSeed();
        $uname = strtolower(trim($username));
        if (strlen($uname) < 3 || strlen($password) < 8) {
            return ['success' => false, 'message' => 'Usuário ou senha muito curtos'];
        }
        $exists = Database::fetch('SELECT id FROM admin_users WHERE username = ?', [$uname]);
        if ($exists) return ['success' => false, 'message' => 'Usuário já existe'];
        $now = Carbon::now()->toDateTimeString();
        $hash = password_hash($password, PASSWORD_ARGON2ID);
        Database::insert('admin_users', [
            'username' => $uname,
            'password_hash' => $hash,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        return ['success' => true];
    }
}
