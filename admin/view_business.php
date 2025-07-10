<?php
require_once '../config/config.php';
require_once '../config/db_connect.php';

// Check admin access (reuse logic from businesses.php)
function checkAdminAccess() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../index.php');
        exit();
    }
    global $pdo;
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    if ($user['role'] !== 'admin') {
        header('Location: ../index.php');
        exit();
    }
}
session_start();
checkAdminAccess();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Business (Admin)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container py-5">
    <h1>View Business (Admin)</h1>
    <div class="alert alert-info mt-4">This page is under construction.</div>
    <a href="businesses.php" class="btn btn-secondary mt-3">Back to Businesses</a>
</body>
</html> 