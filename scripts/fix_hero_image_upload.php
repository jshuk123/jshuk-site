<?php
/**
 * Fix Hero Image Upload
 * Helps diagnose and fix the hero background image issue
 */

echo "<h2>Hero Image Upload Fix</h2>";

// Check current image status
$image_path = '../images/hero-background.jpg';
$image_url = 'https://jshuk.com/images/hero-background.jpg';

echo "<h3>Current Status</h3>";
if (file_exists($image_path)) {
    $size = filesize($image_path);
    echo "📁 File exists: $image_path<br>";
    echo "📏 Size: " . number_format($size) . " bytes (" . number_format($size / 1024 / 1024, 2) . " MB)<br>";
    
    if ($size == 0) {
        echo "❌ <strong>PROBLEM: File is empty (0 bytes)</strong><br>";
        echo "This means the upload failed or the file is corrupted.<br><br>";
        
        echo "<h3>Solution Steps:</h3>";
        echo "<ol>";
        echo "<li><strong>Delete the empty file</strong> from your file manager</li>";
        echo "<li><strong>Re-upload the image</strong> with these requirements:</li>";
        echo "<ul>";
        echo "<li>File format: JPG, PNG, or WebP</li>";
        echo "<li>File size: Should be > 0 bytes (typically 100KB - 5MB)</li>";
        echo "<li>Upload location: /public_html/images/hero-background.jpg</li>";
        echo "<li>Make sure the upload completes fully</li>";
        echo "</ul>";
        echo "<li><strong>Verify the upload</strong> by checking file size in file manager</li>";
        echo "<li><strong>Test the image</strong> by visiting: <a href='$image_url' target='_blank'>$image_url</a></li>";
        echo "</ol>";
        
        echo "<h3>Alternative Solutions:</h3>";
        echo "<ul>";
        echo "<li><strong>Use a different filename:</strong> Try hero-bg.jpg or hero-background.png</li>";
        echo "<li><strong>Check file permissions:</strong> Make sure the file is readable (644)</li>";
        echo "<li><strong>Try a smaller image:</strong> Sometimes large files fail to upload properly</li>";
        echo "</ul>";
        
    } else {
        echo "✅ File has content<br>";
        
        // Check if it's a valid image
        $image_info = getimagesize($image_path);
        if ($image_info) {
            echo "✅ Valid image file<br>";
            echo "📐 Dimensions: " . $image_info[0] . "x" . $image_info[1] . "<br>";
            echo "📄 Type: " . $image_info['mime'] . "<br>";
        } else {
            echo "❌ Not a valid image file<br>";
            echo "The file has content but isn't a recognized image format.<br>";
        }
    }
} else {
    echo "❌ File does not exist<br>";
    echo "Upload the image to: $image_path<br>";
}

echo "<hr>";

echo "<h3>Test Current Background</h3>";
echo "<p>The hero section is currently using a temporary external image.</p>";
echo "<p>Once you fix the local image, we can switch back to using it.</p>";

echo "<h3>Quick Test</h3>";
echo "<p>Try accessing the image directly: <a href='$image_url' target='_blank'>$image_url</a></p>";
echo "<p>If it shows a broken image icon, the file needs to be re-uploaded.</p>";

echo "<h3>Next Steps</h3>";
echo "<ol>";
echo "<li>Re-upload the hero-background.jpg file</li>";
echo "<li>Verify it has content (> 0 bytes)</li>";
echo "<li>Test the direct URL</li>";
echo "<li>Run this script again to confirm it's working</li>";
echo "<li>We'll then switch back to using the local file</li>";
echo "</ol>";
?> 