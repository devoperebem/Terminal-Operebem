<?php
/**
 * StripeService - Wrapper para interação com a API do Stripe
 * 
 * Este serviço encapsula todas as chamadas à API do Stripe.
 */

namespace App\Services;

use App\Core\Database;

class StripeService
{
    private string $secretKey;
    private string $publicKey;
    private string $webhookSecret;
    private array $config;
    
    private const API_BASE = 'https://api.stripe.com/v1';
    
    public function __construct()
    {
        $this->config = require __DIR__ . '/../../config/stripe.php';
        $this->secretKey = $this->config['secret_key'];
        $this->publicKey = $this->config['public_key'];
        $this->webhookSecret = $this->config['webhook_secret'];
    }
    
    /**
     * Retorna a chave pública (para frontend)
     */
    public function getPublicKey(): string
    {
        return $this->publicKey;
    }
    
    /**
     * Retorna configuração
     */
    public function getConfig(): array
    {
        return $this->config;
    }
    
    /**
     * Faz uma requisição para a API do Stripe
     */
    private function request(string $method, string $endpoint, array $data = []): array
    {
        $url = self::API_BASE . $endpoint;
        
        $ch = curl_init();
        
        $headers = [
            'Authorization: Bearer ' . $this->secretKey,
            'Content-Type: application/x-www-form-urlencoded',
        ];
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        } elseif ($method === 'GET' && !empty($data)) {
            $url .= '?' . http_build_query($data);
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }
        
        curl_setopt($ch, CURLOPT_URL, $url);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($error) {
            return ['error' => ['message' => 'cURL error: ' . $error]];
        }
        
        $result = json_decode($response, true) ?? [];
        $result['_http_code'] = $httpCode;
        
