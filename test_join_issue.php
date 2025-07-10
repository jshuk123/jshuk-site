<?php
// Test script to isolate JOIN vs data issues
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>JOIN Logic Test</h1>";

require_once 'config/config.php';

if (isset($pdo) && $pdo) {
    echo "<p style='color:green'>✅ Database connection successful</p>";
    
    // Step 1: Check if there's any data at all
    echo "<h2>Step 1: Basic Data Check</h2>";
    try {
        $stmt = $pdo->query("SELECT id, business_name, created_at, status, user_id FROM businesses ORDER BY created_at DESC LIMIT 10");
        $basic_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<p style='color:green'>✅ Found " . count($basic_data) . " businesses in basic query</p>";
        
        if (!empty($basic_data)) {
            echo "<ul>";
            foreach ($basic_data as $biz) {
                echo "<li><strong>{$biz['business_name']}</strong> - Status: {$biz['status']} - User ID: {$biz['user_id']} - Created: {$biz['created_at']}</li>";
            }
            echo "</ul>";
            
            // Check if user_ids exist in users table
            $user_ids = array_column($basic_data, 'user_id');
            $user_ids = array_filter($user_ids); // Remove nulls
            if (!empty($user_ids)) {
                $placeholders = str_repeat('?,', count($user_ids) - 1) . '?';
                $stmt = $pdo->prepare("SELECT id, subscription_tier FROM users WHERE id IN ($placeholders)");
                $stmt->execute($user_ids);
                $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo "<p style='color:blue'>📊 Found " . count($users) . " matching users:</p>";
                echo "<ul>";
                foreach ($users as $user) {
                    echo "<li>User ID: {$user['id']} - Tier: {$user['subscription_tier']}</li>";
                }
                echo "</ul>";
            }
        }
    } catch (PDOException $e) {
        echo "<p style='color:red'>❌ Basic query failed: " . $e->getMessage() . "</p>";
    }
    
    // Step 2: Try the old query that worked (without JOINs)
    echo "<h2>Step 2: Old Query Test (No JOINs)</h2>";
    try {
        $stmt = $pdo->prepare("
            SELECT b.id, b.business_name, b.description
            FROM businesses b
            ORDER BY b.created_at DESC
            LIMIT 6
        ");
        $stmt->execute();
        $old_query_results = $stmt->fetchAll();
        echo "<p style='color:green'>✅ Old query (no JOINs): " . count($old_query_results) . " results</p>";
        
        if (!empty($old_query_results)) {
            echo "<ul>";
            foreach ($old_query_results as $biz) {
                echo "<li>{$biz['business_name']}</li>";
            }
            echo "</ul>";
        }
    } catch (PDOException $e) {
        echo "<p style='color:red'>❌ Old query failed: " . $e->getMessage() . "</p>";
    }
    
    // Step 3: Test current query with JOINs
    echo "<h2>Step 3: Current Query Test (With JOINs)</h2>";
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
        $current_query_results = $stmt->fetchAll();
        echo "<p style='color:green'>✅ Current query (with JOINs): " . count($current_query_results) . " results</p>";
        
        if (!empty($current_query_results)) {
            echo "<ul>";
            foreach ($current_query_results as $biz) {
                echo "<li>{$biz['business_name']} - Category: {$biz['category_name']} - Tier: {$biz['subscription_tier']}</li>";
            }
            echo "</ul>";
        }
    } catch (PDOException $e) {
        echo "<p style='color:red'>❌ Current query failed: " . $e->getMessage() . "</p>";
    }
    
    // Step 4: Test without status filter
    echo "<h2>Step 4: Test Without Status Filter</h2>";
    try {
        $stmt = $pdo->prepare("
            SELECT b.id, b.business_name, b.description, b.created_at, c.name AS category_name, u.subscription_tier, b.status
            FROM businesses b
            LEFT JOIN business_categories c ON b.category_id = c.id
            LEFT JOIN users u ON b.user_id = u.id
            ORDER BY b.created_at DESC
            LIMIT 6
        ");
        $stmt->execute();
        $no_status_results = $stmt->fetchAll();
        echo "<p style='color:green'>✅ Query without status filter: " . count($no_status_results) . " results</p>";
        
        if (!empty($no_status_results)) {
            echo "<ul>";
            foreach ($no_status_results as $biz) {
                echo "<li>{$biz['business_name']} - Status: {$biz['status']} - Category: {$biz['category_name']}</li>";
            }
            echo "</ul>";
        }
    } catch (PDOException $e) {
        echo "<p style='color:red'>❌ No status filter query failed: " . $e->getMessage() . "</p>";
    }
    
    // Step 5: Test without user JOIN
    echo "<h2>Step 5: Test Without User JOIN</h2>";
    try {
        $stmt = $pdo->prepare("
            SELECT b.id, b.business_name, b.description, b.created_at, c.name AS category_name
            FROM businesses b
            LEFT JOIN business_categories c ON b.category_id = c.id
            WHERE b.status = 'active'
            ORDER BY b.created_at DESC
            LIMIT 6
        ");
        $stmt->execute();
        $no_user_join_results = $stmt->fetchAll();
        echo "<p style='color:green'>✅ Query without user JOIN: " . count($no_user_join_results) . " results</p>";
        
        if (!empty($no_user_join_results)) {
            echo "<ul>";
            foreach ($no_user_join_results as $biz) {
                echo "<li>{$biz['business_name']} - Category: {$biz['category_name']}</li>";
            }
            echo "</ul>";
        }
    } catch (PDOException $e) {
        echo "<p style='color:red'>❌ No user JOIN query failed: " . $e->getMessage() . "</p>";
    }
    
} else {
    echo "<p style='color:red'>❌ Database connection failed</p>";
}

echo "<hr>";
echo "<p><a href='index.php'>Back to Homepage</a></p>";
echo "<p><a href='debug_database.php'>Full Database Diagnostic</a></p>";
?> 