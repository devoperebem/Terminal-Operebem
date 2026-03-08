<?php
/**
 * SubscriptionPlanService - Lógica de negócio para gerenciamento de planos
 * 
 * Este serviço gerencia planos de assinatura, incluindo:
 * - Cálculo de preços com descontos temporários
 * - Estatísticas de assinantes por plano
 * - Validações de promoções vs cupons
 */

namespace App\Services;

use App\Core\Database;

class SubscriptionPlanService
{
    /**
     * Retorna todos os planos com estatísticas
     */
    public function getAllPlansWithStats(): array
    {
        $sql = "
            SELECT 
                sp.id,
                sp.name,
                sp.slug,
                sp.tier,
                sp.interval_type,
                sp.price_cents,
                sp.currency,
                sp.is_active,
                sp.is_featured,
                sp.display_order,
                sp.trial_days,
                sp.discount_percentage,
                sp.discount_start_date,
                sp.discount_end_date,
                sp.discount_label,
                sp.stripe_product_id,
                sp.stripe_price_id,
                COUNT(DISTINCT CASE 
                    WHEN s.status IN ('active', 'trialing') THEN s.id 
                END) as active_subscribers,
                COUNT(DISTINCT CASE 
                    WHEN s.status IN ('active', 'trialing') 
                    AND s.created_at >= NOW() - INTERVAL '30 days' 
                    THEN s.id 
                END) as new_last_30_days,
                SUM(CASE 
                    WHEN s.status IN ('active', 'trialing') 
                    THEN sp.price_cents 
                END) as monthly_revenue_cents
            FROM subscription_plans sp
            LEFT JOIN subscriptions s ON s.plan_slug = sp.slug
            GROUP BY sp.id
            ORDER BY sp.display_order ASC
        ";
        
        $plans = Database::fetchAll($sql);
        
        // Processar cada plano
        foreach ($plans as &$plan) {
            $plan['active_subscribers'] = (int)$plan['active_subscribers'];
            $plan['new_last_30_days'] = (int)$plan['new_last_30_days'];
            $plan['monthly_revenue_cents'] = (int)($plan['monthly_revenue_cents'] ?? 0);
            $plan['has_active_discount'] = $this->hasActiveDiscount($plan);
            $plan['effective_price_cents'] = $this->getEffectivePrice($plan);
        }
        
        return $plans;
    }
    
    /**
     * Busca um plano por ID
     */
    public function getPlanById(int $id): ?array
    {
        $plan = Database::fetch('SELECT * FROM subscription_plans WHERE id = ?', [$id]);
        
        if (!$plan) {
            return null;
        }
        
        $plan['has_active_discount'] = $this->hasActiveDiscount($plan);
        $plan['effective_price_cents'] = $this->getEffectivePrice($plan);
        
        return $plan;
    }
    
    /**
     * Busca um plano por slug
     */
    public function getPlanBySlug(string $slug): ?array
    {
        $plan = Database::fetch('SELECT * FROM subscription_plans WHERE slug = ?', [$slug]);
        
        if (!$plan) {
            return null;
        }
        
        $plan['has_active_discount'] = $this->hasActiveDiscount($plan);
        $plan['effective_price_cents'] = $this->getEffectivePrice($plan);
        
        return $plan;
    }
    
    /**
     * Calcula o preço efetivo (com desconto se aplicável)
     */
    public function getEffectivePrice(array $plan): int
    {
        if (!$this->hasActiveDiscount($plan)) {
            return (int)$plan['price_cents'];
        }
        
        $discount = (int)$plan['discount_percentage'];
        $originalPrice = (int)$plan['price_cents'];
        
        $discountAmount = ($originalPrice * $discount) / 100;
        $effectivePrice = $originalPrice - $discountAmount;
        
        return (int)round($effectivePrice);
    }
    
