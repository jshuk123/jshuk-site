<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'config/config.php';
require_once 'includes/ad_renderer.php';
require_once 'includes/subscription_functions.php';

/**
 * Helper function to safely get business logo URL with fallback
 */
function getBusinessLogoUrl($file_path, $business_name = '') {
    $default_logo = '/images/jshuk-logo.png';
    
    if (empty($file_path)) {
        return $default_logo;
    }
    
    // Check if it's already a full URL
    if (strpos($file_path, 'http') === 0) {
        return $file_path;
    }
    
    // Check if it's already a relative path starting with /
    if (strpos($file_path, '/') === 0) {
        return $file_path;
    }
    
    // It's a relative path, prepend uploads directory
    return '/uploads/' . $file_path;
}

$page_css = "businesses.css";
include 'includes/header_main.php';

// Get filter parameters
$category_filter = $_GET['category'] ?? '';
$sort_by = $_GET['sort'] ?? 'premium_first';
$search_query = $_GET['search'] ?? '';
$location_filter = $_GET['location'] ?? '';

// Get user's location from session or default
$user_location = $_SESSION['location'] ?? 'Manchester';

// Build the main query with enhanced data
$query = "SELECT b.*, c.name as category_name, u.subscription_tier,
          (SELECT COUNT(*) FROM testimonials t WHERE t.business_id = b.id AND t.is_approved = 1) as testimonials_count,
          (SELECT COUNT(*) FROM reviews r WHERE r.business_id = b.id AND r.is_approved = 1) as reviews_count
          FROM businesses b 
          LEFT JOIN business_categories c ON b.category_id = c.id 
          LEFT JOIN users u ON b.user_id = u.id
          WHERE b.status = 'active'";

$params = [];

// Apply category filter
if (!empty($category_filter)) {
    $query .= " AND b.category_id = ?";
    $params[] = $category_filter;
}

// Apply location filter
if (!empty($location_filter) && $location_filter !== 'All') {
    $query .= " AND b.address LIKE ?";
    $params[] = "%$location_filter%";
}

