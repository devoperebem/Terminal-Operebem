<?php
// Ver exchanges existentes
$pdo = new PDO('pgsql:host=147.93.35.184;port=5432;dbname=operebem_quotes', 'quotes_manager', 'ADM_58pIcMEPwKvL53Vq');
$r = $pdo->query('SELECT exchange_code, exchange_name, open_time, close_time, pre_open_time, after_hours_start, after_hours_end, trading_days, timezone_name FROM clock ORDER BY exchange_code')->fetchAll(PDO::FETCH_ASSOC);

$output = "EXCHANGE | TRADING_DAYS | OPEN-CLOSE | PRE | AFTER | TIMEZONE\n";
$output .= str_repeat('-', 120) . "\n";
foreach($r as $row) {
    $output .= sprintf("%-8s | %-15s | %s-%s | %s | %s-%s | %s\n", 
        $row['exchange_code'],
        $row['trading_days'] ?? 'NULL',
        $row['open_time'] ?? '?',
        $row['close_time'] ?? '?',
        $row['pre_open_time'] ?? '-',
        $row['after_hours_start'] ?? '-',
        $row['after_hours_end'] ?? '-',
        $row['timezone_name'] ?? '?'
    );
}

file_put_contents(__DIR__ . '/exchanges_result.txt', $output);
echo "Resultado salvo em exchanges_result.txt\n";
echo $output;
