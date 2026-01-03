<?php
/**
 * Script para atualizar tabela subscription_plans com os Stripe IDs
 */
require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createMutable(__DIR__);
$dotenv->safeLoad();

try {
    $db = new PDO(
        'pgsql:host=' . $_ENV['DB_HOST'] . ';dbname=' . $_ENV['DB_DATABASE'],
        $_ENV['DB_USERNAME'],
        $_ENV['DB_PASSWORD']
    );
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== Atualizando subscription_plans ===\n\n";
    
    // Atualizar PLUS
    $stmt = $db->prepare("
        UPDATE subscription_plans 
        SET stripe_product_id = :product, stripe_price_id = :price, updated_at = NOW()
        WHERE slug = 'plus_monthly'
    ");
    $stmt->execute([
        'product' => 'prod_Tiy0KMof7HfFH3',
        'price' => 'price_1SlW4fDhuEkxOnkWz1Sh1mcS'
    ]);
    echo "✅ PLUS Mensal atualizado ({$stmt->rowCount()} linhas)\n";
    
    // Atualizar PRO
    $stmt = $db->prepare("
        UPDATE subscription_plans 
        SET stripe_product_id = :product, stripe_price_id = :price, updated_at = NOW()
        WHERE slug = 'pro_yearly'
    ");
    $stmt->execute([
        'product' => 'prod_Tiy050l9NF7nEs',
        'price' => 'price_1SlW4gDhuEkxOnkWelPmZJ21'
    ]);
    echo "✅ PRO Anual atualizado ({$stmt->rowCount()} linhas)\n";
    
    // Listar planos atualizados
    echo "\n=== Planos Atualizados ===\n";
    $result = $db->query("SELECT slug, name, stripe_product_id, stripe_price_id, price_cents, is_active FROM subscription_plans ORDER BY display_order");
    foreach ($result as $row) {
        $active = $row['is_active'] ? '✅' : '❌';
        echo "{$active} {$row['name']} ({$row['slug']})\n";
        echo "   Product: {$row['stripe_product_id']}\n";
        echo "   Price: {$row['stripe_price_id']}\n";
        echo "   Valor: R$ " . ($row['price_cents'] / 100) . "\n\n";
    }
    
    echo "🎉 Pronto! Tabela subscription_plans atualizada.\n";
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}
