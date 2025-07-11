<?php
echo "PHP is working!<br>";

// Test basic PHP functionality
$test = "Hello World";
echo "Variable test: $test<br>";

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

// Test database
if (isset($pdo) && $pdo) {
    echo "Database connected<br>";
} else {
    echo "Database not connected<br>";
}

// Test session variables
echo "Session user_id: " . ($_SESSION['user_id'] ?? 'not set') . "<br>";
echo "Session username: " . ($_SESSION['username'] ?? 'not set') . "<br>";
echo "Session role: " . ($_SESSION['role'] ?? 'not set') . "<br>";

echo "End of test<br>";
?> 