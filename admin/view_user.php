<?php
require_once '../config/config.php';
session_start();

if (!isset($_GET['id'])) {
    die('No user ID specified.');
}

$id = (int) $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    die('User not found.');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View User</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-5">
    <h2>Viewing User: <?php echo htmlspecialchars($user['username']); ?></h2>
    <ul class="list-group mt-4">
        <li class="list-group-item"><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></li>
        <li class="list-group-item"><strong>Role:</strong> <?php echo htmlspecialchars($user['role']); ?></li>
        <li class="list-group-item"><strong>Status:</strong> <?php echo $user['is_active'] ? 'Active' : 'Suspended'; ?></li>
        <?php if (isset($user['is_banned']) && $user['is_banned']): ?>
            <li class="list-group-item list-group-item-danger">
                <strong>Ban Status:</strong> Banned
                <?php if (isset($user['ban_reason']) && $user['ban_reason']): ?>
                    <br><strong>Ban Reason:</strong> <?php echo htmlspecialchars($user['ban_reason']); ?>
                <?php endif; ?>
                <?php if (isset($user['banned_at']) && $user['banned_at']): ?>
                    <br><strong>Banned At:</strong> <?php echo $user['banned_at']; ?>
                <?php endif; ?>
            </li>
        <?php endif; ?>
        <li class="list-group-item"><strong>Created At:</strong> <?php echo $user['created_at']; ?></li>
    </ul>
    <a href="users.php" class="btn btn-secondary mt-3">Back to Users</a>
</body>
</html>
