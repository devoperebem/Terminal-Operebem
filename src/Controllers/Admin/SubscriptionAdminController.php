<?php
/**
 * SubscriptionAdminController - Gerenciamento de assinaturas no admin
 * 
 * Permite visualizar, gerenciar assinaturas, dar tiers manualmente,
 * estender trials e gerenciar cupons.
 */

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Core\Database;
use App\Services\SubscriptionService;
use App\Services\AdminAuthService;

class SubscriptionAdminController extends BaseController
{
    private ?SubscriptionService $subscriptionService = null;
    private AdminAuthService $adminAuthService;
    
    public function __construct()
    {
        parent::__construct();
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
     * Renderiza view admin com footerVariant correto
     */
    private function adminView(string $view, array $data = []): void
    {
        $data['footerVariant'] = 'admin-auth';
        $this->view($view, $data);
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
        
        $this->adminView('admin_secure/subscriptions/index', compact('admin', 'subscriptions', 'stats', 'status', 'tier', 'search', 'page', 'totalPages', 'total'));
    }
    
    /**
     * Visualiza detalhes de uma assinatura
     * GET /secure/adm/subscriptions/view?id=X
     */
    public function show(): void
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
                    a.username as admin_granted_name
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
            "SELECT te.*, a.username as admin_name
             FROM trial_extensions te
             LEFT JOIN admin_users a ON te.granted_by = a.id
             WHERE te.subscription_id = ?
             ORDER BY te.created_at DESC",
            [$id]
        );
        
        $this->adminView('admin_secure/subscriptions/view', compact('admin', 'subscription', 'payments', 'trialExtensions'));
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
        
