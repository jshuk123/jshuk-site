<?php
require_once 'config/config.php';
require_once 'includes/helpers.php';

// Get company slug from URL
$company_slug = $_GET['slug'] ?? '';

if (empty($company_slug)) {
    header('Location: /recruitment.php');
    exit;
}

try {
    // Get company profile
    $stmt = $pdo->prepare("
        SELECT cp.*, u.first_name, u.last_name, u.email as user_email
        FROM company_profiles cp
        LEFT JOIN users u ON cp.user_id = u.id
        WHERE cp.slug = ? AND cp.is_active = 1
    ");
    $stmt->execute([$company_slug]);
    $company = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$company) {
        header('Location: /recruitment.php');
        exit;
    }

    // Get company's active job postings
    $stmt = $pdo->prepare("
        SELECT r.*, s.name as sector_name
        FROM recruitment r
        LEFT JOIN job_sectors s ON r.sector_id = s.id
        WHERE r.company_profile_id = ? AND r.is_active = 1
        ORDER BY r.created_at DESC
    ");
    $stmt->execute([$company['id']]);
    $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Increment view count
    $stmt = $pdo->prepare("UPDATE company_profiles SET views_count = views_count + 1 WHERE id = ?");
    $stmt->execute([$company['id']]);

} catch (PDOException $e) {
    error_log("Company Profile Error: " . $e->getMessage());
    header('Location: /recruitment.php');
    exit;
}

$pageTitle = htmlspecialchars($company['company_name']);
$page_css = "company_profile.css";
$metaDescription = htmlspecialchars($company['description'] ?: "Learn more about " . $company['company_name'] . " and view their current job openings.");
include 'includes/header_main.php';
?>

<div class="company-profile-container">
    <!-- Company Header -->
    <div class="company-header">
        <?php if ($company['banner_path']): ?>
            <div class="company-banner">
                <img src="<?= htmlspecialchars($company['banner_path']) ?>" alt="<?= htmlspecialchars($company['company_name']) ?> Banner">
            </div>
        <?php endif; ?>
        
        <div class="company-header-content">
            <div class="company-logo-section">
                <?php if ($company['logo_path']): ?>
                    <img src="<?= htmlspecialchars($company['logo_path']) ?>" alt="<?= htmlspecialchars($company['company_name']) ?> Logo" class="company-logo">
                <?php else: ?>
                    <div class="company-logo-placeholder">
                        <i class="fas fa-building"></i>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="company-info">
                <h1 class="company-name"><?= htmlspecialchars($company['company_name']) ?></h1>
                <?php if ($company['is_verified']): ?>
                    <span class="verified-badge">
                        <i class="fas fa-check-circle"></i> Verified Company
                    </span>
                <?php endif; ?>
                
                <?php if ($company['industry']): ?>
                    <p class="company-industry">
                        <i class="fas fa-industry"></i>
                        <?= htmlspecialchars($company['industry']) ?>
                    </p>
                <?php endif; ?>
                
                <?php if ($company['location']): ?>
                    <p class="company-location">
                        <i class="fas fa-map-marker-alt"></i>
                        <?= htmlspecialchars($company['location']) ?>
                    </p>
                <?php endif; ?>
            </div>
            
            <div class="company-actions">
                <?php if ($company['website']): ?>
                    <a href="<?= htmlspecialchars($company['website']) ?>" target="_blank" class="btn-company-website">
                        <i class="fas fa-external-link-alt"></i>
                        Visit Website
                    </a>
                <?php endif; ?>
                
                <a href="#jobs" class="btn-view-jobs">
                    <i class="fas fa-briefcase"></i>
                    View Jobs (<?= count($jobs) ?>)
                </a>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="company-content">
        <div class="content-grid">
            <!-- Company Information -->
            <div class="company-main-content">
                <?php if ($company['about_us']): ?>
                <section class="company-section">
                    <h2>About Us</h2>
                    <div class="about-content">
                        <?= nl2br(htmlspecialchars($company['about_us'])) ?>
                    </div>
                </section>
                <?php endif; ?>

                <?php if ($company['description']): ?>
                <section class="company-section">
                    <h2>Company Overview</h2>
                    <div class="overview-content">
                        <?= nl2br(htmlspecialchars($company['description'])) ?>
                    </div>
                </section>
                <?php endif; ?>
            </div>

            <!-- Company Details Sidebar -->
            <div class="company-sidebar">
                <div class="sidebar-widget">
                    <h3>At a Glance</h3>
                    <div class="company-details">
                        <?php if ($company['company_size']): ?>
                        <div class="detail-item">
                            <span class="detail-label">Company Size:</span>
                            <span class="detail-value"><?= htmlspecialchars($company['company_size']) ?> employees</span>
                        </div>
                        <?php endif; ?>

                        <?php if ($company['founded_year']): ?>
                        <div class="detail-item">
                            <span class="detail-label">Founded:</span>
                            <span class="detail-value"><?= htmlspecialchars($company['founded_year']) ?></span>
                        </div>
                        <?php endif; ?>

                        <?php if ($company['industry']): ?>
                        <div class="detail-item">
                            <span class="detail-label">Industry:</span>
                            <span class="detail-value"><?= htmlspecialchars($company['industry']) ?></span>
                        </div>
                        <?php endif; ?>

                        <?php if ($company['location']): ?>
                        <div class="detail-item">
                            <span class="detail-label">Location:</span>
                            <span class="detail-value"><?= htmlspecialchars($company['location']) ?></span>
                        </div>
                        <?php endif; ?>

                        <?php if ($company['website']): ?>
                        <div class="detail-item">
                            <span class="detail-label">Website:</span>
                            <span class="detail-value">
                                <a href="<?= htmlspecialchars($company['website']) ?>" target="_blank">
                                    <?= htmlspecialchars(parse_url($company['website'], PHP_URL_HOST) ?: $company['website']) ?>
                                </a>
                            </span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Social Links -->
                <?php if ($company['social_linkedin'] || $company['social_twitter'] || $company['social_facebook']): ?>
                <div class="sidebar-widget">
                    <h3>Follow Us</h3>
                    <div class="social-links">
                        <?php if ($company['social_linkedin']): ?>
                            <a href="<?= htmlspecialchars($company['social_linkedin']) ?>" target="_blank" class="social-link linkedin">
                                <i class="fab fa-linkedin"></i>
                                LinkedIn
                            </a>
                        <?php endif; ?>

                        <?php if ($company['social_twitter']): ?>
                            <a href="<?= htmlspecialchars($company['social_twitter']) ?>" target="_blank" class="social-link twitter">
                                <i class="fab fa-twitter"></i>
                                Twitter
                            </a>
                        <?php endif; ?>

                        <?php if ($company['social_facebook']): ?>
                            <a href="<?= htmlspecialchars($company['social_facebook']) ?>" target="_blank" class="social-link facebook">
                                <i class="fab fa-facebook"></i>
                                Facebook
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Contact Information -->
                <?php if ($company['contact_email'] || $company['contact_phone']): ?>
                <div class="sidebar-widget">
                    <h3>Contact Information</h3>
                    <div class="contact-info">
                        <?php if ($company['contact_email']): ?>
                            <div class="contact-item">
                                <i class="fas fa-envelope"></i>
                                <a href="mailto:<?= htmlspecialchars($company['contact_email']) ?>">
                                    <?= htmlspecialchars($company['contact_email']) ?>
                                </a>
                            </div>
                        <?php endif; ?>

                        <?php if ($company['contact_phone']): ?>
                            <div class="contact-item">
                                <i class="fas fa-phone"></i>
                                <a href="tel:<?= htmlspecialchars($company['contact_phone']) ?>">
                                    <?= htmlspecialchars($company['contact_phone']) ?>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Current Openings Section -->
    <section id="jobs" class="jobs-section">
        <div class="container">
            <div class="section-header">
                <h2>Current Openings at <?= htmlspecialchars($company['company_name']) ?></h2>
                <p class="section-subtitle">
                    <?= count($jobs) ?> active position<?= count($jobs) != 1 ? 's' : '' ?> available
                </p>
            </div>

            <?php if (empty($jobs)): ?>
                <div class="no-jobs-message">
                    <div class="no-jobs-icon">
                        <i class="fas fa-briefcase"></i>
                    </div>
                    <h3>No Open Positions</h3>
                    <p>There are currently no open positions at <?= htmlspecialchars($company['company_name']) ?>.</p>
                    <p>Check back later for new opportunities!</p>
                </div>
            <?php else: ?>
                <div class="jobs-grid">
                    <?php foreach ($jobs as $job): ?>
                        <div class="job-card">
                            <div class="job-header">
                                <div class="job-info">
                                    <h3 class="job-title">
                                        <a href="/job_view.php?id=<?= $job['id'] ?>">
                                            <?= htmlspecialchars($job['job_title']) ?>
                                        </a>
                                    </h3>
                                    <p class="job-location">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <?= htmlspecialchars($job['job_location'] ?? 'Location TBD') ?>
                                    </p>
                                </div>
                                <?php if ($job['is_featured']): ?>
                                    <span class="featured-badge">Featured</span>
                                <?php endif; ?>
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
                                    <?= date('M j, Y', strtotime($job['created_at'])) ?>
                                </span>
                            </div>
                            
                            <div class="job-description">
                                <?= htmlspecialchars(mb_strimwidth($job['job_description'], 0, 150, '...')) ?>
                            </div>
                            
                            <div class="job-footer">
                                <a href="/job_view.php?id=<?= $job['id'] ?>" class="btn-view-job">
                                    <span>View Job</span>
                                    <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
</div>

<style>
/* Company Profile Styles */
.company-profile-container {
    background: #f8f9fa;
    min-height: 100vh;
}

.company-header {
    background: white;
    border-bottom: 1px solid #e9ecef;
    position: relative;
}

.company-banner {
    height: 200px;
    overflow: hidden;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.company-banner img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.company-header-content {
    padding: 2rem;
    display: flex;
    align-items: center;
    gap: 2rem;
    max-width: 1200px;
    margin: 0 auto;
}

.company-logo-section {
    flex-shrink: 0;
}

.company-logo {
    width: 120px;
    height: 120px;
    border-radius: 12px;
    object-fit: cover;
    border: 4px solid white;
    box-shadow: 0 4px 16px rgba(0,0,0,0.1);
}

.company-logo-placeholder {
    width: 120px;
    height: 120px;
    border-radius: 12px;
    background: linear-gradient(135deg, #ffd700 0%, #ffcc00 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3rem;
    color: #1a3353;
    border: 4px solid white;
    box-shadow: 0 4px 16px rgba(0,0,0,0.1);
}

.company-info {
    flex: 1;
}

.company-name {
    font-size: 2.5rem;
    font-weight: 700;
    color: #1a3353;
    margin-bottom: 0.5rem;
}

.verified-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background: #28a745;
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 600;
    margin-bottom: 1rem;
}

.company-industry,
.company-location {
    color: #6c757d;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.company-actions {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    flex-shrink: 0;
}

.btn-company-website,
.btn-view-jobs {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.2s ease;
    min-width: 150px;
}

.btn-company-website {
    background: #6c757d;
    color: white;
}

.btn-company-website:hover {
    background: #5a6268;
    color: white;
    transform: translateY(-1px);
}

.btn-view-jobs {
    background: linear-gradient(90deg, #ffd700 0%, #ffd700 100%);
    color: #1a3353;
}

.btn-view-jobs:hover {
    background: linear-gradient(90deg, #ffd700 0%, #ffcc00 100%);
    color: #1a3353;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(255, 215, 0, 0.3);
}

.company-content {
    padding: 3rem 0;
}

.content-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 3rem;
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 2rem;
}

.company-section {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: 0 4px 16px rgba(0,0,0,0.08);
}

.company-section h2 {
    color: #1a3353;
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 1rem;
    border-bottom: 2px solid #ffd700;
    padding-bottom: 0.5rem;
}

.about-content,
.overview-content {
    line-height: 1.6;
    color: #495057;
}

.sidebar-widget {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 4px 16px rgba(0,0,0,0.08);
}

.sidebar-widget h3 {
    color: #1a3353;
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 1rem;
    border-bottom: 2px solid #ffd700;
    padding-bottom: 0.5rem;
}

.company-details {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.detail-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
    border-bottom: 1px solid #f0f0f0;
}

.detail-item:last-child {
    border-bottom: none;
}

.detail-label {
    font-weight: 600;
    color: #6c757d;
}

.detail-value {
    color: #1a3353;
    text-align: right;
}

.detail-value a {
    color: #ffd700;
    text-decoration: none;
}

.detail-value a:hover {
    text-decoration: underline;
}

.social-links {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.social-link {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.2s ease;
}

.social-link.linkedin {
    background: #0077b5;
    color: white;
}

.social-link.twitter {
    background: #1da1f2;
    color: white;
}

.social-link.facebook {
    background: #1877f2;
    color: white;
}

.social-link:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.contact-info {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.contact-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.5rem 0;
}

.contact-item i {
    color: #ffd700;
    width: 20px;
}

.contact-item a {
    color: #1a3353;
    text-decoration: none;
}

.contact-item a:hover {
    color: #ffd700;
}

.jobs-section {
    background: white;
    padding: 3rem 0;
}

.section-header {
    text-align: center;
    margin-bottom: 3rem;
}

.section-header h2 {
    color: #1a3353;
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.section-subtitle {
    color: #6c757d;
    font-size: 1.1rem;
}

.no-jobs-message {
    text-align: center;
    padding: 4rem 2rem;
}

.no-jobs-icon {
    font-size: 4rem;
    color: #dee2e6;
    margin-bottom: 1rem;
}

.no-jobs-message h3 {
    color: #1a3353;
    margin-bottom: 1rem;
}

.no-jobs-message p {
    color: #6c757d;
    margin-bottom: 0.5rem;
}

.jobs-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 2rem;
}

.job-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 4px 16px rgba(0,0,0,0.08);
    border: 1px solid #f0f0f0;
    transition: all 0.3s ease;
}

.job-card:hover {
    transform: translateY(-4px);
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
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.featured-badge {
    background: #ffd700;
    color: #1a3353;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
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
    justify-content: flex-end;
}

.btn-view-job {
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

.btn-view-job:hover {
    background: linear-gradient(90deg, #ffd700 0%, #ffcc00 100%);
    color: #1a3353;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(255, 215, 0, 0.3);
}

/* Mobile Responsiveness */
@media (max-width: 768px) {
    .company-header-content {
        flex-direction: column;
        text-align: center;
        gap: 1.5rem;
    }
    
    .company-name {
        font-size: 2rem;
    }
    
    .content-grid {
        grid-template-columns: 1fr;
        gap: 2rem;
        padding: 0 1rem;
    }
    
    .company-actions {
        flex-direction: row;
        justify-content: center;
    }
    
    .jobs-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
}
</style>

<?php include 'includes/footer_main.php'; ?> 