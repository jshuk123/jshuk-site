<?php
// Enable error reporting to see what's wrong
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

echo "<!DOCTYPE html>";
echo "<html><head><title>Debug Page</title></head><body>";
echo "<h1>Debug Page - Checking for Errors</h1>";

// Test 1: Basic PHP
echo "<h2>Test 1: Basic PHP</h2>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Current time: " . date('Y-m-d H:i:s') . "</p>";

// Test 2: Check if files exist
echo "<h2>Test 2: File Existence</h2>";
$files_to_check = [
    'config/config.php',
    'config/constants.php', 
    'includes/helpers.php',
    'includes/cache.php',
    'includes/subscription_functions.php',
    'includes/header_main.php',
    'includes/footer_main.php',
    'index.php'
];

foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        echo "<p>✅ $file exists</p>";
    } else {
        echo "<p>❌ $file NOT FOUND</p>";
    }
}

// Test 3: Try to include config files
echo "<h2>Test 3: Including Config Files</h2>";
try {
    require_once 'config/config.php';
    echo "<p>✅ config.php included successfully</p>";
} catch (Exception $e) {
    echo "<p>❌ Error including config.php: " . $e->getMessage() . "</p>";
}

try {
    require_once 'config/constants.php';
    echo "<p>✅ constants.php included successfully</p>";
    echo "<p>BASE_PATH: " . (defined('BASE_PATH') ? BASE_PATH : 'NOT DEFINED') . "</p>";
} catch (Exception $e) {
    echo "<p>❌ Error including constants.php: " . $e->getMessage() . "</p>";
}

// Test 4: Check database connection
echo "<h2>Test 4: Database Connection</h2>";
try {
    if (isset($pdo)) {
        echo "<p>✅ \$pdo variable exists</p>";
        $stmt = $pdo->query("SELECT 1");
        echo "<p>✅ Database query successful</p>";
    } else {
        echo "<p>❌ \$pdo variable not set</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Database error: " . $e->getMessage() . "</p>";
}

// Test 5: Check if functions exist
echo "<h2>Test 5: Function Existence</h2>";
$functions_to_check = [
    'getCategoryIcon',
    'renderBusinessCard', 
    'cache_query',
    'renderSubscriptionBadge'
];

foreach ($functions_to_check as $func) {
    if (function_exists($func)) {
        echo "<p>✅ $func() exists</p>";
    } else {
        echo "<p>❌ $func() NOT FOUND</p>";
    }
}

// Test 6: Check current directory and file paths
echo "<h2>Test 6: File Paths</h2>";
echo "<p>Current directory: " . getcwd() . "</p>";
echo "<p>Script name: " . ($_SERVER['SCRIPT_NAME'] ?? 'NOT SET') . "</p>";
echo "<p>Document root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'NOT SET') . "</p>";

// Test 7: Check if we can read the index.php file
echo "<h2>Test 7: Reading index.php</h2>";
if (file_exists('index.php')) {
    $content = file_get_contents('index.php');
    if ($content !== false) {
        echo "<p>✅ index.php can be read (" . strlen($content) . " bytes)</p>";
        echo "<p>First 200 characters: " . htmlspecialchars(substr($content, 0, 200)) . "...</p>";
    } else {
        echo "<p>❌ index.php cannot be read</p>";
    }
} else {
    echo "<p>❌ index.php not found</p>";
}

echo "</body></html>";
?> 