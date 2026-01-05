<?php
require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

\Stripe\Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);

echo "Listando Produtos e Preços...\n\n";

try {
    $products = \Stripe\Product::all(['limit' => 100, 'active' => true]);
    
    foreach ($products->data as $product) {
        echo "PRODUTO: {$product->name} (ID: {$product->id})\n";
        echo "  Criado em: " . date('Y-m-d H:i:s', $product->created) . "\n";
        
        $prices = \Stripe\Price::all(['product' => $product->id, 'limit' => 10]);
        foreach ($prices->data as $price) {
            $amount = $price->unit_amount / 100;
            $interval = $price->recurring->interval ?? 'unico';
            echo "  - PREÇO: R$ {$amount} / {$interval} (ID: {$price->id})\n";
        }
        echo "--------------------------------------------------\n";
    }
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
