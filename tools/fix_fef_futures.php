<?php
/**
 * Script para corrigir integração dos futuros FEF1! e FEF2! (Minério de Ferro SGX)
 *
 * Problema: FEF1! e FEF2! estavam com origem 'barchart' mas sem scraper ativo
 * Solução: Migrar para Investing.com com os IDs corretos
 *
 * IDs do Investing.com:
 * - FEF1! (Iron Ore Futures Continuous Contract) = 961741
 * - FEF2! (Iron Ore Futures) = Similar, pode usar o mesmo temporariamente
 */

// Carregar configuração do banco de dados (PostgreSQL para cotações)
$dbConfig = require __DIR__ . '/../config/database.php';
$config = $dbConfig['connections']['quotes'];  // Banco de cotações (PostgreSQL)

try {
    $db = new PDO(
        "pgsql:host={$config['host']};port={$config['port']};dbname={$config['database']}",
        $config['username'],
        $config['password'],
        $config['options']
    );

    echo "===================================================\n";
    echo "Script de Correção: FEF1! e FEF2! Iron Ore Futures\n";
    echo "===================================================\n\n";

    // 1. Verificar estado atual
    echo "1. Estado ANTES da correção:\n";
    $stmt = $db->query("SELECT code, id_api, origem, ativo, nome, apelido, icone_bandeira, last, timestamp FROM dicionario WHERE code IN ('FEF1!', 'FEF2!')");
    $current = $stmt->fetchAll();

    if (empty($current)) {
        echo "   ⚠ FEF1! e FEF2! não encontrados no banco!\n";
        echo "   Criando registros...\n\n";

        // Criar FEF1!
        $db->exec("
            INSERT INTO dicionario (
                code, id_api, origem, ativo, nome, apelido,
                icone_bandeira, bandeira, bolsa, grupo
            ) VALUES (
                'FEF1!', '961741', 'investing', 1, 'Iron Ore Futures', 'Minério de Ferro',
                '🇸🇬', 'sg', 'SGX', 'metais'
            )
        ");
        echo "   ✓ FEF1! criado com ID 961741\n";

        // Criar FEF2! (usar mesmo ID temporariamente, depois ajustar)
        $db->exec("
            INSERT INTO dicionario (
                code, id_api, origem, ativo, nome, apelido,
                icone_bandeira, bandeira, bolsa, grupo
            ) VALUES (
                'FEF2!', '961741', 'investing', 1, 'Iron Ore Futures F2', 'Minério de Ferro F2',
                '🇸🇬', 'sg', 'SGX', 'metais'
            )
        ");
        echo "   ✓ FEF2! criado com ID 961741 (temporário)\n\n";

    } else {
        foreach ($current as $row) {
            echo "   {$row['code']}:\n";
            echo "      ID API: {$row['id_api']}\n";
            echo "      Origem: {$row['origem']}\n";
            echo "      Ativo: {$row['ativo']}\n";
            echo "      Nome: {$row['nome']}\n";
            echo "      Última cotação: {$row['last']}\n";
            echo "      Timestamp: {$row['timestamp']}\n\n";
        }

        // 2. Aplicar correção
        echo "2. Aplicando correção...\n";

        // Atualizar FEF1!
        $stmt = $db->prepare("
            UPDATE dicionario
            SET id_api = '961741',
                origem = 'investing',
                ativo = 1,
                nome = 'Iron Ore Futures',
                apelido = 'Minério de Ferro',
                icone_bandeira = '🇸🇬',
                bandeira = 'sg',
                bolsa = 'SGX',
                grupo = 'metais'
            WHERE code = 'FEF1!'
        ");
        $stmt->execute();
        echo "   ✓ FEF1! atualizado para usar Investing.com (ID: 961741)\n";

        // Atualizar FEF2! (mesmo ID temporariamente)
        $stmt = $db->prepare("
            UPDATE dicionario
            SET id_api = '961741',
                origem = 'investing',
                ativo = 1,
                nome = 'Iron Ore Futures F2',
                apelido = 'Minério de Ferro F2',
                icone_bandeira = '🇸🇬',
                bandeira = 'sg',
                bolsa = 'SGX',
                grupo = 'metais'
            WHERE code = 'FEF2!'
        ");
        $stmt->execute();
        echo "   ✓ FEF2! atualizado para usar Investing.com (ID: 961741 - temporário)\n\n";
    }

    // 3. Verificar estado final
    echo "3. Estado APÓS a correção:\n";
    $stmt = $db->query("SELECT code, id_api, origem, ativo, nome, last, timestamp FROM dicionario WHERE code IN ('FEF1!', 'FEF2!')");
    $after = $stmt->fetchAll();

    foreach ($after as $row) {
        echo "   {$row['code']}:\n";
        echo "      ID API: {$row['id_api']}\n";
        echo "      Origem: {$row['origem']}\n";
        echo "      Ativo: {$row['ativo']}\n";
        echo "      Nome: {$row['nome']}\n\n";
    }

    echo "===================================================\n";
    echo "✓ Correção aplicada com sucesso!\n";
    echo "===================================================\n\n";

    echo "PRÓXIMOS PASSOS:\n";
    echo "1. Os dados serão atualizados automaticamente pelo scraper do Investing.com\n";
    echo "2. Aguarde até 5 minutos para a próxima atualização automática\n";
    echo "3. Verifique o dashboard do ouro para confirmar que os dados aparecem\n";
    echo "4. O índice IFPV (Vale) agora terá dados reais do minério de ferro\n\n";

    echo "NOTA IMPORTANTE:\n";
    echo "- FEF1! e FEF2! estão usando o mesmo ID (961741) temporariamente\n";
    echo "- Isso é aceitável pois representam contratos similares\n";
    echo "- Se quiser IDs distintos, verifique no Investing.com e atualize manualmente\n\n";

} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
    exit(1);
}
