<?php
/**
 * AuditLogService - Serviço para registro de logs de auditoria
 * 
 * Registra todas as ações críticas executadas por admins e usuários,
 * incluindo mudanças em assinaturas, perfil, cupons, etc.
 */

namespace App\Services;

use App\Core\Database;

class AuditLogService
{
    /**
     * Registra uma ação executada por um ADMIN
     * 
     * @param array $data [
     *   'admin_id' => int,
     *   'admin_email' => string,
     *   'user_id' => int (opcional - usuário afetado),
     *   'action_type' => string,
     *   'entity_type' => string,
     *   'entity_id' => int,
     *   'description' => string,
     *   'changes' => array (opcional)
     * ]
     * @return bool
     */
    public static function logAdminAction(array $data): bool
    {
        return self::log([
            'actor_type' => 'admin',
            'actor_id' => $data['admin_id'] ?? null,
            'actor_email' => $data['admin_email'] ?? 'unknown',
            'user_id' => $data['user_id'] ?? null,
            'action_type' => $data['action_type'],
            'entity_type' => $data['entity_type'],
            'entity_id' => $data['entity_id'] ?? null,
            'description' => $data['description'] ?? '',
            'changes' => $data['changes'] ?? null
        ]);
    }
    
    /**
     * Registra uma ação executada pelo PRÓPRIO USUÁRIO
     * 
     * @param array $data [
     *   'user_id' => int,
     *   'user_email' => string,
     *   'action_type' => string,
     *   'entity_type' => string,
     *   'entity_id' => int,
     *   'description' => string,
     *   'changes' => array (opcional)
     * ]
     * @return bool
     */
    public static function logUserAction(array $data): bool
    {
        return self::log([
            'actor_type' => 'user',
            'actor_id' => $data['user_id'],
            'actor_email' => $data['user_email'] ?? '',
            'user_id' => $data['user_id'],
            'action_type' => $data['action_type'],
            'entity_type' => $data['entity_type'],
            'entity_id' => $data['entity_id'] ?? null,
            'description' => $data['description'] ?? '',
            'changes' => $data['changes'] ?? null
        ]);
    }
    
