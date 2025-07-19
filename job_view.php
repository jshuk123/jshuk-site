<?php
require_once 'config/config.php';
require_once 'includes/helpers.php';

$job_id = $_GET['id'] ?? null;
if (!$job_id) {
    header('Location: /recruitment.php');
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT r.*, s.name as sector_name, b.business_name, b.logo_path, b.description as business_description,
               u.profile_image, u.first_name, u.last_name, u.email as user_email,
               CASE WHEN sj.id IS NOT NULL THEN 1 ELSE 0 END as is_saved
        FROM recruitment r
        LEFT JOIN job_sectors s ON r.sector_id = s.id
        LEFT JOIN businesses b ON r.business_id = b.id
        LEFT JOIN users u ON r.user_id = u.id
        LEFT JOIN saved_jobs sj ON r.id = sj.job_id AND sj.user_id = ?
        WHERE r.id = ? AND r.is_active = 1
    ");
    $user_id = $_SESSION['user_id'] ?? 0;
    $stmt->execute([$user_id, $job_id]);
    $job = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$job) {
        header('Location: /recruitment.php');
        exit;
    }

    // Get contact information
    $contact_email = $job['user_email'] ?? '';
    $contact_phone = ''; // Add phone field to recruitment table if needed
    
    // Calculate days since posted
    $days_since_posted = floor((time() - strtotime($job['created_at'])) / (60 * 60 * 24));
    
    // Check if job is new (posted within 7 days)
    $is_new = $days_since_posted <= 7;
    
    // Check if job is featured
    $is_featured = isset($job['is_featured']) && $job['is_featured'];

} catch (PDOException $e) {
    error_log("Job View Error: " . $e->getMessage());
    header('Location: /recruitment.php');
    exit;
}

$pageTitle = htmlspecialchars($job['job_title']);
$page_css = "recruitment.css";
include 'includes/header_main.php';
?>

