<?php
// Script simples de diagnóstico
$envPath = __DIR__ . '/../.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
    }
}

$dsn = 'pgsql:host=' . ($_ENV['QUOTES_DB_HOST'] ?? '147.93.35.184') . ';port=' . ($_ENV['QUOTES_DB_PORT'] ?? '5432') . ';dbname=' . ($_ENV['QUOTES_DB_DATABASE'] ?? 'operebem_quotes');
$pdo = new PDO($dsn, $_ENV['QUOTES_DB_USERNAME'] ?? '', $_ENV['QUOTES_DB_PASSWORD'] ?? '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);

$sql = "SELECT id_api, code, nome, apelido, ativo, last, pcp, timestamp, origem, grupo FROM dicionario WHERE code IN ('8833','FEF1!','FEF2!','SX5E') OR id_api IN ('8833') OR nome ILIKE '%brent%' OR nome ILIKE '%stoxx%' OR nome ILIKE '%ferro%' OR apelido ILIKE '%brent%' ORDER BY code LIMIT 15";
$rows = $pdo->query($sql)->fetchAll();

echo "RESULTADO: " . count($rows) . " ativos encontrados\n\n";
foreach ($rows as $r) {
    echo "ID: " . $r['id_api'] . " | CODE: " . $r['code'] . " | ATIVO: " . $r['ativo'] . "\n";
    echo "  Nome: " . $r['nome'] . "\n";
    echo "  Apelido: " . $r['apelido'] . "\n";
    echo "  Last: " . $r['last'] . " | PCP: " . $r['pcp'] . " | Timestamp: " . $r['timestamp'] . "\n";
    echo "  Grupo: " . $r['grupo'] . " | Origem: " . $r['origem'] . "\n\n";
}
