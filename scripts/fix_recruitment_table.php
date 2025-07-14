<?php
/**
 * Fix Recruitment Table - Add Missing is_featured Column
 * 
 * This script adds the missing is_featured column to the recruitment table
 * which is required by the recruitment.php page but missing from the original table structure.
 */

// Load configuration
require_once __DIR__ . '/../config/config.php';

// Check if database connection is available
if (!$pdo) {
    die("❌ Database connection not available. Please check your configuration.\n");
}

echo "🔧 Fixing Recruitment Table - Adding Missing is_featured Column\n";
echo "==============================================================\n\n";

try {
    // Check if the column already exists
    $stmt = $pdo->query("SHOW COLUMNS FROM recruitment LIKE 'is_featured'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Column 'is_featured' already exists in recruitment table.\n";
        exit(0);
    }
    
    echo "📋 Adding is_featured column to recruitment table...\n";
    
    // Add the is_featured column
    $sql = "ALTER TABLE `recruitment` ADD COLUMN `is_featured` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_active`";
    $pdo->exec($sql);
    echo "✅ Added is_featured column successfully.\n";
    
    // Add index for better performance
    echo "📋 Adding index for better performance...\n";
    $sql = "ALTER TABLE `recruitment` ADD INDEX `idx_featured_active` (`is_featured`, `is_active`)";
    $pdo->exec($sql);
    echo "✅ Added index successfully.\n";
    
    // Verify the column was added
    $stmt = $pdo->query("SHOW COLUMNS FROM recruitment LIKE 'is_featured'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Verification: is_featured column exists in recruitment table.\n";
    } else {
        echo "❌ Verification failed: is_featured column not found.\n";
        exit(1);
    }
    
    // Show current table structure
    echo "\n📊 Current recruitment table structure:\n";
    $stmt = $pdo->query("DESCRIBE recruitment");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $column) {
        echo "  - {$column['Field']}: {$column['Type']} {$column['Null']} {$column['Default']}\n";
    }
    
    // Count existing records
    $stmt = $pdo->query("SELECT COUNT(*) FROM recruitment");
    $count = $stmt->fetchColumn();
    echo "\n📈 Total recruitment records: $count\n";
    
    // Count featured records
    $stmt = $pdo->query("SELECT COUNT(*) FROM recruitment WHERE is_featured = 1");
    $featured_count = $stmt->fetchColumn();
    echo "⭐ Featured recruitment records: $featured_count\n";
    
    echo "\n🎉 Migration completed successfully!\n";
    echo "The recruitment.php page should now work properly.\n";
    
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "❌ General error: " . $e->getMessage() . "\n";
    exit(1);
}
?> 