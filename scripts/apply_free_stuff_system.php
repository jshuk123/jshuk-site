<?php
/**
 * Apply Free Stuff / Chessed Giveaway System Database Changes
 * This script adds the necessary database structure for the new feature
 */

require_once '../config/config.php';

echo "<h1>🔄 Applying Free Stuff System Database Changes</h1>\n";
echo "<div style='font-family: monospace; background: #f8f9fa; padding: 20px; border-radius: 8px;'>\n";

try {
    // Read and execute the SQL file
    $sqlFile = '../sql/add_free_stuff_system.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception("SQL file not found: $sqlFile");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Split into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
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
            // Check if it's a "column already exists" error
            if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                echo "⚠️  Column already exists: " . substr($statement, 0, 50) . "...<br>\n";
                $successCount++;
            } else {
                echo "❌ Error: " . $e->getMessage() . "<br>\n";
                echo "Statement: " . substr($statement, 0, 100) . "...<br>\n";
                $errorCount++;
            }
        }
    }
    
    echo "<hr>\n";
    echo "<h3>📊 Summary:</h3>\n";
    echo "✅ Successful operations: $successCount<br>\n";
    echo "❌ Errors: $errorCount<br>\n";
    
    if ($errorCount === 0) {
        echo "<h3>🎉 Free Stuff System Successfully Applied!</h3>\n";
        echo "<p>The following features are now available:</p>\n";
        echo "<ul>\n";
        echo "<li>♻️ Free Stuff category in classifieds</li>\n";
        echo "<li>📦 Pickup method options (porch pickup, contact arrange, collection code)</li>\n";
        echo "<li>⏰ Collection deadline functionality</li>\n";
        echo "<li>💝 Chessed and bundle listing options</li>\n";
        echo "<li>📞 Contact method preferences</li>\n";
        echo "<li>🔐 Pickup code generation</li>\n";
        echo "<li>📋 Item status tracking (available, pending, claimed, expired)</li>\n";
        echo "</ul>\n";
        
        echo "<h3>🚀 Next Steps:</h3>\n";
        echo "<ol>\n";
        echo "<li>Test the new Free Stuff category on /classifieds.php</li>\n";
        echo "<li>Try posting a free item using /submit_classified.php</li>\n";
        echo "<li>Check that the filtering works correctly</li>\n";
        echo "<li>Verify the pickup method and status display</li>\n";
        echo "</ol>\n";
    }
    
} catch (Exception $e) {
    echo "❌ Fatal Error: " . $e->getMessage() . "<br>\n";
}

echo "</div>\n";
?> 