<?php
// Test script to verify homepage data loading
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Homepage Data Test</h1>";

// Test database connection
require_once 'config/config.php';

if (isset($pdo) && $pdo) {
    echo "<p style='color:green'>✅ Database connection successful</p>";
    
    // Test categories query
    try {
        $stmt = $pdo->query("SELECT id, name FROM business_categories ORDER BY name ASC");
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<p style='color:green'>✅ Categories loaded: " . count($categories) . " categories</p>";
        
        if (!empty($categories)) {
            echo "<ul>";
            foreach (array_slice($categories, 0, 5) as $cat) {
                echo "<li>{$cat['name']}</li>";
            }
            echo "</ul>";
        }
    } catch (PDOException $e) {
        echo "<p style='color:red'>❌ Categories query failed: " . $e->getMessage() . "</p>";
    }
    
    // Test stats query
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM businesses WHERE status = 'active'");
        $stmt->execute();
        $total_businesses = $stmt->fetchColumn();
        echo "<p style='color:green'>✅ Total businesses: " . $total_businesses . "</p>";
        
        $stmt = $pdo->prepare("SELECT COUNT(DISTINCT user_id) FROM user_activity WHERE activity_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
        $stmt->execute();
        $monthly_users = $stmt->fetchColumn() ?: 1200;
        echo "<p style='color:green'>✅ Monthly users: " . $monthly_users . "</p>";
    } catch (PDOException $e) {
        echo "<p style='color:red'>❌ Stats query failed: " . $e->getMessage() . "</p>";
    }
    
    // Test featured businesses query
    try {
        $stmt = $pdo->prepare("
            SELECT b.id, b.business_name, b.description, b.category_id, b.is_featured, b.featured_until, 
                   c.name as category_name, u.subscription_tier 
            FROM businesses b 
            LEFT JOIN business_categories c ON b.category_id = c.id 
            LEFT JOIN users u ON b.user_id = u.id
            WHERE b.status = 'active' 
            AND u.subscription_tier IN ('premium', 'premium_plus')
            ORDER BY 
                CASE u.subscription_tier 
                    WHEN 'premium_plus' THEN 1 
                    WHEN 'premium' THEN 2 
                    ELSE 3 
                END,
                b.created_at DESC 
            LIMIT 6
        ");
        $stmt->execute();
        $featured = $stmt->fetchAll();
        echo "<p style='color:green'>✅ Featured businesses loaded: " . count($featured) . " businesses</p>";
        
        if (!empty($featured)) {
            echo "<ul>";
            foreach ($featured as $biz) {
                echo "<li>{$biz['business_name']} ({$biz['subscription_tier']})</li>";
            }
            echo "</ul>";
        }
    } catch (PDOException $e) {
        echo "<p style='color:red'>❌ Featured businesses query failed: " . $e->getMessage() . "</p>";
    }
    
    // Test new businesses query
    try {
        $stmt = $pdo->prepare("
            SELECT b.id, b.business_name, b.description, b.created_at, c.name AS category_name, u.subscription_tier
            FROM businesses b
            LEFT JOIN business_categories c ON b.category_id = c.id
            LEFT JOIN users u ON b.user_id = u.id
            WHERE b.status = 'active'
            ORDER BY b.created_at DESC
            LIMIT 6
        ");
        $stmt->execute();
        $newBusinesses = $stmt->fetchAll();
        echo "<p style='color:green'>✅ New businesses loaded: " . count($newBusinesses) . " businesses</p>";
        
        if (!empty($newBusinesses)) {
            echo "<ul>";
            foreach ($newBusinesses as $biz) {
                echo "<li>{$biz['business_name']} (created: {$biz['created_at']})</li>";
            }
            echo "</ul>";
        }
    } catch (PDOException $e) {
        echo "<p style='color:red'>❌ New businesses query failed: " . $e->getMessage() . "</p>";
    }
    
} else {
    echo "<p style='color:red'>❌ Database connection failed</p>";
}

echo "<hr>";
echo "<p><a href='index.php'>Back to Homepage</a></p>";
?> 