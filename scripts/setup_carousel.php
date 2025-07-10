<?php
/**
 * Complete Carousel Setup Script
 * This script sets up the entire carousel system:
 * 1. Creates the carousel_ads table
 * 2. Generates sample images
 * 3. Adds sample carousel ads
 * 4. Tests the carousel functionality
 */

require_once '../config/config.php';

echo "<h1>🚀 JShuk Carousel Setup</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: #28a745; }
    .error { color: #dc3545; }
    .warning { color: #ffc107; }
    .info { color: #17a2b8; }
    .step { background: #f8f9fa; padding: 10px; margin: 10px 0; border-left: 4px solid #007bff; }
</style>";

// Step 1: Check database connection
echo "<div class='step'><h3>Step 1: Database Connection</h3>";
if (isset($pdo) && $pdo) {
    try {
        $pdo->query('SELECT 1');
        echo "<p class='success'>✅ Database connection successful</p>";
    } catch (PDOException $e) {
        echo "<p class='error'>❌ Database connection failed: " . $e->getMessage() . "</p>";
        exit;
    }
} else {
    echo "<p class='error'>❌ Database connection not available</p>";
    exit;
}
echo "</div>";

// Step 2: Create carousel_ads table
echo "<div class='step'><h3>Step 2: Creating carousel_ads Table</h3>";
try {
    // Check if table already exists
    $table_check = $pdo->query("SHOW TABLES LIKE 'carousel_ads'");
    if ($table_check->rowCount() > 0) {
        echo "<p class='info'>ℹ️ carousel_ads table already exists</p>";
    } else {
        // Create the table
        $create_table_sql = "
        CREATE TABLE `carousel_ads` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `title` VARCHAR(100) NOT NULL,
            `subtitle` VARCHAR(255),
            `image_path` VARCHAR(255) NOT NULL,
            `cta_text` VARCHAR(50),
            `cta_url` VARCHAR(255),
            `active` BOOLEAN DEFAULT TRUE,
            `is_auto_generated` BOOLEAN DEFAULT FALSE,
            `business_id` INT,
            `position` INT DEFAULT 1,
            `expires_at` DATETIME,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            INDEX `idx_active_position` (`active`, `position`),
            INDEX `idx_business_id` (`business_id`),
            INDEX `idx_expires_at` (`expires_at`),
            INDEX `idx_created_at` (`created_at`),
            
            FOREIGN KEY (`business_id`) REFERENCES `businesses`(`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
        $pdo->exec($create_table_sql);
        echo "<p class='success'>✅ carousel_ads table created successfully</p>";
    }
} catch (PDOException $e) {
    echo "<p class='error'>❌ Error creating table: " . $e->getMessage() . "</p>";
    exit;
}
echo "</div>";

// Step 3: Create uploads directory
echo "<div class='step'><h3>Step 3: Creating Uploads Directory</h3>";
$upload_dir = '../uploads/carousel/';
if (!is_dir($upload_dir)) {
    if (mkdir($upload_dir, 0755, true)) {
        echo "<p class='success'>✅ Created uploads/carousel/ directory</p>";
    } else {
        echo "<p class='error'>❌ Failed to create uploads/carousel/ directory</p>";
    }
} else {
    echo "<p class='info'>ℹ️ uploads/carousel/ directory already exists</p>";
}
echo "</div>";

// Step 4: Generate sample images
echo "<div class='step'><h3>Step 4: Generating Sample Images</h3>";
if (extension_loaded('gd')) {
    $carousel_data = [
        [
            'filename' => 'sample_ad1.jpg',
            'title' => 'Welcome to JShuk',
            'subtitle' => 'Your Jewish Community Hub',
            'gradient' => ['#667eea', '#764ba2']
        ],
        [
            'filename' => 'sample_ad2.jpg',
            'title' => 'Kosher Restaurants',
            'subtitle' => 'Find the best kosher dining',
            'gradient' => ['#f093fb', '#f5576c']
        ],
        [
            'filename' => 'sample_ad3.jpg',
            'title' => 'Community Events',
            'subtitle' => 'Stay connected with your community',
            'gradient' => ['#4facfe', '#00f2fe']
        ]
    ];
    
    foreach ($carousel_data as $data) {
        $filepath = $upload_dir . $data['filename'];
        if (!file_exists($filepath)) {
            $width = 1920;
            $height = 600;
            $image = imagecreatetruecolor($width, $height);
            
            // Create gradient background
            $color1 = hex2rgb($data['gradient'][0]);
            $color2 = hex2rgb($data['gradient'][1]);
            
            for ($i = 0; $i < $height; $i++) {
                $ratio = $i / $height;
                $red = $color1[0] + ($ratio * ($color2[0] - $color1[0]));
                $green = $color1[1] + ($ratio * ($color2[1] - $color1[1]));
                $blue = $color1[2] + ($ratio * ($color2[2] - $color1[2]));
                $color = imagecolorallocate($image, $red, $green, $blue);
                imageline($image, 0, $i, $width, $i, $color);
            }
            
            // Add overlay for better text readability
            $overlay = imagecolorallocatealpha($image, 0, 0, 0, 80);
            imagefilledrectangle($image, 0, 0, $width, $height, $overlay);
            
            // Add text
            $text_color = imagecolorallocate($image, 255, 255, 255);
            
            // Main title
            $title = $data['title'];
            $title_x = ($width - strlen($title) * 20) / 2;
            $title_y = $height / 2 - 30;
            imagestring($image, 5, $title_x, $title_y, $title, $text_color);
            
            // Subtitle
            $subtitle = $data['subtitle'];
            $subtitle_x = ($width - strlen($subtitle) * 12) / 2;
            $subtitle_y = $title_y + 60;
            imagestring($image, 3, $subtitle_x, $subtitle_y, $subtitle, $text_color);
            
            // Save image
            imagejpeg($image, $filepath, 90);
            imagedestroy($image);
            
            echo "<p class='success'>✅ Created: {$data['filename']}</p>";
        } else {
            echo "<p class='info'>ℹ️ {$data['filename']} already exists</p>";
        }
    }
} else {
    echo "<p class='warning'>⚠️ GD extension not available. Creating placeholder files.</p>";
    $carousel_data = [
        ['filename' => 'sample_ad1.jpg', 'title' => 'Welcome to JShuk'],
        ['filename' => 'sample_ad2.jpg', 'title' => 'Kosher Restaurants'],
        ['filename' => 'sample_ad3.jpg', 'title' => 'Community Events']
    ];
    
    foreach ($carousel_data as $data) {
        $placeholder_content = "Sample carousel image: {$data['title']}\nGenerated on: " . date('Y-m-d H:i:s');
        file_put_contents($upload_dir . $data['filename'] . '.txt', $placeholder_content);
        echo "<p class='info'>ℹ️ Created placeholder: {$data['filename']}.txt</p>";
    }
}
echo "</div>";

// Step 5: Add sample carousel ads
echo "<div class='step'><h3>Step 5: Adding Sample Carousel Ads</h3>";
try {
    // Check if ads already exist
    $stmt = $pdo->query("SELECT COUNT(*) FROM carousel_ads");
    $existing_count = $stmt->fetchColumn();
    
    if ($existing_count == 0) {
        // Insert sample ads
        $sample_ads = [
            [
                'title' => 'Welcome to JShuk',
                'subtitle' => 'Your Jewish Community Hub - Discover Local Businesses',
                'image_path' => 'uploads/carousel/sample_ad1.jpg',
                'cta_text' => 'Explore Now',
                'cta_url' => 'businesses.php',
                'position' => 1
            ],
            [
                'title' => 'Kosher Restaurants',
                'subtitle' => 'Find the best kosher dining in your area',
                'image_path' => 'uploads/carousel/sample_ad2.jpg',
                'cta_text' => 'Find Restaurants',
                'cta_url' => 'businesses.php?category=restaurants',
                'position' => 2
            ],
            [
                'title' => 'Community Events',
                'subtitle' => 'Stay connected with your local Jewish community',
                'image_path' => 'uploads/carousel/sample_ad3.jpg',
                'cta_text' => 'View Events',
                'cta_url' => 'events.php',
                'position' => 3
            ]
        ];
        
        $stmt = $pdo->prepare("
            INSERT INTO carousel_ads (title, subtitle, image_path, cta_text, cta_url, active, position, created_at)
            VALUES (?, ?, ?, ?, ?, 1, ?, NOW())
        ");
        
        foreach ($sample_ads as $ad) {
            $stmt->execute([
                $ad['title'],
                $ad['subtitle'],
                $ad['image_path'],
                $ad['cta_text'],
                $ad['cta_url'],
                $ad['position']
            ]);
        }
        
        echo "<p class='success'>✅ Added " . count($sample_ads) . " sample carousel ads</p>";
    } else {
        echo "<p class='info'>ℹ️ {$existing_count} carousel ads already exist</p>";
    }
} catch (PDOException $e) {
    echo "<p class='error'>❌ Error adding sample ads: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Step 6: Test carousel functionality
echo "<div class='step'><h3>Step 6: Testing Carousel Functionality</h3>";
try {
    // Count active ads
    $stmt = $pdo->query("SELECT COUNT(*) FROM carousel_ads WHERE active = 1");
    $active_count = $stmt->fetchColumn();
    
    if ($active_count > 0) {
        echo "<p class='success'>✅ Found {$active_count} active carousel ads</p>";
        
        // Show sample ad data
        $stmt = $pdo->query("SELECT title, image_path FROM carousel_ads WHERE active = 1 ORDER BY position LIMIT 3");
        $ads = $stmt->fetchAll();
        
        echo "<p class='info'>📋 Sample ads:</p><ul>";
        foreach ($ads as $ad) {
            echo "<li>{$ad['title']} - {$ad['image_path']}</li>";
        }
        echo "</ul>";
        
    } else {
        echo "<p class='warning'>⚠️ No active carousel ads found</p>";
    }
} catch (PDOException $e) {
    echo "<p class='error'>❌ Error testing carousel: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Step 7: Final status
echo "<div class='step'><h3>Step 7: Setup Complete</h3>";
echo "<p class='success'>🎉 Carousel setup completed successfully!</p>";
echo "<p class='info'>📝 Next steps:</p>";
echo "<ul>";
echo "<li>Visit your homepage to see the carousel in action</li>";
echo "<li>Open browser console to see carousel debugging information</li>";
echo "<li>Use the admin panel to manage carousel ads</li>";
echo "</ul>";
echo "<p><a href='../index.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🏠 Go to Homepage</a></p>";
echo "<p><a href='../admin/carousel_manager.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>⚙️ Manage Carousel</a></p>";
echo "</div>";

// Helper function to convert hex to RGB
function hex2rgb($hex) {
    $hex = str_replace('#', '', $hex);
    return [
        hexdec(substr($hex, 0, 2)),
        hexdec(substr($hex, 2, 2)),
        hexdec(substr($hex, 4, 2))
    ];
}
?> 