<?php
$envPath = __DIR__ . '/../.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0 || strpos($line, '=') === false) continue;
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
    }
}

$dsn = 'pgsql:host=' . ($_ENV['QUOTES_DB_HOST'] ?? '147.93.35.184') . ';port=' . ($_ENV['QUOTES_DB_PORT'] ?? '5432') . ';dbname=' . ($_ENV['QUOTES_DB_DATABASE'] ?? 'operebem_quotes');
$pdo = new PDO($dsn, $_ENV['QUOTES_DB_USERNAME'] ?? '', $_ENV['QUOTES_DB_PASSWORD'] ?? '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);

// Buscar Brent (8833)
$r1 = $pdo->query("SELECT id_api, code, nome, apelido, last, pcp, timestamp, origem, grupo, ativo FROM dicionario WHERE id_api = '8833' OR nome ILIKE '%brent%' LIMIT 5")->fetchAll();
echo "=== BRENT (8833) ===\n";
foreach ($r1 as $r) {
    echo json_encode($r, JSON_UNESCAPED_UNICODE) . "\n";
}

// Buscar FEF
$r2 = $pdo->query("SELECT id_api, code, nome, apelido, last, pcp, timestamp, origem, grupo, ativo FROM dicionario WHERE code LIKE 'FEF%' OR nome ILIKE '%iron%' OR nome ILIKE '%ferro%' LIMIT 5")->fetchAll();
echo "\n=== FEF / MINERIO DE FERRO ===\n";
foreach ($r2 as $r) {
    echo json_encode($r, JSON_UNESCAPED_UNICODE) . "\n";
}

// Buscar Stoxx
$r3 = $pdo->query("SELECT id_api, code, nome, apelido, last, pcp, timestamp, origem, grupo, ativo FROM dicionario WHERE nome ILIKE '%stoxx%' OR apelido ILIKE '%stoxx%' LIMIT 5")->fetchAll();
echo "\n=== EURO STOXX 50 ===\n";
foreach ($r3 as $r) {
    echo json_encode($r, JSON_UNESCAPED_UNICODE) . "\n";
}

// Verificar se tem algum problema com timestamp
echo "\n=== TIMESTAMP ATUAL (servidor) ===\n";
echo "NOW: " . time() . " (" . date('Y-m-d H:i:s') . ")\n";
