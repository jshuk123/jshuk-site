<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

require_once 'config/config.php';
require_once 'includes/helpers.php';

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
               bi.file_path as business_logo, u.profile_image, u.first_name, u.last_name
        FROM recruitment r
        LEFT JOIN job_sectors s ON r.sector_id = s.id
        LEFT JOIN businesses b ON r.business_id = b.id
        LEFT JOIN business_images bi ON b.id = bi.business_id AND bi.sort_order = 0
        LEFT JOIN users u ON r.user_id = u.id
        WHERE r.is_active = 1 AND r.is_featured = 1
        ORDER BY r.created_at DESC
        LIMIT 1
    ";
    $featured_stmt = $pdo->prepare($featured_query);
    $featured_stmt->execute();
    $featured_job = $featured_stmt->fetch(PDO::FETCH_ASSOC);

    // Build the main query with proper joins
    $query = "
        SELECT r.*, s.name as sector_name, b.business_name,
               bi.file_path as business_logo, u.profile_image, u.first_name, u.last_name
        FROM recruitment r
        LEFT JOIN job_sectors s ON r.sector_id = s.id
        LEFT JOIN businesses b ON r.business_id = b.id
        LEFT JOIN business_images bi ON b.id = bi.business_id AND bi.sort_order = 0
        LEFT JOIN users u ON r.user_id = u.id
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
    $stmt->execute($params);
    $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get sectors for filter dropdown
    $sectors = $pdo->query("SELECT * FROM job_sectors ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
    
    // Get unique locations for filter dropdown
    $locations = $pdo->query("SELECT DISTINCT job_location FROM recruitment WHERE job_location IS NOT NULL AND job_location != '' ORDER BY job_location")->fetchAll(PDO::FETCH_COLUMN);

    // ✅ Fixed: Better error handling - only show error for actual DB issues
    if (empty($jobs) && $has_filters) {
        $warning_message = "😕 No jobs found matching your filters. Try adjusting your search criteria or <a href='/post_job.php'>post a new job</a>.";
    } elseif (empty($jobs) && !$has_filters) {
        $warning_message = "😕 No jobs available at the moment. <a href='/post_job.php'>Be the first to post a job</a>!";
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
?>

<!-- HERO SECTION: Match homepage design -->
<section class="hero">
  <div class="hero-inner">
    <h1>Find Your Next Career Opportunity</h1>
    <p class="subheading">
      Discover job opportunities in the Jewish community. Browse full-time, part-time, and contract positions from trusted employers.
    </p>
          <div class="hero-cta-buttons">
        <?php if (isset($_SESSION['user_id'])): ?>
          <a href="/post_job.php" class="hero-btn">Post a Job</a>
          <a href="/users/saved_jobs.php" class="hero-btn">My Saved Jobs</a>
        <?php else: ?>
          <a href="/auth/login.php" class="hero-btn">Login to Post</a>
          <a href="/auth/register.php" class="hero-btn">Sign Up Free</a>
        <?php endif; ?>
        <a href="#jobs" class="hero-btn">Browse Jobs</a>
      </div>
  </div>
</section>

<!-- SEARCH BAR: Use homepage Airbnb-style search -->
<section class="search-banner bg-white py-4 shadow-sm">
  <div class="container">
    <form action="/recruitment.php" method="GET" class="airbnb-search-bar" role="search">
      <select name="sector" class="form-select" aria-label="Select sector">
        <option value="" disabled selected>🏢 Select Sector</option>
        <?php foreach ($sectors as $sector): ?>
          <option value="<?= $sector['id'] ?>" <?= $sector_filter == $sector['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($sector['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <select name="location" class="form-select" aria-label="Select location">
        <option value="" disabled selected>📍 Select Location</option>
        <?php foreach ($locations as $location): ?>
          <option value="<?= $location ?>" <?= $location_filter === $location ? 'selected' : '' ?>>
            <?= htmlspecialchars($location) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <select name="job_type" class="form-select" aria-label="Select job type">
        <option value="" disabled selected>⏰ Job Type</option>
        <option value="full-time" <?= $job_type_filter === 'full-time' ? 'selected' : '' ?>>Full Time</option>
        <option value="part-time" <?= $job_type_filter === 'part-time' ? 'selected' : '' ?>>Part Time</option>
        <option value="contract" <?= $job_type_filter === 'contract' ? 'selected' : '' ?>>Contract</option>
        <option value="temporary" <?= $job_type_filter === 'temporary' ? 'selected' : '' ?>>Temporary</option>
        <option value="internship" <?= $job_type_filter === 'internship' ? 'selected' : '' ?>>Internship</option>
      </select>
      <input type="text" name="search" class="form-control" placeholder="🔍 Search jobs..." value="<?= htmlspecialchars($search_query) ?>" />
      <button type="submit" class="btn btn-search" aria-label="Search">
        <i class="fa fa-search"></i>
        <span class="d-none d-md-inline">Search</span>
      </button>
      
      <?php if (isset($_SESSION['user_id'])): ?>
        <button type="button" class="btn btn-alert" id="createJobAlertBtn" aria-label="Create Job Alert">
          <i class="fa fa-bell"></i>
          <span class="d-none d-md-inline">Create Alert</span>
        </button>
      <?php endif; ?>
    </form>
  </div>
</section>

<!-- Main Content -->
<main class="main-content-wrapper">
  <!-- Featured Job Section -->
  <?php if ($featured_job): ?>
  <section class="featured-job-section" data-scroll>
    <div class="container">
      <div class="section-header">
        <h2 class="section-title">
          <i class="fas fa-star text-warning me-2"></i>Featured Job of the Week
        </h2>
        <p class="section-subtitle">Highlighted opportunity from our community</p>
      </div>
      
      <div class="featured-job-card">
        <div class="featured-job-header">
          <div class="featured-job-logo">
            <?php if ($featured_job['business_logo']): ?>
              <img src="<?= htmlspecialchars($featured_job['business_logo']) ?>" 
                   alt="<?= htmlspecialchars($featured_job['business_name']) ?> Logo"
                   onerror="this.src='/images/jshuk-logo.png';">
            <?php else: ?>
              <img src="/images/jshuk-logo.png" alt="Default Logo">
            <?php endif; ?>
          </div>
          <div class="featured-job-info">
            <h3 class="featured-job-title"><?= htmlspecialchars($featured_job['job_title']) ?></h3>
            <p class="featured-job-company"><?= htmlspecialchars($featured_job['business_name'] ?? 'Company') ?></p>
            <div class="featured-job-meta">
              <span class="job-location">
                <i class="fas fa-map-marker-alt"></i>
                <?= htmlspecialchars($featured_job['job_location'] ?? 'Location TBD') ?>
              </span>
              <span class="job-type">
                <i class="fas fa-clock"></i>
                <?= ucfirst(str_replace('-', ' ', $featured_job['job_type'] ?? 'Full Time')) ?>
              </span>
              <span class="job-sector">
                <i class="fas fa-briefcase"></i>
                <?= htmlspecialchars($featured_job['sector_name'] ?? 'General') ?>
              </span>
            </div>
          </div>
          <div class="featured-job-actions">
            <a href="/job_view.php?id=<?= $featured_job['id'] ?>" class="btn-jshuk-primary">View Job</a>
            <span class="featured-badge">Featured</span>
          </div>
        </div>
        <div class="featured-job-description">
          <?= htmlspecialchars(mb_strimwidth($featured_job['job_description'], 0, 200, '...')) ?>
        </div>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <!-- All Jobs Section -->
  <section id="jobs" class="jobs-section" data-scroll>
    <div class="container">
      <div class="section-header">
        <h2 class="section-title">All Job Listings</h2>
        <p class="section-subtitle">Find your next career opportunity</p>
      </div>
      
      <?php if ($error_message): ?>
        <div class="alert alert-danger text-center">
          <i class="fas fa-exclamation-triangle me-2"></i>
          <?= htmlspecialchars($error_message) ?>
        </div>
      <?php endif; ?>

      <?php if ($warning_message): ?>
        <div class="alert alert-info text-center">
          <?= $warning_message ?>
        </div>
      <?php endif; ?>

      <?php if (empty($jobs) && !$error_message): ?>
        <div class="empty-state text-center py-5">
          <div class="empty-state-icon mb-3">
            <i class="fas fa-briefcase fa-3x text-muted"></i>
          </div>
          <h3>No Jobs Found</h3>
          <p class="text-muted">Be the first to post a job opportunity in our community!</p>
          <?php if (isset($_SESSION['user_id'])): ?>
            <a href="/post_job.php" class="btn-jshuk-primary">Post a Job</a>
          <?php else: ?>
            <a href="/auth/login.php" class="btn-jshuk-primary">Login to Post</a>
          <?php endif; ?>
        </div>
      <?php else: ?>
        <div class="jobs-grid">
          <?php foreach ($jobs as $job): ?>
            <div class="job-card-wrapper">
              <div class="job-card">
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
                    <?php if (isset($_SESSION['user_id'])): ?>
                      <button class="btn-save-job" data-job-id="<?= $job['id'] ?>" title="Save job">
                        <i class="fas fa-bookmark"></i>
                      </button>
                    <?php endif; ?>
                    <?php if ($job['is_featured']): ?>
                      <span class="featured-badge">Featured</span>
                    <?php endif; ?>
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
                    <?= date('M j, Y', strtotime($job['created_at'])) ?>
                  </span>
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
  </section>

  <!-- Popular Sectors Section -->
  <section class="popular-sectors-section" data-scroll>
    <div class="container">
      <div class="section-header">
        <h2 class="section-title">Popular Sectors</h2>
        <p class="section-subtitle">Browse jobs by industry</p>
      </div>
      
      <div class="sectors-grid">
        <?php foreach (array_slice($sectors, 0, 6) as $sector): ?>
          <a href="/recruitment.php?sector=<?= $sector['id'] ?>" class="sector-card">
            <div class="sector-icon">
              <i class="fas fa-briefcase"></i>
            </div>
            <h3 class="sector-name"><?= htmlspecialchars($sector['name']) ?></h3>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
</main>

<?php include 'includes/footer_main.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add loading states to job cards
    const jobCards = document.querySelectorAll('.job-card');
    
    jobCards.forEach(card => {
        card.addEventListener('click', function(e) {
            // Don't add loading if clicking on buttons or links
            if (e.target.tagName === 'A' || e.target.closest('a') || e.target.closest('.btn-save-job')) {
                return;
            }
            
            // Add loading state
            this.classList.add('loading');
            
            // Navigate to job page
            const jobLink = this.querySelector('a[href*="job_view.php"]');
            if (jobLink) {
                window.location.href = jobLink.href;
            }
        });
    });
    
    // Handle save job buttons
    document.querySelectorAll('.btn-save-job').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const jobId = this.dataset.jobId;
            
            // Show loading state
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
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
                        this.innerHTML = '<i class="fas fa-bookmark"></i>';
                        this.style.color = '#ffd700';
                        this.title = 'Remove from saved jobs';
                        showNotification(data.message, 'success');
                    } else {
                        this.innerHTML = '<i class="far fa-bookmark"></i>';
                        this.style.color = '#6c757d';
                        this.title = 'Save job';
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
    });
    
    // Handle create job alert button
    const createAlertBtn = document.getElementById('createJobAlertBtn');
    if (createAlertBtn) {
        createAlertBtn.addEventListener('click', function() {
            // Get current search criteria
            const sectorSelect = document.querySelector('select[name="sector"]');
            const locationSelect = document.querySelector('select[name="location"]');
            const jobTypeSelect = document.querySelector('select[name="job_type"]');
            const searchInput = document.querySelector('input[name="search"]');
            
            const sectorId = sectorSelect ? sectorSelect.value : '';
            const location = locationSelect ? locationSelect.value : '';
            const jobType = jobTypeSelect ? jobTypeSelect.value : '';
            const keywords = searchInput ? searchInput.value : '';
            
            // Check if at least one criteria is set
            if (!sectorId && !location && !jobType && !keywords) {
                showNotification('Please set at least one search criteria before creating an alert', 'warning');
                return;
            }
            
            // Show loading state
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating...';
            this.disabled = true;
            
            // Send AJAX request to create alert
            const formData = new FormData();
            formData.append('sector_id', sectorId);
            formData.append('location', location);
            formData.append('job_type', jobType);
            formData.append('keywords', keywords);
            formData.append('name', 'Job Alert');
            formData.append('email_frequency', 'daily');
            
            fetch('/api/create_job_alert.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'success');
                } else {
                    showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('An error occurred while creating the job alert', 'error');
            })
            .finally(() => {
                this.innerHTML = '<i class="fa fa-bell"></i><span class="d-none d-md-inline">Create Alert</span>';
                this.disabled = false;
            });
        });
    }
    
    // Add smooth scrolling for search form
    const searchForm = document.querySelector('.airbnb-search-bar');
    if (searchForm) {
        searchForm.addEventListener('submit', function() {
            // Add a small delay to show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Searching...';
                submitBtn.disabled = true;
            }
        });
    }
    
    // Add scroll animations
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-in');
            }
        });
    }, observerOptions);
    
    document.querySelectorAll('[data-scroll]').forEach(el => {
        observer.observe(el);
    });
    
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

