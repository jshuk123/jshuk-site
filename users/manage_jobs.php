<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/config.php';

// Authentication check
if (!isset($_SESSION['user_id'])) {
    header('Location: /auth/login.php?redirect=/users/manage_jobs.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'User';

// Handle job status updates
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        $job_id = $_POST['job_id'] ?? null;
        $action = $_POST['action'] ?? '';

        if (!$job_id || !is_numeric($job_id)) {
            throw new Exception('Invalid job ID');
        }

        // Verify the job belongs to the user
        $stmt = $pdo->prepare("SELECT id, job_title FROM recruitment WHERE id = ? AND user_id = ?");
        $stmt->execute([$job_id, $user_id]);
        $job = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$job) {
            throw new Exception('Job not found or access denied');
        }

        switch ($action) {
            case 'activate':
                $stmt = $pdo->prepare("UPDATE recruitment SET is_active = 1 WHERE id = ? AND user_id = ?");
                $stmt->execute([$job_id, $user_id]);
                $success_message = "Job '{$job['job_title']}' has been activated";
                break;

            case 'deactivate':
                $stmt = $pdo->prepare("UPDATE recruitment SET is_active = 0 WHERE id = ? AND user_id = ?");
                $stmt->execute([$job_id, $user_id]);
                $success_message = "Job '{$job['job_title']}' has been deactivated";
                break;

            case 'delete':
                $stmt = $pdo->prepare("DELETE FROM recruitment WHERE id = ? AND user_id = ?");
                $stmt->execute([$job_id, $user_id]);
                $success_message = "Job '{$job['job_title']}' has been deleted";
                break;

            case 'feature':
                $stmt = $pdo->prepare("UPDATE recruitment SET is_featured = 1 WHERE id = ? AND user_id = ?");
                $stmt->execute([$job_id, $user_id]);
                $success_message = "Job '{$job['job_title']}' has been featured";
                break;

            case 'unfeature':
                $stmt = $pdo->prepare("UPDATE recruitment SET is_featured = 0 WHERE id = ? AND user_id = ?");
                $stmt->execute([$job_id, $user_id]);
                $success_message = "Job '{$job['job_title']}' has been unfeatured";
                break;

            default:
                throw new Exception('Invalid action');
        }

    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Fetch user's job postings with application counts
$jobs = [];
try {
    $stmt = $pdo->prepare("
        SELECT r.*, s.name as sector_name,
               (SELECT COUNT(*) FROM job_applications ja WHERE ja.job_id = r.id) as application_count,
               (SELECT COUNT(*) FROM job_applications ja WHERE ja.job_id = r.id AND ja.status = 'pending') as pending_applications
        FROM recruitment r
        LEFT JOIN job_sectors s ON r.sector_id = s.id
        WHERE r.user_id = ?
        ORDER BY r.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching jobs: " . $e->getMessage());
    $error_message = "Unable to load job postings";
}

$pageTitle = "Manage Job Postings";
$page_css = "manage_jobs.css";
include '../includes/header_main.php';
?>

<div class="container my-5">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-lg-3">
            <div class="card sticky-top" style="top: 2rem;">
                <div class="card-body text-center">
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($user_name) ?>&background=0d6efd&color=fff&size=100&rounded=true" alt="User Avatar" class="rounded-circle mb-3">
                    <h5 class="card-title mb-0"><?= htmlspecialchars($user_name) ?></h5>
                    <p class="card-text text-muted small">Employer</p>
                </div>
                <div class="list-group list-group-flush">
                    <a href="/users/dashboard.php" class="list-group-item list-group-item-action">
                        <i class="fa-solid fa-tachometer-alt me-2"></i>Dashboard
                    </a>
                    <a href="/users/company_profile.php" class="list-group-item list-group-item-action">
                        <i class="fa-solid fa-building me-2"></i>Company Profile
                    </a>
                    <a href="/users/manage_jobs.php" class="list-group-item list-group-item-action active">
                        <i class="fa-solid fa-briefcase me-2"></i>Manage Jobs
                    </a>
                    <a href="/users/applications.php" class="list-group-item list-group-item-action">
                        <i class="fa-solid fa-users me-2"></i>Applications
                    </a>
                    <hr class="my-1">
                    <a href="/users/edit_profile.php" class="list-group-item list-group-item-action">
                        <i class="fa-solid fa-user-edit me-2"></i>Edit Profile
                    </a>
                    <a href="/auth/logout.php" class="list-group-item list-group-item-action text-danger">
                        <i class="fa-solid fa-sign-out-alt me-2"></i>Logout
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="mb-1">Manage Job Postings</h1>
                    <p class="text-muted mb-0">
                        <?= count($jobs) ?> job posting<?= count($jobs) != 1 ? 's' : '' ?> • 
                        <?= array_sum(array_column($jobs, 'application_count')) ?> total applications
                    </p>
                </div>
                <a href="/submit_job.php" class="btn btn-primary">
                    <i class="fa-solid fa-plus me-2"></i>Post New Job
                </a>
            </div>

            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>
                    <?= htmlspecialchars($success_message) ?>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?= htmlspecialchars($error_message) ?>
                </div>
            <?php endif; ?>

            <?php if (empty($jobs)): ?>
                <div class="empty-state text-center py-5">
                    <div class="empty-state-icon mb-3">
                        <i class="fas fa-briefcase fa-3x text-muted"></i>
                    </div>
                    <h3>No Job Postings Yet</h3>
                    <p class="text-muted mb-4">
                        Start posting jobs to attract talented candidates to your company.
                    </p>
                    <a href="/submit_job.php" class="btn btn-primary">
                        <i class="fa-solid fa-plus me-2"></i>Post Your First Job
                    </a>
                </div>
            <?php else: ?>
                <div class="jobs-management-grid">
                    <?php foreach ($jobs as $job): ?>
                        <div class="job-management-card">
                            <div class="job-header">
                                <div class="job-info">
                                    <h3 class="job-title">
                                        <a href="/job_view.php?id=<?= $job['id'] ?>" target="_blank">
                                            <?= htmlspecialchars($job['job_title']) ?>
                                        </a>
                                    </h3>
                                    <p class="job-location">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <?= htmlspecialchars($job['job_location'] ?? 'Location TBD') ?>
                                    </p>
                                </div>
                                <div class="job-status">
                                    <?php if ($job['is_active']): ?>
                                        <span class="status-badge status-active">
                                            <i class="fas fa-check-circle"></i> Active
                                        </span>
                                    <?php else: ?>
                                        <span class="status-badge status-inactive">
                                            <i class="fas fa-pause-circle"></i> Inactive
                                        </span>
                                    <?php endif; ?>
                                    
                                    <?php if ($job['is_featured']): ?>
                                        <span class="featured-badge">
                                            <i class="fas fa-star"></i> Featured
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="job-meta">
                                <span class="job-type">
                                    <i class="fas fa-clock"></i>
                                    <?= ucfirst(str_replace('-', ' ', $job['job_type'] ?? 'Full Time')) ?>
                                </span>
                                <?php if ($job['sector_name']): ?>
                                <span class="job-sector">
                                    <i class="fas fa-briefcase"></i>
                                    <?= htmlspecialchars($job['sector_name']) ?>
                                </span>
                                <?php endif; ?>
                                <span class="job-date">
                                    <i class="fas fa-calendar"></i>
                                    Posted <?= date('M j, Y', strtotime($job['created_at'])) ?>
                                </span>
                            </div>
                            
                            <div class="applications-info">
                                <div class="applications-count">
                                    <i class="fas fa-users"></i>
                                    <strong><?= $job['application_count'] ?></strong> application<?= $job['application_count'] != 1 ? 's' : '' ?>
                                    <?php if ($job['pending_applications'] > 0): ?>
                                        <span class="pending-badge">
                                            <?= $job['pending_applications'] ?> new
                                        </span>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if ($job['application_count'] > 0): ?>
                                    <a href="/users/view_applications.php?job_id=<?= $job['id'] ?>" class="btn-view-applications">
                                        <i class="fas fa-eye"></i>
                                        View Applications
                                    </a>
                                <?php endif; ?>
                            </div>
                            
                            <div class="job-actions">
                                <div class="action-buttons">
                                    <?php if ($job['is_active']): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="job_id" value="<?= $job['id'] ?>">
                                            <input type="hidden" name="action" value="deactivate">
                                            <button type="submit" class="btn-action btn-pause" title="Pause Job">
                                                <i class="fas fa-pause"></i>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="job_id" value="<?= $job['id'] ?>">
                                            <input type="hidden" name="action" value="activate">
                                            <button type="submit" class="btn-action btn-play" title="Activate Job">
                                                <i class="fas fa-play"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <?php if ($job['is_featured']): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="job_id" value="<?= $job['id'] ?>">
                                            <input type="hidden" name="action" value="unfeature">
                                            <button type="submit" class="btn-action btn-unfeature" title="Remove from Featured">
                                                <i class="fas fa-star"></i>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="job_id" value="<?= $job['id'] ?>">
                                            <input type="hidden" name="action" value="feature">
                                            <button type="submit" class="btn-action btn-feature" title="Feature Job">
                                                <i class="far fa-star"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <a href="/edit_job.php?id=<?= $job['id'] ?>" class="btn-action btn-edit" title="Edit Job">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this job? This action cannot be undone.')">
                                        <input type="hidden" name="job_id" value="<?= $job['id'] ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <button type="submit" class="btn-action btn-delete" title="Delete Job">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
/* Manage Jobs Styles */
.jobs-management-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
    gap: 1.5rem;
}

.job-management-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 4px 16px rgba(0,0,0,0.08);
    border: 1px solid #f0f0f0;
    transition: all 0.3s ease;
}

