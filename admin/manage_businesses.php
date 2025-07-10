<?php
session_start();
require_once '../config/config.php';

function checkAdminAccess() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../index.php');
        exit();
    }

    global $pdo;
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user || $user['role'] !== 'admin') {
        header('Location: ../index.php');
        exit();
    }
}
checkAdminAccess();

// Toggle Featured
if (isset($_GET['feature'])) {
    $id = (int)$_GET['feature'];
    $stmt = $pdo->prepare("SELECT is_featured FROM businesses WHERE id = ?");
    $stmt->execute([$id]);
    $is_featured = $stmt->fetchColumn();

    $newStatus = $is_featured ? 0 : 1;
    $featured_until = $newStatus ? date('Y-m-d H:i:s', strtotime('+30 days')) : null;
    $stmt = $pdo->prepare("UPDATE businesses SET is_featured = ?, featured_until = ? WHERE id = ?");
    $stmt->execute([$newStatus, $featured_until, $id]);

    $_SESSION['admin_message'] = $newStatus ? "Business marked as featured." : "Business un-featured.";
    header("Location: manage_businesses.php");
    exit();
}

// Fetch Businesses
$stmt = $pdo->query("SELECT b.*, c.name AS category_name, u.username FROM businesses b LEFT JOIN business_categories c ON b.category_id = c.id LEFT JOIN users u ON b.user_id = u.id ORDER BY b.created_at DESC");
$businesses = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Businesses</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container py-5">
<h1 class="mb-4">Business Listings</h1>

<?php if (isset($_SESSION['admin_message'])): ?>
  <div class="alert alert-info"> <?= $_SESSION['admin_message']; unset($_SESSION['admin_message']); ?> </div>
<?php endif; ?>

<div class="table-responsive">
<table class="table table-bordered table-striped">
  <thead class="table-dark">
    <tr>
      <th>Business</th>
      <th>Owner</th>
      <th>Category</th>
      <th>Status</th>
      <th>Featured</th>
      <th>Created</th>
      <th>Actions</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($businesses as $b): ?>
    <tr>
      <td><?= htmlspecialchars($b['business_name']) ?></td>
      <td><?= htmlspecialchars($b['username']) ?></td>
      <td><?= htmlspecialchars($b['category_name']) ?></td>
      <td><span class="badge <?= $b['status'] === 'active' ? 'bg-success' : 'bg-secondary' ?>"><?= $b['status'] ?></span></td>
      <td>
        <span class="badge <?= $b['is_featured'] ? 'bg-warning text-dark' : 'bg-light text-muted' ?>">
          <?= $b['is_featured'] ? 'Yes' : 'No' ?>
        </span>
        <a href="manage_businesses.php?feature=<?= $b['id'] ?>" class="btn btn-sm btn-outline-primary ms-1">Toggle</a>
      </td>
      <td><?= $b['created_at'] ?></td>
      <td>
        <a href="../business.php?id=<?= $b['id'] ?>" target="_blank" class="btn btn-sm btn-info">👁️ View</a>
      </td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
</div>
</body>
</html> 