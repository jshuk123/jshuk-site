<?php
require_once 'config/db_connect.php';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Adding missing columns to ads table...\n\n";
    
    // Array of columns to add with their definitions
    $columns = [
        'status' => "VARCHAR(20) DEFAULT 'active'",
        'zone' => "VARCHAR(50) DEFAULT 'header'",
        'priority' => "INT DEFAULT 1",
        'category_id' => "INT NULL",
        'location' => "VARCHAR(100) NULL",
        'start_date' => "DATE NULL",
        'end_date' => "DATE NULL",
        'clicks' => "INT DEFAULT 0",
        'impressions' => "INT DEFAULT 0",
        'targeting_options' => "TEXT NULL",
        'admin_notes' => "TEXT NULL"
    ];
    
    foreach ($columns as $column => $definition) {
        try {
            // Check if column exists
            $checkStmt = $pdo->query("SHOW COLUMNS FROM ads LIKE '$column'");
            if ($checkStmt->rowCount() == 0) {
                // Column doesn't exist, add it
                $sql = "ALTER TABLE ads ADD COLUMN $column $definition";
                $pdo->exec($sql);
                echo "✅ Added column: $column\n";
            } else {
                echo "⏭️  Column already exists: $column\n";
            }
        } catch (PDOException $e) {
            echo "❌ Error adding column $column: " . $e->getMessage() . "\n";
        }
    }
    
    // Add foreign key constraint for category_id if it doesn't exist
    try {
        $pdo->exec("ALTER TABLE ads ADD CONSTRAINT fk_ads_category 
                   FOREIGN KEY (category_id) REFERENCES business_categories(id) 
                   ON DELETE SET NULL");
        echo "✅ Added foreign key constraint for category_id\n";
    } catch (PDOException $e) {
        echo "⏭️  Foreign key constraint already exists or error: " . $e->getMessage() . "\n";
    }
    
    echo "\n🎉 Database migration completed!\n";
    
} catch (PDOException $e) {
    echo "❌ Database connection error: " . $e->getMessage() . "\n";
}
?> 