<?php
require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Verificar se as chaves são válidas
if (strpos($_ENV['STRIPE_SECRET_KEY'], 'sk_test_') !== 0 || strpos($_ENV['STRIPE_SECRET_KEY'], '...') !== false) {
    die("ERRO: STRIPE_SECRET_KEY inválida no .env. Atualize com a chave real.\n");
}

\Stripe\Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);

echo "Iniciando limpeza de produtos duplicados...\n\n";

try {
    // Buscar todos os produtos ativos
    $products = \Stripe\Product::all(['limit' => 100, 'active' => true]);
    $grouped = [];

    // Agrupar por nome
    foreach ($products->data as $product) {
        $grouped[$product->name][] = $product;
    }

    foreach ($grouped as $name => $list) {
        if (count($list) > 1) {
            echo "Encontrados " . count($list) . " produtos com nome '{$name}':\n";
            
            // Ordenar por data de criação (mais recente primeiro)
            usort($list, function($a, $b) {
                return $b->created - $a->created;
            });

            // Manter o primeiro (mais recente) e arquivar os outros
            $keep = array_shift($list);
            echo "  [MANTER] ID: {$keep->id} (Criado em " . date('Y-m-d H:i:s', $keep->created) . ")\n";
            
            // Listar preços do produto mantido para exibir
            $prices = \Stripe\Price::all(['product' => $keep->id]);
            foreach ($prices->data as $price) {
                echo "    - Preço: R$ " . ($price->unit_amount/100) . " / " . ($price->recurring->interval ?? 'unico') . " (ID: {$price->id})\n";
            }

            // Arquivar os duplicados
            foreach ($list as $duplicate) {
                echo "  [ARQUIVAR] ID: {$duplicate->id} (Criado em " . date('Y-m-d H:i:s', $duplicate->created) . ")... ";
                
                \Stripe\Product::update($duplicate->id, ['active' => false]);
                echo "OK\n";
            }
        } else {
            echo "Produto '{$name}' está OK (único).\n";
        }
        echo "--------------------------------------------------\n";
    }

    echo "\nLimpeza concluída!\n";

} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
