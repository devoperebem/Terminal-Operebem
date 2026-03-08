<?php
// CLI script to seed admin user: mateus@operebem.com.br / admin123

declare(strict_types=1);

// Bootstrap
require_once dirname(__DIR__) . '/vendor/autoload.php';

use App\Core\Application;
use App\Core\Database;
use App\Services\AdminAuthService;

try {
    // Initialize app and DB
    $app = Application::getInstance();
    Database::init($app->config('database'));

    $svc = new AdminAuthService();
    // Ensure table exists
    $svc->ensureTableAndSeed();

    // Check if user exists
    $exists = Database::fetch('SELECT id FROM admin_users WHERE username = ?', ['mateus@operebem.com.br']);
    if ($exists) {
        echo "OK: Admin 'mateus@operebem.com.br' já existe (id={$exists['id']}).\n";
        exit(0);
    }

    // Create admin
    $res = $svc->createAdmin('mateus@operebem.com.br', 'admin123');
    if (!($res['success'] ?? false)) {
        fwrite(STDERR, "ERRO: " . ($res['message'] ?? 'Falha ao criar usuário') . "\n");
        exit(1);
    }

    $row = Database::fetch('SELECT id FROM admin_users WHERE username = ?', ['mateus@operebem.com.br']);
    echo "OK: Admin criado com sucesso (id={$row['id']}).\n";
    exit(0);

} catch (Throwable $e) {
    fwrite(STDERR, "EXCEPTION: " . $e->getMessage() . "\n");
    exit(2);
}
