<?php
require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

if (strpos($_ENV['STRIPE_SECRET_KEY'], 'sk_test_') !== 0 || strpos($_ENV['STRIPE_SECRET_KEY'], '...') !== false) {
    die("ERRO: STRIPE_SECRET_KEY inválida no .env. Atualize com a chave real.\n");
}

\Stripe\Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);

$webhookUrl = 'https://terminal.operebem.com.br/api/stripe/webhook';
$events = [
    'checkout.session.completed',
    'customer.subscription.created',
    'customer.subscription.updated',
    'customer.subscription.deleted',
    'invoice.paid',
    'invoice.payment_failed',
];

echo "Configurando Webhook para: {$webhookUrl}\n";

try {
    // Verificar se já existe
    $webhooks = \Stripe\WebhookEndpoint::all(['limit' => 10]);
    foreach ($webhooks->data as $wh) {
        if ($wh->url === $webhookUrl && $wh->status === 'enabled') {
            echo "Webhook já existe! ID: {$wh->id}\n";
            echo "Secret: (Você deve copiar do dashboard se não tiver salvo)\n";
            exit;
        }
    }

    // Criar novo
    $endpoint = \Stripe\WebhookEndpoint::create([
        'url' => $webhookUrl,
        'enabled_events' => $events,
    ]);

    echo "Webhook criado com sucesso!\n";
    echo "ID: {$endpoint->id}\n";
    echo "Signing Secret: {$endpoint->secret}\n\n";
    echo "IMPORTANTE: Adicione este secret ao seu .env como STRIPE_WEBHOOK_SECRET\n";

} catch (Exception $e) {
    echo "Erro ao criar webhook: " . $e->getMessage() . "\n";
}
