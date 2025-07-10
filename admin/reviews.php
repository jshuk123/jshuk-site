<?php
/**
 * Admin Review Management
 * Allows admins to manage all reviews and testimonials with audit logging
 * Version: 1.2
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/config.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: /auth/login.php');
    exit();
}

$admin_user = $_SESSION['username'] ?? 'Unknown Admin';

// Handle actions
$action = $_GET['action'] ?? '';
$message = '';
$message_type = '';

if ($action === 'edit_review' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $review_id = (int)$_POST['review_id'];
    $new_rating = (int)$_POST['new_rating'];
    $notes = trim($_POST['notes'] ?? '');
    
    if ($new_rating >= 1 && $new_rating <= 5) {
        try {
            $pdo->beginTransaction();
            
            // Get old rating
            $stmt = $pdo->prepare("SELECT rating FROM reviews WHERE id = ?");
            $stmt->execute([$review_id]);
            $old_rating = $stmt->fetchColumn();
            
            // Update review
            $stmt = $pdo->prepare("
                UPDATE reviews 
                SET rating = ?, modified_by_admin = TRUE 
                WHERE id = ?
            ");
            $stmt->execute([$new_rating, $review_id]);
            
            // Log the change
            $stmt = $pdo->prepare("
                INSERT INTO reviews_log (review_id, admin_user, old_rating, new_rating, notes)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$review_id, $admin_user, $old_rating, $new_rating, $notes]);
            
            $pdo->commit();
            $message = 'Review updated successfully';
            $message_type = 'success';
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = 'Error updating review: ' . $e->getMessage();
            $message_type = 'danger';
        }
    } else {
        $message = 'Invalid rating value';
        $message_type = 'danger';
    }
}

if ($action === 'delete_review' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $review_id = (int)$_POST['review_id'];
    
    try {
        $stmt = $pdo->prepare("DELETE FROM reviews WHERE id = ?");
        $stmt->execute([$review_id]);
        
        $message = 'Review deleted successfully';
        $message_type = 'success';
        
    } catch (Exception $e) {
        $message = 'Error deleting review: ' . $e->getMessage();
        $message_type = 'danger';
    }
}

// Get reviews with business info
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 20;
$offset = ($page - 1) * $per_page;

$search = $_GET['search'] ?? '';
$business_filter = $_GET['business_id'] ?? '';

// Build query
$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "(b.business_name LIKE ? OR r.ip_address LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($business_filter) {
    $where_conditions[] = "r.business_id = ?";
    $params[] = $business_filter;
}

$where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total count
$count_stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM reviews r
    JOIN businesses b ON r.business_id = b.id
    $where_clause
");
$count_stmt->execute($params);
$total_reviews = $count_stmt->fetchColumn();
$total_pages = ceil($total_reviews / $per_page);

// Get reviews
$stmt = $pdo->prepare("
    SELECT r.*, b.business_name, b.slug as business_slug
    FROM reviews r
    JOIN businesses b ON r.business_id = b.id
    $where_clause
    ORDER BY r.submitted_at DESC
    LIMIT ? OFFSET ?
");
$params[] = $per_page;
$params[] = $offset;
$stmt->execute($params);
$reviews = $stmt->fetchAll();

// Get businesses for filter
$businesses_stmt = $pdo->prepare("SELECT id, business_name FROM businesses WHERE status = 'active' ORDER BY business_name");
$businesses_stmt->execute();
$businesses = $businesses_stmt->fetchAll();

$pageTitle = "Admin Review Management | JShuk";
include '../includes/header_main.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">
                            <i class="fas fa-star me-2"></i>
                            Review Management
                        </h4>
                        <div class="admin-badge">
                            <span class="badge bg-light text-dark">
                                Admin Panel
                            </span>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    
                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
                            <?php echo htmlspecialchars($message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Filters -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <form method="GET" class="d-flex">
                                <input type="text" name="search" class="form-control me-2" 
                                       placeholder="Search business name or IP..." 
                                       value="<?php echo htmlspecialchars($search); ?>">
                                <button type="submit" class="btn btn-outline-primary">Search</button>
                            </form>
                        </div>
                        <div class="col-md-4">
                            <select name="business_id" class="form-select" onchange="this.form.submit()">
                                <option value="">All Businesses</option>
                                <?php foreach ($businesses as $business): ?>
                                    <option value="<?php echo $business['id']; ?>" 
                                            <?php echo $business_filter == $business['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($business['business_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>" 
                               class="btn btn-outline-secondary">Clear</a>
                        </div>
                    </div>

                    <!-- Reviews Table -->
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Business</th>
                                    <th>Rating</th>
                                    <th>IP Address</th>
                                    <th>Date</th>
                                    <th>Modified?</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($reviews)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4">
                                            <i class="fas fa-inbox fa-2x text-muted mb-3"></i>
                                            <p class="text-muted">No reviews found</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($reviews as $review): ?>
                                        <tr>
                                            <td><?php echo $review['id']; ?></td>
                                            <td>
                                                <a href="/business.php?id=<?php echo $review['business_id']; ?>" 
                                                   target="_blank">
                                                    <?php echo htmlspecialchars($review['business_name']); ?>
                                                </a>
                                            </td>
                                            <td>
                                                <div class="text-warning">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <i class="fas fa-star <?php echo $i <= $review['rating'] ? '' : 'text-muted'; ?>"></i>
                                                    <?php endfor; ?>
                                                    <span class="ms-1"><?php echo $review['rating']; ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <code><?php echo htmlspecialchars($review['ip_address']); ?></code>
                                            </td>
                                            <td><?php echo date('M j, Y H:i', strtotime($review['submitted_at'])); ?></td>
                                            <td>
                                                <?php if ($review['modified_by_admin']): ?>
                                                    <span class="badge bg-warning">Modified</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Original</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-outline-primary" 
                                                            onclick="editReview(<?php echo $review['id']; ?>, <?php echo $review['rating']; ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-outline-danger" 
                                                            onclick="deleteReview(<?php echo $review['id']; ?>)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <nav aria-label="Review pagination">
                            <ul class="pagination justify-content-center">
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Review Modal -->
<div class="modal fade" id="editReviewModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Review Rating</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="?action=edit_review">
                <div class="modal-body">
                    <input type="hidden" name="review_id" id="edit_review_id">
                    
                    <div class="mb-3">
                        <label class="form-label">New Rating:</label>
                        <div class="rating-input">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <input type="radio" name="new_rating" value="<?php echo $i; ?>" id="star<?php echo $i; ?>">
                                <label for="star<?php echo $i; ?>" class="star-label">
                                    <i class="fas fa-star"></i>
                                </label>
                            <?php endfor; ?>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label">Admin Notes (optional):</label>
                        <textarea name="notes" id="notes" class="form-control" rows="3" 
                                  placeholder="Reason for modification..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Rating</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Review Modal -->
<div class="modal fade" id="deleteReviewModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Review</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this review? This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <form method="POST" action="?action=delete_review" style="display: inline;">
                    <input type="hidden" name="review_id" id="delete_review_id">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete Review</button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.rating-input {
    display: flex;
    flex-direction: row-reverse;
    gap: 5px;
}

.rating-input input[type="radio"] {
    display: none;
}

.star-label {
    cursor: pointer;
    color: #ddd;
    font-size: 1.5em;
    transition: color 0.2s;
}

.rating-input input[type="radio"]:checked ~ .star-label,
.star-label:hover,
.star-label:hover ~ .star-label {
    color: #ffc107;
}
</style>

<script>
function editReview(reviewId, currentRating) {
    document.getElementById('edit_review_id').value = reviewId;
    
    // Set current rating
    document.querySelector(`input[name="new_rating"][value="${currentRating}"]`).checked = true;
    
    // Update star display
    updateStarDisplay(currentRating);
    
    new bootstrap.Modal(document.getElementById('editReviewModal')).show();
}

function deleteReview(reviewId) {
    document.getElementById('delete_review_id').value = reviewId;
    new bootstrap.Modal(document.getElementById('deleteReviewModal')).show();
}

function updateStarDisplay(rating) {
    const stars = document.querySelectorAll('.star-label i');
    stars.forEach((star, index) => {
        if (index < rating) {
            star.style.color = '#ffc107';
        } else {
            star.style.color = '#ddd';
        }
    });
}

// Add event listeners for star rating
document.querySelectorAll('input[name="new_rating"]').forEach(input => {
    input.addEventListener('change', function() {
        updateStarDisplay(parseInt(this.value));
    });
});
</script>

<?php include '../includes/footer_main.php'; ?> 