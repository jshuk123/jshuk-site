<?php
/**
 * Debug Carousel System
 * This script helps diagnose carousel-related issues
 */

echo "<h1>🔍 Carousel Debug Information</h1>";

// Check if config file exists
if (file_exists('config/config.php')) {
    echo "✅ config/config.php exists<br>";
} else {
    echo "❌ config/config.php not found<br>";
}

// Try to include config
try {
    require_once 'config/config.php';
    echo "✅ Config loaded successfully<br>";
} catch (Exception $e) {
    echo "❌ Error loading config: " . $e->getMessage() . "<br>";
}

// Check if $pdo is available
if (isset($pdo) && $pdo) {
    echo "✅ Database connection available<br>";
    
    // Test database connection
    try {
        $pdo->query('SELECT 1');
        echo "✅ Database connection working<br>";
    } catch (PDOException $e) {
        echo "❌ Database connection failed: " . $e->getMessage() . "<br>";
    }
    
    // Check if carousel_ads table exists
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'carousel_ads'");
        if ($stmt->rowCount() > 0) {
            echo "✅ carousel_ads table exists<br>";
            
            // Count ads
            $stmt = $pdo->query("SELECT COUNT(*) FROM carousel_ads");
            $count = $stmt->fetchColumn();
            echo "📊 Found {$count} carousel ads<br>";
            
            // Show active ads
            $stmt = $pdo->query("SELECT COUNT(*) FROM carousel_ads WHERE active = 1");
            $active = $stmt->fetchColumn();
            echo "🟢 Active ads: {$active}<br>";
            
        } else {
            echo "⚠️ carousel_ads table does not exist<br>";
        }
    } catch (PDOException $e) {
        echo "❌ Error checking carousel_ads table: " . $e->getMessage() . "<br>";
    }
    
} else {
    echo "❌ Database connection not available<br>";
}

// Check if carousel component file exists
if (file_exists('sections/carousel.php')) {
    echo "✅ sections/carousel.php exists<br>";
} else {
    echo "❌ sections/carousel.php not found<br>";
}

// Check if uploads directory exists
if (is_dir('uploads/carousel/')) {
    echo "✅ uploads/carousel/ directory exists<br>";
    if (is_writable('uploads/carousel/')) {
        echo "✅ uploads/carousel/ is writable<br>";
    } else {
        echo "❌ uploads/carousel/ is not writable<br>";
    }
} else {
    echo "⚠️ uploads/carousel/ directory does not exist<br>";
}

// Check PHP error log
$error_log_path = 'logs/php_errors.log';
if (file_exists($error_log_path)) {
    echo "✅ Error log exists at {$error_log_path}<br>";
    
    // Show last few lines of error log
    $lines = file($error_log_path);
    if ($lines) {
        echo "<h3>Last 10 error log entries:</h3>";
        echo "<pre style='background: #f5f5f5; padding: 10px; border-radius: 5px; max-height: 300px; overflow-y: auto;'>";
        $recent_lines = array_slice($lines, -10);
        foreach ($recent_lines as $line) {
            echo htmlspecialchars($line);
        }
        echo "</pre>";
    }
} else {
    echo "⚠️ Error log not found at {$error_log_path}<br>";
}

// Test carousel component inclusion
echo "<h3>Testing carousel component:</h3>";
try {
    ob_start();
    include 'sections/carousel.php';
    $carousel_output = ob_get_clean();
    echo "✅ Carousel component loaded successfully<br>";
    echo "<details><summary>Carousel HTML (first 500 chars)</summary><pre>" . htmlspecialchars(substr($carousel_output, 0, 500)) . "...</pre></details>";
} catch (Exception $e) {
    echo "❌ Error loading carousel component: " . $e->getMessage() . "<br>";
}

echo "<br><hr><br>";
echo "<h3>🔧 System Information:</h3>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "<br>";
echo "Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown') . "<br>";
echo "Current Directory: " . getcwd() . "<br>";

// Check for common PHP extensions
$extensions = ['pdo', 'pdo_mysql', 'gd', 'fileinfo'];
echo "<h3>Required Extensions:</h3>";
foreach ($extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "✅ {$ext} extension loaded<br>";
    } else {
        echo "❌ {$ext} extension not loaded<br>";
    }
}
?> 