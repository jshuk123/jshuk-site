<?php
require_once '../config/config.php';
require_once '../includes/subscription_functions.php';

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
$gemachim = [];
$stats = [];
$categories = [];

try {
    if (isset($pdo) && $pdo) {
        // Handle actions
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            $gemach_id = $_POST['gemach_id'] ?? 0;
            
            switch ($action) {
                case 'approve':
                    $stmt = $pdo->prepare("UPDATE gemachim SET status = 'active', verified = 1 WHERE id = ?");
                    $stmt->execute([$gemach_id]);
                    $success_message = "Gemach approved successfully!";
                    break;
                    
                case 'reject':
                    $stmt = $pdo->prepare("UPDATE gemachim SET status = 'inactive' WHERE id = ?");
                    $stmt->execute([$gemach_id]);
                    $success_message = "Gemach rejected successfully!";
                    break;
                    
                case 'feature':
                    $stmt = $pdo->prepare("UPDATE gemachim SET featured = 1 WHERE id = ?");
                    $stmt->execute([$gemach_id]);
                    $success_message = "Gemach featured successfully!";
                    break;
                    
                case 'unfeature':
                    $stmt = $pdo->prepare("UPDATE gemachim SET featured = 0 WHERE id = ?");
                    $stmt->execute([$gemach_id]);
                    $success_message = "Gemach unfeatured successfully!";
                    break;
                    
                case 'delete':
                    $stmt = $pdo->prepare("DELETE FROM gemachim WHERE id = ?");
                    $stmt->execute([$gemach_id]);
                    $success_message = "Gemach deleted successfully!";
                    break;
                    
                case 'toggle_donation':
                    $stmt = $pdo->prepare("UPDATE gemachim SET donation_enabled = NOT donation_enabled WHERE id = ?");
                    $stmt->execute([$gemach_id]);
                    $success_message = "Donation setting updated successfully!";
                    break;
            }
        }
        
        // Load statistics
        $stmt = $pdo->query("SELECT COUNT(*) FROM gemachim");
        $stats['total'] = $stmt->fetchColumn();
        
        $stmt = $pdo->query("SELECT COUNT(*) FROM gemachim WHERE status = 'pending'");
        $stats['pending'] = $stmt->fetchColumn();
        
        $stmt = $pdo->query("SELECT COUNT(*) FROM gemachim WHERE status = 'active'");
        $stats['active'] = $stmt->fetchColumn();
        
        $stmt = $pdo->query("SELECT COUNT(*) FROM gemachim WHERE featured = 1");
        $stats['featured'] = $stmt->fetchColumn();
        
        $stmt = $pdo->query("SELECT COUNT(*) FROM gemach_donations WHERE status = 'completed'");
        $stats['donations'] = $stmt->fetchColumn();
        
        // Load categories
        $stmt = $pdo->query("SELECT id, name FROM gemach_categories WHERE is_active = 1 ORDER BY name");
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Load gemachim with filters
        $where_conditions = ["1=1"];
        $params = [];
        
        if (!empty($_GET['status'])) {
            $where_conditions[] = "g.status = ?";
            $params[] = $_GET['status'];
        }
        
        if (!empty($_GET['category'])) {
            $where_conditions[] = "g.category_id = ?";
            $params[] = $_GET['category'];
        }
        
        if (!empty($_GET['search'])) {
            $where_conditions[] = "(g.name LIKE ? OR g.description LIKE ? OR g.location LIKE ?)";
            $search_param = "%{$_GET['search']}%";
            $params[] = $search_param;
            $params[] = $search_param;
            $params[] = $search_param;
        }
        
        $where_clause = implode(" AND ", $where_conditions);
        
        $stmt = $pdo->prepare("
            SELECT g.*, gc.name as category_name, u.name as submitted_by_name
            FROM gemachim g
            LEFT JOIN gemach_categories gc ON g.category_id = gc.id
            LEFT JOIN users u ON g.submitted_by = u.id
            WHERE $where_clause
            ORDER BY g.created_at DESC
        ");
        $stmt->execute($params);
        $gemachim = $stmt->fetchAll();
        
    }
} catch (Exception $e) {
    $error_message = $e->getMessage();
}

$pageTitle = "Admin - Gemachim Management | JShuk";
include '../includes/header_main.php';
?>

