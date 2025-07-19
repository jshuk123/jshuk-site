<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

require_once 'config/config.php';
require_once 'includes/helpers.php';

// Get user_id for saved jobs functionality
$user_id = $_SESSION['user_id'] ?? 0;

// --- Data Fetching ---
$error_message = '';
$warning_message = '';
$jobs = [];
$sectors = [];
$featured_job = null;
$has_filters = false;

// Get filter parameters
$sector_filter = $_GET['sector'] ?? '';
$location_filter = $_GET['location'] ?? '';
$job_type_filter = $_GET['job_type'] ?? '';
$search_query = $_GET['search'] ?? '';
$sort_by = $_GET['sort'] ?? 'newest';

// Check if any filters are applied
$has_filters = !empty($sector_filter) || !empty($location_filter) || !empty($job_type_filter) || !empty($search_query);

// Input validation and sanitization
$valid_job_types = ['full-time', 'part-time', 'contract', 'temporary', 'internship'];
if (!in_array($job_type_filter, $valid_job_types)) {
    $job_type_filter = '';
}

$valid_sort_options = ['newest', 'featured', 'sector', 'location'];
if (!in_array($sort_by, $valid_sort_options)) {
    $sort_by = 'newest';
}

if (!empty($sector_filter) && !ctype_digit($sector_filter)) {
    $sector_filter = '';
}

if (!empty($location_filter)) {
    $location_filter = filter_var($location_filter, FILTER_SANITIZE_STRING);
}

