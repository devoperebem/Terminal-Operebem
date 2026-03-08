<?php
/**
 * Script para listar Price IDs do Stripe
 */
require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createMutable(__DIR__);
$dotenv->safeLoad();

$secretKey = $_ENV['STRIPE_SECRET_KEY'] ?? getenv('STRIPE_SECRET_KEY');

$ch = curl_init('https://api.stripe.com/v1/prices?limit=10');
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $secretKey]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = json_decode(curl_exec($ch), true);
curl_close($ch);

echo "=== Price IDs no Stripe ===\n\n";
foreach ($response['data'] ?? [] as $price) {
    $amount = $price['unit_amount'] / 100;
    $interval = $price['recurring']['interval'] ?? 'one-time';
    $product = $price['product'];
    echo "ID: {$price['id']}\n";
    echo "   Valor: R$ {$amount} / {$interval}\n";
    echo "   Product: {$product}\n\n";
}
