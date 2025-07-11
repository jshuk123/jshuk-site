<?php
// Basic error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Test output
echo "Starting test...<br>";

// Load config
try {
    require_once '../config/config.php';
    echo "Config loaded successfully<br>";
} catch (Exception $e) {
    die("Error loading config: " . $e->getMessage());
}

// Test database connection
try {
    if ($pdo) {
        $stmt = $pdo->query("SELECT 1");
        echo "Database connection successful<br>";
    } else {
        echo "No database connection<br>";
    }
} catch (Exception $e) {
    die("Database error: " . $e->getMessage());
}

// Test session
echo "Session ID: " . session_id() . "<br>";
echo "User ID: " . ($_SESSION['user_id'] ?? 'not set') . "<br>";
echo "User Role: " . ($_SESSION['role'] ?? 'not set') . "<br>";

// Test admin access
if (isset($_SESSION['user_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        echo "User role from database: " . ($user['role'] ?? 'not found') . "<br>";
    } catch (Exception $e) {
        echo "Error checking user role: " . $e->getMessage() . "<br>";
    }
}

// Test environment
echo "<br>Environment Variables:<br>";
echo "APP_ENV: " . (getenv('APP_ENV') ?: 'not set') . "<br>";
echo "DB_HOST: " . (getenv('DB_HOST') ?: 'not set') . "<br>";
echo "DB_NAME: " . (getenv('DB_NAME') ?: 'not set') . "<br>";
echo "DB_USER: " . (getenv('DB_USER') ?: 'not set') . "<br>";
echo "DB_PASS: " . (getenv('DB_PASS') ? 'set' : 'not set') . "<br>"; 