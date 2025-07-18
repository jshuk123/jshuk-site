<?php
/**
 * Test Map Database Changes
 * 
 * This script verifies that the map coordinates database changes were applied successfully
 */

require_once 'config/config.php';

echo "<h1>🗺️ Map Database Test</h1>";
echo "<p>Testing if map coordinates database changes were applied successfully...</p>";

try {
    // Test 1: Check if latitude column exists
    echo "<h3>Test 1: Checking for latitude column</h3>";
    $stmt = $pdo->query("SHOW COLUMNS FROM businesses LIKE 'latitude'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color:green'>✅ Latitude column exists</p>";
    } else {
        echo "<p style='color:red'>❌ Latitude column missing</p>";
    }
    
    // Test 2: Check if longitude column exists
    echo "<h3>Test 2: Checking for longitude column</h3>";
    $stmt = $pdo->query("SHOW COLUMNS FROM businesses LIKE 'longitude'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color:green'>✅ Longitude column exists</p>";
    } else {
        echo "<p style='color:red'>❌ Longitude column missing</p>";
    }
    
    // Test 3: Check if geocoded column exists
    echo "<h3>Test 3: Checking for geocoded column</h3>";
    $stmt = $pdo->query("SHOW COLUMNS FROM businesses LIKE 'geocoded'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color:green'>✅ Geocoded column exists</p>";
    } else {
        echo "<p style='color:red'>❌ Geocoded column missing</p>";
    }
    
    // Test 4: Check for businesses with coordinates
    echo "<h3>Test 4: Checking businesses with coordinates</h3>";
    $stmt = $pdo->query("
        SELECT COUNT(*) as total_businesses,
               SUM(CASE WHEN latitude IS NOT NULL AND longitude IS NOT NULL THEN 1 ELSE 0 END) as with_coordinates,
               SUM(CASE WHEN geocoded = 1 THEN 1 ELSE 0 END) as geocoded_count
        FROM businesses 
        WHERE status = 'active'
    ");
    $stats = $stmt->fetch();
    
    echo "<p>📊 Business Statistics:</p>";
    echo "<ul>";
    echo "<li>Total active businesses: {$stats['total_businesses']}</li>";
    echo "<li>Businesses with coordinates: {$stats['with_coordinates']}</li>";
    echo "<li>Businesses marked as geocoded: {$stats['geocoded_count']}</li>";
    echo "</ul>";
    
    // Test 5: Show sample businesses with coordinates
    echo "<h3>Test 5: Sample businesses with coordinates</h3>";
    $stmt = $pdo->query("
        SELECT business_name, latitude, longitude, geocoded, status
        FROM businesses 
        WHERE latitude IS NOT NULL 
          AND longitude IS NOT NULL 
          AND status = 'active'
        LIMIT 5
    ");
    $businesses = $stmt->fetchAll();
    
    if (count($businesses) > 0) {
        echo "<p style='color:green'>✅ Found businesses with coordinates:</p>";
        echo "<ul>";
        foreach ($businesses as $business) {
            $geocoded_status = $business['geocoded'] ? '✓' : '⚠';
            echo "<li>{$business['business_name']}: ({$business['latitude']}, {$business['longitude']}) {$geocoded_status}</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color:orange'>⚠️ No businesses with coordinates found</p>";
    }
    
    // Test 6: Check if the view exists
    echo "<h3>Test 6: Checking businesses_with_coordinates view</h3>";
    $stmt = $pdo->query("SHOW TABLES LIKE 'businesses_with_coordinates'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color:green'>✅ businesses_with_coordinates view exists</p>";
        
        // Test the view
        $stmt = $pdo->query("SELECT COUNT(*) FROM businesses_with_coordinates");
        $view_count = $stmt->fetchColumn();
        echo "<p>View contains {$view_count} businesses with coordinates</p>";
    } else {
        echo "<p style='color:red'>❌ businesses_with_coordinates view missing</p>";
    }
    
    // Test 7: Check if distance function exists
    echo "<h3>Test 7: Checking distance calculation function</h3>";
    $stmt = $pdo->query("SHOW FUNCTION STATUS WHERE Name = 'calculate_distance'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color:green'>✅ calculate_distance function exists</p>";
        
        // Test the function
        $stmt = $pdo->query("SELECT calculate_distance(51.5074, -0.1278, 51.5074, -0.1278) as distance");
        $distance = $stmt->fetchColumn();
        echo "<p>Test distance calculation: {$distance} km (should be 0)</p>";
    } else {
        echo "<p style='color:red'>❌ calculate_distance function missing</p>";
    }
    
    echo "<h2>🎉 Test Complete!</h2>";
    
    if ($stats['with_coordinates'] > 0) {
        echo "<p style='color:green'>✅ Map system is ready! You can now:</p>";
        echo "<ul>";
        echo "<li><a href='businesses.php'>Visit businesses.php</a> to see the map in action</li>";
        echo "<li><a href='test_map_final.html'>Test the complete map system</a></li>";
        echo "<li>Try the Grid/Map view toggle on the businesses page</li>";
        echo "</ul>";
    } else {
        echo "<p style='color:orange'>⚠️ No businesses have coordinates yet. You may need to:</p>";
        echo "<ul>";
        echo "<li>Run the sample coordinates script</li>";
        echo "<li>Or add real coordinates to your businesses</li>";
        echo "</ul>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?> 