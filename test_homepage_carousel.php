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
    
    // Check carousel_slides table
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'carousel_slides'");
        if ($stmt->rowCount() > 0) {
            echo "✅ carousel_slides table exists<br>";
            
            // Count slides
            $stmt = $pdo->query("SELECT COUNT(*) FROM carousel_slides");
            $total_slides = $stmt->fetchColumn();
            echo "📊 Total carousel slides: $total_slides<br>";
            
            // Get active slides
            $stmt = $pdo->query("SELECT COUNT(*) FROM carousel_slides WHERE active = 1");
            $active_slides = $stmt->fetchColumn();
            echo "✅ Active carousel slides: $active_slides<br>";
            
            if ($active_slides == 0) {
                echo "<p style='color: orange;'>⚠️ No active carousel slides found. The carousel will show a placeholder.</p>";
            }
        } else {
            echo "❌ carousel_slides table does not exist<br>";
        }
    } catch (PDOException $e) {
        echo "❌ Database error: " . $e->getMessage() . "<br>";
    }
} else {
    echo "❌ Database connection failed<br>";
}

echo "<h2>Carousel Section Test</h2>";
// Remove old carousel include
echo "<h3>Rendering Enhanced Carousel:</h3>";
$zone = 'homepage';
$location = null;
include 'sections/enhanced_carousel.php';

echo "<h2>Next Steps</h2>";
echo "<p>If the carousel is working above, you can:</p>";
echo "<ul>";
echo "<li><a href='admin/enhanced_carousel_manager.php'>Manage Carousel Slides</a> - Add real carousel content</li>";
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
    const carousel = document.querySelector('.enhanced-carousel');
    if (carousel) {
        console.log('✅ Enhanced carousel element found');
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
        console.log('❌ Enhanced carousel element not found');
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