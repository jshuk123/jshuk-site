<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'config/config.php';
require_once 'includes/ad_renderer.php';
require_once 'includes/subscription_functions.php';
require_once 'includes/category_functions.php';

// Get parameters with proper fallbacks
$category_id = $_GET['category_id'] ?? null;
$location = $_GET['location'] ?? $_SESSION['location'] ?? 'London'; // Default to London if no location
$sort_by = $_GET['sort'] ?? 'premium_first';

// Validate category_id
if (!$category_id || !is_numeric($category_id)) {
    // Redirect to businesses page if no valid category
    header('Location: /businesses.php');
    exit;
}

// Get category data with error handling
try {
    $category = getCategoryData($category_id);
    if (!$category) {
        // Category doesn't exist, redirect to businesses page
        header('Location: /businesses.php');
        exit;
    }
} catch (Exception $e) {
    error_log("Error loading category data: " . $e->getMessage());
    header('Location: /businesses.php');
    exit;
}

// Get businesses for this category with error handling
try {
    $businesses = getCategoryBusinesses($category_id, $location, $sort_by);
} catch (Exception $e) {
    error_log("Error loading category businesses: " . $e->getMessage());
    $businesses = [];
}

// Separate featured and regular businesses
$featured_businesses = [];
$regular_businesses = [];

foreach ($businesses as $business) {
    if ($business['subscription_tier'] === 'premium_plus') {
        $featured_businesses[] = $business;
    } else {
        $regular_businesses[] = $business;
    }
}

// Get testimonials for premium businesses (with error handling)
try {
    $testimonials = getCategoryTestimonials($category_id, 5);
} catch (Exception $e) {
    error_log("Error loading category testimonials: " . $e->getMessage());
    $testimonials = [];
}

// Get featured story (with error handling)
try {
    $featured_story = getFeaturedStory($category_id);
} catch (Exception $e) {
    error_log("Error loading featured story: " . $e->getMessage());
    $featured_story = null;
}

// Get popular locations
$popular_locations = getPopularLocations();

// SEO and page setup
$pageTitle = $category['seo_title'] ?? "Top {$category['name']} Businesses in {$location} | JShuk";
$metaDescription = $category['seo_description'] ?? "Find the best {$category['name']} professionals in {$location}. Browse trusted businesses and read real reviews on JShuk.";
$metaKeywords = "{$category['name']}, {$location}, Jewish businesses, kosher services, local directory";

$page_css = "category.css";
include 'includes/header_main.php';

// Add structured data for SEO
$structuredData = [
    "@context" => "https://schema.org",
    "@type" => "LocalBusiness",
    "name" => "JShuk",
    "url" => "https://jshuk.com/category.php?category_id=" . $category_id,
    "description" => $metaDescription,
    "address" => [
        "@type" => "PostalAddress",
        "addressLocality" => $location,
        "addressCountry" => "GB"
    ]
];
?>

<!-- SEO Meta Tags -->
<meta name="description" content="<?= htmlspecialchars($metaDescription) ?>">
<meta name="keywords" content="<?= htmlspecialchars($metaKeywords) ?>">
<meta property="og:title" content="<?= htmlspecialchars($pageTitle) ?>">
<meta property="og:description" content="<?= htmlspecialchars($metaDescription) ?>">
<meta property="og:type" content="website">
<meta property="og:url" content="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>">

<!-- Structured Data -->
<script type="application/ld+json"><?= json_encode($structuredData) ?></script>

<!-- HERO SECTION: Match homepage design -->
<section class="hero">
  <div class="hero-inner">
    <h1>Top <?= htmlspecialchars($category['name']) ?> Businesses in <?= htmlspecialchars($location) ?></h1>
    <p class="subheading">
      <?= htmlspecialchars($category['short_description'] ?? "Trusted local professionals offering {$category['name']} services in {$location}") ?>
    </p>
    <div class="hero-cta-buttons">
      <a href="/users/post_business.php" class="hero-btn">Add Your Business</a>
      <a href="/businesses.php" class="hero-btn">Browse All Businesses</a>
    </div>
  </div>
</section>