    /**
     * Método interno para registrar log
     */
    private static function log(array $data): bool
    {
        // Obter IP e User Agent
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        
        // Preparar dados
        $actorType = $data['actor_type'] ?? '';
        $actorId = $data['actor_id'] ?? null;
        $actorEmail = $data['actor_email'] ?? '';
        $userId = $data['user_id'] ?? null;
        $actionType = $data['action_type'] ?? '';
        $entityType = $data['entity_type'] ?? '';
        $entityId = $data['entity_id'] ?? null;
        $description = $data['description'] ?? '';
        $changes = !empty($data['changes']) ? json_encode($data['changes']) : null;
        
        // Validação básica
        if (empty($actionType) || empty($entityType)) {
            error_log("[AUDIT] Falha ao registrar log: dados inválidos (action_type ou entity_type vazios)");
            return false;
        }
        
        // Inserir no banco
        try {
            Database::execute(
                'INSERT INTO admin_audit_logs 
                 (actor_type, actor_id, actor_email, user_id, action_type, entity_type, entity_id, description, changes, ip_address, user_agent, created_at) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())',
                [$actorType, $actorId, $actorEmail, $userId, $actionType, $entityType, $entityId, $description, $changes, $ipAddress, $userAgent]
            );
            return true;
        } catch (\Exception $e) {
            error_log("[AUDIT] Erro ao registrar log: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Busca logs de um usuário específico
     * 
     * @param int $userId
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public static function getUserLogs(int $userId, int $limit = 50, int $offset = 0): array
    {
        return Database::fetchAll(
            'SELECT * FROM admin_audit_logs 
             WHERE user_id = ? 
             ORDER BY created_at DESC 
             LIMIT ? OFFSET ?',
            [$userId, $limit, $offset]
        );
    }
    
    /**
     * Conta total de logs de um usuário
     * 
     * @param int $userId
     * @return int
     */
    public static function countUserLogs(int $userId): int
    {
        $result = Database::fetch(
            'SELECT COUNT(*) as count FROM admin_audit_logs WHERE user_id = ?',
            [$userId]
        );
        return (int)($result['count'] ?? 0);
    }
    
    /**
     * Busca logs com filtros
     * 
     * @param array $filters ['action_type' => string, 'entity_type' => string, 'actor_type' => string]
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public static function getLogs(array $filters = [], int $limit = 100, int $offset = 0): array
    {
        $sql = 'SELECT * FROM admin_audit_logs WHERE 1=1';
        $params = [];
        
        if (!empty($filters['user_id'])) {
            $sql .= ' AND user_id = ?';
            $params[] = $filters['user_id'];
        }
        
        if (!empty($filters['actor_type'])) {
            $sql .= ' AND actor_type = ?';
            $params[] = $filters['actor_type'];
        }
        
        if (!empty($filters['action_type'])) {
            $sql .= ' AND action_type = ?';
            $params[] = $filters['action_type'];
        }
        
        if (!empty($filters['entity_type'])) {
            $sql .= ' AND entity_type = ?';
            $params[] = $filters['entity_type'];
        }
        
        $sql .= ' ORDER BY created_at DESC LIMIT ? OFFSET ?';
        $params[] = $limit;
        $params[] = $offset;
        
        return Database::fetchAll($sql, $params);
    }
    
    /**
     * Limpa logs antigos baseado na configuração AUDIT_LOG_RETENTION_DAYS
     * 
     * @return int Quantidade de logs removidos
     */
    public static function cleanOldLogs(): int
    {
        $retentionDays = (int)(getenv('AUDIT_LOG_RETENTION_DAYS') ?: 90);
        
        try {
            $result = Database::execute(
                'DELETE FROM admin_audit_logs 
                 WHERE created_at < NOW() - INTERVAL \' ? days\'',
                [$retentionDays]
            );
            
            // Retornar quantidade de linhas afetadas
            $deleted = Database::fetch('SELECT ROW_COUNT() as count');
            return (int)($deleted['count'] ?? 0);
        } catch (\Exception $e) {
            error_log("[AUDIT] Erro ao limpar logs antigos: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Formata uma entrada de log para exibição legível
     * 
     * @param array $log
     * @return array Log formatado com campos adicionais
     */
    public static function formatLogEntry(array $log): array
    {
        // Decodificar changes se existir
        if (!empty($log['changes'])) {
            $log['changes_decoded'] = json_decode($log['changes'], true);
        }
        
        // Formatar data de forma legível
        $timestamp = strtotime($log['created_at'] ?? '');
        $log['created_at_formatted'] = $timestamp !== false
            ? date('d/m/Y H:i:s', $timestamp)
            : '-';
        $log['created_at_relative'] = self::getRelativeTime($log['created_at']);
        
        // Definir cor/ícone baseado no tipo de ação
        $log['badge_class'] = self::getActionBadgeClass($log['action_type']);
        $log['icon_class'] = self::getActionIcon($log['action_type']);
        
        // Formatar nome do ator
        if ($log['actor_type'] === 'admin') {
            $log['actor_name'] = 'Admin: ' . $log['actor_email'];
        } else {
            $log['actor_name'] = 'Usuário';
        }
        
        return $log;
    }
    
    /**
     * Retorna classe de badge baseado no tipo de ação
     */
    private static function getActionBadgeClass(string $actionType): string
    {
        $critical = ['subscription_canceled', 'password_reset_by_admin', 'account_deleted', 'logout_all_devices'];
        $warning = ['trial_extended', 'trial_reset', 'tier_granted'];
        $info = ['coupon_updated', 'plan_discount_updated', 'profile_updated'];
        
        if (in_array($actionType, $critical)) {
            return 'bg-danger';
        } elseif (in_array($actionType, $warning)) {
            return 'bg-warning text-dark';
        } elseif (in_array($actionType, $info)) {
            return 'bg-info';
        }
        
        return 'bg-secondary';
    }
    
    /**
     * Retorna ícone baseado no tipo de ação
     */
    private static function getActionIcon(string $actionType): string
    {
        $icons = [
            'subscription_canceled' => 'fa-times-circle',
            'password_reset_by_admin' => 'fa-key',
            'logout_all_devices' => 'fa-sign-out-alt',
            'trial_extended' => 'fa-calendar-plus',
            'trial_reset' => 'fa-redo',
            'tier_granted' => 'fa-crown',
            'avatar_changed' => 'fa-image',
            'profile_updated' => 'fa-user-edit',
            'email_changed' => 'fa-envelope',
            'password_changed' => 'fa-lock',
            'coupon_updated' => 'fa-ticket',
            'coupon_deleted' => 'fa-trash',
            'plan_discount_updated' => 'fa-percent',
        ];
        
        return $icons[$actionType] ?? 'fa-info-circle';
    }
    
    /**
     * Retorna tempo relativo (ex: "há 2 horas", "ontem")
     */
    private static function getRelativeTime(string $datetime): string
    {
        $timestamp = strtotime($datetime ?? '');
        if ($timestamp === false || empty($datetime)) {
            return '-';
        }
        $diff = time() - $timestamp;
        
        if ($diff < 60) {
            return 'agora mesmo';
        } elseif ($diff < 3600) {
            $mins = floor($diff / 60);
            return "há {$mins} " . ($mins == 1 ? 'minuto' : 'minutos');
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return "há {$hours} " . ($hours == 1 ? 'hora' : 'horas');
        } elseif ($diff < 172800) {
            return 'ontem';
        } elseif ($diff < 604800) {
            $days = floor($diff / 86400);
            return "há {$days} dias";
        } else {
            return date('d/m/Y', $timestamp);
        }
    }
}
