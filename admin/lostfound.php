<?php
require_once '../config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is admin
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: /admin/admin_login.php');
    exit;
}

// Error reporting
if (defined('APP_DEBUG') && APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

$success_message = '';
$error_message = '';
$posts = [];
$claims = [];
$stats = [];

// Handle admin actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error_message = "Invalid request. Please try again.";
    } else {
        try {
            $action = $_POST['action'] ?? '';
            
            switch ($action) {
                case 'update_status':
                    $post_id = (int)$_POST['post_id'];
                    $status = $_POST['status'];
                    
                    if (!in_array($status, ['active', 'reunited', 'archived'])) {
                        throw new Exception("Invalid status.");
                    }
                    
                    $stmt = $pdo->prepare("UPDATE lostfound_posts SET status = ? WHERE id = ?");
                    $stmt->execute([$status, $post_id]);
                    
                    $success_message = "Post status updated successfully.";
                    break;
                    
                case 'update_claim_status':
                    $claim_id = (int)$_POST['claim_id'];
                    $status = $_POST['claim_status'];
                    
                    if (!in_array($status, ['pending', 'approved', 'rejected'])) {
                        throw new Exception("Invalid claim status.");
                    }
                    
                    $stmt = $pdo->prepare("UPDATE lostfound_claims SET status = ? WHERE id = ?");
                    $stmt->execute([$status, $claim_id]);
                    
                    // If claim is approved, mark post as reunited
                    if ($status === 'approved') {
                        $stmt = $pdo->prepare("SELECT post_id FROM lostfound_claims WHERE id = ?");
                        $stmt->execute([$claim_id]);
                        $claim = $stmt->fetch();
                        
                        if ($claim) {
                            $stmt = $pdo->prepare("UPDATE lostfound_posts SET status = 'reunited' WHERE id = ?");
                            $stmt->execute([$claim['post_id']]);
                        }
                    }
                    
                    $success_message = "Claim status updated successfully.";
                    break;
                    
                case 'delete_post':
                    $post_id = (int)$_POST['post_id'];
                    
                    $stmt = $pdo->prepare("DELETE FROM lostfound_claims WHERE post_id = ?");
                    $stmt->execute([$post_id]);
                    
                    $stmt = $pdo->prepare("DELETE FROM lostfound_posts WHERE id = ?");
                    $stmt->execute([$post_id]);
                    
                    $success_message = "Post deleted successfully.";
                    break;
                    
                default:
                    throw new Exception("Invalid action.");
            }
            
        } catch (Exception $e) {
            $error_message = $e->getMessage();
        }
    }
}

