<?php
/**
 * Final Ad System Test
 * This will verify that all the fixes are working correctly
 */

require_once 'config/config.php';
require_once 'includes/ad_renderer.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>Final Ad Test</title>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;background:#f5f5f5;} .section{background:white;margin:10px 0;padding:15px;border-radius:5px;border-left:4px solid #007bff;} .error{color:red;} .success{color:green;} .info{color:blue;} .debug{background:#f0f0f0;padding:10px;margin:10px;border:1px solid #ccc;} .ad-display{margin:20px 0;padding:20px;border:2px solid #007bff;background:#f8f9fa;border-radius:8px;}</style>";
echo "</head><body>";
echo "<h1>🎯 Final Ad System Test</h1>";

// Test 1: Basic renderAd function
echo "<div class='section'>";
echo "<h2>Test 1: renderAd('header')</h2>";
try {
    $result = renderAd('header');
    if (strpos($result, 'DB error') !== false) {
        echo "<p class='error'>❌ Still getting database error</p>";
        echo "<div class='debug'>" . htmlspecialchars($result) . "</div>";
    } else {
        echo "<p class='success'>✅ renderAd('header') executed successfully!</p>";
        echo "<div class='ad-display'>";
        echo "<h3>Ad Display:</h3>";
        echo $result;
        echo "</div>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Exception: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Test 2: renderMultipleAds function
echo "<div class='section'>";
echo "<h2>Test 2: renderMultipleAds('header', 3)</h2>";
try {
    $result = renderMultipleAds('header', 3);
    if (is_array($result)) {
        echo "<p class='success'>✅ renderMultipleAds works! Found " . count($result) . " ads</p>";
        if (!empty($result)) {
            echo "<div class='ad-display'>";
            echo "<h3>Multiple Ads Display:</h3>";
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

// Test 3: Check error logs for recent errors
echo "<div class='section'>";
echo "<h2>Test 3: Recent Error Log Check</h2>";
$logFile = 'logs/php_errors.log';
if (file_exists($logFile)) {
    $recentLogs = file_get_contents($logFile);
    $adErrors = preg_grep('/Ad Debug.*error|Invalid parameter number|🔥.*error/', explode("\n", $recentLogs));
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
echo "</div>";

// Test 4: Check recent success logs
echo "<div class='section'>";
echo "<h2>Test 4: Recent Success Log Check</h2>";
if (file_exists($logFile)) {
    $recentLogs = file_get_contents($logFile);
    $adSuccess = preg_grep('/🛠 Ad SQL|🛠 Params/', explode("\n", $recentLogs));
    if (!empty($adSuccess)) {
        echo "<p class='success'>✅ Found recent successful ad queries:</p>";
        echo "<div class='debug'>";
        foreach (array_slice($adSuccess, -3) as $success) {
            echo htmlspecialchars($success) . "<br>";
        }
        echo "</div>";
    } else {
        echo "<p class='info'>ℹ️ No recent successful ad queries found</p>";
    }
} else {
    echo "<p class='info'>ℹ️ No error log file found</p>";
}
echo "</div>";

echo "<div class='section'>";
echo "<h2>🎯 Final Summary</h2>";
echo "<p>If Test 1 shows ✅ and Test 3 shows ✅, the ad system is working correctly!</p>";
echo "<p><a href='index.php?debug_ads=1' target='_blank' style='background:#007bff;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>Test on Homepage</a></p>";
echo "</div>";

echo "</body></html>";
?> 