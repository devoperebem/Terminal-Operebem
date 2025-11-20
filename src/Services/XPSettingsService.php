<?php

namespace App\Services;

use App\Core\Database;

/**
 * Serviço para gerenciar configurações dinâmicas do sistema de XP
 */
class XPSettingsService
{
    private static array $cache = [];
    
    /**
     * Obter valor de uma configuração
     */
    public static function get(string $key, int $default = 0): int
    {
        // Usar cache em memória para evitar queries repetidas
        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }
        
        try {
            $result = Database::fetch(
                "SELECT setting_value FROM xp_settings WHERE setting_key = ?",
                [$key]
            );
            
            $value = $result ? (int)$result['setting_value'] : $default;
            self::$cache[$key] = $value;
            
            return $value;
        } catch (\Exception $e) {
            error_log('[XPSettingsService] Erro ao buscar configuração: ' . $e->getMessage());
            return $default;
        }
    }
    
    /**
     * Definir valor de uma configuração
     */
    public static function set(string $key, int $value, ?int $adminId = null): bool
    {
        try {
            // Verificar se a configuração existe
            $exists = Database::fetch(
                "SELECT id FROM xp_settings WHERE setting_key = ?",
                [$key]
            );
            
            if ($exists) {
                // Atualizar
                Database::update('xp_settings', [
                    'setting_value' => $value,
                    'updated_at' => date('Y-m-d H:i:s'),
                    'updated_by' => $adminId
                ], [
                    'setting_key' => $key
                ]);
            } else {
                // Inserir
                Database::insert('xp_settings', [
                    'setting_key' => $key,
                    'setting_value' => $value,
                    'updated_at' => date('Y-m-d H:i:s'),
                    'updated_by' => $adminId
                ]);
            }
            
            // Atualizar cache
            self::$cache[$key] = $value;
            
            return true;
        } catch (\Exception $e) {
            error_log('[XPSettingsService] Erro ao salvar configuração: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obter todas as configurações
     */
    public static function getAll(): array
    {
        try {
            $results = Database::fetchAll(
                "SELECT setting_key, setting_value, description, updated_at 
                 FROM xp_settings 
                 ORDER BY setting_key"
            );
            
            $settings = [];
            foreach ($results as $row) {
                $settings[$row['setting_key']] = [
                    'value' => (int)$row['setting_value'],
                    'description' => $row['description'],
                    'updated_at' => $row['updated_at']
                ];
                // Atualizar cache
                self::$cache[$row['setting_key']] = (int)$row['setting_value'];
            }
            
            return $settings;
        } catch (\Exception $e) {
            error_log('[XPSettingsService] Erro ao buscar todas configurações: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Limpar cache
     */
    public static function clearCache(): void
    {
        self::$cache = [];
    }
}
