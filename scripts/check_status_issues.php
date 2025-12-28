<?php
/**
 * Script para verificar ativos que estão mostrando status incorreto
 */

$pdo = new PDO('pgsql:host=147.93.35.184;port=5432;dbname=operebem_quotes', 'quotes_manager', 'ADM_58pIcMEPwKvL53Vq');

// Buscar exchanges existentes
$exchanges = [];
$r = $pdo->query('SELECT exchange_code, exchange_name, trading_days, open_time, close_time FROM clock')->fetchAll(PDO::FETCH_ASSOC);
foreach ($r as $row) {
    $exchanges[$row['exchange_code']] = $row;
}

echo "=== EXCHANGES NA TABELA CLOCK ===\n";
foreach ($exchanges as $code => $data) {
    echo "$code: dias={$data['trading_days']} ({$data['open_time']}-{$data['close_time']})\n";
}

// Buscar ativos problemáticos
$problematicos = [
    'Petróleo WTI', 'Gás Natural', 'Gasolina RBOB', 'Ouro Futuro', 'Cobre', 'Prata',
    'S&P 500 VIX', 'Vix Volatility', 'Nasdaq 100 VIX', 'Gold Volatility', 'Crude Oil Volatility'
];

echo "\n=== ATIVOS PROBLEMÁTICOS ===\n";
foreach ($problematicos as $nome) {
    $stmt = $pdo->prepare("SELECT id_api, code, nome, apelido, bolsa, grupo FROM dicionario WHERE (nome ILIKE ? OR apelido ILIKE ?) AND ativo = 'S' LIMIT 1");
    $stmt->execute(["%$nome%", "%$nome%"]);
    $ativo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($ativo) {
        $bolsa = strtoupper(trim($ativo['bolsa'] ?? ''));
        $grupo = $ativo['grupo'] ?? '';
        
        // Verificar se bolsa existe na tabela clock
        $existeNaClock = isset($exchanges[$bolsa]);
        
        echo sprintf("%-25s | Bolsa: %-6s | Grupo: %-20s | Na clock: %s\n",
            $ativo['apelido'] ?? $ativo['nome'],
            $bolsa ?: 'NULL',
            $grupo,
            $existeNaClock ? 'SIM' : 'NÃO'
        );
    } else {
        echo "$nome: NÃO ENCONTRADO\n";
    }
}

// Verificar todos os ativos que têm bolsa definida mas que NÃO está na tabela clock
echo "\n=== BOLSAS USADAS POR ATIVOS MAS NÃO CADASTRADAS NA CLOCK ===\n";
$stmt = $pdo->query("SELECT DISTINCT UPPER(TRIM(bolsa)) as bolsa, COUNT(*) as total FROM dicionario WHERE bolsa IS NOT NULL AND bolsa != '' AND ativo = 'S' GROUP BY UPPER(TRIM(bolsa)) ORDER BY total DESC");
$bolsasUsadas = $stmt->fetchAll(PDO::FETCH_ASSOC);

$faltando = [];
foreach ($bolsasUsadas as $row) {
    $bolsa = $row['bolsa'];
    if (!isset($exchanges[$bolsa])) {
        $faltando[$bolsa] = $row['total'];
        echo "$bolsa: {$row['total']} ativos\n";
    }
}

// Verificar trading_days das exchanges que existem
echo "\n=== VERIFICANDO TRADING_DAYS (hoje é " . date('l') . ") ===\n";
$dayNum = date('w') + 1; // 1=Dom, 2=Seg, etc
echo "Dia numérico de hoje: $dayNum (Domingo=1)\n";

foreach ($exchanges as $code => $data) {
    $dias = $data['trading_days'] ?? '';
    
    // Verificar se hoje está incluído
    $hojeIncluido = false;
    if (preg_match('/^[1-7]+$/', $dias)) {
        $hojeIncluido = strpos($dias, (string)$dayNum) !== false;
    }
    
    if ($hojeIncluido && $code !== 'CRYPTO' && $code !== 'CRIPTO') {
        echo "⚠️ $code tem hoje (domingo) nos trading_days: $dias\n";
    }
}

// Salvar resultado
$resultado = [
    'exchanges_cadastradas' => array_keys($exchanges),
    'bolsas_faltando' => array_keys($faltando),
    'dia_hoje' => date('l') . ' (' . $dayNum . ')'
];
file_put_contents(__DIR__ . '/../public/status_check.json', json_encode($resultado, JSON_PRETTY_PRINT));
echo "\nResultado salvo em public/status_check.json\n";
