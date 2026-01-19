<?php
/**
 * SubscriptionService - Lógica de negócio para assinaturas
 * 
 * Este serviço gerencia a lógica de assinaturas, integrando com o Stripe
 * e mantendo o banco de dados sincronizado.
 */

namespace App\Services;

use App\Core\Database;
use App\Core\Application;

class SubscriptionService
{
    private StripeService $stripe;
    private array $config;
    
    public function __construct()
    {
        $this->stripe = new StripeService();
        $this->config = $this->stripe->getConfig();
    }
    
    // =========================================================================
    // PLANOS
    // =========================================================================
    
    /**
     * Lista todos os planos ativos
     */
    public function getActivePlans(): array
    {
        return Database::fetchAll(
            'SELECT * FROM subscription_plans WHERE is_active = TRUE ORDER BY display_order ASC'
        );
    }
    
    /**
     * Busca um plano pelo slug
     */
    public function getPlanBySlug(string $slug): ?array
    {
        return Database::fetch(
            'SELECT * FROM subscription_plans WHERE slug = ? AND is_active = TRUE',
            [$slug]
        ) ?: null;
    }
    
    // =========================================================================
    // CHECKOUT
    // =========================================================================
    
    /**
     * Inicia o processo de checkout para uma assinatura
     */
    public function createCheckout(int $userId, string $planSlug, ?string $couponCode = null): array
    {
        // Buscar usuário
        $user = Database::fetch('SELECT id, email, name, tier, stripe_customer_id FROM users WHERE id = ?', [$userId]);
        if (!$user) {
            return ['success' => false, 'error' => 'Usuário não encontrado'];
        }
        
        // Buscar plano
        $plan = $this->getPlanBySlug($planSlug);
        if (!$plan) {
            return ['success' => false, 'error' => 'Plano não encontrado'];
        }
        
        // NOVA VALIDAÇÃO: Verificar se o plano está ativo
        if (!$plan['is_active']) {
            return ['success' => false, 'error' => 'Este plano está temporariamente indisponível'];
        }
        
        // Verificar se já tem assinatura ativa do mesmo tier ou superior
        $existingSubscription = $this->getActiveSubscription($userId);
        if ($existingSubscription && $existingSubscription['status'] !== 'canceled') {
            $tierOrder = ['FREE' => 1, 'PLUS' => 2, 'PRO' => 3];
            if ($tierOrder[$existingSubscription['tier']] >= $tierOrder[$plan['tier']]) {
                return ['success' => false, 'error' => 'Você já possui uma assinatura igual ou superior'];
            }
        }
        
        // Obter ou criar customer no Stripe
        $customer = $this->stripe->getOrCreateCustomer($userId, $user['email'], $user['name']);
        if (!$customer) {
            return ['success' => false, 'error' => 'Erro ao criar cliente no Stripe'];
        }
        
        // Verificar cupom
        $stripeCouponId = null;
        if ($couponCode) {
            // NOVA VALIDAÇÃO: Verificar se o plano aceita cupons (não pode ter promoção ativa)
            $planService = new \App\Services\SubscriptionPlanService();
            $fullPlan = $planService->getPlanBySlug($planSlug);
            
            if ($fullPlan && !$planService->canApplyCoupon($fullPlan)) {
                return ['success' => false, 'error' => 'Cupons não podem ser aplicados em planos com promoção ativa'];
            }
            
            $coupon = $this->validateCoupon($couponCode, $planSlug, $userId);
            if (!$coupon['valid']) {
                return ['success' => false, 'error' => $coupon['error']];
            }
            $stripeCouponId = $coupon['stripe_coupon_id'] ?? null;
        }
        
        // Determinar trial (apenas se não usou ainda)
        $trialDays = 0;
        if (!$this->hasUsedTrial($userId)) {
            $trialDays = $plan['trial_days'] ?? $this->config['default_trial_days'];
        }
        
        // NOVA FUNCIONALIDADE: Obter Price ID efetivo (com desconto se aplicável)
        $planService = new \App\Services\SubscriptionPlanService();
        $fullPlan = $planService->getPlanBySlug($planSlug);
        $effectivePriceId = $fullPlan ? $planService->getEffectivePriceId($fullPlan) : $plan['stripe_price_id'];
        
        // Criar sessão de checkout
        $session = $this->stripe->createSubscriptionCheckout(
            $customer['id'],
            $effectivePriceId,
            $trialDays,
            $stripeCouponId,
            [
                'user_id' => $userId,
                'plan_slug' => $planSlug,
                'tier' => $plan['tier'],
            ]
        );
        
        if (isset($session['error'])) {
            return ['success' => false, 'error' => $session['error']['message'] ?? 'Erro no Stripe'];
        }
        
        return [
            'success' => true,
            'checkout_url' => $session['url'],
            'session_id' => $session['id'],
        ];
    }
    
