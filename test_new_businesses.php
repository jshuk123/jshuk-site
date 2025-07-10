<?php
// Test script specifically for new businesses debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>New Businesses Debug Test</h1>";

// Test database connection
require_once 'config/config.php';

if (isset($pdo) && $pdo) {
    echo "<p style='color:green'>✅ Database connection successful</p>";
    
    // Test if businesses table exists
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'businesses'");
        $table_exists = $stmt->fetch();
        if ($table_exists) {
            echo "<p style='color:green'>✅ businesses table exists</p>";
        } else {
            echo "<p style='color:red'>❌ businesses table does not exist</p>";
        }
    } catch (PDOException $e) {
        echo "<p style='color:red'>❌ Error checking table: " . $e->getMessage() . "</p>";
    }
    
    // Test total businesses count
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM businesses");
        $total_businesses = $stmt->fetchColumn();
        echo "<p style='color:green'>✅ Total businesses in table: " . $total_businesses . "</p>";
    } catch (PDOException $e) {
        echo "<p style='color:red'>❌ Basic businesses query failed: " . $e->getMessage() . "</p>";
    }
    
    // Test active businesses count
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM businesses WHERE status = 'active'");
        $stmt->execute();
        $active_businesses = $stmt->fetchColumn();
        echo "<p style='color:green'>✅ Active businesses: " . $active_businesses . "</p>";
    } catch (PDOException $e) {
        echo "<p style='color:red'>❌ Active businesses query failed: " . $e->getMessage() . "</p>";
    }
    
    // Test all status values
    try {
        $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM businesses GROUP BY status");
        $status_counts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<p style='color:blue'>📊 Business status breakdown:</p>";
        echo "<ul>";
        foreach ($status_counts as $status) {
            echo "<li><strong>{$status['status']}</strong>: {$status['count']} businesses</li>";
        }
        echo "</ul>";
    } catch (PDOException $e) {
        echo "<p style='color:red'>❌ Status breakdown query failed: " . $e->getMessage() . "</p>";
    }
    
    // Test the full new businesses query
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
        echo "<p style='color:green'>✅ New businesses query successful: " . count($newBusinesses) . " businesses loaded</p>";
        
        if (!empty($newBusinesses)) {
            echo "<h3>New businesses found:</h3>";
            echo "<ul>";
            foreach ($newBusinesses as $biz) {
                echo "<li><strong>{$biz['business_name']}</strong> - Created: {$biz['created_at']} - Category: {$biz['category_name']}</li>";
            }
            echo "</ul>";
        } else {
            echo "<p style='color:orange'>⚠️ No new businesses returned from query</p>";
            
            // Let's try without the status filter to see if there are any businesses at all
            echo "<h3>Testing without status filter:</h3>";
            $stmt = $pdo->prepare("
                SELECT b.id, b.business_name, b.status, b.created_at, c.name AS category_name
                FROM businesses b
                LEFT JOIN business_categories c ON b.category_id = c.id
                ORDER BY b.created_at DESC
                LIMIT 6
            ");
            $stmt->execute();
            $all_businesses = $stmt->fetchAll();
            
            if (!empty($all_businesses)) {
                echo "<p style='color:blue'>Found businesses with different statuses:</p>";
                echo "<ul>";
                foreach ($all_businesses as $biz) {
                    echo "<li><strong>{$biz['business_name']}</strong> - Status: {$biz['status']} - Created: {$biz['created_at']}</li>";
                }
                echo "</ul>";
            } else {
                echo "<p style='color:red'>❌ No businesses found in database at all</p>";
            }
        }
    } catch (PDOException $e) {
        echo "<p style='color:red'>❌ New businesses query failed: " . $e->getMessage() . "</p>";
    }
    
} else {
    echo "<p style='color:red'>❌ Database connection failed</p>";
}

echo "<hr>";
echo "<p><a href='index.php'>Back to Homepage</a></p>";
echo "<p><a href='test_homepage_data.php'>Run Full Homepage Test</a></p>";
?> 