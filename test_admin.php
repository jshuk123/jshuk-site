<?php
session_start();
require_once 'config/config.php';

echo "<h1>Admin Test Page</h1>";

// Test database connection
echo "<h2>Database Connection Test</h2>";
if ($pdo) {
    echo "<p style='color: green;'>✅ Database connection successful</p>";
    
    // Test if users table exists
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM users");
        $userCount = $stmt->fetchColumn();
        echo "<p style='color: green;'>✅ Users table exists with $userCount users</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Users table error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
    // Test if businesses table exists
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM businesses");
        $businessCount = $stmt->fetchColumn();
        echo "<p style='color: green;'>✅ Businesses table exists with $businessCount businesses</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Businesses table error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
} else {
    echo "<p style='color: red;'>❌ Database connection failed</p>";
}

// Test session
echo "<h2>Session Test</h2>";
echo "<p>Session ID: " . session_id() . "</p>";
echo "<p>User ID: " . ($_SESSION['user_id'] ?? 'Not set') . "</p>";
echo "<p>Is Admin: " . ($_SESSION['is_admin'] ?? 'Not set') . "</p>";
echo "<p>Username: " . ($_SESSION['username'] ?? 'Not set') . "</p>";

// Test admin access function
echo "<h2>Admin Access Test</h2>";
if (isset($_SESSION['user_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT role, username FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        if ($user) {
            echo "<p>User found: " . htmlspecialchars($user['username']) . "</p>";
            echo "<p>Role: " . htmlspecialchars($user['role']) . "</p>";
            
            if ($user['role'] === 'admin') {
                echo "<p style='color: green;'>✅ User has admin role</p>";
            } else {
                echo "<p style='color: red;'>❌ User does not have admin role</p>";
            }
        } else {
            echo "<p style='color: red;'>❌ User not found in database</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Database query error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
} else {
    echo "<p style='color: red;'>❌ No user logged in</p>";
}

// Test admin pages
echo "<h2>Admin Page Links</h2>";
echo "<p><a href='admin/index.php'>Admin Dashboard</a></p>";
echo "<p><a href='admin/businesses.php'>Admin Businesses</a></p>";
echo "<p><a href='admin/users.php'>Admin Users</a></p>";
echo "<p><a href='admin/reviews.php'>Admin Reviews</a></p>";

// Test regular pages
echo "<h2>Regular Page Links</h2>";
echo "<p><a href='index.php'>Home Page</a></p>";
echo "<p><a href='businesses.php'>Businesses Page</a></p>";
echo "<p><a href='auth/login.php'>Login Page</a></p>";
?> 