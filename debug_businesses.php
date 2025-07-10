<?php
// Debug file to test businesses page issues
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>🔍 JShuk Businesses Debug Page</h1>";

// Test basic PHP
echo "<h2>✅ PHP is working</h2>";
echo "<p>PHP Version: " . phpversion() . "</p>";

// Test file includes
echo "<h2>📁 Testing File Includes</h2>";
try {
    if (file_exists('config/config.php')) {
        echo "<p>✅ config.php exists</p>";
        require_once 'config/config.php';
        echo "<p>✅ config.php loaded successfully</p>";
    } else {
        echo "<p>❌ config.php not found</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Error loading config.php: " . $e->getMessage() . "</p>";
}

// Test database connection
echo "<h2>🗄️ Testing Database Connection</h2>";
if (isset($pdo) && $pdo !== null) {
    echo "<p>✅ Database connection exists</p>";
    
    try {
        // Test simple query
        $stmt = $pdo->query("SELECT 1 as test");
        $result = $stmt->fetch();
        echo "<p>✅ Database query successful: " . $result['test'] . "</p>";
        
        // Test businesses table
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM businesses");
        $result = $stmt->fetch();
        echo "<p>✅ Businesses table accessible - Count: " . $result['count'] . "</p>";
        
        // Test business_categories table
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM business_categories");
        $result = $stmt->fetch();
        echo "<p>✅ Business categories table accessible - Count: " . $result['count'] . "</p>";
        
    } catch (Exception $e) {
        echo "<p>❌ Database query error: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p>❌ No database connection available</p>";
}

// Test session
echo "<h2>🔐 Testing Session</h2>";
if (session_status() === PHP_SESSION_ACTIVE) {
    echo "<p>✅ Session is active</p>";
} else {
    echo "<p>❌ Session not active</p>";
}

// Test specific businesses query
echo "<h2>🏢 Testing Businesses Query</h2>";
if (isset($pdo) && $pdo !== null) {
    try {
        $query = "SELECT b.*, c.name as category_name, u.subscription_tier
                  FROM businesses b 
                  LEFT JOIN business_categories c ON b.category_id = c.id 
                  LEFT JOIN users u ON b.user_id = u.id
                  WHERE b.status = 'active'
                  LIMIT 5";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        $businesses = $stmt->fetchAll();
        
        echo "<p>✅ Businesses query successful - Found: " . count($businesses) . " businesses</p>";
        
        if (!empty($businesses)) {
            echo "<h3>Sample businesses:</h3>";
            echo "<ul>";
            foreach ($businesses as $biz) {
                echo "<li>" . htmlspecialchars($biz['business_name'] ?? 'Unknown') . " (" . htmlspecialchars($biz['category_name'] ?? 'No category') . ")</li>";
            }
            echo "</ul>";
        } else {
            echo "<p>⚠️ No active businesses found</p>";
        }
        
    } catch (Exception $e) {
        echo "<p>❌ Businesses query error: " . $e->getMessage() . "</p>";
    }
}

// Test includes
echo "<h2>📦 Testing Required Includes</h2>";
$required_files = [
    'includes/ad_renderer.php',
    'includes/subscription_functions.php',
    'includes/header_main.php'
];

foreach ($required_files as $file) {
    if (file_exists($file)) {
        echo "<p>✅ $file exists</p>";
        try {
            include_once $file;
            echo "<p>✅ $file loaded successfully</p>";
        } catch (Exception $e) {
            echo "<p>❌ Error loading $file: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p>❌ $file not found</p>";
    }
}

echo "<h2>🎯 Conclusion</h2>";
echo "<p>If all tests above are green, the businesses page should work.</p>";
echo "<p><a href='/businesses.php'>→ Try businesses page again</a></p>";
echo "<p><a href='/'>→ Back to homepage</a></p>";
?> 