<?php
/**
 * Check Subscription Plans and Stripe Price IDs
 */

require_once '../config/config.php';

echo "🔍 Checking Subscription Plans and Stripe Price IDs...\n\n";

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

    echo "📋 Found " . count($plans) . " subscription plans:\n\n";

    foreach ($plans as $plan) {
        echo "Plan ID: {$plan['id']}\n";
        echo "Name: {$plan['name']}\n";
        echo "Price: £{$plan['price']}\n";
        echo "Annual Price: " . ($plan['annual_price'] ? "£{$plan['annual_price']}" : "Not set") . "\n";
        echo "Stripe Price ID: " . ($plan['stripe_price_id'] ? $plan['stripe_price_id'] : "❌ NOT SET") . "\n";
        echo "Status: {$plan['status']}\n";
        echo "---\n\n";
    }

    // Check which plans need Stripe Price IDs
    $plans_needing_stripe_ids = array_filter($plans, function($plan) {
        return empty($plan['stripe_price_id']);
    });

    if (!empty($plans_needing_stripe_ids)) {
        echo "⚠️  Plans that need Stripe Price IDs:\n";
        foreach ($plans_needing_stripe_ids as $plan) {
            echo "- {$plan['name']} (ID: {$plan['id']})\n";
        }
        echo "\n";
        echo "🔧 To fix this, you need to:\n";
        echo "1. Go to your Stripe Dashboard: https://dashboard.stripe.com/products\n";
        echo "2. Create products and prices for each plan\n";
        echo "3. Copy the Price IDs (start with 'price_') and update your database\n";
        echo "4. Or use the setup script I'll create for you\n";
    } else {
        echo "✅ All plans have Stripe Price IDs configured!\n";
    }

} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}
?> 