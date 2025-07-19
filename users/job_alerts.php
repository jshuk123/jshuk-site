<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/config.php';

// Authentication check
if (!isset($_SESSION['user_id'])) {
    header('Location: /auth/login.php?redirect=/users/job_alerts.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'User';

// --- Data Fetching ---
$job_alerts = [];
$sectors = [];
$error_message = '';
$success_message = '';

try {
    // Fetch user's job alerts
    $stmt = $pdo->prepare("
        SELECT ja.*, s.name as sector_name
        FROM job_alerts ja
        LEFT JOIN job_sectors s ON ja.sector_id = s.id
        WHERE ja.user_id = ?
        ORDER BY ja.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $job_alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get sectors for the form
    $sectors = $pdo->query("SELECT * FROM job_sectors ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
    
    // Get unique locations for the form
    $locations = $pdo->query("SELECT DISTINCT job_location FROM recruitment WHERE job_location IS NOT NULL AND job_location != '' ORDER BY job_location")->fetchAll(PDO::FETCH_COLUMN);

} catch (PDOException $e) {
    error_log("Job Alerts Error: " . $e->getMessage());
    $error_message = "Unable to load job alerts. Please try again later.";
}

$pageTitle = "Job Alerts";
$page_css = "job_alerts.css";
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
                    <p class="card-text text-muted small">Job Seeker</p>
                </div>
                <div class="list-group list-group-flush">
                    <a href="/users/dashboard.php" class="list-group-item list-group-item-action">
                        <i class="fa-solid fa-tachometer-alt me-2"></i>Dashboard
                    </a>
                    <a href="/users/saved_jobs.php" class="list-group-item list-group-item-action">
                        <i class="fa-solid fa-bookmark me-2"></i>Saved Jobs
                    </a>
                    <a href="/users/job_alerts.php" class="list-group-item list-group-item-action active">
                        <i class="fa-solid fa-bell me-2"></i>Job Alerts
                    </a>
                    <a href="/recruitment.php" class="list-group-item list-group-item-action">
                        <i class="fa-solid fa-search me-2"></i>Browse Jobs
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
                    <h1 class="mb-1">Job Alerts</h1>
                    <p class="text-muted mb-0">
                        Get notified when new jobs match your criteria
                    </p>
                </div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createAlertModal">
                    <i class="fa-solid fa-plus me-2"></i>Create New Alert
                </button>
            </div>

            <?php if ($error_message): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?= htmlspecialchars($error_message) ?>
                </div>
            <?php endif; ?>

            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>
                    <?= htmlspecialchars($success_message) ?>
                </div>
            <?php endif; ?>

            <?php if (empty($job_alerts)): ?>
                <div class="empty-state text-center py-5">
                    <div class="empty-state-icon mb-3">
                        <i class="fas fa-bell fa-3x text-muted"></i>
                    </div>
                    <h3>No Job Alerts Yet</h3>
                    <p class="text-muted mb-4">
                        Create job alerts to get notified when new opportunities match your criteria.
                    </p>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createAlertModal">
                        <i class="fa-solid fa-plus me-2"></i>Create Your First Alert
                    </button>
                </div>
            <?php else: ?>
                <div class="alerts-grid">
                    <?php foreach ($job_alerts as $alert): ?>
                        <div class="alert-card" data-alert-id="<?= $alert['id'] ?>">
                            <div class="alert-header">
                                <div class="alert-info">
                                    <h3 class="alert-name"><?= htmlspecialchars($alert['name']) ?></h3>
                                    <div class="alert-status">
                                        <?php if ($alert['is_active']): ?>
                                            <span class="status-badge status-active">
                                                <i class="fas fa-check-circle"></i> Active
                                            </span>
                                        <?php else: ?>
                                            <span class="status-badge status-inactive">
                                                <i class="fas fa-pause-circle"></i> Paused
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="alert-actions">
                                    <button class="btn-toggle-alert" data-alert-id="<?= $alert['id'] ?>" data-active="<?= $alert['is_active'] ? '1' : '0' ?>">
                                        <i class="fas fa-<?= $alert['is_active'] ? 'pause' : 'play' ?>"></i>
                                    </button>
                                    <button class="btn-delete-alert" data-alert-id="<?= $alert['id'] ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="alert-criteria">
                                <?php
                                $criteria = [];
                                if ($alert['sector_name']) {
                                    $criteria[] = "<strong>Sector:</strong> " . htmlspecialchars($alert['sector_name']);
                                }
                                if ($alert['location']) {
                                    $criteria[] = "<strong>Location:</strong> " . htmlspecialchars($alert['location']);
                                }
                                if ($alert['job_type']) {
                                    $criteria[] = "<strong>Job Type:</strong> " . ucfirst(str_replace('-', ' ', $alert['job_type']));
                                }
                                if ($alert['keywords']) {
                                    $criteria[] = "<strong>Keywords:</strong> " . htmlspecialchars($alert['keywords']);
                                }
                                ?>
                                <?php if (!empty($criteria)): ?>
                                    <div class="criteria-list">
                                        <?= implode('<br>', $criteria) ?>
                                    </div>
                                <?php else: ?>
                                    <div class="criteria-list text-muted">
                                        <em>No specific criteria set</em>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="alert-footer">
                                <div class="alert-meta">
                                    <span class="frequency">
                                        <i class="fas fa-clock"></i>
                                        <?= ucfirst($alert['email_frequency']) ?> updates
                                    </span>
                                    <?php if ($alert['last_sent_at']): ?>
                                        <span class="last-sent">
                                            <i class="fas fa-calendar"></i>
                                            Last sent: <?= date('M j, Y', strtotime($alert['last_sent_at'])) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="alert-created">
                                    <i class="fas fa-calendar-plus"></i>
                                    Created <?= date('M j, Y', strtotime($alert['created_at'])) ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Create Alert Modal -->
<div class="modal fade" id="createAlertModal" tabindex="-1" aria-labelledby="createAlertModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createAlertModalLabel">Create Job Alert</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="createAlertForm">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="alertName" class="form-label">Alert Name</label>
                            <input type="text" class="form-control" id="alertName" name="name" placeholder="e.g., Marketing Jobs in London" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="emailFrequency" class="form-label">Email Frequency</label>
                            <select class="form-select" id="emailFrequency" name="email_frequency">
                                <option value="daily">Daily</option>
                                <option value="weekly">Weekly</option>
                                <option value="monthly">Monthly</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="sectorId" class="form-label">Sector (Optional)</label>
                            <select class="form-select" id="sectorId" name="sector_id">
                                <option value="">Any Sector</option>
                                <?php foreach ($sectors as $sector): ?>
                                    <option value="<?= $sector['id'] ?>"><?= htmlspecialchars($sector['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="location" class="form-label">Location (Optional)</label>
                            <select class="form-select" id="location" name="location">
                                <option value="">Any Location</option>
                                <?php foreach ($locations as $location): ?>
                                    <option value="<?= $location ?>"><?= htmlspecialchars($location) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="jobType" class="form-label">Job Type (Optional)</label>
                            <select class="form-select" id="jobType" name="job_type">
                                <option value="">Any Job Type</option>
                                <option value="full-time">Full Time</option>
                                <option value="part-time">Part Time</option>
                                <option value="contract">Contract</option>
                                <option value="temporary">Temporary</option>
                                <option value="internship">Internship</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="keywords" class="form-label">Keywords (Optional)</label>
                            <input type="text" class="form-control" id="keywords" name="keywords" placeholder="e.g., manager, remote, senior">
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Tip:</strong> You can leave fields empty to get broader results. The more specific your criteria, the more targeted your alerts will be.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-bell me-2"></i>Create Alert
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle create alert form submission
    document.getElementById('createAlertForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        
        // Show loading state
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Creating...';
        submitBtn.disabled = true;
        
        // Send AJAX request
        fetch('/api/create_job_alert.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message and reload page
                showNotification(data.message, 'success');
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                showNotification(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('An error occurred while creating the alert', 'error');
        })
        .finally(() => {
            // Reset button
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        });
    });
    
    // Handle toggle alert buttons
    document.querySelectorAll('.btn-toggle-alert').forEach(button => {
        button.addEventListener('click', function() {
            const alertId = this.dataset.alertId;
            const isActive = this.dataset.active === '1';
            const newStatus = !isActive;
            
            // Show loading state
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            this.disabled = true;
            
            // Send AJAX request to toggle status
            fetch('/api/toggle_job_alert.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `alert_id=${alertId}&is_active=${newStatus ? '1' : '0'}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update button state
                    this.dataset.active = newStatus ? '1' : '0';
                    this.innerHTML = `<i class="fas fa-${newStatus ? 'pause' : 'play'}"></i>`;
                    
                    // Update status badge
                    const statusBadge = this.closest('.alert-card').querySelector('.status-badge');
                    if (newStatus) {
                        statusBadge.className = 'status-badge status-active';
                        statusBadge.innerHTML = '<i class="fas fa-check-circle"></i> Active';
                    } else {
                        statusBadge.className = 'status-badge status-inactive';
                        statusBadge.innerHTML = '<i class="fas fa-pause-circle"></i> Paused';
                    }
                    
                    showNotification(data.message, 'success');
                } else {
                    showNotification(data.message, 'error');
                    // Reset button
                    this.innerHTML = `<i class="fas fa-${isActive ? 'pause' : 'play'}"></i>`;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('An error occurred while updating the alert', 'error');
                // Reset button
                this.innerHTML = `<i class="fas fa-${isActive ? 'pause' : 'play'}"></i>`;
            })
            .finally(() => {
                this.disabled = false;
            });
        });
    });
    
    // Handle delete alert buttons
    document.querySelectorAll('.btn-delete-alert').forEach(button => {
        button.addEventListener('click', function() {
            if (!confirm('Are you sure you want to delete this job alert? This action cannot be undone.')) {
                return;
            }
            
            const alertId = this.dataset.alertId;
            const alertCard = this.closest('.alert-card');
            
            // Show loading state
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            this.disabled = true;
            
            // Send AJAX request to delete alert
            fetch('/api/delete_job_alert.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `alert_id=${alertId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Remove the alert card with animation
                    alertCard.style.opacity = '0';
                    alertCard.style.transform = 'scale(0.95)';
                    setTimeout(() => {
                        alertCard.remove();
                        
                        // Show empty state if no more alerts
                        if (document.querySelectorAll('.alert-card').length === 0) {
                            location.reload();
                        }
                    }, 300);
                    
                    showNotification(data.message, 'success');
                } else {
                    showNotification(data.message, 'error');
                    // Reset button
                    this.innerHTML = '<i class="fas fa-trash"></i>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('An error occurred while deleting the alert', 'error');
                // Reset button
                this.innerHTML = '<i class="fas fa-trash"></i>';
            })
            .finally(() => {
                this.disabled = false;
            });
        });
    });
    
    // Function to show notifications
    function showNotification(message, type) {
        const notification = document.createElement('div');
        notification.className = `alert alert-${type === 'success' ? 'success' : 'danger'} notification`;
        notification.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
            ${message}
        `;
        
        // Add styles
        notification.style.position = 'fixed';
        notification.style.top = '20px';
        notification.style.right = '20px';
        notification.style.zIndex = '9999';
        notification.style.minWidth = '300px';
        notification.style.animation = 'slideInRight 0.3s ease-out';
        
        document.body.appendChild(notification);
        
        // Remove after 3 seconds
        setTimeout(() => {
            notification.style.animation = 'slideOutRight 0.3s ease-out';
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, 3000);
    }
});
</script>



<?php include '../includes/footer_main.php'; ?> 