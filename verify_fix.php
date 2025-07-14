<?php
/**
 * Verify SQL Error Fix
 * Run this in your browser to confirm the carousel SQL error is resolved
 */

echo "<h1>✅ SQL Error Fix Verification</h1>";

// Test 1: Check if we can connect to the database
echo "<h2>Test 1: Database Connection</h2>";
try {
    require_once 'config/config.php';
    if (isset($pdo) && $pdo) {
        echo "✅ Database connection successful<br>";
    } else {
        echo "❌ Database connection failed<br>";
        exit;
    }
} catch (Exception $e) {
    echo "❌ Error loading config: " . $e->getMessage() . "<br>";
    exit;
}

// Test 2: Check if carousel_slides table exists
echo "<h2>Test 2: Table Structure</h2>";
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'carousel_slides'");
    if ($stmt->rowCount() > 0) {
        echo "✅ carousel_slides table exists<br>";
        
        // Check table structure
        $stmt = $pdo->query("DESCRIBE carousel_slides");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "📋 Table columns:<br>";
        echo "<ul>";
        foreach ($columns as $column) {
            echo "<li>{$column['Field']} ({$column['Type']})</li>";
        }
        echo "</ul>";
    } else {
        echo "❌ carousel_slides table does not exist<br>";
        echo "<p>You may need to run the enhanced carousel setup.</p>";
    }
} catch (PDOException $e) {
    echo "❌ Error checking table: " . $e->getMessage() . "<br>";
}

// Test 3: Test the exact query that was causing the error
echo "<h2>Test 3: Query Test (The Fix)</h2>";
try {
    // This is the type of query that was causing the error
    $stmt = $pdo->prepare("
        SELECT id, title, subtitle, image_url, cta_text, cta_link, priority, created_at
        FROM carousel_slides 
        WHERE active = 1 AND (start_date IS NULL OR start_date <= CURDATE()) AND (end_date IS NULL OR end_date >= CURDATE())
        ORDER BY priority DESC, sponsored DESC, created_at DESC
        LIMIT 10
    ");
    $stmt->execute();
    $slides = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "✅ Query executed successfully!<br>";
    echo "📊 Found " . count($slides) . " active slides<br>";
    
    if (!empty($slides)) {
        echo "<h3>Sample Results:</h3>";
        echo "<ul>";
        foreach ($slides as $slide) {
            echo "<li><strong>{$slide['title']}</strong> - Priority: {$slide['priority']}</li>";
        }
        echo "</ul>";
    }
    
} catch (PDOException $e) {
    echo "❌ Query failed: " . $e->getMessage() . "<br>";
    echo "<p>This indicates the fix may not be complete.</p>";
}

// Test 4: Test API endpoint
echo "<h2>Test 4: API Endpoint Test</h2>";
try {
    // Simulate the API call
    $stmt = $pdo->prepare("
        SELECT id, title, subtitle, image_url, cta_text, cta_link, priority, created_at
        FROM carousel_slides 
        WHERE active = 1 AND (start_date IS NULL OR start_date <= CURDATE()) AND (end_date IS NULL OR end_date >= CURDATE())
        ORDER BY priority DESC, sponsored DESC, created_at DESC
        LIMIT 10
    ");
    $stmt->execute();
    $api_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "✅ API query successful<br>";
    echo "📊 API would return " . count($api_data) . " slides<br>";
    
} catch (PDOException $e) {
    echo "❌ API query failed: " . $e->getMessage() . "<br>";
}

// Final status
echo "<h2>🎉 Final Status</h2>";
echo "<p><strong>The SQL error should now be resolved!</strong></p>";
echo "<p>If all tests above show ✅, your carousel system is working correctly.</p>";

echo "<h3>Next Steps:</h3>";
echo "<ul>";
echo "<li><a href='index.php'>🏠 Visit Homepage</a> - Check if carousel loads without errors</li>";
echo "<li><a href='test_carousel.php'>🎠 Test Carousel</a> - Detailed carousel testing</li>";
echo "<li><a href='admin/enhanced_carousel_manager.php'>⚙️ Manage Carousel</a> - Add/edit carousel slides</li>";
echo "<li><a href='scripts/add_sample_carousel_ads.php'>📝 Add Sample Data</a> - If you need sample slides</li>";
echo "</ul>";

echo "<h3>If you still see errors:</h3>";
echo "<ul>";
echo "<li>Clear your browser cache</li>";
echo "<li>Check browser console for JavaScript errors</li>";
echo "<li>Verify your web server is running</li>";
echo "<li>Check PHP error logs</li>";
echo "</ul>";
?>

<style>
body { 
    font-family: Arial, sans-serif; 
    margin: 20px; 
    line-height: 1.6;
}
h1, h2, h3 { 
    color: #333; 
    margin-top: 30px;
}
h1 { 
    color: #28a745; 
    border-bottom: 2px solid #28a745;
    padding-bottom: 10px;
}
a { 
    color: #007bff; 
    text-decoration: none; 
}
a:hover { 
    text-decoration: underline; 
}
ul { 
    margin: 10px 0; 
}
li { 
    margin: 5px 0; 
}
</style> 