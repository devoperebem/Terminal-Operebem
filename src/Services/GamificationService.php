<?php

namespace App\Services;

use App\Core\Database;
use Carbon\Carbon;

/**
 * Sistema de Gamificação - XP e Streak
 * 
 * Regras:
 * - Login diário: XP configurável via admin
 * - Streak: conta dias consecutivos de login
 * - Quebra se não logar por 48h+
 */
class GamificationService
{
    /**
     * Obter XP de login diário (configurável)
     */
    private function getLoginXP(): int
    {
        return XPSettingsService::get('xp_daily_login', 5);
    }
    
    /**
     * Processar login diário do usuário
     * Chamado após autenticação bem-sucedida
     */
    public function processLogin(int $userId): array
    {
        try {
            // Buscar dados atuais do usuário
            $user = Database::fetch(
                "SELECT xp, streak, level, last_login_at FROM users WHERE id = ?",
                [$userId]
            );
            
            if (!$user) {
                error_log("[GamificationService] Usuário $userId não encontrado");
                throw new \Exception('Usuário não encontrado');
            }
            
            $now = Carbon::now();
            $lastLogin = $user['last_login_at'] ? Carbon::parse($user['last_login_at']) : null;
            
            error_log("[GamificationService] User $userId - Last login: " . ($lastLogin ? $lastLogin->toDateTimeString() : 'never') . " | Now: " . $now->toDateTimeString());
            
            $xpEarned = 0;
            $streakIncreased = false;
            $streakBroken = false;
            $newStreak = (int)($user['streak'] ?? 0);
            
            // Verificar se é um novo dia
            $isSameDay = $lastLogin && $lastLogin->isSameDay($now);
            
            if (!$isSameDay) {
                // Dar XP por login diário (configurável)
                $xpEarned = $this->getLoginXP();
                
                // Calcular streak
                if ($lastLogin) {
                    $hoursSinceLastLogin = $lastLogin->diffInHours($now);
                    
                    // Se logou nas últimas 48h, continua streak
                    if ($hoursSinceLastLogin <= 48) {
                        $newStreak = ($user['streak'] ?? 0) + 1;
                        $streakIncreased = true;
                        error_log("[GamificationService] User $userId - Streak increased to $newStreak");
                    } else {
                        // Streak quebrado
                        $newStreak = 1;
                        $streakBroken = true;
                        error_log("[GamificationService] User $userId - Streak broken, reset to 1");
                    }
                } else {
                    // Primeiro login
                    $newStreak = 1;
                    $streakIncreased = true;
                    error_log("[GamificationService] User $userId - First login, streak set to 1");
                }
                
                $oldXp = (int)($user['xp'] ?? 0);
                $newXp = $oldXp + $xpEarned;
                $oldLevel = (int)($user['level'] ?? 1);
                $newLevel = $this->calculateLevel($newXp);
                
                // Atualizar no banco
                Database::update('users', [
                    'xp' => $newXp,
                    'streak' => $newStreak,
                    'level' => $newLevel,
                    'last_login_at' => $now->toDateTimeString()
                ], [
                    'id' => $userId
                ]);
                
                // Registrar no histórico
                try {
                    Database::insert('xp_history', [
                        'user_id' => $userId,
                        'amount' => $xpEarned,
                        'source' => 'daily_login',
                        'source_id' => null,
                        'description' => 'Login diário',
                        'created_at' => $now->toDateTimeString()
                    ]);
                } catch (\Exception $e) {
                    error_log('[GamificationService] Erro ao registrar histórico: ' . $e->getMessage());
                }
                
                error_log("[GamificationService] User $userId earned $xpEarned XP (Total: $oldXp -> $newXp, Level: $oldLevel -> $newLevel)");
                
                return [
                    'xp_earned' => $xpEarned,
                    'total_xp' => $newXp,
                    'streak' => $newStreak,
                    'level' => $newLevel,
                    'streak_increased' => $streakIncreased,
                    'streak_broken' => $streakBroken
                ];
            }
            
            // Nenhuma mudança (já logou hoje)
            error_log("[GamificationService] User $userId already logged in today");
            return [
                'xp_earned' => 0,
                'total_xp' => (int)($user['xp'] ?? 0),
                'streak' => (int)($user['streak'] ?? 0),
                'level' => (int)($user['level'] ?? 1),
                'streak_increased' => false,
                'streak_broken' => false
            ];
            
        } catch (\Exception $e) {
            error_log('[GamificationService] Erro crítico: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
            return [
                'xp_earned' => 0,
                'total_xp' => 0,
                'streak' => 0,
                'level' => 1,
                'streak_increased' => false,
                'streak_broken' => false
            ];
        }
    }
    
    /**
     * Adicionar XP manualmente (admin ou eventos futuros)
     */
    public function addXP(int $userId, int $amount, string $reason = '', string $source = 'manual', ?int $sourceId = null): bool
    {
        try {
            $user = Database::fetch("SELECT xp, level FROM users WHERE id = ?", [$userId]);
            if (!$user) return false;
            
            $oldXp = (int)($user['xp'] ?? 0);
            $newXp = $oldXp + $amount;
            $oldLevel = (int)($user['level'] ?? 1);
            $newLevel = $this->calculateLevel($newXp);
            
            Database::update('users', [
                'xp' => $newXp,
                'level' => $newLevel,
                'last_xp_earned_at' => date('Y-m-d H:i:s')
            ], [
                'id' => $userId
            ]);
            
            // Registrar no histórico
            try {
                Database::insert('xp_history', [
                    'user_id' => $userId,
                    'amount' => $amount,
                    'source' => $source,
                    'source_id' => $sourceId,
                    'description' => $reason,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            } catch (\Exception $e) {
                error_log('[GamificationService] Erro ao registrar histórico: ' . $e->getMessage());
            }
            
            // Log
            error_log("[GamificationService] User $userId earned $amount XP: $reason (Level: $oldLevel -> $newLevel)");
            
            return true;
        } catch (\Exception $e) {
            error_log('[GamificationService] addXP error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Calcular nível baseado em XP
     * Fórmula: level = floor(sqrt(xp / 10))
     * 
     * Progressão:
     * Level 1: 0-9 XP
     * Level 2: 10-39 XP
     * Level 3: 40-89 XP
     * Level 4: 90-159 XP
     * Level 5: 160-249 XP
     * ...
     */
    public function calculateLevel(int $xp): int
    {
        return max(1, (int)floor(sqrt($xp / 10)));
    }
    
    /**
     * Calcular XP necessário para próximo nível
     */
    public function getNextLevelXP(int $currentLevel): int
    {
        return (int)pow($currentLevel + 1, 2) * 10;
    }
    
    /**
     * Resetar streak (admin)
     */
    public function resetStreak(int $userId): bool
    {
        try {
            Database::update('users', [
                'streak' => 0
            ], [
                'id' => $userId
            ]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Obter estatísticas do usuário
     */
    public function getUserStats(int $userId): array
    {
        $user = Database::fetch(
            "SELECT xp, streak, level, last_login_at, last_xp_earned_at, created_at FROM users WHERE id = ?",
            [$userId]
        );
        
        if (!$user) {
            return [
                'xp' => 0,
                'streak' => 0,
                'level' => 1,
                'next_level_xp' => 10,
                'current_level_xp' => 0,
                'progress_percent' => 0,
                'last_login' => null,
                'last_xp_earned' => null,
                'member_since' => null
            ];
        }
        
        $xp = (int)($user['xp'] ?? 0);
        $level = (int)($user['level'] ?? 1);
        $nextLevelXp = $this->getNextLevelXP($level);
        $currentLevelXp = (int)pow($level, 2) * 10;
        $progressPercent = $xp > $currentLevelXp ? (($xp - $currentLevelXp) / ($nextLevelXp - $currentLevelXp)) * 100 : 0;
        
        return [
            'xp' => $xp,
            'streak' => (int)($user['streak'] ?? 0),
            'level' => $level,
            'next_level_xp' => $nextLevelXp,
            'current_level_xp' => $currentLevelXp,
            'progress_percent' => round($progressPercent, 1),
            'last_login' => $user['last_login_at'],
            'last_xp_earned' => $user['last_xp_earned_at'],
            'member_since' => $user['created_at']
        ];
    }
}
