<?php
/**
 * Test Hero Background Image
 * Checks if the hero background image exists and is accessible
 */

echo "<h2>Hero Background Image Test</h2>";

// Test different possible paths
$possible_paths = [
    'images/hero-background.jpg',
    '../images/hero-background.jpg',
    '/images/hero-background.jpg',
    __DIR__ . '/../images/hero-background.jpg'
];

foreach ($possible_paths as $path) {
    echo "<h3>Testing path: $path</h3>";
    
    // Check if file exists
    if (file_exists($path)) {
        echo "✅ File exists<br>";
        
        // Get file info
        $file_info = pathinfo($path);
        $file_size = filesize($path);
        $file_time = filemtime($path);
        
        echo "📁 Directory: " . $file_info['dirname'] . "<br>";
        echo "📄 Filename: " . $file_info['basename'] . "<br>";
        echo "📏 Size: " . number_format($file_size / 1024 / 1024, 2) . " MB<br>";
        echo "🕒 Modified: " . date('Y-m-d H:i:s', $file_time) . "<br>";
        
        // Check if it's a valid image
        $image_info = getimagesize($path);
        if ($image_info) {
            echo "🖼️ Image type: " . $image_info['mime'] . "<br>";
            echo "📐 Dimensions: " . $image_info[0] . "x" . $image_info[1] . "<br>";
        } else {
            echo "❌ Not a valid image file<br>";
        }
        
        // Test if it's readable
        if (is_readable($path)) {
            echo "✅ File is readable<br>";
        } else {
            echo "❌ File is not readable<br>";
        }
        
    } else {
        echo "❌ File does not exist<br>";
    }
    
    echo "<hr>";
}

// Test web accessibility
echo "<h3>Web Accessibility Test</h3>";
$web_paths = [
    'https://jshuk.com/images/hero-background.jpg',
    'https://jshuk.com/images/hero-background.jpg?v=1'
];

foreach ($web_paths as $url) {
    echo "<h4>Testing URL: $url</h4>";
    
    $headers = get_headers($url, 1);
    if ($headers) {
        $status = $headers[0];
        echo "Status: $status<br>";
        
        if (strpos($status, '200') !== false) {
            echo "✅ Image is accessible via web<br>";
            
            // Get content length
            if (isset($headers['Content-Length'])) {
                $size = $headers['Content-Length'];
                echo "📏 Size: " . number_format($size / 1024 / 1024, 2) . " MB<br>";
            }
            
            // Get content type
            if (isset($headers['Content-Type'])) {
                echo "📄 Type: " . $headers['Content-Type'] . "<br>";
            }
        } else {
            echo "❌ Image is not accessible via web<br>";
        }
    } else {
        echo "❌ Could not access URL<br>";
    }
    
    echo "<hr>";
}

echo "<h3>Recommendations</h3>";
echo "<ul>";
echo "<li>If the file exists but web access fails, check file permissions</li>";
echo "<li>If the file doesn't exist, upload it to the correct location</li>";
echo "<li>Try clearing browser cache (Ctrl+F5)</li>";
echo "<li>Check if the image format is supported (JPG, PNG, etc.)</li>";
echo "</ul>";
?> 