        $this->adminView('admin_secure/subscriptions/grant', compact('admin', 'user', 'error', 'success'));
    }
    
    /**
     * Processa dar tier manualmente
     * POST /secure/adm/subscriptions/grant
     */
    public function grant(): void
    {
        $admin = $this->adminAuthService->getCurrentAdmin();
        
        // Validar CSRF
        if (!$this->validateCsrf()) {
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
        
        $this->adminView('admin_secure/subscriptions/extend_trial', compact('admin', 'subscription', 'user', 'error', 'success'));
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
        
        $this->adminView('admin_secure/subscriptions/payments', compact('admin', 'payments', 'stats', 'status', 'search', 'page', 'totalPages', 'total'));
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
        
        $this->adminView('admin_secure/subscriptions/coupons', compact('admin', 'coupons', 'error', 'success'));
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
        
        $this->adminView('admin_secure/subscriptions/coupon_create', compact('admin', 'plans', 'error'));
    }
    
    /**
     * Processa criação de cupom
     * POST /secure/adm/coupons/create
     */
    public function createCoupon(): void
    {
        $admin = $this->adminAuthService->getCurrentAdmin();
        
        // Validar CSRF
        if (!$this->validateCsrf()) {
            header('Location: /secure/adm/coupons/create?error=csrf');
            exit;
        }
        
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $discountType = $_POST['discount_type'] ?? 'percent';
        $discountValue = (int)($_POST['discount_value'] ?? 0);
        $maxRedemptions = !empty($_POST['max_redemptions']) ? (int)$_POST['max_redemptions'] : null;
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
        
        if (!$this->validateCsrf()) {
            header('Location: /secure/adm/coupons?error=csrf');
            exit;
        }
        
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
     * Estende trial com validação de limite máximo
     * POST /secure/adm/subscriptions/extend-trial
     */
    public function extendTrial(): void
    {
        $admin = $this->adminAuthService->getCurrentAdmin();
        
        // Validar CSRF
        if (!$this->validateCsrf()) {
            $_SESSION['error'] = 'Token CSRF inválido';
            header('Location: /secure/adm/subscriptions');
            exit;
        }
        
        $subscriptionId = (int)($_POST['subscription_id'] ?? 0);
        $userId = (int)($_POST['user_id'] ?? 0);
        $additionalDays = (int)($_POST['additional_days'] ?? 0);
        $reason = trim($_POST['reason'] ?? '');
        
        // Se escolheu "custom", usar o valor de custom_days
        if ($additionalDays === 0 && isset($_POST['custom_days'])) {
            $additionalDays = (int)$_POST['custom_days'];
        }
        
        if (!$subscriptionId || !$additionalDays || empty($reason)) {
            $_SESSION['error'] = 'Dados inválidos';
            header("Location: /secure/adm/users/view?id={$userId}");
            exit;
        }
        
        // Buscar assinatura
        $subscription = Database::fetch(
            'SELECT * FROM subscriptions WHERE id = ? AND user_id = ?',
            [$subscriptionId, $userId]
        );
        
        if (!$subscription || $subscription['status'] !== 'trialing') {
            $_SESSION['error'] = 'Assinatura não encontrada ou não está em trial';
            header("Location: /secure/adm/users/view?id={$userId}");
            exit;
        }
        
        // Validar limite de extensão
        $maxExtension = 60; // dias
        $alreadyExtended = (int)($subscription['trial_extended_days'] ?? 0);
        $remaining = $maxExtension - $alreadyExtended;
        
        if ($remaining <= 0) {
            $_SESSION['error'] = 'Limite de extensão de trial atingido (máximo: 60 dias)';
            header("Location: /secure/adm/users/view?id={$userId}");
            exit;
        }
        
        if ($additionalDays > $remaining) {
            $_SESSION['error'] = "Só é possível estender mais {$remaining} dias";
            header("Location: /secure/adm/users/view?id={$userId}");
            exit;
        }
        
        // Calcular nova data de trial_end
        $currentTrialEnd = strtotime($subscription['trial_end']);
        $newTrialEnd = $currentTrialEnd + ($additionalDays * 86400);
        
        // Atualizar no Stripe (se não for manual)
        if ($subscription['stripe_subscription_id']) {
            try {
                $stripe = new \App\Services\StripeService();
                $result = $stripe->updateSubscriptionTrial($subscription['stripe_subscription_id'], $newTrialEnd);
                
                if (isset($result['error'])) {
                    error_log("[ADMIN] Erro ao estender trial no Stripe: " . $result['error']['message']);
                    $_SESSION['error'] = 'Erro ao estender trial no Stripe';
                    header("Location: /secure/adm/users/view?id={$userId}");
                    exit;
                }
            } catch (\Exception $e) {
                error_log("[ADMIN] Erro ao estender trial: " . $e->getMessage());
                $_SESSION['error'] = 'Erro ao estender trial';
                header("Location: /secure/adm/users/view?id={$userId}");
                exit;
            }
        }
        
        // Atualizar no banco
        Database::execute(
            'UPDATE subscriptions 
             SET trial_end = ?, 
                 trial_extended_days = trial_extended_days + ?,
                 updated_at = NOW()
             WHERE id = ?',
            [date('Y-m-d H:i:s', $newTrialEnd), $additionalDays, $subscriptionId]
        );
        
        // Log de auditoria
        $newTotalExtended = $alreadyExtended + $additionalDays;
        \App\Services\AuditLogService::logAdminAction([
            'admin_id' => $admin['id'],
            'admin_email' => $admin['email'],
            'user_id' => $userId,
            'action_type' => 'trial_extended',
            'entity_type' => 'subscription',
            'entity_id' => $subscriptionId,
            'description' => "Trial estendido: +{$additionalDays} dias. Total acumulado: {$newTotalExtended} dias (limite: 60). Motivo: {$reason}",
            'changes' => [
                'additional_days' => $additionalDays,
                'old_trial_end' => $subscription['trial_end'],
                'new_trial_end' => date('Y-m-d H:i:s', $newTrialEnd),
                'total_extended_days' => $newTotalExtended,
                'reason' => $reason
            ]
        ]);
        
        error_log("[ADMIN] Trial da assinatura #{$subscriptionId} estendido em {$additionalDays} dias por {$admin['email']}");
        
        $_SESSION['success'] = "Trial estendido com sucesso (+{$additionalDays} dias)";
        header("Location: /secure/adm/users/view?id={$userId}");
        exit;
    }
    
    /**
     * Reseta o flag trial_used do usuário
     * POST /secure/adm/subscriptions/reset-trial
     */
    public function resetTrial(): void
    {
        $admin = $this->adminAuthService->getCurrentAdmin();
        
        // Validar CSRF
        if (!$this->validateCsrf()) {
            header('Location: /secure/adm/subscriptions?error=csrf');
            exit;
        }
        
        $userId = (int)($_POST['user_id'] ?? 0);
        
        if (!$userId) {
            header('Location: /secure/adm/subscriptions?error=invalid_user');
            exit;
        }
        
        try {
            // Resetar trial_used em todas as assinaturas do usuário
            Database::execute(
                "UPDATE subscriptions SET trial_used = FALSE, updated_at = NOW() WHERE user_id = ?",
                [$userId]
            );
            
            // Log da ação
            error_log("[SubscriptionAdmin] Admin {$admin['id']} resetou trial do usuário {$userId}");
            
            // Redirecionar de volta para o perfil do usuário
            header('Location: /secure/adm/users/view?id=' . $userId . '&success=trial_reset');
        } catch (\Throwable $e) {
            error_log("[SubscriptionAdmin] Erro ao resetar trial: " . $e->getMessage());
            header('Location: /secure/adm/users/view?id=' . $userId . '&error=exception');
        }
        exit;
    }
    
    /**
     * Cancela assinatura de um usuário
     * POST /secure/adm/subscriptions/cancel
     */
    public function cancelSubscription(): void
    {
        $admin = $this->adminAuthService->getCurrentAdmin();
        
        // Validar CSRF
        if (!$this->validateCsrf()) {
            $_SESSION['error'] = 'Token CSRF inválido';
            header('Location: /secure/adm/users');
            exit;
        }
        
        $subscriptionId = (int)($_POST['subscription_id'] ?? 0);
        $userId = (int)($_POST['user_id'] ?? 0);
        $cancelType = $_POST['cancel_type'] ?? 'at_period_end';
        $reason = trim($_POST['reason'] ?? '');
        
        if (!$subscriptionId || !$userId || empty($reason)) {
            $_SESSION['error'] = 'Dados inválidos';
            header("Location: /secure/adm/users/view?id={$userId}");
            exit;
        }
        
        // Buscar assinatura
        $subscription = Database::fetch(
            'SELECT * FROM subscriptions WHERE id = ? AND user_id = ?',
            [$subscriptionId, $userId]
        );
        
        if (!$subscription) {
            $_SESSION['error'] = 'Assinatura não encontrada';
            header("Location: /secure/adm/users/view?id={$userId}");
            exit;
        }
        
        // Cancelar no Stripe (se não for manual)
        $stripe = new \App\Services\StripeService();
        $stripeCanceled = false;
        
        if ($subscription['stripe_subscription_id']) {
            try {
                if ($cancelType === 'immediately') {
                    $result = $stripe->cancelSubscriptionImmediately($subscription['stripe_subscription_id']);
                } else {
                    $result = $stripe->cancelSubscriptionAtPeriodEnd($subscription['stripe_subscription_id']);
                }
                
                $stripeCanceled = !isset($result['error']);
            } catch (\Exception $e) {
                error_log("[ADMIN] Erro ao cancelar no Stripe: " . $e->getMessage());
            }
        }
        
        // Atualizar status no banco
        if ($cancelType === 'immediately' || !$subscription['stripe_subscription_id']) {
            // Cancelamento imediato ou manual
            Database::execute(
                'UPDATE subscriptions SET status = ?, updated_at = NOW() WHERE id = ?',
                ['canceled', $subscriptionId]
            );
            
            Database::execute(
                'UPDATE users SET tier = ? WHERE id = ?',
                ['FREE', $userId]
            );
        } else {
            // Cancelamento ao fim do período
            Database::execute(
                'UPDATE subscriptions SET cancel_at_period_end = TRUE, updated_at = NOW() WHERE id = ?',
                [$subscriptionId]
            );
        }
        
        // Log de auditoria
        \App\Services\AuditLogService::logAdminAction([
            'admin_id' => $admin['id'],
            'admin_email' => $admin['email'],
            'user_id' => $userId,
            'action_type' => 'subscription_canceled',
            'entity_type' => 'subscription',
            'entity_id' => $subscriptionId,
            'description' => "Assinatura cancelada ({$cancelType}). Motivo: {$reason}",
            'changes' => [
                'cancel_type' => $cancelType,
                'reason' => $reason,
                'stripe_canceled' => $stripeCanceled,
                'old_status' => $subscription['status']
            ]
        ]);
        
        error_log("[ADMIN] Assinatura #{$subscriptionId} cancelada ({$cancelType}) por {$admin['email']}. Motivo: {$reason}");
        
        $_SESSION['success'] = 'Assinatura cancelada com sucesso';
        header("Location: /secure/adm/users/view?id={$userId}");
        exit;
    }
    /**
     * Processa reembolso de pagamento
     * POST /secure/adm/subscriptions/refund
     */
    public function refundPayment(): void
    {
        $admin = $this->adminAuthService->getCurrentAdmin();
        
        // Validar CSRF (via AJAX ou Form)
        if (!$this->validateCsrf()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Token CSRF invalido']);
            exit;
        }
        
        $paymentId = (int)($_POST['payment_id'] ?? 0);
        $amountCents = isset($_POST['amount_cents']) && $_POST['amount_cents'] !== '' ? (int)$_POST['amount_cents'] : null;
        $reason = trim($_POST['reason'] ?? '');
        
        if (!$paymentId || empty($reason)) {
            echo json_encode(['success' => false, 'error' => 'Dados invalidos']);
            exit;
        }
        
        // Buscar pagamento
        $payment = Database::fetch('SELECT * FROM payment_history WHERE id = ?', [$paymentId]);
        if (!$payment || !$payment['stripe_charge_id']) {
             echo json_encode(['success' => false, 'error' => 'Pagamento nao encontrado ou nao reembolsavel (sem Charge ID)']);
             exit;
        }
        
        if ($payment['status'] === 'refunded') {
             echo json_encode(['success' => false, 'error' => 'Pagamento ja reembolsado']);
             exit;
        }
        
        try {
            $stripe = new \App\Services\StripeService();
            // Charge ID ou PaymentIntent ID
            $result = $stripe->refundCharge($payment['stripe_charge_id'], $amountCents);
            
            if (isset($result['error'])) {
                echo json_encode(['success' => false, 'error' => 'Erro Stripe: ' . $result['error']['message']]);
                exit;
            }
            
            // Atualizar status no banco
            Database::execute(
                "UPDATE payment_history SET status = 'refunded', updated_at = NOW() WHERE id = ?",
                [$paymentId]
            );
            
            // Log auditoria
             \App\Services\AuditLogService::logAdminAction([
                'admin_id' => $admin['id'],
                'admin_email' => $admin['email'],
                'user_id' => $payment['user_id'],
                'action_type' => 'payment_refunded',
                'entity_type' => 'payment',
                'entity_id' => $paymentId,
                'description' => "Reembolso solicitado. Valor: " . ($amountCents ? $amountCents : 'Total') . ". Motivo: {$reason}",
            ]);
            
            echo json_encode(['success' => true]);
            
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Excecao: ' . $e->getMessage()]);
        }
        exit;
    }

    /**
     * Sincroniza assinatura com Stripe
     * POST /secure/adm/subscriptions/sync
     */
    public function syncSubscription(): void
    {
        $admin = $this->adminAuthService->getCurrentAdmin();
        
        if (!$this->validateCsrf()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Token CSRF invalido']);
            exit;
        }
        
        $userId = (int)($_POST['user_id'] ?? 0);
        
        if (!$userId) {
            echo json_encode(['success' => false, 'error' => 'ID de usuario invalido']);
            exit;
        }
        
        try {
            $result = $this->getSubscriptionService()->syncUserSubscription($userId);
            
            if ($result) {
                 // Log auditoria
                 \App\Services\AuditLogService::logAdminAction([
                    'admin_id' => $admin['id'],
                    'admin_email' => $admin['email'],
                    'user_id' => $userId,
                    'action_type' => 'subscription_synced',
                    'entity_type' => 'user',
                    'entity_id' => $userId,
                    'description' => "Sincronizacao manual com Stripe executada",
                ]);
                 echo json_encode(['success' => true]);
            } else {
                 echo json_encode(['success' => false, 'error' => 'Sincronizacao falhou ou sem dados no Stripe']);
            }

        } catch (\Exception $e) {
             echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }
}
