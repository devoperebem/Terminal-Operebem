<?php
/**
 * Debug: Testar instanciação do SubscriptionController
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

// Registrar handler de erro fatal
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        echo "<h3 style='color: red;'>ERRO FATAL: " . htmlspecialchars($error['message']) . "</h3>\n";
        echo "<p>Arquivo: " . htmlspecialchars($error['file']) . ":" . $error['line'] . "</p>\n";
    }
});

echo "<h2>Teste do SubscriptionController</h2>\n";
ob_flush(); flush();

try {
    require __DIR__ . '/vendor/autoload.php';
    echo "1. Autoload OK<br>\n"; ob_flush(); flush();
    
    // Carregar dotenv
    $dotenv = Dotenv\Dotenv::createMutable(__DIR__);
    $dotenv->safeLoad();
    echo "2. Dotenv OK<br>\n"; ob_flush(); flush();
    
    // Application
    $app = \App\Core\Application::getInstance();
    echo "3. Application OK<br>\n"; ob_flush(); flush();
    
    // Database
    \App\Core\Database::init($app->config('database'));
    echo "4. Database OK<br>\n"; ob_flush(); flush();
    
    // AuthService
    $auth = new \App\Services\AuthService();
    echo "5. AuthService OK<br>\n"; ob_flush(); flush();
    
    // StripeService
    $stripe = new \App\Services\StripeService();
    echo "6. StripeService OK<br>\n"; ob_flush(); flush();
    
    // SubscriptionService
    $sub = new \App\Services\SubscriptionService();
    echo "7. SubscriptionService OK<br>\n"; ob_flush(); flush();
    
    // Session start
    echo "7.5 Iniciando session...<br>\n"; ob_flush(); flush();
    if (session_status() === PHP_SESSION_NONE) {
        @session_start();
        echo "7.6 Session iniciada<br>\n"; ob_flush(); flush();
    } else {
        echo "7.6 Session já ativa<br>\n"; ob_flush(); flush();
    }
    
    // SubscriptionController
    echo "7.7 Criando SubscriptionController...<br>\n"; ob_flush(); flush();
    $controller = new \App\Controllers\SubscriptionController();
    echo "8. SubscriptionController OK<br>\n"; ob_flush(); flush();
    
    echo "<h3 style='color: green;'>SUCESSO: Tudo funcionando!</h3>\n";
    
} catch (Throwable $e) {
    echo "<h3 style='color: red;'>ERRO (catch): " . htmlspecialchars($e->getMessage()) . "</h3>\n";
    echo "<p>Arquivo: " . htmlspecialchars($e->getFile()) . ":" . $e->getLine() . "</p>\n";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>\n";
}
