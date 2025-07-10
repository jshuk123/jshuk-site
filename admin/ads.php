<?php
/**
 * Enhanced Ad Management Dashboard
 * Complete ad management system for JShuk admin
 */

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/ad_renderer.php';

// Check admin access
function checkAdminAccess() {
    global $pdo;
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../index.php');
        exit;
    }
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    if (!$user || $user['role'] !== 'admin') {
        header('Location: ../index.php');
        exit;
    }
}
checkAdminAccess();

// Handle status toggle
if (isset($_GET['toggle_status']) && isset($_GET['id'])) {
    $adId = (int)$_GET['id'];
    
    // Check if status column exists before trying to update it
    try {
        $checkStatus = $pdo->query("SHOW COLUMNS FROM ads LIKE 'status'");
        if ($checkStatus->rowCount() > 0) {
            $stmt = $pdo->prepare("UPDATE ads SET status = CASE WHEN status = 'active' THEN 'paused' ELSE 'active' END WHERE id = ?");
            $stmt->execute([$adId]);
        }
        // If status column doesn't exist, just continue without updating
    } catch (PDOException $e) {
        // Error checking status column, continue anyway
    }
    
    // Log the action (if admin_logs table exists)
    try {
        $stmt = $pdo->prepare("INSERT INTO admin_logs (admin_id, action, table_name, record_id, details, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_SESSION['user_id'],
            'TOGGLE_STATUS',
            'ads',
            $adId,
            'Toggled ad status',
            $_SERVER['REMOTE_ADDR']
        ]);
    } catch (PDOException $e) {
        // Log table might not exist, continue anyway
    }
    
    header('Location: ads.php?success=1');
    exit;
}

// Handle delete
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $adId = (int)$_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM ads WHERE id = ?");
    $stmt->execute([$adId]);
    
    // Log the action (if admin_logs table exists)
    try {
        $stmt = $pdo->prepare("INSERT INTO admin_logs (admin_id, action, table_name, record_id, details, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_SESSION['user_id'],
            'DELETE',
            'ads',
            $adId,
            'Deleted ad',
            $_SERVER['REMOTE_ADDR']
        ]);
    } catch (PDOException $e) {
        // Log table might not exist, continue anyway
    }
    
    header('Location: ads.php?success=2');
    exit;
}

