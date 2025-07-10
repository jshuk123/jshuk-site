<?php
// Comprehensive database diagnostic script
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Database Diagnostic Report</h1>";

// Test database connection
require_once 'config/config.php';

if (isset($pdo) && $pdo) {
    echo "<p style='color:green'>✅ Database connection successful</p>";
    
    // Check which database we're using
    try {
        $current_db = $pdo->query("SELECT DATABASE()")->fetchColumn();
        echo "<p style='color:blue'>📊 Currently using database: <strong>{$current_db}</strong></p>";
    } catch (PDOException $e) {
        echo "<p style='color:red'>❌ Could not determine database: " . $e->getMessage() . "</p>";
    }
    
    // Check all tables
    try {
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "<p style='color:green'>✅ Found " . count($tables) . " tables in database</p>";
        echo "<ul>";
        foreach ($tables as $table) {
            echo "<li>{$table}</li>";
        }
        echo "</ul>";
    } catch (PDOException $e) {
        echo "<p style='color:red'>❌ Error listing tables: " . $e->getMessage() . "</p>";
    }
    
    // Check businesses table specifically
    if (in_array('businesses', $tables)) {
        echo "<h2>Businesses Table Analysis</h2>";
        
        // Total count
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM businesses");
            $total = $stmt->fetchColumn();
            echo "<p style='color:green'>✅ Total businesses: <strong>{$total}</strong></p>";
        } catch (PDOException $e) {
            echo "<p style='color:red'>❌ Error counting businesses: " . $e->getMessage() . "</p>";
        }
        
        // Status breakdown
        try {
            $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM businesses GROUP BY status");
            $statuses = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "<p style='color:blue'>📊 Status breakdown:</p>";
            echo "<ul>";
            foreach ($statuses as $status) {
                echo "<li><strong>{$status['status']}</strong>: {$status['count']} businesses</li>";
            }
            echo "</ul>";
        } catch (PDOException $e) {
            echo "<p style='color:red'>❌ Error getting status breakdown: " . $e->getMessage() . "</p>";
        }
        
        // Sample businesses
        try {
            $stmt = $pdo->query("SELECT id, business_name, status, created_at FROM businesses ORDER BY created_at DESC LIMIT 5");
            $sample = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "<p style='color:blue'>📋 Sample businesses (most recent):</p>";
            if (!empty($sample)) {
                echo "<ul>";
                foreach ($sample as $biz) {
                    echo "<li><strong>{$biz['business_name']}</strong> - Status: {$biz['status']} - Created: {$biz['created_at']}</li>";
                }
                echo "</ul>";
            } else {
                echo "<p style='color:orange'>⚠️ No businesses found</p>";
            }
        } catch (PDOException $e) {
            echo "<p style='color:red'>❌ Error getting sample businesses: " . $e->getMessage() . "</p>";
        }
        
    } else {
        echo "<p style='color:red'>❌ businesses table does not exist!</p>";
    }
    
    // Check users table
    if (in_array('users', $tables)) {
        echo "<h2>Users Table Analysis</h2>";
        
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM users");
            $total_users = $stmt->fetchColumn();
            echo "<p style='color:green'>✅ Total users: <strong>{$total_users}</strong></p>";
            
            // Check subscription tiers
            $stmt = $pdo->query("SELECT subscription_tier, COUNT(*) as count FROM users GROUP BY subscription_tier");
            $tiers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "<p style='color:blue'>📊 Subscription tier breakdown:</p>";
            echo "<ul>";
            foreach ($tiers as $tier) {
                echo "<li><strong>{$tier['subscription_tier']}</strong>: {$tier['count']} users</li>";
            }
            echo "</ul>";
        } catch (PDOException $e) {
            echo "<p style='color:red'>❌ Error analyzing users: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p style='color:red'>❌ users table does not exist!</p>";
    }
    
    // Check business_categories table
    if (in_array('business_categories', $tables)) {
        echo "<h2>Categories Table Analysis</h2>";
        
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM business_categories");
            $total_cats = $stmt->fetchColumn();
            echo "<p style='color:green'>✅ Total categories: <strong>{$total_cats}</strong></p>";
            
            // Sample categories
            $stmt = $pdo->query("SELECT id, name, icon FROM business_categories ORDER BY name LIMIT 5");
            $sample_cats = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "<p style='color:blue'>📋 Sample categories:</p>";
            if (!empty($sample_cats)) {
                echo "<ul>";
                foreach ($sample_cats as $cat) {
                    echo "<li><strong>{$cat['name']}</strong> (ID: {$cat['id']})</li>";
                }
                echo "</ul>";
            } else {
                echo "<p style='color:orange'>⚠️ No categories found</p>";
            }
        } catch (PDOException $e) {
            echo "<p style='color:red'>❌ Error analyzing categories: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p style='color:red'>❌ business_categories table does not exist!</p>";
    }
    
    // Test the exact queries from index.php
    echo "<h2>Testing Index.php Queries</h2>";
    
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
        echo "<p style='color:green'>✅ New businesses query: <strong>" . count($newBusinesses) . "</strong> results</p>";
        
        if (!empty($newBusinesses)) {
            echo "<ul>";
            foreach ($newBusinesses as $biz) {
                echo "<li>{$biz['business_name']} - {$biz['category_name']}</li>";
            }
            echo "</ul>";
        }
    } catch (PDOException $e) {
        echo "<p style='color:red'>❌ New businesses query failed: " . $e->getMessage() . "</p>";
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
        echo "<p style='color:green'>✅ Featured businesses query: <strong>" . count($featured) . "</strong> results</p>";
        
        if (!empty($featured)) {
            echo "<ul>";
            foreach ($featured as $biz) {
                echo "<li>{$biz['business_name']} - {$biz['subscription_tier']}</li>";
            }
            echo "</ul>";
        }
    } catch (PDOException $e) {
        echo "<p style='color:red'>❌ Featured businesses query failed: " . $e->getMessage() . "</p>";
    }
    
} else {
    echo "<p style='color:red'>❌ Database connection failed</p>";
}

echo "<hr>";
echo "<p><a href='index.php'>Back to Homepage</a></p>";
echo "<p><a href='test_homepage_data.php'>Run Homepage Test</a></p>";
?> 