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
    $ban_reason = trim($_POST['ban_reason'] ?? '');

    if ($user_id <= 0) {
        $_SESSION['admin_error'] = 'Invalid user ID.';
        header("Location: ../users.php");
        exit();
    }

    // Prevent admin from banning themselves
    if ($user_id === $_SESSION['user_id']) {
        $_SESSION['admin_error'] = 'You cannot ban your own admin account.';
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

    // Ban the user
    $stmt = $pdo->prepare("UPDATE users SET is_banned = 1, banned_at = NOW(), ban_reason = ?, is_active = 0 WHERE id = ?");
    $stmt->execute([$ban_reason, $user_id]);

    // Log the admin action
    $stmt = $pdo->prepare("INSERT INTO admin_logs (admin_id, action, table_name, record_id, details, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $_SESSION['user_id'],
        'ban_user',
        'users',
        $user_id,
        "Banned user: {$user['username']}. Reason: " . ($ban_reason ?: 'No reason provided'),
        $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);

    $_SESSION['admin_message'] = "User '{$user['username']}' has been banned successfully.";
    header("Location: ../users.php");
    exit();
} else {
    // If accessed directly without POST, redirect to users page
    header("Location: ../users.php");
    exit();
} 