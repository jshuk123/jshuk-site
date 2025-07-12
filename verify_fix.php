<?php
// Use the same database connection as the main site
require_once 'config/config.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>Free Stuff System Verification</title>";
echo "<style>";
echo "body{font-family:Arial,sans-serif;margin:20px;background:#f5f5f5;}";
echo ".section{background:white;margin:10px 0;padding:15px;border-radius:5px;border-left:4px solid #28a745;}";
echo ".error{color:red;} .success{color:green;} .info{color:blue;}";
echo ".missing{color:orange;} .exists{color:green;}";
echo "</style>";
echo "</head><body>";

echo "<h2>🔍 Free Stuff System - Post-Fix Verification</h2>";

// Check if database connection is available
if (!$pdo) {
    echo "<div class='section'>";
    echo "<h3>❌ Database Connection Error</h3>";
    echo "<p>Database connection is not available. This could be due to:</p>";
    echo "<ul>";
    echo "<li>Missing database password in environment variables</li>";
    echo "<li>Database server is down</li>";
    echo "<li>Incorrect database credentials</li>";
    echo "</ul>";
    echo "<p>Please check your database configuration and try again.</p>";
    echo "</div>";
    echo "</body></html>";
    exit;
}

try {
    // Check if all required columns exist
    $columns = [
        'pickup_method',
        'collection_deadline', 
        'is_anonymous',
        'is_chessed',
        'is_bundle',
        'status',
        'pickup_code',
        'contact_method',
        'contact_info'
    ];
    
    $missing_columns = [];
    $existing_columns = [];
    
    foreach ($columns as $column) {
        // Use direct query instead of prepared statement for SHOW COLUMNS
        $stmt = $pdo->query("SHOW COLUMNS FROM classifieds LIKE '{$column}'");
        
        if ($stmt->rowCount() > 0) {
            $existing_columns[] = $column;
        } else {
            $missing_columns[] = $column;
        }
    }
    
    echo "<div class='section'>";
    echo "<h3>📋 Column Status:</h3>";
    
    if (empty($missing_columns)) {
        echo "<p class='success'>✅ All required columns are present!</p>";
        foreach ($existing_columns as $col) {
            echo "<p class='exists'>  • {$col}: ✅ EXISTS</p>";
        }
    } else {
        echo "<p class='error'>❌ Missing columns:</p>";
        foreach ($missing_columns as $col) {
            echo "<p class='missing'>  • {$col}: ❌ MISSING</p>";
        }
        echo "<p><strong>Please run the fix_missing_columns.sql script to add these columns.</strong></p>";
    }
    echo "</div>";
    
    // Test the main query
    echo "<div class='section'>";
    echo "<h3>🧪 Testing Main Query:</h3>";
    
    $query = "
        SELECT 
            c.*,
            cat.name as category_name,
            cat.slug as category_slug,
            cat.icon as category_icon,
            u.name as user_name
        FROM classifieds c
        LEFT JOIN classifieds_categories cat ON c.category_id = cat.id
        LEFT JOIN users u ON c.user_id = u.id
        WHERE c.is_active = 1
        ORDER BY c.created_at DESC
        LIMIT 5
    ";
    
    $stmt = $pdo->query($query);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($results) {
        echo "<p class='success'>✅ Main query successful! Found " . count($results) . " results</p>";
        
        // Show sample data
        $sample = $results[0];
        echo "<h4>📋 Sample Result:</h4>";
        echo "<ul>";
        foreach ($sample as $key => $value) {
            $value = $value ?: 'NULL';
            echo "<li><strong>{$key}:</strong> {$value}</li>";
        }
        echo "</ul>";
    } else {
        echo "<p class='error'>❌ Main query failed or returned no results</p>";
    }
    echo "</div>";
    
    // Test Free Stuff specific query
    echo "<div class='section'>";
    echo "<h3>🧪 Testing Free Stuff Query:</h3>";
    
    $free_stuff_query = "
        SELECT 
            c.*,
            cat.name as category_name,
            cat.slug as category_slug,
            cat.icon as category_icon,
            u.name as user_name
        FROM classifieds c
        LEFT JOIN classifieds_categories cat ON c.category_id = cat.id
        LEFT JOIN users u ON c.user_id = u.id
        WHERE c.is_active = 1 
        AND c.is_chessed = 1
        AND c.status = 'available'
        ORDER BY c.created_at DESC
        LIMIT 5
    ";
    
    $stmt = $pdo->query($free_stuff_query);
    $free_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p class='success'>✅ Free Stuff query successful! Found " . count($free_results) . " free items</p>";
    echo "</div>";
    
    // Final status
    echo "<div class='section'>";
    if (empty($missing_columns) && $results) {
        echo "<h3>🎉 SUCCESS!</h3>";
        echo "<p>All columns are present and queries are working. The Free Stuff system should now be fully functional!</p>";
        echo "<p><a href='classifieds.php?category=free-stuff' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🚀 Go to Free Stuff Section</a></p>";
    } else {
        echo "<h3>⚠️ ISSUES DETECTED</h3>";
        echo "<p>Please run the fix_missing_columns.sql script to add the missing columns.</p>";
        echo "<p><a href='sql/fix_missing_columns.sql' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>📄 View SQL Script</a></p>";
    }
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='section'>";
    echo "<h3>❌ ERROR:</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "</body></html>";
?> 