        return $result;
    }
    
    // =========================================================================
    // CUSTOMERS
    // =========================================================================
    
    /**
     * Cria ou recupera um customer do Stripe
     */
    public function getOrCreateCustomer(int $userId, string $email, string $name): ?array
    {
        // Verificar se usuário já tem customer_id
        $user = Database::fetch('SELECT stripe_customer_id FROM users WHERE id = ?', [$userId]);
        
        if (!empty($user['stripe_customer_id'])) {
            // Recuperar customer existente
            $customer = $this->request('GET', '/customers/' . $user['stripe_customer_id']);
            if (!isset($customer['error'])) {
                return $customer;
            }
        }
        
        // Criar novo customer
        $customer = $this->request('POST', '/customers', [
            'email' => $email,
            'name' => $name,
            'metadata[user_id]' => $userId,
        ]);
        
        if (!isset($customer['error']) && isset($customer['id'])) {
            // Salvar customer_id no usuário
            Database::update('users', ['stripe_customer_id' => $customer['id']], ['id' => $userId]);
            return $customer;
        }
        
        return null;
    }
    
    /**
     * Recupera um customer pelo ID
     */
    public function getCustomer(string $customerId): ?array
    {
        $result = $this->request('GET', '/customers/' . $customerId);
        return isset($result['error']) ? null : $result;
    }
    
    // =========================================================================
    // CHECKOUT SESSION
    // =========================================================================
    
    /**
     * Cria uma sessão de checkout
     */
    public function createCheckoutSession(array $params): array
    {
        return $this->request('POST', '/checkout/sessions', $params);
    }
    
    /**
     * Recupera uma sessão de checkout
     */
    public function getCheckoutSession(string $sessionId): ?array
    {
        $result = $this->request('GET', '/checkout/sessions/' . $sessionId);
        return isset($result['error']) ? null : $result;
    }
    
    /**
     * Cria uma sessão de checkout para assinatura
     */
    public function createSubscriptionCheckout(
        string $customerId,
        string $priceId,
        int $trialDays = 0,
        ?string $couponId = null,
        array $metadata = []
    ): array {
        $params = [
            'customer' => $customerId,
            'mode' => 'subscription',
            'line_items[0][price]' => $priceId,
            'line_items[0][quantity]' => 1,
            'success_url' => $this->config['success_url'],
            'cancel_url' => $this->config['cancel_url'],
            'allow_promotion_codes' => 'true',
        ];
        
        // Adicionar trial
        if ($trialDays > 0) {
            $params['subscription_data[trial_period_days]'] = $trialDays;
        }
        
        // Adicionar cupom
        if ($couponId) {
            $params['discounts[0][coupon]'] = $couponId;
        }
        
        // Adicionar metadata
        foreach ($metadata as $key => $value) {
            $params['subscription_data[metadata][' . $key . ']'] = $value;
        }
        
        // Determinar métodos de pagamento baseado no plano
        // NOTA: PIX desabilitado temporariamente até estar ativo na conta Stripe
        $planSlug = $metadata['plan_slug'] ?? '';
        $planConfig = $this->config['plans'][$planSlug] ?? null;
        
        // Por enquanto, apenas cartão. Para habilitar PIX, ativar no dashboard Stripe primeiro.
        // if ($planConfig && ($planConfig['supports_pix'] ?? false)) {
        //     $params['payment_method_types[0]'] = 'card';
        //     $params['payment_method_types[1]'] = 'pix';
        // } else {
        //     $params['payment_method_types[0]'] = 'card';
        // }
        $params['payment_method_types[0]'] = 'card';
        
        return $this->createCheckoutSession($params);
    }
    
    // =========================================================================
    // SUBSCRIPTIONS
    // =========================================================================
    
    /**
     * Recupera uma assinatura
     */
    public function getSubscription(string $subscriptionId): ?array
    {
        $result = $this->request('GET', '/subscriptions/' . $subscriptionId);
        return isset($result['error']) ? null : $result;
    }
    
    /**
     * Cancela uma assinatura ao fim do período
     */
    public function cancelSubscriptionAtPeriodEnd(string $subscriptionId): array
    {
        return $this->request('POST', '/subscriptions/' . $subscriptionId, [
            'cancel_at_period_end' => 'true',
        ]);
    }
    
    /**
     * Cancela uma assinatura imediatamente
     */
    public function cancelSubscriptionImmediately(string $subscriptionId): array
    {
        return $this->request('DELETE', '/subscriptions/' . $subscriptionId);
    }
    
    /**
     * Reativa uma assinatura cancelada (antes de expirar)
     */
    public function reactivateSubscription(string $subscriptionId): array
    {
        return $this->request('POST', '/subscriptions/' . $subscriptionId, [
            'cancel_at_period_end' => 'false',
        ]);
    }
    
    /**
     * Atualiza o trial de uma assinatura
     */
    public function updateSubscriptionTrial(string $subscriptionId, int $trialEndTimestamp): array
    {
        return $this->request('POST', '/subscriptions/' . $subscriptionId, [
            'trial_end' => $trialEndTimestamp,
        ]);
    }
    
    // =========================================================================
    // INVOICES
    // =========================================================================
    
    /**
     * Lista invoices de um customer
     */
    public function listInvoices(string $customerId, int $limit = 10): array
    {
        return $this->request('GET', '/invoices', [
            'customer' => $customerId,
            'limit' => $limit,
        ]);
    }
    
    /**
     * Recupera uma invoice
     */
    public function getInvoice(string $invoiceId): ?array
    {
        $result = $this->request('GET', '/invoices/' . $invoiceId);
        return isset($result['error']) ? null : $result;
    }
    
    // =========================================================================
    // COUPONS
    // =========================================================================
    
    /**
     * Cria um cupom no Stripe
     */
    public function createCoupon(string $id, string $discountType, int $discountValue, ?int $durationMonths = null): array
    {
        $params = [
            'id' => $id,
            'duration' => $durationMonths ? 'repeating' : 'once',
        ];
        
        if ($durationMonths) {
            $params['duration_in_months'] = $durationMonths;
        }
        
        if ($discountType === 'percent') {
            $params['percent_off'] = $discountValue;
        } else {
            $params['amount_off'] = $discountValue;
            $params['currency'] = $this->config['currency'];
        }
        
        return $this->request('POST', '/coupons', $params);
    }
    
    /**
     * Deleta um cupom
     */
    public function deleteCoupon(string $couponId): array
    {
        return $this->request('DELETE', '/coupons/' . $couponId);
    }
    
    /**
     * Cria um promotion code (código promocional público)
     */
    public function createPromotionCode(string $couponId, string $code, ?int $maxRedemptions = null): array
    {
        $params = [
            'coupon' => $couponId,
            'code' => strtoupper($code),
        ];
        
        if ($maxRedemptions) {
            $params['max_redemptions'] = $maxRedemptions;
        }
        
        return $this->request('POST', '/promotion_codes', $params);
    }
    
    // =========================================================================
    // PRODUCTS & PRICES
    // =========================================================================
    
    /**
     * Lista produtos
     */
    public function listProducts(int $limit = 10): array
    {
        return $this->request('GET', '/products', ['limit' => $limit, 'active' => 'true']);
    }
    
    /**
     * Lista preços de um produto
     */
    public function listPrices(?string $productId = null, int $limit = 10): array
    {
        $params = ['limit' => $limit, 'active' => 'true'];
        if ($productId) {
            $params['product'] = $productId;
        }
        return $this->request('GET', '/prices', $params);
    }
    
    /**
     * Cria um produto
     */
    public function createProduct(string $name, string $description = ''): array
    {
        return $this->request('POST', '/products', [
            'name' => $name,
            'description' => $description,
        ]);
    }
    
    /**
     * Cria um preço recorrente
     * 
     * @param string $productId ID do produto no Stripe
     * @param int $unitAmount Valor em centavos
     * @param string $currency Moeda (ex: BRL, USD)
     * @param string $interval Intervalo de cobrança (month, year)
     * @param int $intervalCount Quantidade de intervalos (default: 1)
     * @param int $trialDays Dias de trial (default: 0)
     * @return array Resposta do Stripe com o Price criado
     */
    public function createPrice(
        string $productId, 
        int $unitAmount, 
        string $currency = 'BRL',
        string $interval = 'month', 
        int $intervalCount = 1, 
        int $trialDays = 0
    ): array {
        $params = [
            'product' => $productId,
            'unit_amount' => $unitAmount,
            'currency' => strtolower($currency),
            'recurring[interval]' => $interval,
            'recurring[interval_count]' => $intervalCount,
        ];
        
        if ($trialDays > 0) {
            $params['recurring[trial_period_days]'] = $trialDays;
        }
        
        return $this->request('POST', '/prices', $params);
    }
    
    // =========================================================================
    // WEBHOOKS
    // =========================================================================
    
    /**
     * Verifica a assinatura de um webhook
     */
    public function verifyWebhookSignature(string $payload, string $sigHeader): bool
    {
        if (empty($this->webhookSecret)) {
            return false;
        }
        
        // Parse signature header
        $parts = explode(',', $sigHeader);
        $timestamp = null;
        $signatures = [];
        
        foreach ($parts as $part) {
            [$key, $value] = explode('=', trim($part), 2);
            if ($key === 't') {
                $timestamp = (int)$value;
            } elseif ($key === 'v1') {
                $signatures[] = $value;
            }
        }
        
        if (!$timestamp || empty($signatures)) {
            return false;
        }
        
        // Verificar tolerância de tempo (5 minutos)
        if (abs(time() - $timestamp) > 300) {
            return false;
        }
        
        // Calcular assinatura esperada
        $signedPayload = $timestamp . '.' . $payload;
        $expectedSignature = hash_hmac('sha256', $signedPayload, $this->webhookSecret);
        
        // Comparar com assinaturas recebidas
        foreach ($signatures as $sig) {
            if (hash_equals($expectedSignature, $sig)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Parse de evento de webhook
     */
    public function parseWebhookEvent(string $payload): ?array
    {
        return json_decode($payload, true);
    }
    
    // =========================================================================
    // HEALTH CHECK
    // =========================================================================
    
    /**
     * Verifica se a conexão com o Stripe está funcionando
     */
    public function ping(): array
    {
        $result = $this->request('GET', '/balance');
        
        if (isset($result['error'])) {
            return [
                'success' => false,
                'error' => $result['error']['message'] ?? 'Unknown error',
            ];
        }
        
        return [
            'success' => true,
            'message' => 'Connected to Stripe',
            'mode' => strpos($this->secretKey, '_test_') !== false ? 'test' : 'live',
        ];
    }
}
