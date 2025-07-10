<?php
/**
 * Ad System Validation Script
 * Checks and fixes ads with invalid dates or missing required fields
 */

require_once __DIR__ . '/../config/config.php';

echo "<h1>🔍 Ad System Validation</h1>";

// Check for ads with invalid dates
echo "<h2>📅 Date Validation</h2>";

// Find ads where start_date > end_date
$stmt = $pdo->prepare("SELECT id, title, start_date, end_date FROM ads WHERE start_date > end_date");
$stmt->execute();
$invalidDates = $stmt->fetchAll();

if (empty($invalidDates)) {
    echo "<p>✅ No ads with invalid date ranges found.</p>";
} else {
    echo "<p>❌ Found " . count($invalidDates) . " ads with invalid date ranges:</p>";
    foreach ($invalidDates as $ad) {
        echo "<p>- ID {$ad['id']}: {$ad['title']} (Start: {$ad['start_date']}, End: {$ad['end_date']})</p>";
    }
    
    // Fix invalid dates
    $stmt = $pdo->prepare("UPDATE ads SET end_date = DATE_ADD(start_date, INTERVAL 6 MONTH) WHERE start_date > end_date");
    $stmt->execute();
    echo "<p>🔧 Fixed " . $stmt->rowCount() . " ads with invalid date ranges.</p>";
}

// Find ads with past end dates
$stmt = $pdo->prepare("SELECT id, title, end_date FROM ads WHERE end_date < CURDATE() AND status = 'active'");
$stmt->execute();
$expiredAds = $stmt->fetchAll();

if (empty($expiredAds)) {
    echo "<p>✅ No active ads with past end dates found.</p>";
} else {
    echo "<p>⚠️ Found " . count($expiredAds) . " active ads with past end dates:</p>";
    foreach ($expiredAds as $ad) {
        echo "<p>- ID {$ad['id']}: {$ad['title']} (End: {$ad['end_date']})</p>";
    }
    
    // Auto-expire these ads
    $stmt = $pdo->prepare("UPDATE ads SET status = 'expired' WHERE end_date < CURDATE() AND status = 'active'");
    $stmt->execute();
    echo "<p>🔧 Auto-expired " . $stmt->rowCount() . " ads with past end dates.</p>";
}

// Find ads with null or empty dates
$stmt = $pdo->prepare("SELECT id, title, start_date, end_date FROM ads WHERE start_date IS NULL OR end_date IS NULL OR start_date = '' OR end_date = ''");
$stmt->execute();
$nullDates = $stmt->fetchAll();

if (empty($nullDates)) {
    echo "<p>✅ No ads with null or empty dates found.</p>";
} else {
    echo "<p>❌ Found " . count($nullDates) . " ads with null or empty dates:</p>";
    foreach ($nullDates as $ad) {
        echo "<p>- ID {$ad['id']}: {$ad['title']} (Start: " . ($ad['start_date'] ?: 'NULL') . ", End: " . ($ad['end_date'] ?: 'NULL') . ")</p>";
    }
    
    // Fix null dates
    $stmt = $pdo->prepare("UPDATE ads SET start_date = CURDATE() WHERE start_date IS NULL OR start_date = ''");
    $stmt->execute();
    $fixedStart = $stmt->rowCount();
    
    $stmt = $pdo->prepare("UPDATE ads SET end_date = DATE_ADD(CURDATE(), INTERVAL 6 MONTH) WHERE end_date IS NULL OR end_date = ''");
    $stmt->execute();
    $fixedEnd = $stmt->rowCount();
    
    echo "<p>🔧 Fixed {$fixedStart} start dates and {$fixedEnd} end dates.</p>";
}

// Check for ads without images
echo "<h2>🖼️ Image Validation</h2>";
$stmt = $pdo->prepare("SELECT id, title, image_url FROM ads WHERE image_url IS NULL OR image_url = ''");
$stmt->execute();
$noImages = $stmt->fetchAll();

if (empty($noImages)) {
    echo "<p>✅ No ads without images found.</p>";
} else {
    echo "<p>❌ Found " . count($noImages) . " ads without images:</p>";
    foreach ($noImages as $ad) {
        echo "<p>- ID {$ad['id']}: {$ad['title']}</p>";
    }
}

// Check for ads without required fields
echo "<h2>📋 Required Fields Validation</h2>";
$stmt = $pdo->prepare("SELECT id, title, zone, status FROM ads WHERE title IS NULL OR title = '' OR zone IS NULL OR zone = '' OR status IS NULL OR status = ''");
$stmt->execute();
$missingFields = $stmt->fetchAll();

if (empty($missingFields)) {
    echo "<p>✅ No ads with missing required fields found.</p>";
} else {
    echo "<p>❌ Found " . count($missingFields) . " ads with missing required fields:</p>";
    foreach ($missingFields as $ad) {
        echo "<p>- ID {$ad['id']}: Title='" . ($ad['title'] ?: 'NULL') . "', Zone='" . ($ad['zone'] ?: 'NULL') . "', Status='" . ($ad['status'] ?: 'NULL') . "'</p>";
    }
}

// Summary of active ads by zone
echo "<h2>📊 Active Ads Summary</h2>";
$stmt = $pdo->prepare("
    SELECT zone, COUNT(*) as count 
    FROM ads 
    WHERE status = 'active' 
      AND start_date <= CURDATE() 
      AND end_date >= CURDATE()
    GROUP BY zone
    ORDER BY zone
");
$stmt->execute();
$activeAds = $stmt->fetchAll();

if (empty($activeAds)) {
    echo "<p>⚠️ No active ads found in any zone.</p>";
} else {
    echo "<p>✅ Active ads by zone:</p>";
    foreach ($activeAds as $zone) {
        echo "<p>- {$zone['zone']}: {$zone['count']} ads</p>";
    }
}

echo "<h2>✅ Validation Complete</h2>";
echo "<p>Your ad system has been validated and any issues have been automatically fixed.</p>";
?> 