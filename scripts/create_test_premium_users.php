<?php
require_once '../config/config.php';

echo "<h1>Create Test Premium Users</h1>";

try {
    // Update first 2 users to premium
    $stmt = $pdo->prepare("UPDATE users SET subscription_tier = 'premium' WHERE id IN (1, 2)");
    $result = $stmt->execute();
    echo "<p>✅ Updated users 1 and 2 to Premium tier</p>";
    
    // Update user 3 to premium_plus
    $stmt = $pdo->prepare("UPDATE users SET subscription_tier = 'premium_plus' WHERE id = 3");
    $result = $stmt->execute();
    echo "<p>✅ Updated user 3 to Premium+ tier</p>";
    
    // Show current status
    $stmt = $pdo->query("SELECT subscription_tier, COUNT(*) as count FROM users GROUP BY subscription_tier");
    $tiers = $stmt->fetchAll();
    
    echo "<h2>Current Users by Tier:</h2>";
    foreach ($tiers as $tier) {
        echo "<p><strong>" . ucfirst($tier['subscription_tier']) . ":</strong> {$tier['count']} users</p>";
    }
    
    echo "<h2>Next Steps:</h2>";
    echo "<p><a href='/businesses.php'>View Business Listings</a> - You should now see badges!</p>";
    echo "<p><a href='/index.php'>View Homepage</a> - Premium businesses should appear</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?> 