.job-management-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 32px rgba(0,0,0,0.12);
    border-color: #ffd700;
}

.job-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
}

.job-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: #1a3353;
    margin-bottom: 0.5rem;
}

.job-title a {
    color: inherit;
    text-decoration: none;
}

.job-title a:hover {
    color: #ffd700;
}

.job-location {
    color: #6c757d;
    font-size: 0.9rem;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.job-status {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    align-items: flex-end;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

.status-active {
    background: #d4edda;
    color: #155724;
}

.status-inactive {
    background: #f8d7da;
    color: #721c24;
}

.featured-badge {
    background: #fff3cd;
    color: #856404;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.job-meta {
    display: flex;
    gap: 1rem;
    margin-bottom: 1rem;
    flex-wrap: wrap;
}

.job-meta span {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.8rem;
    color: #6c757d;
}

.job-meta i {
    color: #ffd700;
}

.applications-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 8px;
}

.applications-count {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #1a3353;
    font-size: 0.9rem;
}

.applications-count i {
    color: #ffd700;
}

.pending-badge {
    background: #ffd700;
    color: #1a3353;
    padding: 0.2rem 0.5rem;
    border-radius: 12px;
    font-size: 0.7rem;
    font-weight: 600;
    margin-left: 0.5rem;
}

.btn-view-applications {
    background: linear-gradient(90deg, #ffd700 0%, #ffd700 100%);
    color: #1a3353;
    border: none;
    border-radius: 6px;
    padding: 0.5rem 1rem;
    font-size: 0.8rem;
    font-weight: 600;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 0.25rem;
    transition: all 0.2s ease;
}

.btn-view-applications:hover {
    background: linear-gradient(90deg, #ffd700 0%, #ffcc00 100%);
    color: #1a3353;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(255, 215, 0, 0.3);
}

.job-actions {
    display: flex;
    justify-content: flex-end;
}

.action-buttons {
    display: flex;
    gap: 0.5rem;
}

.btn-action {
    background: none;
    border: none;
    padding: 0.5rem;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 1rem;
}

.btn-pause {
    color: #ffc107;
}

.btn-pause:hover {
    background: rgba(255, 193, 7, 0.1);
}

.btn-play {
    color: #28a745;
}

.btn-play:hover {
    background: rgba(40, 167, 69, 0.1);
}

.btn-feature {
    color: #ffd700;
}

.btn-feature:hover {
    background: rgba(255, 215, 0, 0.1);
}

.btn-unfeature {
    color: #ffd700;
}

.btn-unfeature:hover {
    background: rgba(255, 215, 0, 0.1);
}

.btn-edit {
    color: #007bff;
    text-decoration: none;
}

.btn-edit:hover {
    background: rgba(0, 123, 255, 0.1);
}

.btn-delete {
    color: #dc3545;
}

.btn-delete:hover {
    background: rgba(220, 53, 69, 0.1);
}

.empty-state {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.08);
}

.empty-state-icon {
    color: #dee2e6;
}

/* Mobile optimizations */
@media (max-width: 768px) {
    .jobs-management-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .job-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .job-status {
        align-items: flex-start;
    }
    
    .applications-info {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .action-buttons {
        flex-wrap: wrap;
    }
}
</style>

<?php include '../includes/footer_main.php'; ?> 