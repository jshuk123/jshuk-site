<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo "PHP is working!<br>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Current time: " . date('Y-m-d H:i:s') . "<br>";

// Test database connection
try {
    require_once 'config/config.php';
    if ($pdo) {
        echo "Database connection: SUCCESS<br>";
        $result = $pdo->query("SELECT COUNT(*) as count FROM businesses")->fetch();
        echo "Businesses in database: " . $result['count'] . "<br>";
    } else {
        echo "Database connection: FAILED (pdo is null)<br>";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}

// Test session
session_start();
echo "Session ID: " . session_id() . "<br>";
echo "Session data: <pre>" . print_r($_SESSION, true) . "</pre>";
?>