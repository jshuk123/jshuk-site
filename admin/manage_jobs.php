<?php
session_start();
require_once '../config/config.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: /auth/login.php');
    exit;
}

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $job_id = (int)$_POST['job_id'];
    $action = $_POST['action'];
    
    try {
        switch ($action) {
            case 'approve':
                $stmt = $pdo->prepare("UPDATE recruitment SET is_active = 1 WHERE id = ?");
                $stmt->execute([$job_id]);
                $_SESSION['admin_message'] = 'Job approved successfully.';
                break;
                
            case 'reject':
                $stmt = $pdo->prepare("UPDATE recruitment SET is_active = 0 WHERE id = ?");
                $stmt->execute([$job_id]);
                $_SESSION['admin_message'] = 'Job rejected successfully.';
                break;
                
            case 'feature':
                $stmt = $pdo->prepare("UPDATE recruitment SET is_featured = 1 WHERE id = ?");
                $stmt->execute([$job_id]);
                $_SESSION['admin_message'] = 'Job featured successfully.';
                break;
                
            case 'unfeature':
                $stmt = $pdo->prepare("UPDATE recruitment SET is_featured = 0 WHERE id = ?");
                $stmt->execute([$job_id]);
                $_SESSION['admin_message'] = 'Job unfeatured successfully.';
                break;
                
            case 'delete':
                $stmt = $pdo->prepare("DELETE FROM recruitment WHERE id = ?");
                $stmt->execute([$job_id]);
                $_SESSION['admin_message'] = 'Job deleted successfully.';
                break;
        }
    } catch (PDOException $e) {
        error_log("Admin Job Management Error: " . $e->getMessage());
        $_SESSION['admin_error'] = 'An error occurred while processing the request.';
    }
    
    header('Location: /admin/manage_jobs.php');
    exit;
}

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';
$sector_filter = $_GET['sector'] ?? '';

// Build query
$where_conditions = ["1=1"];
$params = [];

if ($status_filter === 'pending') {
    $where_conditions[] = "r.is_active = 0";
} elseif ($status_filter === 'active') {
    $where_conditions[] = "r.is_active = 1";
} elseif ($status_filter === 'featured') {
    $where_conditions[] = "r.is_featured = 1";
}

if (!empty($sector_filter)) {
    $where_conditions[] = "r.sector_id = ?";
    $params[] = $sector_filter;
}

$where_clause = implode(" AND ", $where_conditions);

$sql = "
    SELECT r.*, s.name as sector_name, u.first_name, u.last_name, u.email as user_email
    FROM recruitment r
    LEFT JOIN job_sectors s ON r.sector_id = s.id
    LEFT JOIN users u ON r.user_id = u.id
    WHERE $where_clause
    ORDER BY r.created_at DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get sectors for filter