// Apply search filter
if (!empty($search_query)) {
    $query .= " AND (b.business_name LIKE ? OR b.description LIKE ? OR c.name LIKE ?)";
    $search_param = "%$search_query%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

// Apply sorting
switch ($sort_by) {
    case 'most_viewed':
        $query .= " ORDER BY b.views_count DESC, b.created_at DESC";
        break;
    case 'newest':
        $query .= " ORDER BY b.created_at DESC";
        break;
    case 'premium_only':
        $query .= " AND u.subscription_tier IN ('premium', 'premium_plus')";
        $query .= " ORDER BY 
            CASE u.subscription_tier 
                WHEN 'premium_plus' THEN 1 
                WHEN 'premium' THEN 2 
                ELSE 3 
            END,
            b.created_at DESC";
        break;
    default: // premium_first
        $query .= " ORDER BY 
            CASE u.subscription_tier 
                WHEN 'premium_plus' THEN 1 
                WHEN 'premium' THEN 2 
                ELSE 3 
            END,
            b.created_at DESC";
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$businesses = $stmt->fetchAll();

// Separate elite and regular businesses
$elite_businesses = [];
$regular_businesses = [];

foreach ($businesses as $biz) {
    if ($biz['subscription_tier'] === 'premium_plus') {
        $elite_businesses[] = $biz;
    } else {
        $regular_businesses[] = $biz;
    }
}

// Get categories for filter dropdown with enhanced data
$categories_stmt = $pdo->prepare("
    SELECT c.id, c.name, c.icon, 
           (SELECT COUNT(*) FROM businesses b WHERE b.category_id = c.id AND b.status = 'active') as total_listings,
           (SELECT COUNT(*) FROM businesses b WHERE b.category_id = c.id AND b.status = 'active' AND b.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)) as new_this_week
    FROM business_categories c 
    ORDER BY c.name
");
$categories_stmt->execute();
$categories = $categories_stmt->fetchAll();

// Get recently added businesses for the top section
$recent_stmt = $pdo->prepare("
    SELECT b.*, c.name as category_name, c.icon 
    FROM businesses b 
    LEFT JOIN business_categories c ON b.category_id = c.id 
    WHERE b.status = 'active' 
    ORDER BY b.created_at DESC 
    LIMIT 6
");
$recent_stmt->execute();
$recent_businesses = $recent_stmt->fetchAll();

// Get trending businesses (most viewed this week)
$trending_stmt = $pdo->prepare("
    SELECT b.*, c.name as category_name, c.icon, b.views_count
    FROM businesses b 
    LEFT JOIN business_categories c ON b.category_id = c.id 
    WHERE b.status = 'active' 
    ORDER BY b.views_count DESC, b.created_at DESC 
    LIMIT 4
");
$trending_stmt->execute();
$trending_businesses = $trending_stmt->fetchAll();

// Get top-rated premium businesses
$top_stmt = $pdo->prepare("
    SELECT b.*, c.name as category_name, c.icon, AVG(r.rating) as avg_rating 
    FROM businesses b 
    LEFT JOIN business_categories c ON b.category_id = c.id 
    LEFT JOIN reviews r ON b.id = r.business_id 
    WHERE b.status = 'active' AND b.subscription_tier IN ('premium', 'premium_plus') 
    GROUP BY b.id 
    ORDER BY avg_rating DESC, b.created_at DESC 
    LIMIT 4
");
$top_stmt->execute();
$top_businesses = $top_stmt->fetchAll();

// Popular locations
$popular_locations = [
    'Manchester' => 'Manchester',
    'London' => 'London', 
    'Leeds' => 'Leeds',
    'Liverpool' => 'Liverpool',
    'Birmingham' => 'Birmingham',
    'Glasgow' => 'Glasgow',
    'Edinburgh' => 'Edinburgh',
    'Cardiff' => 'Cardiff',
    'Bristol' => 'Bristol',
    'Newcastle' => 'Newcastle'
];

// Category icons mapping
$category_icons = [
    'Restaurants' => 'fas fa-utensils',
    'Catering' => 'fas fa-birthday-cake',
    'Kosher Food' => 'fas fa-star-of-david',
    'Jewish Services' => 'fas fa-synagogue',
    'Education' => 'fas fa-graduation-cap',
    'Healthcare' => 'fas fa-heartbeat',
    'Professional Services' => 'fas fa-briefcase',
    'Retail' => 'fas fa-shopping-bag',
    'Entertainment' => 'fas fa-music',
    'Travel' => 'fas fa-plane',
    'Technology' => 'fas fa-laptop',
    'Finance' => 'fas fa-chart-line',
    'Legal' => 'fas fa-balance-scale',
    'Real Estate' => 'fas fa-home',
    'Automotive' => 'fas fa-car',
    'Beauty & Wellness' => 'fas fa-spa',
    'Fitness' => 'fas fa-dumbbell',
    'Events' => 'fas fa-calendar-alt',
    'Charity' => 'fas fa-hands-helping',
    'Media' => 'fas fa-newspaper'
];

// Fetch main image for each business
$img_stmt = $pdo->prepare("SELECT file_path FROM business_images WHERE business_id = ? AND sort_order = 0 LIMIT 1");
foreach ($businesses as &$biz) {
    $img_stmt->execute([$biz['id']]);
    $image_result = $img_stmt->fetch();
    $raw_path = $image_result['file_path'] ?? '';
    if ($raw_path && strpos($raw_path, '/public_html') !== false) {
        // Remove everything up to and including /public_html
        $web_path = substr($raw_path, strpos($raw_path, '/public_html') + strlen('/public_html'));
    } else {
        $web_path = $raw_path;
    }
    $biz['logo'] = getBusinessLogoUrl($web_path, $biz['business_name'] ?? '');
    if (empty($biz['logo'])) {
        $biz['logo'] = '/images/jshuk-logo.png';
    }
    // Parse contact info
    $contact_info = json_decode($biz['contact_info'] ?? '{}', true);
    $biz['phone'] = $contact_info['phone'] ?? '';
    $biz['email'] = $contact_info['email'] ?? '';
    // Get business hours from opening_hours JSON
    $opening_hours = json_decode($biz['opening_hours'] ?? '{}', true);
    $biz['business_hours'] = '';
    if (!empty($opening_hours['monday']['open']) && !empty($opening_hours['monday']['close'])) {
        $biz['business_hours'] = 'Mon-Fri ' . $opening_hours['monday']['open'] . '-' . $opening_hours['monday']['close'];
    }
    // Get tagline (using description as fallback)
    $biz['tagline'] = !empty($biz['description']) ? substr($biz['description'], 0, 100) . '...' : '';
    // Determine if business is elite
    $biz['is_elite'] = $biz['subscription_tier'] === 'premium_plus';
    $biz['is_pinned'] = $biz['is_featured'] && $biz['subscription_tier'] !== 'basic';
}
unset($biz);

$pageTitle = "Explore Jewish Businesses";
?>

<!-- HERO SECTION: Enhanced with location awareness -->
<section class="hero">
  <div class="hero-inner">
    <h1>Browse Over 500+ Local Jewish Businesses</h1>
    <p class="subheading">
      <?php if (!empty($location_filter) && $location_filter !== 'All'): ?>
        📍 Showing listings near <strong><?= htmlspecialchars($location_filter) ?></strong>
      <?php else: ?>
        Refine your search by category, city, or keyword. Trusted by 1,000+ families across the UK.
      <?php endif; ?>
    </p>
  </div>
</section>

<!-- ENHANCED FILTER BAR: With location integration -->
<section class="search-banner bg-white py-4 shadow-sm directory-filters">
  <div class="container">
    <form action="/businesses.php" method="GET" class="airbnb-search-bar d-flex flex-wrap align-items-center gap-2" role="search">
      <!-- Location Filter -->
      <select name="location" class="form-select" aria-label="Select location">
        <option value="All" <?= $location_filter === 'All' || empty($location_filter) ? 'selected' : '' ?>>📍 All Locations</option>
        <?php foreach ($popular_locations as $key => $value): ?>
          <option value="<?= $key ?>" <?= $location_filter === $key ? 'selected' : '' ?>><?= $value ?></option>
        <?php endforeach; ?>
      </select>
      
      <!-- Category Filter -->
      <select name="category" class="form-select" aria-label="Select category">
        <option value="" disabled selected>🗂 Select a Category</option>
        <?php if (!empty($categories)): ?>
          <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat['id'] ?>" <?= $category_filter == $cat['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($cat['name']) ?> (<?= $cat['total_listings'] ?>)
            </option>
          <?php endforeach; ?>
        <?php endif; ?>
      </select>
      
      <!-- Sort Options -->
      <select name="sort" class="form-select" aria-label="Sort by">
        <option value="premium_first" <?= $sort_by === 'premium_first' ? 'selected' : '' ?>>⭐ Premium First</option>
        <option value="most_viewed" <?= $sort_by === 'most_viewed' ? 'selected' : '' ?>>🔥 Most Viewed</option>
        <option value="newest" <?= $sort_by === 'newest' ? 'selected' : '' ?>>🆕 Newest</option>
        <option value="premium_only" <?= $sort_by === 'premium_only' ? 'selected' : '' ?>>👑 Premium Only</option>
      </select>
      
      <!-- Search Input -->
      <div class="input-group search-group">
        <span class="input-group-text" id="search-icon">🔍</span>
        <input type="text" name="search" class="form-control" placeholder="Search businesses..." value="<?= htmlspecialchars($search_query) ?>" aria-label="Search businesses" aria-describedby="search-icon" />
      </div>
      
      <!-- Action Buttons -->
      <button type="submit" class="btn btn-search" aria-label="Search">
        <i class="fa fa-search"></i>
        <span class="d-none d-md-inline">Search</span>
      </button>
      <a href="/businesses.php" class="btn btn-outline-secondary ms-2">Reset Filters</a>
      <?php if (isset($_SESSION['user_id'])): ?>
        <button type="button" class="btn btn-outline-warning ms-2" id="saveFilterBtn" title="Save this filter"><i class="fas fa-bookmark"></i></button>
      <?php endif; ?>
    </form>
    
    <!-- Saved Filters -->
    <?php if (isset($_SESSION['user_id']) && !empty($_SESSION['saved_filters'])): ?>
      <div class="mt-2">
        <label for="savedFilters" class="form-label small mb-1">Saved Filters:</label>
        <select id="savedFilters" class="form-select form-select-sm" style="max-width:250px;display:inline-block;">
          <option value="">Select a saved filter...</option>
          <?php foreach ($_SESSION['saved_filters'] as $i => $filter): ?>
            <option value="<?= htmlspecialchars($filter) ?>">Filter #<?= $i+1 ?></option>
          <?php endforeach; ?>
        </select>
        <button type="button" class="btn btn-link btn-sm" id="applySavedFilter">Apply</button>
      </div>
    <?php endif; ?>
  </div>
</section>

<!-- CALL-TO-ACTION BANNER: Encourage business owners -->
<section class="cta-banner bg-gradient-primary text-white py-3">
  <div class="container text-center">
    <div class="d-flex align-items-center justify-content-center gap-3">
      <i class="fas fa-hand-wave fa-2x"></i>
      <div>
        <strong>Not listed yet?</strong> Post your business now — it's free!
        <span class="ms-2 badge bg-warning text-dark">Join 500+ businesses</span>
      </div>
      <a href="/auth/register.php" class="btn btn-warning btn-sm">Add Your Business</a>
    </div>
  </div>
</section>

<!-- MAIN CONTENT PANEL: Reordered for better impact -->
<div class="main-content-panel py-5">
  <div class="container-fluid" style="max-width:1400px;">
    <div class="row justify-content-center">
      <div class="col-12 col-lg-10">
        
        <!-- 🔥 TRENDING SECTION: Moved to top for impact -->
        <?php if (!empty($trending_businesses)): ?>
        <div class="trending-section mb-5">
          <h3 class="section-heading">
            <i class="fas fa-fire text-danger me-2"></i>🔥 Trending This Week
          </h3>
          <div class="trending-listings row row-cols-1 row-cols-md-2 row-cols-lg-4 g-3">
            <?php foreach ($trending_businesses as $biz): ?>
              <div class="col">
                <div class="trending-business-card card h-100 border-danger">
                  <div class="card-body d-flex align-items-center gap-3">
                    <img src="<?= htmlspecialchars(getBusinessLogoUrl($biz['logo'] ?? '', $biz['business_name'] ?? '')) ?>" 
                         alt="<?= htmlspecialchars($biz['business_name'] ?? '') ?> Logo" 
                         class="trending-logo rounded" style="width:48px;height:48px;object-fit:cover;">
                    <div class="flex-grow-1">
                      <h6 class="mb-1">
                        <a href="/business.php?id=<?= $biz['id'] ?>" class="text-decoration-none">
                          <?= htmlspecialchars($biz['business_name'] ?? '') ?>
                        </a>
                      </h6>
                      <div class="text-muted small"><?= htmlspecialchars($biz['category_name'] ?? '') ?></div>
                      <div class="text-danger small">
                        <i class="fas fa-eye"></i> <?= number_format($biz['views_count'] ?? 0) ?> views
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>

        <!-- RECENTLY ADDED SECTION: Moved up for better visibility -->
        <div class="recently-added-section mb-5">
          <h3 class="section-heading">
            <i class="fas fa-clock text-primary me-2"></i>🆕 Recently Added
          </h3>
          <div class="recently-added-listings row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
            <?php foreach ($recent_businesses as $biz): ?>
              <div class="col">
                <div class="recent-business-card card h-100">
                  <div class="card-body d-flex align-items-center gap-3">
                    <img src="<?= htmlspecialchars(getBusinessLogoUrl($biz['logo'] ?? '', $biz['business_name'] ?? '')) ?>" 
                         alt="<?= htmlspecialchars($biz['business_name'] ?? '') ?> Logo" 
                         class="recent-logo rounded" style="width:48px;height:48px;object-fit:cover;">
                    <div class="flex-grow-1">
                      <h5 class="mb-1">
                        <a href="/business.php?id=<?= $biz['id'] ?>" class="text-decoration-none">
                          <?= htmlspecialchars($biz['business_name'] ?? '') ?>
                        </a>
                      </h5>
                      <div class="text-muted small">in <?= htmlspecialchars($biz['category_name'] ?? '') ?></div>
                      <div class="text-success small">
                        <i class="fas fa-plus-circle"></i> Added recently
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- TOP-RATED PREMIUM SECTION -->
        <div class="top-rated-section mb-5">
          <h3 class="section-heading">
            <i class="fas fa-star text-warning me-2"></i>⭐ Top-Rated Premium Listings
          </h3>
          <div class="top-rated-listings row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4">
            <?php foreach ($top_businesses as $biz): ?>
              <div class="col">
                <div class="top-business-card card h-100 border-warning">
                  <div class="card-body d-flex align-items-center gap-3">
                    <img src="<?= htmlspecialchars(getBusinessLogoUrl($biz['logo'] ?? '', $biz['business_name'] ?? '')) ?>" 
                         alt="<?= htmlspecialchars($biz['business_name'] ?? '') ?> Logo" 
                         class="recent-logo rounded" style="width:48px;height:48px;object-fit:cover;">
                    <div class="flex-grow-1">
                      <h6 class="mb-1">
                        <a href="/business.php?id=<?= $biz['id'] ?>" class="text-decoration-none">
                          <?= htmlspecialchars($biz['business_name'] ?? '') ?>
                        </a>
                      </h6>
                      <div class="text-muted small"><?= htmlspecialchars($biz['category_name'] ?? '') ?></div>
                      <div class="text-warning small">
                        <i class="fas fa-star"></i> <?= $biz['avg_rating'] ? number_format($biz['avg_rating'],1) : 'N/A' ?>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- ENHANCED CATEGORY GRID: With better visual design -->
        <div class="category-section my-5">
          <h3 class="section-heading mb-4">
            <i class="fas fa-th-large text-info me-2"></i>🗂 Browse by Category
          </h3>
          
          <!-- Category description -->
          <p class="text-muted mb-4">Discover businesses organized by category. Each category shows active listings and recent additions.</p>
          
          <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
            <?php foreach ($categories as $i => $cat):
              // Get category icon
              $icon = $category_icons[$cat['name']] ?? 'fas fa-briefcase';
              // Visual variation with staggered backgrounds
              $bg_class = $i % 2 === 0 ? 'bg-white' : 'bg-light';
              $border_class = $i % 3 === 0 ? 'border-primary' : ($i % 3 === 1 ? 'border-warning' : 'border-info');
            ?>
              <div class="col">
                <a href="/businesses.php?category=<?= $cat['id'] ?>" 
                   class="category-card-enhanced card <?= $bg_class ?> h-100 d-flex flex-row align-items-center p-3 text-decoration-none shadow-sm border-start <?= $border_class ?> border-start-4"
                   data-category="<?= htmlspecialchars($cat['name']) ?>"
                   data-listings="<?= $cat['total_listings'] ?>"
                   data-new="<?= $cat['new_this_week'] ?>">
                  <div class="icon-circle me-3 flex-shrink-0" 
                       style="background:<?= $i%2===0?'#ffd700':'#1a3353' ?>;color:<?= $i%2===0?'#1a3353':'#ffd700' ?>;">
                    <i class="<?= $icon ?> fa-lg"></i>
                  </div>
                  <div class="flex-grow-1">
                    <h5 class="mb-1 category-name"><?= htmlspecialchars($cat['name']) ?></h5>
                    <div class="category-stats small text-muted">
                      <?php if ($cat['total_listings'] > 0): ?>
                        <span class="text-success"><?= $cat['total_listings'] ?> active listings</span>
                        <?php if ($cat['new_this_week'] > 0): ?>
                          <span class="text-primary ms-2">• <?= $cat['new_this_week'] ?> new this week</span>
                        <?php endif; ?>
                      <?php else: ?>
                        <span class="text-muted">Coming soon</span>
                      <?php endif; ?>
                    </div>
                  </div>
                  <div class="category-arrow text-muted">
                    <i class="fas fa-chevron-right"></i>
                  </div>
                </a>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- TESTIMONIAL SECTION: Moved to side -->
        <div class="row">
          <div class="col-12 col-xl-4">
            <div class="testimonial-block card h-100 bg-light border-0 shadow-sm p-4">
              <div class="testimonial-quote mb-2"><i class="fas fa-quote-left fa-2x text-primary"></i></div>
              <blockquote class="blockquote mb-0">
                <p>"JShuk helped us reach hundreds of new customers in just a few months. The directory is a game-changer for Jewish businesses!"</p>
                <footer class="blockquote-footer mt-2">Leah, <cite title="Source Title">Kosher Catering Owner</cite></footer>
              </blockquote>
              
              <!-- Additional CTA under testimonial -->
              <div class="mt-3 pt-3 border-top">
                <p class="small text-muted mb-2">Join 500+ businesses already listed</p>
                <a href="/auth/register.php" class="btn btn-primary btn-sm w-100">
                  <i class="fas fa-plus-circle me-1"></i>Add Your Business
                </a>
              </div>
            </div>
          </div>
        </div>

        <!-- All Listings Section -->
        <section class="new-businesses-section mt-5" data-scroll>
          <div class="section-header">
            <h2 class="section-title section-heading">All Listings</h2>
            <p class="section-subtitle">Discover trusted businesses in your community</p>
          </div>
          
          <?php if (empty($businesses)): ?>
            <div class="empty-state text-center py-5">
              <div class="empty-state-icon mb-3">
                <i class="fas fa-store fa-3x text-muted"></i>
              </div>
              <h3>No Businesses Found</h3>
              <p class="text-muted">Be the first to add your business to our directory!</p>
              <a href="/users/post_business.php" class="btn-jshuk-primary">Add Your Business</a>
            </div>
          <?php else: ?>
            <div class="businesses-grid">
              <?php foreach ($regular_businesses as $business): ?>
                <div class="business-card-wrapper">
                  <div class="new-business-card">
                    <div class="business-logo">
                      <img src="<?= htmlspecialchars($business['logo'] ?? '') ?>" 
                           alt="<?= htmlspecialchars($business['business_name'] ?? '') ?> Logo" 
                           onerror="this.onerror=null; this.src='/images/jshuk-logo.png';">
                    </div>
                    <div class="business-info">
                      <div class="business-header">
                        <h3 class="business-title">
                          <a href="/business.php?id=<?= $business['id'] ?>"><?= htmlspecialchars($business['business_name'] ?? '') ?></a>
                        </h3>
                        <?php if ($business['is_elite']): ?>
                          <span class="badge-elite">Elite</span>
                        <?php elseif ($business['subscription_tier'] === 'premium'): ?>
                          <span class="badge-featured">Premium</span>
                        <?php endif; ?>
                      </div>
                      <p class="business-category">
                        <i class="fas fa-briefcase"></i>
                        <?= htmlspecialchars($business['category_name'] ?? '') ?>
                      </p>
                      <?php if (!empty($business['tagline'] ?? '')): ?>
                        <p class="business-tagline"><?= htmlspecialchars($business['tagline'] ?? '') ?></p>
                      <?php endif; ?>
                      
                      <!-- Business Hours -->
                      <?php if (!empty($business['business_hours'] ?? '')): ?>
                        <p class="business-meta">
                          <i class="fas fa-clock me-1"></i><?= htmlspecialchars($business['business_hours'] ?? '') ?>
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
                      </div>
                      
                      <div class="business-actions">
                        <?php if (!empty($business['phone'] ?? '')): ?>
                          <a href="https://wa.me/44<?= preg_replace('/\D/', '', $business['phone'] ?? '') ?>" target="_blank" class="btn-whatsapp">
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
        </section>
      </div>
    </div>
  </div>
</div>

<!-- Main Content Wrapper -->
<div class="main-content-wrapper">

<!-- Elite Businesses Section -->
<?php if (!empty($elite_businesses)): ?>
<section class="featured-businesses-section" data-scroll>
  <div class="container">
    <div class="section-header">
      <h2 class="section-title">
        <i class="fas fa-crown text-warning me-2"></i>👑 Elite Businesses
      </h2>
      <p class="section-subtitle">Our most trusted and premium business partners</p>
    </div>
    
    <div class="businesses-slider">
      <div class="slider-container">
        <div class="slider-track">
          <?php foreach ($elite_businesses as $business): ?>
            <div class="slider-item">
              <div class="premium-business-card">
                <div class="business-logo">
                  <img src="<?= htmlspecialchars($business['logo'] ?? '') ?>" 
                       alt="<?= htmlspecialchars($business['business_name'] ?? '') ?> Logo" 
                       onerror="this.onerror=null; this.src='/images/jshuk-logo.png';">
                </div>
                <div class="business-content">
                  <div class="business-header">
                    <h3 class="business-title"><?= htmlspecialchars($business['business_name'] ?? '') ?></h3>
                    <span class="badge-elite">Elite</span>
                  </div>
                  <p class="business-category">
                    <i class="fas fa-briefcase"></i>
                    <?= htmlspecialchars($business['category_name'] ?? '') ?>
                  </p>
                  <?php if (!empty($business['tagline'] ?? '')): ?>
                    <p class="business-tagline"><?= htmlspecialchars($business['tagline'] ?? '') ?></p>
                  <?php endif; ?>
                  
                  <!-- Business Hours -->
                  <?php if (!empty($business['business_hours'] ?? '')): ?>
                    <p class="business-meta">
                      <i class="fas fa-clock me-1"></i><?= htmlspecialchars($business['business_hours'] ?? '') ?>
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
                  </div>
                  
                  <div class="business-actions">
                    <?php if (!empty($business['phone'] ?? '')): ?>
                      <a href="https://wa.me/44<?= preg_replace('/\D/', '', $business['phone'] ?? '') ?>" target="_blank" class="btn-whatsapp">
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

<!-- Popular Categories Section -->
<section class="popular-categories-section" data-scroll>
  <div class="container">
    <div class="section-header">
      <h2 class="section-title">Popular Categories</h2>
      <p class="section-subtitle">Browse businesses by category</p>
    </div>
    
    <div class="categories-scroll-wrapper">
      <div class="category-scroll">
        <?php foreach ($categories as $cat): ?>
          <a href="/businesses.php?category=<?= $cat['id'] ?>" class="category-card">
            <div class="icon-circle">
              <i class="fas fa-briefcase"></i>
            </div>
            <h3 class="category-name"><?= htmlspecialchars($cat['name']) ?></h3>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>

</div> <!-- End .main-content-wrapper -->

<?php include 'includes/footer_main.php'; ?>

<script>
// Enhanced JavaScript for Browse Businesses page
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips for category cards
    initializeCategoryTooltips();
    
    // Add loading states to business cards
    const businessCards = document.querySelectorAll('.premium-business-card, .new-business-card, .trending-business-card, .recent-business-card, .top-business-card');
    
    businessCards.forEach(card => {
        card.addEventListener('click', function(e) {
            // Don't add loading if clicking on buttons or links
            if (e.target.tagName === 'A' || e.target.closest('a') || e.target.tagName === 'BUTTON') {
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
    
    // Enhanced search form with better UX
    const searchForm = document.querySelector('.airbnb-search-bar');
    if (searchForm) {
        searchForm.addEventListener('submit', function() {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                const originalContent = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Searching...';
                submitBtn.disabled = true;
                
                // Re-enable after a delay (in case of errors)
                setTimeout(() => {
                    submitBtn.innerHTML = originalContent;
                    submitBtn.disabled = false;
                }, 5000);
            }
        });
    }
    
    // Real-time search suggestions
    const searchInput = document.querySelector('input[name="search"]');
    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                // Could add AJAX search suggestions here
                console.log('Searching for:', this.value);
            }, 300);
        });
    }
    
    // Enhanced keyboard navigation
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            // Clear filters on escape
            const clearBtn = document.querySelector('a[href="businesses.php"]');
            if (clearBtn) {
                clearBtn.click();
            }
        }
    });
    
    // Smooth scroll animations
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
    
    // Category card hover effects
    const categoryCards = document.querySelectorAll('.category-card-enhanced');
    categoryCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-4px) translateX(4px)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) translateX(0)';
        });
    });
    
    // Location filter enhancement
    const locationSelect = document.querySelector('select[name="location"]');
    if (locationSelect) {
        locationSelect.addEventListener('change', function() {
            // Update hero text based on location
            const heroSubheading = document.querySelector('.hero .subheading');
            if (heroSubheading && this.value !== 'All') {
                heroSubheading.innerHTML = `📍 Showing listings near <strong>${this.value}</strong>`;
            } else if (heroSubheading) {
                heroSubheading.innerHTML = 'Refine your search by category, city, or keyword. Trusted by 1,000+ families across the UK.';
            }
        });
    }
    
    // CTA banner interaction
    const ctaBanner = document.querySelector('.cta-banner');
    if (ctaBanner) {
        ctaBanner.addEventListener('click', function(e) {
            if (e.target.tagName === 'A' || e.target.closest('a')) {
                // Track CTA click
                console.log('CTA clicked: Add Your Business');
            }
        });
    }
    
    // Initialize saved filters functionality
    initializeSavedFilters();
});

