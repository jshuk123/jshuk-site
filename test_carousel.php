<?php
/**
 * Test Carousel System
 * This script checks if the carousel table exists and adds a sample ad
 */

require_once 'config/config.php';

echo "<h1>🔍 Carousel System Test</h1>";

// Check if table exists
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'carousel_ads'");
    $table_exists = $stmt->rowCount() > 0;
    
    if ($table_exists) {
        echo "✅ carousel_ads table exists<br>";
        
        // Count existing ads
        $stmt = $pdo->query("SELECT COUNT(*) FROM carousel_ads");
        $ad_count = $stmt->fetchColumn();
        echo "📊 Found {$ad_count} carousel ads<br>";
        
        // Show active ads
        $stmt = $pdo->query("SELECT COUNT(*) FROM carousel_ads WHERE active = 1");
        $active_count = $stmt->fetchColumn();
        echo "🟢 Active ads: {$active_count}<br>";
        
        if ($active_count == 0) {
            echo "<br>⚠️ No active ads found. Adding a sample ad...<br>";
            
            // Create sample image directory if it doesn't exist
            $upload_dir = 'uploads/carousel/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
                echo "📁 Created uploads/carousel/ directory<br>";
            }
            
            // Create a sample ad with a placeholder image
            $sample_image_path = 'uploads/carousel/sample_ad.jpg';
            
            // Create a simple placeholder image using GD
            if (extension_loaded('gd')) {
                $width = 1920;
                $height = 600;
                $image = imagecreatetruecolor($width, $height);
                
                // Create gradient background
                for ($i = 0; $i < $height; $i++) {
                    $ratio = $i / $height;
                    $red = 102 + ($ratio * 50);
                    $green = 126 + ($ratio * 30);
                    $blue = 234 + ($ratio * 20);
                    $color = imagecolorallocate($image, $red, $green, $blue);
                    imageline($image, 0, $i, $width, $i, $color);
                }
                
                // Add text
                $text_color = imagecolorallocate($image, 255, 255, 255);
                $font_size = 48;
                $text = "Welcome to JShuk";
                $text2 = "Your Jewish Community Hub";
                
                // Calculate text position
                $bbox = imagettfbbox($font_size, 0, 'arial.ttf', $text);
                $text_width = $bbox[4] - $bbox[0];
                $text_x = ($width - $text_width) / 2;
                $text_y = $height / 2 - 30;
                
                // Add text to image
                imagestring($image, 5, $text_x, $text_y, $text, $text_color);
                imagestring($image, 3, $text_x, $text_y + 60, $text2, $text_color);
                
                // Save image
                imagejpeg($image, $sample_image_path, 90);
                imagedestroy($image);
                
                echo "🖼️ Created sample carousel image<br>";
            } else {
                // If GD not available, just create a text file as placeholder
                file_put_contents($sample_image_path . '.txt', 'Sample carousel image placeholder');
                echo "📝 Created placeholder file (GD not available)<br>";
            }
            
            // Insert sample ad into database
            $stmt = $pdo->prepare("
                INSERT INTO carousel_ads (
                    title, subtitle, image_path, cta_text, cta_url, 
                    active, position, created_at
                ) VALUES (?, ?, ?, ?, ?, 1, 1, NOW())
            ");
            
            $stmt->execute([
                'Welcome to JShuk',
                'Your Jewish Community Hub - Discover Local Businesses',
                $sample_image_path,
                'Explore Now',
                'businesses.php'
            ]);
            
            echo "✅ Sample carousel ad added successfully!<br>";
            echo "<br>🎉 Your carousel should now appear on the homepage!<br>";
            echo "<a href='index.php' style='color: blue; text-decoration: underline;'>← Go to Homepage</a><br>";
            
        } else {
            echo "<br>✅ Carousel is ready! You have {$active_count} active ads.<br>";
            echo "<a href='index.php' style='color: blue; text-decoration: underline;'>← Go to Homepage</a><br>";
        }
        
    } else {
        echo "❌ carousel_ads table does not exist<br>";
        echo "Please run the carousel manager first to create the table.<br>";
        echo "<a href='admin/carousel_manager.php' style='color: blue; text-decoration: underline;'>→ Go to Carousel Manager</a><br>";
    }
    
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

echo "<br><hr><br>";
echo "<h3>🔧 Debug Information:</h3>";
echo "Database connected: " . (isset($pdo) ? "Yes" : "No") . "<br>";
echo "Uploads directory exists: " . (is_dir('uploads/carousel/') ? "Yes" : "No") . "<br>";
echo "Uploads directory writable: " . (is_writable('uploads/carousel/') ? "Yes" : "No") . "<br>";
echo "GD extension loaded: " . (extension_loaded('gd') ? "Yes" : "No") . "<br>";
?> 