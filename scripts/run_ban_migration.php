<?php
/**
 * Run User Ban Feature Migration
 * This script adds the necessary database fields for user banning functionality
 */

require_once '../config/config.php';

echo "<h2>User Ban Feature Migration</h2>\n";
echo "<pre>\n";

try {
    // Read the migration SQL file
    $migrationFile = '../sql/add_user_ban_feature.sql';
    
    if (!file_exists($migrationFile)) {
        throw new Exception("Migration file not found: $migrationFile");
    }
    
    $sql = file_get_contents($migrationFile);
    
    // Split SQL into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($statements as $statement) {
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue; // Skip comments and empty lines
        }
        
        try {
            $pdo->exec($statement);
            echo "✓ Executed: " . substr($statement, 0, 50) . "...\n";
            $successCount++;
        } catch (PDOException $e) {
            echo "✗ Error: " . $e->getMessage() . "\n";
            echo "  Statement: " . substr($statement, 0, 100) . "...\n";
            $errorCount++;
        }
    }
    
    echo "\n";
    echo "Migration Summary:\n";
    echo "✓ Successful statements: $successCount\n";
    echo "✗ Failed statements: $errorCount\n";
    
    if ($errorCount === 0) {
        echo "\n🎉 Migration completed successfully!\n";
        echo "The user ban feature is now ready to use.\n";
    } else {
        echo "\n⚠️  Migration completed with errors.\n";
        echo "Please check the errors above and fix them manually.\n";
    }
    
} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
}

echo "</pre>\n";
echo "<p><a href='/admin/users.php'>Go to Admin Panel</a></p>\n";
?> 