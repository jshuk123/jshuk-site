<?php
require_once '../config/config.php';
require_once '../includes/subscription_functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
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

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $retreat_id = (int)($_POST['retreat_id'] ?? 0);
    
    if ($retreat_id && validateCsrfToken($_POST['csrf_token'] ?? '')) {
        try {
            switch ($action) {
                case 'approve':
                    $stmt = $pdo->prepare("UPDATE retreats SET status = 'active', verified = 1 WHERE id = ?");
                    $stmt->execute([$retreat_id]);
                    $success_message = "Retreat approved successfully.";
                    break;
                    
                case 'reject':
                    $stmt = $pdo->prepare("UPDATE retreats SET status = 'inactive' WHERE id = ?");
                    $stmt->execute([$retreat_id]);
                    $success_message = "Retreat rejected successfully.";
                    break;
                    
                case 'feature':
                    $stmt = $pdo->prepare("UPDATE retreats SET featured = 1 WHERE id = ?");
                    $stmt->execute([$retreat_id]);
                    $success_message = "Retreat featured successfully.";
                    break;
                    
                case 'unfeature':
                    $stmt = $pdo->prepare("UPDATE retreats SET featured = 0 WHERE id = ?");
                    $stmt->execute([$retreat_id]);
                    $success_message = "Retreat unfeatured successfully.";
                    break;
                    
                case 'trust_host':
                    $stmt = $pdo->prepare("UPDATE retreats SET trusted_host = 1 WHERE id = ?");
                    $stmt->execute([$retreat_id]);
                    $success_message = "Host marked as trusted.";
                    break;
                    
                case 'delete':
                    $stmt = $pdo->prepare("DELETE FROM retreats WHERE id = ?");
                    $stmt->execute([$retreat_id]);
                    $success_message = "Retreat deleted successfully.";
                    break;
            }
        } catch (PDOException $e) {
            $error_message = "Database error: " . $e->getMessage();
        }
    }
}

// Get filter parameters
$filter_status = $_GET['status'] ?? '';
$filter_category = $_GET['category'] ?? '';
$filter_location = $_GET['location'] ?? '';
$filter_sort = $_GET['sort'] ?? 'recent';
$search_query = $_GET['search'] ?? '';

// Initialize variables
$retreats = [];
$categories = [];
$locations = [];
$stats = ['total' => 0, 'pending' => 0, 'active' => 0, 'inactive' => 0];

