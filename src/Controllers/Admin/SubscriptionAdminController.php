<?php
/**
 * SubscriptionAdminController - Gerenciamento de assinaturas no admin
 * 
 * Permite visualizar, gerenciar assinaturas, dar tiers manualmente,
 * estender trials e gerenciar cupons.
 */

namespace App\Controllers\Admin;

use App\Core\Database;
use App\Services\SubscriptionService;
use App\Services\AdminAuthService;

class SubscriptionAdminController
{
    private ?SubscriptionService $subscriptionService = null;
    private AdminAuthService $adminAuthService;
    
    public function __construct()
    {
        $this->adminAuthService = new AdminAuthService();
    }
    
    /**
     * Retorna o SubscriptionService (lazy loading)
     */
    private function getSubscriptionService(): SubscriptionService
    {
        if ($this->subscriptionService === null) {
            $this->subscriptionService = new SubscriptionService();
        }
        return $this->subscriptionService;
    }
    
    /**
     * Lista todas as assinaturas
     * GET /secure/adm/subscriptions
     */
    public function index(): void
    {
        $admin = $this->adminAuthService->getCurrentAdmin();
        
        // Filtros
        $status = $_GET['status'] ?? '';
        $tier = $_GET['tier'] ?? '';
        $search = $_GET['search'] ?? '';
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 20;
        $offset = ($page - 1) * $perPage;
        
        // Query base
        $sql = "SELECT s.*, u.name as user_name, u.email as user_email, u.cpf as user_cpf,
                       p.name as plan_name
                FROM subscriptions s
                JOIN users u ON s.user_id = u.id
                LEFT JOIN subscription_plans p ON s.plan_slug = p.slug
                WHERE 1=1";
        $params = [];
        
        if ($status) {
            $sql .= " AND s.status = ?";
            $params[] = $status;
        }
        
        if ($tier) {
            $sql .= " AND s.tier = ?";
            $params[] = $tier;
        }
        
        if ($search) {
            $sql .= " AND (u.name ILIKE ? OR u.email ILIKE ? OR u.cpf LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }
        
        // Count total
        $countSql = "SELECT COUNT(*) FROM subscriptions s JOIN users u ON s.user_id = u.id WHERE 1=1";
        $countParams = [];
        if ($status) {
            $countSql .= " AND s.status = ?";
            $countParams[] = $status;
        }
        if ($tier) {
            $countSql .= " AND s.tier = ?";
            $countParams[] = $tier;
        }
        if ($search) {
            $countSql .= " AND (u.name ILIKE ? OR u.email ILIKE ? OR u.cpf LIKE ?)";
            $countParams[] = "%{$search}%";
            $countParams[] = "%{$search}%";
            $countParams[] = "%{$search}%";
        }
        $total = Database::fetchColumn($countSql, $countParams);
        $totalPages = ceil($total / $perPage);
        
        // Order and paginate
        $sql .= " ORDER BY s.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $perPage;
        $params[] = $offset;
        
        $subscriptions = Database::fetchAll($sql, $params);
        
        // Stats
        $stats = [
            'total' => Database::fetchColumn("SELECT COUNT(*) FROM subscriptions"),
            'active' => Database::fetchColumn("SELECT COUNT(*) FROM subscriptions WHERE status IN ('active', 'trialing')"),
            'trialing' => Database::fetchColumn("SELECT COUNT(*) FROM subscriptions WHERE status = 'trialing'"),
            'canceled' => Database::fetchColumn("SELECT COUNT(*) FROM subscriptions WHERE status = 'canceled'"),
            'manual' => Database::fetchColumn("SELECT COUNT(*) FROM subscriptions WHERE source = 'admin'"),
        ];
        
        require __DIR__ . '/../../Views/admin_secure/subscriptions/index.php';
    }
    
    /**
     * Visualiza detalhes de uma assinatura
     * GET /secure/adm/subscriptions/view?id=X
     */
    public function view(): void
    {
        $admin = $this->adminAuthService->getCurrentAdmin();
        $id = (int)($_GET['id'] ?? 0);
        
        if (!$id) {
            header('Location: /secure/adm/subscriptions');
            exit;
        }
        
        $subscription = Database::fetch(
            "SELECT s.*, u.name as user_name, u.email as user_email, u.cpf as user_cpf,
                    u.tier as user_tier, u.subscription_expires_at,
                    p.name as plan_name, p.price_cents,
                    a.name as admin_granted_name
             FROM subscriptions s
             JOIN users u ON s.user_id = u.id
             LEFT JOIN subscription_plans p ON s.plan_slug = p.slug
             LEFT JOIN admin_users a ON s.admin_granted_by = a.id
             WHERE s.id = ?",
            [$id]
        );
        
        if (!$subscription) {
            header('Location: /secure/adm/subscriptions');
            exit;
        }
        
        // Histórico de pagamentos
        $payments = Database::fetchAll(
            "SELECT * FROM payment_history 
             WHERE subscription_id = ? 
             ORDER BY created_at DESC 
             LIMIT 20",
            [$id]
        );
        
        // Extensões de trial
        $trialExtensions = Database::fetchAll(
            "SELECT te.*, a.name as admin_name
             FROM trial_extensions te
             LEFT JOIN admin_users a ON te.granted_by = a.id
             WHERE te.subscription_id = ?
             ORDER BY te.created_at DESC",
            [$id]
        );
        
        require __DIR__ . '/../../Views/admin_secure/subscriptions/view.php';
    }
    
    /**
     * Formulário para dar tier manualmente
     * GET /secure/adm/subscriptions/grant
     */
    public function grantForm(): void
    {
        $admin = $this->adminAuthService->getCurrentAdmin();
        $userId = (int)($_GET['user_id'] ?? 0);
        
        $user = null;
        if ($userId) {
            $user = Database::fetch("SELECT * FROM users WHERE id = ?", [$userId]);
        }
        
        $error = $_GET['error'] ?? null;
        $success = $_GET['success'] ?? null;
        
        require __DIR__ . '/../../Views/admin_secure/subscriptions/grant.php';
    }
    
    /**
     * Processa dar tier manualmente
     * POST /secure/adm/subscriptions/grant
     */
    public function grant(): void
    {
        $admin = $this->adminAuthService->getCurrentAdmin();
        
        // Validar CSRF
        $token = $_POST['csrf_token'] ?? '';
        if (!$this->validateCsrf($token)) {
            header('Location: /secure/adm/subscriptions/grant?error=csrf');
            exit;
        }
        
        $userId = (int)($_POST['user_id'] ?? 0);
        $tier = $_POST['tier'] ?? '';
        $expiresAt = $_POST['expires_at'] ?? null;
        $notes = trim($_POST['notes'] ?? '');
        
        // Validações
        if (!$userId || !in_array($tier, ['PLUS', 'PRO'])) {
            header('Location: /secure/adm/subscriptions/grant?error=invalid');
            exit;
        }
        
        $user = Database::fetch("SELECT * FROM users WHERE id = ?", [$userId]);
        if (!$user) {
            header('Location: /secure/adm/subscriptions/grant?error=user_not_found');
            exit;
        }
        
        try {
            $result = $this->getSubscriptionService()->grantTierManually(
                $userId,
                $tier,
                $expiresAt ?: null,
                $admin['id'],
                $notes
            );
            
            if ($result['success']) {
                header('Location: /secure/adm/subscriptions?success=tier_granted');
            } else {
                header('Location: /secure/adm/subscriptions/grant?user_id=' . $userId . '&error=' . urlencode($result['error']));
            }
        } catch (\Throwable $e) {
            error_log("[SubscriptionAdmin] Erro ao dar tier: " . $e->getMessage());
            header('Location: /secure/adm/subscriptions/grant?user_id=' . $userId . '&error=exception');
        }
        exit;
    }
    
    /**
     * Formulário para estender trial
     * GET /secure/adm/subscriptions/extend-trial
     */
    public function extendTrialForm(): void
    {
        $admin = $this->adminAuthService->getCurrentAdmin();
        $subscriptionId = (int)($_GET['subscription_id'] ?? 0);
        $userId = (int)($_GET['user_id'] ?? 0);
        
        $subscription = null;
        $user = null;
        
        if ($subscriptionId) {
            $subscription = Database::fetch(
                "SELECT s.*, u.name as user_name, u.email as user_email
                 FROM subscriptions s
                 JOIN users u ON s.user_id = u.id
                 WHERE s.id = ?",
                [$subscriptionId]
            );
            if ($subscription) {
                $userId = $subscription['user_id'];
            }
        }
        
        if ($userId && !$subscription) {
            $user = Database::fetch("SELECT * FROM users WHERE id = ?", [$userId]);
        }
        
        $error = $_GET['error'] ?? null;
        $success = $_GET['success'] ?? null;
        
        require __DIR__ . '/../../Views/admin_secure/subscriptions/extend_trial.php';
    }
    
    /**
     * Processa extensão de trial
     * POST /secure/adm/subscriptions/extend-trial
     */
    public function extendTrial(): void
    {
        $admin = $this->adminAuthService->getCurrentAdmin();
        
        // Validar CSRF
        $token = $_POST['csrf_token'] ?? '';
        if (!$this->validateCsrf($token)) {
            header('Location: /secure/adm/subscriptions/extend-trial?error=csrf');
            exit;
        }
        
        $userId = (int)($_POST['user_id'] ?? 0);
        $days = (int)($_POST['days'] ?? 0);
        $reason = trim($_POST['reason'] ?? '');
        
        if (!$userId || $days < 1 || $days > 365) {
            header('Location: /secure/adm/subscriptions/extend-trial?user_id=' . $userId . '&error=invalid');
            exit;
        }
        
        try {
            $result = $this->getSubscriptionService()->extendTrial($userId, $days, $admin['id'], $reason);
            
            if ($result['success']) {
                header('Location: /secure/adm/subscriptions?success=trial_extended');
            } else {
                header('Location: /secure/adm/subscriptions/extend-trial?user_id=' . $userId . '&error=' . urlencode($result['error']));
            }
        } catch (\Throwable $e) {
            error_log("[SubscriptionAdmin] Erro ao estender trial: " . $e->getMessage());
            header('Location: /secure/adm/subscriptions/extend-trial?user_id=' . $userId . '&error=exception');
        }
        exit;
    }
    
    /**
     * Histórico de pagamentos geral
     * GET /secure/adm/subscriptions/payments
     */
    public function payments(): void
    {
        $admin = $this->adminAuthService->getCurrentAdmin();
        
        // Filtros
        $status = $_GET['status'] ?? '';
        $search = $_GET['search'] ?? '';
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 30;
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT ph.*, u.name as user_name, u.email as user_email
                FROM payment_history ph
                JOIN users u ON ph.user_id = u.id
                WHERE 1=1";
        $params = [];
        
        if ($status) {
            $sql .= " AND ph.status = ?";
            $params[] = $status;
        }
        
        if ($search) {
            $sql .= " AND (u.name ILIKE ? OR u.email ILIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }
        
        // Count total
        $countSql = "SELECT COUNT(*) FROM payment_history ph JOIN users u ON ph.user_id = u.id WHERE 1=1";
        $countParams = [];
        if ($status) {
            $countSql .= " AND ph.status = ?";
            $countParams[] = $status;
        }
        if ($search) {
            $countSql .= " AND (u.name ILIKE ? OR u.email ILIKE ?)";
            $countParams[] = "%{$search}%";
            $countParams[] = "%{$search}%";
        }
        $total = Database::fetchColumn($countSql, $countParams);
        $totalPages = ceil($total / $perPage);
        
        $sql .= " ORDER BY ph.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $perPage;
        $params[] = $offset;
        
        $payments = Database::fetchAll($sql, $params);
        
        // Stats
        $stats = [
            'total' => Database::fetchColumn("SELECT COUNT(*) FROM payment_history"),
            'succeeded' => Database::fetchColumn("SELECT COUNT(*) FROM payment_history WHERE status = 'succeeded'"),
            'failed' => Database::fetchColumn("SELECT COUNT(*) FROM payment_history WHERE status = 'failed'"),
            'total_amount' => Database::fetchColumn("SELECT COALESCE(SUM(amount_cents), 0) FROM payment_history WHERE status = 'succeeded'"),
        ];
        
        require __DIR__ . '/../../Views/admin_secure/subscriptions/payments.php';
    }
    
    /**
     * Lista de cupons
     * GET /secure/adm/coupons
     */
    public function coupons(): void
    {
        $admin = $this->adminAuthService->getCurrentAdmin();
        
        // Query simplificada usando apenas username
        try {
            $coupons = Database::fetchAll(
                "SELECT c.*, a.username as created_by_name,
                        (SELECT COUNT(*) FROM coupon_redemptions WHERE coupon_id = c.id) as usage_count
                 FROM coupons c
                 LEFT JOIN admin_users a ON c.created_by = a.id
                 ORDER BY c.created_at DESC"
            );
        } catch (\Throwable $e) {
            // Se der erro, tentar sem o JOIN
            $coupons = Database::fetchAll(
                "SELECT c.*, NULL as created_by_name,
                        (SELECT COUNT(*) FROM coupon_redemptions WHERE coupon_id = c.id) as usage_count
                 FROM coupons c
                 ORDER BY c.created_at DESC"
            );
        }
        
        $error = $_GET['error'] ?? null;
        $success = $_GET['success'] ?? null;
        
        require __DIR__ . '/../../Views/admin_secure/subscriptions/coupons.php';
    }
    
    /**
     * Formulário de criação de cupom
     * GET /secure/adm/coupons/create
     */
    public function createCouponForm(): void
    {
        $admin = $this->adminAuthService->getCurrentAdmin();
        
        $plans = Database::fetchAll("SELECT * FROM subscription_plans WHERE is_active = true ORDER BY display_order");
        
        $error = $_GET['error'] ?? null;
        
        require __DIR__ . '/../../Views/admin_secure/subscriptions/coupon_create.php';
    }
    
    /**
     * Processa criação de cupom
     * POST /secure/adm/coupons/create
     */
    public function createCoupon(): void
    {
        $admin = $this->adminAuthService->getCurrentAdmin();
        
        // Validar CSRF
        $token = $_POST['csrf_token'] ?? '';
        if (!$this->validateCsrf($token)) {
            header('Location: /secure/adm/coupons/create?error=csrf');
            exit;
        }
        
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $discountType = $_POST['discount_type'] ?? 'percent';
        $discountValue = (int)($_POST['discount_value'] ?? 0);
        $maxRedemptions = $_POST['max_redemptions'] ? (int)$_POST['max_redemptions'] : null;
        $validUntil = $_POST['valid_until'] ?: null;
        $notes = trim($_POST['notes'] ?? '');
        
        // Validações
        if (!$code || strlen($code) < 3) {
            header('Location: /secure/adm/coupons/create?error=invalid_code');
            exit;
        }
        
        if ($discountValue < 1) {
            header('Location: /secure/adm/coupons/create?error=invalid_value');
            exit;
        }
        
        if ($discountType === 'percent' && $discountValue > 100) {
            header('Location: /secure/adm/coupons/create?error=percent_over_100');
            exit;
        }
        
        // Verificar se código já existe
        $existing = Database::fetch("SELECT id FROM coupons WHERE code = ?", [$code]);
        if ($existing) {
            header('Location: /secure/adm/coupons/create?error=code_exists');
            exit;
        }
        
        try {
            Database::execute(
                "INSERT INTO coupons (code, discount_type, discount_value, max_redemptions, valid_until, notes, created_by, is_active)
                 VALUES (?, ?, ?, ?, ?, ?, ?, true)",
                [$code, $discountType, $discountValue, $maxRedemptions, $validUntil, $notes, $admin['id']]
            );
            
            header('Location: /secure/adm/coupons?success=created');
        } catch (\Throwable $e) {
            error_log("[SubscriptionAdmin] Erro ao criar cupom: " . $e->getMessage());
            header('Location: /secure/adm/coupons/create?error=exception');
        }
        exit;
    }
    
    /**
     * Toggle ativo/inativo de cupom
     * POST /secure/adm/coupons/toggle
     */
    public function toggleCoupon(): void
    {
        $admin = $this->adminAuthService->getCurrentAdmin();
        
        $id = (int)($_POST['id'] ?? 0);
        
        if ($id) {
            $coupon = Database::fetch("SELECT is_active FROM coupons WHERE id = ?", [$id]);
            if ($coupon) {
                $newStatus = !$coupon['is_active'];
                Database::execute("UPDATE coupons SET is_active = ?, updated_at = NOW() WHERE id = ?", [$newStatus, $id]);
            }
        }
        
        header('Location: /secure/adm/coupons');
        exit;
    }
    
    /**
     * Validação simples de CSRF
     */
    private function validateCsrf(string $token): bool
    {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}