<!-- SEARCH BAR: Use homepage Airbnb-style search -->
<section class="search-banner bg-white py-4 shadow-sm">
  <div class="container">
    <form action="/category.php" method="GET" class="airbnb-search-bar" role="search">
      <input type="hidden" name="category_id" value="<?= $category_id ?>">
      <select name="location" class="form-select" aria-label="Select location">
        <option value="" disabled selected>📍 Select Location</option>
        <?php foreach ($popular_locations as $loc_key => $loc_name): ?>
          <option value="<?= $loc_key ?>" <?= $location === $loc_key ? 'selected' : '' ?>>
            <?= htmlspecialchars($loc_name) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <select name="sort" class="form-select" aria-label="Sort by">
        <option value="premium_first" <?= $sort_by === 'premium_first' ? 'selected' : '' ?>>⭐ Premium First</option>
        <option value="rating" <?= $sort_by === 'rating' ? 'selected' : '' ?>>⭐ Highest Rated</option>
        <option value="most_recent" <?= $sort_by === 'most_recent' ? 'selected' : '' ?>>🆕 Most Recent</option>
        <option value="most_viewed" <?= $sort_by === 'most_viewed' ? 'selected' : '' ?>>👁 Most Popular</option>
      </select>
      <button type="submit" class="btn btn-search" aria-label="Update">
        <i class="fa fa-search"></i>
        <span class="d-none d-md-inline">Update</span>
      </button>
    </form>
  </div>
</section>

