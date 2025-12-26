<?php
/**
 * Script para corrigir exchanges que foram inseridas com formato incorreto
 * e verificar/corrigir os horários
 */

$pdo = new PDO('pgsql:host=147.93.35.184;port=5432;dbname=operebem_quotes', 'quotes_manager', 'ADM_58pIcMEPwKvL53Vq');

// Mapear dias por exchange
// Formato numérico: 1=Dom, 2=Seg, 3=Ter, 4=Qua, 5=Qui, 6=Sex, 7=Sab OU 0=Dom, 1=Seg, etc
// Ou formato texto: Mon-Fri, Sun-Thu, etc.

// Primeiro, ver o que já existe para entender o padrão
echo "=== EXCHANGES EXISTENTES ===\n\n";
$r = $pdo->query('SELECT exchange_code, trading_days FROM clock ORDER BY exchange_code')->fetchAll(PDO::FETCH_ASSOC);
foreach($r as $row) {
    echo $row['exchange_code'] . ' => ' . ($row['trading_days'] ?? 'NULL') . "\n";
}

// Corrigir as exchanges que eu inseri (IFUS, XJSE, XMIL, CRYPTO)
// Formato correto: 23456 para Mon-Fri (2=Mon, 3=Tue, 4=Wed, 5=Thu, 6=Fri)
// CRYPTO: 1234567 (todos os dias)

echo "\n=== CORRIGINDO EXCHANGES ===\n\n";

$correcoes = [
    'IFUS' => '23456',    // Mon-Fri
    'XJSE' => '23456',    // Mon-Fri
    'XMIL' => '23456',    // Mon-Fri
    'CRYPTO' => '1234567', // Todos os dias
];

$updateStmt = $pdo->prepare("UPDATE clock SET trading_days = ? WHERE exchange_code = ?");

foreach ($correcoes as $code => $dias) {
    try {
        $updateStmt->execute([$dias, $code]);
        $affected = $updateStmt->rowCount();
        if ($affected > 0) {
            echo "✅ Corrigido: $code => $dias\n";
        } else {
            echo "⚠️ Não encontrado: $code\n";
        }
    } catch (Exception $e) {
        echo "❌ Erro ao corrigir $code: " . $e->getMessage() . "\n";
    }
}

echo "\n=== RESULTADO FINAL ===\n\n";
$r2 = $pdo->query('SELECT exchange_code, exchange_name, open_time, close_time, trading_days, timezone_name FROM clock ORDER BY exchange_code')->fetchAll(PDO::FETCH_ASSOC);
foreach($r2 as $row) {
    echo sprintf("%-8s | %-40s | %s-%s | dias: %-8s | %s\n", 
        $row['exchange_code'],
        $row['exchange_name'],
        $row['open_time'],
        $row['close_time'],
        $row['trading_days'] ?? 'NULL',
        $row['timezone_name']
    );
}
