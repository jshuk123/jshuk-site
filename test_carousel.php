<?php
/**
 * Test Carousel Functionality
 * This page tests the carousel system to identify any issues
 */

require_once 'config/config.php';

echo "<h1>🎠 Carousel Test Page</h1>";
echo "<p>Testing carousel functionality...</p>";

// Test database connection
echo "<h2>Database Connection Test</h2>";
if (isset($pdo) && $pdo) {
    echo "✅ Database connection successful<br>";
    
    // Test carousel_slides table
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
            
            // Show sample slides
            $stmt = $pdo->query("SELECT * FROM carousel_slides ORDER BY priority DESC, created_at DESC LIMIT 5");
            $slides = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($slides)) {
                echo "<h3>Sample Slides:</h3>";
                foreach ($slides as $slide) {
                    echo "- {$slide['title']} (Priority: {$slide['priority']}, Active: " . ($slide['active'] ? 'Yes' : 'No') . ")<br>";
                }
            } else {
                echo "⚠️ No carousel slides found in database<br>";
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

// Test file system
echo "<h2>File System Test</h2>";
$upload_dir = 'uploads/carousel/';
if (is_dir($upload_dir)) {
    echo "✅ Carousel upload directory exists<br>";
    
    $files = scandir($upload_dir);
    $image_files = array_filter($files, function($file) {
        return in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp']);
    });
    
    echo "📁 Image files in carousel directory: " . count($image_files) . "<br>";
    if (!empty($image_files)) {
        foreach ($image_files as $file) {
            echo "- $file<br>";
        }
    }
} else {
    echo "❌ Carousel upload directory does not exist<br>";
}

// Test carousel section
echo "<h2>Carousel Section Test</h2>";
if (file_exists('sections/enhanced_carousel.php')) {
    echo "✅ enhanced_carousel.php section exists<br>";
    
    // Include the carousel section
    echo "<h3>Rendering Enhanced Carousel:</h3>";
    echo "<div style='border: 2px solid #ccc; padding: 20px; margin: 20px 0;'>";
    include 'sections/enhanced_carousel.php';
    echo "</div>";
} else {
    echo "❌ enhanced_carousel.php section not found<br>";
}

echo "<h2>JavaScript Test</h2>";
echo "<p>Check browser console for JavaScript logs...</p>";
?>

<script>
console.log('🔍 Carousel test page loaded');
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
    } else {
        console.log('❌ Enhanced carousel element not found');
    }
});
</script>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h1, h2, h3 { color: #333; }
</style> 