<!-- Main Content -->
<main class="main-content-wrapper">
  <!-- Featured Businesses Section -->
  <?php if (!empty($featured_businesses)): ?>
  <section class="featured-businesses-section" data-scroll>
    <div class="container">
      <div class="section-header">
        <h2 class="section-title">
          <i class="fas fa-crown text-warning me-2"></i>Featured <?= htmlspecialchars($category['name']) ?> Businesses
        </h2>
        <p class="section-subtitle">Our most trusted and premium business partners</p>
      </div>
      
      <div class="businesses-slider">
        <div class="slider-container">
          <div class="slider-track">
            <?php foreach (array_slice($featured_businesses, 0, 10) as $business): ?>
              <div class="slider-item">
                <div class="premium-business-card">
                  <div class="business-logo">
                    <img src="<?= htmlspecialchars($business['logo']) ?>" 
                         alt="<?= htmlspecialchars($business['business_name']) ?> Logo" 
                         onerror="this.onerror=null; this.src='/images/jshuk-logo.png';">
                  </div>
                  <div class="business-content">
                    <div class="business-header">
                      <h3 class="business-title"><?= htmlspecialchars($business['business_name']) ?></h3>
                      <span class="badge-elite">Elite</span>
                    </div>
                    <p class="business-category">
                      <i class="fas fa-briefcase"></i>
                      <?= htmlspecialchars($business['category_name']) ?>
                    </p>
                    <?php if (!empty($business['tagline'])): ?>
                      <p class="business-tagline"><?= htmlspecialchars($business['tagline']) ?></p>
                    <?php endif; ?>
                    
                    <!-- Business Hours -->
                    <?php if (!empty($business['business_hours'])): ?>
                      <p class="business-meta">
                        <i class="fas fa-clock me-1"></i><?= htmlspecialchars($business['business_hours']) ?>
                      </p>
                    <?php endif; ?>
                    
                    <!-- Stats -->
                    <div class="business-stats">
                      <?php if ($business['views_count'] > 0): ?>
                        <span class="stat-item">
                          <i class="fas fa-eye"></i><?= number_format($business['views_count']) ?>
                        </span>
                      <?php endif; ?>
                      <?php if (($business['testimonials_count'] + $business['reviews_count']) > 0): ?>
                        <span class="stat-item">
                          <i class="fas fa-star"></i><?= $business['testimonials_count'] + $business['reviews_count'] ?>
                        </span>
                      <?php endif; ?>
                      <?php if ($business['rating']): ?>
                        <span class="stat-item">
                          <i class="fas fa-star text-warning"></i><?= $business['rating'] ?>
                        </span>
                      <?php endif; ?>
                    </div>
                    
                    <div class="business-actions">
                      <?php if (!empty($business['phone'])): ?>
                        <a href="https://wa.me/44<?= preg_replace('/\D/', '', $business['phone']) ?>" target="_blank" class="btn-whatsapp">
                          <i class="fab fa-whatsapp"></i> WhatsApp
                        </a>
                      <?php endif; ?>
                      <a href="/business.php?id=<?= $business['id'] ?>" class="btn-view">
                        <span>View Profile</span>
                        <i class="fas fa-arrow-right"></i>
                      </a>
                    </div>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <!-- All Listings Section -->
  <section class="all-listings-section" data-scroll>
    <div class="container">
      <div class="section-header">
        <h2 class="section-title">All <?= htmlspecialchars($category['name']) ?> Listings</h2>
        <p class="section-subtitle">Discover trusted businesses in your area</p>
      </div>
      
      <?php if (empty($businesses)): ?>
        <div class="empty-state text-center py-5">
          <div class="empty-state-icon mb-3">
            <i class="fas fa-store fa-3x text-muted"></i>
          </div>
          <h3>No Businesses Found</h3>
          <p class="text-muted">Be the first to add your <?= strtolower($category['name']) ?> business to our directory!</p>
          <a href="/users/post_business.php" class="btn-jshuk-primary">Add Your Business</a>
        </div>
      <?php else: ?>
        <div class="businesses-grid">
          <?php foreach ($regular_businesses as $business): ?>
            <div class="business-card-wrapper">
              <div class="new-business-card">
                <div class="business-logo">
                  <img src="<?= htmlspecialchars($business['logo']) ?>" 
                       alt="<?= htmlspecialchars($business['business_name']) ?> Logo" 
                       onerror="this.onerror=null; this.src='/images/jshuk-logo.png';">
                </div>
                <div class="business-info">
                  <div class="business-header">
                    <h3 class="business-title">
                      <a href="/business.php?id=<?= $business['id'] ?>"><?= htmlspecialchars($business['business_name']) ?></a>
                    </h3>
                    <?php if ($business['is_elite']): ?>
                      <span class="badge-elite">Elite</span>
                    <?php elseif ($business['subscription_tier'] === 'premium'): ?>
                      <span class="badge-featured">Premium</span>
                    <?php endif; ?>
                  </div>
                  <p class="business-category">
                    <i class="fas fa-briefcase"></i>
                    <?= htmlspecialchars($business['category_name']) ?>
                  </p>
                  <?php if (!empty($business['tagline'])): ?>
                    <p class="business-tagline"><?= htmlspecialchars($business['tagline']) ?></p>
                  <?php endif; ?>
                  
                  <!-- Business Hours -->
                  <?php if (!empty($business['business_hours'])): ?>
                    <p class="business-meta">
                      <i class="fas fa-clock me-1"></i><?= htmlspecialchars($business['business_hours']) ?>
                    </p>
                  <?php endif; ?>
                  
                  <!-- Stats -->
                  <div class="business-stats">
                    <?php if ($business['views_count'] > 0): ?>
                      <span class="stat-item">
                        <i class="fas fa-eye"></i><?= number_format($business['views_count']) ?>
                      </span>
                    <?php endif; ?>
                    <?php if (($business['testimonials_count'] + $business['reviews_count']) > 0): ?>
                      <span class="stat-item">
                        <i class="fas fa-star"></i><?= $business['testimonials_count'] + $business['reviews_count'] ?>
                      </span>
                    <?php endif; ?>
                    <?php if ($business['rating']): ?>
                      <span class="stat-item">
                        <i class="fas fa-star text-warning"></i><?= $business['rating'] ?>
                      </span>
                    <?php endif; ?>
                  </div>
                  
                  <div class="business-actions">
                    <?php if (!empty($business['phone'])): ?>
                      <a href="https://wa.me/44<?= preg_replace('/\D/', '', $business['phone']) ?>" target="_blank" class="btn-whatsapp">
                        <i class="fab fa-whatsapp"></i> WhatsApp
                      </a>
                    <?php endif; ?>
                    <a href="/business.php?id=<?= $business['id'] ?>" class="btn-view">
                      <span>View Profile</span>
                      <i class="fas fa-arrow-right"></i>
                    </a>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </section>

  <!-- Category Description Section -->
  <?php if (!empty($category['short_description'])): ?>
  <section class="category-description-section" data-scroll>
    <div class="container">
      <div class="category-description-card">
        <h3>About <?= htmlspecialchars($category['name']) ?> Services</h3>
        <p><?= htmlspecialchars($category['short_description']) ?></p>
        <div class="category-actions">
          <a href="/users/post_business.php" class="btn-jshuk-primary">Add Your Business</a>
          <a href="/businesses.php" class="btn-jshuk-outline">Browse All Categories</a>
        </div>
      </div>
    </div>
  </section>
  <?php endif; ?>