try {
    if (isset($pdo) && $pdo) {
        // Load categories
        $stmt = $pdo->query("SELECT id, name FROM retreat_categories WHERE is_active = 1 ORDER BY name");
        $categories = $stmt->fetchAll();
        
        // Load locations
        $stmt = $pdo->query("SELECT id, name FROM retreat_locations WHERE is_active = 1 ORDER BY name");
        $locations = $stmt->fetchAll();
        
        // Build query
        $where_conditions = ["1=1"];
        $params = [];
        
        if ($filter_status) {
            $where_conditions[] = "r.status = ?";
            $params[] = $filter_status;
        }
        
        if ($filter_category) {
            $where_conditions[] = "r.category_id = ?";
            $params[] = $filter_category;
        }
        
        if ($filter_location) {
            $where_conditions[] = "r.location_id = ?";
            $params[] = $filter_location;
        }
        
        if ($search_query) {
            $where_conditions[] = "(r.title LIKE ? OR r.description LIKE ?)";
            $search_param = "%$search_query%";
            $params[] = $search_param;
            $params[] = $search_param;
        }
        
        $where_clause = implode(" AND ", $where_conditions);
        
        // Determine sort order
        $order_clause = match($filter_sort) {
            'title' => 'r.title ASC',
            'price' => 'r.price_per_night ASC',
            'rating' => 'r.rating_average DESC',
            'views' => 'r.views_count DESC',
            default => 'r.created_at DESC'
        };
        
        // Load retreats
        $stmt = $pdo->prepare("
            SELECT r.*, rc.name as category_name, rl.name as location_name,
                   u.first_name, u.last_name, u.email
            FROM retreats r
            LEFT JOIN retreat_categories rc ON r.category_id = rc.id
            LEFT JOIN retreat_locations rl ON r.location_id = rl.id
            LEFT JOIN users u ON r.host_id = u.id
            WHERE $where_clause
            ORDER BY $order_clause
            LIMIT 100
        ");
        $stmt->execute($params);
        $retreats = $stmt->fetchAll();
        
        // Load stats
        $stmt = $pdo->query("SELECT COUNT(*) FROM retreats");
        $stats['total'] = $stmt->fetchColumn();
        
        $stmt = $pdo->query("SELECT COUNT(*) FROM retreats WHERE status = 'pending'");
        $stats['pending'] = $stmt->fetchColumn();
        
        $stmt = $pdo->query("SELECT COUNT(*) FROM retreats WHERE status = 'active'");
        $stats['active'] = $stmt->fetchColumn();
        
        $stmt = $pdo->query("SELECT COUNT(*) FROM retreats WHERE status = 'inactive'");
        $stats['inactive'] = $stmt->fetchColumn();
        
    }
} catch (PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
}

$pageTitle = "Manage Retreats | Admin Dashboard - JShuk";
$page_css = "admin_retreats.css";

include '../includes/header_admin.php';
?>

<!-- ADMIN HEADER -->
<div class="admin-header">
    <div class="container">
        <div class="admin-header-content">
            <h1 class="admin-title">
                <i class="fas fa-home"></i>
                Manage Retreats
            </h1>
            <div class="admin-stats">
                <div class="stat-item">
                    <span class="stat-number"><?= number_format($stats['total']) ?></span>
                    <span class="stat-label">Total</span>
                </div>
                <div class="stat-item pending">
                    <span class="stat-number"><?= number_format($stats['pending']) ?></span>
                    <span class="stat-label">Pending</span>
                </div>
                <div class="stat-item active">
                    <span class="stat-number"><?= number_format($stats['active']) ?></span>
                    <span class="stat-label">Active</span>
                </div>
                <div class="stat-item inactive">
                    <span class="stat-number"><?= number_format($stats['inactive']) ?></span>
                    <span class="stat-label">Inactive</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MESSAGES -->
<?php if (isset($success_message)): ?>
<div class="alert alert-success">
    <i class="fas fa-check-circle"></i>
    <?= htmlspecialchars($success_message) ?>
</div>
<?php endif; ?>

<?php if (isset($error_message)): ?>
<div class="alert alert-danger">
    <i class="fas fa-exclamation-triangle"></i>
    <?= htmlspecialchars($error_message) ?>
</div>
<?php endif; ?>

<!-- FILTERS -->
<div class="admin-filters">
    <div class="container">
        <form method="GET" class="filter-form">
            <div class="filter-row">
                <div class="filter-group">
                    <label for="search">Search</label>
                    <input type="text" id="search" name="search" class="form-control" 
                           value="<?= htmlspecialchars($search_query) ?>" 
                           placeholder="Search retreats...">
                </div>
                
                <div class="filter-group">
                    <label for="status">Status</label>
                    <select id="status" name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="pending" <?= ($filter_status === 'pending') ? 'selected' : '' ?>>Pending</option>
                        <option value="active" <?= ($filter_status === 'active') ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= ($filter_status === 'inactive') ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="category">Category</label>
                    <select id="category" name="category" class="form-select">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $category): ?>
                        <option value="<?= $category['id'] ?>" 
                                <?= ($filter_category == $category['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($category['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="location">Location</label>
                    <select id="location" name="location" class="form-select">
                        <option value="">All Locations</option>
                        <?php foreach ($locations as $location): ?>
                        <option value="<?= $location['id'] ?>" 
                                <?= ($filter_location == $location['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($location['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="sort">Sort By</label>
                    <select id="sort" name="sort" class="form-select">
                        <option value="recent" <?= ($filter_sort === 'recent') ? 'selected' : '' ?>>Most Recent</option>
                        <option value="title" <?= ($filter_sort === 'title') ? 'selected' : '' ?>>Title</option>
                        <option value="price" <?= ($filter_sort === 'price') ? 'selected' : '' ?>>Price</option>
                        <option value="rating" <?= ($filter_sort === 'rating') ? 'selected' : '' ?>>Rating</option>
                        <option value="views" <?= ($filter_sort === 'views') ? 'selected' : '' ?>>Views</option>
                    </select>
                </div>
                
                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i>
                        Filter
                    </button>
                    <a href="?clear=1" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i>
                        Clear
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- RETREATS TABLE -->
<div class="admin-content">
    <div class="container">
        <div class="table-responsive">
            <table class="table table-hover admin-table">
                <thead>
                    <tr>
                        <th>Property</th>
                        <th>Host</th>
                        <th>Category</th>
                        <th>Location</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Stats</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($retreats)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-4">
                            <i class="fas fa-search"></i>
                            <p>No retreats found matching your criteria.</p>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($retreats as $retreat): ?>
                        <tr class="retreat-row <?= $retreat['status'] ?>">
                            <td class="retreat-info">
                                <div class="retreat-image">
                                    <?php if ($retreat['image_paths']): ?>
                                        <?php $images = json_decode($retreat['image_paths'], true); ?>
                                        <img src="<?= htmlspecialchars($images[0] ?? '/images/elite-placeholder.svg') ?>" 
                                             alt="<?= htmlspecialchars($retreat['title']) ?>">
                                    <?php else: ?>
                                        <img src="/images/elite-placeholder.svg" 
                                             alt="<?= htmlspecialchars($retreat['title']) ?>">
                                    <?php endif; ?>
                                </div>
                                <div class="retreat-details">
                                    <h4 class="retreat-title"><?= htmlspecialchars($retreat['title']) ?></h4>
                                    <div class="retreat-meta">
                                        <span class="retreat-id">ID: <?= $retreat['id'] ?></span>
                                        <span class="retreat-date"><?= date('M j, Y', strtotime($retreat['created_at'])) ?></span>
                                    </div>
                                    <div class="retreat-badges">
                                        <?php if ($retreat['verified']): ?>
                                        <span class="badge verified">Verified</span>
                                        <?php endif; ?>
                                        <?php if ($retreat['featured']): ?>
                                        <span class="badge featured">Featured</span>
                                        <?php endif; ?>
                                        <?php if ($retreat['trusted_host']): ?>
                                        <span class="badge trusted">Trusted Host</span>
                                        <?php endif; ?>
                                        <?php if ($retreat['available_this_shabbos']): ?>
                                        <span class="badge available">Available Shabbos</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            
                            <td class="host-info">
                                <?php if ($retreat['host_id']): ?>
                                <div class="host-name">
                                    <?= htmlspecialchars($retreat['first_name'] . ' ' . $retreat['last_name']) ?>
                                </div>
                                <div class="host-email">
                                    <?= htmlspecialchars($retreat['email']) ?>
                                </div>
                                <a href="/users/profile.php?id=<?= $retreat['host_id'] ?>" 
                                   class="btn btn-sm btn-outline-primary" target="_blank">
                                    <i class="fas fa-user"></i>
                                    View Profile
                                </a>
                                <?php else: ?>
                                <span class="text-muted">No host</span>
                                <?php endif; ?>
                            </td>
                            
                            <td class="category-info">
                                <span class="category-name"><?= htmlspecialchars($retreat['category_name']) ?></span>
                                <div class="capacity-info">
                                    Sleeps <?= $retreat['guest_capacity'] ?> • 
                                    <?= $retreat['bedrooms'] ?> bed • 
                                    <?= $retreat['bathrooms'] ?> bath
                                </div>
                            </td>
                            
                            <td class="location-info">
                                <div class="location-name"><?= htmlspecialchars($retreat['location_name']) ?></div>
                                <?php if ($retreat['distance_to_shul']): ?>
                                <div class="distance-info">
                                    <?= $retreat['distance_to_shul'] ?>m to shul
                                </div>
                                <?php endif; ?>
                            </td>
                            
                            <td class="price-info">
                                <div class="price-main">£<?= number_format($retreat['price_per_night']) ?></div>
                                <div class="price-unit">per night</div>
                                <?php if ($retreat['price_shabbos_package']): ?>
                                <div class="price-shabbos">£<?= number_format($retreat['price_shabbos_package']) ?> Shabbos</div>
                                <?php endif; ?>
                            </td>
                            
                            <td class="status-info">
                                <span class="status-badge <?= $retreat['status'] ?>">
                                    <?= ucfirst($retreat['status']) ?>
                                </span>
                                <?php if ($retreat['status'] === 'pending'): ?>
                                <div class="pending-time">
                                    <?= timeAgo($retreat['created_at']) ?>
                                </div>
                                <?php endif; ?>
                            </td>
                            
                            <td class="stats-info">
                                <div class="stat-item">
                                    <i class="fas fa-eye"></i>
                                    <?= number_format($retreat['views_count']) ?>
                                </div>
                                <?php if ($retreat['rating_average'] > 0): ?>
                                <div class="stat-item">
                                    <i class="fas fa-star"></i>
                                    <?= number_format($retreat['rating_average'], 1) ?>
                                    (<?= $retreat['rating_count'] ?>)
                                </div>
                                <?php endif; ?>
                                <div class="stat-item">
                                    <i class="fas fa-calendar-check"></i>
                                    <?= number_format($retreat['bookings_count']) ?>
                                </div>
                            </td>
                            
                            <td class="actions">
                                <div class="action-buttons">
                                    <a href="/retreat.php?id=<?= $retreat['id'] ?>" 
                                       class="btn btn-sm btn-outline-primary" target="_blank">
                                        <i class="fas fa-eye"></i>
                                        View
                                    </a>
                                    
                                    <a href="/admin/edit_retreat.php?id=<?= $retreat['id'] ?>" 
                                       class="btn btn-sm btn-outline-secondary">
                                        <i class="fas fa-edit"></i>
                                        Edit
                                    </a>
                                    
                                    <?php if ($retreat['status'] === 'pending'): ?>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Approve this retreat?')">
                                        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <input type="hidden" name="retreat_id" value="<?= $retreat['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-success">
                                            <i class="fas fa-check"></i>
                                            Approve
                                        </button>
                                    </form>
                                    
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Reject this retreat?')">
                                        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <input type="hidden" name="retreat_id" value="<?= $retreat['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-warning">
                                            <i class="fas fa-times"></i>
                                            Reject
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                    
                                    <?php if ($retreat['status'] === 'active'): ?>
                                        <?php if (!$retreat['featured']): ?>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                            <input type="hidden" name="action" value="feature">
                                            <input type="hidden" name="retreat_id" value="<?= $retreat['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-info">
                                                <i class="fas fa-star"></i>
                                                Feature
                                            </button>
                                        </form>
                                        <?php else: ?>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                            <input type="hidden" name="action" value="unfeature">
                                            <input type="hidden" name="retreat_id" value="<?= $retreat['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-info">
                                                <i class="fas fa-star"></i>
                                                Unfeature
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                        
                                        <?php if (!$retreat['trusted_host']): ?>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                            <input type="hidden" name="action" value="trust_host">
                                            <input type="hidden" name="retreat_id" value="<?= $retreat['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-success">
                                                <i class="fas fa-shield-alt"></i>
                                                Trust Host
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this retreat? This action cannot be undone.')">
                                        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="retreat_id" value="<?= $retreat['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i>
                                            Delete
                                        </button>
                                    </form>
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

<script>
// Auto-hide success messages
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => {
                alert.style.display = 'none';
            }, 300);
        }, 5000);
    });
});

// Confirm actions
function confirmAction(message) {
    return confirm(message);
}
</script>

<?php
// Helper function for time ago
function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) {
        return 'Just now';
    } elseif ($time < 3600) {
        $minutes = floor($time / 60);
        return $minutes . ' min' . ($minutes > 1 ? 's' : '') . ' ago';
    } elseif ($time < 86400) {
        $hours = floor($time / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } else {
        $days = floor($time / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    }
}

include '../includes/footer_admin.php';
?> 