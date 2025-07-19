<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/config.php';

// Authentication check
if (!isset($_SESSION['user_id'])) {
    header('Location: /auth/login.php?redirect=/users/applications.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'User';

// Handle application status updates
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        $application_id = $_POST['application_id'] ?? null;
        $new_status = $_POST['new_status'] ?? '';
        $notes = $_POST['notes'] ?? '';

        if (!$application_id || !is_numeric($application_id)) {
            throw new Exception('Invalid application ID');
        }

        // Verify the application belongs to a job posted by the user
        $stmt = $pdo->prepare("
            SELECT ja.id, ja.status, ja.applicant_id, r.job_title, u.first_name, u.last_name
            FROM job_applications ja
            JOIN recruitment r ON ja.job_id = r.id
            JOIN users u ON ja.applicant_id = u.id
            WHERE ja.id = ? AND r.user_id = ?
        ");
        $stmt->execute([$application_id, $user_id]);
        $application = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$application) {
            throw new Exception('Application not found or access denied');
        }

        // Update application status
        $stmt = $pdo->prepare("
            UPDATE job_applications 
            SET status = ?, notes = ?, updated_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$new_status, $notes, $application_id]);

        // Log status change
        $stmt = $pdo->prepare("
            INSERT INTO application_status_history (application_id, status, notes, changed_by)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$application_id, $new_status, $notes, $user_id]);

        $applicant_name = $application['first_name'] . ' ' . $application['last_name'];
        $success_message = "Application from $applicant_name for '{$application['job_title']}' has been updated to " . ucfirst($new_status);

    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$job_filter = $_GET['job_id'] ?? '';
$date_filter = $_GET['date'] ?? '';

// Fetch user's job postings for filter
$user_jobs = [];
try {
    $stmt = $pdo->prepare("SELECT id, job_title FROM recruitment WHERE user_id = ? ORDER BY job_title");
    $stmt->execute([$user_id]);
    $user_jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching user jobs: " . $e->getMessage());
}

// Fetch applications with filters
$applications = [];
$total_applications = 0;
$pending_count = 0;
$reviewed_count = 0;
$shortlisted_count = 0;

