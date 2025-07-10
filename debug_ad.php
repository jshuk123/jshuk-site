<?php
/**
 * Debug Ad System
 * Comprehensive debugging for the ad rendering system
 */

if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'config/config.php';
require_once 'includes/ad_renderer.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Ad System Debug</h1>";

// Test database connection
try {
    $test = $pdo->query("SELECT 1");
    echo "<p>✅ Database connection: OK</p>";
} catch (Exception $e) {
    echo "<p>❌ Database connection failed: " . $e->getMessage() . "</p>";
    exit;
}

// Check if ads table exists
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'ads'");
    if ($stmt->rowCount() > 0) {
        echo "<p>✅ Ads table exists</p>";
    } else {
        echo "<p>❌ Ads table does not exist</p>";
        exit;
    }
} catch (Exception $e) {
    echo "<p>❌ Error checking ads table: " . $e->getMessage() . "</p>";
    exit;
}

// Check table structure
echo "<h2>Table Structure</h2>";
try {
    $stmt = $pdo->query("DESCRIBE ads");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($col['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "<p>❌ Error describing table: " . $e->getMessage() . "</p>";
}

// Check all ads in database
echo "<h2>All Ads in Database</h2>";
try {
    $stmt = $pdo->query("SELECT * FROM ads ORDER BY id DESC");
    $ads = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($ads)) {
        echo "<p>❌ No ads found in database</p>";
    } else {
        echo "<p>✅ Found " . count($ads) . " ads</p>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Zone</th><th>Status</th><th>Start Date</th><th>End Date</th><th>Title</th><th>Image URL</th></tr>";
        foreach ($ads as $ad) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($ad['id']) . "</td>";
            echo "<td>" . htmlspecialchars($ad['zone'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($ad['status'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($ad['start_date'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($ad['end_date'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($ad['title'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($ad['image_url'] ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p>❌ Error querying ads: " . $e->getMessage() . "</p>";
}

// Test renderAd function
echo "<h2>Testing renderAd Function</h2>";

// Test header zone
echo "<h3>Header Zone Test</h3>";
$headerAd = renderAd('header');
echo "<p>Header ad result:</p>";
echo "<div style='border: 1px solid #ccc; padding: 10px; background: #f9f9f9;'>";
echo htmlspecialchars($headerAd);
echo "</div>";

// Test sidebar zone
echo "<h3>Sidebar Zone Test</h3>";
$sidebarAd = renderAd('sidebar');
echo "<p>Sidebar ad result:</p>";
echo "<div style='border: 1px solid #ccc; padding: 10px; background: #f9f9f9;'>";
echo htmlspecialchars($sidebarAd);
echo "</div>";

// Test footer zone
echo "<h3>Footer Zone Test</h3>";
$footerAd = renderAd('footer');
echo "<p>Footer ad result:</p>";
echo "<div style='border: 1px solid #ccc; padding: 10px; background: #f9f9f9;'>";
echo htmlspecialchars($footerAd);
echo "</div>";

// Check image files
echo "<h2>Image File Check</h2>";
try {
    $stmt = $pdo->query("SELECT DISTINCT image_url FROM ads WHERE image_url IS NOT NULL AND image_url != ''");
    $images = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($images as $imageUrl) {
        if (!preg_match('/^https?:\/\//', $imageUrl)) {
            $localPath = 'uploads/ads/' . ltrim($imageUrl, '/');
            if (file_exists($localPath)) {
                echo "<p>✅ Image exists: $localPath</p>";
            } else {
                echo "<p>❌ Image missing: $localPath</p>";
            }
        } else {
            echo "<p>🌐 External image: $imageUrl</p>";
        }
    }
} catch (Exception $e) {
    echo "<p>❌ Error checking images: " . $e->getMessage() . "</p>";
}

// Check uploads directory
echo "<h2>Uploads Directory Check</h2>";
$uploadsDir = 'uploads/ads/';
if (is_dir($uploadsDir)) {
    echo "<p>✅ Uploads directory exists: $uploadsDir</p>";
    if (is_writable($uploadsDir)) {
        echo "<p>✅ Uploads directory is writable</p>";
    } else {
        echo "<p>❌ Uploads directory is not writable</p>";
    }
    
    $files = scandir($uploadsDir);
    echo "<p>Files in uploads directory:</p>";
    echo "<ul>";
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            echo "<li>" . htmlspecialchars($file) . "</li>";
        }
    }
    echo "</ul>";
} else {
    echo "<p>❌ Uploads directory does not exist: $uploadsDir</p>";
}

echo "<h2>Debug Complete</h2>";
echo "<p>Check your server's error log for additional debug information from the renderAd function.</p>";
?> 