// Category tooltips functionality
function initializeCategoryTooltips() {
    const categoryCards = document.querySelectorAll('.category-card-enhanced');
    
    categoryCards.forEach(card => {
        const categoryName = card.dataset.category;
        const listings = card.dataset.listings;
        const newThisWeek = card.dataset.new;
        
        // Create tooltip content
        let tooltipContent = `<strong>${categoryName}</strong><br>`;
        if (listings > 0) {
            tooltipContent += `${listings} active listings`;
            if (newThisWeek > 0) {
                tooltipContent += `<br>${newThisWeek} new this week`;
            }
        } else {
            tooltipContent += 'Coming soon';
        }
        
        // Add tooltip using Tippy.js if available
        if (window.tippy) {
            tippy(card, {
                content: tooltipContent,
                theme: 'jshuk-light',
                placement: 'top',
                arrow: true,
                delay: [200, 0],
                duration: [200, 150],
                maxWidth: 200,
                allowHTML: true
            });
        }
    });
}

// Saved filters functionality
function initializeSavedFilters() {
    const saveFilterBtn = document.getElementById('saveFilterBtn');
    const applySavedFilter = document.getElementById('applySavedFilter');
    const savedFiltersSelect = document.getElementById('savedFilters');
    
    if (saveFilterBtn) {
        saveFilterBtn.addEventListener('click', function() {
            const params = new URLSearchParams(window.location.search);
            const filterString = params.toString();
            
            if (filterString) {
                // Save to session storage for demo (in real app, save to server)
                const savedFilters = JSON.parse(sessionStorage.getItem('savedFilters') || '[]');
                const filterName = `Filter ${savedFilters.length + 1}`;
                savedFilters.push({ name: filterName, params: filterString });
                sessionStorage.setItem('savedFilters', JSON.stringify(savedFilters));
                
                // Show success message
                showNotification('Filter saved successfully!', 'success');
                
                // Update saved filters dropdown
                updateSavedFiltersDropdown();
            } else {
                showNotification('No filters to save', 'warning');
            }
        });
    }
    
    if (applySavedFilter && savedFiltersSelect) {
        applySavedFilter.addEventListener('click', function() {
            const selectedFilter = savedFiltersSelect.value;
            if (selectedFilter) {
                const savedFilters = JSON.parse(sessionStorage.getItem('savedFilters') || '[]');
                const filter = savedFilters.find(f => f.name === selectedFilter);
                if (filter) {
                    window.location.search = filter.params;
                }
            }
        });
    }
    
    // Initialize saved filters dropdown
    updateSavedFiltersDropdown();
}

