<?php
require_once 'config/config.php';
require_once 'includes/subscription_functions.php';
if (file_exists('includes/cache.php')) {
    require_once 'includes/cache.php';
}
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'samesite' => 'Lax',
    ]);
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
$stats = ['total_businesses' => 500, 'monthly_users' => 1200];
$featured = [];
$newBusinesses = [];

try {
    if (isset($pdo) && $pdo) {
        // Load categories
        $stmt = $pdo->query("SELECT id, name FROM business_categories ORDER BY name ASC");
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Load stats
        $stats = [];
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM businesses WHERE status = 'active'");
        $stmt->execute();
        $stats['total_businesses'] = $stmt->fetchColumn();
        $stmt = $pdo->prepare("SELECT COUNT(DISTINCT user_id) FROM user_activity WHERE activity_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
        $stmt->execute();
        $stats['monthly_users'] = $stmt->fetchColumn() ?: 1200;

        // Load featured businesses
        $stmt = $pdo->prepare("
            SELECT b.id, b.business_name, b.description, b.category_id, b.is_featured, b.featured_until, 
                   c.name as category_name, u.subscription_tier 
            FROM businesses b 
            LEFT JOIN business_categories c ON b.category_id = c.id 
            LEFT JOIN users u ON b.user_id = u.id
            WHERE b.status = 'active' 
            AND u.subscription_tier IN ('premium', 'premium_plus')
            ORDER BY 
                CASE u.subscription_tier 
                    WHEN 'premium_plus' THEN 1 
                    WHEN 'premium' THEN 2 
                    ELSE 3 
                END,
                b.created_at DESC 
            LIMIT 6
        ");
        $stmt->execute();
        $featured = $stmt->fetchAll();
        
        // If no featured with status=active, try without status filter
        if (empty($featured)) {
            $stmt = $pdo->prepare("
                SELECT b.id, b.business_name, b.description, b.category_id, b.is_featured, b.featured_until, 
                       c.name as category_name, u.subscription_tier, b.status
                FROM businesses b 
                LEFT JOIN business_categories c ON b.category_id = c.id 
                LEFT JOIN users u ON b.user_id = u.id
                WHERE u.subscription_tier IN ('premium', 'premium_plus')
                ORDER BY 
                    CASE u.subscription_tier 
                        WHEN 'premium_plus' THEN 1 
                        WHEN 'premium' THEN 2 
                        ELSE 3 
                    END,
                    b.created_at DESC 
                LIMIT 6
            ");
            $stmt->execute();
            $featured = $stmt->fetchAll();
        }

        // Load new businesses
        $stmt = $pdo->prepare("
            SELECT b.id, b.business_name, b.description, b.created_at, c.name AS category_name, u.subscription_tier
            FROM businesses b
            LEFT JOIN business_categories c ON b.category_id = c.id
            LEFT JOIN users u ON b.user_id = u.id
            WHERE b.status = 'active'
            ORDER BY b.created_at DESC
            LIMIT 6
        ");
        $stmt->execute();
        $newBusinesses = $stmt->fetchAll();
        
        // If no new businesses with status=active, try without status filter
        if (empty($newBusinesses)) {
            $stmt = $pdo->prepare("
                SELECT b.id, b.business_name, b.description, b.created_at, c.name AS category_name, u.subscription_tier, b.status
                FROM businesses b
                LEFT JOIN business_categories c ON b.category_id = c.id
                LEFT JOIN users u ON b.user_id = u.id
                ORDER BY b.created_at DESC
                LIMIT 6
            ");
            $stmt->execute();
            $newBusinesses = $stmt->fetchAll();
            
            if (empty($newBusinesses)) {
                $newBusinesses = [
                    [
                        'id' => 9999,
                        'business_name' => 'Sample Business',
                        'description' => 'Sample business for testing',
                        'category_name' => 'Test Category',
                        'subscription_tier' => 'basic',
                        'created_at' => date('Y-m-d H:i:s')
                    ]
                ];
            }
        }
    }
} catch (PDOException $e) {
    // Set fallback data
    $categories = [];
    $stats = ['total_businesses' => 500, 'monthly_users' => 1200];
    $featured = [];
    $newBusinesses = [
        [
            'id' => 9999,
            'business_name' => 'Fallback Business',
            'description' => 'Database connection failed',
            'category_name' => 'Test Category',
            'subscription_tier' => 'basic',
            'created_at' => date('Y-m-d H:i:s')
        ]
    ];
}

$pageTitle = "JShuk | Jewish Business Directory London & UK - Find Trusted Jewish Businesses";
$page_css = "homepage.css";
$metaDescription = "Find trusted Jewish businesses in London, Manchester, and across the UK. Discover kosher restaurants, Jewish services, local businesses, and community resources. Your complete Jewish business directory for London and beyond.";
$metaKeywords = "jewish business london, jewish directory, kosher restaurants london, jewish services uk, local jewish business, community marketplace, manchester, gateshead, jewish professionals, kosher caterers, jewish businesses near me";
include 'includes/header_main.php';
require_once 'includes/ad_renderer.php';
?>

<main>
  <!-- ===== HERO SECTION ===== -->
  <section class="hero" data-scroll>
    <div class="hero-inner">
      <h1>JShuk</h1>
      <p class="subheading">Your Jewish Business Hub in the UK | Find Trusted Local Businesses</p>
      
      <form class="search-bar" method="GET" action="/search.php" role="search" aria-label="Search businesses">
        <input type="text" name="query" placeholder="Search for businesses..." aria-label="Search query" />
        <select name="category_id" aria-label="Business category">
          <option value="">All Categories</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit" aria-label="Search"><i class="fas fa-search"></i> Search</button>
      </form>
      
      <div class="hero-cta-buttons">
        <a href="/auth/register.php" class="hero-btn">List Your Business</a>
        <a href="/businesses.php" class="hero-btn">Browse All</a>
      </div>
    </div>
  </section>

  <!-- ===== FEATURED BUSINESSES SECTION ===== -->
  <?php include 'sections/featured_businesses.php'; ?>

  <!-- ===== NEW BUSINESSES SECTION ===== -->
  <?php include 'sections/new_businesses.php'; ?>

  <!-- ===== CATEGORIES SECTION ===== -->
  <?php include 'sections/categories.php'; ?>

  <!-- ===== TRUST SECTION ===== -->
  <?php include 'sections/trust.php'; ?>

  <!-- ===== RECENT LISTINGS SECTION ===== -->
  <?php include 'sections/recent_listings.php'; ?>

  <!-- ===== FEATURED CAROUSEL SECTION ===== -->
  <?php include 'sections/featured_carousel.php'; ?>

</main>

<?php include 'includes/footer_main.php'; ?> 