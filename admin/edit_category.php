<?php
require_once __DIR__ . '/../config/config.php';

if (!isset($_GET['id'])) {
    die('Category ID missing.');
}

$id = (int)$_GET['id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['description'])) {
    $desc = trim($_POST['description']);
    $stmt = $pdo->prepare("UPDATE business_categories SET description = ? WHERE id = ?");
    $stmt->execute([$desc, $id]);
    header('Location: /admin/categories.php?desc_updated=1');
    exit;
}

// Fetch category
$stmt = $pdo->prepare("SELECT * FROM business_categories WHERE id = ?");
$stmt->execute([$id]);
$cat = $stmt->fetch();

if (!$cat) {
    die('Category not found.');
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Category Description</title>
    <link rel="stylesheet" href="/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5">
    <h1>Edit Description for <?= htmlspecialchars($cat['name']) ?></h1>
    <form method="post">
        <div class="mb-3">
            <label for="description" class="form-label">Description</label>
            <textarea name="description" id="description" class="form-control" rows="3" required><?= htmlspecialchars($cat['description']) ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Save</button>
        <a href="/admin/categories.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>
</body>
</html> 