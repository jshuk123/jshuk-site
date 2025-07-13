<?php
/**
 * Add Sample Carousel Ads
 * This script adds sample carousel ads to the database for testing
 */

require_once '../config/config.php';

echo "<h1>🎠 Adding Sample Carousel Ads</h1>";

try {
    // Check if table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'carousel_ads'");
    if ($stmt->rowCount() == 0) {
        echo "❌ carousel_ads table does not exist. Please run the carousel manager first.<br>";
        exit;
    }
    
    echo "✅ carousel_ads table exists<br>";
    
    // Check if we already have ads
    $stmt = $pdo->query("SELECT COUNT(*) FROM carousel_ads");
    $existing_count = $stmt->fetchColumn();
    
    if ($existing_count > 0) {
        echo "📊 Found {$existing_count} existing carousel ads<br>";
        echo "Skipping sample ads to avoid duplicates.<br>";
        echo "<a href='../index.php'>← Go to Homepage</a><br>";
        exit;
    }
    
    // Create sample ads
    $sample_ads = [
        [
            'title' => 'Welcome to JShuk',
            'subtitle' => 'Your Jewish Community Hub - Discover Local Businesses',
            'cta_text' => 'Explore Now',
            'cta_url' => 'businesses.php',
            'position' => 1,
            'active' => 1
        ],
        [
            'title' => 'Kosher Restaurants',
            'subtitle' => 'Find the best kosher dining in your area',
            'cta_text' => 'Find Restaurants',
            'cta_url' => 'businesses.php?category=restaurants',
            'position' => 2,
            'active' => 1
        ],
        [
            'title' => 'Community Events',
            'subtitle' => 'Stay connected with your local Jewish community',
            'cta_text' => 'View Events',
            'cta_url' => 'events.php',
            'position' => 3,
            'active' => 1
        ]
    ];
    
    // Create uploads directory if it doesn't exist
    $upload_dir = '../uploads/carousel/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
        echo "📁 Created uploads/carousel/ directory<br>";
    }
    
    // Create sample images using GD or SVG placeholders
    foreach ($sample_ads as $index => $ad) {
        $image_filename = 'sample_ad_' . ($index + 1) . '.jpg';
        $image_path = 'uploads/carousel/' . $image_filename;
        $full_path = '../' . $image_path;
        
        // Create a simple gradient image
        if (extension_loaded('gd')) {
            $width = 1920;
            $height = 600;
            $image = imagecreatetruecolor($width, $height);
            
            // Create gradient background
            $colors = [
                [102, 126, 234], // Blue
                [118, 75, 162],  // Purple
                [255, 107, 107]  // Red
            ];
            
            $color_index = $index % count($colors);
            $color1 = $colors[$color_index];
            $color2 = $colors[($color_index + 1) % count($colors)];
            
            for ($i = 0; $i < $height; $i++) {
                $ratio = $i / $height;
                $red = $color1[0] + ($ratio * ($color2[0] - $color1[0]));
                $green = $color1[1] + ($ratio * ($color2[1] - $color1[1]));
                $blue = $color1[2] + ($ratio * ($color2[2] - $color1[2]));
                $color = imagecolorallocate($image, $red, $green, $blue);
                imageline($image, 0, $i, $width, $i, $color);
            }
            
            // Add text
            $text_color = imagecolorallocate($image, 255, 255, 255);
            $title = $ad['title'];
            $subtitle = $ad['subtitle'];
            
            // Add title
            $font_size = 48;
            $text_x = $width / 2;
            $text_y = $height / 2 - 30;
            imagestring($image, 5, $text_x - (strlen($title) * 12), $text_y, $title, $text_color);
            
            // Add subtitle
            $font_size = 24;
            $text_y = $height / 2 + 30;
            imagestring($image, 3, $text_x - (strlen($subtitle) * 8), $text_y, $subtitle, $text_color);
            
            // Save image
            imagejpeg($image, $full_path, 90);
            imagedestroy($image);
            
            echo "🖼️ Created sample image: {$image_filename}<br>";
        } else {
            // Create SVG placeholder if GD not available
            $svg_content = '<svg width="1920" height="600" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <linearGradient id="grad' . $index . '" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" style="stop-color:#667eea;stop-opacity:1" />
                        <stop offset="100%" style="stop-color:#764ba2;stop-opacity:1" />
                    </linearGradient>
                </defs>
                <rect width="100%" height="100%" fill="url(#grad' . $index . ')"/>
                <text x="50%" y="45%" font-family="Arial, sans-serif" font-size="48" fill="white" text-anchor="middle">' . htmlspecialchars($ad['title']) . '</text>
                <text x="50%" y="55%" font-family="Arial, sans-serif" font-size="24" fill="white" text-anchor="middle">' . htmlspecialchars($ad['subtitle']) . '</text>
            </svg>';
            
            file_put_contents($full_path . '.svg', $svg_content);
            $image_path = 'uploads/carousel/' . $image_filename . '.svg';
            
            echo "🖼️ Created SVG placeholder: {$image_filename}.svg<br>";
        }
        
        // Insert into database
        $stmt = $pdo->prepare("
            INSERT INTO carousel_ads (title, subtitle, image_path, cta_text, cta_url, position, active, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $ad['title'],
            $ad['subtitle'],
            $image_path,
            $ad['cta_text'],
            $ad['cta_url'],
            $ad['position'],
            $ad['active']
        ]);
        
        echo "✅ Added carousel ad: {$ad['title']}<br>";
    }
    
    echo "<br>🎉 Successfully added " . count($sample_ads) . " sample carousel ads!<br>";
    echo "<a href='../index.php'>← Go to Homepage to see the carousel</a><br>";
    echo "<a href='../admin/carousel_manager.php'>→ Manage Carousel Ads</a><br>";
    
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h1 { color: #333; }
a { color: #007bff; text-decoration: none; }
a:hover { text-decoration: underline; }
</style> 