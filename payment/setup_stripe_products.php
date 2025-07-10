<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/stripe_config.php';

try {
    // Create Basic Plan Product
    $basic_product = \Stripe\Product::create([
        'name' => 'Basic Plan',
        'description' => 'Basic plan for small businesses',
        'metadata' => [
            'plan_type' => 'basic'
        ]
    ]);

    // Create Basic Plan Price
    $basic_price = \Stripe\Price::create([
        'product' => $basic_product->id,
        'unit_amount' => 0, // Free plan
        'currency' => 'gbp',
        'recurring' => [
            'interval' => 'month'
        ]
    ]);

    // Create Premium Plan Product
    $premium_product = \Stripe\Product::create([
        'name' => 'Premium Plan',
        'description' => 'Enhanced features for growing businesses',
        'metadata' => [
            'plan_type' => 'premium'
        ]
    ]);

    // Create Premium Plan Price
    $premium_price = \Stripe\Price::create([
        'product' => $premium_product->id,
        'unit_amount' => 750, // £7.50
        'currency' => 'gbp',
        'recurring' => [
            'interval' => 'month'
        ]
    ]);

    // Create Premium Plus Plan Product
    $premium_plus_product = \Stripe\Product::create([
        'name' => 'Premium Plus Plan',
        'description' => 'Ultimate features for established businesses',
        'metadata' => [
            'plan_type' => 'premium_plus'
        ]
    ]);

    // Create Premium Plus Plan Price
    $premium_plus_price = \Stripe\Price::create([
        'product' => $premium_plus_product->id,
        'unit_amount' => 1500, // £15.00
        'currency' => 'gbp',
        'recurring' => [
            'interval' => 'month'
        ]
    ]);

    // Update database with new product and price IDs
    $stmt = $pdo->prepare("
        UPDATE subscription_plans 
        SET stripe_product_id = ?, stripe_price_id = ? 
        WHERE name = ?
    ");

    // Update Basic Plan
    $stmt->execute([
        $basic_product->id,
        $basic_price->id,
        'Basic'
    ]);

    // Update Premium Plan
    $stmt->execute([
        $premium_product->id,
        $premium_price->id,
        'Premium'
    ]);

    // Update Premium Plus Plan
    $stmt->execute([
        $premium_plus_product->id,
        $premium_plus_price->id,
        'Premium Plus'
    ]);

    echo "Successfully created and updated Stripe products and prices!\n";
    echo "Basic Plan: Product ID: {$basic_product->id}, Price ID: {$basic_price->id}\n";
    echo "Premium Plan: Product ID: {$premium_product->id}, Price ID: {$premium_price->id}\n";
    echo "Premium Plus Plan: Product ID: {$premium_plus_product->id}, Price ID: {$premium_plus_price->id}\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
} 