function updateSavedFiltersDropdown() {
    const savedFiltersSelect = document.getElementById('savedFilters');
    if (savedFiltersSelect) {
        const savedFilters = JSON.parse(sessionStorage.getItem('savedFilters') || '[]');
        
        // Clear existing options except the first one
        while (savedFiltersSelect.children.length > 1) {
            savedFiltersSelect.removeChild(savedFiltersSelect.lastChild);
        }
        
        // Add saved filters
        savedFilters.forEach(filter => {
            const option = document.createElement('option');
            option.value = filter.name;
            option.textContent = filter.name;
            savedFiltersSelect.appendChild(option);
        });
    }
}

// Notification system
function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    notification.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    // Add to page
    document.body.appendChild(notification);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
}

// Geolocation for Nearby Businesses (enhanced)
function haversine(lat1, lon1, lat2, lon2) {
    function toRad(x) { return x * Math.PI / 180; }
    var R = 3958.8; // miles
    var dLat = toRad(lat2-lat1);
    var dLon = toRad(lon2-lon1);
    var a = Math.sin(dLat/2)*Math.sin(dLat/2) + Math.cos(toRad(lat1))*Math.cos(toRad(lat2))*Math.sin(dLon/2)*Math.sin(dLon/2);
    var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    return R * c;
}

