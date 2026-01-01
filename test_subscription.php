<?php
/**
 * Script de teste para verificar se o sistema de assinaturas está funcionando
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h2>Teste do Sistema de Assinaturas</h2>";

try {
    require __DIR__ . '/vendor/autoload.php';
    
    // Carregar dotenv
    $dotenv = Dotenv\Dotenv::createMutable(__DIR__);
    $dotenv->safeLoad();
    echo "✅ Dotenv carregado<br>";
    
    // Inicializar Application
    $app = \App\Core\Application::getInstance();
    echo "✅ Application inicializada<br>";
    
    // Inicializar Database
    \App\Core\Database::init($app->config('database'));
    echo "✅ Database conectado<br>";
    
    // Testar StripeService
    $stripe = new \App\Services\StripeService();
    echo "✅ StripeService criado<br>";
    
    $config = $stripe->getConfig();
    echo "- Stripe Public Key: " . (substr($config['public_key'], 0, 20) ?? 'VAZIO') . "...<br>";
    echo "- Stripe Secret Key: " . (empty($config['secret_key']) ? 'VAZIO' : 'OK (configurada)') . "<br>";
    
    // Testar SubscriptionService
    $subscriptionService = new \App\Services\SubscriptionService();
    echo "✅ SubscriptionService criado<br>";
    
    // Listar planos
    $plans = $subscriptionService->getActivePlans();
    echo "✅ Planos carregados: " . count($plans) . " plano(s)<br>";
    
    foreach ($plans as $plan) {
        echo "  - {$plan['name']} ({$plan['tier']}) - R$ " . number_format($plan['price_cents']/100, 2, ',', '.') . "<br>";
    }
    
    echo "<h3>RESULTADO: Sistema funcionando! ✅</h3>";
    
} catch (Throwable $e) {
    echo "<h3 style='color: red;'>ERRO: " . $e->getMessage() . "</h3>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
