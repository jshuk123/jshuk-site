<?php
require_once 'config/config.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
    http_response_code(404);
    $pageTitle = "Not Found";
    include 'includes/header_main.php';
    echo '<div class="container py-5 text-center"><h2>Classified not found.</h2></div>';
    include 'includes/footer_main.php';
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT c.* FROM classifieds c WHERE c.id = ? AND c.is_active = 1 LIMIT 1");
    $stmt->execute([$id]);
    // Query ran successfully
    $c = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "QUERY ERROR: " . $e->getMessage();
    exit;
}

if (!$c) {
    http_response_code(404);
    $pageTitle = "Not Found";
    include 'includes/header_main.php';
    echo '<div class="container py-5 text-center"><h2>Classified not found or inactive.</h2></div>';
    include 'includes/footer_main.php';
    exit;
}
$pageTitle = htmlspecialchars($c['title']);
include 'includes/header_main.php';
?>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-lg">
                <?php if ($c['image_path']): ?>
                    <img src="<?= htmlspecialchars($c['image_path']) ?>" class="card-img-top" alt="Classified image" style="object-fit:cover;max-height:350px;">
                <?php endif; ?>
                <div class="card-body">
                    <h2 class="card-title mb-2"><?= htmlspecialchars($c['title']) ?></h2>
                    <?php if ($c['price']): ?>
                        <div class="mb-2 fw-bold">Price: <?= htmlspecialchars($c['price']) ?></div>
                    <?php endif; ?>
                    <?php if ($c['location']): ?>
                        <div class="mb-2"><i class="fa fa-map-marker-alt me-1"></i><?= htmlspecialchars($c['location']) ?></div>
                    <?php endif; ?>
                    <p class="card-text mb-3"> <?= nl2br(htmlspecialchars($c['description'])) ?> </p>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include 'includes/footer_main.php'; ?> 