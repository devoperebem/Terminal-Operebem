<?php
/**
 * Configuração do Stripe
 * 
 * Este arquivo contém todas as configurações relacionadas à integração com o Stripe.
 */

// Helper para obter variáveis de ambiente
$getEnv = function(string $key, $default = '') {
    return $_ENV[$key] ?? getenv($key) ?: $default;
};

return [
    // API Keys (carregadas do .env)
    'public_key' => $getEnv('STRIPE_PUBLIC_KEY'),
    'secret_key' => $getEnv('STRIPE_SECRET_KEY'),
    'webhook_secret' => $getEnv('STRIPE_WEBHOOK_SECRET'),
    
    // Price IDs dos planos (criar no Stripe Dashboard)
    'prices' => [
        'plus_monthly' => $getEnv('STRIPE_PRICE_PLUS_MONTHLY'),
        'pro_yearly' => $getEnv('STRIPE_PRICE_PRO_YEARLY'),
        'pro_yearly_installments' => $getEnv('STRIPE_PRICE_PRO_YEARLY_INSTALLMENTS'),
    ],
    
    // Configurações de planos
    'plans' => [
        'plus_monthly' => [
            'name' => 'PLUS Mensal',
            'tier' => 'PLUS',
            'interval' => 'month',
            'price_cents' => 2990, // R$ 29,90
            'trial_days' => 7,
            'supports_pix' => false,
        ],
        'pro_yearly' => [
            'name' => 'PRO Anual',
            'tier' => 'PRO',
            'interval' => 'year',
            'price_cents' => 69700, // R$ 697,00
            'trial_days' => 7,
            'supports_pix' => true,
        ],
        'pro_yearly_installments' => [
            'name' => 'PRO Anual (12x)',
            'tier' => 'PRO',
            'interval' => 'year',
            'price_cents' => 83880, // 12x R$ 69,90 = R$ 838,80
            'trial_days' => 7,
            'supports_pix' => false,
            'installments' => 12,
        ],
    ],
    
    // URLs de callback
    'success_url' => $getEnv('STRIPE_SUCCESS_URL', 'https://terminal.operebem.com.br/subscription/success?session_id={CHECKOUT_SESSION_ID}'),
    'cancel_url' => $getEnv('STRIPE_CANCEL_URL', 'https://terminal.operebem.com.br/subscription/canceled'),
    
    // Trial padrão
    'default_trial_days' => (int)$getEnv('SUBSCRIPTION_TRIAL_DAYS', '7'),
    
    // Moeda
    'currency' => 'brl',
    
    // Métodos de pagamento
    'payment_methods' => ['card'],
    'payment_methods_pix' => ['card', 'pix'], // Para planos que suportam PIX
];
