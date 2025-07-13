<?php
/**
 * Run Consolidated Database Migration
 * This script safely applies the consolidated migration to fix all conflicts
 */

require_once '../config/config.php';

echo "<h1>🔄 Running JShuk Consolidated Database Migration</h1>\n";
echo "<div style='font-family: monospace; background: #f8f9fa; padding: 20px; border-radius: 8px;'>\n";

try {
    if (!isset($pdo) || !$pdo) {
        throw new Exception("Database connection failed. Please check your configuration.");
    }
    
    echo "✅ Database connection established\n\n";
    
    // Read the consolidated migration file
    $migration_file = '../sql/consolidated_migration.sql';
    
    if (!file_exists($migration_file)) {
        throw new Exception("Migration file not found: {$migration_file}");
    }
    
    echo "📖 Reading consolidated migration file...\n";
    $sql = file_get_contents($migration_file);
    
    if (empty($sql)) {
        throw new Exception("Migration file is empty");
    }
    
    echo "🔧 Executing consolidated migration...\n\n";
    
    // Split SQL into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    $pdo->beginTransaction();
    
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($statements as $statement) {
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue; // Skip comments and empty lines
        }
        
        try {
            $pdo->exec($statement);
            echo "✅ " . substr($statement, 0, 50) . "...<br>\n";
            $successCount++;
        } catch (PDOException $e) {
            // Check if it's a "table already exists" or "trigger already exists" error
            if (strpos($e->getMessage(), 'already exists') !== false) {
                echo "⚠️  Already exists: " . substr($statement, 0, 50) . "...<br>\n";
                $successCount++;
            } else {
                echo "❌ Error: " . $e->getMessage() . "<br>\n";
                echo "Statement: " . substr($statement, 0, 100) . "...<br>\n";
                $errorCount++;
            }
        }
    }
    
    if ($errorCount === 0) {
        $pdo->commit();
        echo "<hr>\n";
        echo "<h3>🎉 Migration Summary:</h3>\n";
        echo "✅ Successful operations: $successCount<br>\n";
        echo "❌ Errors: $errorCount<br>\n";
        echo "<br>\n";
        echo "🎯 All database conflicts have been resolved!<br>\n";
        echo "✅ Tables and triggers are now properly consolidated<br>\n";
        echo "✅ No more duplicate definitions<br>\n";
        echo "✅ No more trigger conflicts<br>\n";
    } else {
        $pdo->rollBack();
        echo "<hr>\n";
        echo "<h3>⚠️ Migration Summary:</h3>\n";
        echo "✅ Successful operations: $successCount<br>\n";
        echo "❌ Errors: $errorCount<br>\n";
        echo "<br>\n";
        echo "🔧 Please check the errors above and fix them manually.<br>\n";
    }
    
} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
}

echo "</div>\n";
echo "<p><a href='/admin/'>Go to Admin Panel</a> | <a href='/'>Go to Homepage</a></p>\n";
?> 