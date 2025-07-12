<?php
require_once 'config/db_connect.php';

echo "<h2>🔍 Free Stuff System - Post-Fix Verification</h2>";

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
        $stmt = $pdo->prepare("SHOW COLUMNS FROM classifieds LIKE ?");
        $stmt->execute([$column]);
        
        if ($stmt->rowCount() > 0) {
            $existing_columns[] = $column;
        } else {
            $missing_columns[] = $column;
        }
    }
    
    echo "<h3>📋 Column Status:</h3>";
    
    if (empty($missing_columns)) {
        echo "✅ All required columns are present!<br>";
        foreach ($existing_columns as $col) {
            echo "  • {$col}: ✅ EXISTS<br>";
        }
    } else {
        echo "❌ Missing columns:<br>";
        foreach ($missing_columns as $col) {
            echo "  • {$col}: ❌ MISSING<br>";
        }
    }
    
    // Test the main query
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
        echo "✅ Main query successful! Found " . count($results) . " results<br>";
        
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
        echo "❌ Main query failed or returned no results<br>";
    }
    
    // Test Free Stuff specific query
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
    
    echo "✅ Free Stuff query successful! Found " . count($free_results) . " free items<br>";
    
    if (empty($missing_columns) && $results) {
        echo "<h3>🎉 SUCCESS!</h3>";
        echo "<p>All columns are present and queries are working. The Free Stuff system should now be fully functional!</p>";
        echo "<p><a href='classifieds.php?category=free-stuff' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🚀 Go to Free Stuff Section</a></p>";
    } else {
        echo "<h3>⚠️ ISSUES DETECTED</h3>";
        echo "<p>Please run the fix_missing_columns.sql script again or contact support.</p>";
    }
    
} catch (Exception $e) {
    echo "<h3>❌ ERROR:</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?> 