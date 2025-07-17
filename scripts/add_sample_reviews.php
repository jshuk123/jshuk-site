<?php
/**
 * Add Sample Reviews Script
 * This script adds sample reviews to test the rating system
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../config/config.php';

echo "<h1>Add Sample Reviews</h1>";

try {
    // Check if reviews table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'reviews'");
    if ($stmt->rowCount() == 0) {
        echo "<p style='color:red'>❌ Reviews table does not exist. Please run the testimonials_star_ratings_system.sql script first.</p>";
        exit;
    }
    
    // Get active businesses
    $stmt = $pdo->query("SELECT id, business_name FROM businesses WHERE status = 'active' LIMIT 5");
    $businesses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($businesses)) {
        echo "<p style='color:orange'>⚠️ No active businesses found. Please add some businesses first.</p>";
        exit;
    }
    
    echo "<p style='color:green'>✅ Found " . count($businesses) . " active businesses</p>";
    
    // Check if reviews already exist
    $stmt = $pdo->query("SELECT COUNT(*) FROM reviews");
    $existing_reviews = $stmt->fetchColumn();
    
    if ($existing_reviews > 0) {
        echo "<p style='color:blue'>ℹ️ Already have {$existing_reviews} reviews in the database.</p>";
        echo "<p><a href='../businesses.php'>View Businesses Page</a> to see the ratings.</p>";
        exit;
    }
    
    // Add sample reviews
    $sample_reviews = [];
    foreach ($businesses as $business) {
        // Add 2-4 reviews per business
        $num_reviews = rand(2, 4);
        for ($i = 0; $i < $num_reviews; $i++) {
            $rating = rand(3, 5); // Random rating between 3-5
            $sample_reviews[] = [
                'business_id' => $business['id'],
                'rating' => $rating,
                'ip_address' => '192.168.1.' . rand(1, 254),
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
            ];
        }
    }
    
    // Insert reviews
    $stmt = $pdo->prepare("
        INSERT INTO reviews (business_id, rating, ip_address, user_agent, submitted_at)
        VALUES (?, ?, ?, ?, NOW())
    ");
    
    $inserted = 0;
    foreach ($sample_reviews as $review) {
        try {
            $stmt->execute([
                $review['business_id'],
                $review['rating'],
                $review['ip_address'],
                $review['user_agent']
            ]);
            $inserted++;
        } catch (PDOException $e) {
            echo "<p style='color:red'>❌ Failed to insert review: " . $e->getMessage() . "</p>";
        }
    }
    
    echo "<p style='color:green'>✅ Successfully inserted {$inserted} sample reviews!</p>";
    echo "<p><a href='../businesses.php'>View Businesses Page</a> to see the ratings.</p>";
    
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='../businesses.php'>View Businesses Page</a></p>";
echo "<p><a href='../index.php'>Back to Homepage</a></p>";
?> 