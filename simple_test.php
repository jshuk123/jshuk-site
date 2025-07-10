<?php
/**
 * Simple Test to Isolate the Issue
 */

require_once 'config/config.php';
require_once 'includes/ad_renderer.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>Simple Test</title>";
echo "<style>body{font-family:monospace;margin:20px;} .error{color:red;} .success{color:green;} pre{background:#f0f0f0;padding:10px;border-radius:5px;}</style>";
echo "</head><body>";
echo "<h1>🔍 Simple Test</h1>";

// Test 1: Check if function exists
echo "<h2>Test 1: Function Check</h2>";
if (function_exists('renderAd')) {
    echo "<p class='success'>✅ renderAd function exists</p>";
} else {
    echo "<p class='error'>❌ renderAd function does not exist</p>";
    exit;
}

// Test 2: Check function signature
echo "<h2>Test 2: Function Signature</h2>";
$reflection = new ReflectionFunction('renderAd');
$params = $reflection->getParameters();
echo "<p>Function has " . count($params) . " parameters:</p>";
foreach ($params as $param) {
    echo "<p>- " . $param->getName() . " (default: " . var_export($param->getDefaultValue(), true) . ")</p>";
}

// Test 3: Manual SQL test
echo "<h2>Test 3: Manual SQL Test</h2>";
try {
    $zone = 'header';
    $category_id = null;
    $location = null;
    $now = date('Y-m-d');

    $sql = "SELECT * FROM ads 
            WHERE zone = :zone 
              AND status = 'active' 
              AND start_date <= :now 
              AND end_date >= :now";
    $params = [':zone' => $zone, ':now' => $now];

    // Append category filter only if it's not null
    if (!is_null($category_id)) {
        $sql .= " AND (category_id = :cat OR category_id IS NULL)";
        $params[':cat'] = $category_id;
    }

    // Append location filter only if it's not null
    if (!is_null($location)) {
        $sql .= " AND (location = :loc OR location IS NULL)";
        $params[':loc'] = $location;
    }

    $sql .= " ORDER BY priority DESC LIMIT 1";

    echo "<p><strong>Generated SQL:</strong></p>";
    echo "<pre>" . htmlspecialchars($sql) . "</pre>";
    
    echo "<p><strong>Parameters:</strong></p>";
    echo "<pre>" . htmlspecialchars(json_encode($params, JSON_PRETTY_PRINT)) . "</pre>";

    // Count placeholders
    preg_match_all('/:(\w+)/', $sql, $matches);
    $placeholders = $matches[1];
    echo "<p><strong>Placeholders found:</strong> " . implode(', ', $placeholders) . "</p>";
    echo "<p><strong>Parameters provided:</strong> " . implode(', ', array_keys($params)) . "</p>";

    if (count($placeholders) === count($params) && empty(array_diff($placeholders, array_keys($params)))) {
        echo "<p class='success'>✅ Placeholders and parameters match!</p>";
    } else {
        echo "<p class='error'>❌ MISMATCH!</p>";
    }

    // Try to execute
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

// Test 4: Call the actual function
echo "<h2>Test 4: Actual Function Call</h2>";
try {
    $result = renderAd('header');
    echo "<p class='success'>✅ Function executed without exception</p>";
    echo "<p><strong>Result:</strong></p>";
    echo "<pre>" . htmlspecialchars($result) . "</pre>";
} catch (Exception $e) {
    echo "<p class='error'>❌ Function exception: " . $e->getMessage() . "</p>";
}

echo "</body></html>";
?> 