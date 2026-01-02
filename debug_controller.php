<?php
/**
 * Debug: Testar instanciação do SubscriptionController
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h2>Teste do SubscriptionController</h2>\n";

try {
    require __DIR__ . '/vendor/autoload.php';
    echo "1. Autoload OK<br>\n";
    
    // Carregar dotenv
    $dotenv = Dotenv\Dotenv::createMutable(__DIR__);
    $dotenv->safeLoad();
    echo "2. Dotenv OK<br>\n";
    
    // Application
    $app = \App\Core\Application::getInstance();
    echo "3. Application OK<br>\n";
    
    // Database
    \App\Core\Database::init($app->config('database'));
    echo "4. Database OK<br>\n";
    
    // AuthService
    $auth = new \App\Services\AuthService();
    echo "5. AuthService OK<br>\n";
    
    // StripeService
    $stripe = new \App\Services\StripeService();
    echo "6. StripeService OK<br>\n";
    
    // SubscriptionService
    $sub = new \App\Services\SubscriptionService();
    echo "7. SubscriptionService OK<br>\n";
    
    // BaseController precisa de session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // SubscriptionController
    $controller = new \App\Controllers\SubscriptionController();
    echo "8. SubscriptionController OK<br>\n";
    
    echo "<h3 style='color: green;'>SUCESSO: Tudo funcionando!</h3>\n";
    
} catch (Throwable $e) {
    echo "<h3 style='color: red;'>ERRO: " . htmlspecialchars($e->getMessage()) . "</h3>\n";
    echo "<p>Arquivo: " . htmlspecialchars($e->getFile()) . ":" . $e->getLine() . "</p>\n";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>\n";
}
