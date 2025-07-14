<?php
/**
 * Test Database Fix
 * This script tests if the database is working correctly with the new carousel_slides table
 */

require_once 'config/config.php';

echo "<h1>🔧 Database Fix Test</h1>";
echo "<p>Testing if the database is working correctly with the new carousel_slides table...</p>";

try {
    // Test database connection
    if (isset($pdo) && $pdo) {
        echo "✅ Database connection successful<br>";
        
        // Test if carousel_slides table exists
        $stmt = $pdo->query("SHOW TABLES LIKE 'carousel_slides'");
        if ($stmt->rowCount() > 0) {
            echo "✅ carousel_slides table exists<br>";
            
            // Test querying the table
            $stmt = $pdo->query("SELECT COUNT(*) FROM carousel_slides");
            $total_slides = $stmt->fetchColumn();
            echo "📊 Total carousel slides: $total_slides<br>";
            
            // Test querying with active filter
            $stmt = $pdo->query("SELECT COUNT(*) FROM carousel_slides WHERE active = 1");
            $active_slides = $stmt->fetchColumn();
            echo "✅ Active carousel slides: $active_slides<br>";
            
            // Test a more complex query similar to what the API uses
            $stmt = $pdo->prepare("
                SELECT id, title, subtitle, image_url, cta_text, cta_link, priority, created_at
                FROM carousel_slides 
                WHERE active = 1 AND (start_date IS NULL OR start_date <= CURDATE()) AND (end_date IS NULL OR end_date >= CURDATE())
                ORDER BY priority DESC, sponsored DESC, created_at DESC
                LIMIT 10
            ");
            $stmt->execute();
            $slides = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "✅ Complex query successful - found " . count($slides) . " slides<br>";
            
            if (!empty($slides)) {
                echo "<h3>Sample Slides:</h3>";
                echo "<ul>";
                foreach ($slides as $slide) {
                    echo "<li>{$slide['title']} (Priority: {$slide['priority']})</li>";
                }
                echo "</ul>";
            }
            
            echo "<h2>🎉 Database Fix Successful!</h2>";
            echo "<p>The database is now working correctly with the new carousel_slides table.</p>";
            echo "<p><a href='index.php'>← Go to Homepage</a></p>";
            
        } else {
            echo "❌ carousel_slides table does not exist<br>";
            echo "<p>You may need to run the enhanced carousel setup script.</p>";
            echo "<p><a href='scripts/setup_carousel.php'>→ Run Setup Script</a></p>";
        }
        
    } else {
        echo "❌ Database connection failed<br>";
    }
    
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
    echo "<p>This indicates there's still an issue with the database structure.</p>";
}

echo "<h2>Next Steps</h2>";
echo "<ul>";
echo "<li>If the test passed, your carousel should now work correctly</li>";
echo "<li>If there are still errors, you may need to run the migration script</li>";
echo "<li>Check the browser console for any JavaScript errors</li>";
echo "</ul>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h1, h2, h3 { color: #333; }
a { color: #007bff; text-decoration: none; }
a:hover { text-decoration: underline; }
ul { margin: 10px 0; }
li { margin: 5px 0; }
</style> 