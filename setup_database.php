<?php
/**
 * Temporary Database Setup Script
 * Run this through your web browser to fix the missing classifieds_categories table
 */

require_once 'config/config.php';

echo "<h1>🔧 Database Setup - Fixing Missing Tables</h1>\n";
echo "<div style='font-family: monospace; background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px;'>\n";

try {
    // Check if classifieds_categories table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'classifieds_categories'");
    if ($stmt->rowCount() > 0) {
        echo "✅ classifieds_categories table already exists<br>\n";
    } else {
        echo "❌ classifieds_categories table does not exist. Creating it...<br>\n";
        
        // Create the classifieds_categories table
        $createTableSQL = "
        CREATE TABLE `classifieds_categories` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `name` varchar(100) NOT NULL,
          `slug` varchar(110) NOT NULL,
          `description` text DEFAULT NULL,
          `icon` varchar(50) DEFAULT NULL,
          `is_active` tinyint(1) DEFAULT 1,
          `sort_order` int(11) DEFAULT 0,
          `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
          PRIMARY KEY (`id`),
          UNIQUE KEY `name` (`name`),
          UNIQUE KEY `slug` (`slug`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        
        $pdo->exec($createTableSQL);
        echo "✅ classifieds_categories table created successfully<br>\n";
        
        // Insert default categories
        $categories = [
            ['Free Stuff', 'free-stuff', 'Free items and chessed giveaways', '♻️', 1],
            ['Furniture', 'furniture', 'Furniture and home furnishings', '🛋️', 2],
            ['Electronics', 'electronics', 'Electronics and gadgets', '💻', 3],
            ['Books & Seforim', 'books-seforim', 'Books, seforim, and educational materials', '📚', 4],
            ['Clothing', 'clothing', 'Clothing and accessories', '👕', 5],
            ['Toys & Games', 'toys-games', 'Toys, games, and children\'s items', '🧸', 6],
            ['Kitchen Items', 'kitchen-items', 'Kitchen appliances and utensils', '🍽️', 7],
            ['Jewelry', 'jewelry', 'Jewelry and accessories', '💎', 8],
            ['Judaica', 'judaica', 'Jewish religious items and books', '🕯️', 9],
            ['Office & School', 'office-school', 'Office supplies and school materials', '💼', 10],
            ['Baby & Kids', 'baby-kids', 'Baby and children\'s items', '👶', 11],
            ['Miscellaneous', 'miscellaneous', 'Other items', '📦', 12]
        ];
        
        $insertStmt = $pdo->prepare("
            INSERT INTO `classifieds_categories` (`name`, `slug`, `description`, `icon`, `sort_order`) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        foreach ($categories as $category) {
            try {
                $insertStmt->execute($category);
                echo "✅ Added category: {$category[0]}<br>\n";
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                    echo "⚠️  Category already exists: {$category[0]}<br>\n";
                } else {
                    echo "❌ Error adding category {$category[0]}: " . $e->getMessage() . "<br>\n";
                }
            }
        }
    }
    
    // Check if classifieds table has category_id column
    $stmt = $pdo->query("SHOW COLUMNS FROM `classifieds` LIKE 'category_id'");
    if ($stmt->rowCount() > 0) {
        echo "✅ category_id column exists in classifieds table<br>\n";
    } else {
        echo "❌ category_id column missing. Adding it...<br>\n";
        
        // Add category_id column
        $pdo->exec("ALTER TABLE `classifieds` ADD COLUMN `category_id` int(11) NULL AFTER `user_id`");
        echo "✅ category_id column added successfully<br>\n";
        
        // Set default category for existing records
        $pdo->exec("UPDATE `classifieds` SET `category_id` = 12 WHERE `category_id` IS NULL");
        echo "✅ Set default category for existing classifieds<br>\n";
    }
    
    echo "<hr>\n";
    echo "<h3>🎉 Database setup completed successfully!</h3>\n";
    echo "<p>The classifieds page should now work properly. You can:</p>\n";
    echo "<ul>\n";
    echo "<li><a href='/classifieds.php'>Visit the classifieds page</a></li>\n";
    echo "<li><a href='/submit_classified.php'>Post a new classified</a></li>\n";
    echo "<li><a href='/classifieds.php?category=free-stuff'>Browse free stuff</a></li>\n";
    echo "</ul>\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>\n";
    echo "<p>Please check your database connection and try again.</p>\n";
}

echo "</div>\n";
?> 