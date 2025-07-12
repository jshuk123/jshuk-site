<?php
/**
 * Debug script for Free Stuff system
 * This will help identify database issues
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>🔍 Free Stuff System Debug</h1>";
echo "<div style='font-family: monospace; background: #f8f9fa; padding: 20px; border-radius: 8px;'>";

try {
    // Test database connection
    require_once 'config/config.php';
    echo "✅ Database connection: OK<br><br>";
    
    // Check if classifieds_categories table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'classifieds_categories'");
    if ($stmt->rowCount() > 0) {
        echo "✅ classifieds_categories table: EXISTS<br>";
        
        // Check table structure
        $stmt = $pdo->query("DESCRIBE classifieds_categories");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "📋 Table structure:<br>";
        foreach ($columns as $col) {
            echo "&nbsp;&nbsp;• {$col['Field']} - {$col['Type']}<br>";
        }
        
        // Check if categories exist
        $stmt = $pdo->query("SELECT COUNT(*) FROM classifieds_categories");
        $count = $stmt->fetchColumn();
        echo "📊 Categories count: $count<br><br>";
        
    } else {
        echo "❌ classifieds_categories table: MISSING<br><br>";
    }
    
    // Check if free_stuff_requests table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'free_stuff_requests'");
    if ($stmt->rowCount() > 0) {
        echo "✅ free_stuff_requests table: EXISTS<br>";
    } else {
        echo "❌ free_stuff_requests table: MISSING<br>";
    }
    
    // Check classifieds table structure
    echo "<br>🔍 Checking classifieds table structure:<br>";
    $stmt = $pdo->query("DESCRIBE classifieds");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $required_columns = [
        'category_id', 'pickup_method', 'collection_deadline', 
        'is_anonymous', 'is_chessed', 'is_bundle', 'status', 
        'pickup_code', 'contact_method', 'contact_info'
    ];
    
    $existing_columns = array_column($columns, 'Field');
    
    foreach ($required_columns as $col) {
        if (in_array($col, $existing_columns)) {
            echo "✅ $col: EXISTS<br>";
        } else {
            echo "❌ $col: MISSING<br>";
        }
    }
    
    // Test the main query from classifieds.php
    echo "<br>🧪 Testing main classifieds query:<br>";
    try {
        $stmt = $pdo->prepare("
            SELECT c.*, cc.name as category_name, cc.slug as category_slug, cc.icon as category_icon,
                   u.username as user_name
            FROM classifieds c
            LEFT JOIN classifieds_categories cc ON c.category_id = cc.id
            LEFT JOIN users u ON c.user_id = u.id
            WHERE c.is_active = 1 
            ORDER BY c.created_at DESC
            LIMIT 5
        ");
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "✅ Main query: SUCCESS (" . count($results) . " results)<br>";
        
        if (!empty($results)) {
            echo "📋 Sample result columns:<br>";
            $sample = $results[0];
            foreach ($sample as $key => $value) {
                echo "&nbsp;&nbsp;• $key: " . (is_null($value) ? 'NULL' : $value) . "<br>";
            }
        }
        
    } catch (PDOException $e) {
        echo "❌ Main query failed: " . $e->getMessage() . "<br>";
    }
    
    // Check for any existing classifieds
    $stmt = $pdo->query("SELECT COUNT(*) FROM classifieds");
    $count = $stmt->fetchColumn();
    echo "<br>📊 Total classifieds: $count<br>";
    
    if ($count > 0) {
        $stmt = $pdo->query("SELECT id, title, price, category_id FROM classifieds LIMIT 3");
        $sample = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "📋 Sample classifieds:<br>";
        foreach ($sample as $item) {
            echo "&nbsp;&nbsp;• ID: {$item['id']}, Title: {$item['title']}, Price: {$item['price']}, Category: {$item['category_id']}<br>";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Fatal Error: " . $e->getMessage() . "<br>";
}

echo "</div>";

echo "<br><h3>🔧 Next Steps:</h3>";
echo "<ol>";
echo "<li>If tables are missing, run the SQL setup scripts</li>";
echo "<li>If columns are missing, run the column addition scripts</li>";
echo "<li>If queries fail, check the error messages above</li>";
echo "</ol>";

echo "<br><a href='setup_free_stuff.html' style='background: #ffd700; color: #1a3353; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🔧 Go to Setup Guide</a>";
?> 