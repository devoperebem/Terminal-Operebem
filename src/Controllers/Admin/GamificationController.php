<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Core\Database;
use App\Services\GamificationService;
use App\Services\XPSettingsService;

class GamificationController extends BaseController
{
    /**
     * GET /admin/gamification
     * Painel de gerenciamento de XP
     */
    public function index(): void
    {
        // Estatísticas gerais
        $stats = Database::fetch("
            SELECT 
                COUNT(*) as total_users,
                COALESCE(SUM(xp), 0) as total_xp,
                COALESCE(AVG(xp), 0) as avg_xp,
                COALESCE(MAX(xp), 0) as max_xp,
                COALESCE(AVG(streak), 0) as avg_streak,
                COALESCE(MAX(streak), 0) as max_streak
            FROM users 
            WHERE deleted_at IS NULL
        ");
        
        // Top 10 usuários por XP
        $topUsers = Database::fetchAll("
            SELECT id, name, email, xp, streak, level
            FROM users
            WHERE deleted_at IS NULL
            ORDER BY xp DESC
            LIMIT 10
        ");
        
        // Histórico recente de XP (últimas 50 transações)
        $recentXP = Database::fetchAll("
            SELECT 
                h.id,
                h.user_id,
                u.name as user_name,
                u.email as user_email,
                h.amount,
                h.source,
                h.source_id,
                h.description,
                h.created_at
            FROM xp_history h
            JOIN users u ON u.id = h.user_id
            ORDER BY h.created_at DESC
            LIMIT 50
        ");
        
        // XP por fonte (últimos 30 dias)
        $xpBySource = Database::fetchAll("
            SELECT 
                source,
                COUNT(*) as count,
                SUM(amount) as total_xp
            FROM xp_history
            WHERE created_at >= NOW() - INTERVAL '30 days'
            GROUP BY source
            ORDER BY total_xp DESC
        ");
        
        $this->view('admin_secure/gamification/index', [
            'title' => 'Admin - Gamificação',
            'footerVariant' => 'admin-auth',
            'stats' => $stats,
            'topUsers' => $topUsers,
            'recentXP' => $recentXP,
            'xpBySource' => $xpBySource
        ]);
    }
    
    /**
     * POST /admin/gamification/add-xp
     * Adicionar XP manualmente para um usuário
     */
    public function addXP(): void
    {
        $userId = (int)($_POST['user_id'] ?? 0);
        $amount = (int)($_POST['amount'] ?? 0);
        $reason = trim($_POST['reason'] ?? '');
        
        if ($userId <= 0 || $amount == 0) {
            $this->json(['success' => false, 'message' => 'Dados inválidos'], 400);
            return;
        }
        
        $gamification = new GamificationService();
        $success = $gamification->addXP($userId, $amount, $reason, 'admin_manual', null);
        
        if ($success) {
            $this->json([
                'success' => true,
                'message' => $amount > 0 ? "XP adicionado com sucesso" : "XP removido com sucesso"
            ]);
        } else {
            $this->json(['success' => false, 'message' => 'Erro ao adicionar XP'], 500);
        }
    }
    
    /**
     * POST /admin/gamification/reset-streak
     * Resetar streak de um usuário
     */
    public function resetStreak(): void
    {
        $userId = (int)($_POST['user_id'] ?? 0);
        
        if ($userId <= 0) {
            $this->json(['success' => false, 'message' => 'User ID inválido'], 400);
            return;
        }
        
        try {
            Database::update('users', ['streak' => 0], ['id' => $userId]);
            $this->json(['success' => true, 'message' => 'Streak resetado']);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => 'Erro ao resetar streak'], 500);
        }
    }
    
    /**
     * POST /admin/gamification/recalculate-levels
     * Recalcular níveis de todos os usuários
     */
    public function recalculateLevels(): void
    {
        try {
            $users = Database::fetchAll("SELECT id, xp FROM users WHERE deleted_at IS NULL");
            $gamification = new GamificationService();
            $updated = 0;
            
            foreach ($users as $user) {
                $newLevel = $gamification->calculateLevel((int)$user['xp']);
                Database::update('users', ['level' => $newLevel], ['id' => $user['id']]);
                $updated++;
            }
            
            $this->json([
                'success' => true,
                'message' => "Níveis recalculados para $updated usuários"
            ]);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => 'Erro ao recalcular níveis'], 500);
        }
    }
    
