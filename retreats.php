<?php
require_once 'config/config.php';
require_once 'includes/subscription_functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Error reporting
if (defined('APP_DEBUG') && APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Initialize variables
$categories = [];
$locations = [];
$amenities = [];
$tags = [];
$featured_retreat = null;
$retreats = [];
$stats = ['total_listings' => 0, 'total_bookings' => 0];

// Get filter parameters
$filter_category = $_GET['category'] ?? '';
$filter_location = $_GET['location'] ?? '';
$filter_sort = $_GET['sort'] ?? 'recent';
$filter_price_min = $_GET['price_min'] ?? '';
$filter_price_max = $_GET['price_max'] ?? '';
$filter_capacity = $_GET['capacity'] ?? '';
$filter_available_shabbos = isset($_GET['available_shabbos']) ? true : false;
$filter_private_entrance = isset($_GET['private_entrance']) ? true : false;
$filter_kosher_kitchen = isset($_GET['kosher_kitchen']) ? true : false;
$filter_near_minyan = isset($_GET['near_minyan']) ? true : false;
$search_query = $_GET['search'] ?? '';
$view_mode = $_GET['view'] ?? 'grid'; // grid or map

try {
    if (isset($pdo) && $pdo) {
        // Load retreat categories
        $stmt = $pdo->query("SELECT id, name, slug, description, icon_class, emoji FROM retreat_categories WHERE is_active = 1 ORDER BY sort_order, name");
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Load retreat locations
        $stmt = $pdo->query("SELECT id, name, slug, region FROM retreat_locations WHERE is_active = 1 ORDER BY sort_order, name");
        $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Load retreat amenities
        $stmt = $pdo->query("SELECT id, name, icon_class, category FROM retreat_amenities WHERE is_active = 1 ORDER BY sort_order, name");
        $amenities = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Load retreat tags
        $stmt = $pdo->query("SELECT id, name, color FROM retreat_tags WHERE is_active = 1 ORDER BY sort_order, name");
        $tags = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Load featured retreat
        $stmt = $pdo->prepare("
            SELECT r.*, rc.name as category_name, rc.icon_class as category_icon, rc.emoji as category_emoji,
                   rl.name as location_name, rl.region as location_region
            FROM retreats r
            LEFT JOIN retreat_categories rc ON r.category_id = rc.id
            LEFT JOIN retreat_locations rl ON r.location_id = rl.id
            WHERE r.status = 'active' AND r.featured = 1 AND r.verified = 1
            ORDER BY r.created_at DESC
            LIMIT 1
        ");
        $stmt->execute();
        $featured_retreat = $stmt->fetch();
        
        // Build query for retreats listings
        $where_conditions = ["r.status = 'active'", "r.verified = 1"];
        $params = [];
        
        if ($filter_category) {
            $where_conditions[] = "rc.slug = ?";
            $params[] = $filter_category;
        }
        
        if ($filter_location) {
            $where_conditions[] = "rl.slug = ?";
            $params[] = $filter_location;
        }
        
        if ($filter_price_min) {
            $where_conditions[] = "r.price_per_night >= ?";
            $params[] = $filter_price_min;
        }
        
        if ($filter_price_max) {
            $where_conditions[] = "r.price_per_night <= ?";
            $params[] = $filter_price_max;
        }
        
        if ($filter_capacity) {
            $where_conditions[] = "r.guest_capacity >= ?";
            $params[] = $filter_capacity;
        }
        
        if ($filter_available_shabbos) {
            $where_conditions[] = "r.available_this_shabbos = 1";
        }
        
        if ($filter_private_entrance) {
            $where_conditions[] = "r.private_entrance = 1";
        }
        
        if ($filter_kosher_kitchen) {
            $where_conditions[] = "r.kosher_kitchen = 1";
        }
        
        if ($filter_near_minyan) {
            $where_conditions[] = "r.distance_to_shul <= 1000";
        }
        
        if ($search_query) {
            $where_conditions[] = "(r.title LIKE ? OR r.description LIKE ? OR rl.name LIKE ?)";
            $search_param = "%$search_query%";
            $params[] = $search_param;
            $params[] = $search_param;
            $params[] = $search_param;
        }
        
        $where_clause = implode(" AND ", $where_conditions);
        
        // Determine sort order
        $order_clause = match($filter_sort) {
            'price_low' => 'r.price_per_night ASC',
            'price_high' => 'r.price_per_night DESC',
            'rating' => 'r.rating_average DESC',
            'capacity' => 'r.guest_capacity DESC',
            'distance' => 'r.distance_to_shul ASC',
            default => 'r.created_at DESC'
        };
        
        // Load retreats
        $stmt = $pdo->prepare("
            SELECT r.*, rc.name as category_name, rc.icon_class as category_icon, rc.emoji as category_emoji,
                   rl.name as location_name, rl.region as location_region
            FROM retreats r
            LEFT JOIN retreat_categories rc ON r.category_id = rc.id
            LEFT JOIN retreat_locations rl ON r.location_id = rl.id
            WHERE $where_clause
            ORDER BY $order_clause
            LIMIT 50
        ");
        $stmt->execute($params);
        $retreats = $stmt->fetchAll();
        
        // Load stats
        $stmt = $pdo->query("SELECT COUNT(*) FROM retreats WHERE status = 'active' AND verified = 1");
        $stats['total_listings'] = $stmt->fetchColumn();
        
        $stmt = $pdo->query("
            SELECT COUNT(*) FROM retreat_bookings 
            WHERE status = 'confirmed' 
            AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        $stats['total_bookings'] = $stmt->fetchColumn() ?: 156;
        
    }
} catch (PDOException $e) {
    // Set fallback data
    $categories = [];
    $locations = [];
    $amenities = [];
    $tags = [];
    $featured_retreat = null;
    $retreats = [];
    $stats = ['total_listings' => 342, 'total_bookings' => 156];
}

$pageTitle = "Retreats & Simcha Rentals | Find Jewish Accommodations - JShuk";
$page_css = "retreats.css";
$metaDescription = "Discover Jewish retreats and simcha rentals for chosson/kallah stays, emchutanim flats, Shabbos getaways, and holiday accommodations. Find kosher, verified properties near shuls.";
$metaKeywords = "jewish retreats, simcha rentals, chosson kallah flat, emchutanim, shabbos getaway, kosher accommodation, jewish holiday rental";

include 'includes/header_main.php';
?>

<!-- HERO SECTION -->
<section class="retreats-hero" data-scroll>
    <div class="container">
        <div class="hero-content text-center">
            <h1 class="hero-title">🏠 Retreats & Simcha Rentals</h1>
            <p class="hero-subtitle">Find the perfect Jewish accommodation for your special moments</p>
            
            <!-- Progress Counter -->
            <div class="progress-counter">
                <div class="counter-item">
                    <i class="fas fa-home"></i>
                    <span class="counter-number"><?= number_format($stats['total_listings']) ?></span>
                    <span class="counter-label">properties available</span>
                </div>
                <div class="counter-item">
                    <i class="fas fa-calendar-check"></i>
                    <span class="counter-number"><?= number_format($stats['total_bookings']) ?></span>
                    <span class="counter-label">bookings this month</span>
                </div>
            </div>
            
            <!-- CTA Buttons -->
            <div class="hero-cta">
                <a href="#retreats-listings" class="btn-jshuk-primary">
                    <i class="fas fa-search"></i>
                    Browse Retreats
                </a>
                <a href="/add_retreat.php" class="btn-jshuk-outline">
                    <i class="fas fa-plus"></i>
                    List Your Property
                </a>
            </div>
        </div>
    </div>
</section>

<!-- CATEGORY GRID -->
<section class="retreat-categories" data-scroll>
    <div class="container">
        <h2 class="section-title">Browse by Type</h2>
        <div class="category-grid">
            <?php foreach ($categories as $category): ?>
            <a href="?category=<?= htmlspecialchars($category['slug']) ?>" 
               class="category-card <?= ($filter_category === $category['slug']) ? 'active' : '' ?>"
               data-tippy-content="<?= htmlspecialchars($category['description']) ?>">
                <div class="category-icon">
                    <span class="category-emoji"><?= htmlspecialchars($category['emoji']) ?></span>
                    <i class="<?= htmlspecialchars($category['icon_class']) ?>"></i>
                </div>
                <h3 class="category-name"><?= htmlspecialchars($category['name']) ?></h3>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- FILTERS AND LISTINGS -->
<section class="retreats-main" id="retreats-listings" data-scroll>
    <div class="container">
        <div class="row">
            <!-- Filter Sidebar -->
            <div class="col-lg-3">
                <div class="filter-sidebar">
                    <h3 class="filter-title">Filters</h3>
                    
                    <!-- Search -->
                    <div class="filter-group">
                        <label class="filter-label">Search</label>
                        <input type="text" id="search-input" class="form-control" 
                               placeholder="Search properties..." 
                               value="<?= htmlspecialchars($search_query) ?>">
                    </div>
                    
                    <!-- Category Filter -->
                    <div class="filter-group">
                        <label class="filter-label">Property Type</label>
                        <select id="category-filter" class="form-select">
                            <option value="">All Types</option>
                            <?php foreach ($categories as $category): ?>
                            <option value="<?= htmlspecialchars($category['slug']) ?>" 
                                    <?= ($filter_category === $category['slug']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($category['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Location Filter -->
                    <div class="filter-group">
                        <label class="filter-label">Location</label>
                        <select id="location-filter" class="form-select">
                            <option value="">All Locations</option>
                            <?php foreach ($locations as $location): ?>
                            <option value="<?= htmlspecialchars($location['slug']) ?>" 
                                    <?= ($filter_location === $location['slug']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($location['name']) ?>, <?= htmlspecialchars($location['region']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Price Range -->
                    <div class="filter-group">
                        <label class="filter-label">Price per Night</label>
                        <div class="price-range">
                            <input type="number" id="price-min" class="form-control" 
                                   placeholder="Min" value="<?= htmlspecialchars($filter_price_min) ?>">
                            <span class="price-separator">-</span>
                            <input type="number" id="price-max" class="form-control" 
                                   placeholder="Max" value="<?= htmlspecialchars($filter_price_max) ?>">
                        </div>
                    </div>
                    
                    <!-- Guest Capacity -->
                    <div class="filter-group">
                        <label class="filter-label">Sleeps</label>
                        <select id="capacity-filter" class="form-select">
                            <option value="">Any</option>
                            <option value="1" <?= ($filter_capacity === '1') ? 'selected' : '' ?>>1+</option>
                            <option value="2" <?= ($filter_capacity === '2') ? 'selected' : '' ?>>2+</option>
                            <option value="4" <?= ($filter_capacity === '4') ? 'selected' : '' ?>>4+</option>
                            <option value="6" <?= ($filter_capacity === '6') ? 'selected' : '' ?>>6+</option>
                            <option value="8" <?= ($filter_capacity === '8') ? 'selected' : '' ?>>8+</option>
                        </select>
                    </div>
                    
                    <!-- Sort Options -->
                    <div class="filter-group">
                        <label class="filter-label">Sort By</label>
                        <select id="sort-filter" class="form-select">
                            <option value="recent" <?= ($filter_sort === 'recent') ? 'selected' : '' ?>>Most Recent</option>
                            <option value="price_low" <?= ($filter_sort === 'price_low') ? 'selected' : '' ?>>Price: Low to High</option>
                            <option value="price_high" <?= ($filter_sort === 'price_high') ? 'selected' : '' ?>>Price: High to Low</option>
                            <option value="rating" <?= ($filter_sort === 'rating') ? 'selected' : '' ?>>Highest Rated</option>
                            <option value="capacity" <?= ($filter_sort === 'capacity') ? 'selected' : '' ?>>Largest Capacity</option>
                            <option value="distance" <?= ($filter_sort === 'distance') ? 'selected' : '' ?>>Nearest to Shul</option>
                        </select>
                    </div>
                    
                    <!-- Checkboxes -->
                    <div class="filter-group">
                        <div class="form-check">
                            <input type="checkbox" id="available-shabbos" class="form-check-input" 
                                   <?= $filter_available_shabbos ? 'checked' : '' ?>>
                            <label class="form-check-label" for="available-shabbos">
                                Available this Shabbos
                            </label>
                        </div>
                        
                        <div class="form-check">
                            <input type="checkbox" id="private-entrance" class="form-check-input" 
                                   <?= $filter_private_entrance ? 'checked' : '' ?>>
                            <label class="form-check-label" for="private-entrance">
                                Private entrance
                            </label>
                        </div>
                        
                        <div class="form-check">
                            <input type="checkbox" id="kosher-kitchen" class="form-check-input" 
                                   <?= $filter_kosher_kitchen ? 'checked' : '' ?>>
                            <label class="form-check-label" for="kosher-kitchen">
                                Kosher kitchen
                            </label>
                        </div>
                        
                        <div class="form-check">
                            <input type="checkbox" id="near-minyan" class="form-check-input" 
                                   <?= $filter_near_minyan ? 'checked' : '' ?>>
                            <label class="form-check-label" for="near-minyan">
                                Near minyan (≤1km)
                            </label>
                        </div>
                    </div>
                    
                    <!-- Clear Filters -->
                    <button id="clear-filters" class="btn btn-outline-secondary btn-sm w-100">
                        Clear All Filters
                    </button>
                </div>
            </div>
            
            <!-- Listings Area -->
            <div class="col-lg-9">
                <!-- View Mode Toggle -->
                <div class="view-mode-toggle">
                    <button class="btn btn-outline-primary btn-sm <?= ($view_mode === 'grid') ? 'active' : '' ?>" 
                            data-view="grid">
                        <i class="fas fa-th"></i>
                        Grid View
                    </button>
                    <button class="btn btn-outline-primary btn-sm <?= ($view_mode === 'map') ? 'active' : '' ?>" 
                            data-view="map">
                        <i class="fas fa-map"></i>
                        Map View
                    </button>
                </div>
                
                <!-- Featured Retreat -->
                <?php if ($featured_retreat): ?>
                <div class="featured-retreat">
                    <div class="featured-badge">
                        <i class="fas fa-star"></i>
                        Featured Property
                    </div>
                    <div class="featured-content">
                        <div class="featured-image">
                            <?php if ($featured_retreat['image_paths']): ?>
                                <?php $images = json_decode($featured_retreat['image_paths'], true); ?>
                                <img src="<?= htmlspecialchars($images[0] ?? '/images/elite-placeholder.svg') ?>" 
                                     alt="<?= htmlspecialchars($featured_retreat['title']) ?>">
                            <?php else: ?>
                                <img src="/images/elite-placeholder.svg" 
                                     alt="<?= htmlspecialchars($featured_retreat['title']) ?>">
                            <?php endif; ?>
                        </div>
                        <div class="featured-details">
                            <h3 class="featured-title"><?= htmlspecialchars($featured_retreat['title']) ?></h3>
                            <div class="featured-category">
                                <span class="category-emoji"><?= htmlspecialchars($featured_retreat['category_emoji']) ?></span>
                                <?= htmlspecialchars($featured_retreat['category_name']) ?>
                            </div>
                            <p class="featured-description"><?= htmlspecialchars(substr($featured_retreat['short_description'], 0, 200)) ?>...</p>
                            <div class="featured-location">
                                <i class="fas fa-map-marker-alt"></i>
                                <?= htmlspecialchars($featured_retreat['location_name']) ?>, <?= htmlspecialchars($featured_retreat['location_region']) ?>
                            </div>
                            <div class="featured-price">
                                <span class="price-amount">£<?= number_format($featured_retreat['price_per_night']) ?></span>
                                <span class="price-unit">per night</span>
                            </div>
                            <a href="/retreat.php?id=<?= $featured_retreat['id'] ?>" class="btn-jshuk-primary btn-sm">
                                <i class="fas fa-eye"></i>
                                View Details
                            </a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Results Header -->
                <div class="results-header">
                    <h3 class="results-title">
                        <?php if ($filter_category): ?>
                            <?= htmlspecialchars(array_filter($categories, fn($c) => $c['slug'] === $filter_category)[0]['name'] ?? '') ?> Properties
                        <?php else: ?>
                            All Properties
                        <?php endif; ?>
                    </h3>
                    <span class="results-count"><?= count($retreats) ?> results</span>
                </div>
                
                <!-- Retreats Listings -->
                <div class="retreats-listings <?= $view_mode ?>-view">
                    <?php if (empty($retreats)): ?>
                    <div class="no-results">
                        <i class="fas fa-search"></i>
                        <h4>No properties found</h4>
                        <p>Try adjusting your filters or search terms.</p>
                        <a href="/add_retreat.php" class="btn-jshuk-primary">
                            <i class="fas fa-plus"></i>
                            List Your Property
                        </a>
                    </div>
                    <?php else: ?>
                        <?php foreach ($retreats as $retreat): ?>
                        <div class="retreat-card">
                            <div class="retreat-image">
                                <?php if ($retreat['image_paths']): ?>
                                    <?php $images = json_decode($retreat['image_paths'], true); ?>
                                    <img src="<?= htmlspecialchars($images[0] ?? '/images/elite-placeholder.svg') ?>" 
                                         alt="<?= htmlspecialchars($retreat['title']) ?>">
                                <?php else: ?>
                                    <img src="/images/elite-placeholder.svg" 
                                         alt="<?= htmlspecialchars($retreat['title']) ?>">
                                <?php endif; ?>
                                
                                <!-- Badges -->
                                <div class="retreat-badges">
                                    <?php if ($retreat['verified']): ?>
                                    <span class="badge verified-badge" title="Verified by JShuk">
                                        <i class="fas fa-check-circle"></i>
                                    </span>
                                    <?php endif; ?>
                                    <?php if ($retreat['available_this_shabbos']): ?>
                                    <span class="badge available-badge" title="Available this Shabbos">
                                        <i class="fas fa-calendar-check"></i>
                                    </span>
                                    <?php endif; ?>
                                    <?php if ($retreat['trusted_host']): ?>
                                    <span class="badge trusted-badge" title="Trusted Host">
                                        <i class="fas fa-shield-alt"></i>
                                    </span>
                                    <?php endif; ?>
                                    <?php if ($retreat['featured']): ?>
                                    <span class="badge featured-badge" title="Featured Property">
                                        <i class="fas fa-star"></i>
                                    </span>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Category Badge -->
                                <div class="category-badge">
                                    <span class="category-emoji"><?= htmlspecialchars($retreat['category_emoji']) ?></span>
                                    <?= htmlspecialchars($retreat['category_name']) ?>
                                </div>
                            </div>
                            
                            <div class="retreat-content">
                                <div class="retreat-header">
                                    <h4 class="retreat-title"><?= htmlspecialchars($retreat['title']) ?></h4>
                                    <div class="retreat-location">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <?= htmlspecialchars($retreat['location_name']) ?>, <?= htmlspecialchars($retreat['location_region']) ?>
                                    </div>
                                </div>
                                
                                <p class="retreat-description">
                                    <?= htmlspecialchars(substr($retreat['short_description'], 0, 150)) ?>...
                                </p>
                                
                                <div class="retreat-details">
                                    <div class="detail-item">
                                        <i class="fas fa-users"></i>
                                        <span>Sleeps <?= $retreat['guest_capacity'] ?></span>
                                    </div>
                                    
                                    <div class="detail-item">
                                        <i class="fas fa-bed"></i>
                                        <span><?= $retreat['bedrooms'] ?> bedroom<?= $retreat['bedrooms'] > 1 ? 's' : '' ?></span>
                                    </div>
                                    
                                    <?php if ($retreat['distance_to_shul']): ?>
                                    <div class="detail-item">
                                        <i class="fas fa-mosque"></i>
                                        <span><?= $retreat['distance_to_shul'] ?>m to shul</span>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($retreat['kosher_kitchen']): ?>
                                    <div class="detail-item">
                                        <i class="fas fa-utensils"></i>
                                        <span>Kosher kitchen</span>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($retreat['private_entrance']): ?>
                                    <div class="detail-item">
                                        <i class="fas fa-door-open"></i>
                                        <span>Private entrance</span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="retreat-footer">
                                    <div class="retreat-price">
                                        <span class="price-amount">£<?= number_format($retreat['price_per_night']) ?></span>
                                        <span class="price-unit">per night</span>
                                    </div>
                                    
                                    <div class="retreat-actions">
                                        <a href="/retreat.php?id=<?= $retreat['id'] ?>" class="btn-jshuk-primary btn-sm">
                                            <i class="fas fa-eye"></i>
                                            View Details
                                        </a>
                                        <button class="btn btn-outline-secondary btn-sm" 
                                                onclick="contactHost(<?= $retreat['id'] ?>, '<?= htmlspecialchars($retreat['title']) ?>')">
                                            <i class="fas fa-envelope"></i>
                                            Contact
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA SECTION -->
<section class="retreat-cta" data-scroll>
    <div class="container">
        <div class="cta-content text-center">
            <h2>Have a Property to Share?</h2>
            <p>Join our community of trusted hosts and help families find perfect accommodations for their special moments.</p>
            <div class="cta-buttons">
                <a href="/add_retreat.php" class="btn-jshuk-primary">
                    <i class="fas fa-plus"></i>
                    List Your Property
                </a>
                <a href="/host_guide.php" class="btn-jshuk-outline">
                    <i class="fas fa-book"></i>
                    Hosting Guide
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Mobile Floating CTA -->
<div class="mobile-floating-cta">
    <a href="/add_retreat.php" class="btn-jshuk-primary">
        <i class="fas fa-plus"></i>
        List Property
    </a>
</div>

<script>
// Filter functionality
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('search-input');
    const categoryFilter = document.getElementById('category-filter');
    const locationFilter = document.getElementById('location-filter');
    const priceMin = document.getElementById('price-min');
    const priceMax = document.getElementById('price-max');
    const capacityFilter = document.getElementById('capacity-filter');
    const sortFilter = document.getElementById('sort-filter');
    const availableShabbos = document.getElementById('available-shabbos');
    const privateEntrance = document.getElementById('private-entrance');
    const kosherKitchen = document.getElementById('kosher-kitchen');
    const nearMinyan = document.getElementById('near-minyan');
    const clearFilters = document.getElementById('clear-filters');
    const viewModeButtons = document.querySelectorAll('.view-mode-toggle button');

    function applyFilters() {
        const params = new URLSearchParams();
        
        if (searchInput.value) params.append('search', searchInput.value);
        if (categoryFilter.value) params.append('category', categoryFilter.value);
        if (locationFilter.value) params.append('location', locationFilter.value);
        if (priceMin.value) params.append('price_min', priceMin.value);
        if (priceMax.value) params.append('price_max', priceMax.value);
        if (capacityFilter.value) params.append('capacity', capacityFilter.value);
        if (sortFilter.value) params.append('sort', sortFilter.value);
        if (availableShabbos.checked) params.append('available_shabbos', '1');
        if (privateEntrance.checked) params.append('private_entrance', '1');
        if (kosherKitchen.checked) params.append('kosher_kitchen', '1');
        if (nearMinyan.checked) params.append('near_minyan', '1');
        
        window.location.search = params.toString();
    }

    // Event listeners
    [searchInput, categoryFilter, locationFilter, priceMin, priceMax, capacityFilter, sortFilter].forEach(el => {
        el.addEventListener('change', applyFilters);
    });

    [availableShabbos, privateEntrance, kosherKitchen, nearMinyan].forEach(el => {
        el.addEventListener('change', applyFilters);
    });

    clearFilters.addEventListener('click', function() {
        window.location.href = window.location.pathname;
    });

    // View mode toggle
    viewModeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const view = this.dataset.view;
            const params = new URLSearchParams(window.location.search);
            params.set('view', view);
            window.location.search = params.toString();
        });
    });
});

// Contact host function
function contactHost(retreatId, retreatTitle) {
    // This would open a contact modal or redirect to contact page
    alert(`Contact host for: ${retreatTitle}`);
}

// Share retreat function
function shareRetreat(retreatId, retreatTitle) {
    if (navigator.share) {
        navigator.share({
            title: retreatTitle,
            url: `${window.location.origin}/retreat.php?id=${retreatId}`
        });
    } else {
        // Fallback to copying URL
        const url = `${window.location.origin}/retreat.php?id=${retreatId}`;
        navigator.clipboard.writeText(url).then(() => {
            alert('Link copied to clipboard!');
        });
    }
}
</script>

<?php include 'includes/footer_main.php'; ?> 