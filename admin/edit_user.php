<?php
require_once '../config/config.php';
session_start();

if (!isset($_GET['id'])) {
    die('No user ID specified.');
}

$id = (int) $_GET['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $role = $_POST['role'];

    $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, role = ? WHERE id = ?");
    $stmt->execute([$username, $email, $role, $id]);

    $_SESSION['admin_message'] = "User updated successfully.";
    header("Location: users.php");
    exit();
}

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
    <title>Edit User</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-5">
    <h2>Edit User</h2>
    <form method="post" class="mt-4">
        <div class="mb-3">
            <label class="form-label">Username</label>
            <input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Role</label>
            <select name="role" class="form-select">
                <option value="user" <?php if ($user['role'] === 'user') echo 'selected'; ?>>User</option>
                <option value="admin" <?php if ($user['role'] === 'admin') echo 'selected'; ?>>Admin</option>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Save Changes</button>
        <a href="users.php" class="btn btn-secondary ms-2">Cancel</a>
    </form>
</body>
</html>
