<?php
/**
 * Fix Hero Image Script
 * This script helps diagnose and fix the hero background image issue
 */

echo "<h1>Hero Image Fix</h1>";

echo "<h2>Current Status:</h2>";

$image_path = '../images/hero-background.jpg';
if (file_exists($image_path)) {
    $file_size = filesize($image_path);
    $file_size_mb = round($file_size / 1024 / 1024, 2);
    
    // Check if it's actually an image file
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $image_path);
    finfo_close($finfo);
    
    if (strpos($mime_type, 'image/') === 0) {
        echo "<p style='color: green;'>✅ Hero background image found and is a valid image!</p>";
        echo "<p>File size: {$file_size_mb} MB</p>";
        echo "<p>MIME type: {$mime_type}</p>";
        
        // Check image dimensions
        $image_info = getimagesize($image_path);
        if ($image_info) {
            $width = $image_info[0];
            $height = $image_info[1];
            echo "<p>Dimensions: {$width} x {$height} pixels</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ File exists but is NOT a valid image file!</p>";
        echo "<p>MIME type: {$mime_type}</p>";
        echo "<p>This appears to be a text file, not an image.</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Hero background image not found</p>";
}

echo "<h2>How to Fix:</h2>";
echo "<ol>";
echo "<li><strong>Download your desired image</strong> (the network/connecting dots image)</li>";
echo "<li><strong>Save it as 'hero-background.jpg'</strong> in the /images/ directory</li>";
echo "<li><strong>Make sure it's a real image file</strong> (JPG, PNG, etc.)</li>";
echo "<li><strong>Recommended size:</strong> 1920x1080 pixels or larger</li>";
echo "</ol>";

echo "<h2>Alternative: Use External Image URL</h2>";
echo "<p>If you want to use an external image URL instead, I can update the CSS to use:</p>";
echo "<code>https://googleusercontent.com/image_generation_content/2</code>";

echo "<h2>Test the Fix:</h2>";
echo "<p>After saving the image, visit:</p>";
echo "<ul>";
echo "<li><a href='../index.php'>Homepage</a> - to see the hero background</li>";
echo "<li><a href='../businesses.php'>Businesses Page</a> - to see the enhanced cards</li>";
echo "</ul>";

echo "<hr>";
echo "<p><strong>Need help?</strong> Let me know if you want me to:</p>";
echo "<ul>";
echo "<li>Update the CSS to use an external image URL</li>";
echo "<li>Create a different placeholder image</li>";
echo "<li>Help with any other image-related issues</li>";
echo "</ul>";
?> 