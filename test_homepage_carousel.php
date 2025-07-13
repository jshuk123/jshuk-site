<?php
/**
 * Test Homepage Carousel
 * This page tests the carousel functionality on the homepage
 */

require_once 'config/config.php';

echo "<h1>🏠 Homepage Carousel Test</h1>";
echo "<p>Testing the carousel functionality on the homepage...</p>";

// Test database connection
echo "<h2>Database Test</h2>";
if (isset($pdo) && $pdo) {
    echo "✅ Database connection successful<br>";
    
    // Check carousel_ads table
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'carousel_ads'");
        if ($stmt->rowCount() > 0) {
            echo "✅ carousel_ads table exists<br>";
            
            // Count ads
            $stmt = $pdo->query("SELECT COUNT(*) FROM carousel_ads");
            $total_ads = $stmt->fetchColumn();
            echo "📊 Total carousel ads: $total_ads<br>";
            
            // Get active ads
            $stmt = $pdo->query("SELECT COUNT(*) FROM carousel_ads WHERE active = 1");
            $active_ads = $stmt->fetchColumn();
            echo "✅ Active carousel ads: $active_ads<br>";
            
            if ($active_ads == 0) {
                echo "<p style='color: orange;'>⚠️ No active carousel ads found. The carousel will show a placeholder.</p>";
            }
        } else {
            echo "❌ carousel_ads table does not exist<br>";
        }
    } catch (PDOException $e) {
        echo "❌ Database error: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ Database connection failed<br>";
}

echo "<h2>Carousel Section Test</h2>";
if (file_exists('sections/carousel.php')) {
    echo "✅ carousel.php section exists<br>";
    
    // Include the carousel section
    echo "<h3>Rendering Carousel:</h3>";
    echo "<div style='border: 2px solid #ccc; padding: 20px; margin: 20px 0;'>";
    include 'sections/carousel.php';
    echo "</div>";
} else {
    echo "❌ carousel.php section not found<br>";
}

echo "<h2>Next Steps</h2>";
echo "<p>If the carousel is working above, you can:</p>";
echo "<ul>";
echo "<li><a href='admin/carousel_manager.php'>Manage Carousel Ads</a> - Add real carousel content</li>";
echo "<li><a href='index.php'>View Homepage</a> - See the carousel in action</li>";
echo "<li><a href='carousel_test.html'>Test Page</a> - Standalone carousel test</li>";
echo "</ul>";

echo "<h2>JavaScript Test</h2>";
echo "<p>Check browser console for JavaScript logs...</p>";
?>

<script>
console.log('🔍 Homepage carousel test loaded');
console.log('Swiper available:', typeof Swiper !== 'undefined');

if (typeof Swiper !== 'undefined') {
    console.log('✅ Swiper library is loaded');
} else {
    console.log('❌ Swiper library not found');
}

// Test carousel element
document.addEventListener('DOMContentLoaded', function() {
    const carousel = document.querySelector('.homepage-carousel');
    if (carousel) {
        console.log('✅ Carousel element found');
        console.log('Carousel slides:', carousel.querySelectorAll('.swiper-slide').length);
        
        // Check if Swiper is initialized
        setTimeout(() => {
            if (carousel.swiper) {
                console.log('✅ Swiper instance found on carousel');
                console.log('Active slide:', carousel.swiper.activeIndex);
            } else {
                console.log('⚠️ Swiper instance not found on carousel');
            }
        }, 1000);
    } else {
        console.log('❌ Carousel element not found');
    }
});
</script>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h1, h2, h3 { color: #333; }
a { color: #007bff; text-decoration: none; }
a:hover { text-decoration: underline; }
ul { margin: 10px 0; }
li { margin: 5px 0; }
</style> 