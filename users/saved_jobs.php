<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/config.php';

// Authentication check
if (!isset($_SESSION['user_id'])) {
    header('Location: /auth/login.php?redirect=/users/saved_jobs.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'User';

// --- Data Fetching ---
$saved_jobs = [];
$error_message = '';

try {
    // Fetch user's saved jobs with full job details
    $stmt = $pdo->prepare("
        SELECT r.*, s.name as sector_name, b.business_name,
               bi.file_path as business_logo, u.profile_image, u.first_name, u.last_name,
               sj.saved_at
        FROM saved_jobs sj
        JOIN recruitment r ON sj.job_id = r.id
        LEFT JOIN job_sectors s ON r.sector_id = s.id
        LEFT JOIN businesses b ON r.business_id = b.id
        LEFT JOIN business_images bi ON b.id = bi.business_id AND bi.sort_order = 0
        LEFT JOIN users u ON r.user_id = u.id
        WHERE sj.user_id = ? AND r.is_active = 1
        ORDER BY sj.saved_at DESC
    ");
    $stmt->execute([$user_id]);
    $saved_jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Saved Jobs Error: " . $e->getMessage());
    $error_message = "Unable to load saved jobs. Please try again later.";
}

$pageTitle = "My Saved Jobs";
$page_css = "saved_jobs.css";
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
                    <a href="/users/saved_jobs.php" class="list-group-item list-group-item-action active">
                        <i class="fa-solid fa-bookmark me-2"></i>Saved Jobs
                    </a>
                    <a href="/users/job_alerts.php" class="list-group-item list-group-item-action">
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
                    <h1 class="mb-1">My Saved Jobs</h1>
                    <p class="text-muted mb-0">
                        <?= count($saved_jobs) ?> saved job<?= count($saved_jobs) != 1 ? 's' : '' ?>
                    </p>
                </div>
                <a href="/recruitment.php" class="btn btn-primary">
                    <i class="fa-solid fa-search me-2"></i>Browse More Jobs
                </a>
            </div>

            <?php if ($error_message): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?= htmlspecialchars($error_message) ?>
                </div>
            <?php endif; ?>

            <?php if (empty($saved_jobs)): ?>
                <div class="empty-state text-center py-5">
                    <div class="empty-state-icon mb-3">
                        <i class="fas fa-bookmark fa-3x text-muted"></i>
                    </div>
                    <h3>No Saved Jobs Yet</h3>
                    <p class="text-muted mb-4">
                        Start saving interesting job opportunities to keep track of them here.
                    </p>
                    <a href="/recruitment.php" class="btn btn-primary">
                        <i class="fa-solid fa-search me-2"></i>Browse Jobs
                    </a>
                </div>
            <?php else: ?>
                <div class="saved-jobs-grid">
                    <?php foreach ($saved_jobs as $job): ?>
                        <div class="saved-job-card" data-job-id="<?= $job['id'] ?>">
                            <div class="job-header">
                                <div class="job-logo">
                                    <?php if ($job['business_logo']): ?>
                                        <img src="<?= htmlspecialchars($job['business_logo']) ?>" 
                                             alt="<?= htmlspecialchars($job['business_name']) ?> Logo"
                                             onerror="this.src='/images/jshuk-logo.png';">
                                    <?php else: ?>
                                        <img src="/images/jshuk-logo.png" alt="Default Logo">
                                    <?php endif; ?>
                                </div>
                                <div class="job-info">
                                    <h3 class="job-title">
                                        <a href="/job_view.php?id=<?= $job['id'] ?>">
                                            <?= htmlspecialchars($job['job_title']) ?>
                                        </a>
                                    </h3>
                                    <p class="job-company"><?= htmlspecialchars($job['business_name'] ?? 'Company') ?></p>
                                </div>
                                <div class="job-actions">
                                    <button class="btn-unsave" data-job-id="<?= $job['id'] ?>" title="Remove from saved jobs">
                                        <i class="fas fa-bookmark"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="job-meta">
                                <span class="job-location">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <?= htmlspecialchars($job['job_location'] ?? 'Location TBD') ?>
                                </span>
                                <span class="job-type">
                                    <i class="fas fa-clock"></i>
                                    <?= ucfirst(str_replace('-', ' ', $job['job_type'] ?? 'Full Time')) ?>
                                </span>
                                <span class="job-sector">
                                    <i class="fas fa-briefcase"></i>
                                    <?= htmlspecialchars($job['sector_name'] ?? 'General') ?>
                                </span>
                            </div>
                            
                            <div class="job-description">
                                <?= htmlspecialchars(mb_strimwidth($job['job_description'], 0, 150, '...')) ?>
                            </div>
                            
                            <div class="job-footer">
                                <span class="job-date">
                                    <i class="fas fa-calendar"></i>
                                    Saved <?= date('M j, Y', strtotime($job['saved_at'])) ?>
                                </span>
                                <div class="job-buttons">
                                    <a href="/job_view.php?id=<?= $job['id'] ?>" class="btn-view">
                                        <span>View Job</span>
                                        <i class="fas fa-arrow-right"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle unsave job buttons
    document.querySelectorAll('.btn-unsave').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const jobId = this.dataset.jobId;
            const jobCard = this.closest('.saved-job-card');
            
            // Show loading state
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            this.disabled = true;
            
            // Send AJAX request to unsave job
            fetch('/api/save_job.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `job_id=${jobId}&action=unsave`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Remove the job card with animation
                    jobCard.style.opacity = '0';
                    jobCard.style.transform = 'scale(0.95)';
                    setTimeout(() => {
                        jobCard.remove();
                        
                        // Update the count
                        const countElement = document.querySelector('.text-muted');
                        const currentCount = parseInt(countElement.textContent.match(/\d+/)[0]);
                        const newCount = currentCount - 1;
                        countElement.textContent = `${newCount} saved job${newCount != 1 ? 's' : ''}`;
                        
                        // Show empty state if no more jobs
                        if (newCount === 0) {
                            location.reload();
                        }
                    }, 300);
                    
                    // Show success message
                    showNotification(data.message, 'success');
                } else {
                    // Show error message
                    showNotification(data.message, 'error');
                    
                    // Reset button
                    this.innerHTML = '<i class="fas fa-bookmark"></i>';
                    this.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('An error occurred while removing the job', 'error');
                
                // Reset button
                this.innerHTML = '<i class="fas fa-bookmark"></i>';
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

<style>
/* Saved Jobs Styles */
.saved-jobs-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 1.5rem;
}

.saved-job-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.08);
    padding: 1.5rem;
    transition: all 0.3s ease;
    border: 1px solid #f0f0f0;
    position: relative;
}

.saved-job-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 32px rgba(0,0,0,0.12);
    border-color: #ffd700;
}

