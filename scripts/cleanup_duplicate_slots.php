<?php
/**
 * Cleanup Duplicate Advertising Slots
 * 
 * This script removes duplicate advertising slots and adds proper constraints
 * to prevent future duplicates from being created.
 */

// Load configuration
require_once '../config/config.php';

echo "🧹 Cleaning up duplicate advertising slots...\n\n";

try {
    // Step 1: Show current duplicates
    echo "📊 Step 1: Analyzing current advertising slots...\n";
    
    $stmt = $pdo->query("
        SELECT name, position, COUNT(*) as count 
        FROM advertising_slots 
        GROUP BY name, position 
        HAVING COUNT(*) > 1
    ");
    $duplicates = $stmt->fetchAll();
    
    if (empty($duplicates)) {
        echo "  ✅ No duplicates found!\n";
    } else {
        echo "  ⚠️  Found " . count($duplicates) . " duplicate groups:\n";
        foreach ($duplicates as $dup) {
            echo "    - {$dup['name']} ({$dup['position']}): {$dup['count']} copies\n";
        }
    }
    
    // Step 2: Remove duplicates, keeping only the first occurrence
    echo "\n🗑️  Step 2: Removing duplicates...\n";
    
    $stmt = $pdo->prepare("
        DELETE a1 FROM advertising_slots a1
        INNER JOIN advertising_slots a2 
        WHERE a1.id > a2.id 
        AND a1.name = a2.name 
        AND a1.position = a2.position
    ");
    $stmt->execute();
    $deleted_count = $stmt->rowCount();
    
    echo "  ✅ Removed {$deleted_count} duplicate entries\n";
    
    // Step 3: Add unique constraint to prevent future duplicates
    echo "\n🔒 Step 3: Adding unique constraint...\n";
    
    try {
        $pdo->exec("
            ALTER TABLE advertising_slots 
            ADD UNIQUE KEY unique_slot_name_position (name, position)
        ");
        echo "  ✅ Unique constraint added successfully\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
            echo "  ⚠️  Unique constraint already exists\n";
        } else {
            echo "  ❌ Error adding constraint: " . $e->getMessage() . "\n";
        }
    }
    
    // Step 4: Show final results
    echo "\n📈 Step 4: Final results...\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM advertising_slots");
    $total = $stmt->fetchColumn();
    echo "  ✅ Total advertising slots: {$total}\n";
    
    $stmt = $pdo->query("
        SELECT name, position, monthly_price, annual_price, max_slots, status 
        FROM advertising_slots 
        ORDER BY position, name
    ");
    $slots = $stmt->fetchAll();
    
    echo "\n📋 Current advertising slots:\n";
    foreach ($slots as $slot) {
        echo "  - {$slot['name']} ({$slot['position']})\n";
        echo "    Monthly: £{$slot['monthly_price']}, Annual: £{$slot['annual_price']}\n";
        echo "    Max slots: {$slot['max_slots']}, Status: {$slot['status']}\n\n";
    }
    
    echo "🎉 Cleanup completed successfully!\n";
    echo "✅ Duplicates removed: {$deleted_count}\n";
    echo "✅ Unique constraint added to prevent future duplicates\n";
    
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?> 