    /**
     * GET /admin/gamification/user/{id}
     * Ver detalhes de XP de um usuário específico
     */
    public function userDetails(array $params): void
    {
        $userId = (int)($params['id'] ?? 0);
        
        if ($userId <= 0) {
            http_response_code(404);
            echo "Usuário não encontrado";
            return;
        }
        
        $user = Database::fetch("
            SELECT id, name, email, xp, streak, level, last_login_at, last_xp_earned_at, created_at, email_verified_at
            FROM users
            WHERE id = :id AND deleted_at IS NULL
        ", ['id' => $userId]);
        
        // Debug log
        error_log('[GamificationController] userDetails - userId: ' . $userId . ', user found: ' . ($user ? 'yes' : 'no'));
        if ($user) {
            error_log('[GamificationController] user data: ' . json_encode($user));
        }
        
        if (!$user) {
            http_response_code(404);
            echo "Usuário não encontrado";
            return;
        }
        
        // Histórico de XP do usuário
        $history = Database::fetchAll("
            SELECT id, amount, source, source_id, description, created_at
            FROM xp_history
            WHERE user_id = :user_id
            ORDER BY created_at DESC
            LIMIT 100
        ", ['user_id' => $userId]);
        
        // XP por fonte
        $xpBySource = Database::fetchAll("
            SELECT 
                source,
                COUNT(*) as count,
                SUM(amount) as total_xp
            FROM xp_history
            WHERE user_id = :user_id
            GROUP BY source
            ORDER BY total_xp DESC
        ", ['user_id' => $userId]);
        
        $gamification = new GamificationService();
        $nextLevelXP = $gamification->getNextLevelXP((int)$user['level']);
        
        $this->view('admin_secure/gamification/user_details', [
            'title' => 'Admin - Detalhes do Usuário',
            'footerVariant' => 'admin-auth',
            'user' => $user,
            'history' => $history,
            'xpBySource' => $xpBySource,
            'nextLevelXP' => $nextLevelXP
        ]);
    }
    
    /**
     * GET /admin/gamification/settings
     * Configurações do sistema de XP
     */
    public function settings(): void
    {
        // Carregar configurações do banco
        $settings = XPSettingsService::getAll();
        
        $this->view('admin_secure/gamification/settings', [
            'title' => 'Admin - Configurações de XP',
            'footerVariant' => 'admin-auth',
            'xp_daily_login' => $settings['xp_daily_login']['value'] ?? 5,
            'xp_lesson_base' => $settings['xp_lesson_base']['value'] ?? 10,
            'xp_lesson_bonus_30min' => $settings['xp_lesson_bonus_30min']['value'] ?? 5,
            'xp_lesson_bonus_1h' => $settings['xp_lesson_bonus_1h']['value'] ?? 10,
            'xp_course_complete' => $settings['xp_course_complete']['value'] ?? 50,
            // Discord mensagens
            'xp_discord_msg_amount' => $settings['xp_discord_msg_amount']['value'] ?? 1,
            'xp_discord_msg_cooldown_minutes' => $settings['xp_discord_msg_cooldown_minutes']['value'] ?? 10,
            'xp_discord_msg_daily_cap' => $settings['xp_discord_msg_daily_cap']['value'] ?? 25
        ]);
    }
    
    /**
     * POST /secure/adm/gamification/settings
     * Salvar configurações de XP
     */
    public function saveSettings(): void
    {
        $adminId = $_SESSION['admin_user']['id'] ?? null;
        
        try {
            // Validar e salvar cada configuração
            $configs = [
                'xp_daily_login' => (int)($_POST['xp_daily_login'] ?? 5),
                'xp_lesson_base' => (int)($_POST['xp_lesson_base'] ?? 10),
                'xp_lesson_bonus_30min' => (int)($_POST['xp_lesson_bonus_30min'] ?? 5),
                'xp_lesson_bonus_1h' => (int)($_POST['xp_lesson_bonus_1h'] ?? 10),
                'xp_course_complete' => (int)($_POST['xp_course_complete'] ?? 50),
                // Discord mensagens
                'xp_discord_msg_amount' => (int)($_POST['xp_discord_msg_amount'] ?? 1),
                'xp_discord_msg_cooldown_minutes' => (int)($_POST['xp_discord_msg_cooldown_minutes'] ?? 10),
                'xp_discord_msg_daily_cap' => (int)($_POST['xp_discord_msg_daily_cap'] ?? 25)
            ];
            
            foreach ($configs as $key => $value) {
                // Validações específicas por chave
                $min = 0; $max = 500;
                if ($key === 'xp_discord_msg_amount') { $max = 25; }
                if ($key === 'xp_discord_msg_cooldown_minutes') { $max = 180; }
                if ($value < $min || $value > $max) {
                    http_response_code(400);
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => 'Valores inválidos para ' . $key]);
                    return;
                }
                XPSettingsService::set($key, $value, $adminId);
            }
            
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Configurações salvas com sucesso!']);
        } catch (\Exception $e) {
            error_log('[GamificationController] Erro ao salvar configurações: ' . $e->getMessage());
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Erro ao salvar configurações']);
        }
    }

    /**
     * GET /secure/adm/gamification/run-migration/xp-settings
     * Executa as migrations 009 e 010 via navegador (somente admin)
     */
    public function runXPSettingsMigration(): void
    {
        header('Content-Type: application/json');
        try {
            $base = dirname(__DIR__, 3);
            $file009 = $base . '/database/migrations/009_create_xp_settings_table.sql';
            $file010 = $base . '/database/migrations/010_seed_xp_discord_settings.sql';
            
            $pdo = \App\Core\Database::connection();
            $ran009 = false; 
            $ran010 = false;
            
            if (file_exists($file009)) {
                $sql = file_get_contents($file009);
                if (!empty($sql)) {
                    $pdo->exec($sql);
                    $ran009 = true;
                }
            }
            if (file_exists($file010)) {
                $sql2 = file_get_contents($file010);
                if (!empty($sql2)) {
                    $pdo->exec($sql2);
                    $ran010 = true;
                }
            }
            echo json_encode(['success' => true, 'migration_009' => $ran009, 'migration_010' => $ran010]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Erro ao executar migration']);
        }
    }
}
