<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php_errors.log');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo "Session started<br>";

// Test environment variables
echo "Environment variables:<br>";
echo "DB_HOST: " . (getenv('DB_HOST') ?: 'not set') . "<br>";
echo "DB_NAME: " . (getenv('DB_NAME') ?: 'not set') . "<br>";
echo "DB_USER: " . (getenv('DB_USER') ?: 'not set') . "<br>";
echo "DB_PASS: " . (getenv('DB_PASS') ? 'set' : 'not set') . "<br>";

// Try database connection
try {
    require_once '../config/config.php';
    echo "Config loaded<br>";
    
    if ($pdo) {
        echo "Database connected successfully<br>";
        // Test query
        $stmt = $pdo->query("SELECT COUNT(*) FROM users");
        $count = $stmt->fetchColumn();
        echo "Total users: " . $count . "<br>";
    } else {
        echo "No database connection<br>";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}

// Check session data
echo "Session data:<br>";
echo "User ID: " . ($_SESSION['user_id'] ?? 'not set') . "<br>";
echo "Username: " . ($_SESSION['user_name'] ?? 'not set') . "<br>";
echo "Role: " . ($_SESSION['role'] ?? 'not set') . "<br>"; 