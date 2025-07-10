<?php
/**
 * Debug SQL Generation
 * This will show exactly what SQL is being generated and what parameters are being passed
 */

require_once 'config/config.php';
require_once 'includes/ad_renderer.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>SQL Debug</title>";
echo "<style>body{font-family:monospace;margin:20px;background:#f5f5f5;} .section{background:white;margin:10px 0;padding:15px;border-radius:5px;} .error{color:red;} .success{color:green;} pre{background:#f0f0f0;padding:10px;border-radius:5px;overflow-x:auto;}</style>";
echo "</head><body>";
echo "<h1>🔍 SQL Debug</h1>";

// Test the exact SQL generation
echo "<h2>Testing renderAd('header')</h2>";

// Manually recreate the SQL generation to see what's happening
$zone = 'header';
$category_id = null;
$location = null;
$now = date('Y-m-d');

echo "<div class='section'>";
echo "<h3>Step 1: Initial SQL</h3>";
$sql = "SELECT * FROM ads 
        WHERE zone = :zone 
          AND status = 'active' 
          AND start_date <= :now 
          AND end_date >= :now";
$params = [':zone' => $zone, ':now' => $now];

echo "<p><strong>SQL:</strong></p>";
echo "<pre>" . htmlspecialchars($sql) . "</pre>";
echo "<p><strong>Params:</strong></p>";
echo "<pre>" . htmlspecialchars(json_encode($params, JSON_PRETTY_PRINT)) . "</pre>";

echo "<h3>Step 2: After category_id check</h3>";
echo "<p>category_id = " . var_export($category_id, true) . "</p>";
echo "<p>!is_null(category_id) = " . var_export(!is_null($category_id), true) . "</p>";

if (!is_null($category_id)) {
    $sql .= " AND (category_id = :cat OR category_id IS NULL)";
    $params[':cat'] = $category_id;
    echo "<p><strong>SQL after category_id:</strong></p>";
    echo "<pre>" . htmlspecialchars($sql) . "</pre>";
    echo "<p><strong>Params after category_id:</strong></p>";
    echo "<pre>" . htmlspecialchars(json_encode($params, JSON_PRETTY_PRINT)) . "</pre>";
} else {
    echo "<p class='success'>✅ category_id is null, no SQL addition</p>";
}

echo "<h3>Step 3: After location check</h3>";
echo "<p>location = " . var_export($location, true) . "</p>";
echo "<p>!is_null(location) = " . var_export(!is_null($location), true) . "</p>";

if (!is_null($location)) {
    $sql .= " AND (location = :loc OR location IS NULL)";
    $params[':loc'] = $location;
    echo "<p><strong>SQL after location:</strong></p>";
    echo "<pre>" . htmlspecialchars($sql) . "</pre>";
    echo "<p><strong>Params after location:</strong></p>";
    echo "<pre>" . htmlspecialchars(json_encode($params, JSON_PRETTY_PRINT)) . "</pre>";
} else {
    echo "<p class='success'>✅ location is null, no SQL addition</p>";
}

echo "<h3>Step 4: Final SQL</h3>";
$sql .= " ORDER BY priority DESC LIMIT 1";
echo "<p><strong>Final SQL:</strong></p>";
echo "<pre>" . htmlspecialchars($sql) . "</pre>";
echo "<p><strong>Final Params:</strong></p>";
echo "<pre>" . htmlspecialchars(json_encode($params, JSON_PRETTY_PRINT)) . "</pre>";

echo "<h3>Step 5: Count placeholders vs parameters</h3>";
$placeholders = [];
preg_match_all('/:(\w+)/', $sql, $matches);
$placeholders = $matches[1];

echo "<p><strong>Placeholders found in SQL:</strong></p>";
echo "<pre>" . htmlspecialchars(json_encode($placeholders, JSON_PRETTY_PRINT)) . "</pre>";

echo "<p><strong>Parameters provided:</strong></p>";
echo "<pre>" . htmlspecialchars(json_encode(array_keys($params), JSON_PRETTY_PRINT)) . "</pre>";

$missing = array_diff($placeholders, array_keys($params));
$extra = array_diff(array_keys($params), $placeholders);

if (empty($missing) && empty($extra)) {
    echo "<p class='success'>✅ Placeholders and parameters match perfectly!</p>";
} else {
    echo "<p class='error'>❌ MISMATCH FOUND!</p>";
    if (!empty($missing)) {
        echo "<p class='error'>Missing parameters: " . implode(', ', $missing) . "</p>";
    }
    if (!empty($extra)) {
        echo "<p class='error'>Extra parameters: " . implode(', ', $extra) . "</p>";
    }
}

echo "</div>";

// Now test the actual function
echo "<h2>Testing Actual renderAd Function</h2>";
echo "<div class='section'>";

try {
    $result = renderAd('header');
    echo "<p class='success'>✅ renderAd executed without exception</p>";
    echo "<p><strong>Result:</strong></p>";
    echo "<pre>" . htmlspecialchars(substr($result, 0, 1000)) . "</pre>";
} catch (Exception $e) {
    echo "<p class='error'>❌ Exception: " . $e->getMessage() . "</p>";
}

echo "</div>";

echo "</body></html>";
?> 