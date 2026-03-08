<?php
// Test specific controller method with full app initialization
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Simular request normal
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/secure/adm/subscriptions';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

// Carregar autoload
require_once __DIR__ . '/vendor/autoload.php';

// Carregar .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

echo "=== TESTE CONTROLLER ===\n\n";

// Inicializar Application (igual ao index.php)
echo "1. Inicializando Application...\n";
try {
    $app = App\Core\Application::getInstance();
    echo "   OK\n\n";
} catch (Throwable $e) {
    echo "   ERRO: " . $e->getMessage() . "\n";
    exit(1);
}

// Simular sessão
echo "2. Iniciando sessão...\n";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['admin_id'] = 1;
$_SESSION['admin_username'] = 'test@admin.com';
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
echo "   OK\n\n";

try {
    echo "3. Criando controller...\n";
    $controller = new \App\Controllers\Admin\SubscriptionAdminController();
    echo "   OK\n\n";
    
    echo "4. Chamando metodo index()...\n";
    ob_start();
    $controller->index();
    $output = ob_get_clean();
    
    if (strlen($output) > 0) {
        echo "   OK - Output: " . strlen($output) . " bytes\n";
        echo "   Primeiros 500 chars:\n";
        echo substr($output, 0, 500) . "\n";
    } else {
        echo "   AVISO: Output vazio\n";
    }
    
} catch (Throwable $e) {
    echo "   ERRO: " . $e->getMessage() . "\n";
    echo "   Arquivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "   Stack trace (últimos 5):\n";
    $trace = explode("\n", $e->getTraceAsString());
    echo implode("\n", array_slice($trace, 0, 5)) . "\n";
}

echo "\n=== FIM ===\n";