<div class="container main-content">
    <!-- Back to Jobs Link -->
    <div class="back-link">
        <a href="/recruitment.php" class="btn-back">
            <i class="fa fa-arrow-left"></i> Back to Job Board
        </a>
    </div>

    <!-- Job Header -->
    <div class="job-header">
        <div class="job-header-content">
            <div class="job-company-info">
                <?php if (!empty($job['logo_path'])): ?>
                    <img src="<?= htmlspecialchars($job['logo_path']) ?>" alt="Company Logo" class="company-logo-large">
                <?php elseif (!empty($job['profile_image'])): ?>
                    <img src="<?= htmlspecialchars($job['profile_image']) ?>" alt="Profile" class="company-logo-large">
                <?php else: ?>
                    <div class="company-logo-placeholder-large">
                        <i class="fa fa-building"></i>
                    </div>
                <?php endif; ?>
                <div class="job-title-section">
                    <h1><?= htmlspecialchars($job['job_title']) ?></h1>
                    <div class="company-name-large">
                        <?= htmlspecialchars($job['business_name'] ?: ($job['first_name'] . ' ' . $job['last_name'])) ?>
                    </div>
                </div>
            </div>
            
            <div class="job-badges-large">
                <?php if ($is_featured): ?>
                    <span class="badge badge-featured">🏅 Featured</span>
                <?php endif; ?>
                <?php if ($is_new): ?>
                    <span class="badge badge-new">🆕 New</span>
                <?php endif; ?>
                <span class="badge badge-type badge-<?= $job['job_type'] ?>">
                    <?= ucfirst(str_replace('-', ' ', $job['job_type'])) ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Job Meta Information -->
    <div class="job-meta-section">
        <div class="meta-grid">
            <div class="meta-item-large">
                <i class="fa fa-map-marker"></i>
                <div>
                    <span class="meta-label">Location</span>
                    <span class="meta-value"><?= htmlspecialchars($job['job_location']) ?></span>
                </div>
            </div>
            
            <?php if (!empty($job['sector_name'])): ?>
            <div class="meta-item-large">
                <i class="fa fa-tag"></i>
                <div>
                    <span class="meta-label">Sector</span>
                    <span class="meta-value"><?= htmlspecialchars($job['sector_name']) ?></span>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="meta-item-large">
                <i class="fa fa-calendar"></i>
                <div>
                    <span class="meta-label">Posted</span>
                    <span class="meta-value">
                        <?= date('M d, Y', strtotime($job['created_at'])) ?>
                        <?php if ($days_since_posted > 0): ?>
                            <span class="days-ago">(<?= $days_since_posted ?> day<?= $days_since_posted != 1 ? 's' : '' ?> ago)</span>
                        <?php endif; ?>
                    </span>
                </div>
            </div>
            
            <?php if (!empty($job['salary'])): ?>
            <div class="meta-item-large">
                <i class="fa fa-money-bill"></i>
                <div>
                    <span class="meta-label">Salary</span>
                    <span class="meta-value"><?= htmlspecialchars($job['salary']) ?></span>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="job-content-grid">
        <div class="job-main-content">
            <!-- Job Description -->
            <div class="content-section">
                <h2>Job Description</h2>
                <div class="job-description">
                    <?= nl2br(htmlspecialchars($job['job_description'])) ?>
                </div>
            </div>

            <?php if (!empty($job['requirements'])): ?>
            <div class="content-section">
                <h2>Requirements</h2>
                <div class="job-requirements">
                    <?= nl2br(htmlspecialchars($job['requirements'])) ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($job['skills'])): ?>
            <div class="content-section">
                <h2>Skills & Qualifications</h2>
                <div class="job-skills">
                    <?= nl2br(htmlspecialchars($job['skills'])) ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Company Information -->
            <?php if (!empty($job['business_description'])): ?>
            <div class="content-section">
                <h2>About the Company</h2>
                <div class="company-description">
                    <?= nl2br(htmlspecialchars($job['business_description'])) ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="job-sidebar">
            <!-- Quick Apply Section -->
            <div class="sidebar-widget apply-section">
                <h3>Apply for this Position</h3>
                
                <?php if (isset($_SESSION['user_id'])): ?>
                <div class="save-job-section mb-3">
                    <button class="btn-save-job-large <?= $job['is_saved'] ? 'saved' : '' ?>" 
                            data-job-id="<?= $job['id'] ?>" 
                            title="<?= $job['is_saved'] ? 'Remove from saved jobs' : 'Save this job' ?>">
                        <i class="fas fa-bookmark"></i>
                        <span><?= $job['is_saved'] ? 'Saved' : 'Save Job' ?></span>
                    </button>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($contact_email)): ?>
                <div class="apply-options">
                    <a href="mailto:<?= htmlspecialchars($contact_email) ?>?subject=Application for <?= urlencode($job['job_title']) ?>" 
                       class="btn-apply btn-email">
                        <i class="fa fa-envelope"></i>
                        Apply via Email
                    </a>
                    
                    <a href="https://wa.me/?text=Hi, I'm interested in the <?= urlencode($job['job_title']) ?> position at <?= urlencode($job['business_name'] ?: ($job['first_name'] . ' ' . $job['last_name'])) ?>. Can you provide more details about the application process?" 
                       class="btn-apply btn-whatsapp" target="_blank">
                        <i class="fa fa-whatsapp"></i>
                        Apply via WhatsApp
                    </a>
                    
                    <?php if (!empty($contact_phone)): ?>
                    <a href="tel:<?= htmlspecialchars($contact_phone) ?>" class="btn-apply btn-phone">
                        <i class="fa fa-phone"></i>
                        Call to Apply
                    </a>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <div class="no-contact-info">
                    <p>Contact information not available. Please check back later or contact the employer directly.</p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Job Summary -->
            <div class="sidebar-widget">
                <h3>Job Summary</h3>
                <div class="job-summary">
                    <div class="summary-item">
                        <span class="summary-label">Job Type:</span>
                        <span class="summary-value"><?= ucfirst(str_replace('-', ' ', $job['job_type'])) ?></span>
                    </div>
                    
                    <div class="summary-item">
                        <span class="summary-label">Location:</span>
                        <span class="summary-value"><?= htmlspecialchars($job['job_location']) ?></span>
                    </div>
                    
                    <?php if (!empty($job['sector_name'])): ?>
                    <div class="summary-item">
                        <span class="summary-label">Sector:</span>
                        <span class="summary-value"><?= htmlspecialchars($job['sector_name']) ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <div class="summary-item">
                        <span class="summary-label">Posted:</span>
                        <span class="summary-value"><?= date('M d, Y', strtotime($job['created_at'])) ?></span>
                    </div>
                </div>
            </div>

            <!-- Similar Jobs -->
            <div class="sidebar-widget">
                <h3>Similar Jobs</h3>
                <div class="similar-jobs">
                    <?php
                    try {
                        $similar_stmt = $pdo->prepare("
                            SELECT r.id, r.job_title, r.job_location, r.job_type, s.name as sector_name
                            FROM recruitment r
                            LEFT JOIN job_sectors s ON r.sector_id = s.id
                            WHERE r.is_active = 1 
                            AND r.id != ? 
                            AND (r.sector_id = ? OR r.job_location LIKE ?)
                            ORDER BY r.created_at DESC
                            LIMIT 3
                        ");
                        $similar_stmt->execute([
                            $job_id, 
                            $job['sector_id'], 
                            '%' . $job['job_location'] . '%'
                        ]);
                        $similar_jobs = $similar_stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        if (!empty($similar_jobs)):
                            foreach ($similar_jobs as $similar_job):
                    ?>
                        <a href="job_view.php?id=<?= $similar_job['id'] ?>" class="similar-job-item">
                            <div class="similar-job-title"><?= htmlspecialchars($similar_job['job_title']) ?></div>
                            <div class="similar-job-meta">
                                <span><?= htmlspecialchars($similar_job['job_location']) ?></span>
                                <span class="job-type-badge"><?= ucfirst($similar_job['job_type']) ?></span>
                            </div>
                        </a>
                    <?php 
                            endforeach;
                        else:
                    ?>
                        <p class="no-similar-jobs">No similar jobs found at the moment.</p>
                    <?php 
                        endif;
                    } catch (PDOException $e) {
                        echo '<p class="no-similar-jobs">Unable to load similar jobs.</p>';
                    }
                    ?>
                </div>
            </div>

            <!-- Share Job -->
            <div class="sidebar-widget">
                <h3>Share this Job</h3>
                <div class="share-buttons">
                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($_SERVER['REQUEST_URI']) ?>" 
                       target="_blank" class="btn-share btn-facebook">
                        <i class="fa fa-facebook"></i> Facebook
                    </a>
                    <a href="https://twitter.com/intent/tweet?text=<?= urlencode('Check out this job: ' . $job['job_title']) ?>&url=<?= urlencode($_SERVER['REQUEST_URI']) ?>" 
                       target="_blank" class="btn-share btn-twitter">
                        <i class="fa fa-twitter"></i> Twitter
                    </a>
                    <a href="https://wa.me/?text=<?= urlencode('Check out this job opportunity: ' . $job['job_title'] . ' - ' . $_SERVER['REQUEST_URI']) ?>" 
                       target="_blank" class="btn-share btn-whatsapp">
                        <i class="fa fa-whatsapp"></i> WhatsApp
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Job View Specific Styles */
.back-link {
    margin-bottom: 2rem;
}

.btn-back {
    color: #6b7280;
    text-decoration: none;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: color 0.3s ease;
}

.btn-back:hover {
    color: #2563eb;
}

.job-header {
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    border-radius: 16px;
    padding: 2rem;
    margin-bottom: 2rem;
    border: 2px solid #e2e8f0;
}

.job-header-content {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 2rem;
}

.job-company-info {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    flex: 1;
}

.company-logo-large {
    width: 80px;
    height: 80px;
    border-radius: 16px;
    object-fit: cover;
    border: 3px solid #e2e8f0;
}

.company-logo-placeholder-large {
    width: 80px;
    height: 80px;
    border-radius: 16px;
    background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #9ca3af;
    font-size: 2rem;
    border: 3px solid #e2e8f0;
}

.job-title-section h1 {
    color: #1e3a8a;
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    line-height: 1.2;
}

.company-name-large {
    color: #6b7280;
    font-size: 1.1rem;
    font-weight: 600;
}

.job-badges-large {
    display: flex;
    flex-wrap: wrap;
    gap: 0.8rem;
    align-items: flex-start;
}

.job-meta-section {
    background: #fff;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    border: 2px solid #e2e8f0;
}

.meta-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
}

