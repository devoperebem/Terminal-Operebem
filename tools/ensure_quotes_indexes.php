<?php
// Ensure PostgreSQL indexes for quotes database (dicionario)
require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use App\Services\QuotesService;

echo "\n== Ensuring indexes on quotes database ==\n";

try {
    $base = dirname(__DIR__);
    try {
        $dotenv = Dotenv::createMutable($base);
        $dotenv->safeLoad();
        if (is_file($base . '/.env.local')) {
            Dotenv::createMutable($base, '.env.local')->safeLoad();
        }
    } catch (\Throwable $t) { /* ignore */ }

    $qs = new QuotesService();
    $pdo = $qs->pdo();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmts = [
        "CREATE INDEX IF NOT EXISTS idx_dicionario_id_api ON dicionario (id_api)",
        "CREATE INDEX IF NOT EXISTS idx_dicionario_code ON dicionario (code)",
        "CREATE INDEX IF NOT EXISTS idx_dicionario_ativo ON dicionario (ativo)",
        "CREATE INDEX IF NOT EXISTS idx_dicionario_origem ON dicionario (origem)",
        "CREATE INDEX IF NOT EXISTS idx_dicionario_origem_ativo ON dicionario (origem, ativo)",
        "CREATE INDEX IF NOT EXISTS idx_dicionario_ativo_order ON dicionario (ativo, order_tabela)"
    ];

    foreach ($stmts as $sql) {
        echo " - $sql\n";
        $pdo->exec($sql);
    }

    echo "\nOK: indexes ensured.\n";
    exit(0);
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
