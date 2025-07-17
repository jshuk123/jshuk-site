<?php
/**
 * Test Enhanced Business Cards Script
 * This script sets up everything needed to test the enhanced business cards
 * with location and rating information
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../config/config.php';

echo "<h1>Test Enhanced Business Cards Setup</h1>";

try {
    echo "<h2>Step 1: Check Database Connection</h2>";
    if (isset($pdo) && $pdo) {
        echo "<p style='color:green'>✅ Database connection successful</p>";
    } else {
        echo "<p style='color:red'>❌ Database connection failed</p>";
        exit;
    }
    
    echo "<h2>Step 2: Check Required Tables</h2>";
    
    // Check businesses table
    $stmt = $pdo->query("SHOW TABLES LIKE 'businesses'");
    if ($stmt->rowCount() == 0) {
        echo "<p style='color:red'>❌ Businesses table does not exist</p>";
        exit;
    }
    echo "<p style='color:green'>✅ Businesses table exists</p>";
    
    // Check reviews table
    $stmt = $pdo->query("SHOW TABLES LIKE 'reviews'");
    if ($stmt->rowCount() == 0) {
        echo "<p style='color:orange'>⚠️ Reviews table does not exist. Creating it...</p>";
        
        // Create reviews table
        $pdo->exec("
            CREATE TABLE `reviews` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `business_id` int(11) NOT NULL,
              `rating` int(11) NOT NULL CHECK (rating BETWEEN 1 AND 5),
              `ip_address` varchar(45) DEFAULT NULL,
              `user_agent` text DEFAULT NULL,
              `submitted_at` datetime DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              KEY `business_id` (`business_id`),
              CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo "<p style='color:green'>✅ Created reviews table</p>";
    } else {
        echo "<p style='color:green'>✅ Reviews table exists</p>";
    }
    
    // Check if location column exists in businesses table
    $stmt = $pdo->query("SHOW COLUMNS FROM businesses LIKE 'location'");
    if ($stmt->rowCount() == 0) {
        echo "<p style='color:orange'>⚠️ Location column does not exist. Adding it...</p>";
        $pdo->exec("ALTER TABLE businesses ADD COLUMN location VARCHAR(100) DEFAULT NULL AFTER address");
        echo "<p style='color:green'>✅ Added location column</p>";
    } else {
        echo "<p style='color:green'>✅ Location column exists</p>";
    }
    
    echo "<h2>Step 3: Check for Test Data</h2>";
    
    // Check for businesses
    $stmt = $pdo->query("SELECT COUNT(*) FROM businesses WHERE status = 'active'");
    $business_count = $stmt->fetchColumn();
    
    if ($business_count == 0) {
        echo "<p style='color:orange'>⚠️ No active businesses found. Please run insert_test_data.php first.</p>";
        echo "<p><a href='../insert_test_data.php'>Run Insert Test Data</a></p>";
    } else {
        echo "<p style='color:green'>✅ Found {$business_count} active businesses</p>";
        
        // Check for reviews
        $stmt = $pdo->query("SELECT COUNT(*) FROM reviews");
        $review_count = $stmt->fetchColumn();
        
        if ($review_count == 0) {
            echo "<p style='color:orange'>⚠️ No reviews found. Adding sample reviews...</p>";
            
            // Get businesses
            $stmt = $pdo->query("SELECT id, business_name FROM businesses WHERE status = 'active' LIMIT 5");
            $businesses = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Add sample reviews
            $stmt = $pdo->prepare("INSERT INTO reviews (business_id, rating, ip_address, user_agent) VALUES (?, ?, ?, ?)");
            
            $inserted = 0;
            foreach ($businesses as $business) {
                $num_reviews = rand(2, 4);
                for ($i = 0; $i < $num_reviews; $i++) {
                    $rating = rand(3, 5);
                    $stmt->execute([
                        $business['id'],
                        $rating,
                        '192.168.1.' . rand(1, 254),
                        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
                    ]);
                    $inserted++;
                }
            }
            echo "<p style='color:green'>✅ Added {$inserted} sample reviews</p>";
        } else {
            echo "<p style='color:green'>✅ Found {$review_count} reviews</p>";
        }
        
        // Check for locations
        $stmt = $pdo->query("SELECT COUNT(*) FROM businesses WHERE status = 'active' AND (location IS NULL OR location = '')");
        $no_location_count = $stmt->fetchColumn();
        
        if ($no_location_count > 0) {
            echo "<p style='color:orange'>⚠️ {$no_location_count} businesses without location. Adding sample locations...</p>";
            
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
            
            $stmt = $pdo->prepare("UPDATE businesses SET location = ? WHERE id = ?");
            $updated = 0;
            
            $businesses = $pdo->query("SELECT id, business_name FROM businesses WHERE status = 'active' AND (location IS NULL OR location = '')")->fetchAll();
            
            foreach ($businesses as $business) {
                $location = $sample_locations[array_rand($sample_locations)];
                $stmt->execute([$location, $business['id']]);
                $updated++;
            }
            echo "<p style='color:green'>✅ Added locations to {$updated} businesses</p>";
        } else {
            echo "<p style='color:green'>✅ All businesses have location data</p>";
        }
    }
    
    echo "<h2>Step 4: Test Query</h2>";
    
    // Test the enhanced query
    $query = "SELECT b.*, c.name as category_name, u.subscription_tier,
                     COALESCE(b.location, b.address) as business_location,
                     COALESCE(AVG(r.rating), 0) as average_rating,
                     COUNT(r.id) as review_count
              FROM businesses b 
              LEFT JOIN business_categories c ON b.category_id = c.id 
              LEFT JOIN users u ON b.user_id = u.id
              LEFT JOIN reviews r ON b.id = r.business_id 
              WHERE b.status = 'active'
              GROUP BY b.id 
              ORDER BY b.created_at DESC 
              LIMIT 3";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $test_businesses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($test_businesses)) {
        echo "<p style='color:red'>❌ Test query returned no results</p>";
    } else {
        echo "<p style='color:green'>✅ Test query successful. Found " . count($test_businesses) . " businesses</p>";
        
        echo "<h3>Sample Data:</h3>";
        foreach ($test_businesses as $business) {
            echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 10px 0;'>";
            echo "<strong>{$business['business_name']}</strong><br>";
            echo "Category: {$business['category_name']}<br>";
            echo "Location: {$business['business_location']}<br>";
            echo "Rating: {$business['average_rating']} ({$business['review_count']} reviews)<br>";
            echo "Tier: {$business['subscription_tier']}<br>";
            echo "</div>";
        }
    }
    
    echo "<h2>Step 5: Ready to Test</h2>";
    echo "<p style='color:green'>🎉 Setup complete! You can now test the enhanced business cards.</p>";
    echo "<p><a href='../businesses.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>View Enhanced Businesses Page</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='../businesses.php'>View Businesses Page</a></p>";
echo "<p><a href='../index.php'>Back to Homepage</a></p>";
?> 