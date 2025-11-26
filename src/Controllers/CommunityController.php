<?php

namespace App\Controllers;

use App\Core\Database;
use App\Controllers\BaseController;
use App\Services\XPSettingsService;

class CommunityController extends BaseController
{
    /**
     * GET /app/community
     * Página da comunidade Discord
     */
    public function index(): void
    {
        $userId = $_SESSION['user_id'] ?? null;
        
        if (!$userId) {
            header('Location: /login');
            exit;
        }
        
        // Buscar dados do Discord do usuário
        $discordData = Database::fetch("
            SELECT 
                discord_id,
                discord_username,
                discord_avatar,
                verification_code,
                is_verified,
                verified_at,
                last_sync_at
            FROM discord_users
            WHERE user_id = :user_id
        ", ['user_id' => $userId]);
        
        // Se não existe, criar entrada (trigger já deve ter criado, mas garantir)
        if (!$discordData) {
            // Gerar código único
            $verificationCode = $this->generateVerificationCode();
            
            Database::query("
                INSERT INTO discord_users (user_id, verification_code, discord_id)
                VALUES (:user_id, :code, NULL)
                ON DUPLICATE KEY UPDATE updated_at = NOW()
            ", [
                'user_id' => $userId,
                'code' => $verificationCode
            ]);
            
            $discordData = [
                'verification_code' => $verificationCode,
                'is_verified' => false,
                'discord_id' => null,
                'discord_username' => null,
                'discord_avatar' => null,
                'verified_at' => null,
                'last_sync_at' => null
            ];
        }
        
        // Buscar dados de gamificação do usuário
        $userData = Database::fetch("
            SELECT 
                id,
                name,
                email,
                xp,
                level,
                streak,
                created_at
            FROM users
            WHERE id = :user_id AND deleted_at IS NULL
        ", ['user_id' => $userId]);
        
        if (!$userData) {
            http_response_code(404);
            echo "Usuário não encontrado";
            return;
        }
        
        // Buscar estatísticas recentes
        $recentXP = Database::fetch("
            SELECT 
                COALESCE(SUM(amount), 0) as total_xp,
                COUNT(*) as activities
            FROM xp_history
            WHERE user_id = :user_id
              AND created_at >= NOW() - INTERVAL '7 days'
        ", ['user_id' => $userId]);
        
        // Configurações dinâmicas de XP para exibição no FAQ
        $xpDailyLogin = XPSettingsService::get('xp_daily_login', 5);
        $xpDiscordAmount = XPSettingsService::get('xp_discord_msg_amount', 1);
        $xpDiscordCooldown = XPSettingsService::get('xp_discord_msg_cooldown_minutes', 10);
        $xpDiscordDailyCap = XPSettingsService::get('xp_discord_msg_daily_cap', 25);
        $xpLessonBase = XPSettingsService::get('xp_lesson_base', 10);
        $xpLessonBonus30 = XPSettingsService::get('xp_lesson_bonus_30min', 5);
        $xpLessonBonus1h = XPSettingsService::get('xp_lesson_bonus_1h', 10);
        $xpCourseComplete = XPSettingsService::get('xp_course_complete', 50);

        $this->view('app/community', [
            'discord' => $discordData,
            'user' => $userData,
            'recentXP' => $recentXP,
            'inviteUrl' => $_ENV['DISCORD_INVITE_URL'] ?? 'https://discord.com/invite/',
            // XP settings
            'xp_daily_login' => $xpDailyLogin,
            'xp_discord_msg_amount' => $xpDiscordAmount,
            'xp_discord_msg_cooldown_minutes' => $xpDiscordCooldown,
            'xp_discord_msg_daily_cap' => $xpDiscordDailyCap,
            'xp_lesson_base' => $xpLessonBase,
            'xp_lesson_bonus_30min' => $xpLessonBonus30,
            'xp_lesson_bonus_1h' => $xpLessonBonus1h,
            'xp_course_complete' => $xpCourseComplete,
            'footerVariant' => 'auth'
        ]);
    }
    
    /**
     * GET /app/community/status
     * Retorna status atual para atualização dinâmica da página
     */
    public function status(): void
    {
        $userId = $_SESSION['user_id'] ?? null;
        header('Content-Type: application/json');
        if (!$userId) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Não autenticado']);
            return;
        }
        try {
            $discordData = Database::fetch(
                "SELECT discord_id, discord_username, discord_avatar, verification_code, is_verified, verified_at, last_sync_at
                 FROM discord_users WHERE user_id = :user_id",
                ['user_id' => $userId]
            ) ?? [];
            $userData = Database::fetch(
                "SELECT xp, level, streak FROM users WHERE id = :user_id AND deleted_at IS NULL",
                ['user_id' => $userId]
            ) ?? [];
            $recent = Database::fetch(
                "SELECT COALESCE(SUM(amount), 0) AS total_xp
                 FROM xp_history
                 WHERE user_id = :user_id AND created_at >= NOW() - INTERVAL '7 days'",
                ['user_id' => $userId]
            ) ?? ['total_xp' => 0];
            $userData['recent_xp'] = (int)($recent['total_xp'] ?? 0);
            echo json_encode([
                'success' => true,
                'discord' => $discordData,
                'user' => $userData
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erro ao obter status']);
        }
    }

    /**
     * POST /app/community/disconnect
     * Desconectar conta do Discord
     */
    public function disconnect(): void
    {
        $userId = $_SESSION['user_id'] ?? null;
        
        if (!$userId) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Não autenticado']);
            return;
        }
        
        try {
            // Resetar dados do Discord mas manter o código
            Database::query("
                UPDATE discord_users
                SET discord_id = NULL,
                    discord_username = NULL,
                    discord_avatar = NULL,
                    is_verified = FALSE,
                    verified_at = NULL,
                    updated_at = NOW()
                WHERE user_id = :user_id
            ", ['user_id' => $userId]);
            
            // Log
            Database::query("
                INSERT INTO discord_logs (user_id, action, details, created_at)
                VALUES (:user_id, 'disconnect', :details, NOW())
            ", [
                'user_id' => $userId,
                'details' => json_encode(['timestamp' => date('Y-m-d H:i:s')])
            ]);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Conta Discord desconectada com sucesso'
            ]);
        } catch (\Exception $e) {
            error_log('[CommunityController] Erro ao desconectar: ' . $e->getMessage());
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Erro ao desconectar conta'
            ]);
        }
    }
    
    /**
     * POST /app/community/regenerate-code
     * Regenerar código de verificação
     */
    public function regenerateCode(): void
    {
        $userId = $_SESSION['user_id'] ?? null;
        
        if (!$userId) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Não autenticado']);
            return;
        }
        
        try {
            // Verificar se já está verificado
            $current = Database::fetch("
                SELECT is_verified FROM discord_users WHERE user_id = :user_id
            ", ['user_id' => $userId]);
            
            if ($current && $current['is_verified']) {
                http_response_code(400);
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Não é possível regenerar código de conta já verificada'
                ]);
                return;
            }
            
            // Gerar novo código
            $newCode = $this->generateVerificationCode();
            
            Database::query("
                UPDATE discord_users
                SET verification_code = :code,
                    updated_at = NOW()
                WHERE user_id = :user_id
            ", [
                'code' => $newCode,
                'user_id' => $userId
            ]);
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'code' => $newCode,
                'message' => 'Novo código gerado com sucesso'
            ]);
        } catch (\Exception $e) {
            error_log('[CommunityController] Erro ao regenerar código: ' . $e->getMessage());
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Erro ao gerar novo código'
            ]);
        }
    }
    
    /**
     * Gera código de verificação único de 32 caracteres
     */
    private function generateVerificationCode(): string
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $maxAttempts = 10;
        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $code = '';
            for ($i = 0; $i < 32; $i++) {
                $code .= $chars[random_int(0, strlen($chars) - 1)];
            }
            // Verificar duplicata no banco para garantir unicidade
            $exists = Database::fetch(
                "SELECT 1 FROM discord_users WHERE verification_code = :code",
                ['code' => $code]
            );
            if (!$exists) {
                return $code;
            }
        }
        // Se não conseguiu gerar um código único após diversas tentativas, lançar exceção
        throw new \RuntimeException('Falha ao gerar código único de verificação');
    }
}
