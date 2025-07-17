<?php
/**
 * Setup Hero Image Script
 * This script helps set up the hero background image
 */

echo "<h1>Hero Image Setup</h1>";

echo "<h2>Instructions:</h2>";
echo "<ol>";
echo "<li>Save the network image as 'hero-background.jpg' in the /images/ directory</li>";
echo "<li>The image should be high resolution (recommended: 1920x1080 or higher)</li>";
echo "<li>The image should be in JPG format for optimal performance</li>";
echo "</ol>";

echo "<h2>Current Status:</h2>";

$image_path = '../images/hero-background.jpg';
if (file_exists($image_path)) {
    $file_size = filesize($image_path);
    $file_size_mb = round($file_size / 1024 / 1024, 2);
    
    echo "<p style='color: green;'>✅ Hero background image found!</p>";
    echo "<p>File size: {$file_size_mb} MB</p>";
    
    // Check image dimensions
    $image_info = getimagesize($image_path);
    if ($image_info) {
        $width = $image_info[0];
        $height = $image_info[1];
        echo "<p>Dimensions: {$width} x {$height} pixels</p>";
        
        if ($width >= 1920 && $height >= 1080) {
            echo "<p style='color: green;'>✅ Image resolution is good for hero background</p>";
        } else {
            echo "<p style='color: orange;'>⚠️ Consider using a higher resolution image (1920x1080 or higher)</p>";
        }
    }
} else {
    echo "<p style='color: red;'>❌ Hero background image not found</p>";
    echo "<p>Please save the network image as 'hero-background.jpg' in the /images/ directory</p>";
}

echo "<h2>Next Steps:</h2>";
echo "<p>Once the image is saved, you can:</p>";
echo "<ul>";
echo "<li><a href='../index.php'>View the homepage</a> to see the new hero background</a></li>";
echo "<li>The hero section will automatically use the new background image</li>";
echo "<li>The image will have a dark overlay to ensure text readability</li>";
echo "</ul>";

echo "<hr>";
echo "<p><a href='../index.php'>View Homepage</a></p>";
echo "<p><a href='../images/'>Open Images Directory</a></p>";
?> 