    // =========================================================================
    // ASSINATURAS
    // =========================================================================
    
    /**
     * Busca assinatura ativa do usuário
     */
    public function getActiveSubscription(int $userId): ?array
    {
        return Database::fetch(
            "SELECT * FROM subscriptions 
             WHERE user_id = ? AND status IN ('trialing', 'active', 'past_due') 
             ORDER BY created_at DESC LIMIT 1",
            [$userId]
        ) ?: null;
    }
    
    /**
     * Busca assinatura pelo ID do Stripe
     */
    public function getSubscriptionByStripeId(string $stripeSubscriptionId): ?array
    {
        return Database::fetch(
            'SELECT * FROM subscriptions WHERE stripe_subscription_id = ?',
            [$stripeSubscriptionId]
        ) ?: null;
    }
    
    /**
     * Cria uma assinatura no banco (chamado pelo webhook)
     */
    public function createSubscription(array $data): int
    {
        $subscriptionId = Database::insert('subscriptions', [
            'user_id' => $data['user_id'],
            'stripe_customer_id' => $data['stripe_customer_id'] ?? null,
            'stripe_subscription_id' => $data['stripe_subscription_id'] ?? null,
            'stripe_price_id' => $data['stripe_price_id'] ?? null,
            'plan_slug' => $data['plan_slug'],
            'tier' => $data['tier'],
            'interval_type' => $data['interval_type'] ?? 'month',
            'status' => $data['status'] ?? 'active',
            'trial_start' => $data['trial_start'] ?? null,
            'trial_end' => $data['trial_end'] ?? null,
            'trial_used' => $data['trial_used'] ?? false,
            'current_period_start' => $data['current_period_start'] ?? null,
            'current_period_end' => $data['current_period_end'] ?? null,
            'source' => $data['source'] ?? 'stripe',
            'admin_granted_by' => $data['admin_granted_by'] ?? null,
            'admin_notes' => $data['admin_notes'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        
        // Sincronizar tier do usuário imediatamente
        $this->syncUserTier($data['user_id']);
        
        return $subscriptionId;
    }
    
    /**
     * Atualiza uma assinatura
     */
    public function updateSubscription(int $subscriptionId, array $data): void
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        Database::update('subscriptions', $data, ['id' => $subscriptionId]);
    }
    
    /**
     * Atualiza o tier do usuário baseado na assinatura ativa
     */
    public function syncUserTier(int $userId): void
    {
        $subscription = $this->getActiveSubscription($userId);
        
        if ($subscription && in_array($subscription['status'], ['trialing', 'active'])) {
            $tier = $subscription['tier'];
            $expiresAt = $subscription['current_period_end'];
            
            // Se trial, usar trial_end
            if ($subscription['status'] === 'trialing' && $subscription['trial_end']) {
                $expiresAt = $subscription['trial_end'];
            }
        } else {
            $tier = 'FREE';
            $expiresAt = null;
        }
        
        Database::update('users', [
            'tier' => $tier,
            'subscription_expires_at' => $expiresAt,
        ], ['id' => $userId]);
    }
    
    /**
     * Cancela a assinatura do usuário
     */
    public function cancelSubscription(int $userId, bool $immediate = false): array
    {
        $subscription = $this->getActiveSubscription($userId);
        
        if (!$subscription) {
            return ['success' => false, 'error' => 'Nenhuma assinatura ativa encontrada'];
        }
        
        // Se assinatura manual (admin), apenas atualizar no banco
        if ($subscription['source'] === 'admin') {
            if ($immediate) {
                $this->updateSubscription($subscription['id'], [
                    'status' => 'canceled',
                    'canceled_at' => date('Y-m-d H:i:s'),
                    'ended_at' => date('Y-m-d H:i:s'),
                ]);
                $this->syncUserTier($userId);
            } else {
                $this->updateSubscription($subscription['id'], [
                    'cancel_at_period_end' => true,
                    'canceled_at' => date('Y-m-d H:i:s'),
                ]);
            }
            return ['success' => true, 'message' => 'Assinatura cancelada'];
        }
        
        // Se Stripe, cancelar via API
        if ($subscription['stripe_subscription_id']) {
            if ($immediate) {
                $result = $this->stripe->cancelSubscriptionImmediately($subscription['stripe_subscription_id']);
            } else {
                $result = $this->stripe->cancelSubscriptionAtPeriodEnd($subscription['stripe_subscription_id']);
            }
            
            if (isset($result['error'])) {
                return ['success' => false, 'error' => $result['error']['message'] ?? 'Erro ao cancelar'];
            }
            
            $this->updateSubscription($subscription['id'], [
                'cancel_at_period_end' => !$immediate,
                'canceled_at' => date('Y-m-d H:i:s'),
                'status' => $immediate ? 'canceled' : $subscription['status'],
            ]);
            
            if ($immediate) {
                $this->syncUserTier($userId);
            }
            
            return ['success' => true, 'message' => 'Assinatura será cancelada ao fim do período'];
        }
        
        return ['success' => false, 'error' => 'Erro ao processar cancelamento'];
    }
    
    // =========================================================================
    // TIER MANUAL (ADMIN)
    // =========================================================================
    
    /**
     * Concede tier manualmente via admin
     */
    public function grantTierManually(int $userId, string $tier, ?string $expiresAt, int $adminId, ?string $notes = null): array
    {
        // Cancelar assinatura ativa se existir
        $existing = $this->getActiveSubscription($userId);
        if ($existing) {
            $this->updateSubscription($existing['id'], [
                'status' => 'canceled',
                'ended_at' => date('Y-m-d H:i:s'),
            ]);
        }
        
        // Criar nova assinatura manual
        $subscriptionId = $this->createSubscription([
            'user_id' => $userId,
            'plan_slug' => 'manual_' . strtolower($tier),
            'tier' => $tier,
            'interval_type' => 'manual',
            'status' => 'active', // Ativa imediatamente para tier manual
            'current_period_start' => date('Y-m-d H:i:s'),
            'current_period_end' => $expiresAt,
            'source' => 'admin',
            'admin_granted_by' => $adminId,
            'admin_notes' => $notes,
        ]);
        
        // Atualizar tier do usuário
        Database::update('users', [
            'tier' => $tier,
            'subscription_expires_at' => $expiresAt,
        ], ['id' => $userId]);
        
        return [
            'success' => true,
            'subscription_id' => $subscriptionId,
            'message' => 'Tier concedido com sucesso',
        ];
    }
    
    /**
     * Estende o trial de um usuário
     */
    public function extendTrial(int $userId, int $days, int $adminId, ?string $reason = null): array
    {
        $subscription = $this->getActiveSubscription($userId);
        
        if (!$subscription) {
            return ['success' => false, 'error' => 'Nenhuma assinatura encontrada'];
        }
        
        $previousTrialEnd = $subscription['trial_end'];
        $newTrialEnd = date('Y-m-d H:i:s', strtotime("+{$days} days", 
            $previousTrialEnd ? strtotime($previousTrialEnd) : time()
        ));
        
        // Se assinatura Stripe e em trial, atualizar no Stripe
        if ($subscription['stripe_subscription_id'] && $subscription['status'] === 'trialing') {
            $result = $this->stripe->updateSubscriptionTrial(
                $subscription['stripe_subscription_id'],
                strtotime($newTrialEnd)
            );
            
            if (isset($result['error'])) {
                return ['success' => false, 'error' => $result['error']['message'] ?? 'Erro no Stripe'];
            }
        }
        
        // Atualizar no banco
        $this->updateSubscription($subscription['id'], [
            'trial_end' => $newTrialEnd,
        ]);
        
        // Registrar extensão
        Database::insert('trial_extensions', [
            'user_id' => $userId,
            'subscription_id' => $subscription['id'],
            'days_extended' => $days,
            'previous_trial_end' => $previousTrialEnd,
            'new_trial_end' => $newTrialEnd,
            'granted_by' => $adminId,
            'reason' => $reason,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        
        // Sincronizar tier
        $this->syncUserTier($userId);
        
        return [
            'success' => true,
            'new_trial_end' => $newTrialEnd,
            'message' => "Trial estendido por {$days} dias",
        ];
    }
    
    // =========================================================================
    // TRIAL
    // =========================================================================
    
    /**
     * Verifica se o usuário já usou o trial
     */
    public function hasUsedTrial(int $userId): bool
    {
        $result = Database::fetch(
            'SELECT COUNT(*) as count FROM subscriptions WHERE user_id = ? AND trial_used = TRUE',
            [$userId]
        );
        return (int)($result['count'] ?? 0) > 0;
    }
    
    // =========================================================================
    // CUPONS
    // =========================================================================
    
    /**
     * Valida um cupom
     */
    public function validateCoupon(string $code, string $planSlug, int $userId): array
    {
        $coupon = Database::fetch(
            'SELECT * FROM coupons WHERE code = ? AND is_active = TRUE',
            [strtoupper($code)]
        );
        
        if (!$coupon) {
            return ['valid' => false, 'error' => 'Cupom não encontrado'];
        }
        
        // Verificar validade
        if ($coupon['valid_until'] && strtotime($coupon['valid_until']) < time()) {
            return ['valid' => false, 'error' => 'Cupom expirado'];
        }
        
        if ($coupon['valid_from'] && strtotime($coupon['valid_from']) > time()) {
            return ['valid' => false, 'error' => 'Cupom ainda não está válido'];
        }
        
        // Verificar limite de usos
        if ($coupon['max_redemptions'] !== null && $coupon['redemptions_count'] >= $coupon['max_redemptions']) {
            return ['valid' => false, 'error' => 'Cupom esgotado'];
        }
        
        // Verificar planos aplicáveis
        if (!empty($coupon['applicable_plans'])) {
            $plans = is_array($coupon['applicable_plans']) 
                ? $coupon['applicable_plans'] 
                : json_decode($coupon['applicable_plans'], true);
            
            if (!in_array($planSlug, $plans)) {
                return ['valid' => false, 'error' => 'Cupom não aplicável a este plano'];
            }
        }
        
        // Verificar se usuário já usou (se first_time_only)
        if ($coupon['first_time_only']) {
            $used = Database::fetch(
                'SELECT COUNT(*) as count FROM coupon_redemptions WHERE coupon_id = ? AND user_id = ?',
                [$coupon['id'], $userId]
            );
            if ((int)($used['count'] ?? 0) > 0) {
                return ['valid' => false, 'error' => 'Cupom já utilizado'];
            }
        }
        
        return [
            'valid' => true,
            'coupon' => $coupon,
            'stripe_coupon_id' => $coupon['stripe_coupon_id'],
            'discount_type' => $coupon['discount_type'],
            'discount_value' => $coupon['discount_value'],
        ];
    }
    
    // =========================================================================
    // PAGAMENTOS
    // =========================================================================
    
    /**
     * Registra um pagamento
     */
    public function recordPayment(array $data): int
    {
        return Database::insert('payment_history', [
            'user_id' => $data['user_id'],
            'subscription_id' => $data['subscription_id'] ?? null,
            'stripe_payment_intent_id' => $data['stripe_payment_intent_id'] ?? null,
            'stripe_invoice_id' => $data['stripe_invoice_id'] ?? null,
            'stripe_charge_id' => $data['stripe_charge_id'] ?? null,
            'amount_cents' => $data['amount_cents'],
            'currency' => $data['currency'] ?? 'BRL',
            'status' => $data['status'],
            'payment_method_type' => $data['payment_method_type'] ?? null,
            'card_last4' => $data['card_last4'] ?? null,
            'card_brand' => $data['card_brand'] ?? null,
            'description' => $data['description'] ?? null,
            'failure_code' => $data['failure_code'] ?? null,
            'failure_message' => $data['failure_message'] ?? null,
            'hosted_invoice_url' => $data['hosted_invoice_url'] ?? null,
            'invoice_pdf_url' => $data['invoice_pdf_url'] ?? null,
            'receipt_url' => $data['receipt_url'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
            'paid_at' => $data['status'] === 'succeeded' ? date('Y-m-d H:i:s') : null,
        ]);
    }
    
    /**
     * Lista histórico de pagamentos de um usuário
     */
    public function getPaymentHistory(int $userId, int $limit = 20): array
    {
        return Database::fetchAll(
            'SELECT * FROM payment_history WHERE user_id = ? ORDER BY created_at DESC LIMIT ?',
            [$userId, $limit]
        );
    }
    
    // =========================================================================
    // TIER EFETIVO
    // =========================================================================
    
    /**
     * Retorna o tier efetivo do usuário (considerando expiração)
     */
    public function getEffectiveTier(array $user): string
    {
        $tier = strtoupper($user['tier'] ?? 'FREE');
        
        if ($tier === 'FREE') {
            return 'FREE';
        }
        
        $expiresAt = $user['subscription_expires_at'] ?? null;
        
        // Se não tem expiração, é vitalício
        if ($expiresAt === null || $expiresAt === '') {
            return $tier;
        }
        
        // Verificar se expirou
        if (strtotime($expiresAt) < time()) {
            return 'FREE';
        }
        
        return $tier;
    }
}