</main>

<?php include 'includes/footer_main.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add loading states to business cards
    const businessCards = document.querySelectorAll('.premium-business-card, .new-business-card');
    
    businessCards.forEach(card => {
        card.addEventListener('click', function(e) {
            // Don't add loading if clicking on buttons or links
            if (e.target.tagName === 'A' || e.target.closest('a')) {
                return;
            }
            
            // Add loading state
            this.classList.add('loading');
            
            // Navigate to business page
            const businessLink = this.querySelector('a[href*="business.php"]');
            if (businessLink) {
                window.location.href = businessLink.href;
            }
        });
    });
    
    // Add smooth scrolling for search form
    const searchForm = document.querySelector('.airbnb-search-bar');
    if (searchForm) {
        searchForm.addEventListener('submit', function() {
            // Add a small delay to show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
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
});
</script>

<style>
/* Import homepage styles */
@import url('/css/pages/homepage.css');
@import url('/css/components/search-bar.css');

/* Category-specific styles */
.all-listings-section {
    padding: 3rem 0;
}

.category-description-section {
    padding: 3rem 0;
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
}

.category-description-card {
    background: white;
    border-radius: 16px;
    padding: 2rem;
    box-shadow: 0 4px 16px rgba(0,0,0,0.08);
    text-align: center;
    border: 1px solid #f0f0f0;
}

.category-description-card h3 {
    color: #1a3353;
    font-weight: 700;
    margin-bottom: 1rem;
}

.category-description-card p {
    color: #495057;
    line-height: 1.6;
    margin-bottom: 1.5rem;
    font-size: 1.1rem;
}

.category-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
}

/* Business stats styling */
.business-stats {
    display: flex;
    gap: 1rem;
    margin: 0.5rem 0;
    flex-wrap: wrap;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.875rem;
    color: #6c757d;
}

.stat-item i {
    color: #ffd700;
}

.business-actions {
    display: flex;
    gap: 0.5rem;
    margin-top: 1rem;
}

.btn-whatsapp {
    background: #25d366;
    color: white;
    border: none;
    border-radius: 8px;
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    font-weight: 600;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 0.25rem;
    transition: all 0.2s ease;
}

.btn-whatsapp:hover {
    background: #128c7e;
    color: white;
    transform: translateY(-1px);
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
    gap: 0.25rem;
    transition: all 0.2s ease;
}

.btn-view:hover {
    background: linear-gradient(90deg, #ffd700 0%, #ffcc00 100%);
    color: #1a3353;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(255, 215, 0, 0.3);
}

/* Mobile optimizations */
@media (max-width: 768px) {
    .category-actions {
        flex-direction: column;
        align-items: center;
    }
    
    .business-actions {
        flex-direction: column;
    }
    
    .btn-whatsapp,
    .btn-view {
        justify-content: center;
        width: 100%;
    }
    
    .business-stats {
        gap: 0.75rem;
    }
}

/* Loading states */
.premium-business-card.loading,
.new-business-card.loading {
    opacity: 0.7;
    pointer-events: none;
}

.premium-business-card.loading::after,
.new-business-card.loading::after {
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

/* Focus states for accessibility */
.premium-business-card:focus-within,
.new-business-card:focus-within {
    outline: 2px solid #ffd700;
    outline-offset: 2px;
}

.btn-whatsapp:focus,
.btn-view:focus {
    outline: 2px solid #ffd700;
    outline-offset: 2px;
}
</style> 