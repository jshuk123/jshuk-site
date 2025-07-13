<?php
require_once 'config/config.php';

echo "<h2>Carousel Image Path Debug</h2>";

// Get all carousel slides
try {
    $stmt = $pdo->query("SELECT id, title, image_url FROM carousel_slides ORDER BY id");
    $slides = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "<p style='color:red'>Database error: " . $e->getMessage() . "</p>";
    exit;
}

echo "<h3>Database Image URLs:</h3>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>ID</th><th>Title</th><th>Database URL</th><th>Full Server Path</th><th>File Exists</th><th>Web Accessible URL</th></tr>";

foreach ($slides as $slide) {
    $db_url = $slide['image_url'];
    $full_path = __DIR__ . '/' . $db_url;
    $file_exists = file_exists($full_path) ? '✅ Yes' : '❌ No';
    
    // Calculate web-accessible URL
    $web_url = 'https://jshuk.com/' . $db_url;
    
    echo "<tr>";
    echo "<td>" . $slide['id'] . "</td>";
    echo "<td>" . htmlspecialchars($slide['title']) . "</td>";
    echo "<td>" . htmlspecialchars($db_url) . "</td>";
    echo "<td>" . htmlspecialchars($full_path) . "</td>";
    echo "<td>" . $file_exists . "</td>";
    echo "<td><a href='" . htmlspecialchars($web_url) . "' target='_blank'>" . htmlspecialchars($web_url) . "</a></td>";
    echo "</tr>";
}
echo "</table>";

echo "<h3>Server Information:</h3>";
echo "<ul>";
echo "<li><strong>Document Root:</strong> " . $_SERVER['DOCUMENT_ROOT'] . "</li>";
echo "<li><strong>Script Path:</strong> " . __DIR__ . "</li>";
echo "<li><strong>Current URL:</strong> " . $_SERVER['REQUEST_URI'] . "</li>";
echo "<li><strong>Base URL:</strong> https://" . $_SERVER['HTTP_HOST'] . "</li>";
echo "</ul>";

echo "<h3>Test Image Access:</h3>";
echo "<p>Click these links to test if images are accessible via web:</p>";
echo "<ul>";

// Test a few specific images
$test_images = [
    'uploads/carousel/sample_ad1.jpg',
    'uploads/carousel/sample_ad2.jpg', 
    'uploads/carousel/sample_ad3.jpg',
    'uploads/carousel/carousel_1752424340_6873df948ff57.png'
];

foreach ($test_images as $img) {
    $web_url = 'https://jshuk.com/' . $img;
    $full_path = __DIR__ . '/' . $img;
    $exists = file_exists($full_path) ? '✅' : '❌';
    
    echo "<li>{$exists} <a href='" . htmlspecialchars($web_url) . "' target='_blank'>" . htmlspecialchars($img) . "</a></li>";
}
echo "</ul>";

echo "<h3>Directory Listing Test:</h3>";
$upload_dir = __DIR__ . '/uploads/carousel/';
if (is_dir($upload_dir)) {
    $files = scandir($upload_dir);
    echo "<ul>";
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            $file_path = $upload_dir . $file;
            $web_url = 'https://jshuk.com/uploads/carousel/' . $file;
            echo "<li><a href='" . htmlspecialchars($web_url) . "' target='_blank'>" . htmlspecialchars($file) . "</a></li>";
        }
    }
    echo "</ul>";
} else {
    echo "<p style='color:red'>Upload directory not found!</p>";
}
?> 