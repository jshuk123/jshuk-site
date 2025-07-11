<?php
// Simple test to check if PHP is working
echo "PHP is working!<br>";
echo "PHP version: " . phpversion() . "<br>";

// Test session
session_start();
echo "Session started<br>";

// Test config loading
try {
    require_once '../config/config.php';
    echo "Config loaded successfully<br>";
} catch (Exception $e) {
    echo "Config error: " . $e->getMessage() . "<br>";
}

// Test database connection
if (isset($pdo) && $pdo) {
    echo "Database connected<br>";
} else {
    echo "Database not connected<br>";
}

// Test session variables
echo "Session user_id: " . ($_SESSION['user_id'] ?? 'not set') . "<br>";
echo "Session username: " . ($_SESSION['username'] ?? 'not set') . "<br>";
echo "Session role: " . ($_SESSION['role'] ?? 'not set') . "<br>";
?> 