<?php
// Test script specifically for categories debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Categories Debug Test</h1>";

// Test database connection
require_once 'config/config.php';

if (isset($pdo) && $pdo) {
    echo "<p style='color:green'>✅ Database connection successful</p>";
    
    // Test if business_categories table exists
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'business_categories'");
        $table_exists = $stmt->fetch();
        if ($table_exists) {
            echo "<p style='color:green'>✅ business_categories table exists</p>";
        } else {
            echo "<p style='color:red'>❌ business_categories table does not exist</p>";
        }
    } catch (PDOException $e) {
        echo "<p style='color:red'>❌ Error checking table: " . $e->getMessage() . "</p>";
    }
    
    // Test basic categories query
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM business_categories");
        $count = $stmt->fetchColumn();
        echo "<p style='color:green'>✅ Total categories in table: " . $count . "</p>";
    } catch (PDOException $e) {
        echo "<p style='color:red'>❌ Basic categories query failed: " . $e->getMessage() . "</p>";
    }
    
    // Test the full categories query with business counts
    try {
        $categories_stmt = $pdo->query("
            SELECT c.id, c.name, c.icon, c.description, COUNT(b.id) AS business_count
            FROM business_categories c
            LEFT JOIN businesses b ON b.category_id = c.id AND b.status = 'active'
            GROUP BY c.id, c.name, c.icon, c.description
            ORDER BY business_count DESC, c.name ASC
        ");
        $categories_with_counts = $categories_stmt->fetchAll();
        echo "<p style='color:green'>✅ Full categories query successful: " . count($categories_with_counts) . " categories loaded</p>";
        
        if (!empty($categories_with_counts)) {
            echo "<h3>Categories found:</h3>";
            echo "<ul>";
            foreach (array_slice($categories_with_counts, 0, 10) as $cat) {
                echo "<li><strong>{$cat['name']}</strong> - {$cat['business_count']} businesses</li>";
            }
            echo "</ul>";
        } else {
            echo "<p style='color:orange'>⚠️ No categories returned from query</p>";
        }
    } catch (PDOException $e) {
        echo "<p style='color:red'>❌ Full categories query failed: " . $e->getMessage() . "</p>";
    }
    
    // Test if businesses table exists and has data
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'businesses'");
        $table_exists = $stmt->fetch();
        if ($table_exists) {
            echo "<p style='color:green'>✅ businesses table exists</p>";
            
            $stmt = $pdo->query("SELECT COUNT(*) FROM businesses WHERE status = 'active'");
            $active_businesses = $stmt->fetchColumn();
            echo "<p style='color:green'>✅ Active businesses: " . $active_businesses . "</p>";
        } else {
            echo "<p style='color:red'>❌ businesses table does not exist</p>";
        }
    } catch (PDOException $e) {
        echo "<p style='color:red'>❌ Error checking businesses table: " . $e->getMessage() . "</p>";
    }
    
} else {
    echo "<p style='color:red'>❌ Database connection failed</p>";
}

echo "<hr>";
echo "<p><a href='index.php'>Back to Homepage</a></p>";
echo "<p><a href='test_homepage_data.php'>Run Full Homepage Test</a></p>";
?> 