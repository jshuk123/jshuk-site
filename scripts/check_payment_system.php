<?php
/**
 * Payment System Status Checker
 * 
 * This script checks the status of the payment system and ensures
 * all components are properly configured and working.
 */

require_once '../config/config.php';
require_once '../includes/subscription_functions.php';

echo "<h1>🔍 Payment System Status Check</h1>";
echo "<p>Checking the status of your payment system...</p>";

$checks = [];
$errors = [];

// Check 1: Database Tables
echo "<h2>1. Database Tables</h2>";
try {
    // Check subscription_plans table
    $stmt = $pdo->query("SHOW TABLES LIKE 'subscription_plans'");
    if ($stmt->rowCount() > 0) {
        echo "<p>✅ subscription_plans table exists</p>";
        $checks['subscription_plans_table'] = true;
    } else {
        echo "<p>❌ subscription_plans table missing</p>";
        $checks['subscription_plans_table'] = false;
        $errors[] = "subscription_plans table missing";
    }
    
    // Check user_subscriptions table
    $stmt = $pdo->query("SHOW TABLES LIKE 'user_subscriptions'");
    if ($stmt->rowCount() > 0) {
        echo "<p>✅ user_subscriptions table exists</p>";
        $checks['user_subscriptions_table'] = true;
    } else {
        echo "<p>❌ user_subscriptions table missing</p>";
        $checks['user_subscriptions_table'] = false;
        $errors[] = "user_subscriptions table missing";
    }
    
    // Check users table has subscription_tier column
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'subscription_tier'");
    if ($stmt->rowCount() > 0) {
        echo "<p>✅ users.subscription_tier column exists</p>";
        $checks['subscription_tier_column'] = true;
    } else {
        echo "<p>❌ users.subscription_tier column missing</p>";
        $checks['subscription_tier_column'] = false;
        $errors[] = "users.subscription_tier column missing";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Database error: " . $e->getMessage() . "</p>";
    $errors[] = "Database error: " . $e->getMessage();
}

// Check 2: Subscription Plans
echo "<h2>2. Subscription Plans</h2>";
try {
    $stmt = $pdo->query("SELECT * FROM subscription_plans ORDER BY price ASC");
    $plans = $stmt->fetchAll();
    
    if (count($plans) > 0) {
        echo "<p>✅ Found " . count($plans) . " subscription plans:</p>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Plan</th><th>Price</th><th>Annual Price</th><th>Trial Days</th><th>Status</th></tr>";
        
        foreach ($plans as $plan) {
            echo "<tr>";
            echo "<td><strong>{$plan['name']}</strong></td>";
            echo "<td>£{$plan['price']}/month</td>";
            echo "<td>£{$plan['annual_price']}/year</td>";
            echo "<td>{$plan['trial_period_days']}</td>";
            echo "<td>{$plan['status']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        $checks['subscription_plans'] = true;
    } else {
        echo "<p>❌ No subscription plans found</p>";
        $checks['subscription_plans'] = false;
        $errors[] = "No subscription plans found";
    }
} catch (Exception $e) {
    echo "<p>❌ Error checking subscription plans: " . $e->getMessage() . "</p>";
    $errors[] = "Error checking subscription plans: " . $e->getMessage();
}

// Check 3: Stripe Configuration
echo "<h2>3. Stripe Configuration</h2>";
try {
    if (defined('STRIPE_SECRET_KEY') && !empty(STRIPE_SECRET_KEY)) {
        echo "<p>✅ Stripe secret key configured</p>";
        $checks['stripe_secret_key'] = true;
    } else {
        echo "<p>❌ Stripe secret key not configured</p>";
        $checks['stripe_secret_key'] = false;
        $errors[] = "Stripe secret key not configured";
    }
    
    if (defined('STRIPE_PUBLIC_KEY') && !empty(STRIPE_PUBLIC_KEY)) {
        echo "<p>✅ Stripe public key configured</p>";
        $checks['stripe_public_key'] = true;
    } else {
        echo "<p>❌ Stripe public key not configured</p>";
        $checks['stripe_public_key'] = false;
        $errors[] = "Stripe public key not configured";
    }
    
    // Test Stripe connection
    if ($checks['stripe_secret_key']) {
        try {
            require_once '../vendor/autoload.php';
            \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
            $account = \Stripe\Account::retrieve();
            echo "<p>✅ Stripe connection successful</p>";
            $checks['stripe_connection'] = true;
        } catch (Exception $e) {
            echo "<p>❌ Stripe connection failed: " . $e->getMessage() . "</p>";
            $checks['stripe_connection'] = false;
            $errors[] = "Stripe connection failed: " . $e->getMessage();
        }
    }
} catch (Exception $e) {
    echo "<p>❌ Error checking Stripe configuration: " . $e->getMessage() . "</p>";
    $errors[] = "Error checking Stripe configuration: " . $e->getMessage();
}

// Check 4: User Subscriptions
echo "<h2>4. User Subscriptions</h2>";
try {
    $stmt = $pdo->query("
        SELECT COUNT(*) as total_users,
               SUM(CASE WHEN subscription_tier = 'basic' THEN 1 ELSE 0 END) as basic_users,
               SUM(CASE WHEN subscription_tier = 'premium' THEN 1 ELSE 0 END) as premium_users,
               SUM(CASE WHEN subscription_tier = 'premium_plus' THEN 1 ELSE 0 END) as premium_plus_users
        FROM users
    ");
    $user_stats = $stmt->fetch();
    
    echo "<p>✅ User subscription tiers:</p>";
    echo "<ul>";
    echo "<li>Total users: {$user_stats['total_users']}</li>";
    echo "<li>Basic tier: {$user_stats['basic_users']}</li>";
    echo "<li>Premium tier: {$user_stats['premium_users']}</li>";
    echo "<li>Premium+ tier: {$user_stats['premium_plus_users']}</li>";
    echo "</ul>";
    $checks['user_subscriptions'] = true;
    
    // Check active Stripe subscriptions
    $stmt = $pdo->query("
        SELECT COUNT(*) as active_subscriptions 
        FROM user_subscriptions 
        WHERE status IN ('active', 'trialing')
    ");
    $active_subs = $stmt->fetchColumn();
    echo "<p>✅ Active Stripe subscriptions: {$active_subs}</p>";
    
} catch (Exception $e) {
    echo "<p>❌ Error checking user subscriptions: " . $e->getMessage() . "</p>";
    $errors[] = "Error checking user subscriptions: " . $e->getMessage();
}

// Check 5: Payment Files
echo "<h2>5. Payment System Files</h2>";
$payment_files = [
    '../payment/checkout.php',
    '../payment/subscription_success.php',
    '../payment/webhook.php',
    '../users/upgrade_subscription.php',
    '../includes/subscription_functions.php'
];

foreach ($payment_files as $file) {
    if (file_exists($file)) {
        echo "<p>✅ {$file}</p>";
        $checks['payment_files'] = true;
    } else {
        echo "<p>❌ {$file} missing</p>";
        $checks['payment_files'] = false;
        $errors[] = "Payment file missing: {$file}";
    }
}

// Check 6: Webhook Endpoint
echo "<h2>6. Webhook Configuration</h2>";
$webhook_url = "https://" . $_SERVER['HTTP_HOST'] . "/payment/webhook.php";
echo "<p>Webhook URL: <code>{$webhook_url}</code></p>";
echo "<p>⚠️ Make sure this URL is configured in your Stripe dashboard</p>";

// Summary
echo "<h2>📊 Summary</h2>";
$total_checks = count($checks);
$passed_checks = count(array_filter($checks));

echo "<div style='background: " . ($passed_checks === $total_checks ? '#d4edda' : '#f8d7da') . "; padding: 20px; border-radius: 8px;'>";
echo "<h3>" . ($passed_checks === $total_checks ? "✅ All Systems Operational" : "⚠️ Issues Found") . "</h3>";
echo "<p>Passed: {$passed_checks}/{$total_checks} checks</p>";

if (!empty($errors)) {
    echo "<h4>Issues to Fix:</h4>";
    echo "<ul>";
    foreach ($errors as $error) {
        echo "<li>{$error}</li>";
    }
    echo "</ul>";
}

echo "</div>";

// Recommendations
echo "<h2>🔧 Recommendations</h2>";
echo "<ul>";
if (!in_array('subscription_plans', $checks) || !$checks['subscription_plans']) {
    echo "<li><a href='/scripts/setup_subscription_plans.php' target='_blank'>Run Setup Subscription Plans</a></li>";
}
if (!in_array('stripe_connection', $checks) || !$checks['stripe_connection']) {
    echo "<li><a href='/payment/setup_stripe_products.php' target='_blank'>Setup Stripe Products</a></li>";
}
echo "<li><a href='/users/dashboard.php' target='_blank'>Test User Dashboard</a></li>";
echo "<li><a href='/businesses.php' target='_blank'>Test Business Listings</a></li>";
echo "<li>Configure webhook endpoint in Stripe dashboard: <code>{$webhook_url}</code></li>";
echo "</ul>";

echo "<hr>";
echo "<p><small>Payment system check completed at " . date('Y-m-d H:i:s') . "</small></p>";
?> 