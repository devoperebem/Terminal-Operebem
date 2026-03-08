<?php
/**
 * StripeWebhookController - Processa eventos do Stripe
 * 
 * Este controller recebe e processa todos os webhooks enviados pelo Stripe.
 * Endpoint: POST /api/stripe/webhook
 */

namespace App\Controllers;

use App\Core\Application;
use App\Core\Database;
use App\Services\StripeService;
use App\Services\SubscriptionService;

class StripeWebhookController
{
    private StripeService $stripe;
    private SubscriptionService $subscriptionService;
    
    public function __construct()
    {
        $this->stripe = new StripeService();
        $this->subscriptionService = new SubscriptionService();
    }
    
    /**
     * Endpoint principal de webhook
     * POST /api/stripe/webhook
     */
    public function handle(): void
    {
        // Ler payload raw
        $payload = file_get_contents('php://input');
        $sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
        
        // Log inicial
        $this->log('Webhook received', ['signature_present' => !empty($sigHeader)]);
        
        // Verificar assinatura (se configurado)
        $config = $this->stripe->getConfig();
        if (!empty($config['webhook_secret'])) {
            if (!$this->stripe->verifyWebhookSignature($payload, $sigHeader)) {
                $this->log('Invalid signature', [], 'error');
                http_response_code(400);
                echo json_encode(['error' => 'Invalid signature']);
                return;
            }
        }
        
        // Parse do evento
        $event = json_decode($payload, true);
        
        if (!$event || !isset($event['type'])) {
            $this->log('Invalid payload', [], 'error');
            http_response_code(400);
            echo json_encode(['error' => 'Invalid payload']);
            return;
        }
        
        $eventType = $event['type'];
        $eventId = $event['id'] ?? 'unknown';
        
        $this->log("Processing event: {$eventType}", ['event_id' => $eventId]);
        
        try {
            // Processar evento
            $handled = match ($eventType) {
                'checkout.session.completed' => $this->handleCheckoutCompleted($event),
                'customer.subscription.created' => $this->handleSubscriptionCreated($event),
                'customer.subscription.updated' => $this->handleSubscriptionUpdated($event),
                'customer.subscription.deleted' => $this->handleSubscriptionDeleted($event),
                'invoice.paid' => $this->handleInvoicePaid($event),
                'invoice.payment_failed' => $this->handleInvoicePaymentFailed($event),
                'invoice.created' => $this->handleInvoiceCreated($event),
                'customer.subscription.trial_will_end' => $this->handleTrialWillEnd($event),
                'charge.refunded' => $this->handleChargeRefunded($event),
                'charge.dispute.created' => $this->handleDisputeCreated($event),
                default => false,
            };
            
            if ($handled) {
                $this->log("Event handled: {$eventType}", ['event_id' => $eventId]);
            } else {
                $this->log("Event ignored: {$eventType}", ['event_id' => $eventId]);
            }
            
            http_response_code(200);
            echo json_encode(['received' => true, 'handled' => $handled]);
            
        } catch (\Throwable $e) {
            $this->log("Event error: {$eventType}", [
                'event_id' => $eventId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ], 'error');
            
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
    
    // =========================================================================
    // HANDLERS
    // =========================================================================
    
    /**
     * Checkout completado com sucesso
     */
    private function handleCheckoutCompleted(array $event): bool
    {
        $session = $event['data']['object'] ?? [];
        
        if (($session['mode'] ?? '') !== 'subscription') {
            return false;
        }
        
        $metadata = $session['metadata'] ?? [];
        if (empty($metadata)) {
            // Tentar pegar do subscription
            $subscriptionData = $session['subscription'] ?? null;
            if (is_array($subscriptionData)) {
                $metadata = $subscriptionData['metadata'] ?? [];
            }
        }
        
        $userId = (int)($metadata['user_id'] ?? 0);
        $planSlug = $metadata['plan_slug'] ?? '';
        $tier = $metadata['tier'] ?? 'PLUS';
        
        if (!$userId) {
            $this->log('checkout.session.completed: No user_id in metadata', $metadata, 'warning');
            return false;
        }
        
        $stripeSubscriptionId = $session['subscription'] ?? '';
        $stripeCustomerId = $session['customer'] ?? '';
        
        // Buscar detalhes da assinatura no Stripe
        $subscriptionData = null;
        if ($stripeSubscriptionId) {
            $subscriptionData = $this->stripe->getSubscription($stripeSubscriptionId);
        }
        
        // Determinar status e datas
        $status = 'active';
        $trialStart = null;
        $trialEnd = null;
        $periodStart = null;
        $periodEnd = null;
        
        if ($subscriptionData) {
            $status = $subscriptionData['status'] ?? 'active';
            
            if ($subscriptionData['trial_start'] ?? null) {
                $trialStart = date('Y-m-d H:i:s', $subscriptionData['trial_start']);
            }
            if ($subscriptionData['trial_end'] ?? null) {
                $trialEnd = date('Y-m-d H:i:s', $subscriptionData['trial_end']);
            }
            if ($subscriptionData['current_period_start'] ?? null) {
                $periodStart = date('Y-m-d H:i:s', $subscriptionData['current_period_start']);
            }
            if ($subscriptionData['current_period_end'] ?? null) {
                $periodEnd = date('Y-m-d H:i:s', $subscriptionData['current_period_end']);
            }
        }
        
        // Verificar se já existe assinatura
        $existing = $this->subscriptionService->getSubscriptionByStripeId($stripeSubscriptionId);
        
        if ($existing) {
            // Atualizar existente
            $this->subscriptionService->updateSubscription($existing['id'], [
                'status' => $status,
                'trial_start' => $trialStart,
                'trial_end' => $trialEnd,
                'trial_used' => $trialEnd !== null,
                'current_period_start' => $periodStart,
                'current_period_end' => $periodEnd,
            ]);
        } else {
            // Criar nova assinatura
            $plan = $this->subscriptionService->getPlanBySlug($planSlug);
            
            $this->subscriptionService->createSubscription([
                'user_id' => $userId,
                'stripe_customer_id' => $stripeCustomerId,
                'stripe_subscription_id' => $stripeSubscriptionId,
                'stripe_price_id' => $plan['stripe_price_id'] ?? null,
                'plan_slug' => $planSlug ?: 'unknown',
                'tier' => $tier,
                'interval_type' => $plan['interval_type'] ?? 'month',
                'status' => $status,
                'trial_start' => $trialStart,
                'trial_end' => $trialEnd,
                'trial_used' => $trialEnd !== null,
                'current_period_start' => $periodStart,
                'current_period_end' => $periodEnd,
                'source' => 'stripe',
            ]);
        }
        
        // Sincronizar tier do usuário
        $this->subscriptionService->syncUserTier($userId);
        
        $this->log('checkout.session.completed: Success', [
            'user_id' => $userId,
            'tier' => $tier,
            'status' => $status,
        ]);
        
        return true;
    }
    
    /**
     * Assinatura criada
     */
    private function handleSubscriptionCreated(array $event): bool
    {
        $subscription = $event['data']['object'] ?? [];
        $stripeSubscriptionId = $subscription['id'] ?? '';
        
        // Verificar se já processamos via checkout
        $existing = $this->subscriptionService->getSubscriptionByStripeId($stripeSubscriptionId);
        if ($existing) {
            return true; // Já existe, processado pelo checkout
        }
        
        // Processar se não existir (fallback)
        $metadata = $subscription['metadata'] ?? [];
        $userId = (int)($metadata['user_id'] ?? 0);
        
        if (!$userId) {
            // Tentar encontrar pelo customer
            $customerId = $subscription['customer'] ?? '';
            if ($customerId) {
                $user = Database::fetch('SELECT id FROM users WHERE stripe_customer_id = ?', [$customerId]);
                $userId = (int)($user['id'] ?? 0);
            }
        }
        
        if (!$userId) {
            $this->log('subscription.created: No user found', $metadata, 'warning');
            return false;
        }
        
        // Criar assinatura no banco
        $planSlug = $metadata['plan_slug'] ?? 'unknown';
        $tier = $metadata['tier'] ?? 'PLUS';
        
        $status = $subscription['status'] ?? 'active';
        $trialEnd = isset($subscription['trial_end']) ? date('Y-m-d H:i:s', $subscription['trial_end']) : null;
        $periodEnd = isset($subscription['current_period_end']) ? date('Y-m-d H:i:s', $subscription['current_period_end']) : null;
        
        $this->subscriptionService->createSubscription([
            'user_id' => $userId,
            'stripe_customer_id' => $subscription['customer'] ?? null,
            'stripe_subscription_id' => $stripeSubscriptionId,
            'plan_slug' => $planSlug,
            'tier' => $tier,
            'interval_type' => 'month',
            'status' => $status,
            'trial_end' => $trialEnd,
            'trial_used' => $trialEnd !== null,
            'current_period_end' => $periodEnd,
            'source' => 'stripe',
        ]);
        
        $this->subscriptionService->syncUserTier($userId);
        
        return true;
    }
    
    /**
     * Assinatura atualizada
     */
    private function handleSubscriptionUpdated(array $event): bool
    {
        $subscription = $event['data']['object'] ?? [];
        $stripeSubscriptionId = $subscription['id'] ?? '';
        
        $existing = $this->subscriptionService->getSubscriptionByStripeId($stripeSubscriptionId);
        if (!$existing) {
            return false;
        }
        
        $status = $subscription['status'] ?? $existing['status'];
        $cancelAtPeriodEnd = $subscription['cancel_at_period_end'] ?? false;
        
        $updateData = [
            'status' => $status,
            'cancel_at_period_end' => $cancelAtPeriodEnd,
        ];
        
        if (isset($subscription['trial_end'])) {
            $updateData['trial_end'] = date('Y-m-d H:i:s', $subscription['trial_end']);
        }
        if (isset($subscription['current_period_start'])) {
            $updateData['current_period_start'] = date('Y-m-d H:i:s', $subscription['current_period_start']);
        }
        if (isset($subscription['current_period_end'])) {
            $updateData['current_period_end'] = date('Y-m-d H:i:s', $subscription['current_period_end']);
        }
        if (isset($subscription['canceled_at'])) {
            $updateData['canceled_at'] = date('Y-m-d H:i:s', $subscription['canceled_at']);
        }
        
        $this->subscriptionService->updateSubscription($existing['id'], $updateData);
        $this->subscriptionService->syncUserTier($existing['user_id']);
        
        return true;
    }
    
    /**
     * Assinatura deletada/expirada
     */
    private function handleSubscriptionDeleted(array $event): bool
    {
        $subscription = $event['data']['object'] ?? [];
        $stripeSubscriptionId = $subscription['id'] ?? '';
        
        $existing = $this->subscriptionService->getSubscriptionByStripeId($stripeSubscriptionId);
        if (!$existing) {
            return false;
        }
        
        $this->subscriptionService->updateSubscription($existing['id'], [
            'status' => 'canceled',
            'ended_at' => date('Y-m-d H:i:s'),
        ]);
        
        $this->subscriptionService->syncUserTier($existing['user_id']);
        
        $this->log('subscription.deleted: User tier synced', [
            'user_id' => $existing['user_id'],
        ]);
        
        return true;
    }
    
    /**
     * Invoice paga
     */
    private function handleInvoicePaid(array $event): bool
    {
        $invoice = $event['data']['object'] ?? [];
        
        $stripeSubscriptionId = $invoice['subscription'] ?? '';
        $existing = $this->subscriptionService->getSubscriptionByStripeId($stripeSubscriptionId);
        
        $userId = null;
        if ($existing) {
            $userId = $existing['user_id'];
        } else {
            // Tentar encontrar pelo customer
            $customerId = $invoice['customer'] ?? '';
            if ($customerId) {
                $user = Database::fetch('SELECT id FROM users WHERE stripe_customer_id = ?', [$customerId]);
                $userId = (int)($user['id'] ?? 0);
            }
        }
        
        if (!$userId) {
            return false;
        }
        
        // Extrair detalhes do pagamento
        $charge = $invoice['charge'] ?? null;
        $paymentMethod = null;
        $cardLast4 = null;
        $cardBrand = null;
        
        if (is_array($charge)) {
            $pm = $charge['payment_method_details'] ?? [];
            $paymentMethod = $pm['type'] ?? 'card';
            if (isset($pm['card'])) {
                $cardLast4 = $pm['card']['last4'] ?? null;
                $cardBrand = $pm['card']['brand'] ?? null;
            }
        }
        
        // Registrar pagamento
        $this->subscriptionService->recordPayment([
            'user_id' => $userId,
            'subscription_id' => $existing['id'] ?? null,
            'stripe_invoice_id' => $invoice['id'] ?? null,
            'stripe_charge_id' => is_string($charge) ? $charge : ($charge['id'] ?? null),
            'stripe_payment_intent_id' => $invoice['payment_intent'] ?? null,
            'amount_cents' => $invoice['amount_paid'] ?? 0,
            'currency' => strtoupper($invoice['currency'] ?? 'BRL'),
            'status' => 'succeeded',
            'payment_method_type' => $paymentMethod,
            'card_last4' => $cardLast4,
            'card_brand' => $cardBrand,
            'description' => 'Pagamento de assinatura',
            'hosted_invoice_url' => $invoice['hosted_invoice_url'] ?? null,
            'invoice_pdf_url' => $invoice['invoice_pdf'] ?? null,
        ]);
        
        // Atualizar período da assinatura
        if ($existing && isset($invoice['lines']['data'][0])) {
            $line = $invoice['lines']['data'][0];
            $this->subscriptionService->updateSubscription($existing['id'], [
                'status' => 'active',
                'current_period_start' => date('Y-m-d H:i:s', $line['period']['start'] ?? time()),
                'current_period_end' => date('Y-m-d H:i:s', $line['period']['end'] ?? time()),
            ]);
            $this->subscriptionService->syncUserTier($userId);
        }
        
        return true;
    }
    
    /**
     * Pagamento de invoice falhou
     */
    private function handleInvoicePaymentFailed(array $event): bool
    {
        $invoice = $event['data']['object'] ?? [];
        
        $stripeSubscriptionId = $invoice['subscription'] ?? '';
        $existing = $this->subscriptionService->getSubscriptionByStripeId($stripeSubscriptionId);
        
        $userId = $existing['user_id'] ?? null;
        
        if (!$userId) {
            $customerId = $invoice['customer'] ?? '';
            if ($customerId) {
                $user = Database::fetch('SELECT id FROM users WHERE stripe_customer_id = ?', [$customerId]);
                $userId = (int)($user['id'] ?? 0);
            }
        }
        
        if (!$userId) {
            return false;
        }
        
        // Registrar falha
        $this->subscriptionService->recordPayment([
            'user_id' => $userId,
            'subscription_id' => $existing['id'] ?? null,
            'stripe_invoice_id' => $invoice['id'] ?? null,
            'stripe_payment_intent_id' => $invoice['payment_intent'] ?? null,
            'amount_cents' => $invoice['amount_due'] ?? 0,
            'currency' => strtoupper($invoice['currency'] ?? 'BRL'),
            'status' => 'failed',
            'failure_message' => 'Pagamento recusado',
            'description' => 'Tentativa de pagamento falhou',
        ]);
        
        // Atualizar status da assinatura
        if ($existing) {
            $this->subscriptionService->updateSubscription($existing['id'], [
                'status' => 'past_due',
            ]);
        }
        
        $this->log('invoice.payment_failed: Recorded', [
            'user_id' => $userId,
            'invoice_id' => $invoice['id'] ?? null,
        ]);
        
        // Enviar email de falha de pagamento
        try {
             $userData = Database::fetch('SELECT name, email FROM users WHERE id = ?', [$userId]);
             if ($userData) {
                 $emailService = new \App\Services\EmailService();
                 $planName = $existing['plan_slug'] ?? 'Assinatura';
                 // Tentar buscar nome bonito do plano
                 if ($existing && $existing['plan_slug']) {
                     $planDetails = Database::fetch('SELECT name FROM subscription_plans WHERE slug = ?', [$existing['plan_slug']]);
                     if ($planDetails) {
                         $planName = $planDetails['name'];
                     }
                 }
                 
                 $amount = 'R$ ' . number_format(($invoice['amount_due'] ?? 0) / 100, 2, ',', '.');
                 $emailService->sendPaymentFailedEmail($userData['email'], $userData['name'], $planName, $amount);
                 
                 $this->log('Payment failed email sent', ['user_id' => $userId]);
             }
        } catch (\Throwable $e) {
             $this->log('Failed to send failure email', ['error' => $e->getMessage()], 'error');
        }
        
        return true;
    }
    
    /**
     * Invoice criada
     */
    private function handleInvoiceCreated(array $event): bool
    {
        // Apenas log, não requer ação
        $invoice = $event['data']['object'] ?? [];
        $this->log('invoice.created', [
            'invoice_id' => $invoice['id'] ?? null,
            'customer' => $invoice['customer'] ?? null,
        ]);
        return true;
    }
    
    /**
     * Trial vai acabar em 3 dias
     */
    private function handleTrialWillEnd(array $event): bool
    {
        $subscription = $event['data']['object'] ?? [];
        $stripeSubscriptionId = $subscription['id'] ?? '';
        
        $existing = $this->subscriptionService->getSubscriptionByStripeId($stripeSubscriptionId);
        if (!$existing) {
            return false;
        }
        
        $this->log('trial_will_end: Notification', [
            'user_id' => $existing['user_id'],
            'trial_end' => date('Y-m-d H:i:s', $subscription['trial_end'] ?? time()),
        ]);
        
        // TODO: Trigger email de trial expirando
        
        return true;
    }
    
    /**
     * Reembolso
     */
    private function handleChargeRefunded(array $event): bool
    {
        $charge = $event['data']['object'] ?? [];
        
        $customerId = $charge['customer'] ?? '';
        if (!$customerId) {
            return false;
        }
        
        $user = Database::fetch('SELECT id FROM users WHERE stripe_customer_id = ?', [$customerId]);
        $userId = (int)($user['id'] ?? 0);
        
        if (!$userId) {
            return false;
        }
        
        $this->subscriptionService->recordPayment([
            'user_id' => $userId,
            'stripe_charge_id' => $charge['id'] ?? null,
            'amount_cents' => $charge['amount_refunded'] ?? 0,
            'currency' => strtoupper($charge['currency'] ?? 'BRL'),
            'status' => 'refunded',
            'description' => 'Reembolso processado',
        ]);
        
        return true;
    }
    
    /**
     * Disputa criada
     */
    private function handleDisputeCreated(array $event): bool
    {
        $dispute = $event['data']['object'] ?? [];
        
        $this->log('dispute.created: ALERT', [
            'dispute_id' => $dispute['id'] ?? null,
            'charge' => $dispute['charge'] ?? null,
            'amount' => $dispute['amount'] ?? 0,
            'reason' => $dispute['reason'] ?? null,
        ], 'warning');
        
        // TODO: Notificar admin urgentemente
        
        return true;
    }
    
    // =========================================================================
    // LOGGING
    // =========================================================================
    
    private function log(string $message, array $context = [], string $level = 'info'): void
    {
        try {
            $logger = Application::getInstance()->logger();
            $context['component'] = 'StripeWebhook';
            
            match ($level) {
                'error' => $logger->error($message, $context),
                'warning' => $logger->warning($message, $context),
                default => $logger->info($message, $context),
            };
        } catch (\Throwable $e) {
            // Fallback para error_log
            error_log("[StripeWebhook] {$message}: " . json_encode($context));
        }
    }
}