.job-header {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    margin-bottom: 1rem;
}

.job-logo {
    width: 60px;
    height: 60px;
    border-radius: 8px;
    overflow: hidden;
    background: #f8f9fa;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.job-logo img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.job-info {
    flex: 1;
}

.job-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: #1a3353;
    margin-bottom: 0.25rem;
    line-height: 1.3;
}

.job-title a {
    color: inherit;
    text-decoration: none;
}

.job-title a:hover {
    color: #ffd700;
}

.job-company {
    font-size: 0.9rem;
    color: #6c757d;
    margin: 0;
}

.job-actions {
    display: flex;
    gap: 0.5rem;
}

.btn-unsave {
    background: none;
    border: none;
    color: #ffd700;
    font-size: 1.2rem;
    cursor: pointer;
    padding: 0.25rem;
    border-radius: 4px;
    transition: all 0.2s ease;
}

.btn-unsave:hover {
    background: rgba(255, 215, 0, 0.1);
    transform: scale(1.1);
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

.job-description {
    font-size: 0.875rem;
    color: #495057;
    line-height: 1.5;
    margin-bottom: 1rem;
}

.job-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.job-date {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.75rem;
    color: #6c757d;
}

.job-date i {
    color: #ffd700;
}

.btn-view {
    background: linear-gradient(90deg, #ffd700 0%, #ffd700 100%);
    color: #1a3353;
    border: none;
    border-radius: 8px;
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    font-weight: 600;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.2s ease;
}

.btn-view:hover {
    background: linear-gradient(90deg, #ffd700 0%, #ffcc00 100%);
    color: #1a3353;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(255, 215, 0, 0.3);
}

.empty-state {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.08);
}

.empty-state-icon {
    color: #dee2e6;
}

/* Notification animations */
@keyframes slideInRight {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

@keyframes slideOutRight {
    from {
        transform: translateX(0);
        opacity: 1;
    }
    to {
        transform: translateX(100%);
        opacity: 0;
    }
}

/* Mobile optimizations */
@media (max-width: 768px) {
    .saved-jobs-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .job-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.75rem;
    }
    
    .job-meta {
        gap: 0.75rem;
    }
    
    .job-footer {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.75rem;
    }
    
    .btn-view {
        width: 100%;
        justify-content: center;
    }
}
</style>

<?php include '../includes/footer_main.php'; ?> 