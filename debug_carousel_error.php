<?php
/**
 * Debug Carousel SQL Error
 * This script helps identify the exact SQL error in the combined carousel
 */

require_once 'config/config.php';

echo "<h1>🔍 Debugging Carousel SQL Error</h1>";

try {
    echo "<h2>Step 1: Check Database Connection</h2>";
    $pdo->query("SELECT 1");
    echo "<p style='color:green'>✅ Database connection successful</p>";
    
    echo "<h2>Step 2: Check if carousel_slides table exists</h2>";
    $tableExists = $pdo->query("SHOW TABLES LIKE 'carousel_slides'")->rowCount() > 0;
    if ($tableExists) {
        echo "<p style='color:green'>✅ carousel_slides table exists</p>";
        
        echo "<h3>Check table structure:</h3>";
        $columns = $pdo->query("SHOW COLUMNS FROM carousel_slides")->fetchAll(PDO::FETCH_ASSOC);
        echo "<ul>";
        foreach ($columns as $column) {
            echo "<li><strong>{$column['Field']}</strong> - {$column['Type']}</li>";
        }
        echo "</ul>";
        
        echo "<h3>Check for active slides:</h3>";
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM carousel_slides WHERE active = 1 AND zone = ?");
        $stmt->execute(['homepage']);
        $count = $stmt->fetchColumn();
        echo "<p>Active slides in homepage zone: <strong>{$count}</strong></p>";
        
    } else {
        echo "<p style='color:orange'>⚠️ carousel_slides table does not exist</p>";
    }
    
    echo "<h2>Step 3: Check businesses table structure</h2>";
    $businessColumns = $pdo->query("SHOW COLUMNS FROM businesses")->fetchAll(PDO::FETCH_ASSOC);
    $hasStatus = false;
    $hasUserId = false;
    foreach ($businessColumns as $column) {
        if ($column['Field'] === 'status') $hasStatus = true;
        if ($column['Field'] === 'user_id') $hasUserId = true;
    }
    echo "<p>Businesses table has 'status' column: " . ($hasStatus ? '✅' : '❌') . "</p>";
    echo "<p>Businesses table has 'user_id' column: " . ($hasUserId ? '✅' : '❌') . "</p>";
    
    echo "<h2>Step 4: Check users table structure</h2>";
    $userColumns = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_ASSOC);
    $hasSubscriptionTier = false;
    foreach ($userColumns as $column) {
        if ($column['Field'] === 'subscription_tier') $hasSubscriptionTier = true;
    }
    echo "<p>Users table has 'subscription_tier' column: " . ($hasSubscriptionTier ? '✅' : '❌') . "</p>";
    
    echo "<h2>Step 5: Test Query A (Sponsored Slides)</h2>";
    if ($tableExists) {
        try {
            $zone = 'homepage';
            $today = date('Y-m-d');
            
            $stmt = $pdo->prepare("
                SELECT 
                    id,
                    title,
                    subtitle,
                    image_url,
                    cta_text,
                    cta_link,
                    priority,
                    sponsored,
                    'carousel_slide' as slide_type
                FROM carousel_slides
                WHERE active = 1
                  AND zone = :zone
                  AND (start_date IS NULL OR start_date <= :today)
                  AND (end_date IS NULL OR end_date >= :today)
                ORDER BY priority DESC, id DESC
            ");
            $stmt->execute([':zone' => $zone, ':today' => $today]);
            $sponsored_slides = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "<p style='color:green'>✅ Query A successful - Found " . count($sponsored_slides) . " slides</p>";
        } catch (PDOException $e) {
            echo "<p style='color:red'>❌ Query A failed: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    } else {
        echo "<p style='color:orange'>⚠️ Skipping Query A - table doesn't exist</p>";
    }
    
    echo "<h2>Step 6: Test Query B (Featured Businesses)</h2>";
    try {
        $stmt = $pdo->prepare("
            SELECT 
                b.id,
                b.business_name as title,
                c.name as subtitle,
                COALESCE(bi.file_path, 'images/jshuk-logo.png') as image_url,
                'View Profile' as cta_text,
                CONCAT('business.php?id=', b.id) as cta_link,
                CASE 
                    WHEN u.subscription_tier = 'premium_plus' THEN 6
                    WHEN u.subscription_tier = 'premium' THEN 5
                    ELSE 4
                END as priority,
                1 as sponsored,
                'featured_business' as slide_type,
                u.subscription_tier
            FROM businesses b 
            LEFT JOIN business_categories c ON b.category_id = c.id 
            LEFT JOIN users u ON b.user_id = u.id
            LEFT JOIN business_images bi ON b.id = bi.business_id AND bi.sort_order = 0
            WHERE b.status = 'active' 
            AND u.subscription_tier IN ('premium', 'premium_plus')
            ORDER BY 
                CASE u.subscription_tier 
                    WHEN 'premium_plus' THEN 1 
                    WHEN 'premium' THEN 2 
                    ELSE 3 
                END,
                b.created_at DESC 
            LIMIT 10
        ");
        $stmt->execute();
        $featured_businesses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<p style='color:green'>✅ Query B successful - Found " . count($featured_businesses) . " businesses</p>";
    } catch (PDOException $e) {
        echo "<p style='color:red'>❌ Query B failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
    echo "<h2>Step 7: Check for Premium/Premium+ businesses</h2>";
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count, u.subscription_tier
            FROM businesses b 
            JOIN users u ON b.user_id = u.id
            WHERE b.status = 'active' 
            AND u.subscription_tier IN ('premium', 'premium_plus')
            GROUP BY u.subscription_tier
        ");
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($results)) {
            echo "<ul>";
            foreach ($results as $result) {
                echo "<li>{$result['subscription_tier']}: {$result['count']} businesses</li>";
            }
            echo "</ul>";
        } else {
            echo "<p style='color:orange'>⚠️ No Premium/Premium+ businesses found</p>";
        }
    } catch (PDOException $e) {
        echo "<p style='color:red'>❌ Count query failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color:red'>❌ Database connection failed: " . htmlspecialchars($e->getMessage()) . "</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>❌ General error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr>";
echo "<p><a href='index.php'>🏠 Back to Homepage</a> | <a href='test_combined_carousel.html'>🧪 Test Page</a></p>";
?> 