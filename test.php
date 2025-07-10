<?php
// Simple test page to check if PHP is working
echo "<!DOCTYPE html>";
echo "<html><head><title>PHP Test</title></head><body>";
echo "<h1>PHP Test Page</h1>";

// Test basic PHP
echo "<p>PHP is working!</p>";
echo "<p>Current time: " . date('Y-m-d H:i:s') . "</p>";

// Test includes
echo "<h2>Testing Includes:</h2>";

if (file_exists('config/config.php')) {
    echo "<p>✅ config.php exists</p>";
    try {
        require_once 'config/config.php';
        echo "<p>✅ config.php loaded successfully</p>";
    } catch (Exception $e) {
        echo "<p>❌ Error loading config.php: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p>❌ config.php not found</p>";
}

if (file_exists('includes/helpers.php')) {
    echo "<p>✅ helpers.php exists</p>";
    try {
        require_once 'includes/helpers.php';
        echo "<p>✅ helpers.php loaded successfully</p>";
    } catch (Exception $e) {
        echo "<p>❌ Error loading helpers.php: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p>❌ helpers.php not found</p>";
}

if (file_exists('includes/cache.php')) {
    echo "<p>✅ cache.php exists</p>";
    try {
        require_once 'includes/cache.php';
        echo "<p>✅ cache.php loaded successfully</p>";
    } catch (Exception $e) {
        echo "<p>❌ Error loading cache.php: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p>❌ cache.php not found</p>";
}

// Test database connection
echo "<h2>Testing Database:</h2>";
try {
    if (isset($pdo)) {
        echo "<p>✅ Database connection available</p>";
        $stmt = $pdo->query("SELECT COUNT(*) FROM businesses");
        $count = $stmt->fetchColumn();
        echo "<p>✅ Database query successful: " . $count . " businesses found</p>";
    } else {
        echo "<p>❌ Database connection not available</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Database error: " . $e->getMessage() . "</p>";
}

// Test functions
echo "<h2>Testing Functions:</h2>";
if (function_exists('getCategoryIcon')) {
    echo "<p>✅ getCategoryIcon function exists</p>";
} else {
    echo "<p>❌ getCategoryIcon function not found</p>";
}

if (function_exists('renderBusinessCard')) {
    echo "<p>✅ renderBusinessCard function exists</p>";
} else {
    echo "<p>❌ renderBusinessCard function not found</p>";
}

if (function_exists('cache_query')) {
    echo "<p>✅ cache_query function exists</p>";
} else {
    echo "<p>❌ cache_query function not found</p>";
}

echo "</body></html>";
?>