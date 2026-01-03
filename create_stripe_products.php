<?php
/**
 * Script para criar produtos e preços no Stripe
 * Este script usa a API do Stripe para criar os produtos de assinatura
 */

require __DIR__ . '/vendor/autoload.php';

// Carregar .env
$dotenv = Dotenv\Dotenv::createMutable(__DIR__);
$dotenv->safeLoad();

$secretKey = $_ENV['STRIPE_SECRET_KEY'] ?? getenv('STRIPE_SECRET_KEY');

if (empty($secretKey)) {
    die("❌ STRIPE_SECRET_KEY não configurada\n");
}

echo "=== Criando Produtos no Stripe ===\n\n";
echo "Modo: " . (strpos($secretKey, '_test_') !== false ? 'TESTE' : 'PRODUÇÃO') . "\n\n";

/**
 * Faz requisição para a API do Stripe
 */
function stripeRequest(string $method, string $endpoint, array $data = []): array {
    global $secretKey;
    
    $url = 'https://api.stripe.com/v1' . $endpoint;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $secretKey,
        'Content-Type: application/x-www-form-urlencoded',
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    }
    
    curl_setopt($ch, CURLOPT_URL, $url);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $result = json_decode($response, true) ?? [];
    $result['_http_code'] = $httpCode;
    
    return $result;
}

// ============================================================================
// 1. Criar Produto PLUS
// ============================================================================
echo "1️⃣ Criando produto PLUS Terminal Operebem...\n";

$productPlus = stripeRequest('POST', '/products', [
    'name' => 'PLUS Terminal Operebem',
    'description' => 'Assinatura mensal do plano PLUS - Acesso ao Dashboard Ouro e funcionalidades premium',
    'metadata[tier]' => 'PLUS',
    'metadata[slug]' => 'plus_monthly',
    'statement_descriptor' => 'OPEREBEM PLUS',
]);

if (isset($productPlus['error'])) {
    die("❌ Erro ao criar produto PLUS: " . $productPlus['error']['message'] . "\n");
}

$productPlusId = $productPlus['id'];
echo "   ✅ Produto criado: {$productPlusId}\n";

// ============================================================================
// 2. Criar Preço PLUS Mensal (R$ 29,90)
// ============================================================================
echo "2️⃣ Criando preço PLUS Mensal (R$ 29,90/mês)...\n";

$pricePlus = stripeRequest('POST', '/prices', [
    'product' => $productPlusId,
    'unit_amount' => 2990, // R$ 29,90 em centavos
    'currency' => 'brl',
    'recurring[interval]' => 'month',
    'recurring[interval_count]' => 1,
    'metadata[plan_slug]' => 'plus_monthly',
]);

if (isset($pricePlus['error'])) {
    die("❌ Erro ao criar preço PLUS: " . $pricePlus['error']['message'] . "\n");
}

$pricePlusId = $pricePlus['id'];
echo "   ✅ Preço criado: {$pricePlusId}\n";

// ============================================================================
// 3. Criar Produto PRO
// ============================================================================
echo "3️⃣ Criando produto PRO Terminal Operebem...\n";

$productPro = stripeRequest('POST', '/products', [
    'name' => 'PRO Terminal Operebem',
    'description' => 'Assinatura anual do plano PRO - Acesso completo a todas as funcionalidades do Terminal',
    'metadata[tier]' => 'PRO',
    'metadata[slug]' => 'pro_yearly',
    'statement_descriptor' => 'OPEREBEM PRO',
]);

if (isset($productPro['error'])) {
    die("❌ Erro ao criar produto PRO: " . $productPro['error']['message'] . "\n");
}

$productProId = $productPro['id'];
echo "   ✅ Produto criado: {$productProId}\n";

// ============================================================================
// 4. Criar Preço PRO Anual (R$ 697,00)
// ============================================================================
echo "4️⃣ Criando preço PRO Anual (R$ 697,00/ano)...\n";

$pricePro = stripeRequest('POST', '/prices', [
    'product' => $productProId,
    'unit_amount' => 69700, // R$ 697,00 em centavos
    'currency' => 'brl',
    'recurring[interval]' => 'year',
    'recurring[interval_count]' => 1,
    'metadata[plan_slug]' => 'pro_yearly',
]);

if (isset($pricePro['error'])) {
    die("❌ Erro ao criar preço PRO: " . $pricePro['error']['message'] . "\n");
}

$priceProId = $pricePro['id'];
echo "   ✅ Preço criado: {$priceProId}\n";

// ============================================================================
// Resumo
// ============================================================================
echo "\n=== RESUMO ===\n";
echo "Produtos e Preços criados com sucesso!\n\n";

echo "📋 Adicione estas variáveis ao .env:\n";
echo "STRIPE_PRICE_PLUS_MONTHLY={$pricePlusId}\n";
echo "STRIPE_PRICE_PRO_YEARLY={$priceProId}\n\n";

echo "📋 Product IDs (para referência):\n";
echo "PLUS Product: {$productPlusId}\n";
echo "PRO Product: {$productProId}\n\n";

echo "✅ Pronto! Agora atualize o .env e a tabela subscription_plans.\n";