try {
    if (isset($pdo) && $pdo) {
        // Get filter parameters
        $post_type = $_GET['type'] ?? '';
        $status = $_GET['status'] ?? '';
        $location = $_GET['location'] ?? '';
        $date_from = $_GET['date_from'] ?? '';
        $date_to = $_GET['date_to'] ?? '';
        
        // Build query for posts
        $where_conditions = ["1=1"];
        $params = [];
        
        if ($post_type) {
            $where_conditions[] = "post_type = ?";
            $params[] = $post_type;
        }
        
        if ($status) {
            $where_conditions[] = "status = ?";
            $params[] = $status;
        }
        
        if ($location) {
            $where_conditions[] = "location = ?";
            $params[] = $location;
        }
        
        if ($date_from) {
            $where_conditions[] = "created_at >= ?";
            $params[] = $date_from . ' 00:00:00';
        }
        
        if ($date_to) {
            $where_conditions[] = "created_at <= ?";
            $params[] = $date_to . ' 23:59:59';
        }
        
        $where_clause = implode(" AND ", $where_conditions);
        
        // Get posts
        $query = "
            SELECT p.*, 
                   COUNT(c.id) as claim_count,
                   u.name as user_name
            FROM lostfound_posts p
            LEFT JOIN lostfound_claims c ON p.id = c.post_id
            LEFT JOIN users u ON p.user_id = u.id
            WHERE {$where_clause}
            GROUP BY p.id
            ORDER BY p.created_at DESC
        ";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get claims
        $stmt = $pdo->query("
            SELECT c.*, p.title as post_title, p.post_type
            FROM lostfound_claims c
            JOIN lostfound_posts p ON c.post_id = p.id
            ORDER BY c.created_at DESC
            LIMIT 50
        ");
        $claims = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get stats
        $stmt = $pdo->query("SELECT COUNT(*) FROM lostfound_posts WHERE status = 'active'");
        $stats['active_posts'] = $stmt->fetchColumn();
        
        $stmt = $pdo->query("SELECT COUNT(*) FROM lostfound_posts WHERE status = 'reunited'");
        $stats['reunited_posts'] = $stmt->fetchColumn();
        
        $stmt = $pdo->query("SELECT COUNT(*) FROM lostfound_claims WHERE status = 'pending'");
        $stats['pending_claims'] = $stmt->fetchColumn();
        
        $stmt = $pdo->query("SELECT COUNT(*) FROM lostfound_posts WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
        $stats['recent_posts'] = $stmt->fetchColumn();
        
    }
} catch (PDOException $e) {
    if (APP_DEBUG) {
        error_log("Admin Lost & Found query error: " . $e->getMessage());
    }
}

$pageTitle = "Lost & Found Management | JShuk Admin";
include '../includes/header_main.php';
?>

<!-- ADMIN HEADER -->
<div class="bg-dark text-white py-3">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 class="h3 mb-0">
                    <i class="fas fa-search me-2"></i>Lost & Found Management
                </h1>
            </div>
            <div class="col-md-6 text-md-end">
                <a href="/admin/" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-arrow-left me-1"></i>Back to Admin
                </a>
            </div>
        </div>
    </div>
</div>

<!-- MAIN CONTENT -->
<div class="container-fluid py-4">
    
    <!-- Messages -->
    <?php if ($success_message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($success_message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($error_message): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error_message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0"><?= $stats['active_posts'] ?? 0 ?></h4>
                            <small>Active Posts</small>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-list fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0"><?= $stats['reunited_posts'] ?? 0 ?></h4>
                            <small>Reunited Items</small>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-check-circle fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0"><?= $stats['pending_claims'] ?? 0 ?></h4>
                            <small>Pending Claims</small>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-clock fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0"><?= $stats['recent_posts'] ?? 0 ?></h4>
                            <small>This Week</small>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-calendar-week fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filter Bar -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-filter me-2"></i>Filters
            </h5>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-2">
                    <label class="form-label">Type</label>
                    <select class="form-select" name="type">
                        <option value="">All Types</option>
                        <option value="lost" <?= $post_type === 'lost' ? 'selected' : '' ?>>Lost</option>
                        <option value="found" <?= $post_type === 'found' ? 'selected' : '' ?>>Found</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <option value="">All Status</option>
                        <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="reunited" <?= $status === 'reunited' ? 'selected' : '' ?>>Reunited</option>
                        <option value="archived" <?= $status === 'archived' ? 'selected' : '' ?>>Archived</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Location</label>
                    <input type="text" class="form-control" name="location" 
                           value="<?= htmlspecialchars($location) ?>" placeholder="Location">
                </div>
                <div class="col-md-2">
                    <label class="form-label">From Date</label>
                    <input type="date" class="form-control" name="date_from" 
                           value="<?= htmlspecialchars($date_from) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">To Date</label>
                    <input type="date" class="form-control" name="date_to" 
                           value="<?= htmlspecialchars($date_to) ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search me-1"></i>Filter
                        </button>
                        <a href="/admin/lostfound.php" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-1"></i>Clear
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Posts Table -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-list me-2"></i>Posts (<?= count($posts) ?>)
            </h5>
            <a href="/lostfound.php" class="btn btn-sm btn-outline-primary" target="_blank">
                <i class="fas fa-external-link-alt me-1"></i>View Public Page
            </a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Type</th>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Location</th>
                            <th>Status</th>
                            <th>Claims</th>
                            <th>Posted</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($posts)): ?>
                            <tr>
                                <td colspan="9" class="text-center py-4 text-muted">
                                    <i class="fas fa-inbox fa-2x mb-2"></i>
                                    <br>No posts found
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($posts as $post): ?>
                                <tr>
                                    <td><?= $post['id'] ?></td>
                                    <td>
                                        <?php if ($post['post_type'] === 'lost'): ?>
                                            <span class="badge bg-danger">Lost</span>
                                        <?php else: ?>
                                            <span class="badge bg-primary">Found</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($post['title']) ?></strong>
                                        <?php if ($post['is_anonymous']): ?>
                                            <small class="text-muted">(Anonymous)</small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($post['category']) ?></td>
                                    <td><?= htmlspecialchars($post['location']) ?></td>
                                    <td>
                                        <?php
                                        $status_class = '';
                                        $status_text = ucfirst($post['status']);
                                        switch ($post['status']) {
                                            case 'active':
                                                $status_class = 'bg-success';
                                                break;
                                            case 'reunited':
                                                $status_class = 'bg-info';
                                                break;
                                            case 'archived':
                                                $status_class = 'bg-secondary';
                                                break;
                                        }
                                        ?>
                                        <span class="badge <?= $status_class ?>"><?= $status_text ?></span>
                                    </td>
                                    <td>
                                        <?php if ($post['claim_count'] > 0): ?>
                                            <span class="badge bg-warning"><?= $post['claim_count'] ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">0</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small><?= date('M j, Y', strtotime($post['created_at'])) ?></small>
                                        <?php if ($post['user_name']): ?>
                                            <br><small class="text-muted">by <?= htmlspecialchars($post['user_name']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-primary" 
                                                    onclick="viewPost(<?= $post['id'] ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-secondary dropdown-toggle dropdown-toggle-split" 
                                                    data-bs-toggle="dropdown">
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                                        <input type="hidden" name="action" value="update_status">
                                                        <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                                                        <button type="submit" name="status" value="active" 
                                                                class="dropdown-item <?= $post['status'] === 'active' ? 'active' : '' ?>">
                                                            <i class="fas fa-check me-2"></i>Mark Active
                                                        </button>
                                                        <button type="submit" name="status" value="reunited" 
                                                                class="dropdown-item <?= $post['status'] === 'reunited' ? 'active' : '' ?>">
                                                            <i class="fas fa-handshake me-2"></i>Mark Reunited
                                                        </button>
                                                        <button type="submit" name="status" value="archived" 
                                                                class="dropdown-item <?= $post['status'] === 'archived' ? 'active' : '' ?>">
                                                            <i class="fas fa-archive me-2"></i>Archive
                                                        </button>
                                                    </form>
                                                </li>
                                                <li><hr class="dropdown-divider"></li>
                                                <li>
                                                    <form method="POST" style="display: inline;" 
                                                          onsubmit="return confirm('Are you sure you want to delete this post?')">
                                                        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                                        <input type="hidden" name="action" value="delete_post">
                                                        <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                                                        <button type="submit" class="dropdown-item text-danger">
                                                            <i class="fas fa-trash me-2"></i>Delete
                                                        </button>
                                                    </form>
                                                </li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Claims Table -->
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-handshake me-2"></i>Recent Claims
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Post</th>
                            <th>Claimant</th>
                            <th>Status</th>
                            <th>Submitted</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($claims)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    <i class="fas fa-inbox fa-2x mb-2"></i>
                                    <br>No claims found
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($claims as $claim): ?>
                                <tr>
                                    <td><?= $claim['id'] ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($claim['post_title']) ?></strong>
                                        <br><small class="text-muted"><?= ucfirst($claim['post_type']) ?></small>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($claim['claimant_name']) ?>
                                        <br><small class="text-muted"><?= htmlspecialchars($claim['contact_email']) ?></small>
                                    </td>
                                    <td>
                                        <?php
                                        $status_class = '';
                                        switch ($claim['status']) {
                                            case 'pending':
                                                $status_class = 'bg-warning';
                                                break;
                                            case 'approved':
                                                $status_class = 'bg-success';
                                                break;
                                            case 'rejected':
                                                $status_class = 'bg-danger';
                                                break;
                                        }
                                        ?>
                                        <span class="badge <?= $status_class ?>"><?= ucfirst($claim['status']) ?></span>
                                    </td>
                                    <td>
                                        <small><?= date('M j, Y g:i A', strtotime($claim['created_at'])) ?></small>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-primary" 
                                                    onclick="viewClaim(<?= $claim['id'] ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <?php if ($claim['status'] === 'pending'): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                                    <input type="hidden" name="action" value="update_claim_status">
                                                    <input type="hidden" name="claim_id" value="<?= $claim['id'] ?>">
                                                    <button type="submit" name="claim_status" value="approved" 
                                                            class="btn btn-outline-success">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                    <button type="submit" name="claim_status" value="rejected" 
                                                            class="btn btn-outline-danger">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- View Post Modal -->
<div class="modal fade" id="viewPostModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Post Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="postModalContent">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>
</div>

<!-- View Claim Modal -->
<div class="modal fade" id="viewClaimModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Claim Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="claimModalContent">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>
</div>

<script>
function viewPost(postId) {
    // TODO: Implement AJAX loading of post details
    alert('View post functionality coming soon!');
}

function viewClaim(claimId) {
    // TODO: Implement AJAX loading of claim details
    alert('View claim functionality coming soon!');
}

// Auto-submit form when filters change
document.addEventListener('DOMContentLoaded', function() {
    const filterForm = document.querySelector('form[method="GET"]');
    const filterInputs = filterForm.querySelectorAll('select, input[type="text"], input[type="date"]');
    
    filterInputs.forEach(input => {
        input.addEventListener('change', () => {
            filterForm.submit();
        });
    });
});
</script>

<?php include '../includes/footer_main.php'; ?> 