<!-- Admin Header -->
<div class="admin-header">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center">
            <h1 class="admin-title">Gemachim Management</h1>
            <a href="/admin/" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i>
                Back to Admin
            </a>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<section class="admin-stats">
    <div class="container">
        <div class="row">
            <div class="col-md-2">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-list"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?= $stats['total'] ?? 0 ?></div>
                        <div class="stat-label">Total Gemachim</div>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stat-card pending">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?= $stats['pending'] ?? 0 ?></div>
                        <div class="stat-label">Pending Approval</div>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stat-card active">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?= $stats['active'] ?? 0 ?></div>
                        <div class="stat-label">Active</div>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stat-card featured">
                    <div class="stat-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?= $stats['featured'] ?? 0 ?></div>
                        <div class="stat-label">Featured</div>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stat-card donations">
                    <div class="stat-icon">
                        <i class="fas fa-heart"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-number"><?= $stats['donations'] ?? 0 ?></div>
                        <div class="stat-label">Total Donations</div>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-plus"></i>
                    </div>
                    <div class="stat-content">
                        <a href="/add_gemach.php" class="btn btn-primary btn-sm">Add New</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Filters and Search -->
<section class="admin-filters">
    <div class="container">
        <div class="filter-card">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" 
                           value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" 
                           placeholder="Search gemachim...">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="pending" <?= ($_GET['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="active" <?= ($_GET['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= ($_GET['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Category</label>
                    <select name="category" class="form-select">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $category): ?>
                        <option value="<?= $category['id'] ?>" 
                                <?= (($_GET['category'] ?? '') == $category['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($category['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i>
                            Filter
                        </button>
                        <a href="/admin/gemachim.php" class="btn btn-outline-secondary">
                            <i class="fas fa-times"></i>
                            Clear
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</section>

<!-- Messages -->
<?php if ($success_message): ?>
<div class="container">
    <div class="alert alert-success" role="alert">
        <i class="fas fa-check-circle"></i>
        <?= htmlspecialchars($success_message) ?>
    </div>
</div>
<?php endif; ?>

<?php if ($error_message): ?>
<div class="container">
    <div class="alert alert-danger" role="alert">
        <i class="fas fa-exclamation-triangle"></i>
        <?= htmlspecialchars($error_message) ?>
    </div>
</div>
<?php endif; ?>

<!-- Gemachim Table -->
<section class="admin-table">
    <div class="container">
        <div class="table-card">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Location</th>
                            <th>Status</th>
                            <th>Featured</th>
                            <th>Donations</th>
                            <th>Submitted By</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($gemachim)): ?>
                        <tr>
                            <td colspan="10" class="text-center py-4">
                                <i class="fas fa-inbox fa-2x text-muted mb-2"></i>
                                <p class="text-muted">No gemachim found</p>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($gemachim as $gemach): ?>
                            <tr>
                                <td><?= $gemach['id'] ?></td>
                                <td>
                                    <div class="gemach-name">
                                        <strong><?= htmlspecialchars($gemach['name']) ?></strong>
                                        <?php if ($gemach['urgent_need']): ?>
                                        <span class="badge bg-danger ms-1">Urgent</span>
                                        <?php endif; ?>
                                        <?php if ($gemach['in_memory_of']): ?>
                                        <span class="badge bg-purple ms-1">Memory</span>
                                        <?php endif; ?>
                                    </div>
                                    <small class="text-muted"><?= htmlspecialchars(substr($gemach['description'], 0, 100)) ?>...</small>
                                </td>
                                <td>
                                    <span class="badge bg-secondary"><?= htmlspecialchars($gemach['category_name']) ?></span>
                                </td>
                                <td><?= htmlspecialchars($gemach['location']) ?></td>
                                <td>
                                    <?php
                                    $status_class = match($gemach['status']) {
                                        'active' => 'success',
                                        'pending' => 'warning',
                                        'inactive' => 'danger',
                                        default => 'secondary'
                                    };
                                    ?>
                                    <span class="badge bg-<?= $status_class ?>"><?= ucfirst($gemach['status']) ?></span>
                                </td>
                                <td>
                                    <?php if ($gemach['featured']): ?>
                                    <span class="badge bg-warning">Featured</span>
                                    <?php else: ?>
                                    <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($gemach['donation_enabled']): ?>
                                    <span class="badge bg-success">Enabled</span>
                                    <?php else: ?>
                                    <span class="text-muted">Disabled</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($gemach['submitted_by_name']): ?>
                                    <?= htmlspecialchars($gemach['submitted_by_name']) ?>
                                    <?php else: ?>
                                    <span class="text-muted">Anonymous</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small><?= date('M j, Y', strtotime($gemach['created_at'])) ?></small>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle" 
                                                data-bs-toggle="dropdown" aria-expanded="false">
                                            Actions
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li>
                                                <a class="dropdown-item" href="/gemachim.php?id=<?= $gemach['id'] ?>" target="_blank">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                            </li>
                                            <?php if ($gemach['status'] === 'pending'): ?>
                                            <li>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="approve">
                                                    <input type="hidden" name="gemach_id" value="<?= $gemach['id'] ?>">
                                                    <button type="submit" class="dropdown-item text-success">
                                                        <i class="fas fa-check"></i> Approve
                                                    </button>
                                                </form>
                                            </li>
                                            <li>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="reject">
                                                    <input type="hidden" name="gemach_id" value="<?= $gemach['id'] ?>">
                                                    <button type="submit" class="dropdown-item text-danger">
                                                        <i class="fas fa-times"></i> Reject
                                                    </button>
                                                </form>
                                            </li>
                                            <?php endif; ?>
                                            <?php if ($gemach['status'] === 'active'): ?>
                                            <li>
                                                <?php if ($gemach['featured']): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="unfeature">
                                                    <input type="hidden" name="gemach_id" value="<?= $gemach['id'] ?>">
                                                    <button type="submit" class="dropdown-item">
                                                        <i class="fas fa-star"></i> Unfeature
                                                    </button>
                                                </form>
                                                <?php else: ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="feature">
                                                    <input type="hidden" name="gemach_id" value="<?= $gemach['id'] ?>">
                                                    <button type="submit" class="dropdown-item">
                                                        <i class="fas fa-star"></i> Feature
                                                    </button>
                                                </form>
                                                <?php endif; ?>
                                            </li>
                                            <li>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="toggle_donation">
                                                    <input type="hidden" name="gemach_id" value="<?= $gemach['id'] ?>">
                                                    <button type="submit" class="dropdown-item">
                                                        <i class="fas fa-heart"></i> 
                                                        <?= $gemach['donation_enabled'] ? 'Disable' : 'Enable' ?> Donations
                                                    </button>
                                                </form>
                                            </li>
                                            <?php endif; ?>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <form method="POST" style="display: inline;" 
                                                      onsubmit="return confirm('Are you sure you want to delete this gemach?')">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="gemach_id" value="<?= $gemach['id'] ?>">
                                                    <button type="submit" class="dropdown-item text-danger">
                                                        <i class="fas fa-trash"></i> Delete
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
</section>

<!-- Export Section -->
<section class="admin-export">
    <div class="container">
        <div class="export-card">
            <h3>Export Data</h3>
            <div class="row">
                <div class="col-md-4">
                    <a href="/admin/export_gemachim.php?format=csv" class="btn btn-outline-primary">
                        <i class="fas fa-download"></i>
                        Export as CSV
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="/admin/export_gemachim.php?format=json" class="btn btn-outline-secondary">
                        <i class="fas fa-code"></i>
                        Export as JSON
                    </a>
                </div>
                <div class="col-md-4">
                    <a href="/admin/gemach_analytics.php" class="btn btn-outline-info">
                        <i class="fas fa-chart-bar"></i>
                        View Analytics
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
/* Admin Styles */
.admin-header {
    background: linear-gradient(135deg, #1a3353 0%, #2C4E6D 100%);
    color: white;
    padding: 2rem 0;
    margin-bottom: 2rem;
}

.admin-title {
    margin: 0;
    font-size: 2rem;
    font-weight: 700;
}

.admin-stats {
    margin-bottom: 2rem;
}

.stat-card {
    background: white;
    border-radius: 10px;
    padding: 1.5rem;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    gap: 1rem;
    transition: transform 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: white;
    background: linear-gradient(135deg, #ffd700, #ffed4e);
}

.stat-card.pending .stat-icon {
    background: linear-gradient(135deg, #ffc107, #ffca2c);
}

.stat-card.active .stat-icon {
    background: linear-gradient(135deg, #28a745, #34ce57);
}

.stat-card.featured .stat-icon {
    background: linear-gradient(135deg, #fd7e14, #ff922b);
}

.stat-card.donations .stat-icon {
    background: linear-gradient(135deg, #e83e8c, #f06292);
}

.stat-number {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1a3353;
    line-height: 1;
}

.stat-label {
    font-size: 0.875rem;
    color: #666;
    margin-top: 0.25rem;
}

.admin-filters {
    margin-bottom: 2rem;
}

.filter-card {
    background: white;
    border-radius: 10px;
    padding: 1.5rem;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.admin-table {
    margin-bottom: 2rem;
}

.table-card {
    background: white;
    border-radius: 10px;
    padding: 1.5rem;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.table th {
    background: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
    font-weight: 600;
    color: #1a3353;
}

.gemach-name {
    margin-bottom: 0.5rem;
}

.admin-export {
    margin-bottom: 2rem;
}

.export-card {
    background: white;
    border-radius: 10px;
    padding: 1.5rem;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.export-card h3 {
    margin-bottom: 1rem;
    color: #1a3353;
}

.badge.bg-purple {
    background-color: #6f42c1 !important;
}

@media (max-width: 768px) {
    .stat-card {
        margin-bottom: 1rem;
    }
    
    .table-responsive {
        font-size: 0.875rem;
    }
    
    .btn-group {
        display: block;
    }
    
    .btn-group .btn {
        width: 100%;
        margin-bottom: 0.25rem;
    }
}
</style>

<?php include '../includes/footer_main.php'; ?> 