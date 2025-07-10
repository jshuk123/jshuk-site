<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/config.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /auth/login.php');
    exit();
}

// Get user's businesses with categories and stats
$stmt = $pdo->prepare("
    SELECT 
        b.*,
        c.name as category_name,
        (SELECT COUNT(*) FROM reviews r WHERE r.business_id = b.id) as review_count,
        (SELECT COALESCE(AVG(rating), 0) FROM reviews r WHERE r.business_id = b.id) as average_rating,
        (SELECT COUNT(*) FROM business_products p WHERE p.business_id = b.id) as product_count
    FROM businesses b
    LEFT JOIN business_categories c ON b.category_id = c.id
    WHERE b.user_id = ?
    ORDER BY b.created_at DESC
");

$stmt->execute([$_SESSION['user_id']]);
$businesses = $stmt->fetchAll();

// Fetch main image for each business
$img_stmt = $pdo->prepare("SELECT file_path FROM business_images WHERE business_id = ? AND sort_order = 0 LIMIT 1");
foreach ($businesses as &$business) {
    $img_stmt->execute([$business['id']]);
    $main_image_path = $img_stmt->fetchColumn();
    $business['main_image'] = $main_image_path ? $main_image_path : '/images/default-business.jpg';
}
unset($business);

$pageTitle = "My Businesses";
$page_css = "my_businesses.css";
include '../includes/header_main.php';
?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>My Businesses</h2>
        <a href="post_business.php" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Add New Business
        </a>
    </div>

    <?php if (empty($businesses)): ?>
        <div class="card shadow">
            <div class="card-body text-center py-5">
                <i class="fas fa-store fa-4x text-muted mb-3"></i>
                <h3>No Businesses Yet</h3>
                <p class="text-muted mb-4">Start by adding your first business listing</p>
                <a href="post_business.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-plus me-2"></i>Add Your First Business
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($businesses as $business): ?>
                <div class="col-md-6">
                    <div class="card shadow h-100">
                        <div class="position-relative">
                            <img src="<?php echo htmlspecialchars($business['main_image']); ?>" 
                                 class="card-img-top" alt="<?php echo htmlspecialchars($business['business_name']); ?>"
                                 style="height: 200px; object-fit: cover;">
                            <span class="position-absolute top-0 end-0 m-2 badge bg-<?php 
                                echo $business['biz_status'] === 'active' ? 'success' : 
                                    ($business['biz_status'] === 'pending' ? 'warning' : 'danger'); 
                            ?>">
                                <?php echo ucfirst($business['biz_status']); ?>
                            </span>
                        </div>
                        <div class="card-body">
                            <h4 class="card-title mb-3"><?php echo htmlspecialchars($business['business_name']); ?></h4>
                            
                            <div class="mb-3">
                                <span class="badge bg-primary">
                                    <i class="fas fa-tag me-1"></i><?php echo htmlspecialchars($business['category_name']); ?>
                                </span>
                            </div>
                            
                            <div class="row mb-3 g-2">
                                <div class="col-auto">
                                    <span class="badge bg-light text-dark">
                                        <i class="fas fa-star text-warning me-1"></i>
                                        <?php echo number_format($business['average_rating'], 1); ?>
                                        (<?php echo $business['review_count']; ?> reviews)
                                    </span>
                                </div>
                                <div class="col-auto">
                                    <span class="badge bg-light text-dark">
                                        <i class="fas fa-box me-1"></i>
                                        <?php echo $business['product_count']; ?> products
                                    </span>
                                </div>
                                <div class="col-auto">
                                    <span class="badge bg-light text-dark">
                                        <i class="fas fa-eye me-1"></i>
                                        <?php echo $business['views_count']; ?> views
                                    </span>
                                </div>
                            </div>

                            <p class="card-text text-muted mb-3">
                                <?php echo substr(htmlspecialchars($business['description']), 0, 100); ?>...
                            </p>

                            <div class="d-flex gap-2">
                                <a href="/business.php?id=<?php echo $business['id']; ?>" class="btn btn-outline-primary flex-grow-1">
                                    <i class="fas fa-eye me-1"></i>View Business
                                </a>
                                <button type="button" class="btn btn-outline-danger" 
                                        onclick="confirmDelete(<?php echo $business['id']; ?>)">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-footer bg-light">
                            <small class="text-muted">
                                <i class="fas fa-clock me-1"></i>Created: <?php 
                                    echo date('M j, Y', strtotime($business['created_at'])); 
                                ?>
                            </small>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this business? This action cannot be undone.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteForm" action="/users/delete_business.php" method="POST" style="display: inline;">
                    <input type="hidden" name="business_id" id="deleteBusinessId">
                    <button type="submit" class="btn btn-danger">Delete Business</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="/js/my_businesses.js"></script>
<?php include '../includes/footer_main.php'; ?> 