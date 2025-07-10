<?php
/**
 * Test Category Setup
 * Verifies that all category page components are working correctly
 */

require_once '../config/config.php';

echo "<h1>Category Page Setup Test</h1>";

// Test 1: Check if category_meta table exists
echo "<h2>1. Database Tables Check</h2>";
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'category_meta'");
    if ($stmt->rowCount() > 0) {
        echo "✅ category_meta table exists<br>";
    } else {
        echo "❌ category_meta table missing<br>";
    }
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'featured_stories'");
    if ($stmt->rowCount() > 0) {
        echo "✅ featured_stories table exists<br>";
    } else {
        echo "❌ featured_stories table missing<br>";
    }
} catch (Exception $e) {
    echo "❌ Error checking tables: " . $e->getMessage() . "<br>";
}

// Test 2: Check if category functions file exists
echo "<h2>2. Category Functions Check</h2>";
if (file_exists('../includes/category_functions.php')) {
    echo "✅ category_functions.php exists<br>";
    require_once '../includes/category_functions.php';
    echo "✅ category_functions.php loaded successfully<br>";
} else {
    echo "❌ category_functions.php missing<br>";
}

// Test 3: Test category data retrieval
echo "<h2>3. Category Data Retrieval Test</h2>";
try {
    $categories = $pdo->query("SELECT c.*, cm.short_description FROM business_categories c LEFT JOIN category_meta cm ON c.id = cm.category_id LIMIT 3")->fetchAll();
    
    if (!empty($categories)) {
        echo "✅ Found " . count($categories) . " categories with metadata<br>";
        foreach ($categories as $cat) {
            echo "&nbsp;&nbsp;• {$cat['name']} - " . ($cat['short_description'] ? 'Has metadata' : 'No metadata') . "<br>";
        }
    } else {
        echo "❌ No categories found<br>";
    }
} catch (Exception $e) {
    echo "❌ Error retrieving categories: " . $e->getMessage() . "<br>";
}

// Test 4: Test business retrieval for a category
echo "<h2>4. Business Retrieval Test</h2>";
try {
    $first_category = $pdo->query("SELECT id FROM business_categories LIMIT 1")->fetch();
    if ($first_category) {
        $businesses = getCategoryBusinesses($first_category['id'], 'Manchester', 'premium_first');
        echo "✅ Found " . count($businesses) . " businesses for category ID {$first_category['id']}<br>";
    } else {
        echo "❌ No categories available for testing<br>";
    }
} catch (Exception $e) {
    echo "❌ Error retrieving businesses: " . $e->getMessage() . "<br>";
}

// Test 5: Check if category.php exists
echo "<h2>5. Category Page File Check</h2>";
if (file_exists('../category.php')) {
    echo "✅ category.php exists<br>";
} else {
    echo "❌ category.php missing<br>";
}

// Test 6: Check if CSS and JS files exist
echo "<h2>6. Asset Files Check</h2>";
if (file_exists('../css/category.css')) {
    echo "✅ category.css exists<br>";
} else {
    echo "❌ category.css missing<br>";
}

if (file_exists('../js/category.js')) {
    echo "✅ category.js exists<br>";
} else {
    echo "❌ category.js missing<br>";
}

// Test 7: Test category links
echo "<h2>7. Category Links Test</h2>";
try {
    $categories = $pdo->query("SELECT id, name FROM business_categories LIMIT 3")->fetchAll();
    echo "Sample category links:<br>";
    foreach ($categories as $cat) {
        echo "&nbsp;&nbsp;• <a href='/category.php?category_id={$cat['id']}' target='_blank'>{$cat['name']}</a><br>";
    }
} catch (Exception $e) {
    echo "❌ Error generating links: " . $e->getMessage() . "<br>";
}

// Test 8: Check testimonials functionality
echo "<h2>8. Testimonials Check</h2>";
try {
    $testimonials = getCategoryTestimonials(1, 3);
    echo "✅ Found " . count($testimonials) . " testimonials for category 1<br>";
} catch (Exception $e) {
    echo "❌ Error retrieving testimonials: " . $e->getMessage() . "<br>";
}

echo "<h2>Setup Complete!</h2>";
echo "<p>If all tests pass, your category page is ready to use.</p>";
echo "<p><a href='/categories.php'>View Categories Page</a></p>";
echo "<p><a href='/admin/categories.php'>Manage Categories (Admin)</a></p>";
?> 