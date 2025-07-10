<?php
/**
 * Simple Ad System Test Script
 * Use this to test if ads are working correctly
 */

require_once 'config/config.php';
require_once 'includes/ad_renderer.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>Ad System Test</title></head><body>";
echo "<h1>Ad System Test</h1>";

// Test database connection
try {
    $test = $pdo->query("SELECT 1");
    echo "<p>✅ Database connection: OK</p>";
} catch (Exception $e) {
    echo "<p>❌ Database connection failed: " . $e->getMessage() . "</p>";
    exit;
}

// Check ads table
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'ads'");
    if ($stmt->rowCount() > 0) {
        echo "<p>✅ Ads table exists</p>";
        
        // Count total ads
        $stmt = $pdo->query("SELECT COUNT(*) FROM ads");
        $total = $stmt->fetchColumn();
        echo "<p>📊 Total ads in database: $total</p>";
        
        // Check for header ads
        $now = date('Y-m-d');
        $stmt = $pdo->prepare("SELECT * FROM ads WHERE zone = 'header' AND status = 'active' AND start_date <= ? AND end_date >= ?");
        $stmt->execute([$now, $now]);
        $headerAds = $stmt->fetchAll();
        echo "<p>🎯 Header ads matching criteria: " . count($headerAds) . "</p>";
        
        if (!empty($headerAds)) {
            echo "<p>📋 Header ad details:</p>";
            foreach ($headerAds as $ad) {
                echo "<ul>";
                echo "<li>ID: " . $ad['id'] . "</li>";
                echo "<li>Title: " . htmlspecialchars($ad['title'] ?? 'N/A') . "</li>";
                echo "<li>Status: " . htmlspecialchars($ad['status'] ?? 'N/A') . "</li>";
                echo "<li>Start Date: " . htmlspecialchars($ad['start_date'] ?? 'N/A') . "</li>";
                echo "<li>End Date: " . htmlspecialchars($ad['end_date'] ?? 'N/A') . "</li>";
                echo "<li>Image URL: " . htmlspecialchars($ad['image_url'] ?? 'N/A') . "</li>";
                echo "</ul>";
            }
        }
    } else {
        echo "<p>❌ Ads table does not exist</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Error checking ads table: " . $e->getMessage() . "</p>";
}

// Test renderAd function
echo "<h2>Testing renderAd('header') function:</h2>";
$headerAd = renderAd('header');
echo "<div style='border: 2px solid #007bff; padding: 10px; margin: 10px;'>";
echo $headerAd;
echo "</div>";

echo "<h2>Testing renderAd('sidebar') function:</h2>";
$sidebarAd = renderAd('sidebar');
echo "<div style='border: 2px solid #28a745; padding: 10px; margin: 10px;'>";
echo $sidebarAd;
echo "</div>";

echo "<h2>Testing renderAd('footer') function:</h2>";
$footerAd = renderAd('footer');
echo "<div style='border: 2px solid #dc3545; padding: 10px; margin: 10px;'>";
echo $footerAd;
echo "</div>";

echo "</body></html>";
?> 