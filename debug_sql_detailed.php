<?php
/**
 * Detailed SQL Debug
 * This will show exactly what's happening with the SQL generation
 */

require_once 'config/config.php';
require_once 'includes/ad_renderer.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>Detailed SQL Debug</title>";
echo "<style>body{font-family:monospace;margin:20px;background:#f5f5f5;} .section{background:white;margin:10px 0;padding:15px;border-radius:5px;} .error{color:red;} .success{color:green;} .info{color:blue;} pre{background:#f0f0f0;padding:10px;border-radius:5px;overflow-x:auto;}</style>";
echo "</head><body>";
echo "<h1>🔍 Detailed SQL Debug</h1>";

// Test 1: Check function definition
echo "<div class='section'>";
echo "<h2>Test 1: Function Definition Check</h2>";
if (function_exists('renderAd')) {
    echo "<p class='success'>✅ renderAd function exists</p>";
    
    // Get function source
    $reflection = new ReflectionFunction('renderAd');
    $filename = $reflection->getFileName();
    $startLine = $reflection->getStartLine();
    $endLine = $reflection->getEndLine();
    
    echo "<p>Function defined in: $filename (lines $startLine-$endLine)</p>";
    
    // Read the actual function code
    $lines = file($filename);
    $functionCode = implode('', array_slice($lines, $startLine - 1, $endLine - $startLine + 1));
    
    echo "<p><strong>Current function code:</strong></p>";
    echo "<pre>" . htmlspecialchars($functionCode) . "</pre>";
    
} else {
    echo "<p class='error'>❌ renderAd function does not exist</p>";
}
echo "</div>";

// Test 2: Manual SQL recreation
echo "<div class='section'>";
echo "<h2>Test 2: Manual SQL Recreation</h2>";
try {
    $zone = 'header';
    $category_id = null;
    $location = null;
    $now = date('Y-m-d');

    echo "<p><strong>Input values:</strong></p>";
    echo "<ul>";
    echo "<li>zone: " . var_export($zone, true) . "</li>";
    echo "<li>category_id: " . var_export($category_id, true) . "</li>";
    echo "<li>location: " . var_export($location, true) . "</li>";
    echo "<li>now: " . var_export($now, true) . "</li>";
    echo "</ul>";

    $sql = "SELECT * FROM ads 
            WHERE zone = :zone 
              AND status = 'active' 
              AND start_date <= :now 
              AND end_date >= :now";

    $params = [':zone' => $zone, ':now' => $now];

    echo "<p><strong>Step 1 - Base SQL:</strong></p>";
    echo "<pre>" . htmlspecialchars($sql) . "</pre>";
    echo "<p><strong>Base Params:</strong></p>";
    echo "<pre>" . htmlspecialchars(json_encode($params, JSON_PRETTY_PRINT)) . "</pre>";

    // Only add filters if values are NOT null
    if (!is_null($category_id)) {
        $sql .= " AND (category_id = :cat OR category_id IS NULL)";
        $params[':cat'] = $category_id;
        echo "<p class='info'>✅ Added category filter</p>";
    } else {
        echo "<p class='info'>ℹ️ category_id is null, skipping category filter</p>";
    }

    if (!is_null($location)) {
        $sql .= " AND (location = :loc OR location IS NULL)";
        $params[':loc'] = $location;
        echo "<p class='info'>✅ Added location filter</p>";
    } else {
        echo "<p class='info'>ℹ️ location is null, skipping location filter</p>";
    }

    $sql .= " ORDER BY priority DESC LIMIT 1";

    echo "<p><strong>Step 2 - Final SQL:</strong></p>";
    echo "<pre>" . htmlspecialchars($sql) . "</pre>";
    echo "<p><strong>Final Params:</strong></p>";
    echo "<pre>" . htmlspecialchars(json_encode($params, JSON_PRETTY_PRINT)) . "</pre>";

    // Count placeholders
    preg_match_all('/:(\w+)/', $sql, $matches);
    $placeholders = $matches[1];
    echo "<p><strong>Placeholders found:</strong> " . implode(', ', $placeholders) . "</p>";
    echo "<p><strong>Parameters provided:</strong> " . implode(', ', array_keys($params)) . "</p>";

    if (count($placeholders) === count($params) && empty(array_diff($placeholders, array_keys($params)))) {
        echo "<p class='success'>✅ Placeholders and parameters match perfectly!</p>";
    } else {
        echo "<p class='error'>❌ MISMATCH!</p>";
        $missing = array_diff($placeholders, array_keys($params));
        $extra = array_diff(array_keys($params), $placeholders);
        if (!empty($missing)) {
            echo "<p class='error'>Missing parameters: " . implode(', ', $missing) . "</p>";
        }
        if (!empty($extra)) {
            echo "<p class='error'>Extra parameters: " . implode(', ', $extra) . "</p>";
        }
    }

    // Try to execute
    echo "<p><strong>Step 3 - Database Execution:</strong></p>";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $ad = $stmt->fetch();
    
    if ($ad) {
        echo "<p class='success'>✅ Query executed successfully! Found ad ID: " . $ad['id'] . "</p>";
    } else {
        echo "<p class='success'>✅ Query executed successfully! No ads found (this is normal)</p>";
    }

} catch (PDOException $e) {
    echo "<p class='error'>❌ Manual SQL error: " . $e->getMessage() . "</p>";
}
echo "</div>";

// Test 3: Call the actual function with detailed logging
echo "<div class='section'>";
echo "<h2>Test 3: Actual Function Call with Detailed Logging</h2>";

// Enable detailed error logging
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    echo "<p><strong>Calling renderAd('header', null, null):</strong></p>";
    $result = renderAd('header', null, null);
    
    echo "<p><strong>Function returned:</strong></p>";
    echo "<pre>" . htmlspecialchars($result) . "</pre>";
    
    if (strpos($result, 'DB error') !== false) {
        echo "<p class='error'>❌ Function returned database error</p>";
    } else {
        echo "<p class='success'>✅ Function executed without database error</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Function exception: " . $e->getMessage() . "</p>";
    echo "<p><strong>Stack trace:</strong></p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
echo "</div>";

echo "</body></html>";
?> 