// Get filter parameters
$zoneFilter = $_GET['zone'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$categoryFilter = $_GET['category'] ?? '';
$locationFilter = $_GET['location'] ?? '';

// Check if required columns exist
$statusColumnExists = false;
$locationColumnExists = false;
$zoneColumnExists = false;
$categoryColumnExists = false;

try {
    $checkStatus = $pdo->query("SHOW COLUMNS FROM ads LIKE 'status'");
    $statusColumnExists = $checkStatus->rowCount() > 0;
    
    $checkLocation = $pdo->query("SHOW COLUMNS FROM ads LIKE 'location'");
    $locationColumnExists = $checkLocation->rowCount() > 0;
    
    $checkZone = $pdo->query("SHOW COLUMNS FROM ads LIKE 'zone'");
    $zoneColumnExists = $checkZone->rowCount() > 0;
    
    $checkCategory = $pdo->query("SHOW COLUMNS FROM ads LIKE 'category_id'");
    $categoryColumnExists = $checkCategory->rowCount() > 0;
} catch (PDOException $e) {
    // Columns don't exist, continue with defaults
}

// Build query with filters
$sql = "SELECT a.*, c.name as category_name, b.business_name 
        FROM ads a 
        LEFT JOIN business_categories c ON a.category_id = c.id 
        LEFT JOIN businesses b ON a.business_id = b.id 
        WHERE 1=1";
$params = [];

if ($zoneFilter && $zoneColumnExists) {
    $sql .= " AND a.zone = :zone";
    $params[':zone'] = $zoneFilter;
}

if ($statusFilter && $statusColumnExists) {
    $sql .= " AND a.status = :status";
    $params[':status'] = $statusFilter;
}

if ($categoryFilter && $categoryColumnExists) {
    $sql .= " AND a.category_id = :category";
    $params[':category'] = $categoryFilter;
}

if ($locationFilter && $locationColumnExists) {
    $sql .= " AND a.location = :location";
    $params[':location'] = $locationFilter;
}

$sql .= " ORDER BY a.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$ads = $stmt->fetchAll();

// Get categories for filter
$categories = $pdo->query("SELECT id, name FROM business_categories ORDER BY name")->fetchAll();

// Get businesses for filter - check if status column exists in businesses table
$businesses = [];
try {
    // Check if status column exists in businesses table
    $checkBusinessStatus = $pdo->query("SHOW COLUMNS FROM businesses LIKE 'status'");
    if ($checkBusinessStatus->rowCount() > 0) {
        $businesses = $pdo->query("SELECT id, business_name FROM businesses WHERE status = 'active' ORDER BY business_name")->fetchAll();
    } else {
        // No status column, get all businesses
        $businesses = $pdo->query("SELECT id, business_name FROM businesses ORDER BY business_name")->fetchAll();
    }
} catch (PDOException $e) {
    // Error querying businesses table, use empty array
    $businesses = [];
}

// Get locations for filter - check if location column exists first
$locations = [];
if ($locationColumnExists) {
    try {
        $locations = $pdo->query("SELECT DISTINCT location FROM ads WHERE location IS NOT NULL AND location != '' ORDER BY location")->fetchAll();
    } catch (PDOException $e) {
        // Location column doesn't exist, use empty array
        $locations = [];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ad Management - JShuk Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../css/admin_ads.css" rel="stylesheet">
</head>
<body>

<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Ad Management</h1>
            <p class="text-muted">Manage all advertisements across JShuk</p>
        </div>
        <div>
            <a href="add_ad.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add New Ad
            </a>
            <a href="index.php" class="btn btn-outline-secondary ms-2">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Success Messages -->
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php 
            switch ($_GET['success']) {
                case '1': echo 'Ad status updated successfully.'; break;
                case '2': echo 'Ad deleted successfully.'; break;
                case '3': echo 'Ad created successfully.'; break;
                case '4': echo 'Ad updated successfully.'; break;
                default: echo 'Action completed successfully.';
            }
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-filter"></i> Filters</h5>
        </div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-2">
                    <label class="form-label">Zone</label>
                    <select name="zone" class="form-select">
                        <option value="">All Zones</option>
                        <option value="header" <?= $zoneFilter === 'header' ? 'selected' : '' ?>>Header</option>
                        <option value="sidebar" <?= $zoneFilter === 'sidebar' ? 'selected' : '' ?>>Sidebar</option>
                        <option value="footer" <?= $zoneFilter === 'footer' ? 'selected' : '' ?>>Footer</option>
                        <option value="carousel" <?= $zoneFilter === 'carousel' ? 'selected' : '' ?>>Carousel</option>
                        <option value="inline" <?= $zoneFilter === 'inline' ? 'selected' : '' ?>>Inline</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="paused" <?= $statusFilter === 'paused' ? 'selected' : '' ?>>Paused</option>
                        <option value="expired" <?= $statusFilter === 'expired' ? 'selected' : '' ?>>Expired</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Category</label>
                    <select name="category" class="form-select">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $categoryFilter == $cat['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Location</label>
                    <select name="location" class="form-select">
                        <option value="">All Locations</option>
                        <?php foreach ($locations as $loc): ?>
                            <option value="<?= htmlspecialchars($loc['location']) ?>" <?= $locationFilter === $loc['location'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($loc['location']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-search"></i> Filter
                    </button>
                    <a href="ads.php" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i> Clear
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Stats Summary -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Total Ads</h6>
                            <h3 class="mb-0"><?= count($ads) ?></h3>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-ad fa-2x"></i>
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
                            <h6 class="card-title">Active Ads</h6>
                            <h3 class="mb-0"><?= $statusColumnExists ? count(array_filter($ads, fn($ad) => ($ad['status'] ?? 'active') === 'active')) : count($ads) ?></h3>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-check-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Paused Ads</h6>
                            <h3 class="mb-0"><?= $statusColumnExists ? count(array_filter($ads, fn($ad) => ($ad['status'] ?? '') === 'paused')) : 0 ?></h3>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-pause-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Expired Ads</h6>
                            <h3 class="mb-0"><?= $statusColumnExists ? count(array_filter($ads, fn($ad) => ($ad['status'] ?? '') === 'expired')) : 0 ?></h3>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-exclamation-triangle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Ads Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-list"></i> All Ads (<?= count($ads) ?>)</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Ad</th>
                            <th>Zone</th>
                            <th>Targeting</th>
                            <th>Dates</th>
                            <th>Status</th>
                            <th>Priority</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($ads)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">No ads found matching your criteria.</p>
                                    <a href="add_ad.php" class="btn btn-primary">Add Your First Ad</a>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($ads as $ad): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="<?= BASE_PATH . 'uploads/ads/' . $ad['image_url'] ?>" 
                                                 alt="<?= htmlspecialchars($ad['title']) ?>" 
                                                 class="ad-thumbnail me-3">
                                            <div>
                                                <h6 class="mb-1"><?= htmlspecialchars($ad['title']) ?></h6>
                                                <small class="text-muted">
                                                    <i class="fas fa-link"></i> 
                                                    <a href="<?= htmlspecialchars($ad['link_url']) ?>" target="_blank">
                                                        <?= htmlspecialchars(substr($ad['link_url'], 0, 30)) ?>...
                                                    </a>
                                                </small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary"><?= ucfirst($ad['zone'] ?? 'header') ?></span>
                                    </td>
                                    <td>
                                        <div class="small">
                                            <?php if ($ad['category_name']): ?>
                                                <div><strong>Category:</strong> <?= htmlspecialchars($ad['category_name']) ?></div>
                                            <?php endif; ?>
                                            <?php if ($ad['location']): ?>
                                                <div><strong>Location:</strong> <?= htmlspecialchars($ad['location']) ?></div>
                                            <?php endif; ?>
                                            <?php if ($ad['business_name']): ?>
                                                <div><strong>Business:</strong> <?= htmlspecialchars($ad['business_name']) ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="small">
                                            <div><strong>Start:</strong> <?= $ad['start_date'] ? date('M j, Y', strtotime($ad['start_date'])) : 'Not set' ?></div>
                                            <div><strong>End:</strong> <?= $ad['end_date'] ? date('M j, Y', strtotime($ad['end_date'])) : 'Not set' ?></div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php
                                        $status = $ad['status'] ?? 'active';
                                        $statusClass = match($status) {
                                            'active' => 'success',
                                            'paused' => 'warning',
                                            'expired' => 'danger',
                                            default => 'secondary'
                                        };
                                        ?>
                                        <span class="badge bg-<?= $statusClass ?>"><?= ucfirst($status) ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-info"><?= $ad['priority'] ?? 1 ?></span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="edit_ad.php?id=<?= $ad['id'] ?>" class="btn btn-outline-primary" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="?toggle_status=1&id=<?= $ad['id'] ?>" 
                                               class="btn btn-outline-<?= ($ad['status'] ?? 'active') === 'active' ? 'warning' : 'success' ?>" 
                                               title="<?= ($ad['status'] ?? 'active') === 'active' ? 'Pause' : 'Activate' ?>"
                                               onclick="return confirm('Are you sure you want to <?= ($ad['status'] ?? 'active') === 'active' ? 'pause' : 'activate' ?> this ad?')">
                                                <i class="fas fa-<?= ($ad['status'] ?? 'active') === 'active' ? 'pause' : 'play' ?>"></i>
                                            </a>
                                            <a href="?delete=1&id=<?= $ad['id'] ?>" 
                                               class="btn btn-outline-danger" 
                                               title="Delete"
                                               onclick="return confirm('Are you sure you want to delete this ad? This action cannot be undone.')">
                                                <i class="fas fa-trash"></i>
                                            </a>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
