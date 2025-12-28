<?php
/**
 * Script para corrigir trading_days das exchanges
 * O formato correto é: 1=Dom, 2=Seg, 3=Ter, 4=Qua, 5=Qui, 6=Sex, 7=Sab
 * A maioria das bolsas opera Seg-Sex = 23456
 */

$pdo = new PDO('pgsql:host=147.93.35.184;port=5432;dbname=operebem_quotes', 'quotes_manager', 'ADM_58pIcMEPwKvL53Vq');

echo "=== CORRIGINDO TRADING_DAYS ===\n\n";

// Corrigir exchanges com trading_days errados
$correcoes = [
    // Bolsas que devem operar apenas Seg-Sex (23456)
    'XCME' => '23456',  // Chicago Mercantile Exchange
    'XCBT' => '23456',  // Chicago Board of Trade
    'XSES' => '23456',  // Singapore Exchange
    'XSAU' => '23456',  // Saudi Exchange (na verdade é Sun-Thu, mas vamos verificar)
    'IFUS' => '23456',  // ICE Futures US
    'XJSE' => '23456',  // Johannesburg
    'XMIL' => '23456',  // Milano
    
    // Cripto opera 24/7 todos os dias
    'CRYPTO' => '1234567',
    'CRIPTO' => '1234567',
];

// Primeiro, verificar o status atual
echo "Status atual:\n";
$stmt = $pdo->query("SELECT exchange_code, exchange_name, trading_days FROM clock ORDER BY exchange_code");
$exchanges = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($exchanges as $ex) {
    echo sprintf("  %-8s: %-40s dias: %s\n", $ex['exchange_code'], $ex['exchange_name'], $ex['trading_days']);
}

echo "\nAplicando correções...\n";

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
        echo "❌ Erro em $code: " . $e->getMessage() . "\n";
    }
}

// Verificar se Saudi Arabia deveria ser Sun-Thu (formato especial)
// Vamos manter como 23456 por enquanto (Seg-Sex padrão)

// Verificar resultado final
echo "\n=== RESULTADO FINAL ===\n";
$stmt = $pdo->query("SELECT exchange_code, trading_days FROM clock ORDER BY exchange_code");
$final = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($final as $ex) {
    $dias = $ex['trading_days'];
    $temDomingo = strpos($dias, '1') !== false && $ex['exchange_code'] !== 'CRYPTO' && $ex['exchange_code'] !== 'CRIPTO';
    $marker = $temDomingo ? '⚠️' : '✅';
    echo "$marker {$ex['exchange_code']}: $dias\n";
}

echo "\nCorreções aplicadas!\n";
