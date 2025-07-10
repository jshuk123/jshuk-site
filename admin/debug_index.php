<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo "DEBUG: Starting admin debug index<br>";

// Test 1: Basic PHP
echo "DEBUG: PHP is working<br>";

// Test 2: Session
try {
    session_start();
    echo "DEBUG: Session started successfully<br>";
} catch (Exception $e) {
    echo "DEBUG: Session error: " . $e->getMessage() . "<br>";
}

// Test 3: Config file
echo "DEBUG: About to include config<br>";
if (file_exists('../config/config.php')) {
    echo "DEBUG: Config file exists<br>";
    try {
        require_once '../config/config.php';
        echo "DEBUG: Config loaded successfully<br>";
    } catch (Exception $e) {
        echo "DEBUG: Config error: " . $e->getMessage() . "<br>";
    }
} else {
    echo "DEBUG: Config file NOT found<br>";
}

// Test 4: Database
if (isset($pdo) && $pdo) {
    echo "DEBUG: Database connection available<br>";
} else {
    echo "DEBUG: Database connection NOT available<br>";
}

// Test 5: Session data
echo "DEBUG: Session user_id = " . ($_SESSION['user_id'] ?? 'NOT SET') . "<br>";

// Test 6: Basic HTML output
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Debug</title>
</head>
<body>
    <h1>Admin Debug Page</h1>
    <p>If you can see this, the basic admin functionality is working.</p>
    <p>Session ID: <?php echo session_id(); ?></p>
    <p>User ID: <?php echo $_SESSION['user_id'] ?? 'Not logged in'; ?></p>
</body>
</html> 