try {
    // Get featured job of the week (most recent featured job)
    $featured_query = "
        SELECT r.*, s.name as sector_name, b.business_name,
               bi.file_path as business_logo, u.profile_image, u.first_name, u.last_name,
               CASE WHEN sj.id IS NOT NULL THEN 1 ELSE 0 END as is_saved
        FROM recruitment r
        LEFT JOIN job_sectors s ON r.sector_id = s.id
        LEFT JOIN businesses b ON r.business_id = b.id
        LEFT JOIN business_images bi ON b.id = bi.business_id AND bi.sort_order = 0
        LEFT JOIN users u ON r.user_id = u.id
        LEFT JOIN saved_jobs sj ON r.id = sj.job_id AND sj.user_id = ?
        WHERE r.is_active = 1 AND r.is_featured = 1
        ORDER BY r.created_at DESC
        LIMIT 1
    ";
    $featured_stmt = $pdo->prepare($featured_query);
    $featured_stmt->execute([$user_id]);
    $featured_job = $featured_stmt->fetch(PDO::FETCH_ASSOC);

    // Build the main query with proper joins
    $query = "
        SELECT r.*, s.name as sector_name, b.business_name,
               bi.file_path as business_logo, u.profile_image, u.first_name, u.last_name,
               CASE WHEN sj.id IS NOT NULL THEN 1 ELSE 0 END as is_saved
        FROM recruitment r
        LEFT JOIN job_sectors s ON r.sector_id = s.id
        LEFT JOIN businesses b ON r.business_id = b.id
        LEFT JOIN business_images bi ON b.id = bi.business_id AND bi.sort_order = 0
        LEFT JOIN users u ON r.user_id = u.id
        LEFT JOIN saved_jobs sj ON r.id = sj.job_id AND sj.user_id = ?
        WHERE r.is_active = 1
    ";
    
    // Build the query with filters
    $where_conditions = ["r.is_active = 1"];
    $params = [];
    
    if (!empty($sector_filter)) {
        $where_conditions[] = "r.sector_id = ?";
        $params[] = $sector_filter;
    }
    
    if (!empty($location_filter)) {
        $where_conditions[] = "r.job_location LIKE ?";
        $params[] = "%$location_filter%";
    }
    
    if (!empty($job_type_filter)) {
        $where_conditions[] = "r.job_type = ?";
        $params[] = $job_type_filter;
    }
    
    if (!empty($search_query)) {
        $where_conditions[] = "(r.job_title LIKE ? OR r.job_description LIKE ? OR b.business_name LIKE ?)";
        $params[] = "%$search_query%";
        $params[] = "%$search_query%";
        $params[] = "%$search_query%";
    }
    
    $where_clause = implode(" AND ", $where_conditions);
    
    // Build ORDER BY clause
    $order_clause = "ORDER BY ";
    switch ($sort_by) {
        case 'featured':
            $order_clause .= "r.is_featured DESC, r.created_at DESC";
            break;
        case 'sector':
            $order_clause .= "s.name ASC, r.created_at DESC";
            break;
        case 'location':
            $order_clause .= "r.job_location ASC, r.created_at DESC";
            break;
        default: // newest
            $order_clause .= "r.created_at DESC";
    }
    
    $sql = $query . " AND $where_clause $order_clause";
    
    $stmt = $pdo->prepare($sql);
    
    // Add user_id as the first parameter for the saved_jobs join
    $all_params = array_merge([$user_id], $params);
    
    $stmt->execute($all_params);
    $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get sectors for filter dropdown
    $sectors = $pdo->query("SELECT * FROM job_sectors ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
    
    // Get unique locations for filter dropdown
    $locations = $pdo->query("SELECT DISTINCT job_location FROM recruitment WHERE job_location IS NOT NULL AND job_location != '' ORDER BY job_location")->fetchAll(PDO::FETCH_COLUMN);

    // ✅ Fixed: Better error handling - only show error for actual DB issues
    if (empty($jobs) && $has_filters) {
        $warning_message = "😕 No jobs found matching your filters. Try adjusting your search criteria or <a href='/submit_job.php'>post a new job</a>.";
    } elseif (empty($jobs) && !$has_filters) {
        $warning_message = "😕 No jobs available at the moment. <a href='/submit_job.php'>Be the first to post a job</a>!";
    }

} catch (PDOException $e) {
    // ✅ Fixed: Log the error for debugging
    error_log("Database error in recruitment.php: " . $e->getMessage());
    $jobs = [];
    $error_message = "Unable to load job listings. Please try again later.";
}

$sector_map = [];
foreach ($sectors as $sector) {
    $sector_map[$sector['id']] = $sector['name'];
}

$pageTitle = "Job Board | Find Jewish Community Jobs";
$page_css = "recruitment.css";
$metaDescription = "Find job opportunities in the Jewish community. Browse full-time, part-time, and contract positions from trusted employers. Post your own job listings on JShuk.";
$metaKeywords = "jewish jobs, community employment, kosher jobs, jewish community careers, job board";
include 'includes/header_main.php';

require_once 'includes/ad_renderer.php'; 

// DEBUG: Add debug output for ad system
if (isset($_GET['debug_ads'])) {
    echo "<div style='background: #f0f0f0; border: 2px solid #ff0000; padding: 10px; margin: 10px; font-family: monospace;'>";
    echo "<h3>🔍 AD SYSTEM DEBUG - RECRUITMENT PAGE</h3>";
    
    // Test database connection
    try {
        $test = $pdo->query("SELECT 1");
        echo "<p>✅ Database connection: OK</p>";
    } catch (Exception $e) {
        echo "<p>❌ Database connection failed: " . $e->getMessage() . "</p>";
    }
    
    // Check ads table
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'ads'");
        if ($stmt->rowCount() > 0) {
            echo "<p>✅ Ads table exists</p>";
            
            // Count total ads
            $stmt = $pdo->query("SELECT COUNT(*) FROM ads");
            $total = $stmt->fetchColumn();
            echo "<p>📊 Total ads in database: $total</p>";
            
            // Check for header ads
            $now = date('Y-m-d');
            $stmt = $pdo->prepare("SELECT * FROM ads WHERE zone = 'header' AND status = 'active' AND start_date <= ? AND end_date >= ?");
            $stmt->execute([$now, $now]);
            $headerAds = $stmt->fetchAll();
            echo "<p>🎯 Header ads matching criteria: " . count($headerAds) . "</p>";
            
            if (!empty($headerAds)) {
                echo "<p>📋 Header ad details:</p>";
                foreach ($headerAds as $ad) {
                    echo "<ul>";
                    echo "<li>ID: " . $ad['id'] . "</li>";
                    echo "<li>Title: " . htmlspecialchars($ad['title'] ?? 'N/A') . "</li>";
                    echo "<li>Status: " . htmlspecialchars($ad['status'] ?? 'N/A') . "</li>";
                    echo "<li>Start Date: " . htmlspecialchars($ad['start_date'] ?? 'N/A') . "</li>";
                    echo "<li>End Date: " . htmlspecialchars($ad['end_date'] ?? 'N/A') . "</li>";
                    echo "<li>Image URL: " . htmlspecialchars($ad['image_url'] ?? 'N/A') . "</li>";
                    echo "</ul>";
                }
            }
        } else {
            echo "<p>❌ Ads table does not exist</p>";
        }
    } catch (Exception $e) {
        echo "<p>❌ Error checking ads table: " . $e->getMessage() . "</p>";
    }
    
    echo "</div>";
}

