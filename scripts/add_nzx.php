<?php
/**
 * Adicionar NZX (New Zealand Exchange) à tabela clock
 */

$pdo = new PDO('pgsql:host=147.93.35.184;port=5432;dbname=operebem_quotes', 'quotes_manager', 'ADM_58pIcMEPwKvL53Vq');

echo "=== ADICIONANDO NZX (New Zealand Exchange) ===\n\n";

// Verificar se já existe
$check = $pdo->query("SELECT exchange_code FROM clock WHERE exchange_code = 'XNZX'")->fetch();
if ($check) {
    echo "⚠️ XNZX já existe na tabela clock!\n";
    exit(0);
}

// Inserir NZX
$stmt = $pdo->prepare("INSERT INTO clock (exchange_code, exchange_name, timezone_name, timezone_utc, open_time, close_time, trading_days, current_status, source) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

$stmt->execute([
    'XNZX',                    // exchange_code
    'New Zealand Exchange',    // exchange_name
    'Pacific/Auckland',        // timezone_name
    '+13',                     // timezone_utc (horário de verão NZ)
    '10:00:00',               // open_time (hora local)
    '16:45:00',               // close_time (hora local)
    '23456',                  // trading_days (Seg-Sex)
    'closed',                 // current_status
    'manual'                  // source
]);

echo "✅ XNZX (New Zealand Exchange) adicionada com sucesso!\n\n";

// Verificar resultado
$result = $pdo->query("SELECT * FROM clock WHERE exchange_code = 'XNZX'")->fetch(PDO::FETCH_ASSOC);
echo "Dados inseridos:\n";
foreach ($result as $key => $value) {
    echo "  - $key: $value\n";
}

echo "\n✅ NZX agora aparecerá corretamente no relógio de mercados globais!\n";