    /**
     * Verifica se o plano tem desconto ativo no momento
     */
    public function hasActiveDiscount(array $plan): bool
    {
        // Sem desconto configurado
        if (empty($plan['discount_percentage']) || $plan['discount_percentage'] <= 0) {
            return false;
        }
        
        $now = new \DateTime();
        
        // Verificar data de início
        if (!empty($plan['discount_start_date'])) {
            $startDate = new \DateTime($plan['discount_start_date']);
            if ($now < $startDate) {
                return false;
            }
        }
        
        // Verificar data de fim
        if (!empty($plan['discount_end_date'])) {
            $endDate = new \DateTime($plan['discount_end_date']);
            if ($now > $endDate) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Verifica se um cupom pode ser aplicado ao plano
     * (cupons não podem ser usados em planos com promoção ativa)
     */
    public function canApplyCoupon(array $plan): bool
    {
        return !$this->hasActiveDiscount($plan);
    }
    
    /**
     * Atualiza o preço de um plano e cria novo Price ID no Stripe
     */
    public function updatePrice(int $planId, int $newPriceCents): array
    {
        $plan = $this->getPlanById($planId);
        if (!$plan) {
            return ['success' => false, 'error' => 'Plano não encontrado'];
        }
        
        // Se o preço não mudou, não fazer nada
        if ((int)$plan['price_cents'] === $newPriceCents) {
            return ['success' => true, 'message' => 'Preço já está atualizado'];
        }
        
        // Criar novo Price no Stripe
        $stripeService = new StripeService();
        $newPrice = $stripeService->createPrice(
            $plan['stripe_product_id'],
            $newPriceCents,
            $plan['currency'],
            $plan['interval_type']
        );
        
        if (!$newPrice || empty($newPrice['id'])) {
            return ['success' => false, 'error' => 'Erro ao criar novo preço no Stripe'];
        }
        
        // Atualizar no banco de dados
        $updated = Database::execute(
            'UPDATE subscription_plans 
             SET price_cents = ?, 
                 stripe_price_id = ?,
                 updated_at = NOW()
             WHERE id = ?',
            [$newPriceCents, $newPrice['id'], $planId]
        );
        
        if (!$updated) {
            return ['success' => false, 'error' => 'Erro ao atualizar preço no banco de dados'];
        }
        
        return [
            'success' => true,
            'new_stripe_price_id' => $newPrice['id'],
            'old_price_cents' => $plan['price_cents'],
            'new_price_cents' => $newPriceCents
        ];
    }
    
    /**
     * Aplica desconto temporário a um plano
     */
    public function applyDiscount(
        int $planId, 
        int $discountPercentage, 
        ?string $startDate = null, 
        ?string $endDate = null,
        ?string $label = null
    ): array {
        // Validações
        if ($discountPercentage < 0 || $discountPercentage > 100) {
            return ['success' => false, 'error' => 'Desconto deve estar entre 0 e 100'];
        }
        
        $plan = $this->getPlanById($planId);
        if (!$plan) {
            return ['success' => false, 'error' => 'Plano não encontrado'];
        }
        
        // Validar datas
        if ($startDate && $endDate) {
            $start = new \DateTime($startDate);
            $end = new \DateTime($endDate);
            if ($end <= $start) {
                return ['success' => false, 'error' => 'Data de fim deve ser posterior à data de início'];
            }
        }
        
        // Atualizar no banco
        $updated = Database::execute(
            'UPDATE subscription_plans 
             SET discount_percentage = ?,
                 discount_start_date = ?,
                 discount_end_date = ?,
                 discount_label = ?,
                 updated_at = NOW()
             WHERE id = ?',
            [
                $discountPercentage,
                $startDate ?: null,
                $endDate ?: null,
                $label ?: null,
                $planId
            ]
        );
        
        if (!$updated) {
            return ['success' => false, 'error' => 'Erro ao aplicar desconto'];
        }
        
        return ['success' => true, 'message' => 'Desconto aplicado com sucesso'];
    }
    
    /**
     * Remove desconto de um plano
     */
    public function removeDiscount(int $planId): array
    {
        $plan = $this->getPlanById($planId);
        if (!$plan) {
            return ['success' => false, 'error' => 'Plano não encontrado'];
        }
        
        $updated = Database::execute(
            'UPDATE subscription_plans 
             SET discount_percentage = 0,
                 discount_start_date = NULL,
                 discount_end_date = NULL,
                 discount_label = NULL,
                 updated_at = NOW()
             WHERE id = ?',
            [$planId]
        );
        
        if (!$updated) {
            return ['success' => false, 'error' => 'Erro ao remover desconto'];
        }
        
        return ['success' => true, 'message' => 'Desconto removido com sucesso'];
    }
    
    /**
     * Ativa ou desativa um plano
     */
    public function toggleActive(int $planId, bool $isActive): array
    {
        $plan = $this->getPlanById($planId);
        if (!$plan) {
            return ['success' => false, 'error' => 'Plano não encontrado'];
        }
        
        $updated = Database::execute(
            'UPDATE subscription_plans 
             SET is_active = ?,
                 updated_at = NOW()
             WHERE id = ?',
            [$isActive ? 'true' : 'false', $planId]
        );
        
        if (!$updated) {
            return ['success' => false, 'error' => 'Erro ao atualizar status do plano'];
        }
        
        $status = $isActive ? 'ativado' : 'desativado';
        return ['success' => true, 'message' => "Plano {$status} com sucesso"];
    }
    
    /**
     * Retorna o Stripe Price ID efetivo (cria novo se houver desconto ativo)
     * 
     * Se o plano tem desconto ativo, cria um novo Price no Stripe com o valor com desconto.
     * Caso contrário, retorna o Price ID padrão do plano.
     */
    public function getEffectivePriceId(array $plan): string
    {
        // Se não tem desconto ativo, usar o Price ID padrão
        if (!$this->hasActiveDiscount($plan)) {
            return $plan['stripe_price_id'];
        }
        
        // Se tem desconto ativo, verificar se já existe um Price ID temporário
        // Por simplicidade, vamos sempre criar um novo Price (o Stripe permite múltiplos prices)
        $effectivePrice = $this->getEffectivePrice($plan);
        
        $stripeService = new StripeService();
        $newPrice = $stripeService->createPrice(
            $plan['stripe_product_id'],
            $effectivePrice,
            $plan['currency'],
            $plan['interval_type']
        );
        
        if (!$newPrice || empty($newPrice['id'])) {
            // Se falhar, usar o preço original
            error_log("[WARN] Falha ao criar Price com desconto para plano {$plan['slug']}. Usando preço original.");
            return $plan['stripe_price_id'];
        }
        
        return $newPrice['id'];
    }
    
    /**
     * Retorna estatísticas gerais de todos os planos
     */
    public function getGeneralStats(): array
    {
        $sql = "
            SELECT 
                COUNT(DISTINCT CASE WHEN s.status IN ('active', 'trialing') THEN s.id END) as total_active_subscriptions,
                COUNT(DISTINCT CASE WHEN s.status = 'canceled' THEN s.id END) as total_canceled_subscriptions,
                COUNT(DISTINCT CASE WHEN s.created_at >= NOW() - INTERVAL '30 days' THEN s.id END) as new_subscriptions_30d,
                SUM(CASE WHEN s.status IN ('active', 'trialing') THEN sp.price_cents END) as total_mrr_cents
            FROM subscriptions s
            LEFT JOIN subscription_plans sp ON sp.slug = s.plan_slug
        ";
        
        $stats = Database::fetch($sql);
        
        return [
            'total_active' => (int)($stats['total_active_subscriptions'] ?? 0),
            'total_canceled' => (int)($stats['total_canceled_subscriptions'] ?? 0),
            'new_30_days' => (int)($stats['new_subscriptions_30d'] ?? 0),
            'mrr_cents' => (int)($stats['total_mrr_cents'] ?? 0)
        ];
    }
}
