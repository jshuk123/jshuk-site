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

// ✅ 1. Minimal Data Loading - Only Essential Data
$categories = [];
$stats = ['total_businesses' => 500, 'monthly_users' => 1200];
$featured = [];
$newBusinesses = [];

try {
    if (isset($pdo) && $pdo) {
        // Load categories for search
        $stmt = $pdo->query("SELECT id, name FROM business_categories ORDER BY name ASC");
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Load basic stats
        $stats = [];
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM businesses WHERE status = 'active'");
        $stmt->execute();
        $stats['total_businesses'] = $stmt->fetchColumn();
        $stmt = $pdo->prepare("SELECT COUNT(DISTINCT user_id) FROM user_activity WHERE activity_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
        $stmt->execute();
        $stats['monthly_users'] = $stmt->fetchColumn() ?: 1200;

        // Load featured businesses - FIXED QUERY
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

        // Load new businesses - FIXED QUERY
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
                // Add fallback data
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
            'business_name' => 'Sample Business',
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

<!-- HERO SECTION -->
<?php include 'sections/hero.php'; ?>

<!-- SEARCH BAR -->
<?php include 'sections/search_bar.php'; ?>

<!-- CATEGORY SHOWCASE -->
<?php include 'sections/categories.php'; ?>

<!-- GEMACHIM SECTION: Community Lending Made Easy -->
<section id="gemachim-section" class="py-5 bg-light">
  <div class="container">
    <h2 class="text-center mb-4">🤲 Gemachim: Community Lending Made Easy</h2>
    <p class="text-center text-muted mb-5">Explore free-loan resources in your area – from baby gear to simcha décor, it's all shared with love.</p>
    <div class="row">
      <!-- Example cards -->
      <div class="col-md-4">
        <div class="card h-100 shadow-sm">
          <div class="card-body">
            <h5 class="card-title">👶 Baby Items</h5>
            <p class="card-text">Pushchairs, cots, and car seats – all available through your local gemachim.</p>
            <a href="/gemachim.php?category=baby-maternity" class="btn btn-primary btn-sm mt-3">Browse Baby Gemachs</a>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card h-100 shadow-sm">
          <div class="card-body">
            <h5 class="card-title">🍽 Kitchen & Simcha</h5>
            <p class="card-text">Kitchenware, simcha décor, and more – borrow for your next event or family need.</p>
            <a href="/gemachim.php?category=kitchen-items" class="btn btn-primary btn-sm mt-3">Browse Kitchen Gemachs</a>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card h-100 shadow-sm">
          <div class="card-body">
            <h5 class="card-title">💊 Medical & More</h5>
            <p class="card-text">Wheelchairs, crutches, and health supplies – supporting community wellness.</p>
            <a href="/gemachim.php?category=medical-supplies" class="btn btn-primary btn-sm mt-3">Browse Medical Gemachs</a>
          </div>
        </div>
      </div>
    </div>
    <div class="text-center mt-4">
      <a href="/gemachim.php" class="btn btn-outline-primary">View All Gemachim</a>
      <span class="badge bg-warning text-dark ms-3" style="font-size:1rem;vertical-align:middle;">📦 482 Items Borrowed This Month!</span>
    </div>
  </div>
</section>

<!-- FEATURED BUSINESSES SECTION -->
<?php include 'sections/featured_businesses.php'; ?>

<!-- NEW BUSINESSES SECTION -->
<?php include 'sections/new_businesses.php'; ?>

<!-- TRUST SECTION -->
<?php include 'sections/trust.php'; ?>

<!-- WHATSAPP HOOK -->
<?php include 'sections/whatsapp_hook.php'; ?>

<!-- ABOUT LINK -->
<section class="about-link-section" data-scroll>
  <div class="container">
    <div class="about-link-content">
      <h3>Want to learn more about JShuk?</h3>
      <p>Discover how it works and find answers to common questions</p>
      <a href="<?= BASE_PATH ?>about.php" class="btn-jshuk-outline">Learn More</a>
    </div>
  </div>
</section>

<!-- FAQ SECTION -->
<section class="faq-section" data-scroll id="about-jshuk">
  <div class="container">
    <h2 class="section-title">Frequently Asked Questions</h2>
    <div class="accordion" id="faqAccordion">
      <div class="accordion-item">
        <h2 class="accordion-header" id="faq1">
          <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse1" aria-expanded="true" aria-controls="collapse1">
            How do I post a business?
          </button>
        </h2>
        <div id="collapse1" class="accordion-collapse collapse show" aria-labelledby="faq1" data-bs-parent="#faqAccordion">
          <div class="accordion-body">
            Simply click "Post Your Business" above, create a free account, and fill out your business details. It takes just a few minutes to get started!
          </div>
        </div>
      </div>
      
      <div class="accordion-item">
        <h2 class="accordion-header" id="faq2">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse2" aria-expanded="false" aria-controls="collapse2">
            Is JShuk free to use?
          </button>
        </h2>
        <div id="collapse2" class="accordion-collapse collapse" aria-labelledby="faq2" data-bs-parent="#faqAccordion">
          <div class="accordion-body">
            Yes! Basic listings are completely free. We also offer premium features for businesses who want enhanced visibility and additional tools.
          </div>
        </div>
      </div>
      
      <div class="accordion-item">
        <h2 class="accordion-header" id="faq3">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse3" aria-expanded="false" aria-controls="collapse3">
            How do I find local Jewish businesses?
          </button>
        </h2>
        <div id="collapse3" class="accordion-collapse collapse" aria-labelledby="faq3" data-bs-parent="#faqAccordion">
          <div class="accordion-body">
            Use our search bar above or browse by categories. You can filter by location, service type, and more to find exactly what you need.
          </div>
        </div>
      </div>
      
      <div class="accordion-item">
        <h2 class="accordion-header" id="faq4">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse4" aria-expanded="false" aria-controls="collapse4">
            Are all businesses kosher-certified?
          </button>
        </h2>
        <div id="collapse4" class="accordion-collapse collapse" aria-labelledby="faq4" data-bs-parent="#faqAccordion">
          <div class="accordion-body">
            We list all Jewish-owned businesses. For kosher certification, please check with individual businesses as requirements vary.
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- HOW IT WORKS -->
<section class="how-it-works-section" data-scroll>
  <div class="container">
    <h2 class="section-title">How It Works</h2>
    <p class="section-subtitle">Get started in just three simple steps</p>
    <div class="steps-grid">
      <div class="step-card">
        <div class="step-icon">
          <i class="fa-solid fa-user-plus"></i>
        </div>
        <h3>1. Sign Up</h3>
        <p>Create your free account and join our growing community of Jewish businesses and customers.</p>
      </div>
      <div class="step-card">
        <div class="step-icon">
          <i class="fa-solid fa-store"></i>
        </div>
        <h3>2. List Your Business</h3>
        <p>Add your business details, photos, and services to showcase what makes you unique.</p>
      </div>
      <div class="step-card">
        <div class="step-icon">
          <i class="fa-solid fa-search"></i>
        </div>
        <h3>3. Get Discovered</h3>
        <p>Connect with local customers who are actively searching for businesses like yours.</p>
      </div>
    </div>
    <div class="section-actions">
      <a href="<?= BASE_PATH ?>auth/register.php" class="btn-jshuk-primary" data-track="post_business_cta" data-category="conversion">Post Your Business for Free</a>
    </div>
  </div>
</section>



<!-- FOOTER CTA -->
<?php include 'sections/footer_cta.php'; ?>

<?php 
  // ✅ Deploy confirmation message
  echo '<p style="color: green; text-align: center;">✅ Deploy test: index.php updated successfully</p>';
  
  include 'includes/footer_main.php'; 
?>


<!-- Structured Data for SEO -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "WebSite",
  "name": "JShuk",
  "description": "Jewish Business Directory for London, Manchester, and across the UK",
  "url": "https://jshuk.com",
  "potentialAction": {
    "@type": "SearchAction",
    "target": "https://jshuk.com/businesses.php?search={search_term_string}",
    "query-input": "required name=search_term_string"
  },
  "sameAs": [
    "https://jshuk.com"
  ]
}
</script>

<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Organization",
  "name": "JShuk",
  "description": "Jewish Business Directory and Community Marketplace",
  "url": "https://jshuk.com",
  "logo": "https://jshuk.com/images/jshuk-logo.png",
  "address": {
    "@type": "PostalAddress",
    "addressCountry": "GB"
  },
  "serviceArea": {
    "@type": "Place",
    "name": "United Kingdom",
    "containsPlace": [
      {
        "@type": "Place",
        "name": "London"
      },
      {
        "@type": "Place", 
        "name": "Manchester"
      },
      {
        "@type": "Place",
        "name": "Gateshead"
      }
    ]
  }
}
</script>

<!-- Essential CSS for sections -->
<style>
  /* Ensure sections are visible */
  .faq-section, .how-it-works-section {
    display: block !important;
    visibility: visible !important;
    opacity: 1 !important;
  }
  
  /* Basic accordion styles if Bootstrap fails to load */
  .accordion-button {
    display: block !important;
    width: 100% !important;
    padding: 1rem !important;
    background: #fff !important;
    border: 1px solid #e9ecef !important;
    text-align: left !important;
    cursor: pointer !important;
  }
  
  .accordion-body {
    display: block !important;
    padding: 1rem !important;
    background: #f8f9fa !important;
    border-top: 1px solid #e9ecef !important;
  }
  
  .steps-grid {
    display: grid !important;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)) !important;
    gap: 2rem !important;
    margin: 2rem 0 !important;
  }
  
  .step-card {
    display: block !important;
    background: #fff !important;
    padding: 2rem !important;
    border-radius: 16px !important;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06) !important;
    text-align: center !important;
  }
</style>
