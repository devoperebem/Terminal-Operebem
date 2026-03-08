<?php

namespace App\Controllers;

use App\Core\Application;
use App\Core\Database;

class AdminAlunoController extends BaseController
{
    public function portal(): void
    {
        $this->view('admin_secure/aluno_portal', [
            'title' => 'Portal do Aluno (Admin) — Atalhos',
            'footerVariant' => 'admin-auth',
        ]);
    }

    public function status(): void
    {
        $ok = false; $error = null; $stats = [
            'courses' => 0,
            'enrollments' => 0,
            'users_total' => 0,
        ];
        try {
            // Test connection
            $row = Database::fetch('SELECT 1 AS ok', [], 'aluno');
            $ok = isset($row['ok']);
            // Try optional tables
            try { $r = Database::fetch('SELECT COUNT(*) AS c FROM courses', [], 'aluno'); $stats['courses'] = (int)($r['c'] ?? 0); } catch (\Throwable $__) {}
            try { $r = Database::fetch('SELECT COUNT(*) AS c FROM enrollments', [], 'aluno'); $stats['enrollments'] = (int)($r['c'] ?? 0); } catch (\Throwable $__) {}
            // Users são do DB do Terminal (padrão)
            try { $r = Database::fetch('SELECT COUNT(*) AS c FROM users'); $stats['users_total'] = (int)($r['c'] ?? 0); } catch (\Throwable $__) {}
        } catch (\Throwable $e) {
            $error = $e->getMessage();
        }
        $this->view('admin_secure/aluno_status', [
            'title' => 'Secure Admin - Integração Aluno',
            'ok' => $ok,
            'error' => $error,
            'stats' => $stats,
            'footerVariant' => 'admin-auth',
        ]);
    }
}
