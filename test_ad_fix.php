<?php
/**
 * Test Script for Ad System Fix
 * This will test if the SQL parameter binding issue is resolved
 */

require_once 'config/config.php';
require_once 'includes/ad_renderer.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>Ad System Fix Test</title>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .success{color:green;} .error{color:red;} .info{color:blue;} .debug{background:#f0f0f0;padding:10px;margin:10px;border:1px solid #ccc;}</style>";
echo "</head><body>";
echo "<h1>🔧 Ad System Fix Test</h1>";

// Test 1: Basic renderAd function
echo "<h2>Test 1: Basic renderAd('header')</h2>";
try {
    $result = renderAd('header');
    if (strpos($result, 'DB error') !== false) {
        echo "<p class='error'>❌ Still getting database error: $result</p>";
    } else {
        echo "<p class='success'>✅ renderAd('header') executed successfully!</p>";
        echo "<div class='debug'>";
        echo "<strong>Result:</strong><br>";
        echo htmlspecialchars(substr($result, 0, 500)) . "...";
        echo "</div>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Exception: " . $e->getMessage() . "</p>";
}

// Test 2: Test with null parameters
echo "<h2>Test 2: renderAd with null parameters</h2>";
try {
    $result = renderAd('header', null, null);
    if (strpos($result, 'DB error') !== false) {
        echo "<p class='error'>❌ Still getting database error with null params: $result</p>";
    } else {
        echo "<p class='success'>✅ renderAd with null parameters works!</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Exception with null params: " . $e->getMessage() . "</p>";
}

// Test 3: Test multiple ads function
echo "<h2>Test 3: renderMultipleAds function</h2>";
try {
    $result = renderMultipleAds('header', 3, null, null);
    if (empty($result) && !is_array($result)) {
        echo "<p class='error'>❌ renderMultipleAds returned invalid result</p>";
    } else {
        echo "<p class='success'>✅ renderMultipleAds works! Found " . count($result) . " ads</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Exception in renderMultipleAds: " . $e->getMessage() . "</p>";
}

// Test 4: Check error logs
echo "<h2>Test 4: Recent Error Log Check</h2>";
$logFile = 'logs/php_errors.log';
if (file_exists($logFile)) {
    $recentLogs = file_get_contents($logFile);
    $adErrors = preg_grep('/Ad Debug.*error|Invalid parameter number/', explode("\n", $recentLogs));
    if (!empty($adErrors)) {
        echo "<p class='error'>❌ Found recent ad errors in log:</p>";
        echo "<div class='debug'>";
        foreach (array_slice($adErrors, -5) as $error) {
            echo htmlspecialchars($error) . "<br>";
        }
        echo "</div>";
    } else {
        echo "<p class='success'>✅ No recent ad errors found in log</p>";
    }
} else {
    echo "<p class='info'>ℹ️ No error log file found</p>";
}

echo "<h2>🎯 Summary</h2>";
echo "<p>If all tests show ✅, the ad system should now be working correctly!</p>";
echo "<p><a href='index.php?debug_ads=1' target='_blank'>Test on homepage with debug</a></p>";
echo "</body></html>";
?> 