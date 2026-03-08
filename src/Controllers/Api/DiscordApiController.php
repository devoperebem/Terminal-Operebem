<?php

namespace App\Controllers\Api;

use App\Core\Database;
use App\Controllers\BaseController;
use App\Services\GamificationService;
use App\Services\XPSettingsService;

class DiscordApiController extends BaseController
{
    /**
     * POST /api/discord/verify
     * Valida código de verificação (chamado pelo bot)
     */
    public function verify(): void
    {
        header('Content-Type: application/json');
        
        // Validar API Key
        $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
        $expectedKey = $_ENV['DISCORD_BOT_API_KEY'] ?? '';
        
        if (empty($expectedKey) || $apiKey !== $expectedKey) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }
        
        // Obter dados do POST
        $input = json_decode(file_get_contents('php://input'), true);
        $code = $input['code'] ?? '';
        $discordId = $input['discord_id'] ?? '';
        $discordUsername = $input['discord_username'] ?? '';
        $discordAvatar = $input['discord_avatar'] ?? null;
        
        if (empty($code) || empty($discordId)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Código e Discord ID são obrigatórios']);
            return;
        }
        
        try {
            // Buscar usuário pelo código
            $user = Database::fetch("
                SELECT 
                    du.user_id,
                    du.is_verified,
                    du.discord_id as current_discord_id,
                    u.name,
                    u.email,
                    u.xp,
                    u.level,
                    u.streak
                FROM discord_users du
                JOIN users u ON du.user_id = u.id
                WHERE du.verification_code = :code
                  AND u.deleted_at IS NULL
            ", ['code' => $code]);
            
            if (!$user) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Código inválido ou usuário não encontrado'
                ]);
                return;
            }
            
            // Verificar se já está verificado com outro Discord ID
            if ($user['is_verified'] && !empty($user['current_discord_id']) && $user['current_discord_id'] !== $discordId) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Este código já foi utilizado por outro usuário Discord'
                ]);
                return;
            }
            
            // Atualizar verificação
            Database::query("
                UPDATE discord_users
                SET discord_id = :discord_id,
                    discord_username = :username,
                    discord_avatar = :avatar,
                    is_verified = TRUE,
                    verified_at = NOW(),
                    last_sync_at = NOW(),
                    updated_at = NOW()
                WHERE verification_code = :code
            ", [
                'discord_id' => $discordId,
                'username' => $discordUsername,
                'avatar' => $discordAvatar,
                'code' => $code
            ]);
            
            // Log da verificação
            Database::query("
                INSERT INTO discord_logs (user_id, discord_id, action, details, created_at)
                VALUES (:user_id, :discord_id, 'verify_success', :details, NOW())
            ", [
                'user_id' => $user['user_id'],
                'discord_id' => $discordId,
                'details' => json_encode([
                    'username' => $discordUsername,
                    'code' => substr($code, 0, 8) . '...',
                    'timestamp' => date('Y-m-d H:i:s')
                ])
            ]);
            
            // Retornar dados do usuário
            echo json_encode([
                'success' => true,
                'user' => [
                    'user_id' => $user['user_id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'xp' => (int)$user['xp'],
                    'level' => (int)$user['level'],
                    'streak' => (int)$user['streak']
                ]
            ]);
            
        } catch (\Exception $e) {
            error_log('[DiscordApiController] Erro na verificação: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Erro interno do servidor'
            ]);
        }
    }
    
    /**
     * POST /api/discord/award-xp
     * Concede 1 XP por mensagem, com cooldown de 10 minutos e limite diário de 25 XP
     */
    public function awardXP(): void
    {
        header('Content-Type: application/json');
        
        // Validar API Key
        $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
        $expectedKey = $_ENV['DISCORD_BOT_API_KEY'] ?? '';
        
        if (empty($expectedKey) || $apiKey !== $expectedKey) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $discordId = $input['discord_id'] ?? '';
        $messageId = $input['message_id'] ?? null;
        $channelId = $input['channel_id'] ?? null;
        
        if (empty($discordId)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Discord ID é obrigatório']);
            return;
        }
        
        try {
            // Buscar usuário verificado
            $user = Database::fetch(
                "SELECT du.user_id, u.xp, u.level
                 FROM discord_users du
                 JOIN users u ON du.user_id = u.id
                 WHERE du.discord_id = :discord_id
                   AND du.is_verified = TRUE
                   AND u.deleted_at IS NULL",
                ['discord_id' => $discordId]
            );
            
            if (!$user) {
                echo json_encode(['success' => true, 'awarded' => false, 'reason' => 'not_verified']);
                return;
            }
            
            $userId = (int)$user['user_id'];

            // Carregar configurações dinâmicas
            $cooldownMin = XPSettingsService::get('xp_discord_msg_cooldown_minutes', 10);
            $cooldownSeconds = max(0, $cooldownMin) * 60;
            $dailyCap = XPSettingsService::get('xp_discord_msg_daily_cap', 25);
            $amount = XPSettingsService::get('xp_discord_msg_amount', 1);

            // Se desabilitado por configuração
            if ($dailyCap <= 0 || $amount <= 0) {
                echo json_encode(['success' => true, 'awarded' => false, 'reason' => 'disabled']);
                return;
            }

            // Cooldown entre prêmios de mensagem
            $last = Database::fetch(
                "SELECT created_at
                 FROM xp_history
                 WHERE user_id = :user_id AND source = 'discord_message'
                 ORDER BY created_at DESC
                 LIMIT 1",
                ['user_id' => $userId]
            );
            
            if ($last && strtotime($last['created_at']) > (time() - $cooldownSeconds)) {
                $elapsed = time() - strtotime($last['created_at']);
                $remaining = max(0, $cooldownSeconds - $elapsed);
                echo json_encode(['success' => true, 'awarded' => false, 'reason' => 'cooldown', 'cooldown_remaining' => $remaining]);
                return;
            }
            
            // Limite diário configurável
            $today = Database::fetch(
                "SELECT COALESCE(SUM(amount), 0) AS total
                 FROM xp_history
                 WHERE user_id = :user_id
                   AND source = 'discord_message'
                   AND created_at::date = CURRENT_DATE",
                ['user_id' => $userId]
            );
            
            $awardedToday = (int)($today['total'] ?? 0);
            if ($awardedToday >= $dailyCap) {
                echo json_encode(['success' => true, 'awarded' => false, 'reason' => 'daily_cap', 'remaining_today' => 0]);
                return;
            }
            
            // Ajustar para não ultrapassar o limite diário
            if ($awardedToday + $amount > $dailyCap) {
                $amount = $dailyCap - $awardedToday;
            }
            
            if ($amount <= 0) {
                echo json_encode(['success' => true, 'awarded' => false, 'reason' => 'daily_cap']);
                return;
            }
            
            // Conceder XP
            $service = new GamificationService();
            $ok = $service->addXP(
                $userId,
                $amount,
                'Discord: mensagem' . ($messageId ? (' #' . $messageId) : ''),
                'discord_message',
                null
            );
            
            if (!$ok) {
                http_response_code(500);
                echo json_encode(['success' => false, 'error' => 'Falha ao conceder XP']);
                return;
            }
            
            // Atualizar last_sync_at
            Database::query(
                "UPDATE discord_users SET last_sync_at = NOW() WHERE user_id = :user_id",
                ['user_id' => $userId]
            );
            
            echo json_encode([
                'success' => true,
                'awarded' => true,
                'amount' => $amount,
                'awarded_today' => $awardedToday + $amount
            ]);
        } catch (\Exception $e) {
            error_log('[DiscordApiController] awardXP error: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Erro interno do servidor']);
        }
    }
    
    /**
     * POST /api/discord/sync-xp
     * Sincroniza XP de um usuário (chamado pelo bot)
     */
    public function syncXP(): void
    {
        header('Content-Type: application/json');
        
        // Validar API Key
        $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
        $expectedKey = $_ENV['DISCORD_BOT_API_KEY'] ?? '';
        
        if (empty($expectedKey) || $apiKey !== $expectedKey) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }
        
        // Obter dados do POST
        $input = json_decode(file_get_contents('php://input'), true);
        $discordId = $input['discord_id'] ?? '';
        
        if (empty($discordId)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Discord ID é obrigatório']);
            return;
        }
        
        try {
            // Buscar dados do usuário
            $user = Database::fetch("
                SELECT 
                    du.user_id,
                    u.name,
                    u.xp,
                    u.level,
                    u.streak
                FROM discord_users du
                JOIN users u ON du.user_id = u.id
                WHERE du.discord_id = :discord_id
                  AND du.is_verified = TRUE
                  AND u.deleted_at IS NULL
            ", ['discord_id' => $discordId]);
            
            if (!$user) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Usuário não encontrado ou não verificado'
                ]);
                return;
            }
            
            // Atualizar timestamp de sincronização
            Database::query("
                UPDATE discord_users
                SET last_sync_at = NOW()
                WHERE discord_id = :discord_id
            ", ['discord_id' => $discordId]);
            
            // Retornar dados atualizados
            echo json_encode([
                'success' => true,
                'user' => [
                    'user_id' => $user['user_id'],
                    'name' => $user['name'],
                    'xp' => (int)$user['xp'],
                    'level' => (int)$user['level'],
                    'streak' => (int)$user['streak']
                ]
            ]);
            
        } catch (\Exception $e) {
            error_log('[DiscordApiController] Erro na sincronização: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Erro interno do servidor'
            ]);
        }
    }
    
    /**
     * GET /api/discord/user/{discord_id}
     * Obtém dados de um usuário pelo Discord ID
     */
    public function getUser(array $params): void
    {
        header('Content-Type: application/json');
        
        // Validar API Key
        $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
        $expectedKey = $_ENV['DISCORD_BOT_API_KEY'] ?? '';
        
        if (empty($expectedKey) || $apiKey !== $expectedKey) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }
        
        $discordId = $params['discord_id'] ?? '';
        
        if (empty($discordId)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Discord ID é obrigatório']);
            return;
        }
        
        try {
            $user = Database::fetch("
                SELECT 
                    du.user_id,
                    du.discord_username,
                    du.is_verified,
                    du.verified_at,
                    du.last_sync_at,
                    u.name,
                    u.email,
                    u.xp,
                    u.level,
                    u.streak,
                    u.created_at
                FROM discord_users du
                JOIN users u ON du.user_id = u.id
                WHERE du.discord_id = :discord_id
                  AND u.deleted_at IS NULL
            ", ['discord_id' => $discordId]);
            
            if (!$user) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Usuário não encontrado'
                ]);
                return;
            }
            
            echo json_encode([
                'success' => true,
                'user' => [
                    'user_id' => $user['user_id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'xp' => (int)$user['xp'],
                    'level' => (int)$user['level'],
                    'streak' => (int)$user['streak'],
                    'is_verified' => (bool)$user['is_verified'],
                    'verified_at' => $user['verified_at'],
                    'last_sync_at' => $user['last_sync_at'],
                    'member_since' => $user['created_at']
                ]
            ]);
            
        } catch (\Exception $e) {
            error_log('[DiscordApiController] Erro ao buscar usuário: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Erro interno do servidor'
            ]);
        }
    }
    
    /**
     * GET /api/discord/verified-users
     * Obtém lista de usuários verificados para sincronização
     */
    public function getVerifiedUsers(): void
    {
        header('Content-Type: application/json');
        
        // Validar API Key
        $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
        $expectedKey = $_ENV['DISCORD_BOT_API_KEY'] ?? '';
        
        if (empty($expectedKey) || $apiKey !== $expectedKey) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }
        
        try {
            $users = Database::fetchAll("
                SELECT 
                    du.discord_id,
                    du.user_id,
                    u.name,
                    u.xp,
                    u.level
                FROM discord_users du
                JOIN users u ON du.user_id = u.id
                WHERE du.is_verified = TRUE
                  AND du.discord_id IS NOT NULL
                  AND du.discord_id != ''
                  AND u.deleted_at IS NULL
                ORDER BY u.level DESC
            ");
            
            echo json_encode([
                'success' => true,
                'users' => $users
            ]);
            
        } catch (\Exception $e) {
            error_log('[DiscordApiController] Erro ao buscar usuários verificados: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Erro interno do servidor'
            ]);
        }
    }
    
    /**
     * GET /api/discord/stats
     * Obtém estatísticas do sistema
     */
    public function getStats(): void
    {
        header('Content-Type: application/json');
        
        // Validar API Key
        $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
        $expectedKey = $_ENV['DISCORD_BOT_API_KEY'] ?? '';
        
        if (empty($expectedKey) || $apiKey !== $expectedKey) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }
        
        try {
            // Total de usuários ativos
            $totalUsers = Database::fetch("
                SELECT COUNT(*) as count 
                FROM users 
                WHERE deleted_at IS NULL
            ");
            
            // Usuários verificados
            $verifiedUsers = Database::fetch("
                SELECT COUNT(*) as count 
                FROM discord_users 
                WHERE is_verified = TRUE
            ");
            
            $total = (int)($totalUsers['count'] ?? 0);
            $verified = (int)($verifiedUsers['count'] ?? 0);
            
            echo json_encode([
                'success' => true,
                'stats' => [
                    'total_users' => $total,
                    'verified_users' => $verified,
                    'pending_users' => $total - $verified
                ]
            ]);
            
        } catch (\Exception $e) {
            error_log('[DiscordApiController] Erro ao buscar estatísticas: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Erro interno do servidor'
            ]);
        }
    }
    
    /**
     * POST /api/discord/log
     * Registra ação no log
     */
    public function log(): void
    {
        header('Content-Type: application/json');
        
        // Validar API Key
        $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
        $expectedKey = $_ENV['DISCORD_BOT_API_KEY'] ?? '';
        
        if (empty($expectedKey) || $apiKey !== $expectedKey) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';
        $discordId = $input['discord_id'] ?? null;
        $userId = $input['user_id'] ?? null;
        $details = $input['details'] ?? null;
        
        if (empty($action)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Action é obrigatório']);
            return;
        }
        
        try {
            Database::query("
                INSERT INTO discord_logs (user_id, discord_id, action, details, created_at)
                VALUES (:user_id, :discord_id, :action, :details, NOW())
            ", [
                'user_id' => $userId,
                'discord_id' => $discordId,
                'action' => $action,
                'details' => $details ? json_encode($details) : null
            ]);
            
            echo json_encode(['success' => true]);
            
        } catch (\Exception $e) {
            error_log('[DiscordApiController] Erro ao registrar log: ' . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Erro interno do servidor'
            ]);
        }
    }
    
    /**
     * GET /api/discord/ping
     * Testa conexão com a API
     */
    public function ping(): void
    {
        header('Content-Type: application/json');
        
        // Validar API Key
        $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
        $expectedKey = $_ENV['DISCORD_BOT_API_KEY'] ?? '';
        
        if (empty($expectedKey) || $apiKey !== $expectedKey) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'pong',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * GET /api/discord/message-xp-config
     * Retorna apenas as configurações necessárias para o bot alinhar cooldown/cap/amount
     */
    public function getMessageXPConfig(): void
    {
        header('Content-Type: application/json');
        
        // Validar API Key (somente o bot deve acessar)
        $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
        $expectedKey = $_ENV['DISCORD_BOT_API_KEY'] ?? '';
        
        if (empty($expectedKey) || $apiKey !== $expectedKey) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }
        
        try {
            $amount = XPSettingsService::get('xp_discord_msg_amount', 1);
            $cooldownMin = XPSettingsService::get('xp_discord_msg_cooldown_minutes', 10);
            $dailyCap = XPSettingsService::get('xp_discord_msg_daily_cap', 25);
            
            echo json_encode([
                'success' => true,
                'config' => [
                    'xp_per_message' => (int)$amount,
                    'cooldown_minutes' => (int)$cooldownMin,
                    'cooldown_seconds' => max(0, (int)$cooldownMin) * 60,
                    'daily_cap' => (int)$dailyCap
                ]
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Erro interno do servidor']);
        }
    }
}
