<?php
/**
 * Script para executar migration 027 via linha de comando
 * Uso: php run_migration_027.php
 */

// Carregar configuração
require_once __DIR__ . '/vendor/autoload.php';

use App\Core\Application;
use App\Core\Database;

echo "===========================================\n";
echo " Executando Migration 027\n";
echo "===========================================\n\n";

try {
    // Inicializar aplicação
    $app = Application::getInstance();
    $config = $app->config('database');
    
    // Conectar ao banco
    Database::init($config);
    $pdo = Database::connection();
    
    echo "✅ Conexão com banco de dados estabelecida\n\n";
    
    // Verificar se migration já foi executada
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM migrations WHERE filename = ?");
    $stmt->execute(['027_create_admin_audit_logs.sql']);
    $count = $stmt->fetchColumn();
    
    if ($count > 0) {
        echo "⚠️  Migration 027 já foi executada anteriormente.\n";
        echo "   Nenhuma ação necessária.\n\n";
        exit(0);
    }
    
    // Ler arquivo de migration
    $migrationFile = __DIR__ . '/database/migrations/027_create_admin_audit_logs.sql';
    
    if (!file_exists($migrationFile)) {
        throw new Exception("Arquivo de migration não encontrado: $migrationFile");
    }
    
    $sql = file_get_contents($migrationFile);
    echo "📄 Arquivo de migration carregado\n\n";
    
    // Executar migration
    echo "🔧 Executando SQL...\n\n";
    
    $pdo->beginTransaction();
    
    try {
        // Executar o SQL
        $pdo->exec($sql);
        
        // Registrar migration como executada
        $stmt = $pdo->prepare("INSERT INTO migrations (filename, executed_at) VALUES (?, NOW())");
        $stmt->execute(['027_create_admin_audit_logs.sql']);
        
        $pdo->commit();
        
        echo "✅ Migration 027 executada com sucesso!\n\n";
        echo "Alterações realizadas:\n";
        echo "  • Tabela 'admin_audit_logs' criada\n";
        echo "  • Índices de performance criados\n";
        echo "  • Campo 'trial_extended_days' adicionado em 'subscriptions'\n";
        echo "  • Campo 'deleted_at' adicionado em 'coupons'\n\n";
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
    
    echo "===========================================\n";
    echo " Migration concluída!\n";
    echo "===========================================\n";
    
} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