<style>
/* Import homepage styles */
@import url('/css/pages/homepage.css');
@import url('/css/components/search-bar.css');

/* Recruitment-specific styles */
.jobs-section {
    padding: 3rem 0;
}

.featured-job-section {
    padding: 3rem 0;
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
}

.featured-job-card {
    background: white;
    border-radius: 16px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.1);
    padding: 2rem;
    border: 2px solid #ffd700;
    position: relative;
    overflow: hidden;
}

.featured-job-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #ffd700 0%, #ffcc00 100%);
}

.featured-job-header {
    display: flex;
    align-items: flex-start;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}

.featured-job-logo {
    width: 80px;
    height: 80px;
    border-radius: 12px;
    overflow: hidden;
    background: #f8f9fa;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.featured-job-logo img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.featured-job-info {
    flex: 1;
}

.featured-job-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1a3353;
    margin-bottom: 0.5rem;
}

.featured-job-company {
    font-size: 1.1rem;
    color: #6c757d;
    margin-bottom: 1rem;
}

.featured-job-meta {
    display: flex;
    gap: 1.5rem;
    flex-wrap: wrap;
}

.featured-job-meta span {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: #495057;
}

.featured-job-meta i {
    color: #ffd700;
}

.featured-job-actions {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 0.5rem;
}

