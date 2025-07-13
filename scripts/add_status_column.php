<?php
/**
 * Add status column to users table
 * This script safely adds the status column needed for user account management
 */

require_once '../config/config.php';

echo "Starting database migration: Adding status column to users table...\n";

try {
    // Check if status column already exists
    $stmt = $pdo->prepare("SHOW COLUMNS FROM users LIKE 'status'");
    $stmt->execute();
    $column_exists = $stmt->fetch();
    
    if ($column_exists) {
        echo "✅ Status column already exists in users table.\n";
        exit(0);
    }
    
    // Add the status column
    echo "Adding status column to users table...\n";
    $pdo->exec("ALTER TABLE users ADD COLUMN status VARCHAR(20) DEFAULT 'active' AFTER email_verified");
    echo "✅ Status column added successfully.\n";
    
    // Update existing users to have 'active' status
    echo "Updating existing users to have 'active' status...\n";
    $stmt = $pdo->prepare("UPDATE users SET status = 'active' WHERE status IS NULL");
    $stmt->execute();
    $affected_rows = $stmt->rowCount();
    echo "✅ Updated {$affected_rows} existing users to 'active' status.\n";
    
    // Add index for better performance
    echo "Adding index for status column...\n";
    $pdo->exec("CREATE INDEX idx_users_status ON users(status)");
    echo "✅ Index added successfully.\n";
    
    // Add comment to document the column
    echo "Adding column comment...\n";
    $pdo->exec("ALTER TABLE users MODIFY COLUMN status VARCHAR(20) DEFAULT 'active' COMMENT 'User account status: active, inactive, banned'");
    echo "✅ Column comment added successfully.\n";
    
    echo "\n🎉 Database migration completed successfully!\n";
    echo "The users table now has a status column with all existing users set to 'active'.\n";
    
} catch (PDOException $e) {
    echo "❌ Error during migration: " . $e->getMessage() . "\n";
    echo "Please check your database connection and try again.\n";
    exit(1);
} catch (Exception $e) {
    echo "❌ Unexpected error: " . $e->getMessage() . "\n";
    exit(1);
}
?> 