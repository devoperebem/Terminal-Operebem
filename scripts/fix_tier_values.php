<?php
/**
 * Corrigir valores de tier - Remover constraint e atualizar
 */

echo "=== CORREÇÃO DE TIERS (v3) ===\n\n";

try {
    $pdo = new PDO(
        'pgsql:host=147.93.35.184;port=5432;dbname=terminal_database',
        'terminal_database_adm',
        '5n4NS>soCb/85n4NS>soCb/8'
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Conexão OK!\n\n";
} catch (Exception $e) {
    echo "❌ Erro de conexão: " . $e->getMessage() . "\n";
    exit(1);
}

// 1. Lista constraints na tabela users
echo "1. CONSTRAINTS EXISTENTES:\n";
$sql = "SELECT conname, pg_get_constraintdef(oid) as definition 
        FROM pg_constraint 
        WHERE conrelid = 'users'::regclass AND contype = 'c'";
$stmt = $pdo->query($sql);
$constraints = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (count($constraints) > 0) {
    foreach ($constraints as $c) {
        echo "   - {$c['conname']}: {$c['definition']}\n";
    }
} else {
    echo "   (nenhuma constraint CHECK)\n";
}

// 2. Remover constraint de tier se existir
echo "\n2. REMOVENDO CONSTRAINT DE TIER...\n";
foreach ($constraints as $c) {
    if (strpos($c['definition'], 'tier') !== false) {
        try {
            $pdo->exec("ALTER TABLE users DROP CONSTRAINT {$c['conname']}");
            echo "   ✅ Removida: {$c['conname']}\n";
        } catch (Exception $e) {
            echo "   ⚠️ Erro ao remover {$c['conname']}: " . $e->getMessage() . "\n";
        }
    }
}

// 3. Atualizar valores
echo "\n3. ATUALIZANDO VALORES:\n";

try {
    $count = $pdo->exec("UPDATE users SET tier = 'FREE' WHERE tier = 'free'");
    echo "   - 'free' -> 'FREE': {$count}\n";
} catch (Exception $e) {
    echo "   ⚠️ " . $e->getMessage() . "\n";
}

try {
    $count = $pdo->exec("UPDATE users SET tier = 'PLUS' WHERE tier = 'premium'");
    echo "   - 'premium' -> 'PLUS': {$count}\n";
} catch (Exception $e) {
    echo "   ⚠️ " . $e->getMessage() . "\n";
}

try {
    $count = $pdo->exec("UPDATE users SET tier = 'PRO' WHERE tier = 'pro'");
    echo "   - 'pro' -> 'PRO': {$count}\n";
} catch (Exception $e) {
    echo "   ⚠️ " . $e->getMessage() . "\n";
}

// 4. Adicionar nova constraint com valores corretos
echo "\n4. ADICIONANDO NOVA CONSTRAINT...\n";
try {
    $pdo->exec("ALTER TABLE users ADD CONSTRAINT users_tier_check CHECK (tier IN ('FREE', 'PLUS', 'PRO'))");
    echo "   ✅ Constraint criada: tier IN ('FREE', 'PLUS', 'PRO')\n";
} catch (Exception $e) {
    echo "   ⚠️ " . $e->getMessage() . "\n";
}

// 5. Resultado final
echo "\n5. SITUAÇÃO FINAL:\n";
$stmt = $pdo->query("SELECT tier, COUNT(*) as total FROM users GROUP BY tier ORDER BY tier");
$after = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($after as $row) {
    $tier = $row['tier'] ?? '(NULL)';
    echo "   - {$tier}: {$row['total']} usuários\n";
}

echo "\n=== CONCLUÍDO ===\n";