.featured-badge {
    background: #ffd700;
    color: #1a3353;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}

.featured-job-description {
    color: #495057;
    line-height: 1.6;
    font-size: 0.95rem;
}

.jobs-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 2rem;
    margin-top: 2rem;
}

.job-card-wrapper {
    animation: fadeInUp 0.6s ease-out;
}

.job-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.08);
    padding: 1.5rem;
    transition: all 0.3s ease;
    cursor: pointer;
    border: 1px solid #f0f0f0;
}

.job-card:hover {
    transform: translateY(-4px);
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

/* Sectors Grid */
.sectors-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-top: 2rem;
}

.sector-card {
    background: white;
    border-radius: 12px;
    padding: 2rem 1.5rem;
    text-align: center;
    text-decoration: none;
    color: inherit;
    box-shadow: 0 4px 16px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
    border: 1px solid #f0f0f0;
}

.sector-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 32px rgba(0,0,0,0.12);
    border-color: #ffd700;
    color: inherit;
}

.sector-icon {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #ffd700 0%, #ffcc00 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
    font-size: 1.5rem;
    color: #1a3353;
}

.sector-name {
    font-size: 1rem;
    font-weight: 600;
    color: #1a3353;
    margin: 0;
}

/* Loading states */
.job-card.loading {
    opacity: 0.7;
    pointer-events: none;
}

