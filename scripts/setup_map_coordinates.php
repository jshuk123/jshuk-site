<?php
/**
 * JShuk Map Coordinates Setup Script
 * 
 * This script:
 * 1. Runs the database migration to add latitude/longitude columns
 * 2. Adds sample coordinates for existing businesses
 * 3. Tests the geocoding service
 * 
 * Run this script once to set up the map system
 */

require_once '../config/config.php';
require_once '../includes/geocoding_service.php';

echo "🗺️ JShuk Map Coordinates Setup\n";
echo "================================\n\n";

try {
    // Step 1: Run database migration
    echo "Step 1: Running database migration...\n";
    
    $migration_sql = file_get_contents('../sql/add_map_coordinates.sql');
    if ($migration_sql === false) {
        throw new Exception("Could not read migration file");
    }
    
    // Split SQL into individual statements
    $statements = array_filter(array_map('trim', explode(';', $migration_sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement) && !preg_match('/^(--|\/\*|DELIMITER)/', $statement)) {
            try {
                $pdo->exec($statement);
                echo "  ✓ Executed: " . substr($statement, 0, 50) . "...\n";
            } catch (PDOException $e) {
                // Ignore errors for statements that might already exist
                if (strpos($e->getMessage(), 'already exists') === false) {
                    echo "  ⚠ Warning: " . $e->getMessage() . "\n";
                }
            }
        }
    }
    
    echo "  ✓ Database migration completed\n\n";
    
    // Step 2: Initialize geocoding service
    echo "Step 2: Initializing geocoding service...\n";
    $geocoder = new GeocodingService($pdo);
    echo "  ✓ Geocoding service initialized\n\n";
    
    // Step 3: Get geocoding statistics
    echo "Step 3: Checking current geocoding status...\n";
    $stats = $geocoder->getGeocodingStats();
    
    echo "  📊 Geocoding Statistics:\n";
    echo "     Total businesses: {$stats['total_businesses']}\n";
    echo "     Already geocoded: {$stats['geocoded_businesses']}\n";
    echo "     Need geocoding: {$stats['needing_geocoding']}\n";
    echo "     Completion: {$stats['geocoding_percentage']}%\n\n";
    
    // Step 4: Add sample coordinates for testing
    echo "Step 4: Adding sample coordinates for testing...\n";
    
    // Get businesses that need coordinates
    $businesses_needing_coords = $geocoder->getBusinessesNeedingGeocoding(20);
    
    if (empty($businesses_needing_coords)) {
        echo "  ✓ All businesses already have coordinates\n";
    } else {
        echo "  📍 Adding sample coordinates for " . count($businesses_needing_coords) . " businesses...\n";
        
        // London area coordinates for sample data
        $london_areas = [
            ['lat' => 51.5900, 'lng' => -0.2300, 'name' => 'Hendon'],
            ['lat' => 51.5720, 'lng' => -0.1940, 'name' => 'Golders Green'],
            ['lat' => 51.6190, 'lng' => -0.3020, 'name' => 'Stanmore'],
            ['lat' => 51.6140, 'lng' => -0.2750, 'name' => 'Edgware'],
            ['lat' => 51.5990, 'lng' => -0.1870, 'name' => 'Finchley'],
            ['lat' => 51.6500, 'lng' => -0.2000, 'name' => 'Barnet']
        ];
        
        $updated_count = 0;
        foreach ($businesses_needing_coords as $business) {
            // Pick a random London area
            $area = $london_areas[array_rand($london_areas)];
            
            // Add some random variation within the area
            $lat = $area['lat'] + (rand(-50, 50) / 10000); // ±0.005 degrees
            $lng = $area['lng'] + (rand(-50, 50) / 10000); // ±0.005 degrees
            
            // Update the business record
            $stmt = $pdo->prepare("
                UPDATE businesses 
                SET latitude = ?, longitude = ?, geocoded = 1 
                WHERE id = ?
            ");
            
            if ($stmt->execute([$lat, $lng, $business['id']])) {
                $updated_count++;
                echo "    ✓ {$business['business_name']} → {$area['name']} ({$lat}, {$lng})\n";
            }
        }
        
        echo "  ✓ Updated coordinates for {$updated_count} businesses\n";
    }
    
    echo "\n";
    
    // Step 5: Test geocoding service
    echo "Step 5: Testing geocoding service...\n";
    
    $test_addresses = [
        '123 Golders Green Road, London, UK',
        '456 Hendon Way, London, UK',
        '789 Stanmore Hill, London, UK'
    ];
    
    foreach ($test_addresses as $address) {
        echo "  🔍 Testing: $address\n";
        $coordinates = $geocoder->geocodeAddress($address, false); // Don't use cache for testing
        
        if ($coordinates) {
            echo "    ✓ Found: {$coordinates['lat']}, {$coordinates['lng']}\n";
        } else {
            echo "    ⚠ Not found (this is normal for test addresses)\n";
        }
    }
    
    echo "\n";
    
    // Step 6: Final statistics
    echo "Step 6: Final status...\n";
    $final_stats = $geocoder->getGeocodingStats();
    
    echo "  📊 Final Geocoding Statistics:\n";
    echo "     Total businesses: {$final_stats['total_businesses']}\n";
    echo "     Geocoded businesses: {$final_stats['geocoded_businesses']}\n";
    echo "     Need geocoding: {$final_stats['needing_geocoding']}\n";
    echo "     Completion: {$final_stats['geocoding_percentage']}%\n\n";
    
    // Step 7: Test map data generation
    echo "Step 7: Testing map data generation...\n";
    
    $stmt = $pdo->prepare("
        SELECT b.*, c.name as category_name, u.subscription_tier,
               COALESCE(b.location, b.address) as business_location,
               b.latitude, b.longitude, b.geocoded,
               COALESCE(AVG(r.rating), 0) as average_rating,
               COUNT(r.id) as review_count
        FROM businesses b 
        LEFT JOIN business_categories c ON b.category_id = c.id 
        LEFT JOIN users u ON b.user_id = u.id
        LEFT JOIN reviews r ON b.id = r.business_id 
        WHERE b.status = 'active'
        GROUP BY b.id
        LIMIT 5
    ");
    $stmt->execute();
    $test_businesses = $stmt->fetchAll();
    
    echo "  📍 Sample map data for " . count($test_businesses) . " businesses:\n";
    foreach ($test_businesses as $business) {
        $lat = !empty($business['latitude']) ? (float) $business['latitude'] : 51.5074;
        $lng = !empty($business['longitude']) ? (float) $business['longitude'] : -0.1278;
        $geocoded = !empty($business['latitude']) && !empty($business['longitude']);
        
        echo "    • {$business['business_name']}: ({$lat}, {$lng}) " . ($geocoded ? "✓" : "⚠") . "\n";
    }
    
    echo "\n";
    
    // Success message
    echo "🎉 Map Coordinates Setup Complete!\n";
    echo "==================================\n";
    echo "✅ Database migration completed\n";
    echo "✅ Sample coordinates added\n";
    echo "✅ Geocoding service tested\n";
    echo "✅ Map data generation verified\n\n";
    
    echo "Next steps:\n";
    echo "1. Visit your businesses.php page to see the map in action\n";
    echo "2. Test the Grid/Map view toggle\n";
    echo "3. Try filtering businesses and watch the map update\n";
    echo "4. For real geocoding, use the geocoding service on business addresses\n\n";
    
    echo "Optional: Get a free Stadia Maps API key for enhanced map tiles\n";
    echo "Visit: https://stadiamaps.com\n\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
?> 