// Enhanced geolocation with better error handling
if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(
        function(pos) {
            var userLat = pos.coords.latitude;
            var userLon = pos.coords.longitude;
            
            // Show loading state
            const nearbySection = document.getElementById('nearby-businesses-section');
            if (nearbySection) {
                nearbySection.style.display = 'block';
                nearbySection.innerHTML = '<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i> Finding nearby businesses...</div>';
            }
            
            // Fetch businesses with coordinates
            fetch('/api/businesses_with_coords.php')
                .then(res => res.json())
                .then(data => {
                    var nearby = data.filter(function(biz) {
                        if (!biz.latitude || !biz.longitude) return false;
                        var dist = haversine(userLat, userLon, parseFloat(biz.latitude), parseFloat(biz.longitude));
                        return dist <= 20; // Within 20 miles
                    });
                    
                    if (nearby.length > 0) {
                        var html = '<div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">';
                        nearby.slice(0,6).forEach(function(biz) {
                            html += `
                                <div class="col">
                                    <div class="card h-100">
                                        <div class="card-body d-flex align-items-center gap-3">
                                            <img src="${biz.logo || '/images/jshuk-logo.png'}" 
                                                 alt="${biz.business_name} Logo" 
                                                 class="recent-logo rounded" 
                                                 style="width:48px;height:48px;object-fit:cover;">
                                            <div>
                                                <h5 class="mb-1">
                                                    <a href="/business.php?id=${biz.id}">${biz.business_name}</a>
                                                </h5>
                                                <div class="text-muted small">in ${biz.category_name || ''}</div>
                                                <div class="text-success small">
                                                    <i class="fas fa-map-marker-alt"></i> Nearby
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `;
                        });
                        html += '</div>';
                        
                        if (nearbySection) {
                            nearbySection.innerHTML = `
                                <div class="nearby-section mb-5">
                                    <h3 class="section-heading">
                                        <i class="fas fa-map-marker-alt text-success me-2"></i>📍 Nearby Businesses
                                    </h3>
                                    ${html}
                                </div>
                            `;
                        }
                    } else {
                        if (nearbySection) {
                            nearbySection.style.display = 'none';
                        }
                    }
                })
                .catch(error => {
                    console.error('Error fetching nearby businesses:', error);
                    if (nearbySection) {
                        nearbySection.style.display = 'none';
                    }
                });
        },
        function(error) {
            console.log('Geolocation error:', error);
            // Hide nearby section if geolocation fails
            const nearbySection = document.getElementById('nearby-businesses-section');
            if (nearbySection) {
                nearbySection.style.display = 'none';
            }
        },
        {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 300000 // 5 minutes
        }
    );
}

