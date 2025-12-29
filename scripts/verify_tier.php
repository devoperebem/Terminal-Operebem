<?php
/**
 * Verificar estrutura do tier no banco terminal_database
 */

echo "=== VERIFICANDO TIER NO BANCO ===\n\n";

try {
    $pdo = new PDO(
        'pgsql:host=147.93.35.184;port=5432;dbname=terminal_database',
        'terminal_database_adm',
        '5n4NS>soCb/85n4NS>soCb/8'
    );
    echo "✅ Conexão OK!\n\n";
} catch (Exception $e) {
    echo "❌ Erro de conexão: " . $e->getMessage() . "\n";
    exit(1);
}

// 1. Verificar se coluna tier existe
echo "1. ESTRUTURA DA COLUNA TIER:\n";
$sql = "SELECT column_name, data_type, column_default, is_nullable 
        FROM information_schema.columns 
        WHERE table_name = 'users' AND column_name = 'tier'";
$stmt = $pdo->query($sql);
$col = $stmt->fetch(PDO::FETCH_ASSOC);

if ($col) {
    echo "   - column_name: " . ($col['column_name'] ?? 'N/A') . "\n";
    echo "   - data_type: " . ($col['data_type'] ?? 'N/A') . "\n";
    echo "   - column_default: " . ($col['column_default'] ?? 'NULL') . "\n";
    echo "   - is_nullable: " . ($col['is_nullable'] ?? 'N/A') . "\n";
} else {
    echo "   ❌ Coluna 'tier' NÃO ENCONTRADA!\n";
}

// 2. Contar usuários por tier
echo "\n2. CONTAGEM POR TIER:\n";
$stmt = $pdo->query("SELECT tier, COUNT(*) as total FROM users GROUP BY tier ORDER BY tier");
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($results) > 0) {
    foreach ($results as $row) {
        $tier = $row['tier'] ?? '(NULL)';
        echo "   - {$tier}: {$row['total']} usuários\n";
    }
} else {
    echo "   (nenhum resultado)\n";
}

// 3. Mostrar primeiros 5 usuários com tier
echo "\n3. AMOSTRA (5 primeiros usuários):\n";
$stmt = $pdo->query("SELECT id, name, email, tier FROM users ORDER BY id LIMIT 5");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($users as $u) {
    $tier = $u['tier'] ?? '(NULL)';
    echo "   ID {$u['id']}: {$u['name']} ({$u['email']}) - Tier: {$tier}\n";
}

// 4. Verificar se subscription_expires_at existe
echo "\n4. COLUNA SUBSCRIPTION_EXPIRES_AT:\n";
$sql = "SELECT column_name, data_type 
        FROM information_schema.columns 
        WHERE table_name = 'users' AND column_name = 'subscription_expires_at'";
$stmt = $pdo->query($sql);
$col = $stmt->fetch(PDO::FETCH_ASSOC);

if ($col) {
    echo "   ✅ Existe ({$col['data_type']})\n";
} else {
    echo "   ❌ NÃO existe\n";
}

echo "\n=== FIM DA VERIFICAÇÃO ===\n";
