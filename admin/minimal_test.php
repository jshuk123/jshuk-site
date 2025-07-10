<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo "Step 1: Basic PHP working<br>";

// Test session
session_start();
echo "Step 2: Session started<br>";

// Test config include
echo "Step 3: About to include config<br>";
require_once '../config/config.php';
echo "Step 4: Config included<br>";

// Test database
if ($pdo) {
    echo "Step 5: Database connected<br>";
} else {
    echo "Step 5: Database NOT connected<br>";
}

// Test session data
echo "Step 6: Session user_id = " . ($_SESSION['user_id'] ?? 'NOT SET') . "<br>";

// Test admin check
if (isset($_SESSION['user_id'])) {
    echo "Step 7: User ID exists, checking role<br>";
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    echo "Step 8: User role = " . ($user['role'] ?? 'NOT FOUND') . "<br>";
} else {
    echo "Step 7: No user_id in session<br>";
}

echo "Step 9: Script completed successfully!<br>";
?> 