// Performance optimization: Lazy load images
function lazyLoadImages() {
    const images = document.querySelectorAll('img[data-src]');
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.removeAttribute('data-src');
                imageObserver.unobserve(img);
            }
        });
    });
    
    images.forEach(img => imageObserver.observe(img));
}

// Initialize lazy loading
if ('IntersectionObserver' in window) {
    lazyLoadImages();
}

// Analytics tracking for business interactions
function trackBusinessInteraction(action, businessId, businessName) {
    // Track user interactions for analytics
    if (typeof gtag !== 'undefined') {
        gtag('event', action, {
            'event_category': 'Business Interaction',
            'event_label': businessName,
            'value': businessId
        });
    }
    
    // Track in console for development
    console.log(`Business interaction: ${action} - ${businessName} (ID: ${businessId})`);
}

// Add tracking to business card clicks
document.addEventListener('click', function(e) {
    const businessLink = e.target.closest('a[href*="business.php"]');
    if (businessLink) {
        const businessId = businessLink.href.match(/id=(\d+)/)?.[1];
        const businessName = businessLink.textContent.trim();
        if (businessId && businessName) {
            trackBusinessInteraction('view_business', businessId, businessName);
        }
    }
});
</script>

<style>
/* Import homepage styles */
@import url('/css/pages/homepage.css');
@import url('/css/components/search-bar.css');

