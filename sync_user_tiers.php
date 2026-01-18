<?php
/**
 * Script para sincronizar o tier de um usuário específico
 */

require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$dbHost = $_ENV['DB_HOST'];
$dbPort = $_ENV['DB_PORT'] ?? '5432';
$dbName = $_ENV['DB_DATABASE'];
$dbUser = $_ENV['DB_USERNAME'];
$dbPass = $_ENV['DB_PASSWORD'];

$dsn = "pgsql:host={$dbHost};port={$dbPort};dbname={$dbName}";

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    echo "Conectado ao banco de dados!\n\n";
    
    // Listar usuários com assinaturas
    $stmt = $pdo->query("
        SELECT 
            u.id, u.email, u.tier as user_tier, u.subscription_expires_at,
            s.id as sub_id, s.plan_slug, s.tier as sub_tier, s.status, s.trial_end, s.current_period_end
        FROM users u
        LEFT JOIN subscriptions s ON s.user_id = u.id AND s.status IN ('active', 'trialing')
        WHERE s.id IS NOT NULL
        ORDER BY u.id
    ");
    
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Usuários com assinaturas ativas:\n";
    echo str_repeat("-", 100) . "\n";
    
    foreach ($users as $user) {
        echo "User ID: {$user['id']}\n";
        echo "  Email: {$user['email']}\n";
        echo "  Tier atual (users): {$user['user_tier']}\n";
        echo "  Tier da assinatura: {$user['sub_tier']}\n";
        echo "  Status: {$user['status']}\n";
        echo "  Plano: {$user['plan_slug']}\n";
        
        // Se tier diferente, sincronizar
        if ($user['user_tier'] !== $user['sub_tier']) {
            echo "  >>> DESINCRONIZADO! Atualizando...\n";
            
            $expiresAt = $user['status'] === 'trialing' && $user['trial_end'] 
                ? $user['trial_end'] 
                : $user['current_period_end'];
            
            $updateStmt = $pdo->prepare("
                UPDATE users 
                SET tier = ?, subscription_expires_at = ?
                WHERE id = ?
            ");
            $updateStmt->execute([$user['sub_tier'], $expiresAt, $user['id']]);
            
            echo "  >>> Atualizado para tier: {$user['sub_tier']}\n";
        } else {
            echo "  OK (sincronizado)\n";
        }
        
        echo str_repeat("-", 100) . "\n";
    }
    
    echo "\nConcluído!\n";
    
} catch (PDOException $e) {
    die("Erro: " . $e->getMessage() . "\n");
}
