<?php
session_start();
require_once '../config/config.php';

// Only allow admins
if (!isset($_SESSION['user_id']) || empty($_SESSION['is_admin'])) {
    header('Location: /auth/login.php');
    exit;
}

// Handle actions
$msg = '';
if (isset($_GET['action'], $_GET['id'])) {
    $id = (int)$_GET['id'];
    if ($_GET['action'] === 'approve') {
        $stmt = $pdo->prepare("UPDATE classifieds SET status = 'active' WHERE id = ?");
        $stmt->execute([$id]);
        $msg = 'Classified approved.';
    } elseif ($_GET['action'] === 'reject') {
        $stmt = $pdo->prepare("UPDATE classifieds SET status = 'rejected' WHERE id = ?");
        $stmt->execute([$id]);
        $msg = 'Classified rejected.';
    } elseif ($_GET['action'] === 'delete') {
        $stmt = $pdo->prepare("DELETE FROM classifieds WHERE id = ?");
        $stmt->execute([$id]);
        $msg = 'Classified deleted.';
    }
}

// Filter by status
$status = $_GET['status'] ?? '';
$where = '1';
$params = [];
if (in_array($status, ['pending', 'active', 'rejected', 'closed'])) {
    $where = 'c.status = ?';
    $params[] = $status;
}

// Fetch classifieds
$stmt = $pdo->prepare("SELECT c.*, u.username, cat.name AS category_name FROM classifieds c LEFT JOIN users u ON c.user_id = u.id LEFT JOIN classifieds_categories cat ON c.category_id = cat.id WHERE $where ORDER BY c.created_at DESC");
$stmt->execute($params);
$classifieds = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Removed missing header/footer includes
// include '../config/header.php';
?>
<div class="container py-5">
    <h1 class="mb-4">Manage Classifieds</h1>
    <?php if ($msg): ?>
        <div class="alert alert-success"> <?= htmlspecialchars($msg) ?> </div>
    <?php endif; ?>
    <form class="mb-4" method="get">
        <div class="row g-2 align-items-center">
            <div class="col-auto">
                <select name="status" class="form-select" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="rejected" <?= $status === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                </select>
            </div>
        </div>
    </form>
    <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>User</th>
                    <th>Category</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($classifieds as $c): ?>
                    <tr>
                        <td><?= $c['id'] ?></td>
                        <td><?= htmlspecialchars($c['title'] ?? '') ?></td>
                        <td><?= htmlspecialchars($c['username'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($c['category_name'] ?? '') ?></td>
                        <td><span class="badge bg-<?= $c['status'] === 'pending' ? 'warning' : ($c['status'] === 'active' ? 'success' : 'danger') ?>"> <?= htmlspecialchars(ucfirst($c['status'] ?? '')) ?> </span></td>
                        <td><?= htmlspecialchars($c['created_at'] ?? '') ?></td>
                        <td>
                            <?php if (($c['status'] ?? '') === 'pending'): ?>
                                <a href="?action=approve&id=<?= $c['id'] ?>" class="btn btn-success btn-sm">Approve</a>
                                <a href="?action=reject&id=<?= $c['id'] ?>" class="btn btn-warning btn-sm">Reject</a>
                            <?php endif; ?>
                            <a href="?action=delete&id=<?= $c['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this classified?')">Delete</a>
                            <button class="btn btn-info btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#details<?= $c['id'] ?>">View</button>
                        </td>
                    </tr>
                    <tr class="collapse" id="details<?= $c['id'] ?>">
                        <td colspan="7">
                            <strong>Description:</strong> <?= nl2br(htmlspecialchars($c['description'] ?? '')) ?><br>
                            <?php if (!empty($c['image'])): ?>
                                <img src="../<?= htmlspecialchars($c['image']) ?>" alt="Classified image" style="max-width:200px;max-height:200px;">
                            <?php endif; ?>
                            <br><strong>Price:</strong> <?= htmlspecialchars($c['price'] ?? '') ?>
                            <br><strong>Location:</strong> <?= htmlspecialchars($c['location'] ?? '') ?>
                            <br><strong>Contact Email:</strong> <?= htmlspecialchars($c['contact_email'] ?? '') ?>
                            <br><strong>Contact Phone:</strong> <?= htmlspecialchars($c['contact_phone'] ?? '') ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php /* include '../config/footer.php'; */ ?> 