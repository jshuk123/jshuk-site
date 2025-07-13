<?php
/**
 * Setup Stripe Prices for Subscription Plans
 * 
 * This script will:
 * 1. Create Stripe products for each subscription plan
 * 2. Create Stripe prices for each plan
 * 3. Update the database with the Stripe Price IDs
 */

require_once '../config/config.php';
require_once '../config/stripe_config.php';

echo "🚀 Setting up Stripe Prices for Subscription Plans...\n\n";

try {
    // Check if subscription_plans table exists
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'subscription_plans'");
    $stmt->execute();
    if (!$stmt->fetch()) {
        echo "❌ subscription_plans table does not exist!\n";
        exit;
    }

    // Get all subscription plans
    $stmt = $pdo->query("SELECT * FROM subscription_plans ORDER BY price ASC");
    $plans = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($plans)) {
        echo "❌ No subscription plans found in database!\n";
        exit;
    }

    echo "📋 Found " . count($plans) . " subscription plans to process:\n\n";

    $success_count = 0;
    $error_count = 0;

    foreach ($plans as $plan) {
        echo "Processing: {$plan['name']} (£{$plan['price']}/month)\n";
        
        // Skip if already has Stripe Price ID
        if (!empty($plan['stripe_price_id'])) {
            echo "  ⚠️  Already has Stripe Price ID: {$plan['stripe_price_id']}\n";
            $success_count++;
            continue;
        }

        try {
            // Create Stripe Product
            $product = \Stripe\Product::create([
                'name' => $plan['name'] . ' Plan',
                'description' => $plan['description'] ?: "JShuk {$plan['name']} subscription plan",
                'metadata' => [
                    'plan_id' => $plan['id'],
                    'jshuk_plan' => 'true'
                ]
            ]);

            echo "  ✅ Created Stripe Product: {$product->id}\n";

            // Create Stripe Price for monthly billing
            $price = \Stripe\Price::create([
                'product' => $product->id,
                'unit_amount' => (int)($plan['price'] * 100), // Convert to cents
                'currency' => 'gbp',
                'recurring' => [
                    'interval' => 'month'
                ],
                'metadata' => [
                    'plan_id' => $plan['id'],
                    'billing_interval' => 'monthly',
                    'jshuk_plan' => 'true'
                ]
            ]);

            echo "  ✅ Created Stripe Price: {$price->id}\n";

            // Update database with Stripe Price ID
            $stmt = $pdo->prepare("UPDATE subscription_plans SET stripe_price_id = ? WHERE id = ?");
            $stmt->execute([$price->id, $plan['id']]);

            echo "  ✅ Updated database with Stripe Price ID\n";

            // Create annual price if annual_price is set
            if (!empty($plan['annual_price']) && $plan['annual_price'] > 0) {
                $annual_price = \Stripe\Price::create([
                    'product' => $product->id,
                    'unit_amount' => (int)($plan['annual_price'] * 100), // Convert to cents
                    'currency' => 'gbp',
                    'recurring' => [
                        'interval' => 'year'
                    ],
                    'metadata' => [
                        'plan_id' => $plan['id'],
                        'billing_interval' => 'yearly',
                        'jshuk_plan' => 'true'
                    ]
                ]);

                echo "  ✅ Created Annual Stripe Price: {$annual_price->id}\n";

                // Update database with annual Stripe Price ID
                $stmt = $pdo->prepare("UPDATE subscription_plans SET stripe_annual_price_id = ? WHERE id = ?");
                $stmt->execute([$annual_price->id, $plan['id']]);

                echo "  ✅ Updated database with Annual Stripe Price ID\n";
            }

            $success_count++;
            echo "  🎉 Successfully set up Stripe pricing for {$plan['name']}\n\n";

        } catch (\Stripe\Exception\ApiErrorException $e) {
            echo "  ❌ Stripe API Error: " . $e->getMessage() . "\n";
            $error_count++;
        } catch (PDOException $e) {
            echo "  ❌ Database Error: " . $e->getMessage() . "\n";
            $error_count++;
        }
    }

    echo "🎉 Setup completed!\n";
    echo "✅ Successful operations: {$success_count}\n";
    echo "❌ Errors: {$error_count}\n\n";

    if ($error_count === 0) {
        echo "🎯 All subscription plans now have Stripe Price IDs!\n";
        echo "📝 You can now test the payment system.\n";
    } else {
        echo "⚠️  Some operations failed. Please check the errors above.\n";
    }

} catch (Exception $e) {
    echo "❌ Fatal error: " . $e->getMessage() . "\n";
}

echo "\n";
?> 