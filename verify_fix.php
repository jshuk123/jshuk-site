<?php
/**
 * Final Verification Test
 * This will confirm the renderAd fix is working correctly
 */

require_once 'config/config.php';
require_once 'includes/ad_renderer.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>Fix Verification</title>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;background:#f5f5f5;} .section{background:white;margin:10px 0;padding:15px;border-radius:5px;border-left:4px solid #28a745;} .error{color:red;} .success{color:green;} .info{color:blue;} .ad-display{margin:20px 0;padding:20px;border:2px solid #007bff;background:#f8f9fa;border-radius:8px;}</style>";
echo "</head><body>";
echo "<h1>✅ Fix Verification Test</h1>";

// Test 1: renderAd with null parameters
echo "<div class='section'>";
echo "<h2>Test 1: renderAd('header') with null parameters</h2>";
try {
    $result = renderAd('header', null, null);
    if (strpos($result, 'DB error') !== false) {
        echo "<p class='error'>❌ Still getting database error</p>";
        echo "<div class='info'>" . htmlspecialchars($result) . "</div>";
    } else {
        echo "<p class='success'>✅ renderAd('header') works perfectly!</p>";
        echo "<div class='ad-display'>";
        echo "<h3>Ad Display:</h3>";
        echo $result;
        echo "</div>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Exception: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Test 2: renderMultipleAds
echo "<div class='section'>";
echo "<h2>Test 2: renderMultipleAds('header', 3)</h2>";
try {
    $result = renderMultipleAds('header', 3, null, null);
    if (is_array($result)) {
        echo "<p class='success'>✅ renderMultipleAds works! Found " . count($result) . " ads</p>";
        if (!empty($result)) {
            echo "<div class='ad-display'>";
            echo "<h3>Multiple Ads:</h3>";
            foreach ($result as $index => $ad) {
                echo "<div style='margin:10px 0;'>";
                echo "<strong>Ad " . ($index + 1) . ":</strong><br>";
                echo $ad;
                echo "</div>";
            }
            echo "</div>";
        }
    } else {
        echo "<p class='error'>❌ renderMultipleAds returned invalid result</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Exception: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Test 3: Check recent logs for success
echo "<div class='section'>";
echo "<h2>Test 3: Recent Success Logs</h2>";
$logFile = 'logs/php_errors.log';
if (file_exists($logFile)) {
    $recentLogs = file_get_contents($logFile);
    $successLogs = preg_grep('/🛠 renderAd SQL|🛠 Multiple Ads SQL/', explode("\n", $recentLogs));
    if (!empty($successLogs)) {
        echo "<p class='success'>✅ Found successful SQL queries:</p>";
        echo "<div class='info'>";
        foreach (array_slice($successLogs, -3) as $log) {
            echo htmlspecialchars($log) . "<br>";
        }
        echo "</div>";
    } else {
        echo "<p class='info'>ℹ️ No recent successful SQL queries found</p>";
    }
} else {
    echo "<p class='info'>ℹ️ No error log file found</p>";
}
echo "</div>";

// Test 4: Check for any remaining errors
echo "<div class='section'>";
echo "<h2>Test 4: Error Check</h2>";
if (file_exists($logFile)) {
    $recentLogs = file_get_contents($logFile);
    $errorLogs = preg_grep('/Invalid parameter number|🔥.*error/', explode("\n", $recentLogs));
    if (!empty($errorLogs)) {
        echo "<p class='error'>❌ Found recent errors:</p>";
        echo "<div class='info'>";
        foreach (array_slice($errorLogs, -3) as $log) {
            echo htmlspecialchars($log) . "<br>";
        }
        echo "</div>";
    } else {
        echo "<p class='success'>✅ No recent errors found!</p>";
    }
} else {
    echo "<p class='info'>ℹ️ No error log file found</p>";
}
echo "</div>";

echo "<div class='section'>";
echo "<h2>🎯 Final Result</h2>";
echo "<p>If Test 1 shows ✅ and Test 4 shows ✅, the fix is working perfectly!</p>";
echo "<p><a href='index.php?debug_ads=1' target='_blank' style='background:#28a745;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>Test on Homepage</a></p>";
echo "</div>";

echo "</body></html>";
?> 