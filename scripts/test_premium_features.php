<?php
/**
 * Test Script for Premium Features
 * 
 * This script helps you test the premium features by updating user subscription tiers.
 * Run this script to see the premium features in action.
 */

require_once '../config/config.php';
require_once '../includes/subscription_functions.php';

echo "<h1>Premium Features Test Script</h1>";
echo "<p>This script will help you test the premium features by updating user subscription tiers.</p>";

// Check if action is requested
$action = $_GET['action'] ?? '';

if ($action === 'update_tiers') {
    try {
        // Update some users to different tiers for testing
        $updates = [
            ['user_id' => 1, 'tier' => 'premium_plus'],
            ['user_id' => 2, 'tier' => 'premium'],
            ['user_id' => 3, 'tier' => 'basic']
        ];
        
        $stmt = $pdo->prepare("UPDATE users SET subscription_tier = ? WHERE id = ?");
        
        foreach ($updates as $update) {
            $stmt->execute([$update['tier'], $update['user_id']]);
            echo "<p>✅ Updated user ID {$update['user_id']} to {$update['tier']} tier</p>";
        }
        
        echo "<h3>✅ Tier updates completed!</h3>";
        echo "<p>Now you can see the premium features in action:</p>";
        echo "<ul>";
        echo "<li><a href='/businesses.php' target='_blank'>View businesses page</a> - See pinned Premium+ businesses and animated effects</li>";
        echo "<li><a href='/users/dashboard.php' target='_blank'>View dashboard</a> - See subscription upgrade options</li>";
        echo "<li><a href='/' target='_blank'>View homepage</a> - See featured premium businesses</li>";
        echo "</ul>";
        
    } catch (PDOException $e) {
        echo "<p>❌ Error: " . $e->getMessage() . "</p>";
    }
} else {
    // Show current tier status
    echo "<h2>Current User Subscription Tiers</h2>";
    
    $stmt = $pdo->prepare("SELECT id, first_name, last_name, subscription_tier FROM users ORDER BY id LIMIT 10");
    $stmt->execute();
    $users = $stmt->fetchAll();
    
    if (empty($users)) {
        echo "<p>No users found.</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>User ID</th><th>Name</th><th>Current Tier</th></tr>";
        
        foreach ($users as $user) {
            $tier = $user['subscription_tier'] ?? 'basic';
            $tier_class = $tier === 'premium_plus' ? 'color: #007bff; font-weight: bold;' : 
                         ($tier === 'premium' ? 'color: #ffd700; font-weight: bold;' : 'color: #6c757d;');
            
            echo "<tr>";
            echo "<td>{$user['id']}</td>";
            echo "<td>{$user['first_name']} {$user['last_name']}</td>";
            echo "<td style='{$tier_class}'>{$tier}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<h2>Test Premium Features</h2>";
    echo "<p>Click the button below to update some users to different tiers for testing:</p>";
    echo "<a href='?action=update_tiers' class='btn btn-primary' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Update Tiers for Testing</a>";
    
    echo "<h2>What You'll See After Testing</h2>";
    echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 10px; margin: 20px 0;'>";
    echo "<h3>Premium+ Features (Elite):</h3>";
    echo "<ul>";
    echo "<li>⭐ Pinned at top of businesses page</li>";
    echo "<li>✨ Animated glow effect on business cards</li>";
    echo "<li>👑 Elite ribbon with crown icon</li>";
    echo "<li>🔵 Blue Premium+ badge with glow animation</li>";
    echo "<li>∞ Unlimited images and testimonials</li>";
    echo "<li>🧪 Beta features access</li>";
    echo "</ul>";
    
    echo "<h3>Premium Features:</h3>";
    echo "<ul>";
    echo "<li>⭐ Featured ribbon on business cards</li>";
    echo "<li>🟡 Gold Premium badge</li>";
    echo "<li>📸 Up to 5 images and testimonials</li>";
    echo "<li>🏠 Homepage carousel visibility</li>";
    echo "</ul>";
    
    echo "<h3>Basic Features:</h3>";
    echo "<ul>";
    echo "<li>📝 Basic listing</li>";
    echo "<li>🖼️ 1 image only</li>";
    echo "<li>📝 No testimonials</li>";
    echo "<li>⚪ Gray Basic badge</li>";
    echo "</ul>";
    echo "</div>";
}

echo "<hr>";
echo "<p><small>Test script completed. You can now see the premium features in action across the site!</small></p>";
?> 