/* Additional businesses-specific styles */
.business-stats {
    display: flex;
    gap: 1rem;
    margin: 0.5rem 0;
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

.business-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 0.5rem;
}

.business-title {
    margin: 0;
    font-size: 1.1rem;
    font-weight: 700;
    color: #1a3353;
    line-height: 1.2;
}

.business-title a {
    color: inherit;
    text-decoration: none;
}

.business-title a:hover {
    color: #ffd700;
}

.business-category {
    font-size: 0.875rem;
    color: #6c757d;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.business-category i {
    color: #ffd700;
}

.business-tagline {
    font-size: 0.875rem;
    color: #495057;
    margin-bottom: 0.5rem;
    line-height: 1.4;
}

.business-meta {
    font-size: 0.875rem;
    color: #6c757d;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.business-meta i {
    color: #ffd700;
}

/* Mobile optimizations */
@media (max-width: 768px) {
    .business-actions {
        flex-direction: column;
    }
    
    .btn-whatsapp,
    .btn-view {
        justify-content: center;
        width: 100%;
    }
    
    .business-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
    
    .badge-elite,
    .badge-featured {
        align-self: flex-start;
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
.directory-filters {
  border-bottom: 2px solid #f0f0f0;
  margin-bottom: 2rem;
  background: #f9fafb;
}
.search-group .input-group-text {
  background: #fff;
  border-right: 0;
  font-size: 1.1rem;
}
.search-group .form-control {
  border-left: 0;
}
.btn.btn-outline-secondary {
  border-radius: 8px;
  font-weight: 500;
}
@media (max-width: 768px) {
  .directory-filters {
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
  }
  .airbnb-search-bar {
    flex-direction: column;
    gap: 0.5rem;
  }
}
.dynamic-title { font-size: 1.3rem; font-weight: 600; margin-bottom: 1rem; }
.recent-business-card, .top-business-card { transition: box-shadow 0.2s; }
.recent-business-card:hover, .top-business-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.10); }
.recent-logo { border: 2px solid #eee; background: #fff; }
.category-card-2col { border-radius: 12px; transition: box-shadow 0.2s, background 0.2s; }
.category-card-2col:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.10); background: #f7f7f7 !important; }
.category-name-2col { font-size: 1.1rem; font-weight: 600; color: #1a3353; }
.icon-circle { width: 48px; height: 48px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; }
.testimonial-block { border-radius: 16px; }
@media (max-width: 768px) {
  .dynamic-title { font-size: 1.1rem; }
  .category-card-2col { flex-direction: column !important; align-items: flex-start !important; padding: 1rem !important; }
  .icon-circle { margin-bottom: 0.5rem; }
}
body { background: #e9eef6; }
.main-content-panel { background: #fff; border-radius: 24px; box-shadow: 0 8px 32px rgba(0,0,0,0.08); margin-top: -60px; min-height: 80vh; }
.section-heading { font-size: 2rem; font-weight: 800; color: #1a3353; letter-spacing: -1px; margin-bottom: 1.5rem; }
@media (max-width: 991px) {
  .main-content-panel { border-radius: 0; margin-top: 0; }
  .section-heading { font-size: 1.3rem; }
}
</style>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function() {
  $('select').select2({ width: 'resolve' });
});
</script>