<?php
/**
 * Migration Runner - Executar migrations via URL
 * ATENÇÃO: Este arquivo deve ser protegido ou removido após uso em produção
 * Acesso: /migrate.php?token=SEU_TOKEN_SECRETO&migration=006
 */

// Token de segurança - ALTERE ESTE VALOR!
define('MIGRATION_TOKEN', 'operebem_migration_2025');

// Verificar token
$token = $_GET['token'] ?? '';
if ($token !== MIGRATION_TOKEN) {
    http_response_code(403);
    die('❌ Acesso negado. Token inválido.');
}

// Carregar configuração do banco
require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

// Configuração do banco
$dbConfig = [
    'host' => $_ENV['DB_HOST'] ?? 'localhost',
    'port' => $_ENV['DB_PORT'] ?? '5432',
    'database' => $_ENV['DB_NAME'] ?? '',
    'user' => $_ENV['DB_USER'] ?? '',
    'password' => $_ENV['DB_PASSWORD'] ?? ''
];

// Verificar qual migration executar
$migration = $_GET['migration'] ?? '';
$migrationsDir = dirname(__DIR__) . '/database/migrations';

if (empty($migration)) {
    // Listar migrations disponíveis
    $files = glob($migrationsDir . '/*.sql');
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Migrations</title>";
    echo "<style>body{font-family:monospace;padding:20px;background:#f5f5f5;}";
    echo "h1{color:#333;}ul{list-style:none;padding:0;}";
    echo "li{background:#fff;margin:10px 0;padding:15px;border-radius:5px;border-left:4px solid #007bff;}";
    echo "a{color:#007bff;text-decoration:none;font-weight:bold;}a:hover{text-decoration:underline;}";
    echo ".info{color:#666;font-size:0.9em;margin-top:5px;}</style></head><body>";
    echo "<h1>📋 Migrations Disponíveis</h1>";
    echo "<p class='info'>Clique para executar uma migration</p>";
    echo "<ul>";
    foreach ($files as $file) {
        $filename = basename($file);
        $number = preg_match('/^(\d+)_/', $filename, $matches) ? $matches[1] : '';
        echo "<li>";
        echo "<a href='?token=" . urlencode($token) . "&migration=" . urlencode($number) . "'>";
        echo "🔧 " . htmlspecialchars($filename);
        echo "</a>";
        echo "</li>";
    }
    echo "</ul></body></html>";
    exit;
}

// Executar migration específica
$migrationFile = null;
$files = glob($migrationsDir . '/' . $migration . '_*.sql');

if (empty($files)) {
    http_response_code(404);
    die("❌ Migration {$migration} não encontrada.");
}

$migrationFile = $files[0];
$migrationName = basename($migrationFile);

// Ler SQL
$sql = file_get_contents($migrationFile);
if ($sql === false) {
    http_response_code(500);
    die("❌ Erro ao ler arquivo de migration.");
}

// Conectar ao banco
try {
    $dsn = sprintf(
        "pgsql:host=%s;port=%s;dbname=%s",
        $dbConfig['host'],
        $dbConfig['port'],
        $dbConfig['database']
    );
    
    $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    // Executar migration
    $pdo->exec($sql);
    
    // Sucesso
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Migration Executada</title>";
    echo "<style>body{font-family:monospace;padding:20px;background:#f5f5f5;}";
    echo ".success{background:#d4edda;color:#155724;padding:20px;border-radius:5px;border-left:4px solid #28a745;}";
    echo "pre{background:#fff;padding:15px;border-radius:5px;overflow-x:auto;margin-top:15px;}";
    echo "a{display:inline-block;margin-top:15px;color:#007bff;text-decoration:none;}a:hover{text-decoration:underline;}</style></head><body>";
    echo "<div class='success'>";
    echo "<h1>✅ Migration Executada com Sucesso!</h1>";
    echo "<p><strong>Arquivo:</strong> " . htmlspecialchars($migrationName) . "</p>";
    echo "<p><strong>Banco:</strong> " . htmlspecialchars($dbConfig['database']) . "</p>";
    echo "<pre>" . htmlspecialchars($sql) . "</pre>";
    echo "<a href='?token=" . urlencode($token) . "'>← Voltar para lista de migrations</a>";
    echo "</div></body></html>";
    
} catch (PDOException $e) {
    http_response_code(500);
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Erro na Migration</title>";
    echo "<style>body{font-family:monospace;padding:20px;background:#f5f5f5;}";
    echo ".error{background:#f8d7da;color:#721c24;padding:20px;border-radius:5px;border-left:4px solid #dc3545;}";
    echo "pre{background:#fff;padding:15px;border-radius:5px;overflow-x:auto;margin-top:15px;}</style></head><body>";
    echo "<div class='error'>";
    echo "<h1>❌ Erro ao Executar Migration</h1>";
    echo "<p><strong>Arquivo:</strong> " . htmlspecialchars($migrationName) . "</p>";
    echo "<p><strong>Erro:</strong></p>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    echo "</div></body></html>";
}
?>
