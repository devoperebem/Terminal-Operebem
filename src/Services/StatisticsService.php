<?php

namespace App\Services;

use App\Core\Database;
use App\Core\Application;

class StatisticsService
{
    private static ?int $userCount = null;
    
    public static function getUserCount(): int
    {
        if (self::$userCount === null) {
            try {
                $result = Database::fetch(
                    "SELECT COUNT(*) as total FROM users WHERE deleted_at IS NULL"
                );
                self::$userCount = $result['total'] ?? 0;
            } catch (\Exception $e) {
                Application::getInstance()->logger()->error('Erro ao contar usuários: ' . $e->getMessage());
                self::$userCount = 0; // Fallback para 0 em caso de erro
            }
        }
        
        return self::$userCount;
    }
    
    public static function getActiveAssets(): int
    {
        // Retorna número fixo de ativos conforme solicitado
        return 135;
    }
    
    public static function getUptime(): float
    {
        // Retorna uptime fixo
        return 99.9;
    }
    
    public static function getDataProcessed(): int
    {
        // Retorna dados processados em TB por mês
        return 2;
    }
    
    public static function getSupportHours(): int
    {
        // Retorna horas de suporte fixo
        return 24;
    }
}