$sectors = $pdo->query("SELECT * FROM job_sectors ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = "Manage Job Postings";
include '../includes/header_main.php';
?>

<div class="container main-content">
    <div class="admin-header">
        <h1>Manage Job Postings</h1>
        <a href="/recruitment.php" class="btn btn-outline-primary">
            <i class="fa fa-eye"></i> View Job Board
        </a>
    </div>

    <?php if (isset($_SESSION['admin_message'])): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($_SESSION['admin_message']) ?>
        </div>
        <?php unset($_SESSION['admin_message']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['admin_error'])): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($_SESSION['admin_error']) ?>
        </div>
        <?php unset($_SESSION['admin_error']); ?>
    <?php endif; ?>

    <!-- Filters -->
    <div class="filters-section">
        <form method="get" class="filters-form">
            <div class="filter-row">
                <select name="status" class="filter-select">
                    <option value="all" <?= ($status_filter === 'all') ? 'selected' : '' ?>>All Jobs</option>
                    <option value="pending" <?= ($status_filter === 'pending') ? 'selected' : '' ?>>Pending Approval</option>
                    <option value="active" <?= ($status_filter === 'active') ? 'selected' : '' ?>>Active Jobs</option>
                    <option value="featured" <?= ($status_filter === 'featured') ? 'selected' : '' ?>>Featured Jobs</option>
                </select>
                
                <select name="sector" class="filter-select">
                    <option value="">All Sectors</option>
                    <?php foreach ($sectors as $sector): ?>
                        <option value="<?= $sector['id'] ?>" <?= ($sector_filter == $sector['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($sector['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="/admin/manage_jobs.php" class="btn btn-outline-secondary">Clear</a>
            </div>
        </form>
    </div>

    <!-- Job Listings -->
    <div class="jobs-table-container">
        <?php if (empty($jobs)): ?>
            <div class="empty-state">
                <i class="fa fa-briefcase"></i>
                <h3>No jobs found</h3>
                <p>No job postings match your current filters.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="jobs-table">
                    <thead>
                        <tr>
                            <th>Job Title</th>
                            <th>Company</th>
                            <th>Location</th>
                            <th>Sector</th>
                            <th>Posted By</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($jobs as $job): ?>
                            <tr class="job-row <?= $job['is_active'] ? 'active' : 'pending' ?>">
                                <td class="job-title-cell">
                                    <div class="job-title"><?= htmlspecialchars($job['job_title']) ?></div>
                                    <?php if ($job['is_featured']): ?>
                                        <span class="badge badge-featured">🏅 Featured</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($job['company'] ?: 'Individual') ?></td>
                                <td><?= htmlspecialchars($job['job_location']) ?></td>
                                <td><?= htmlspecialchars($job['sector_name'] ?? 'N/A') ?></td>
                                <td>
                                    <div class="user-info">
                                        <div><?= htmlspecialchars($job['first_name'] . ' ' . $job['last_name']) ?></div>
                                        <small><?= htmlspecialchars($job['user_email']) ?></small>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($job['is_active']): ?>
                                        <span class="status-badge status-active">Active</span>
                                    <?php else: ?>
                                        <span class="status-badge status-pending">Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= date('M d, Y', strtotime($job['created_at'])) ?></td>
                                <td class="actions-cell">
                                    <div class="action-buttons">
                                        <a href="/job_view.php?id=<?= $job['id'] ?>" class="btn btn-sm btn-outline-primary" target="_blank">
                                            <i class="fa fa-eye"></i> View
                                        </a>
                                        
                                        <?php if (!$job['is_active']): ?>
                                            <form method="post" style="display: inline;">
                                                <input type="hidden" name="job_id" value="<?= $job['id'] ?>">
                                                <input type="hidden" name="action" value="approve">
                                                <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Approve this job posting?')">
                                                    <i class="fa fa-check"></i> Approve
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <form method="post" style="display: inline;">
                                                <input type="hidden" name="job_id" value="<?= $job['id'] ?>">
                                                <input type="hidden" name="action" value="reject">
                                                <button type="submit" class="btn btn-sm btn-warning" onclick="return confirm('Reject this job posting?')">
                                                    <i class="fa fa-times"></i> Reject
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <?php if (!$job['is_featured']): ?>
                                            <form method="post" style="display: inline;">
                                                <input type="hidden" name="job_id" value="<?= $job['id'] ?>">
                                                <input type="hidden" name="action" value="feature">
                                                <button type="submit" class="btn btn-sm btn-info">
                                                    <i class="fa fa-star"></i> Feature
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <form method="post" style="display: inline;">
                                                <input type="hidden" name="job_id" value="<?= $job['id'] ?>">
                                                <input type="hidden" name="action" value="unfeature">
                                                <button type="submit" class="btn btn-sm btn-secondary">
                                                    <i class="fa fa-star-o"></i> Unfeature
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <form method="post" style="display: inline;">
                                            <input type="hidden" name="job_id" value="<?= $job['id'] ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this job posting? This action cannot be undone.')">
                                                <i class="fa fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* Admin Job Management Styles */
.admin-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    flex-wrap: wrap;
    gap: 1rem;
}

.admin-header h1 {
    color: #1e3a8a;
    font-size: 2rem;
    font-weight: 700;
    margin: 0;
}

.filters-section {
    background: #f8fafc;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    border: 2px solid #e2e8f0;
}

.filter-row {
    display: flex;
    gap: 1rem;
    align-items: center;
    flex-wrap: wrap;
}

.filter-select {
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    padding: 0.6rem 1rem;
    font-size: 0.9rem;
    background: #fff;
    min-width: 150px;
}

.filter-select:focus {
    outline: none;
    border-color: #2563eb;
}

.jobs-table-container {
    background: #fff;
    border-radius: 12px;
    overflow: hidden;
    border: 2px solid #e2e8f0;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.jobs-table {
    width: 100%;
    border-collapse: collapse;
}

.jobs-table th {
    background: #f8fafc;
    color: #374151;
    font-weight: 600;
    padding: 1rem;
    text-align: left;
    border-bottom: 2px solid #e2e8f0;
    font-size: 0.9rem;
}

.jobs-table td {
    padding: 1rem;
    border-bottom: 1px solid #e2e8f0;
    vertical-align: top;
}

.job-row:hover {
    background: #f8fafc;
}

.job-row.pending {
    background: #fef3c7;
}

.job-title-cell {
    max-width: 200px;
}

.job-title {
    font-weight: 600;
    color: #1e3a8a;
    margin-bottom: 0.3rem;
}

.badge-featured {
    background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
    color: #92400e;
    padding: 0.2rem 0.5rem;
    border-radius: 12px;
    font-size: 0.7rem;
    font-weight: 600;
}

.user-info {
    font-size: 0.9rem;
}

.user-info small {
    color: #6b7280;
    display: block;
}

.status-badge {
    padding: 0.3rem 0.8rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
}

.status-active {
    background: #d1fae5;
    color: #065f46;
}

.status-pending {
    background: #fef3c7;
    color: #92400e;
}

.actions-cell {
    min-width: 300px;
}

.action-buttons {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.btn-sm {
    padding: 0.4rem 0.8rem;
    font-size: 0.8rem;
    border-radius: 6px;
}

.btn-success {
    background: #10b981;
    border-color: #10b981;
    color: #fff;
}

.btn-success:hover {
    background: #059669;
    border-color: #059669;
    color: #fff;
}

.btn-warning {
    background: #f59e0b;
    border-color: #f59e0b;
    color: #fff;
}

.btn-warning:hover {
    background: #d97706;
    border-color: #d97706;
    color: #fff;
}

.btn-info {
    background: #3b82f6;
    border-color: #3b82f6;
    color: #fff;
}

.btn-info:hover {
    background: #2563eb;
    border-color: #2563eb;
    color: #fff;
}

.btn-danger {
    background: #ef4444;
    border-color: #ef4444;
    color: #fff;
}

.btn-danger:hover {
    background: #dc2626;
    border-color: #dc2626;
    color: #fff;
}

.empty-state {
    text-align: center;
    padding: 3rem 2rem;
    color: #6b7280;
}

.empty-state i {
    font-size: 3rem;
    margin-bottom: 1rem;
    color: #9ca3af;
}

.empty-state h3 {
    color: #374151;
    margin-bottom: 0.5rem;
}

/* Responsive Design */
@media (max-width: 1200px) {
    .jobs-table {
        font-size: 0.85rem;
    }
    
    .actions-cell {
        min-width: 250px;
    }
}

@media (max-width: 900px) {
    .admin-header {
        flex-direction: column;
        text-align: center;
    }
    
    .filter-row {
        flex-direction: column;
        align-items: stretch;
    }
    
    .filter-select {
        min-width: auto;
    }
    
    .table-responsive {
        overflow-x: auto;
    }
    
    .jobs-table {
        min-width: 800px;
    }
    
    .action-buttons {
        flex-direction: column;
    }
    
    .btn-sm {
        width: 100%;
        justify-content: center;
    }
}

@media (max-width: 600px) {
    .container, .main-content {
        padding: 1rem;
    }
    
    .admin-header h1 {
        font-size: 1.5rem;
    }
    
    .filters-section {
        padding: 1rem;
    }
    
    .jobs-table th,
    .jobs-table td {
        padding: 0.8rem 0.5rem;
    }
}
</style>

<?php include '../includes/footer_main.php'; ?> 