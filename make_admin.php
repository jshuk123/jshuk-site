<?php
// This is a temporary, one-time-use script to grant admin privileges.
// PLEASE DELETE THIS FILE IMMEDIATELY AFTER USE.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config/config.php';

// Step 1: Check if a user is logged in.
if (!isset($_SESSION['user_id'])) {
    header('Location: /auth/login.php');
    exit('<h2>Error: You must be logged in to use this script.</h2><p>Please <a href="/auth/login.php">log in</a> with the account you wish to make an admin, then run this script again.</p>');
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'user';

// Step 2: Update the user in the database.
try {
    // MODIFIED: Assuming the column is 'role' and the admin value is 'admin'.
    $stmt = $pdo->prepare("UPDATE users SET role = 'admin' WHERE id = ?");
    $stmt->execute([$user_id]);

    if ($stmt->rowCount() > 0) {
        // Step 3: Update the current session to reflect the change immediately.
        // We will set both 'is_admin' and 'role' for compatibility.
        $_SESSION['is_admin'] = true;
        $_SESSION['role'] = 'admin';
        
        echo "<h1>Success!</h1>";
        echo "<p>User '<strong>" . htmlspecialchars($user_name) . "</strong>' (ID: " . htmlspecialchars($user_id) . ") has been granted admin privileges by setting their role to 'admin'.</p>";
        echo "<p>Your current session has been updated. You should now see the 'Admin' link in the navigation.</p>";
        echo "<hr>";
        echo "<p><strong>IMPORTANT:</strong> For security reasons, you must now delete this file ('make_admin.php') from your server.</p>";
        echo '<a href="/">Go to Homepage</a>';

    } else {
        echo "<h1>Warning</h1>";
        echo "<p>Could not update user with ID: " . htmlspecialchars($user_id) . ". They may already be an admin, or the user could not be found.</p>";
    }

} catch (PDOException $e) {
    echo "<h1>Database Error</h1>";
    echo "<p>An error occurred: " . $e->getMessage() . "</p>";
    echo "<p><strong>Note:</strong> If the error is 'Unknown column 'role'', please contact support and mention that neither 'is_admin' nor 'role' are the correct columns for administrator status.</p>";
}