.meta-item-large {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.meta-item-large i {
    color: #2563eb;
    font-size: 1.2rem;
    width: 20px;
    text-align: center;
}

.meta-label {
    display: block;
    color: #6b7280;
    font-size: 0.9rem;
    font-weight: 500;
    margin-bottom: 0.2rem;
}

.meta-value {
    display: block;
    color: #374151;
    font-weight: 600;
}

.days-ago {
    color: #6b7280;
    font-weight: 400;
    font-size: 0.9rem;
}

.job-content-grid {
    display: grid;
    grid-template-columns: 1fr 350px;
    gap: 2rem;
    align-items: start;
}

.job-main-content {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.content-section {
    background: #fff;
    border-radius: 12px;
    padding: 2rem;
    border: 2px solid #e2e8f0;
}

.content-section h2 {
    color: #1e3a8a;
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 1rem;
    border-bottom: 2px solid #e2e8f0;
    padding-bottom: 0.5rem;
}

.job-description, .job-requirements, .job-skills, .company-description {
    color: #374151;
    line-height: 1.7;
    font-size: 1rem;
}

.job-sidebar {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.apply-section {
    background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
    border: 2px solid #3b82f6;
}

.apply-options {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.btn-apply {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.8rem;
    padding: 1rem 1.5rem;
    border-radius: 12px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.btn-email {
    background: linear-gradient(135deg, #2563eb 0%, #1e3a8a 100%);
    color: #fff;
}

.btn-email:hover {
    background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%);
    transform: translateY(-2px);
    color: #fff;
}

.btn-whatsapp {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: #fff;
}

.btn-whatsapp:hover {
    background: linear-gradient(135deg, #059669 0%, #10b981 100%);
    transform: translateY(-2px);
    color: #fff;
}

.btn-phone {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    color: #fff;
}

.btn-phone:hover {
    background: linear-gradient(135deg, #d97706 0%, #f59e0b 100%);
    transform: translateY(-2px);
    color: #fff;
}

.no-contact-info {
    text-align: center;
    color: #6b7280;
    font-style: italic;
}

.job-summary {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.summary-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.8rem 0;
    border-bottom: 1px solid #e2e8f0;
}

.summary-item:last-child {
    border-bottom: none;
}

.summary-label {
    color: #6b7280;
    font-weight: 500;
}

.summary-value {
    color: #374151;
    font-weight: 600;
}

.similar-jobs {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.similar-job-item {
    background: #f8fafc;
    border-radius: 8px;
    padding: 1rem;
    text-decoration: none;
    color: inherit;
    border: 1px solid #e2e8f0;
    transition: all 0.3s ease;
}

.similar-job-item:hover {
    background: #e0e7ff;
    border-color: #2563eb;
    transform: translateY(-1px);
}

.similar-job-title {
    color: #1e3a8a;
    font-weight: 600;
    margin-bottom: 0.5rem;
    font-size: 0.95rem;
}

.similar-job-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.85rem;
    color: #6b7280;
}

.job-type-badge {
    background: #e0e7ff;
    color: #3730a3;
    padding: 0.2rem 0.5rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 500;
}

.no-similar-jobs {
    color: #6b7280;
    font-style: italic;
    text-align: center;
}

.share-buttons {
    display: flex;
    flex-direction: column;
    gap: 0.8rem;
}

.btn-share {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.8rem 1rem;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s ease;
}

.btn-facebook {
    background: #1877f2;
    color: #fff;
}

.btn-facebook:hover {
    background: #166fe5;
    color: #fff;
    transform: translateY(-1px);
}

.btn-twitter {
    background: #1da1f2;
    color: #fff;
}

.btn-twitter:hover {
    background: #1a91da;
    color: #fff;
    transform: translateY(-1px);
}

.btn-whatsapp {
    background: #25d366;
    color: #fff;
}

.btn-whatsapp:hover {
    background: #22c55e;
    color: #fff;
    transform: translateY(-1px);
}

/* Responsive Design */
@media (max-width: 1200px) {
    .job-content-grid {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
    
    .job-sidebar {
        order: -1;
    }
}

@media (max-width: 900px) {
    .job-header-content {
        flex-direction: column;
        align-items: flex-start;
        gap: 1.5rem;
    }
    
    .job-badges-large {
        align-self: flex-start;
    }
    
    .meta-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .job-title-section h1 {
        font-size: 1.8rem;
    }
}

@media (max-width: 600px) {
    .job-header {
        padding: 1.5rem;
    }
    
    .job-company-info {
        flex-direction: column;
        text-align: center;
        gap: 1rem;
    }
    
    .content-section {
        padding: 1.5rem;
    }
    
    .apply-options {
        gap: 0.8rem;
    }
    
    .btn-apply {
        padding: 0.8rem 1rem;
        font-size: 0.9rem;
    }
}
/* Save Job Button */
.save-job-section {
    text-align: center;
}

.btn-save-job-large {
    background: linear-gradient(90deg, #ffd700 0%, #ffd700 100%);
    color: #1a3353;
    border: none;
    border-radius: 8px;
    padding: 0.75rem 1.5rem;
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    width: 100%;
    transition: all 0.2s ease;
}

.btn-save-job-large:hover {
    background: linear-gradient(90deg, #ffd700 0%, #ffcc00 100%);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(255, 215, 0, 0.3);
}

.btn-save-job-large.saved {
    background: linear-gradient(90deg, #28a745 0%, #20c997 100%);
    color: white;
}

.btn-save-job-large.saved:hover {
    background: linear-gradient(90deg, #218838 0%, #1ea085 100%);
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
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle save job button
    const saveJobBtn = document.querySelector('.btn-save-job-large');
    if (saveJobBtn) {
        saveJobBtn.addEventListener('click', function() {
            const jobId = this.dataset.jobId;
            
            // Show loading state
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>Saving...</span>';
            this.disabled = true;
            
            // Send AJAX request to save job
            fetch('/api/save_job.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `job_id=${jobId}&action=toggle`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update button state
                    if (data.is_saved) {
                        this.innerHTML = '<i class="fas fa-bookmark"></i><span>Saved</span>';
                        this.classList.add('saved');
                        this.title = 'Remove from saved jobs';
                        showNotification(data.message, 'success');
                    } else {
                        this.innerHTML = '<i class="fas fa-bookmark"></i><span>Save Job</span>';
                        this.classList.remove('saved');
                        this.title = 'Save this job';
                        showNotification(data.message, 'success');
                    }
                } else if (data.action === 'login_required') {
                    // Show login modal or redirect
                    showNotification('Please log in to save jobs', 'info');
                    setTimeout(() => {
                        window.location.href = '/auth/login.php?redirect=' + encodeURIComponent(window.location.href);
                    }, 2000);
                } else {
                    showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('An error occurred while saving the job', 'error');
            })
            .finally(() => {
                this.disabled = false;
            });
        });
    }
    
    // Function to show notifications
    function showNotification(message, type) {
        const notification = document.createElement('div');
        notification.className = `alert alert-${type === 'success' ? 'success' : type === 'error' ? 'danger' : type === 'warning' ? 'warning' : 'info'} notification`;
        notification.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-triangle' : type === 'warning' ? 'exclamation-triangle' : 'info-circle'} me-2"></i>
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

<?php include 'includes/footer_main.php'; ?> 