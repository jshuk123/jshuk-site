<?php
/**
 * Lost & Found Database Migration Script
 * This script safely creates the necessary tables for the Lost & Found feature
 */

require_once '../config/config.php';

// Error reporting
if (defined('APP_DEBUG') && APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

echo "=== JShuk Lost & Found Database Migration ===\n\n";

try {
    if (!isset($pdo) || !$pdo) {
        throw new Exception("Database connection failed. Please check your configuration.");
    }
    
    echo "✓ Database connection established\n";
    
    // Check if tables already exist
    $existing_tables = [];
    $stmt = $pdo->query("SHOW TABLES LIKE 'lostfound_%'");
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        $existing_tables[] = $row[0];
    }
    
    if (!empty($existing_tables)) {
        echo "⚠️  Found existing Lost & Found tables: " . implode(', ', $existing_tables) . "\n";
        echo "   Migration may have already been run.\n\n";
    }
    
    // Read and execute the migration SQL
    $migration_file = '../sql/create_lostfound_tables.sql';
    
    if (!file_exists($migration_file)) {
        throw new Exception("Migration file not found: {$migration_file}");
    }
    
    echo "📖 Reading migration file...\n";
    $sql = file_get_contents($migration_file);
    
    if (empty($sql)) {
        throw new Exception("Migration file is empty");
    }
    
    echo "🔧 Executing migration...\n";
    
    // Split SQL into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    $pdo->beginTransaction();
    
    foreach ($statements as $statement) {
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue; // Skip comments and empty lines
        }
        
        try {
            $pdo->exec($statement);
            echo "   ✓ Executed: " . substr($statement, 0, 50) . "...\n";
        } catch (PDOException $e) {
            // Check if it's a "table already exists" error
            if (strpos($e->getMessage(), 'already exists') !== false) {
                echo "   ⚠️  Skipped (already exists): " . substr($statement, 0, 50) . "...\n";
            } else {
                throw $e;
            }
        }
    }
    
    $pdo->commit();
    echo "✓ Migration completed successfully!\n\n";
    
    // Verify tables were created
    echo "🔍 Verifying tables...\n";
    $required_tables = [
        'lostfound_posts',
        'lostfound_claims', 
        'lostfound_categories',
        'lostfound_locations'
    ];
    
    foreach ($required_tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM {$table}");
            $count = $stmt->fetchColumn();
            echo "   ✓ {$table}: {$count} records\n";
        } catch (PDOException $e) {
            echo "   ❌ {$table}: Error - " . $e->getMessage() . "\n";
        }
    }
    
    // Test data insertion
    echo "\n🧪 Testing data insertion...\n";
    
    // Test categories
    $stmt = $pdo->query("SELECT COUNT(*) FROM lostfound_categories");
    $category_count = $stmt->fetchColumn();
    
    if ($category_count == 0) {
        echo "   ⚠️  No categories found, inserting default categories...\n";
        
        $default_categories = [
            ['Keys', 'fas fa-key', 'House keys, car keys, office keys', 1],
            ['Phones', 'fas fa-mobile-alt', 'Mobile phones, smartphones', 2],
            ['Hats', 'fas fa-hat-cowboy', 'Kippot, hats, head coverings', 3],
            ['Jewelry', 'fas fa-gem', 'Rings, necklaces, watches', 4],
            ['Sefarim', 'fas fa-book', 'Books, prayer books, religious texts', 5],
            ['Bags', 'fas fa-briefcase', 'Handbags, backpacks, briefcases', 6],
            ['Clothing', 'fas fa-tshirt', 'Coats, jackets, clothing items', 7],
            ['Electronics', 'fas fa-laptop', 'Laptops, tablets, electronic devices', 8],
            ['Documents', 'fas fa-file-alt', 'ID cards, passports, important papers', 9],
            ['Other', 'fas fa-question-circle', 'Other miscellaneous items', 10]
        ];
        
        $stmt = $pdo->prepare("INSERT INTO lostfound_categories (name, icon, description, sort_order) VALUES (?, ?, ?, ?)");
        foreach ($default_categories as $category) {
            $stmt->execute($category);
        }
        echo "   ✓ Inserted {$category_count} default categories\n";
    } else {
        echo "   ✓ Found {$category_count} categories\n";
    }
    
    // Test locations
    $stmt = $pdo->query("SELECT COUNT(*) FROM lostfound_locations");
    $location_count = $stmt->fetchColumn();
    
    if ($location_count == 0) {
        echo "   ⚠️  No locations found, inserting default locations...\n";
        
        $default_locations = [
            ['Golders Green', 'London', 1],
            ['Edgware', 'London', 2],
            ['Stamford Hill', 'London', 3],
            ['Hendon', 'London', 4],
            ['Finchley', 'London', 5],
            ['Manchester', 'Manchester', 6],
            ['Gateshead', 'Gateshead', 7],
            ['Leeds', 'Leeds', 8],
            ['Birmingham', 'Birmingham', 9],
            ['Liverpool', 'Liverpool', 10],
            ['Other', 'Various', 11]
        ];
        
        $stmt = $pdo->prepare("INSERT INTO lostfound_locations (name, area, sort_order) VALUES (?, ?, ?)");
        foreach ($default_locations as $location) {
            $stmt->execute($location);
        }
        echo "   ✓ Inserted {$location_count} default locations\n";
    } else {
        echo "   ✓ Found {$location_count} locations\n";
    }
    
    echo "\n🎉 Migration completed successfully!\n";
    echo "📋 Next steps:\n";
    echo "   1. Test the Lost & Found pages at /lostfound.php\n";
    echo "   2. Test posting items at /post_lostfound.php\n";
    echo "   3. Check admin panel at /admin/lostfound.php\n";
    echo "   4. Verify email notifications are working\n\n";
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    echo "🔧 Please check your database configuration and try again.\n";
    
    if (APP_DEBUG) {
        echo "\nDebug information:\n";
        echo "Error: " . $e->getMessage() . "\n";
        echo "File: " . $e->getFile() . "\n";
        echo "Line: " . $e->getLine() . "\n";
    }
    
    exit(1);
}

echo "\n=== Migration Complete ===\n";
?> 