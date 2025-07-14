<?php
/**
 * Community Corner Migration Script
 * Run this script to set up the community corner database table
 */

require_once '../config/config.php';

echo "=== Community Corner Migration Script ===\n\n";

try {
    // Read and execute the SQL file
    $sqlFile = '../sql/create_community_corner_table.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception("SQL file not found: $sqlFile");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Split SQL into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    echo "Executing SQL statements...\n";
    
    foreach ($statements as $statement) {
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue; // Skip comments and empty lines
        }
        
        try {
            $pdo->exec($statement);
            echo "✓ Executed: " . substr($statement, 0, 50) . "...\n";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'already exists') !== false) {
                echo "⚠ Table already exists, skipping...\n";
            } else {
                echo "✗ Error: " . $e->getMessage() . "\n";
            }
        }
    }
    
    // Verify the table was created
    $stmt = $pdo->query("SHOW TABLES LIKE 'community_corner'");
    if ($stmt->rowCount() > 0) {
        echo "\n✓ Community corner table created successfully!\n";
        
        // Check sample data
        $stmt = $pdo->query("SELECT COUNT(*) FROM community_corner");
        $count = $stmt->fetchColumn();
        echo "✓ Sample data inserted: $count items\n";
        
        // Show sample items
        $stmt = $pdo->query("SELECT title, type, emoji FROM community_corner LIMIT 3");
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "\nSample items:\n";
        foreach ($items as $item) {
            echo "  • {$item['emoji']} {$item['title']} ({$item['type']})\n";
        }
        
    } else {
        echo "\n✗ Error: Table was not created successfully\n";
    }
    
    echo "\n=== Migration Complete ===\n";
    echo "You can now:\n";
    echo "1. Visit the homepage to see the Community Corner section\n";
    echo "2. Access /admin/community_corner.php to manage content\n";
    echo "3. Visit /lost_and_found.php to see the Lost & Found page\n";
    
} catch (Exception $e) {
    echo "✗ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?> 