<?php

namespace App\Controllers\Api;

use App\Core\Application;
use App\Core\Database;
use App\Controllers\BaseController;

/**
 * API Controller para gerenciamento de assinaturas/tiers de usuários
 * Permite que sistemas externos (Portal do Aluno, etc) atualizem o tier do usuário
 */
class SubscriptionApiController extends BaseController
{
    /**
     * Valida a API Key do header
     * @return bool
     */
    private function validateApiKey(): bool
    {
        $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
        $validKey = trim((string)($_ENV['SUBSCRIPTION_API_KEY'] ?? ''));
        
        if ($validKey === '' || $apiKey === '') {
            return false;
        }
        
        return hash_equals($validKey, $apiKey);
    }
    
    /**
     * Logar operação para auditoria
     */
    private function logOperation(string $action, array $data): void
    {
        try {
            $logger = Application::getInstance()->logger();
            $logger->info('Subscription API: ' . $action, array_merge($data, [
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                'timestamp' => date('Y-m-d H:i:s')
            ]));
        } catch (\Throwable $e) {
            // Ignorar erros de log
        }
    }

    /**
     * POST /api/subscription/update
     * Atualiza o tier de um usuário
     * 
     * Headers:
     *   X-API-KEY: chave_secreta
     * 
     * Body (JSON):
     *   {
     *     "user_id": 123,           // ou "email": "user@example.com"
     *     "tier": "PLUS",           // FREE, PLUS, PRO
     *     "expires_at": "2025-12-31 23:59:59"  // opcional
     *   }
     */
    public function update(): void
    {
        // 1. Validar API Key
        if (!$this->validateApiKey()) {
            $this->logOperation('update_failed', ['reason' => 'Invalid API key']);
            $this->json([
                'success' => false,
                'error' => 'Unauthorized',
                'message' => 'Invalid or missing API key'
            ], 401);
            return;
        }
        
        // 2. Obter dados do body
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || !is_array($input)) {
            $this->json([
                'success' => false,
                'error' => 'Bad Request',
                'message' => 'Invalid JSON body'
            ], 400);
            return;
        }
        
        // 3. Identificar usuário (por ID ou email)
        $userId = (int)($input['user_id'] ?? 0);
        $email = trim((string)($input['email'] ?? ''));
        
        if ($userId <= 0 && $email === '') {
            $this->json([
                'success' => false,
                'error' => 'Bad Request',
                'message' => 'user_id or email is required'
            ], 400);
            return;
        }
        
        // 4. Validar tier
        $tier = strtoupper(trim((string)($input['tier'] ?? '')));
        $validTiers = ['FREE', 'PLUS', 'PRO'];
        
        if (!in_array($tier, $validTiers, true)) {
            $this->json([
                'success' => false,
                'error' => 'Bad Request',
                'message' => 'Invalid tier. Must be one of: ' . implode(', ', $validTiers)
            ], 400);
            return;
        }
        
        // 5. Validar expires_at (opcional)
        $expiresAt = null;
        if (!empty($input['expires_at'])) {
            $expiresAt = $input['expires_at'];
            // Validar formato de data
            $date = \DateTime::createFromFormat('Y-m-d H:i:s', $expiresAt);
            if (!$date) {
                $date = \DateTime::createFromFormat('Y-m-d', $expiresAt);
                if ($date) {
                    $expiresAt = $date->format('Y-m-d') . ' 23:59:59';
                }
            }
            
            if (!$date) {
                $this->json([
                    'success' => false,
                    'error' => 'Bad Request',
                    'message' => 'Invalid expires_at format. Use YYYY-MM-DD or YYYY-MM-DD HH:MM:SS'
                ], 400);
                return;
            }
        }
        
