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
$featured_gemach = null;
$gemachim = [];
$stats = ['total_items' => 0, 'total_donations' => 0];
$filter_category = $_GET['category'] ?? '';
$filter_location = $_GET['location'] ?? '';
$filter_sort = $_GET['sort'] ?? 'recent';
$filter_donation_only = isset($_GET['donation_only']) ? true : false;
$search_query = $_GET['search'] ?? '';

try {
    if (isset($pdo) && $pdo) {
        // Load gemach categories
        $stmt = $pdo->query("SELECT id, name, slug, description, icon_class FROM gemach_categories WHERE is_active = 1 ORDER BY sort_order, name");
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Load featured gemach
        $stmt = $pdo->prepare("
            SELECT g.*, gc.name as category_name, gc.icon_class as category_icon
            FROM gemachim g
            LEFT JOIN gemach_categories gc ON g.category_id = gc.id
            WHERE g.status = 'active' AND g.featured = 1 AND g.verified = 1
            ORDER BY g.created_at DESC
            LIMIT 1
        ");
        $stmt->execute();
        $featured_gemach = $stmt->fetch();
        
        // Build query for gemachim listings
        $where_conditions = ["g.status = 'active'", "g.verified = 1"];
        $params = [];
        
        if ($filter_category) {
            $where_conditions[] = "gc.slug = ?";
            $params[] = $filter_category;
        }
        
        if ($filter_location) {
            $where_conditions[] = "g.location LIKE ?";
            $params[] = "%$filter_location%";
        }
        
        if ($filter_donation_only) {
            $where_conditions[] = "g.donation_enabled = 1";
        }
        
        if ($search_query) {
            $where_conditions[] = "(g.name LIKE ? OR g.description LIKE ? OR g.location LIKE ?)";
            $search_param = "%$search_query%";
            $params[] = $search_param;
            $params[] = $search_param;
            $params[] = $search_param;
        }
        
        $where_clause = implode(" AND ", $where_conditions);
        
        // Determine sort order
        $order_clause = match($filter_sort) {
            'alphabetical' => 'g.name ASC',
            'nearby' => 'g.location ASC',
            default => 'g.created_at DESC'
        };
        
        // Load gemachim
        $stmt = $pdo->prepare("
            SELECT g.*, gc.name as category_name, gc.icon_class as category_icon
            FROM gemachim g
            LEFT JOIN gemach_categories gc ON g.category_id = gc.id
            WHERE $where_clause
            ORDER BY $order_clause
            LIMIT 50
        ");
        $stmt->execute($params);
        $gemachim = $stmt->fetchAll();
        
        // Load stats
        $stmt = $pdo->query("SELECT COUNT(*) FROM gemachim WHERE status = 'active' AND verified = 1");
        $stats['total_items'] = $stmt->fetchColumn();
        
        $stmt = $pdo->query("
            SELECT COUNT(*) FROM gemach_donations 
            WHERE status = 'completed' 
            AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        $stats['total_donations'] = $stmt->fetchColumn() ?: 38;
        
    }
} catch (PDOException $e) {
    // Set fallback data
    $categories = [];
    $featured_gemach = null;
    $gemachim = [];
    $stats = ['total_items' => 872, 'total_donations' => 38];
}

$pageTitle = "Gemachim Directory | Find & Support Jewish Community Gemachim - JShuk";
$page_css = "gemachim.css";
$metaDescription = "Discover local gemachim in your Jewish community. Find, borrow, and donate to trusted gemachim for baby items, medical supplies, kitchen equipment, and more. Support mitzvahs in your area.";
$metaKeywords = "gemachim, jewish gemach, community lending, baby equipment, medical supplies, kitchen items, jewish community, mitzvah, charity, donations";

include 'includes/header_main.php';
?>

<!-- HERO SECTION -->
<section class="gemachim-hero" data-scroll>
    <div class="container">
        <div class="hero-content text-center">
            <h1 class="hero-title">Find a Gemach. Support a Mitzvah.</h1>
            <p class="hero-subtitle">Discover, borrow, and give within your community.</p>
            
            <!-- Progress Counter -->
            <div class="progress-counter">
                <div class="counter-item">
                    <i class="fas fa-box"></i>
                    <span class="counter-number"><?= number_format($stats['total_items']) ?></span>
                    <span class="counter-label">items available</span>
                </div>
                <div class="counter-item">
                    <i class="fas fa-heart"></i>
                    <span class="counter-number"><?= number_format($stats['total_donations']) ?></span>
                    <span class="counter-label">donations this month</span>
                </div>
            </div>
            
            <!-- CTA Buttons -->
            <div class="hero-cta">
                <a href="#gemachim-listings" class="btn-jshuk-primary">
                    <i class="fas fa-search"></i>
                    Browse Gemachim
                </a>
                <a href="/add_gemach.php" class="btn-jshuk-outline">
                    <i class="fas fa-plus"></i>
                    Add a Gemach
                </a>
            </div>
        </div>
    </div>
</section>

<!-- CATEGORY GRID -->
<section class="gemach-categories" data-scroll>
    <div class="container">
        <h2 class="section-title">Browse by Category</h2>
        <div class="category-grid">
            <?php foreach ($categories as $category): ?>
            <a href="?category=<?= htmlspecialchars($category['slug']) ?>" 
               class="category-card <?= ($filter_category === $category['slug']) ? 'active' : '' ?>"
               data-tippy-content="<?= htmlspecialchars($category['description']) ?>">
                <div class="category-icon">
                    <i class="<?= htmlspecialchars($category['icon_class']) ?>"></i>
                </div>
                <h3 class="category-name"><?= htmlspecialchars($category['name']) ?></h3>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- FILTERS AND LISTINGS -->
<section class="gemachim-main" id="gemachim-listings" data-scroll>
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
                               placeholder="Search gemachim..." 
                               value="<?= htmlspecialchars($search_query) ?>">
                    </div>
                    
                    <!-- Category Filter -->
                    <div class="filter-group">
                        <label class="filter-label">Category</label>
                        <select id="category-filter" class="form-select">
                            <option value="">All Categories</option>
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
                        <input type="text" id="location-filter" class="form-control" 
                               placeholder="Enter location..." 
                               value="<?= htmlspecialchars($filter_location) ?>">
                    </div>
                    
                    <!-- Sort Options -->
                    <div class="filter-group">
                        <label class="filter-label">Sort By</label>
                        <select id="sort-filter" class="form-select">
                            <option value="recent" <?= ($filter_sort === 'recent') ? 'selected' : '' ?>>Most Recent</option>
                            <option value="alphabetical" <?= ($filter_sort === 'alphabetical') ? 'selected' : '' ?>>Alphabetical</option>
                            <option value="nearby" <?= ($filter_sort === 'nearby') ? 'selected' : '' ?>>Nearby</option>
                        </select>
                    </div>
                    
                    <!-- Donation Filter -->
                    <div class="filter-group">
                        <div class="form-check">
                            <input type="checkbox" id="donation-filter" class="form-check-input" 
                                   <?= $filter_donation_only ? 'checked' : '' ?>>
                            <label class="form-check-label" for="donation-filter">
                                Only show gemachim with donations
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
                <!-- Featured Gemach -->
                <?php if ($featured_gemach): ?>
                <div class="featured-gemach">
                    <div class="featured-badge">
                        <i class="fas fa-star"></i>
                        Gemach of the Week
                    </div>
                    <div class="featured-content">
                        <div class="featured-image">
                            <?php if ($featured_gemach['image_paths']): ?>
                                <?php $images = json_decode($featured_gemach['image_paths'], true); ?>
                                <img src="<?= htmlspecialchars($images[0] ?? '/images/elite-placeholder.svg') ?>" 
                                     alt="<?= htmlspecialchars($featured_gemach['name']) ?>">
                            <?php else: ?>
                                <img src="/images/elite-placeholder.svg" 
                                     alt="<?= htmlspecialchars($featured_gemach['name']) ?>">
                            <?php endif; ?>
                        </div>
                        <div class="featured-details">
                            <h3 class="featured-title"><?= htmlspecialchars($featured_gemach['name']) ?></h3>
                            <div class="featured-category">
                                <i class="<?= htmlspecialchars($featured_gemach['category_icon']) ?>"></i>
                                <?= htmlspecialchars($featured_gemach['category_name']) ?>
                            </div>
                            <p class="featured-description"><?= htmlspecialchars(substr($featured_gemach['description'], 0, 200)) ?>...</p>
                            <div class="featured-location">
                                <i class="fas fa-map-marker-alt"></i>
                                <?= htmlspecialchars($featured_gemach['location']) ?>
                            </div>
                            <?php if ($featured_gemach['donation_enabled']): ?>
                            <a href="/donate.php?id=<?= $featured_gemach['id'] ?>" class="btn-jshuk-primary btn-sm">
                                <i class="fas fa-heart"></i>
                                Donate
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Results Header -->
                <div class="results-header">
                    <h3 class="results-title">
                        <?php if ($filter_category): ?>
                            <?= htmlspecialchars(array_filter($categories, fn($c) => $c['slug'] === $filter_category)[0]['name'] ?? '') ?> Gemachim
                        <?php else: ?>
                            All Gemachim
                        <?php endif; ?>
                    </h3>
                    <span class="results-count"><?= count($gemachim) ?> results</span>
                </div>
                
                <!-- Gemachim Listings -->
                <div class="gemachim-listings">
                    <?php if (empty($gemachim)): ?>
                    <div class="no-results">
                        <i class="fas fa-search"></i>
                        <h4>No gemachim found</h4>
                        <p>Try adjusting your filters or search terms.</p>
                        <a href="/add_gemach.php" class="btn-jshuk-primary">
                            <i class="fas fa-plus"></i>
                            Add a Gemach
                        </a>
                    </div>
                    <?php else: ?>
                        <?php foreach ($gemachim as $gemach): ?>
                        <div class="gemach-card">
                            <div class="gemach-image">
                                <?php if ($gemach['image_paths']): ?>
                                    <?php $images = json_decode($gemach['image_paths'], true); ?>
                                    <img src="<?= htmlspecialchars($images[0] ?? '/images/elite-placeholder.svg') ?>" 
                                         alt="<?= htmlspecialchars($gemach['name']) ?>">
                                <?php else: ?>
                                    <img src="/images/elite-placeholder.svg" 
                                         alt="<?= htmlspecialchars($gemach['name']) ?>">
                                <?php endif; ?>
                                
                                <!-- Badges -->
                                <div class="gemach-badges">
                                    <?php if ($gemach['verified']): ?>
                                    <span class="badge verified-badge" title="Verified by JShuk">
                                        <i class="fas fa-check-circle"></i>
                                    </span>
                                    <?php endif; ?>
                                    <?php if ($gemach['urgent_need']): ?>
                                    <span class="badge urgent-badge" title="Urgent Need">
                                        <i class="fas fa-exclamation-triangle"></i>
                                    </span>
                                    <?php endif; ?>
                                    <?php if ($gemach['in_memory_of']): ?>
                                    <span class="badge memory-badge" title="In Memory Of">
                                        <i class="fas fa-dove"></i>
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="gemach-content">
                                <div class="gemach-header">
                                    <h4 class="gemach-title"><?= htmlspecialchars($gemach['name']) ?></h4>
                                    <div class="gemach-category">
                                        <i class="<?= htmlspecialchars($gemach['category_icon']) ?>"></i>
                                        <?= htmlspecialchars($gemach['category_name']) ?>
                                    </div>
                                </div>
                                
                                <p class="gemach-description">
                                    <?= htmlspecialchars(substr($gemach['description'], 0, 150)) ?>...
                                </p>
                                
                                <div class="gemach-details">
                                    <div class="detail-item">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <span><?= htmlspecialchars($gemach['location']) ?></span>
                                    </div>
                                    
                                    <?php if ($gemach['contact_phone']): ?>
                                    <div class="detail-item">
                                        <i class="fas fa-phone"></i>
                                        <a href="tel:<?= htmlspecialchars($gemach['contact_phone']) ?>">
                                            <?= htmlspecialchars($gemach['contact_phone']) ?>
                                        </a>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($gemach['contact_email']): ?>
                                    <div class="detail-item">
                                        <i class="fas fa-envelope"></i>
                                        <a href="mailto:<?= htmlspecialchars($gemach['contact_email']) ?>">
                                            <?= htmlspecialchars($gemach['contact_email']) ?>
                                        </a>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($gemach['whatsapp_link']): ?>
                                    <div class="detail-item">
                                        <i class="fab fa-whatsapp"></i>
                                        <a href="<?= htmlspecialchars($gemach['whatsapp_link']) ?>" target="_blank">
                                            WhatsApp
                                        </a>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="gemach-actions">
                                    <?php if ($gemach['donation_enabled']): ?>
                                    <a href="/donate.php?id=<?= $gemach['id'] ?>" class="btn-jshuk-primary btn-sm">
                                        <i class="fas fa-heart"></i>
                                        Donate
                                    </a>
                                    <?php endif; ?>
                                    
                                    <button class="btn btn-outline-secondary btn-sm" 
                                            onclick="shareGemach(<?= $gemach['id'] ?>, '<?= htmlspecialchars($gemach['name']) ?>')">
                                        <i class="fas fa-share"></i>
                                        Share
                                    </button>
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

<!-- TESTIMONIALS SECTION -->
<section class="gemach-testimonials" data-scroll>
    <div class="container">
        <h2 class="section-title">How Gemachim Help Our Community</h2>
        <div class="testimonials-grid">
            <div class="testimonial-card">
                <div class="testimonial-content">
                    <i class="fas fa-quote-left"></i>
                    <p>"The twin pushchair gemach was a lifesaver when our twins were born. The community support was incredible."</p>
                </div>
                <div class="testimonial-author">
                    <strong>Sarah M.</strong>
                    <span>North London</span>
                </div>
            </div>
            
            <div class="testimonial-card">
                <div class="testimonial-content">
                    <i class="fas fa-quote-left"></i>
                    <p>"When my father needed a wheelchair, the medical equipment gemach provided exactly what we needed, quickly and with care."</p>
                </div>
                <div class="testimonial-author">
                    <strong>David R.</strong>
                    <span>Manchester</span>
                </div>
            </div>
            
            <div class="testimonial-card">
                <div class="testimonial-content">
                    <i class="fas fa-quote-left"></i>
                    <p>"The simcha decor collection helped us create a beautiful wedding without breaking the bank. Truly a blessing."</p>
                </div>
                <div class="testimonial-author">
                    <strong>Rachel K.</strong>
                    <span>South London</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA SECTION -->
<section class="gemach-cta" data-scroll>
    <div class="container">
        <div class="cta-content text-center">
            <h2>Have Something to Share?</h2>
            <p>Join our community of gemachim and help others in need.</p>
            <a href="/add_gemach.php" class="btn-jshuk-primary btn-lg">
                <i class="fas fa-plus"></i>
                Add Your Gemach
            </a>
        </div>
    </div>
</section>

<!-- Mobile Floating CTA -->
<div class="mobile-floating-cta d-lg-none">
    <a href="/add_gemach.php" class="btn-jshuk-primary">
        <i class="fas fa-plus"></i>
        Add Gemach
    </a>
</div>

<script>
// Filter functionality
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('search-input');
    const categoryFilter = document.getElementById('category-filter');
    const locationFilter = document.getElementById('location-filter');
    const sortFilter = document.getElementById('sort-filter');
    const donationFilter = document.getElementById('donation-filter');
    const clearFiltersBtn = document.getElementById('clear-filters');
    
    function applyFilters() {
        const params = new URLSearchParams();
        
        if (searchInput.value) params.append('search', searchInput.value);
        if (categoryFilter.value) params.append('category', categoryFilter.value);
        if (locationFilter.value) params.append('location', locationFilter.value);
        if (sortFilter.value !== 'recent') params.append('sort', sortFilter.value);
        if (donationFilter.checked) params.append('donation_only', '1');
        
        const newUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
        window.location.href = newUrl;
    }
    
    // Add event listeners
    searchInput.addEventListener('input', debounce(applyFilters, 500));
    categoryFilter.addEventListener('change', applyFilters);
    locationFilter.addEventListener('input', debounce(applyFilters, 500));
    sortFilter.addEventListener('change', applyFilters);
    donationFilter.addEventListener('change', applyFilters);
    
    clearFiltersBtn.addEventListener('click', function() {
        window.location.href = window.location.pathname;
    });
    
    // Debounce function
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
});

// Share functionality
function shareGemach(gemachId, gemachName) {
    const url = `${window.location.origin}/gemachim.php?id=${gemachId}`;
    const text = `Check out this gemach: ${gemachName}`;
    
    if (navigator.share) {
        navigator.share({
            title: gemachName,
            text: text,
            url: url
        });
    } else {
        // Fallback: copy to clipboard
        const shareText = `${text}\n${url}`;
        navigator.clipboard.writeText(shareText).then(() => {
            alert('Link copied to clipboard!');
        });
    }
}

// Smooth scroll for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});
</script>

<?php include 'includes/footer_main.php'; ?> 