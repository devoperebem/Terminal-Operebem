<?php
/**
 * Script de diagnóstico de tier
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
    
    echo "=== DIAGNÓSTICO DE TIER ===\n\n";
    
    // Buscar usuário 6
    $stmt = $pdo->prepare("SELECT id, email, tier, subscription_expires_at FROM users WHERE id = ?");
    $stmt->execute([6]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "USUÁRIO:\n";
    print_r($user);
    
    // Buscar assinatura
    $stmt2 = $pdo->prepare("SELECT * FROM subscriptions WHERE user_id = ? ORDER BY id DESC LIMIT 1");
    $stmt2->execute([6]);
    $sub = $stmt2->fetch(PDO::FETCH_ASSOC);
    
    echo "\nASSINATURA:\n";
    print_r($sub);
    
    // Verificar lógica de tier efetivo
    $tier = strtoupper($user['tier'] ?? 'FREE');
    $expiresAt = $user['subscription_expires_at'] ?? null;
    
    echo "\n=== ANÁLISE ===\n";
    echo "Tier do usuário: $tier\n";
    echo "Expira em: " . ($expiresAt ?? 'NULL') . "\n";
    echo "Tempo atual: " . date('Y-m-d H:i:s') . "\n";
    
    if ($tier !== 'FREE' && $expiresAt) {
        $expTime = strtotime($expiresAt);
        echo "Timestamp expiração: $expTime\n";
        echo "Timestamp atual: " . time() . "\n";
        echo "Expirou? " . ($expTime < time() ? 'SIM' : 'NÃO') . "\n";
    }
    
    echo "\nTier efetivo calculado: ";
    
    if ($tier === 'FREE') {
        echo "FREE (tier é FREE)\n";
    } elseif ($expiresAt === null || $expiresAt === '') {
        echo "$tier (sem expiração)\n";
    } elseif (strtotime($expiresAt) < time()) {
        echo "FREE (expirou)\n";
    } else {
        echo "$tier\n";
    }
    
} catch (PDOException $e) {
    die("Erro: " . $e->getMessage() . "\n");
}