// Render header ad
$header_ad = renderAd('header', $pdo);
if ($header_ad) {
    echo $header_ad;
}
?>

<!-- Enhanced Hero Section with Integration -->
<div class="hero-section">
    <div class="container">
        <div class="hero-content">
            <h1 class="hero-title">Find Your Perfect Job</h1>
            <p class="hero-subtitle">
                Discover opportunities in the Jewish community. From entry-level positions to executive roles, 
                find jobs that align with your values and career goals.
            </p>
            
            <!-- Enhanced CTA Buttons with Integration -->
            <div class="hero-cta-buttons">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="/submit_job.php" class="hero-btn">Post a Job</a>
                    <a href="/users/saved_jobs.php" class="hero-btn">My Saved Jobs</a>
                    <a href="/users/dashboard.php" class="hero-btn hero-btn-secondary">My Dashboard</a>
                <?php else: ?>
                    <a href="/auth/login.php" class="hero-btn">Login to Post</a>
                    <a href="/auth/register.php" class="hero-btn hero-btn-secondary">Sign Up Free</a>
                <?php endif; ?>
            </div>
            
            <!-- Quick Access Links -->
            <div class="quick-access-links">
                <a href="/salary-guide.php" class="quick-link">
                    <i class="fas fa-chart-line"></i>
                    Salary Guide
                </a>
                <a href="/career-advice.php" class="quick-link">
                    <i class="fas fa-lightbulb"></i>
                    Career Advice
                </a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="/users/manage_jobs.php" class="quick-link">
                        <i class="fas fa-briefcase"></i>
                        Manage Jobs
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Enhanced Search & Filter Section -->
<div class="search-section">
    <div class="container">
        <form method="GET" class="search-form" id="jobSearchForm">
            <div class="search-row">
                <div class="search-group">
                    <label for="search">Search Jobs</label>
                    <input type="text" id="search" name="search" 
                           value="<?= htmlspecialchars($search_query) ?>" 
                           placeholder="Job title, company, or keywords...">
                </div>
                
                <div class="search-group">
                    <label for="sector">Sector</label>
                    <select id="sector" name="sector">
                        <option value="">All Sectors</option>
                        <?php foreach ($sectors as $sector): ?>
                            <option value="<?= $sector['id'] ?>" 
                                    <?= $sector_filter == $sector['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($sector['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="search-group">
                    <label for="location">Location</label>
                    <select id="location" name="location">
                        <option value="">All Locations</option>
                        <?php foreach ($locations as $location): ?>
                            <option value="<?= htmlspecialchars($location) ?>" 
                                    <?= $location_filter === $location ? 'selected' : '' ?>>
                                <?= htmlspecialchars($location) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="search-group">
                    <label for="job_type">Job Type</label>
                    <select id="job_type" name="job_type">
                        <option value="">All Types</option>
                        <option value="full-time" <?= $job_type_filter === 'full-time' ? 'selected' : '' ?>>Full-time</option>
                        <option value="part-time" <?= $job_type_filter === 'part-time' ? 'selected' : '' ?>>Part-time</option>
                        <option value="contract" <?= $job_type_filter === 'contract' ? 'selected' : '' ?>>Contract</option>
                        <option value="temporary" <?= $job_type_filter === 'temporary' ? 'selected' : '' ?>>Temporary</option>
                        <option value="internship" <?= $job_type_filter === 'internship' ? 'selected' : '' ?>>Internship</option>
                    </select>
                </div>
                
                <div class="search-group">
                    <label for="sort">Sort By</label>
                    <select id="sort" name="sort">
                        <option value="newest" <?= $sort_by === 'newest' ? 'selected' : '' ?>>Newest First</option>
                        <option value="featured" <?= $sort_by === 'featured' ? 'selected' : '' ?>>Featured Jobs</option>
                        <option value="sector" <?= $sort_by === 'sector' ? 'selected' : '' ?>>By Sector</option>
                        <option value="location" <?= $sort_by === 'location' ? 'selected' : '' ?>>By Location</option>
                    </select>
                </div>
            </div>
            
            <div class="search-actions">
                <button type="submit" class="search-btn">
                    <i class="fas fa-search"></i>
                    Search Jobs
                </button>
                <a href="/recruitment.php" class="clear-btn">
                    <i class="fas fa-times"></i>
                    Clear Filters
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Featured Job of the Week Section -->
<?php if ($featured_job): ?>
<div class="featured-job-section">
    <div class="container">
        <div class="featured-job-card">
            <div class="featured-badge">
                <i class="fas fa-star"></i>
                Featured Job of the Week
            </div>
            
            <div class="featured-job-content">
                <div class="featured-job-header">
                    <h2 class="featured-job-title">
                        <a href="/job_view.php?id=<?= $featured_job['id'] ?>">
                            <?= htmlspecialchars($featured_job['job_title']) ?>
                        </a>
                    </h2>
                    
                    <?php if ($featured_job['business_name']): ?>
                        <div class="featured-company">
                            <a href="/company-profile.php?slug=<?= urlencode(strtolower(str_replace(' ', '-', $featured_job['business_name']))) ?>">
                                <?= htmlspecialchars($featured_job['business_name']) ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="featured-job-meta">
                    <span class="job-location">
                        <i class="fas fa-map-marker-alt"></i>
                        <?= htmlspecialchars($featured_job['job_location']) ?>
                    </span>
                    <span class="job-type">
                        <i class="fas fa-clock"></i>
                        <?= ucfirst(str_replace('-', ' ', $featured_job['job_type'])) ?>
                    </span>
                    <?php if ($featured_job['sector_name']): ?>
                        <span class="job-sector">
                            <i class="fas fa-briefcase"></i>
                            <?= htmlspecialchars($featured_job['sector_name']) ?>
                        </span>
                    <?php endif; ?>
                </div>
                
                <p class="featured-job-excerpt">
                    <?= htmlspecialchars(mb_strimwidth($featured_job['job_description'], 0, 200, '...')) ?>
                </p>
                
                <div class="featured-job-actions">
                    <a href="/job_view.php?id=<?= $featured_job['id'] ?>" class="btn btn-primary">
                        <i class="fas fa-eye"></i>
                        View Job
                    </a>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <button class="btn btn-outline-secondary save-job-btn" data-job-id="<?= $featured_job['id'] ?>">
                            <i class="fas fa-bookmark"></i>
                            Save Job
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Career Hub Integration Section -->
<div class="career-hub-section">
    <div class="container">
        <div class="career-hub-grid">
            <div class="career-hub-card">
                <div class="career-hub-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h3>Salary Research</h3>
                <p>Research salary ranges for different roles and locations to negotiate better offers.</p>
                <a href="/salary-guide.php" class="career-hub-link">
                    Explore Salary Guide
                    <i class="fas fa-arrow-right"></i>
                </a>
            </div>
            
            <div class="career-hub-card">
                <div class="career-hub-icon">
                    <i class="fas fa-lightbulb"></i>
                </div>
                <h3>Career Advice</h3>
                <p>Get expert tips on interviews, resume writing, and career development.</p>
                <a href="/career-advice.php" class="career-hub-link">
                    Read Career Advice
                    <i class="fas fa-arrow-right"></i>
                </a>
            </div>
            
            <?php if (isset($_SESSION['user_id'])): ?>
            <div class="career-hub-card">
                <div class="career-hub-icon">
                    <i class="fas fa-bell"></i>
                </div>
                <h3>Job Alerts</h3>
                <p>Set up personalized job alerts to never miss relevant opportunities.</p>
                <a href="/users/job_alerts.php" class="career-hub-link">
                    Manage Alerts
                    <i class="fas fa-arrow-right"></i>
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Main Job Listings Section -->
<div class="jobs-section">
    <div class="container">
        <?php if ($error_message): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i>
                <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($warning_message): ?>
            <div class="alert alert-warning">
                <i class="fas fa-info-circle"></i>
                <?= $warning_message ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($jobs)): ?>
            <div class="jobs-header">
                <div class="jobs-count">
                    <h2>Job Opportunities</h2>
                    <p><?= count($jobs) ?> job<?= count($jobs) != 1 ? 's' : '' ?> found</p>
                </div>
                
                <div class="jobs-actions">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="/users/saved_jobs.php" class="btn btn-outline-primary">
                            <i class="fas fa-bookmark"></i>
                            My Saved Jobs
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="jobs-grid">
                <?php foreach ($jobs as $job): ?>
                    <div class="job-card" data-job-id="<?= $job['id'] ?>">
                        <?php if ($job['is_featured']): ?>
                            <div class="featured-badge">
                                <i class="fas fa-star"></i>
                                Featured
                            </div>
                        <?php endif; ?>
                        
                        <div class="job-header">
                            <h3 class="job-title">
                                <a href="/job_view.php?id=<?= $job['id'] ?>">
                                    <?= htmlspecialchars($job['job_title']) ?>
                                </a>
                            </h3>
                            
                            <?php if ($job['business_name']): ?>
                                <div class="job-company">
                                    <a href="/company-profile.php?slug=<?= urlencode(strtolower(str_replace(' ', '-', $job['business_name']))) ?>">
                                        <?= htmlspecialchars($job['business_name']) ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="job-meta">
                            <span class="job-location">
                                <i class="fas fa-map-marker-alt"></i>
                                <?= htmlspecialchars($job['job_location']) ?>
                            </span>
                            <span class="job-type">
                                <i class="fas fa-clock"></i>
                                <?= ucfirst(str_replace('-', ' ', $job['job_type'])) ?>
                            </span>
                            <?php if ($job['sector_name']): ?>
                                <span class="job-sector">
                                    <i class="fas fa-briefcase"></i>
                                    <?= htmlspecialchars($job['sector_name']) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <p class="job-excerpt">
                            <?= htmlspecialchars(mb_strimwidth($job['job_description'], 0, 150, '...')) ?>
                        </p>
                        
                        <div class="job-actions">
                            <a href="/job_view.php?id=<?= $job['id'] ?>" class="btn btn-primary btn-sm">
                                <i class="fas fa-eye"></i>
                                View Job
                            </a>
                            
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <button class="btn <?= $job['is_saved'] ? 'btn-success' : 'btn-outline-secondary' ?> btn-sm save-job-btn" 
                                        data-job-id="<?= $job['id'] ?>" 
                                        title="<?= $job['is_saved'] ? 'Remove from saved jobs' : 'Save this job' ?>">
                                    <i class="fas fa-bookmark"></i>
                                    <?= $job['is_saved'] ? 'Saved' : 'Save' ?>
                                </button>
                                
                                <button class="btn btn-outline-info btn-sm create-alert-btn" 
                                        data-job-title="<?= htmlspecialchars($job['job_title']) ?>"
                                        data-job-sector="<?= htmlspecialchars($job['sector_name'] ?? '') ?>"
                                        data-job-location="<?= htmlspecialchars($job['job_location']) ?>"
                                        title="Create job alert for similar positions">
                                    <i class="fas fa-bell"></i>
                                    Alert
                                </button>
                            <?php endif; ?>
                        </div>
                        
                        <div class="job-footer">
                            <span class="job-date">
                                Posted <?= date('M j, Y', strtotime($job['created_at'])) ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Popular Sectors Section -->
