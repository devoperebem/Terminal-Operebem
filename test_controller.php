<?php
// Test specific controller method
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

echo "=== TESTE CONTROLLER ===\n\n";

// Simular sessão
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Simular admin logado (para pegar o layout)
$_SESSION['admin_user_id'] = 1;
$_SESSION['admin_name'] = 'Test Admin';
$_SESSION['csrf_token'] = 'test_token';

try {
    echo "1. Criando controller...\n";
    $controller = new \App\Controllers\Admin\SubscriptionAdminController();
    echo "   OK\n\n";
    
    echo "2. Chamando metodo index()...\n";
    ob_start();
    $controller->index();
    $output = ob_get_clean();
    
    if (strlen($output) > 0) {
        echo "   OK - Output: " . strlen($output) . " bytes\n";
        echo "   Primeiros 200 chars:\n";
        echo substr($output, 0, 200) . "\n";
    } else {
        echo "   AVISO: Output vazio\n";
    }
    
} catch (Throwable $e) {
    echo "   ERRO: " . $e->getMessage() . "\n";
    echo "   Arquivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "   Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
}

echo "\n=== FIM ===\n";
