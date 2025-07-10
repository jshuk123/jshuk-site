<?php
session_start();
require_once '../../config/config.php';

// Check admin access
function checkAdminAccess() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../../index.php');
        exit();
    }

    global $pdo;
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user || $user['role'] !== 'admin') {
        header('Location: ../../index.php');
        exit();
    }
}

checkAdminAccess();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = intval($_POST['user_id'] ?? 0);

    if ($user_id <= 0) {
        $_SESSION['admin_error'] = 'Invalid user ID.';
        header("Location: ../users.php");
        exit();
    }

    // Check if user exists
    $stmt = $pdo->prepare("SELECT username, is_banned FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user) {
        $_SESSION['admin_error'] = 'User not found.';
        header("Location: ../users.php");
        exit();
    }

    // Unban the user
    $stmt = $pdo->prepare("UPDATE users SET is_banned = 0, banned_at = NULL, ban_reason = NULL, is_active = 1 WHERE id = ?");
    $stmt->execute([$user_id]);

    // Log the admin action
    $stmt = $pdo->prepare("INSERT INTO admin_logs (admin_id, action, table_name, record_id, details, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $_SESSION['user_id'],
        'unban_user',
        'users',
        $user_id,
        "Unbanned user: {$user['username']}",
        $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);

    $_SESSION['admin_message'] = "User '{$user['username']}' has been unbanned successfully.";
    header("Location: ../users.php");
    exit();
} else {
    // If accessed directly without POST, redirect to users page
    header("Location: ../users.php");
    exit();
} 