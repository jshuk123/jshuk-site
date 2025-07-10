<?php
// Basic test - no includes, no database
echo "Basic PHP test - working!<br>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Current time: " . date('Y-m-d H:i:s') . "<br>";

// Test session
session_start();
echo "Session ID: " . session_id() . "<br>";
echo "Session data: <pre>" . print_r($_SESSION, true) . "</pre>";

// Test if we can include config
echo "Testing config include...<br>";
if (file_exists('config/config.php')) {
    echo "Config file exists<br>";
    try {
        require_once 'config/config.php';
        echo "Config loaded successfully<br>";
        if (isset($pdo)) {
            echo "Database connection available<br>";
        } else {
            echo "Database connection NOT available<br>";
        }
    } catch (Exception $e) {
        echo "Error loading config: " . $e->getMessage() . "<br>";
    }
} else {
    echo "Config file NOT found<br>";
}
?> 