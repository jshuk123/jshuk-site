<?php
/**
 * Apply Category Presets
 * Updates existing categories with the comprehensive preset metadata
 */

require_once '../config/config.php';
require_once '../includes/category_presets.php';

echo "<h1>Applying Category Presets</h1>";
echo "<p>This script will update existing categories with comprehensive metadata presets.</p>";

// Check if category_meta table exists
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'category_meta'");
    if ($stmt->rowCount() == 0) {
        echo "<p style='color: red;'>❌ category_meta table does not exist. Please run the database setup first.</p>";
        exit;
    }
    echo "<p>✅ category_meta table exists</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error checking database: " . $e->getMessage() . "</p>";
    exit;
}

// Get existing categories
try {
    $stmt = $pdo->query("SELECT id, name FROM business_categories ORDER BY name");
    $existing_categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p>Found " . count($existing_categories) . " existing categories</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error fetching categories: " . $e->getMessage() . "</p>";
    exit;
}

// Apply presets
$updated = 0;
$not_found = [];

echo "<h2>Applying Presets:</h2>";
echo "<ul>";

foreach ($existing_categories as $category) {
    $preset = getCategoryPreset($category['name']);
    
    if ($preset) {
        try {
            // Insert or update category metadata
            $meta_stmt = $pdo->prepare("INSERT INTO category_meta (category_id, short_description, seo_title, seo_description) 
                                       VALUES (?, ?, ?, ?) 
                                       ON DUPLICATE KEY UPDATE 
                                       short_description = VALUES(short_description),
                                       seo_title = VALUES(seo_title),
                                       seo_description = VALUES(seo_description)");
            $meta_stmt->execute([
                $category['id'],
                $preset['shortDesc'],
                $preset['seoTitle'],
                $preset['seoDesc']
            ]);
            
            echo "<li>✅ <strong>{$category['name']}</strong> - Updated with preset data</li>";
            $updated++;
            
        } catch (Exception $e) {
            echo "<li>❌ <strong>{$category['name']}</strong> - Error: " . $e->getMessage() . "</li>";
        }
    } else {
        echo "<li>⚠️ <strong>{$category['name']}</strong> - No preset found</li>";
        $not_found[] = $category['name'];
    }
}

echo "</ul>";

// Summary
echo "<h2>Summary:</h2>";
echo "<p><strong>Updated:</strong> {$updated} categories</p>";
echo "<p><strong>No preset found:</strong> " . count($not_found) . " categories</p>";

if (!empty($not_found)) {
    echo "<h3>Categories without presets:</h3>";
    echo "<ul>";
    foreach ($not_found as $name) {
        echo "<li>{$name}</li>";
    }
    echo "</ul>";
}

// Show available presets
echo "<h2>Available Presets:</h2>";
$preset_names = getCategoryPresetNames();
echo "<p>Total presets available: " . count($preset_names) . "</p>";
echo "<details>";
echo "<summary>View all preset names</summary>";
echo "<ul>";
foreach ($preset_names as $name) {
    echo "<li>{$name}</li>";
}
echo "</ul>";
echo "</details>";

// Test a few categories
echo "<h2>Test Results:</h2>";
try {
    $test_categories = array_slice($existing_categories, 0, 3);
    foreach ($test_categories as $category) {
        $stmt = $pdo->prepare("SELECT c.name, cm.short_description, cm.seo_title, cm.seo_description 
                               FROM business_categories c 
                               LEFT JOIN category_meta cm ON c.id = cm.category_id 
                               WHERE c.id = ?");
        $stmt->execute([$category['id']]);
        $result = $stmt->fetch();
        
        if ($result) {
            echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 10px;'>";
            echo "<h4>{$result['name']}</h4>";
            echo "<p><strong>Short Description:</strong> " . ($result['short_description'] ?: 'Not set') . "</p>";
            echo "<p><strong>SEO Title:</strong> " . ($result['seo_title'] ?: 'Not set') . "</p>";
            echo "<p><strong>SEO Description:</strong> " . ($result['seo_description'] ?: 'Not set') . "</p>";
            echo "</div>";
        }
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Error testing categories: " . $e->getMessage() . "</p>";
}

echo "<h2>Next Steps:</h2>";
echo "<ul>";
echo "<li><a href='/admin/categories.php'>Go to Admin Panel</a> to manage categories</li>";
echo "<li><a href='/categories.php'>View Categories Page</a> to see the results</li>";
echo "<li><a href='/scripts/test_category_setup.php'>Run Category Setup Test</a> to verify everything</li>";
echo "</ul>";

echo "<p style='color: green; font-weight: bold;'>✅ Category presets applied successfully!</p>";
?> 