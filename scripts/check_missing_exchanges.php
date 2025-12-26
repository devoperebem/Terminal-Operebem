<?php
/**
 * Script para verificar quais ativos ainda estão com "Sem dados" 
 * porque suas exchanges não estão na tabela clock
 */

$pdo = new PDO('pgsql:host=147.93.35.184;port=5432;dbname=operebem_quotes', 'quotes_manager', 'ADM_58pIcMEPwKvL53Vq');

// Buscar todas as exchanges que existem na tabela clock
$exchangesExistentes = [];
$r = $pdo->query('SELECT exchange_code FROM clock')->fetchAll(PDO::FETCH_ASSOC);
foreach ($r as $row) {
    $exchangesExistentes[] = $row['exchange_code'];
}

echo "=== EXCHANGES EXISTENTES NA TABELA CLOCK ===\n";
echo implode(', ', $exchangesExistentes) . "\n\n";

// Buscar todos os ativos ativos
$ativos = $pdo->query("SELECT id_api, code, nome, apelido, grupo, bolsa, icone_bandeira FROM dicionario WHERE ativo = 'S' ORDER BY grupo, nome")->fetchAll(PDO::FETCH_ASSOC);

echo "Total de ativos ativos: " . count($ativos) . "\n\n";

// Função que simula a lógica do boot.js para determinar a exchange
function getExchangeForItem($item) {
    // Primeiro, tenta determinar pela bolsa
    $bolsa = strtolower($item['bolsa'] ?? '');
    if ($bolsa) {
        if (strpos($bolsa, 'b3') !== false || strpos($bolsa, 'sao paulo') !== false || strpos($bolsa, 'são paulo') !== false) return 'XBSP';
        if (strpos($bolsa, 'nyse') !== false || strpos($bolsa, 'xnys') !== false) return 'XNYS';
        if (strpos($bolsa, 'nasdaq') !== false) return 'XNAS';
        if (strpos($bolsa, 'london') !== false || strpos($bolsa, 'lse') !== false) return 'XLON';
        if (strpos($bolsa, 'xetra') !== false || strpos($bolsa, 'frankfurt') !== false || strpos($bolsa, 'fwb') !== false) return 'XETR';
        if (strpos($bolsa, 'paris') !== false || strpos($bolsa, 'euronext paris') !== false) return 'XPAR';
        if (strpos($bolsa, 'tokyo') !== false || strpos($bolsa, 'jpx') !== false) return 'XTKS';
        if (strpos($bolsa, 'hong kong') !== false || strpos($bolsa, 'hkex') !== false) return 'XHKG';
        if (strpos($bolsa, 'shanghai') !== false || strpos($bolsa, 'sse') !== false) return 'XSHG';
        if (strpos($bolsa, 'shenzhen') !== false || strpos($bolsa, 'szse') !== false) return 'XSHE';
        if (strpos($bolsa, 'toronto') !== false || strpos($bolsa, 'tsx') !== false) return 'XTSE';
        if (strpos($bolsa, 'australian') !== false || strpos($bolsa, 'asx') !== false || strpos($bolsa, 'sydney') !== false) return 'XASX';
        if (strpos($bolsa, 'madrid') !== false || strpos($bolsa, 'bme') !== false) return 'XMAD';
        if (strpos($bolsa, 'zurich') !== false || strpos($bolsa, 'six') !== false) return 'XSWX';
        if (strpos($bolsa, 'singapore') !== false || strpos($bolsa, 'sgx') !== false) return 'XSES';
        if (strpos($bolsa, 'mexico') !== false || strpos($bolsa, 'bmv') !== false) return 'XMEX';
        if (strpos($bolsa, 'johannesburg') !== false || strpos($bolsa, 'jse') !== false) return 'XJSE';
        if (strpos($bolsa, 'milan') !== false || strpos($bolsa, 'borsa italiana') !== false || strpos($bolsa, 'mib') !== false) return 'XMIL';
        if (strpos($bolsa, 'xcbt') !== false || strpos($bolsa, 'cbot') !== false) return 'XCBT';
        if (strpos($bolsa, 'ice') !== false) return 'IFUS';
        if (strpos($bolsa, 'cme') !== false || strpos($bolsa, 'chicago') !== false) return 'XCME';
        if (strpos($bolsa, 'nymex') !== false) return 'XNYM';
        if (strpos($bolsa, 'comex') !== false) return 'XCEC';
    }
    
    // Se não encontrou pela bolsa, tenta pelo grupo
    $grupo = strtolower($item['grupo'] ?? '');
    if (strpos($grupo, 'cripto') !== false) return 'CRYPTO';
    if (strpos($grupo, 'indice_brasileiro') !== false) return 'XBSP';
    if (strpos($grupo, 'indice_usa') !== false || strpos($grupo, 'big_tech') !== false || strpos($grupo, 'adrs') !== false) return 'XNYS';
    if (strpos($grupo, 'indice_europeu') !== false) return 'XETR';
    if (strpos($grupo, 'indice_asia') !== false) return 'XTKS';
    if (strpos($grupo, 'futuros_ouro') !== false) return 'XCME';
    if (strpos($grupo, 'energia') !== false) return 'XNYM';
    if (strpos($grupo, 'metais') !== false) return 'XCEC';
    if (strpos($grupo, 'agricola') !== false) return 'XCBT';
    
    // Tenta pela bandeira
    $flag = strtolower($item['icone_bandeira'] ?? '');
    if (strpos($flag, 'fi-br') !== false) return 'XBSP';
    if (strpos($flag, 'fi-us') !== false || strpos($flag, 'fi-um') !== false) return 'XNYS';
    if (strpos($flag, 'fi-de') !== false) return 'XETR';
    if (strpos($flag, 'fi-gb') !== false) return 'XLON';
    if (strpos($flag, 'fi-fr') !== false) return 'XPAR';
    if (strpos($flag, 'fi-jp') !== false) return 'XTKS';
    if (strpos($flag, 'fi-hk') !== false || strpos($flag, 'fi-cn') !== false) return 'XHKG';
    
    return 'UNKNOWN';
}

// Verificar cada ativo
$exchangesFaltando = [];
$ativosSemDados = [];

foreach ($ativos as $ativo) {
    $exchange = getExchangeForItem($ativo);
    
    if (!in_array($exchange, $exchangesExistentes)) {
        if (!isset($exchangesFaltando[$exchange])) {
            $exchangesFaltando[$exchange] = [];
        }
        $exchangesFaltando[$exchange][] = [
            'id_api' => $ativo['id_api'],
            'code' => $ativo['code'],
            'nome' => $ativo['nome'],
            'apelido' => $ativo['apelido'],
            'grupo' => $ativo['grupo'],
            'bolsa' => $ativo['bolsa']
        ];
    }
}

echo "=== EXCHANGES FALTANDO E SEUS ATIVOS ===\n\n";

if (empty($exchangesFaltando)) {
    echo "Todas as exchanges estão cadastradas!\n";
} else {
    foreach ($exchangesFaltando as $exchange => $ativos) {
        echo "Exchange: $exchange (" . count($ativos) . " ativos)\n";
        echo str_repeat('-', 80) . "\n";
        foreach ($ativos as $a) {
            echo "  - [{$a['id_api']}] {$a['apelido']} | Grupo: {$a['grupo']} | Bolsa: {$a['bolsa']}\n";
        }
        echo "\n";
    }
    
    echo "\n=== EXCHANGES QUE PRECISAM SER ADICIONADAS ===\n";
    echo implode(', ', array_keys($exchangesFaltando)) . "\n";
}
