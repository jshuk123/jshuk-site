<?php
/**
 * Test Path Resolution Issues
 */

echo "<!DOCTYPE html>";
echo "<html><head><title>Path Test</title>";
echo "<style>body{font-family:monospace;margin:20px;} .error{color:red;} .success{color:green;} .info{color:blue;} pre{background:#f0f0f0;padding:10px;border-radius:5px;}</style>";
echo "</head><body>";
echo "<h1>🔍 Path Resolution Test</h1>";

// Test 1: Check document root
echo "<h2>Test 1: Document Root Check</h2>";
echo "<p><strong>DOCUMENT_ROOT:</strong> " . $_SERVER['DOCUMENT_ROOT'] . "</p>";
echo "<p><strong>Current file:</strong> " . __FILE__ . "</p>";
echo "<p><strong>Current directory:</strong> " . __DIR__ . "</p>";

// Test 2: Check if ad_renderer exists at different paths
echo "<h2>Test 2: File Existence Check</h2>";

$paths = [
    'Relative' => 'includes/ad_renderer.php',
    'Document Root' => $_SERVER['DOCUMENT_ROOT'] . '/includes/ad_renderer.php',
    'Absolute' => __DIR__ . '/includes/ad_renderer.php',
    'Parent + Relative' => dirname(__DIR__) . '/includes/ad_renderer.php'
];

foreach ($paths as $name => $path) {
    if (file_exists($path)) {
        echo "<p class='success'>✅ $name: $path (EXISTS)</p>";
        echo "<p class='info'>File size: " . filesize($path) . " bytes</p>";
        echo "<p class='info'>Last modified: " . date('Y-m-d H:i:s', filemtime($path)) . "</p>";
    } else {
        echo "<p class='error'>❌ $name: $path (NOT FOUND)</p>";
    }
}

// Test 3: Check function after different includes
echo "<h2>Test 3: Function Loading Test</h2>";

// Clear any existing functions
if (function_exists('renderAd')) {
    echo "<p class='info'>ℹ️ renderAd function already exists before test</p>";
}

// Test relative include
echo "<h3>Testing relative include:</h3>";
try {
    require_once 'includes/ad_renderer.php';
    if (function_exists('renderAd')) {
        echo "<p class='success'>✅ renderAd function loaded via relative path</p>";
        
        // Get function source
        $reflection = new ReflectionFunction('renderAd');
        $filename = $reflection->getFileName();
        echo "<p class='info'>Function loaded from: $filename</p>";
    } else {
        echo "<p class='error'>❌ renderAd function not loaded via relative path</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Error loading via relative path: " . $e->getMessage() . "</p>";
}

// Test document root include
echo "<h3>Testing document root include:</h3>";
try {
    // Clear function first
    if (function_exists('renderAd')) {
        echo "<p class='info'>ℹ️ Function exists, testing document root path</p>";
    }
    
    $docRootPath = $_SERVER['DOCUMENT_ROOT'] . '/includes/ad_renderer.php';
    if (file_exists($docRootPath)) {
        echo "<p class='success'>✅ Document root file exists</p>";
        echo "<p class='info'>Document root file size: " . filesize($docRootPath) . " bytes</p>";
        echo "<p class='info'>Document root file modified: " . date('Y-m-d H:i:s', filemtime($docRootPath)) . "</p>";
        
        // Compare files
        $relativeContent = file_get_contents('includes/ad_renderer.php');
        $docRootContent = file_get_contents($docRootPath);
        
        if ($relativeContent === $docRootContent) {
            echo "<p class='success'>✅ Files are identical</p>";
        } else {
            echo "<p class='error'>❌ Files are different!</p>";
            echo "<p class='info'>Relative file size: " . strlen($relativeContent) . " bytes</p>";
            echo "<p class='info'>Document root file size: " . strlen($docRootContent) . " bytes</p>";
        }
    } else {
        echo "<p class='error'>❌ Document root file does not exist</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Error checking document root: " . $e->getMessage() . "</p>";
}

echo "</body></html>";
?> 