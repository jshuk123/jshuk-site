<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

echo "<!DOCTYPE html>";
echo "<html lang='en'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
echo "<title>JShuk - Simple Test</title>";
echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css' rel='stylesheet'>";
echo "<link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css'>";
echo "</head>";
echo "<body>";

echo "<div class='container mt-5'>";
echo "<h1>JShuk - Simple Homepage Test</h1>";
echo "<p>If you can see this, the basic PHP is working!</p>";

// Test basic includes
echo "<h2>Testing Basic Includes:</h2>";

if (file_exists('config/config.php')) {
    echo "<p>✅ config.php exists</p>";
    try {
        require_once 'config/config.php';
        echo "<p>✅ config.php loaded</p>";
    } catch (Exception $e) {
        echo "<p>❌ config.php error: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p>❌ config.php not found</p>";
}

if (file_exists('config/constants.php')) {
    echo "<p>✅ constants.php exists</p>";
    try {
        require_once 'config/constants.php';
        echo "<p>✅ constants.php loaded</p>";
        echo "<p>BASE_PATH: " . (defined('BASE_PATH') ? BASE_PATH : 'NOT DEFINED') . "</p>";
    } catch (Exception $e) {
        echo "<p>❌ constants.php error: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p>❌ constants.php not found</p>";
}

// Test database
echo "<h2>Testing Database:</h2>";
try {
    if (isset($pdo)) {
        echo "<p>✅ Database connection available</p>";
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM businesses");
        $result = $stmt->fetch();
        echo "<p>✅ Database query: " . $result['count'] . " businesses found</p>";
    } else {
        echo "<p>❌ No database connection</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Database error: " . $e->getMessage() . "</p>";
}

// Test helper functions
echo "<h2>Testing Helper Functions:</h2>";
if (file_exists('includes/helpers.php')) {
    echo "<p>✅ helpers.php exists</p>";
    try {
        require_once 'includes/helpers.php';
        echo "<p>✅ helpers.php loaded</p>";
        
        if (function_exists('getCategoryIcon')) {
            echo "<p>✅ getCategoryIcon function works: " . getCategoryIcon('Restaurant') . "</p>";
        } else {
            echo "<p>❌ getCategoryIcon function not found</p>";
        }
    } catch (Exception $e) {
        echo "<p>❌ helpers.php error: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p>❌ helpers.php not found</p>";
}

echo "<hr>";
echo "<h2>Simple Homepage Content:</h2>";
echo "<div class='row'>";
echo "<div class='col-md-6'>";
echo "<div class='card'>";
echo "<div class='card-body'>";
echo "<h5 class='card-title'>Welcome to JShuk</h5>";
echo "<p class='card-text'>Your local Jewish business directory</p>";
echo "<a href='#' class='btn btn-primary'>Browse Businesses</a>";
echo "</div>";
echo "</div>";
echo "</div>";
echo "<div class='col-md-6'>";
echo "<div class='card'>";
echo "<div class='card-body'>";
echo "<h5 class='card-title'>List Your Business</h5>";
echo "<p class='card-text'>Join our community today</p>";
echo "<a href='#' class='btn btn-success'>Get Started</a>";
echo "</div>";
echo "</div>";
echo "</div>";
echo "</div>";

echo "</div>";

echo "<script src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js'></script>";
echo "</body>";
echo "</html>";
?> 