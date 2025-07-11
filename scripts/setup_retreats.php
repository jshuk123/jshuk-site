<?php
/**
 * Retreats System Setup Script
 * 
 * This script initializes the database tables and sample data for the
 * Retreats & Simcha Rentals system.
 */

require_once '../config/config.php';

echo "🏠 JShuk Retreats System Setup\n";
echo "==============================\n\n";

// Check database connection
if (!isset($pdo) || !$pdo) {
    echo "❌ Database connection failed. Please check your configuration.\n";
    exit(1);
}

try {
    echo "📊 Setting up database tables...\n";
    
    // Read and execute the SQL file
    $sql_file = '../sql/create_retreats_tables.sql';
    
    if (!file_exists($sql_file)) {
        echo "❌ SQL file not found: $sql_file\n";
        exit(1);
    }
    
    $sql_content = file_get_contents($sql_file);
    
    // Split SQL into individual statements
    $statements = array_filter(
        array_map('trim', explode(';', $sql_content)),
        function($stmt) { return !empty($stmt) && !preg_match('/^(--|\/\*)/', $stmt); }
    );
    
    $pdo->beginTransaction();
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            $pdo->exec($statement);
        }
    }
    
    $pdo->commit();
    
    echo "✅ Database tables created successfully!\n\n";
    
    // Verify tables were created
    $tables = [
        'retreat_categories',
        'retreat_locations', 
        'retreats',
        'retreat_amenities',
        'retreat_amenity_relations',
        'retreat_tags',
        'retreat_tag_relations',
        'retreat_availability',
        'retreat_bookings',
        'retreat_reviews',
        'retreat_views'
    ];
    
    echo "🔍 Verifying table creation...\n";
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "  ✅ $table\n";
        } else {
            echo "  ❌ $table (missing)\n";
        }
    }
    
    echo "\n📈 Checking data population...\n";
    
    // Check categories
    $stmt = $pdo->query("SELECT COUNT(*) FROM retreat_categories");
    $category_count = $stmt->fetchColumn();
    echo "  📋 Categories: $category_count\n";
    
    // Check locations
    $stmt = $pdo->query("SELECT COUNT(*) FROM retreat_locations");
    $location_count = $stmt->fetchColumn();
    echo "  📍 Locations: $location_count\n";
    
    // Check amenities
    $stmt = $pdo->query("SELECT COUNT(*) FROM retreat_amenities");
    $amenity_count = $stmt->fetchColumn();
    echo "  🛠️  Amenities: $amenity_count\n";
    
    // Check tags
    $stmt = $pdo->query("SELECT COUNT(*) FROM retreat_tags");
    $tag_count = $stmt->fetchColumn();
    echo "  🏷️  Tags: $tag_count\n";
    
    // Check sample retreats
    $stmt = $pdo->query("SELECT COUNT(*) FROM retreats");
    $retreat_count = $stmt->fetchColumn();
    echo "  🏠 Sample Retreats: $retreat_count\n";
    
    echo "\n🎉 Setup completed successfully!\n\n";
    
    echo "📝 Next Steps:\n";
    echo "  1. Visit /retreats.php to see the main listings page\n";
    echo "  2. Visit /add_retreat.php to test the property submission form\n";
    echo "  3. Visit /admin/retreats.php to access the admin panel\n";
    echo "  4. Customize categories, locations, and amenities as needed\n\n";
    
    echo "🔗 Useful URLs:\n";
    echo "  • Main Listings: https://yourdomain.com/retreats.php\n";
    echo "  • Add Property: https://yourdomain.com/add_retreat.php\n";
    echo "  • Admin Panel: https://yourdomain.com/admin/retreats.php\n";
    echo "  • Documentation: https://yourdomain.com/RETREATS_SYSTEM_README.md\n\n";
    
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "❌ Database error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "❌ Setup error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "✨ Retreats system is ready to use!\n";
?> 