.job-card.loading::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 20px;
    height: 20px;
    margin: -10px 0 0 -10px;
    border: 2px solid #ffd700;
    border-top: 2px solid transparent;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Mobile optimizations */
@media (max-width: 768px) {
    .featured-job-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .featured-job-actions {
        align-items: flex-start;
        width: 100%;
    }
    
    .featured-job-meta {
        gap: 1rem;
    }
    
    .jobs-grid {
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
    
    .sectors-grid {
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 1rem;
    }
}

/* Job Actions */
.job-actions {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-save-job {
    background: none;
    border: none;
    color: #6c757d;
    font-size: 1.2rem;
    cursor: pointer;
    padding: 0.5rem;
    border-radius: 6px;
    transition: all 0.2s ease;
}

.btn-save-job:hover {
    background: rgba(255, 215, 0, 0.1);
    transform: scale(1.1);
}

.btn-save-job.saved {
    color: #ffd700;
}

/* Job Alert Button */
.btn-alert {
    background: linear-gradient(90deg, #28a745 0%, #20c997 100%);
    color: white;
    border: none;
    border-radius: 8px;
    padding: 0.75rem 1.5rem;
    font-size: 0.875rem;
    font-weight: 600;
    transition: all 0.2s ease;
    margin-left: 0.5rem;
}

.btn-alert:hover {
    background: linear-gradient(90deg, #218838 0%, #1ea085 100%);
    color: white;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
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

/* Focus states for accessibility */
.job-card:focus-within {
    outline: 2px solid #ffd700;
    outline-offset: 2px;
}

.btn-view:focus,
.btn-save-job:focus,
.btn-alert:focus {
    outline: 2px solid #ffd700;
    outline-offset: 2px;
}

/* Animation delays for staggered loading */
.job-card-wrapper:nth-child(1) { animation-delay: 0.1s; }
.job-card-wrapper:nth-child(2) { animation-delay: 0.2s; }
.job-card-wrapper:nth-child(3) { animation-delay: 0.3s; }
.job-card-wrapper:nth-child(4) { animation-delay: 0.4s; }
.job-card-wrapper:nth-child(5) { animation-delay: 0.5s; }
.job-card-wrapper:nth-child(6) { animation-delay: 0.6s; }
</style>