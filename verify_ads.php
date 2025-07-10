<?php
/**
 * Ad System Verification Script
 * Tests each component step by step
 */

echo "<!DOCTYPE html>";
echo "<html><head><title>Ad System Verification</title>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .success{color:green;} .error{color:red;} .info{color:blue;} .debug{background:#f0f0f0;padding:10px;margin:10px;border:1px solid #ccc;}</style>";
echo "</head><body>";
echo "<h1>🔍 Ad System Verification</h1>";

// Step 1: Test config loading
echo "<h2>Step 1: Configuration Loading</h2>";
try {
    require_once 'config/config.php';
    echo "<p class='success'>✅ config.php loaded successfully</p>";
    echo "<p class='info'>APP_DEBUG: " . (defined('APP_DEBUG') ? (APP_DEBUG ? 'true' : 'false') : 'not defined') . "</p>";
} catch (Exception $e) {
    echo "<p class='error'>❌ config.php failed: " . $e->getMessage() . "</p>";
    exit;
}

// Step 2: Test database connection
echo "<h2>Step 2: Database Connection</h2>";
if (isset($pdo)) {
    echo "<p class='success'>✅ PDO connection available</p>";
    try {
        $test = $pdo->query("SELECT 1");
        echo "<p class='success'>✅ Database query successful</p>";
    } catch (Exception $e) {
        echo "<p class='error'>❌ Database query failed: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p class='error'>❌ PDO connection not available</p>";
}

// Step 3: Test ads table
echo "<h2>Step 3: Ads Table</h2>";
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'ads'");
    if ($stmt->rowCount() > 0) {
        echo "<p class='success'>✅ Ads table exists</p>";
        
        // Check table structure
        $stmt = $pdo->query("DESCRIBE ads");
        $columns = $stmt->fetchAll();
        echo "<p class='info'>📋 Ads table columns:</p><ul>";
        foreach ($columns as $col) {
            echo "<li>" . $col['Field'] . " (" . $col['Type'] . ")</li>";
        }
        echo "</ul>";
        
        // Count ads
        $stmt = $pdo->query("SELECT COUNT(*) FROM ads");
        $total = $stmt->fetchColumn();
        echo "<p class='info'>📊 Total ads: $total</p>";
        
    } else {
        echo "<p class='error'>❌ Ads table does not exist</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Error checking ads table: " . $e->getMessage() . "</p>";
}

// Step 4: Test ad renderer loading
echo "<h2>Step 4: Ad Renderer</h2>";
try {
    require_once 'includes/ad_renderer.php';
    echo "<p class='success'>✅ Ad renderer loaded</p>";
    
    if (function_exists('renderAd')) {
        echo "<p class='success'>✅ renderAd function exists</p>";
    } else {
        echo "<p class='error'>❌ renderAd function not found</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Ad renderer failed: " . $e->getMessage() . "</p>";
}

// Step 5: Test header ad rendering
echo "<h2>Step 5: Header Ad Rendering</h2>";
if (function_exists('renderAd')) {
    try {
        $headerAd = renderAd('header');
        echo "<p class='success'>✅ Header ad rendered successfully</p>";
        echo "<div class='debug'>";
        echo "<h3>Rendered Ad HTML:</h3>";
        echo htmlspecialchars($headerAd);
        echo "</div>";
    } catch (Exception $e) {
        echo "<p class='error'>❌ Header ad rendering failed: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p class='error'>❌ Cannot test rendering - renderAd function not available</p>";
}

// Step 6: Test header partial inclusion
echo "<h2>Step 6: Header Partial Inclusion</h2>";
try {
    $partialPath = $_SERVER['DOCUMENT_ROOT'] . '/partials/ads/header_ad.php';
    if (file_exists($partialPath)) {
        echo "<p class='success'>✅ Header ad partial exists at: $partialPath</p>";
        
        // Test including it
        ob_start();
        include $partialPath;
        $includedContent = ob_get_clean();
        
        echo "<p class='success'>✅ Header ad partial included successfully</p>";
        echo "<div class='debug'>";
        echo "<h3>Included Content:</h3>";
        echo htmlspecialchars($includedContent);
        echo "</div>";
    } else {
        echo "<p class='error'>❌ Header ad partial not found at: $partialPath</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Header ad partial inclusion failed: " . $e->getMessage() . "</p>";
}

echo "<h2>🎯 Summary</h2>";
echo "<p>Visit <a href='index.php?debug_ads=1'>index.php?debug_ads=1</a> to see the ad system in action on the live site.</p>";
echo "</body></html>";
?> 