try {
    // Build query with filters
    $where_conditions = ["r.user_id = ?"];
    $params = [$user_id];

    if (!empty($status_filter)) {
        $where_conditions[] = "ja.status = ?";
        $params[] = $status_filter;
    }

    if (!empty($job_filter)) {
        $where_conditions[] = "ja.job_id = ?";
        $params[] = $job_filter;
    }

    if (!empty($date_filter)) {
        $where_conditions[] = "DATE(ja.applied_at) = ?";
        $params[] = $date_filter;
    }

    $where_clause = implode(" AND ", $where_conditions);

    // Get applications
    $stmt = $pdo->prepare("
        SELECT ja.*, r.job_title, r.job_location, r.job_type,
               u.first_name, u.last_name, u.email as applicant_email,
               s.name as sector_name
        FROM job_applications ja
        JOIN recruitment r ON ja.job_id = r.id
        JOIN users u ON ja.applicant_id = u.id
        LEFT JOIN job_sectors s ON r.sector_id = s.id
        WHERE $where_clause
        ORDER BY ja.applied_at DESC
    ");
    $stmt->execute($params);
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get counts for different statuses
    $stmt = $pdo->prepare("
        SELECT ja.status, COUNT(*) as count
        FROM job_applications ja
        JOIN recruitment r ON ja.job_id = r.id
        WHERE r.user_id = ?
        GROUP BY ja.status
    ");
    $stmt->execute([$user_id]);
    $status_counts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($status_counts as $count) {
        switch ($count['status']) {
            case 'pending':
                $pending_count = $count['count'];
                break;
            case 'reviewed':
                $reviewed_count = $count['count'];
                break;
            case 'shortlisted':
                $shortlisted_count = $count['count'];
                break;
        }
        $total_applications += $count['count'];
    }

} catch (PDOException $e) {
    error_log("Error fetching applications: " . $e->getMessage());
    $error_message = "Unable to load applications";
}

$pageTitle = "Manage Applications";
$page_css = "applications.css";
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
                    <a href="/users/manage_jobs.php" class="list-group-item list-group-item-action">
                        <i class="fa-solid fa-briefcase me-2"></i>Manage Jobs
                    </a>
                    <a href="/users/applications.php" class="list-group-item list-group-item-action active">
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
                    <h1 class="mb-1">Manage Applications</h1>
                    <p class="text-muted mb-0">
                        Review and manage job applications from candidates
                    </p>
                </div>
                <a href="/users/manage_jobs.php" class="btn btn-outline-primary">
                    <i class="fa-solid fa-briefcase me-2"></i>Back to Jobs
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

            <!-- Application Stats -->
            <div class="stats-grid mb-4">
                <div class="stat-card">
                    <div class="stat-icon total">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?= $total_applications ?></h3>
                        <p>Total Applications</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon pending">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?= $pending_count ?></h3>
                        <p>Pending Review</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon reviewed">
                        <i class="fas fa-eye"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?= $reviewed_count ?></h3>
                        <p>Reviewed</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon shortlisted">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?= $shortlisted_count ?></h3>
                        <p>Shortlisted</p>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="filters-card mb-4">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label for="status_filter" class="form-label">Status</label>
                        <select class="form-select" id="status_filter" name="status">
                            <option value="">All Statuses</option>
                            <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="reviewed" <?= $status_filter === 'reviewed' ? 'selected' : '' ?>>Reviewed</option>
                            <option value="shortlisted" <?= $status_filter === 'shortlisted' ? 'selected' : '' ?>>Shortlisted</option>
                            <option value="interviewed" <?= $status_filter === 'interviewed' ? 'selected' : '' ?>>Interviewed</option>
                            <option value="hired" <?= $status_filter === 'hired' ? 'selected' : '' ?>>Hired</option>
                            <option value="rejected" <?= $status_filter === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                        </select>
                    </div>
                    
                    <div class="col-md-4">
                        <label for="job_filter" class="form-label">Job Position</label>
                        <select class="form-select" id="job_filter" name="job_id">
                            <option value="">All Jobs</option>
                            <?php foreach ($user_jobs as $job): ?>
                                <option value="<?= $job['id'] ?>" <?= $job_filter == $job['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($job['job_title']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label for="date_filter" class="form-label">Application Date</label>
                        <input type="date" class="form-control" id="date_filter" name="date" 
                               value="<?= htmlspecialchars($date_filter) ?>">
                    </div>
                    
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter"></i>
                            </button>
                            <a href="/users/applications.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i>
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <?php if (empty($applications)): ?>
                <div class="empty-state text-center py-5">
                    <div class="empty-state-icon mb-3">
                        <i class="fas fa-users fa-3x text-muted"></i>
                    </div>
                    <h3>No Applications Found</h3>
                    <p class="text-muted mb-4">
                        <?php if (!empty($status_filter) || !empty($job_filter) || !empty($date_filter)): ?>
                            No applications match your current filters. Try adjusting your search criteria.
                        <?php else: ?>
                            You haven't received any applications yet. Make sure your job postings are active and visible.
                        <?php endif; ?>
                    </p>
                    <a href="/users/manage_jobs.php" class="btn btn-primary">
                        <i class="fa-solid fa-briefcase me-2"></i>Manage Job Postings
                    </a>
                </div>
            <?php else: ?>
                <div class="applications-table-container">
                    <div class="table-responsive">
                        <table class="table table-hover applications-table">
                            <thead>
                                <tr>
                                    <th>Candidate</th>
                                    <th>Job Position</th>
                                    <th>Applied</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($applications as $app): ?>
                                    <tr>
                                        <td>
                                            <div class="candidate-info">
                                                <div class="candidate-avatar">
                                                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($app['first_name'] . ' ' . $app['last_name']) ?>&background=0d6efd&color=fff&size=40&rounded=true" 
                                                         alt="Candidate Avatar">
                                                </div>
                                                <div class="candidate-details">
                                                    <h6 class="candidate-name">
                                                        <?= htmlspecialchars($app['first_name'] . ' ' . $app['last_name']) ?>
                                                    </h6>
                                                    <p class="candidate-email">
                                                        <?= htmlspecialchars($app['applicant_email']) ?>
                                                    </p>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="job-info">
                                                <h6 class="job-title">
                                                    <a href="/job_view.php?id=<?= $app['job_id'] ?>" target="_blank">
                                                        <?= htmlspecialchars($app['job_title']) ?>
                                                    </a>
                                                </h6>
                                                <p class="job-meta">
                                                    <i class="fas fa-map-marker-alt"></i>
                                                    <?= htmlspecialchars($app['job_location'] ?? 'Location TBD') ?>
                                                </p>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="date-info">
                                                <span class="date">
                                                    <?= date('M j, Y', strtotime($app['applied_at'])) ?>
                                                </span>
                                                <span class="time">
                                                    <?= date('g:i A', strtotime($app['applied_at'])) ?>
                                                </span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?= $app['status'] ?>">
                                                <?= ucfirst($app['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn-action btn-view" 
                                                        onclick="viewApplication(<?= $app['id'] ?>)"
                                                        title="View Application">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                
                                                <button class="btn-action btn-status" 
                                                        onclick="updateStatus(<?= $app['id'] ?>, '<?= $app['status'] ?>')"
                                                        title="Update Status">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                
                                                <?php if ($app['resume_path']): ?>
                                                    <a href="<?= htmlspecialchars($app['resume_path']) ?>" 
                                                       class="btn-action btn-download" 
                                                       target="_blank"
                                                       title="Download Resume">
                                                        <i class="fas fa-download"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Application View Modal -->
<div class="modal fade" id="applicationModal" tabindex="-1" aria-labelledby="applicationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="applicationModalLabel">Application Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="applicationModalBody">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>
</div>

<!-- Status Update Modal -->
<div class="modal fade" id="statusModal" tabindex="-1" aria-labelledby="statusModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="statusModalLabel">Update Application Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" id="statusForm">
                <div class="modal-body">
                    <input type="hidden" name="application_id" id="statusApplicationId">
                    <input type="hidden" name="action" value="update_status">
                    
                    <div class="mb-3">
                        <label for="new_status" class="form-label">New Status</label>
                        <select class="form-select" id="new_status" name="new_status" required>
                            <option value="pending">Pending</option>
                            <option value="reviewed">Reviewed</option>
                            <option value="shortlisted">Shortlisted</option>
                            <option value="interviewed">Interviewed</option>
                            <option value="hired">Hired</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes (Optional)</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3" 
                                  placeholder="Add any notes about this application..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function viewApplication(applicationId) {
    // Load application details via AJAX
    fetch(`/api/get_application.php?id=${applicationId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('applicationModalBody').innerHTML = data.html;
                new bootstrap.Modal(document.getElementById('applicationModal')).show();
            } else {
                alert('Error loading application details');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading application details');
        });
}

function updateStatus(applicationId, currentStatus) {
    document.getElementById('statusApplicationId').value = applicationId;
    document.getElementById('new_status').value = currentStatus;
    new bootstrap.Modal(document.getElementById('statusModal')).show();
}
</script>

<style>
/* Applications Styles */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.stat-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 4px 16px rgba(0,0,0,0.08);
    display: flex;
    align-items: center;
    gap: 1rem;
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: white;
}

.stat-icon.total {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.stat-icon.pending {
    background: linear-gradient(135deg, #ffd700 0%, #ffcc00 100%);
}

.stat-icon.reviewed {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
}

.stat-icon.shortlisted {
    background: linear-gradient(135deg, #fd7e14 0%, #e83e8c 100%);
}

.stat-content h3 {
    margin: 0;
    font-size: 2rem;
    font-weight: 700;
    color: #1a3353;
}

.stat-content p {
    margin: 0;
    color: #6c757d;
    font-size: 0.9rem;
}

.filters-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 4px 16px rgba(0,0,0,0.08);
}

.applications-table-container {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.08);
    overflow: hidden;
}

.applications-table {
    margin: 0;
}

.applications-table th {
    background: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
    color: #1a3353;
    font-weight: 600;
    padding: 1rem;
}

.applications-table td {
    padding: 1rem;
    vertical-align: middle;
    border-bottom: 1px solid #f0f0f0;
}

.candidate-info {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.candidate-avatar img {
    width: 40px;
    height: 40px;
    border-radius: 50%;
}

.candidate-name {
    margin: 0;
    color: #1a3353;
    font-weight: 600;
}

.candidate-email {
    margin: 0;
    color: #6c757d;
    font-size: 0.8rem;
}

.job-info h6 {
    margin: 0;
    color: #1a3353;
}

.job-info h6 a {
    color: inherit;
    text-decoration: none;
}

.job-info h6 a:hover {
    color: #ffd700;
}

.job-meta {
    margin: 0;
    color: #6c757d;
    font-size: 0.8rem;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.date-info {
    display: flex;
    flex-direction: column;
}

.date {
    font-weight: 600;
    color: #1a3353;
}

.time {
    font-size: 0.8rem;
    color: #6c757d;
}

.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

.status-pending {
    background: #fff3cd;
    color: #856404;
}

.status-reviewed {
    background: #d1ecf1;
    color: #0c5460;
}

.status-shortlisted {
    background: #d4edda;
    color: #155724;
}

.status-interviewed {
    background: #e2e3e5;
    color: #383d41;
}

.status-hired {
    background: #d1ecf1;
    color: #0c5460;
}

.status-rejected {
    background: #f8d7da;
    color: #721c24;
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

.btn-view {
    color: #007bff;
}

.btn-view:hover {
    background: rgba(0, 123, 255, 0.1);
}

.btn-status {
    color: #ffd700;
}

.btn-status:hover {
    background: rgba(255, 215, 0, 0.1);
}

.btn-download {
    color: #28a745;
    text-decoration: none;
}

.btn-download:hover {
    background: rgba(40, 167, 69, 0.1);
}

.empty-state {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.08);
}

.empty-state-icon {
    color: #dee2e6;
}

/* Mobile responsiveness */
@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .applications-table {
        font-size: 0.9rem;
    }
    
    .candidate-info {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
    
    .action-buttons {
        flex-direction: column;
        gap: 0.25rem;
    }
}
</style>

<?php include '../includes/footer_main.php'; ?> 