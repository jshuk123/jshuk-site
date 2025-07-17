<?php
/**
 * Add Sample Locations Script
 * This script adds sample location data to existing businesses
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../config/config.php';

echo "<h1>Add Sample Locations</h1>";

try {
    // Get businesses without location data
    $stmt = $pdo->query("SELECT id, business_name, address FROM businesses WHERE status = 'active' AND (location IS NULL OR location = '')");
    $businesses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($businesses)) {
        echo "<p style='color:blue'>ℹ️ All businesses already have location data or no active businesses found.</p>";
        echo "<p><a href='../businesses.php'>View Businesses Page</a></p>";
        exit;
    }
    
    echo "<p style='color:green'>✅ Found " . count($businesses) . " businesses without location data</p>";
    
    // Sample locations for Jewish communities
    $sample_locations = [
        'Golders Green, London',
        'Hendon, London', 
        'Stamford Hill, London',
        'Edgware, London',
        'Manchester',
        'Gateshead',
        'Leeds',
        'Birmingham',
        'Liverpool',
        'Brighton'
    ];
    
    // Update businesses with sample locations
    $stmt = $pdo->prepare("UPDATE businesses SET location = ? WHERE id = ?");
    
    $updated = 0;
    foreach ($businesses as $business) {
        $location = $sample_locations[array_rand($sample_locations)];
        
        try {
            $stmt->execute([$location, $business['id']]);
            $updated++;
            echo "<p style='color:green'>✅ Updated {$business['business_name']} with location: {$location}</p>";
        } catch (PDOException $e) {
            echo "<p style='color:red'>❌ Failed to update {$business['business_name']}: " . $e->getMessage() . "</p>";
        }
    }
    
    echo "<p style='color:green'>🎉 Successfully updated {$updated} businesses with sample locations!</p>";
    echo "<p><a href='../businesses.php'>View Businesses Page</a> to see the locations.</p>";
    
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='../businesses.php'>View Businesses Page</a></p>";
echo "<p><a href='../index.php'>Back to Homepage</a></p>";
?> 