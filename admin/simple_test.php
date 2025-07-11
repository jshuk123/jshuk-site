<?php
ob_start();
session_start();
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Custom error handler to catch hidden fatal errors
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    echo "<pre>PHP ERROR [$errno] $errstr in $errfile on line $errline</pre>";
});

echo "<!-- Debug: Starting simple test -->\n";
require_once '../config/config.php';
echo "<!-- Debug: Config loaded -->\n";

echo "<!-- Debug: About to check admin access -->\n";
if (!isset($_SESSION['user_id'])) {
    echo "<!-- Debug: No user_id, redirecting -->\n";
    exit("No user ID in session");
}
echo "<!-- Debug: User ID: " . $_SESSION['user_id'] . " -->\n";

if (!$pdo) {
    echo "<!-- Debug: Database connection failed -->\n";
    exit("Database connection failed!");
}
echo "<!-- Debug: Database connected -->\n";

$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
echo "<!-- Debug: User role: " . ($user['role'] ?? 'not found') . " -->\n";

if ($user['role'] !== 'admin') {
    echo "<!-- Debug: Not admin, redirecting -->\n";
    exit("Not admin");
}
echo "<!-- Debug: Admin access granted -->\n";

echo "<!-- Debug: Simple test completed successfully -->\n";
echo "<h1>Simple Test Success!</h1>";
echo "<p>If you see this, the basic admin logic is working.</p>";
?> 