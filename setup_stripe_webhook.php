<?php
/**
 * Configurar Webhooks no Stripe (TESTE e PRODUÇÃO)
 */
require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$mode = $argv[1] ?? 'test';

if ($mode === 'live') {
    // Para modo live, usar chave de produção do .env (comentada)
    // Adicione STRIPE_SECRET_KEY_LIVE no .env para usar modo produção
    $secretKey = $_ENV['STRIPE_SECRET_KEY_LIVE'] ?? die("ERRO: STRIPE_SECRET_KEY_LIVE não definido no .env\n");
    echo "⚠️  MODO: PRODUÇÃO (LIVE)\n";
} else {
    $secretKey = $_ENV['STRIPE_SECRET_KEY'];
    echo "🧪 MODO: TESTE\n";
}

\Stripe\Stripe::setApiKey($secretKey);

$webhookUrl = 'https://terminal.operebem.com.br/api/stripe/webhook';
$events = [
    'checkout.session.completed',
    'customer.subscription.created',
    'customer.subscription.updated',
    'customer.subscription.deleted',
    'invoice.paid',
    'invoice.payment_failed',
    'invoice.payment_succeeded',
    'customer.subscription.trial_will_end',
];

echo "======================================\n";
echo "Configurando Webhook para: {$webhookUrl}\n\n";

try {
    // Verificar webhooks existentes
    $webhooks = \Stripe\WebhookEndpoint::all(['limit' => 20]);
    
    echo "📋 Webhooks existentes:\n";
    $existingWebhook = null;
    
    foreach ($webhooks->data as $wh) {
        $status = $wh->status === 'enabled' ? '✅' : '❌';
        echo "   {$status} {$wh->url} (ID: {$wh->id})\n";
        
        if ($wh->url === $webhookUrl) {
            $existingWebhook = $wh;
        }
    }
    
    echo "\n";
    
    if ($existingWebhook && $existingWebhook->status === 'enabled') {
        echo "⚠️  Webhook já existe e está ativo!\n";
        echo "   ID: {$existingWebhook->id}\n";
        echo "   Para obter o secret, acesse o Stripe Dashboard.\n";
    } else {
        // Criar novo webhook
        echo "🔧 Criando novo webhook...\n";
        
        $endpoint = \Stripe\WebhookEndpoint::create([
            'url' => $webhookUrl,
            'enabled_events' => $events,
            'description' => 'Terminal Operebem - Subscription Webhooks',
        ]);
        
        echo "\n======================================\n";
        echo "✅ WEBHOOK CRIADO COM SUCESSO!\n\n";
        echo "📋 Detalhes:\n";
        echo "   ID: {$endpoint->id}\n";
        echo "   URL: {$endpoint->url}\n";
        echo "   Status: {$endpoint->status}\n\n";
        
        echo "🔐 WEBHOOK SECRET (IMPORTANTE!):\n";
        echo "   {$endpoint->secret}\n\n";
        
        echo "📝 Adicione este secret ao .env:\n";
        if ($mode === 'live') {
            echo "   STRIPE_WEBHOOK_SECRET_LIVE={$endpoint->secret}\n";
        } else {
            echo "   STRIPE_WEBHOOK_SECRET={$endpoint->secret}\n";
        }
    }
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
