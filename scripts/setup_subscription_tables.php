<?php
/**
 * Setup Subscription Tables Script
 * 
 * This script creates the necessary subscription tables in the database.
 * Run this script once to set up the subscription system.
 */

// Load configuration
require_once __DIR__ . '/../config/config.php';

echo "Setting up subscription tables...\n";

try {
    // Read and execute the subscription tables SQL
    $sql_file = __DIR__ . '/../sql/subscription_tables.sql';
    if (!file_exists($sql_file)) {
        throw new Exception("SQL file not found: $sql_file");
    }
    
    $sql_content = file_get_contents($sql_file);
    
    // Split SQL into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql_content)));
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            echo "Executing: " . substr($statement, 0, 50) . "...\n";
            $pdo->exec($statement);
        }
    }
    
    echo "✅ Subscription tables created successfully!\n";
    
    // Now run the update script to add additional columns
    $update_sql_file = __DIR__ . '/../sql/update_subscription_plans.sql';
    if (file_exists($update_sql_file)) {
        echo "Updating subscription plans with additional features...\n";
        
        $update_sql_content = file_get_contents($update_sql_file);
        $update_statements = array_filter(array_map('trim', explode(';', $update_sql_content)));
        
        foreach ($update_statements as $statement) {
            if (!empty($statement)) {
                echo "Executing update: " . substr($statement, 0, 50) . "...\n";
                $pdo->exec($statement);
            }
        }
        
        echo "✅ Subscription plans updated successfully!\n";
    }
    
    echo "\n🎉 All subscription tables and plans have been set up successfully!\n";
    echo "You can now use the Google authentication and subscription features.\n";
    
} catch (Exception $e) {
    echo "❌ Error setting up subscription tables: " . $e->getMessage() . "\n";
    exit(1);
} 