        // 6. Buscar usuário
        try {
            if ($userId > 0) {
                $user = Database::fetch(
                    'SELECT id, email, tier FROM users WHERE id = ? AND deleted_at IS NULL',
                    [$userId]
                );
            } else {
                $user = Database::fetch(
                    'SELECT id, email, tier FROM users WHERE email = ? AND deleted_at IS NULL',
                    [$email]
                );
            }
            
            if (!$user) {
                $this->logOperation('update_failed', [
                    'reason' => 'User not found',
                    'user_id' => $userId,
                    'email' => $email
                ]);
                $this->json([
                    'success' => false,
                    'error' => 'Not Found',
                    'message' => 'User not found'
                ], 404);
                return;
            }
            
            $userId = (int)$user['id'];
            $oldTier = $user['tier'] ?? 'FREE';
            
            // 7. Atualizar tier
            $updateData = [
                'tier' => $tier,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            if ($expiresAt !== null) {
                $updateData['subscription_expires_at'] = $expiresAt;
            }
            
            Database::update('users', $updateData, ['id' => $userId]);
            
            // 8. Logar operação
            $this->logOperation('update_success', [
                'user_id' => $userId,
                'email' => $user['email'],
                'old_tier' => $oldTier,
                'new_tier' => $tier,
                'expires_at' => $expiresAt
            ]);
            
            // 9. Retornar sucesso
            $response = [
                'success' => true,
                'message' => "Tier updated from {$oldTier} to {$tier}",
                'data' => [
                    'user_id' => $userId,
                    'email' => $user['email'],
                    'old_tier' => $oldTier,
                    'new_tier' => $tier
                ]
            ];
            
            if ($expiresAt !== null) {
                $response['data']['expires_at'] = $expiresAt;
            }
            
            $this->json($response);
            
        } catch (\Exception $e) {
            $this->logOperation('update_error', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
                'email' => $email
            ]);
            
            $this->json([
                'success' => false,
                'error' => 'Internal Server Error',
                'message' => 'Failed to update user tier'
            ], 500);
        }
    }
    
    /**
     * GET /api/subscription/status
     * Retorna o status da assinatura de um usuário
     * 
     * Headers:
     *   X-API-KEY: chave_secreta
     * 
     * Query:
     *   ?user_id=123 ou ?email=user@example.com
     */
    public function status(): void
    {
        // 1. Validar API Key
        if (!$this->validateApiKey()) {
            $this->json([
                'success' => false,
                'error' => 'Unauthorized',
                'message' => 'Invalid or missing API key'
            ], 401);
            return;
        }
        
        // 2. Obter identificador do usuário
        $userId = (int)($_GET['user_id'] ?? 0);
        $email = trim((string)($_GET['email'] ?? ''));
        
        if ($userId <= 0 && $email === '') {
            $this->json([
                'success' => false,
                'error' => 'Bad Request',
                'message' => 'user_id or email query parameter is required'
            ], 400);
            return;
        }
        
        // 3. Buscar usuário
        try {
            if ($userId > 0) {
                $user = Database::fetch(
                    'SELECT id, email, tier, subscription_expires_at, created_at FROM users WHERE id = ? AND deleted_at IS NULL',
                    [$userId]
                );
            } else {
                $user = Database::fetch(
                    'SELECT id, email, tier, subscription_expires_at, created_at FROM users WHERE email = ? AND deleted_at IS NULL',
                    [$email]
                );
            }
            
            if (!$user) {
                $this->json([
                    'success' => false,
                    'error' => 'Not Found',
                    'message' => 'User not found'
                ], 404);
                return;
            }
            
            // 4. Verificar se assinatura expirou
            $tier = $user['tier'] ?? 'FREE';
            $expiresAt = $user['subscription_expires_at'] ?? null;
            $isExpired = false;
            
            if ($expiresAt && $tier !== 'FREE') {
                $now = new \DateTime();
                $expDate = new \DateTime($expiresAt);
                $isExpired = $now > $expDate;
            }
            
            $this->json([
                'success' => true,
                'data' => [
                    'user_id' => (int)$user['id'],
                    'email' => $user['email'],
                    'tier' => $tier,
                    'is_active' => !$isExpired,
                    'expires_at' => $expiresAt,
                    'member_since' => $user['created_at']
                ]
            ]);
            
        } catch (\Exception $e) {
            $this->json([
                'success' => false,
                'error' => 'Internal Server Error',
                'message' => 'Failed to fetch subscription status'
            ], 500);
        }
    }
    
    /**
     * GET /api/subscription/ping
     * Health check (não requer autenticação)
     */
    public function ping(): void
    {
        $this->json([
            'success' => true,
            'message' => 'Subscription API is running',
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => '1.0'
        ]);
    }
}