<div class="sectors-section">
    <div class="container">
        <h2 class="section-title">Popular Job Sectors</h2>
        <div class="sectors-grid">
            <?php 
            $popular_sectors = array_slice($sectors, 0, 8); // Show top 8 sectors
            foreach ($popular_sectors as $sector): 
                // Count jobs in this sector
                $sector_job_count = 0;
                foreach ($jobs as $job) {
                    if ($job['sector_name'] === $sector['name']) {
                        $sector_job_count++;
                    }
                }
            ?>
                <a href="/recruitment.php?sector=<?= $sector['id'] ?>" class="sector-card">
                    <div class="sector-icon">
                        <i class="fas fa-briefcase"></i>
                    </div>
                    <h3><?= htmlspecialchars($sector['name']) ?></h3>
                    <p><?= $sector_job_count ?> job<?= $sector_job_count != 1 ? 's' : '' ?> available</p>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Call to Action Section -->
<div class="cta-section">
    <div class="container">
        <div class="cta-content">
            <h2>Ready to Take the Next Step?</h2>
            <p>Whether you're looking for your next opportunity or seeking talented candidates, JShuk has you covered.</p>
            
            <div class="cta-buttons">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="/submit_job.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-plus"></i>
                        Post a Job
                    </a>
                    <a href="/users/dashboard.php" class="btn btn-outline-primary btn-lg">
                        <i class="fas fa-tachometer-alt"></i>
                        My Dashboard
                    </a>
                <?php else: ?>
                    <a href="/auth/register.php" class="btn btn-primary btn-lg">
                        <i class="fas fa-user-plus"></i>
                        Sign Up Free
                    </a>
                    <a href="/auth/login.php" class="btn btn-outline-primary btn-lg">
                        <i class="fas fa-sign-in-alt"></i>
                        Login
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Job Alert Modal -->
<div class="modal fade" id="jobAlertModal" tabindex="-1" aria-labelledby="jobAlertModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="jobAlertModalLabel">Create Job Alert</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="jobAlertForm">
                    <div class="mb-3">
                        <label for="alertName" class="form-label">Alert Name</label>
                        <input type="text" class="form-control" id="alertName" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="alertSector" class="form-label">Job Sector</label>
                        <select class="form-select" id="alertSector" name="sector">
                            <option value="">All Sectors</option>
                            <?php foreach ($sectors as $sector): ?>
                                <option value="<?= $sector['id'] ?>"><?= htmlspecialchars($sector['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="alertLocation" class="form-label">Location</label>
                        <select class="form-select" id="alertLocation" name="location">
                            <option value="">All Locations</option>
                            <?php foreach ($locations as $location): ?>
                                <option value="<?= htmlspecialchars($location) ?>"><?= htmlspecialchars($location) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="alertJobType" class="form-label">Job Type</label>
                        <select class="form-select" id="alertJobType" name="job_type">
                            <option value="">All Types</option>
                            <option value="full-time">Full-time</option>
                            <option value="part-time">Part-time</option>
                            <option value="contract">Contract</option>
                            <option value="temporary">Temporary</option>
                            <option value="internship">Internship</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="createAlertBtn">Create Alert</button>
            </div>
        </div>
    </div>
</div>

<!-- Include AJAX Filter JavaScript -->
<script src="/js/jobs_filter.js"></script>

<script>
// Job alert modal functionality (kept separate from AJAX system)
document.addEventListener('DOMContentLoaded', function() {
    // Create alert functionality
    const createAlertBtn = document.getElementById('createAlertBtn');
    if (createAlertBtn) {
        createAlertBtn.addEventListener('click', function() {
            const form = document.getElementById('jobAlertForm');
            const formData = new FormData(form);
            
            fetch('/api/create_job_alert.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Job alert created successfully!');
                    const modal = bootstrap.Modal.getInstance(document.getElementById('jobAlertModal'));
                    modal.hide();
                    form.reset();
                } else {
                    alert('Error creating alert: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error creating alert. Please try again.');
            });
        });
    }
});
</script>

<?php
// Render footer ad
$footer_ad = renderAd('footer', $pdo);
if ($footer_ad) {
    echo $footer_ad;
}

include 'includes/footer_main.php';
?>