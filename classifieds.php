<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

require_once 'config/config.php';

// --- Data Fetching ---
$error_message = '';
$classifieds = [];
$params = [];
$where_clauses = ["is_active = 1"];

// Handle search filter
if (!empty($_GET['q'])) {
    $where_clauses[] = "(title LIKE ? OR description LIKE ?)";
    $search_term = '%' . $_GET['q'] . '%';
    $params[] = $search_term;
    $params[] = $search_term;
}

// Handle category filter
if (!empty($_GET['category'])) {
    $category_slug = $_GET['category'];
    $where_clauses[] = "cc.slug = ?";
    $params[] = $category_slug;
}

// Handle free items filter
if (isset($_GET['free_only']) && $_GET['free_only'] == '1') {
    $where_clauses[] = "c.price = 0";
}

$where_sql = "WHERE " . implode(" AND ", $where_clauses);

try {
    $stmt = $pdo->prepare("
        SELECT c.*, cc.name as category_name, cc.slug as category_slug, cc.icon as category_icon,
               u.username as user_name
        FROM classifieds c
        LEFT JOIN classifieds_categories cc ON c.category_id = cc.id
        LEFT JOIN users u ON c.user_id = u.id
        $where_sql 
        ORDER BY c.created_at DESC
    ");
    $stmt->execute($params);
    $classifieds = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Classifieds Page Error: " . $e->getMessage());
    $error_message = "A database error occurred. Please try again later.";
}

$pageTitle = "Classifieds | Buy & Sell in the Jewish Community";
$page_css = "classifieds.css";
$metaDescription = "Buy and sell items in the Jewish community. Find great deals on furniture, electronics, books, and more. Post your own classified ads on JShuk.";
$metaKeywords = "jewish classifieds, buy sell jewish community, furniture, electronics, books, community marketplace";
include 'includes/header_main.php';
require_once 'includes/ad_renderer.php';

// DEBUG: Add debug output for ad system
if (isset($_GET['debug_ads'])) {
    echo "<div style='background: #f0f0f0; border: 2px solid #ff0000; padding: 10px; margin: 10px; font-family: monospace;'>";
    echo "<h3>🔍 AD SYSTEM DEBUG - CLASSIFIEDS PAGE</h3>";
    
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
    <h1>Buy & Sell in the Jewish Community</h1>
    <p class="subheading">
      Find great deals on furniture, electronics, books, and more. Connect with trusted community members for safe, local transactions.
    </p>
    <div class="hero-cta-buttons">
      <?php if (isset($_SESSION['user_id'])): ?>
        <a href="submit_classified.php" class="hero-btn">Post a Classified</a>
      <?php else: ?>
        <a href="/auth/login.php" class="hero-btn">Login to Post</a>
        <a href="/auth/register.php" class="hero-btn">Sign Up Free</a>
      <?php endif; ?>
      <a href="#classifieds" class="hero-btn">Browse Classifieds</a>
    </div>
  </div>
</section>

<!-- SEARCH BAR: Use homepage Airbnb-style search -->
<section class="search-banner bg-white py-4 shadow-sm">
  <div class="container">
    <form action="/classifieds.php" method="GET" class="airbnb-search-bar" role="search">
      <select name="category" class="form-select" aria-label="Select category">
        <option value="" disabled selected>🏷️ Select Category</option>
        <option value="free-stuff" <?= ($_GET['category'] ?? '') === 'free-stuff' ? 'selected' : '' ?>>♻️ Free Stuff</option>
        <option value="furniture" <?= ($_GET['category'] ?? '') === 'furniture' ? 'selected' : '' ?>>Furniture</option>
        <option value="electronics" <?= ($_GET['category'] ?? '') === 'electronics' ? 'selected' : '' ?>>Electronics</option>
        <option value="books-seforim" <?= ($_GET['category'] ?? '') === 'books-seforim' ? 'selected' : '' ?>>Books & Seforim</option>
        <option value="clothing" <?= ($_GET['category'] ?? '') === 'clothing' ? 'selected' : '' ?>>Clothing</option>
        <option value="toys-games" <?= ($_GET['category'] ?? '') === 'toys-games' ? 'selected' : '' ?>>Toys & Games</option>
        <option value="kitchen-items" <?= ($_GET['category'] ?? '') === 'kitchen-items' ? 'selected' : '' ?>>Kitchen Items</option>
        <option value="jewelry" <?= ($_GET['category'] ?? '') === 'jewelry' ? 'selected' : '' ?>>Jewelry</option>
        <option value="judaica" <?= ($_GET['category'] ?? '') === 'judaica' ? 'selected' : '' ?>>Judaica</option>
        <option value="office-school" <?= ($_GET['category'] ?? '') === 'office-school' ? 'selected' : '' ?>>Office & School</option>
        <option value="baby-kids" <?= ($_GET['category'] ?? '') === 'baby-kids' ? 'selected' : '' ?>>Baby & Kids</option>
        <option value="miscellaneous" <?= ($_GET['category'] ?? '') === 'miscellaneous' ? 'selected' : '' ?>>Miscellaneous</option>
      </select>
      <select name="location" class="form-select" aria-label="Select location">
        <option value="" disabled selected>📍 Select Location</option>
        <option value="manchester">Manchester</option>
        <option value="london">London</option>
        <option value="leeds">Leeds</option>
        <option value="liverpool">Liverpool</option>
        <option value="birmingham">Birmingham</option>
      </select>
      <input type="text" name="q" class="form-control" placeholder="🔍 Search classifieds..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>" />
      <button type="submit" class="btn btn-search" aria-label="Search">
        <i class="fa fa-search"></i>
        <span class="d-none d-md-inline">Search</span>
      </button>
    </form>
  </div>
</section>

<!-- Main Content -->
<main class="main-content-wrapper">
  <section id="classifieds" class="classifieds-section" data-scroll>
    <div class="container">
      <div class="section-header">
        <h2 class="section-title">
          <?php if (isset($_GET['category']) && $_GET['category'] === 'free-stuff'): ?>
            ♻️ Free Stuff & Chessed Giveaways
          <?php elseif (isset($_GET['free_only']) && $_GET['free_only'] == '1'): ?>
            🆓 Free Items Only
          <?php else: ?>
            Latest Classifieds
          <?php endif; ?>
        </h2>
        <p class="section-subtitle">
          <?php if (isset($_GET['category']) && $_GET['category'] === 'free-stuff'): ?>
            Give with heart, receive with gratitude. Find and share free items in our community.
          <?php elseif (isset($_GET['free_only']) && $_GET['free_only'] == '1'): ?>
            Find great free items from trusted community members
          <?php else: ?>
            Find great deals from trusted community members
          <?php endif; ?>
        </p>
      </div>
      
      <!-- Filter Section -->
      <div class="filters-section mb-4">
        <div class="row align-items-center">
          <div class="col-md-6">
            <div class="filter-buttons">
              <a href="/classifieds.php" class="btn btn-outline-primary <?= !isset($_GET['free_only']) ? 'active' : '' ?>">
                All Items
              </a>
              <a href="/classifieds.php?free_only=1" class="btn btn-outline-success <?= (isset($_GET['free_only']) && $_GET['free_only'] == '1') ? 'active' : '' ?>">
                ♻️ Free Items Only
              </a>
              <a href="/classifieds.php?category=free-stuff" class="btn btn-outline-info <?= (isset($_GET['category']) && $_GET['category'] === 'free-stuff') ? 'active' : '' ?>">
                Free Stuff Category
              </a>
            </div>
          </div>
          <div class="col-md-6 text-md-end">
            <span class="text-muted">
              <?= count($classifieds) ?> item<?= count($classifieds) !== 1 ? 's' : '' ?> found
            </span>
          </div>
        </div>
      </div>
      
      <?php if ($error_message): ?>
        <div class="alert alert-danger text-center"><?= htmlspecialchars($error_message) ?></div>
      <?php endif; ?>

      <?php if (empty($classifieds)): ?>
        <div class="empty-state text-center py-5">
          <div class="empty-state-icon mb-3">
            <i class="fas fa-tags fa-3x text-muted"></i>
          </div>
          <h3>No Classifieds Found</h3>
          <p class="text-muted">Be the first to post a classified ad in our community!</p>
          <?php if (isset($_SESSION['user_id'])): ?>
            <a href="submit_classified.php" class="btn-jshuk-primary">Post a Classified</a>
          <?php else: ?>
            <a href="/auth/login.php" class="btn-jshuk-primary">Login to Post</a>
          <?php endif; ?>
        </div>
      <?php else: ?>
        <div class="classifieds-grid">
          <?php foreach ($classifieds as $c): ?>
            <div class="classified-card-wrapper">
              <div class="classified-card">
                <div class="classified-image">
                  <a href="classified_view.php?id=<?= $c['id'] ?>">
                    <?php if ($c['image_path']): ?>
                      <img src="<?= htmlspecialchars($c['image_path']) ?>" 
                           alt="<?= htmlspecialchars($c['title']) ?>"
                           onerror="this.src='/images/placeholder.png';">
                    <?php else: ?>
                      <img src="/images/placeholder.png" alt="No image available">
                    <?php endif; ?>
                  </a>
                  <?php if ($c['price'] == 0): ?>
                    <span class="badge-free">♻️ Free</span>
                  <?php endif; ?>
                  <?php if ($c['is_chessed']): ?>
                    <span class="badge-chessed">💝 Chessed</span>
                  <?php endif; ?>
                  <?php if ($c['is_bundle']): ?>
                    <span class="badge-bundle">📦 Bundle</span>
                  <?php endif; ?>
                </div>
                
                <div class="classified-content">
                  <div class="classified-header">
                    <h3 class="classified-title">
                      <a href="classified_view.php?id=<?= $c['id'] ?>">
                        <?= htmlspecialchars($c['title']) ?>
                      </a>
                    </h3>
                    <div class="classified-price">
                      <?= ($c['price'] > 0) ? '£' . number_format($c['price'], 2) : '♻️ Free' ?>
                    </div>
                  </div>
                  
                  <?php if ($c['category_name']): ?>
                    <div class="classified-category">
                      <i class="fas fa-tag"></i>
                      <?= htmlspecialchars($c['category_name']) ?>
                    </div>
                  <?php endif; ?>
                  
                  <?php if ($c['location']): ?>
                    <div class="classified-location">
                      <i class="fas fa-map-marker-alt"></i>
                      <?= htmlspecialchars($c['location']) ?>
                    </div>
                  <?php endif; ?>
                  
                  <div class="classified-description">
                    <?= htmlspecialchars(mb_strimwidth($c['description'], 0, 100, '...')) ?>
                  </div>
                  
                  <div class="classified-meta">
                    <span class="classified-date">
                      <i class="fas fa-clock"></i>
                      <?= date('M j, Y', strtotime($c['created_at'])) ?>
                    </span>
                    <?php if ($c['price'] == 0 && $c['pickup_method']): ?>
                      <span class="classified-pickup">
                        <i class="fas fa-handshake"></i>
                        <?php
                        switch($c['pickup_method']) {
                          case 'porch_pickup': echo 'Porch Pickup'; break;
                          case 'contact_arrange': echo 'Contact to Arrange'; break;
                          case 'collection_code': echo 'Collection Code'; break;
                          default: echo 'Contact Seller';
                        }
                        ?>
                      </span>
                    <?php endif; ?>
                    <?php if ($c['price'] == 0 && $c['status']): ?>
                      <span class="classified-status status-<?= $c['status'] ?>">
                        <i class="fas fa-circle"></i>
                        <?= ucfirst($c['status']) ?>
                      </span>
                    <?php endif; ?>
                  </div>
                  
                  <div class="classified-actions">
                    <?php if ($c['price'] == 0): ?>
                      <a href="classified_view.php?id=<?= $c['id'] ?>" class="btn-view btn-request">
                        <span>Request This Item</span>
                        <i class="fas fa-gift"></i>
                      </a>
                    <?php else: ?>
                      <a href="classified_view.php?id=<?= $c['id'] ?>" class="btn-view">
                        <span>View Details</span>
                        <i class="fas fa-arrow-right"></i>
                      </a>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </section>

  <!-- Popular Categories Section -->
  <section class="popular-categories-section" data-scroll>
    <div class="container">
      <div class="section-header">
        <h2 class="section-title">Popular Categories</h2>
        <p class="section-subtitle">Browse classifieds by category</p>
      </div>
      
      <div class="categories-scroll-wrapper">
        <div class="category-scroll">
          <a href="/classifieds.php?category=furniture" class="category-card">
            <div class="icon-circle">
              <i class="fas fa-couch"></i>
            </div>
            <h3 class="category-name">Furniture</h3>
          </a>
          <a href="/classifieds.php?category=electronics" class="category-card">
            <div class="icon-circle">
              <i class="fas fa-laptop"></i>
            </div>
            <h3 class="category-name">Electronics</h3>
          </a>
          <a href="/classifieds.php?category=books" class="category-card">
            <div class="icon-circle">
              <i class="fas fa-book"></i>
            </div>
            <h3 class="category-name">Books</h3>
          </a>
          <a href="/classifieds.php?category=clothing" class="category-card">
            <div class="icon-circle">
              <i class="fas fa-tshirt"></i>
            </div>
            <h3 class="category-name">Clothing</h3>
          </a>
          <a href="/classifieds.php?category=toys" class="category-card">
            <div class="icon-circle">
              <i class="fas fa-gamepad"></i>
            </div>
            <h3 class="category-name">Toys & Games</h3>
          </a>
          <a href="/classifieds.php?category=jewelry" class="category-card">
            <div class="icon-circle">
              <i class="fas fa-gem"></i>
            </div>
            <h3 class="category-name">Jewelry</h3>
          </a>
        </div>
      </div>
    </div>
  </section>
</main>

<?php include 'includes/footer_main.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add loading states to classified cards
    const classifiedCards = document.querySelectorAll('.classified-card');
    
    classifiedCards.forEach(card => {
        card.addEventListener('click', function(e) {
            // Don't add loading if clicking on buttons or links
            if (e.target.tagName === 'A' || e.target.closest('a')) {
                return;
            }
            
            // Add loading state
            this.classList.add('loading');
            
            // Navigate to classified page
            const classifiedLink = this.querySelector('a[href*="classified_view.php"]');
            if (classifiedLink) {
                window.location.href = classifiedLink.href;
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
});
</script>

<style>
/* Import homepage styles */
@import url('/css/pages/homepage.css');
@import url('/css/components/search-bar.css');

/* Classifieds-specific styles */
.filters-section {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.filter-buttons {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.filter-buttons .btn {
    border-radius: 25px;
    font-weight: 500;
    transition: all 0.2s ease;
}

.filter-buttons .btn.active {
    background: #ffd700;
    border-color: #ffd700;
    color: #1a3353;
}

.filter-buttons .btn:hover {
    transform: translateY(-1px);
}
.classifieds-section {
    padding: 3rem 0;
}

.classifieds-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 2rem;
    margin-top: 2rem;
}

.classified-card-wrapper {
    animation: fadeInUp 0.6s ease-out;
}

.classified-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.08);
    overflow: hidden;
    transition: all 0.3s ease;
    cursor: pointer;
}

.classified-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 32px rgba(0,0,0,0.12);
}

.classified-image {
    position: relative;
    height: 200px;
    overflow: hidden;
}

.classified-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.classified-card:hover .classified-image img {
    transform: scale(1.05);
}

.badge-free {
    position: absolute;
    top: 0.75rem;
    right: 0.75rem;
    background: #28a745;
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 600;
}

.badge-chessed {
    position: absolute;
    top: 0.75rem;
    left: 0.75rem;
    background: #e91e63;
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 600;
}

.badge-bundle {
    position: absolute;
    top: 3rem;
    right: 0.75rem;
    background: #ff9800;
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 600;
}

.classified-content {
    padding: 1.5rem;
}

.classified-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 0.75rem;
}

.classified-title {
    margin: 0;
    font-size: 1.1rem;
    font-weight: 600;
    color: #1a3353;
    line-height: 1.3;
    flex: 1;
}

.classified-title a {
    color: inherit;
    text-decoration: none;
}

.classified-title a:hover {
    color: #ffd700;
}

.classified-price {
    font-size: 1.2rem;
    font-weight: 700;
    color: #ffd700;
    margin-left: 1rem;
}

.classified-category {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: #6c757d;
    margin-bottom: 0.5rem;
}

.classified-category i {
    color: #ffd700;
}

.classified-location {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: #6c757d;
    margin-bottom: 0.75rem;
}

.classified-location i {
    color: #ffd700;
}

.classified-description {
    font-size: 0.875rem;
    color: #495057;
    line-height: 1.5;
    margin-bottom: 1rem;
}

.classified-meta {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1rem;
    flex-wrap: wrap;
}

.classified-date,
.classified-pickup,
.classified-status {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.75rem;
    color: #6c757d;
}

.classified-date i,
.classified-pickup i {
    color: #ffd700;
}

.classified-status.status-available i {
    color: #28a745;
}

.classified-status.status-pending_pickup i {
    color: #ffc107;
}

.classified-status.status-claimed i {
    color: #6c757d;
}

.classified-status.status-expired i {
    color: #dc3545;
}

.classified-actions {
    display: flex;
    justify-content: center;
}

.btn-view {
    background: linear-gradient(90deg, #ffd700 0%, #ffd700 100%);
    color: #1a3353;
    border: none;
    border-radius: 8px;
    padding: 0.75rem 1.5rem;
    font-size: 0.875rem;
    font-weight: 600;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.2s ease;
    width: 100%;
    justify-content: center;
}

.btn-view:hover {
    background: linear-gradient(90deg, #ffd700 0%, #ffcc00 100%);
    color: #1a3353;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(255, 215, 0, 0.3);
}

.btn-request {
    background: linear-gradient(90deg, #28a745 0%, #20c997 100%);
    color: white;
}

.btn-request:hover {
    background: linear-gradient(90deg, #20c997 0%, #17a2b8 100%);
    color: white;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
}

/* Loading states */
.classified-card.loading {
    opacity: 0.7;
    pointer-events: none;
}

.classified-card.loading::after {
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
    .classifieds-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .classified-content {
        padding: 1rem;
    }
    
    .classified-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
    
    .classified-price {
        margin-left: 0;
    }
    
    .btn-view {
        padding: 0.75rem 1rem;
    }
}

/* Focus states for accessibility */
.classified-card:focus-within {
    outline: 2px solid #ffd700;
    outline-offset: 2px;
}

.btn-view:focus {
    outline: 2px solid #ffd700;
    outline-offset: 2px;
}

/* Animation delays for staggered loading */
.classified-card-wrapper:nth-child(1) { animation-delay: 0.1s; }
.classified-card-wrapper:nth-child(2) { animation-delay: 0.2s; }
.classified-card-wrapper:nth-child(3) { animation-delay: 0.3s; }
.classified-card-wrapper:nth-child(4) { animation-delay: 0.4s; }
.classified-card-wrapper:nth-child(5) { animation-delay: 0.5s; }
.classified-card-wrapper:nth-child(6) { animation-delay: 0.6s; }
</style> 
</style> 