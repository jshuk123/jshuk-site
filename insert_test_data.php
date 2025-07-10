<?php
// Script to insert test data for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Insert Test Data</h1>";

// Test database connection
require_once 'config/config.php';

if (isset($pdo) && $pdo) {
    echo "<p style='color:green'>✅ Database connection successful</p>";
    
    // Check if we need to insert test data
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM businesses");
        $business_count = $stmt->fetchColumn();
        
        if ($business_count == 0) {
            echo "<p style='color:orange'>⚠️ No businesses found. Inserting test data...</p>";
            
            // First, check if we have users
            $stmt = $pdo->query("SELECT COUNT(*) FROM users");
            $user_count = $stmt->fetchColumn();
            
            if ($user_count == 0) {
                echo "<p style='color:red'>❌ No users found. Cannot insert businesses without users.</p>";
                echo "<p>Please create at least one user first, or check your database setup.</p>";
            } else {
                // Get first user ID
                $stmt = $pdo->query("SELECT id FROM users LIMIT 1");
                $user_id = $stmt->fetchColumn();
                
                // Check if we have categories
                $stmt = $pdo->query("SELECT COUNT(*) FROM business_categories");
                $category_count = $stmt->fetchColumn();
                
                if ($category_count == 0) {
                    echo "<p style='color:red'>❌ No categories found. Cannot insert businesses without categories.</p>";
                    echo "<p>Please create categories first, or check your database setup.</p>";
                } else {
                    // Get first category ID
                    $stmt = $pdo->query("SELECT id FROM business_categories LIMIT 1");
                    $category_id = $stmt->fetchColumn();
                    
                    // Insert test businesses
                    $test_businesses = [
                        [
                            'user_id' => $user_id,
                            'business_name' => 'Test Kosher Restaurant',
                            'description' => 'A delicious kosher restaurant serving traditional Jewish cuisine.',
                            'status' => 'active',
                            'category_id' => $category_id
                        ],
                        [
                            'user_id' => $user_id,
                            'business_name' => 'Jewish Bookstore',
                            'description' => 'Specializing in Jewish literature, religious texts, and educational materials.',
                            'status' => 'active',
                            'category_id' => $category_id
                        ],
                        [
                            'user_id' => $user_id,
                            'business_name' => 'Kosher Catering Service',
                            'description' => 'Professional kosher catering for all your special occasions.',
                            'status' => 'active',
                            'category_id' => $category_id
                        ]
                    ];
                    
                    $inserted = 0;
                    foreach ($test_businesses as $business) {
                        try {
                            $stmt = $pdo->prepare("
                                INSERT INTO businesses (user_id, business_name, description, status, created_at, category_id)
                                VALUES (?, ?, ?, ?, NOW(), ?)
                            ");
                            $stmt->execute([
                                $business['user_id'],
                                $business['business_name'],
                                $business['description'],
                                $business['status'],
                                $business['category_id']
                            ]);
                            $inserted++;
                            echo "<p style='color:green'>✅ Inserted: {$business['business_name']}</p>";
                        } catch (PDOException $e) {
                            echo "<p style='color:red'>❌ Failed to insert {$business['business_name']}: " . $e->getMessage() . "</p>";
                        }
                    }
                    
                    if ($inserted > 0) {
                        echo "<p style='color:green'>🎉 Successfully inserted {$inserted} test businesses!</p>";
                        echo "<p><a href='index.php'>Visit Homepage</a> to see the new businesses.</p>";
                    }
                }
            }
        } else {
            echo "<p style='color:green'>✅ Database already has {$business_count} businesses. No need to insert test data.</p>";
            echo "<p><a href='debug_database.php'>Run Database Diagnostic</a> to see what's in the database.</p>";
        }
        
    } catch (PDOException $e) {
        echo "<p style='color:red'>❌ Error checking database: " . $e->getMessage() . "</p>";
    }
    
} else {
    echo "<p style='color:red'>❌ Database connection failed</p>";
}

echo "<hr>";
echo "<p><a href='index.php'>Back to Homepage</a></p>";
echo "<p><a href='debug_database.php'>Run Database Diagnostic</a></p>";
?> 