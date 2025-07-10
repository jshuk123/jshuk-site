<?php
/**
 * Diagnostic Script for Subscription Badges
 * 
 * This script helps troubleshoot why subscription badges aren't showing.
 */

require_once '../config/config.php';
require_once '../includes/subscription_functions.php';

echo "<h1>Badge Diagnostic</h1>";

// Test badge rendering
echo "<h2>Test Badges:</h2>";
echo renderSubscriptionBadge('basic', true) . "<br>";
echo renderSubscriptionBadge('premium', true) . "<br>";
echo renderSubscriptionBadge('premium_plus', true) . "<br>";

// Check database
$stmt = $pdo->query("SELECT subscription_tier, COUNT(*) as count FROM users GROUP BY subscription_tier");
$tiers = $stmt->fetchAll();
echo "<h2>Users by Tier:</h2>";
foreach ($tiers as $tier) {
    echo ucfirst($tier['subscription_tier']) . ": " . $tier['count'] . "<br>";
}

echo "<h1>Subscription Badge Diagnostic</h1>";
echo "<style>
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .info { color: blue; font-weight: bold; }
    .test-badge { margin: 10px; padding: 10px; border: 1px solid #ccc; }
</style>";

try {
    echo "<h2>1. Database Connection Test</h2>";
    if (isset($pdo) && $pdo) {
        echo "<p class='success'>✅ Database connection successful</p>";
    } else {
        echo "<p class='error'>❌ Database connection failed</p>";
        exit;
    }
    
    echo "<h2>2. Check if subscription_tier column exists in users table</h2>";
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    if (in_array('subscription_tier', $columns)) {
        echo "<p class='success'>✅ subscription_tier column exists in users table</p>";
    } else {
        echo "<p class='error'>❌ subscription_tier column missing from users table</p>";
        echo "<p class='info'>Run: <a href='/scripts/run_subscription_updates.php'>Database Updates</a></p>";
    }
    
    echo "<h2>3. Check if subscription_tier column exists in businesses table</h2>";
    $stmt = $pdo->query("DESCRIBE businesses");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    if (in_array('subscription_tier', $columns)) {
        echo "<p class='success'>✅ subscription_tier column exists in businesses table</p>";
    } else {
        echo "<p class='error'>❌ subscription_tier column missing from businesses table</p>";
        echo "<p class='info'>Run: <a href='/scripts/run_subscription_updates.php'>Database Updates</a></p>";
    }
    
    echo "<h2>4. Current Users by Tier</h2>";
    $stmt = $pdo->query("SELECT subscription_tier, COUNT(*) as count FROM users GROUP BY subscription_tier");
    $user_tiers = $stmt->fetchAll();
    
    if (empty($user_tiers)) {
        echo "<p class='error'>❌ No users found in database</p>";
    } else {
        echo "<ul>";
        foreach ($user_tiers as $tier) {
            echo "<li><strong>" . ucfirst($tier['subscription_tier']) . ":</strong> {$tier['count']} users</li>";
        }
        echo "</ul>";
    }
    
    echo "<h2>5. Current Businesses by Tier</h2>";
    $stmt = $pdo->query("SELECT subscription_tier, COUNT(*) as count FROM businesses GROUP BY subscription_tier");
    $business_tiers = $stmt->fetchAll();
    
    if (empty($business_tiers)) {
        echo "<p class='error'>❌ No businesses found in database</p>";
    } else {
        echo "<ul>";
        foreach ($business_tiers as $tier) {
            echo "<li><strong>" . ucfirst($tier['subscription_tier']) . ":</strong> {$tier['count']} businesses</li>";
        }
        echo "</ul>";
    }
    
    echo "<h2>6. Test Badge Rendering Functions</h2>";
    echo "<div class='test-badge'>";
    echo "<h3>Basic Badge:</h3>";
    echo renderSubscriptionBadge('basic', true);
    echo "</div>";
    
    echo "<div class='test-badge'>";
    echo "<h3>Premium Badge:</h3>";
    echo renderSubscriptionBadge('premium', true);
    echo "</div>";
    
    echo "<div class='test-badge'>";
    echo "<h3>Premium+ Badge:</h3>";
    echo renderSubscriptionBadge('premium_plus', true);
    echo "</div>";
    
    echo "<h2>7. Test with Sample Business Data</h2>";
    echo "<div class='test-badge'>";
    echo "<h3>Sample Business Card with Premium Badge:</h3>";
    
    // Create sample business data
    $sample_biz = [
        'id' => 999,
        'business_name' => 'Sample Premium Business',
        'description' => 'This is a sample business to test subscription badges',
        'category_name' => 'Test Category',
        'subscription_tier' => 'premium'
    ];
    
    // Test the renderBusinessCard function
    echo renderBusinessCard($sample_biz);
    echo "</div>";
    
    echo "<h2>8. Check CSS File</h2>";
    $css_file = '../css/components/subscription-badges.css';
    if (file_exists($css_file)) {
        echo "<p class='success'>✅ Subscription badges CSS file exists</p>";
        $css_content = file_get_contents($css_file);
        if (strpos($css_content, '.subscription-badge') !== false) {
            echo "<p class='success'>✅ CSS contains subscription badge styles</p>";
        } else {
            echo "<p class='error'>❌ CSS file missing subscription badge styles</p>";
        }
    } else {
        echo "<p class='error'>❌ Subscription badges CSS file missing</p>";
    }
    
    echo "<h2>9. Quick Fix Options</h2>";
    echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 10px;'>";
    echo "<h3>If badges still don't show:</h3>";
    echo "<ol>";
    echo "<li><a href='/scripts/run_subscription_updates.php'>Run Database Updates</a> - Add subscription_tier columns</li>";
    echo "<li><a href='/scripts/setup_subscription_plans.php'>Setup Subscription Plans</a> - Configure the plans</li>";
    echo "<li><a href='/scripts/test_premium_features.php'>Test Premium Features</a> - Update some users to premium tiers</li>";
    echo "<li>Check browser console for CSS errors</li>";
    echo "<li>Clear browser cache and refresh</li>";
    echo "</ol>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<p><small>Diagnostic script completed.</small></p>";
?> 