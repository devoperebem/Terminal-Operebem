<?php
/**
 * Script de diagnóstico para verificar ativos no banco de dados
 * REMOVER APÓS USO!
 */

// Carregar .env (está na raiz do projeto, não em public/)
$envPath = __DIR__ . '/../.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
        putenv(trim($name) . '=' . trim($value));
    }
}

header('Content-Type: text/plain; charset=utf-8');

try {
    $dsn = sprintf(
        'pgsql:host=%s;port=%s;dbname=%s',
        $_ENV['QUOTES_DB_HOST'] ?? '147.93.35.184',
        $_ENV['QUOTES_DB_PORT'] ?? '5432',
        $_ENV['QUOTES_DB_DATABASE'] ?? 'operebem_quotes'
    );
    
    $pdo = new PDO($dsn, $_ENV['QUOTES_DB_USERNAME'] ?? '', $_ENV['QUOTES_DB_PASSWORD'] ?? '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "=== CONEXÃO OK ===\n\n";
    
    // Buscar ativos específicos que estão com problema
    $ativosProblema = ['8833', 'FEF1!', 'FEF2!', 'SX5E', 'STOXX50', 'EU50'];
    
    echo "=== BUSCANDO ATIVOS ESPECÍFICOS ===\n";
    echo "Códigos buscados: " . implode(', ', $ativosProblema) . "\n\n";
    
    // Query flexível
    $sql = "SELECT id_api, code, nome, apelido, ativo, last, pcp, timestamp, status_mercado, origem, grupo
            FROM dicionario 
            WHERE code IN ('" . implode("','", $ativosProblema) . "')
               OR id_api IN ('" . implode("','", $ativosProblema) . "')
               OR nome ILIKE '%brent%'
               OR nome ILIKE '%stoxx%'
               OR nome ILIKE '%ferro%'
               OR apelido ILIKE '%brent%'
               OR apelido ILIKE '%stoxx%'
               OR apelido ILIKE '%ferro%'
            ORDER BY code";
    
    $stmt = $pdo->query($sql);
    $results = $stmt->fetchAll();
    
    echo "Encontrados: " . count($results) . " registros\n\n";
    
    if (count($results) === 0) {
        echo "NENHUM ATIVO ENCONTRADO! Os ativos podem não existir no banco.\n\n";
        
        // Buscar por padrão mais amplo
        echo "=== BUSCANDO PADRÕES AMPLOS ===\n";
        $sql2 = "SELECT id_api, code, nome, apelido, ativo, grupo 
                 FROM dicionario 
                 WHERE grupo ILIKE '%energia%' OR grupo ILIKE '%petroleo%'
                 LIMIT 20";
        $stmt2 = $pdo->query($sql2);
        $results2 = $stmt2->fetchAll();
        echo "Ativos do grupo energia/petroleo: " . count($results2) . "\n";
        foreach ($results2 as $r) {
            echo "  - [{$r['id_api']}] {$r['code']} | {$r['apelido']} | Ativo: {$r['ativo']} | Grupo: {$r['grupo']}\n";
        }
    } else {
        foreach ($results as $row) {
            echo "-------------------------------------------\n";
            echo "ID_API:   " . ($row['id_api'] ?? 'NULL') . "\n";
            echo "Code:     " . ($row['code'] ?? 'NULL') . "\n";
            echo "Nome:     " . ($row['nome'] ?? 'NULL') . "\n";
            echo "Apelido:  " . ($row['apelido'] ?? 'NULL') . "\n";
            echo "ATIVO:    " . ($row['ativo'] ?? 'NULL') . " <-- SE FOR 'N', NÃO APARECE!\n";
            echo "Last:     " . ($row['last'] ?? 'NULL') . "\n";
            echo "PCP:      " . ($row['pcp'] ?? 'NULL') . "\n";
            echo "Timestamp:" . ($row['timestamp'] ?? 'NULL') . "\n";
            echo "Status:   " . ($row['status_mercado'] ?? 'NULL') . "\n";
            echo "Origem:   " . ($row['origem'] ?? 'NULL') . "\n";
            echo "Grupo:    " . ($row['grupo'] ?? 'NULL') . "\n";
        }
    }
    
    // Verificar todos os ativos com ativo='N' para ver o que está desativado
    echo "\n\n=== ATIVOS DESATIVADOS (ativo='N') QUE PODEM SER OS PROCURADOS ===\n";
    $sql3 = "SELECT id_api, code, nome, apelido, grupo 
             FROM dicionario 
             WHERE ativo = 'N' 
             AND (nome ILIKE '%brent%' OR nome ILIKE '%stoxx%' OR nome ILIKE '%euro%' 
                  OR apelido ILIKE '%brent%' OR apelido ILIKE '%stoxx%' OR apelido ILIKE '%euro%')
             LIMIT 20";
    $stmt3 = $pdo->query($sql3);
    $results3 = $stmt3->fetchAll();
    echo "Encontrados desativados: " . count($results3) . "\n";
    foreach ($results3 as $r) {
        echo "  - [{$r['id_api']}] {$r['code']} | {$r['apelido']} | Grupo: {$r['grupo']}\n";
    }
    
    // Contar totais
    echo "\n\n=== ESTATÍSTICAS GERAIS ===\n";
    $stmt4 = $pdo->query("SELECT ativo, COUNT(*) as total FROM dicionario GROUP BY ativo");
    $stats = $stmt4->fetchAll();
    foreach ($stats as $s) {
        echo "Ativo='{$s['ativo']}': {$s['total']} registros\n";
    }
    
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
