<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Simple constants
define('BASE_PATH', '/');
define('SITE_NAME', 'JShuk');
define('SITE_DESCRIPTION', 'Jewish Local Directory');

// Mock data for testing
$featured = [
    [
        'id' => 1,
        'business_name' => 'Sample Restaurant',
        'description' => 'Delicious kosher dining in the heart of the community.',
        'category_name' => 'Restaurant',
        'subscription_tier' => 'premium'
    ],
    [
        'id' => 2,
        'business_name' => 'Community Store',
        'description' => 'Your one-stop shop for all your needs.',
        'category_name' => 'Retail',
        'subscription_tier' => 'premium_plus'
    ]
];

$categories = [
    ['id' => 1, 'name' => 'Restaurant', 'business_count' => 5],
    ['id' => 2, 'name' => 'Retail', 'business_count' => 3],
    ['id' => 3, 'name' => 'Education', 'business_count' => 2],
    ['id' => 4, 'name' => 'Healthcare', 'business_count' => 4]
];

$new = [
    [
        'id' => 3,
        'business_name' => 'New Business',
        'description' => 'Recently added to our community.',
        'category_name' => 'Services',
        'subscription_tier' => 'basic'
    ]
];

$stats = [
    'total_businesses' => 50,
    'total_users' => 200,
    'monthly_users' => 1200
];

$pageTitle = "JShuk | Jewish Local Directory - Find Trusted Jewish Businesses Near You";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/assets/css/homepage.css">
    
    <!-- Favicon -->
    <link rel="icon" href="/images/jshuk-logo.png" type="image/png">
</head>
<body class="bg-light">

<!-- EMOTIONAL WELCOME SECTION -->
<section class="welcome-section" data-scroll>
  <div class="container">
    <div class="welcome-content">
      <div class="welcome-text">
        <h1 class="welcome-title">Looking for trusted Jewish services in your neighborhood?</h1>
        <p class="welcome-subtitle">Welcome to JShuk — your local marketplace to connect, discover, and support our community.</p>
        <div class="welcome-actions">
          <a href="<?= BASE_PATH ?>auth/register.php" class="btn-jshuk-primary" data-track="welcome_post_business" data-category="conversion">Post Your Business</a>
          <a href="<?= BASE_PATH ?>businesses.php" class="btn-jshuk-outline" data-track="welcome_find_service" data-category="navigation">Find a Local Service</a>
        </div>
        <div class="welcome-info">
          <a href="#about-jshuk" class="info-link" data-track="what_is_jshuk" data-category="navigation">
            <i class="fa-solid fa-info-circle"></i>
            What is JShuk?
          </a>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- SEARCH & FILTERS SECTION - ABOVE THE FOLD -->
<section class="search-filters-section" data-scroll>
  <div class="container">
    <div class="search-container">
      <form class="search-form" method="get" action="<?= BASE_PATH ?>search.php" autocomplete="off">
        <div class="search-row">
          <div class="search-box">
            <i class="fa fa-search"></i>
            <input type="text" name="q" placeholder="Search by name, service, or keyword..." 
                   value="<?= htmlspecialchars($_GET['q'] ?? '') ?>" class="search-input">
          </div>
          <select name="cat" class="category-select">
            <option value="">All Categories</option>
            <?php foreach (array_slice($categories, 0, 10) as $cat): ?>
              <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
            <?php endforeach; ?>
          </select>
          <button type="submit" class="btn-search">
            <i class="fa fa-search"></i> Search
          </button>
        </div>
      </form>
    </div>
  </div>
</section>

<!-- HERO SECTION -->
<section class="hero-section" data-scroll>
  <div class="container">
    <div class="hero-content">
      <div class="hero-text">
        <h2>Discover, Connect, Thrive.<br>Your Jewish Community Hub.</h2>
        <p class="lead">JShuk is your ultimate directory to explore local Jewish businesses, find community events, discover job opportunities, and engage with classifieds. Supporting local has never been easier.</p>
        <div class="hero-actions">
          <a href="<?= BASE_PATH ?>businesses.php" class="btn-jshuk-primary" data-track="hero_click" data-category="navigation">Browse Businesses</a>
          <div class="secondary-actions">
            <a href="<?= BASE_PATH ?>recruitment.php" class="btn-jshuk-outline" data-track="hero_click" data-category="navigation">Find Jobs</a>
            <a href="<?= BASE_PATH ?>classifieds.php" class="btn-jshuk-outline" data-track="hero_click" data-category="navigation">Browse Classifieds</a>
          </div>
        </div>
      </div>
      <div class="hero-visual">
        <div class="featured-preview">
          <?php foreach (array_slice($featured, 0, 3) as $index => $biz): ?>
            <div class="preview-item" style="animation-delay: <?= $index * 0.2 ?>s">
              <div class="preview-icon">
                <i class="fa-solid fa-store"></i>
              </div>
              <div class="preview-text">
                <strong><?= htmlspecialchars($biz['business_name']) ?></strong>
                <span><?= htmlspecialchars($biz['category_name']) ?></span>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- CATEGORY SHOWCASE -->
<section class="category-showcase-section" data-scroll>
  <div class="container">
    <h2 class="section-title">Browse by Categories</h2>
    <p class="section-subtitle">Discover local Jewish businesses in your favorite categories</p>
    
    <!-- Scrollable Categories Section -->
    <div class="scroll-categories py-4">
      <div class="scroll-wrapper d-flex gap-3 overflow-auto pb-2">
        <?php foreach ($categories as $category): ?>
          <a href="<?= BASE_PATH ?>search.php?category=<?= urlencode($category['id']) ?>" 
             class="category-pill text-center flex-shrink-0" 
             data-track="scroll_category_click" 
             data-category="navigation"
             data-label="<?= htmlspecialchars($category['name']) ?>"
             data-bs-toggle="tooltip" 
             data-bs-placement="bottom"
             title="<?= htmlspecialchars($category['name']) ?>">
            <div class="icon-circle">
              <i class="fa-solid <?= htmlspecialchars($category['icon'] ?: 'fa-briefcase') ?>"></i>
            </div>
            <span class="category-name"><?= htmlspecialchars($category['name']) ?></span>
            <?php if ($category['business_count'] > 0): ?>
              <span class="business-count"><?= $category['business_count'] ?></span>
            <?php endif; ?>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
    
    <div class="category-grid">
      <?php foreach (array_slice($categories, 0, 6) as $category): ?>
        <div class="category-card" data-category="<?= htmlspecialchars($category['name']) ?>">
          <a href="<?= BASE_PATH ?>search.php?category=<?= urlencode($category['id']) ?>" class="category-link" aria-label="Browse <?= htmlspecialchars($category['name']) ?> businesses" data-track="category_click" data-category="navigation">
            <div class="category-icon">
              <i class="fa-solid <?= htmlspecialchars($category['icon'] ?: 'fa-briefcase') ?>"></i>
            </div>
            <div class="category-content">
              <h3><?= htmlspecialchars($category['name']) ?></h3>
              <p class="category-count"><?= $category['business_count'] ?> Businesses</p>
              <p class="category-description">Discover local <?= htmlspecialchars(strtolower($category['name'])) ?> businesses.</p>
            </div>
          </a>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- FEATURED BUSINESSES SLIDER -->
<section class="featured-businesses-section" data-scroll>
  <div class="container">
    <h2 class="section-title">Premium Businesses</h2>
    <p class="section-subtitle">Featured businesses with enhanced visibility and premium features</p>
    <div class="businesses-slider">
      <div class="slider-container">
        <div class="slider-track">
          <?php foreach ($featured as $biz): ?>
            <div class="slider-item">
              <div class="business-card">
                <div class="business-content">
                  <h3 class="business-title"><?= htmlspecialchars($biz['business_name']) ?></h3>
                  <p class="business-description"><?= htmlspecialchars(mb_strimwidth($biz['description'], 0, 120, '...')) ?></p>
                  <div class="business-category">
                    <i class="fas fa-tag"></i>
                    <span><?= htmlspecialchars($biz['category_name']) ?></span>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        <button class="slider-control prev" aria-label="Previous">
          <i class="fa-solid fa-chevron-left"></i>
        </button>
        <button class="slider-control next" aria-label="Next">
          <i class="fa-solid fa-chevron-right"></i>
        </button>
      </div>
    </div>
    <div class="section-actions">
      <a href="<?= BASE_PATH ?>search.php?featured=1" class="btn-section" data-track="view_all_featured" data-category="navigation">View All Premium</a>
    </div>
  </div>
</section>

<!-- NEW BUSINESSES SECTION -->
<section class="new-businesses-section" data-scroll>
  <div class="container">
    <h2 class="section-title">New This Week</h2>
    <p class="section-subtitle">Recently added businesses to our community</p>
    <div class="businesses-grid">
      <?php foreach (array_slice($new, 0, 6) as $biz): ?>
        <div class="business-card-wrapper">
          <div class="business-card">
            <div class="business-content">
              <h3 class="business-title"><?= htmlspecialchars($biz['business_name']) ?></h3>
              <p class="business-description"><?= htmlspecialchars(mb_strimwidth($biz['description'], 0, 120, '...')) ?></p>
              <div class="business-category">
                <i class="fas fa-tag"></i>
                <span><?= htmlspecialchars($biz['category_name']) ?></span>
              </div>
            </div>
          </div>
          <div class="new-badge">Just Joined</div>
        </div>
      <?php endforeach; ?>
    </div>
    <div class="section-actions">
      <a href="<?= BASE_PATH ?>search.php?sort=newest" class="btn-section" data-track="view_all_new" data-category="navigation">Explore More New Listings</a>
    </div>
  </div>
</section>

<!-- TRUST SECTION -->
<section class="trust-section" data-scroll>
  <div class="container">
    <div class="trust-grid">
      <div class="trust-item">
        <div class="trust-icon">
          <i class="fa-solid fa-users"></i>
        </div>
        <div class="trust-content">
          <h3><?= number_format($stats['monthly_users']) ?>+</h3>
          <p>Monthly Users</p>
        </div>
      </div>
      <div class="trust-item">
        <div class="trust-icon">
          <i class="fa-brands fa-whatsapp"></i>
        </div>
        <div class="trust-content">
          <h3>1,000+</h3>
          <p>WhatsApp Status Views</p>
        </div>
      </div>
      <div class="trust-item">
        <div class="trust-icon">
          <i class="fa-solid fa-store"></i>
        </div>
        <div class="trust-content">
          <h3><?= number_format($stats['total_businesses']) ?>+</h3>
          <p>Businesses Listed</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- FAQ SECTION -->
<section class="faq-section" data-scroll id="about-jshuk">
  <div class="container">
    <h2 class="section-title">Frequently Asked Questions</h2>
    <div class="faq-grid">
      <div class="faq-item">
        <h3>How do I post a business?</h3>
        <p>Simply click "Post Your Business" above, create a free account, and fill out your business details. It takes just a few minutes to get started!</p>
      </div>
      <div class="faq-item">
        <h3>Is JShuk free to use?</h3>
        <p>Yes! Basic listings are completely free. We also offer premium features for businesses who want enhanced visibility and additional tools.</p>
      </div>
      <div class="faq-item">
        <h3>How do I find local Jewish businesses?</h3>
        <p>Use our search bar above or browse by categories. You can filter by location, service type, and more to find exactly what you need.</p>
      </div>
      <div class="faq-item">
        <h3>Are all businesses kosher-certified?</h3>
        <p>We list all Jewish-owned businesses. For kosher certification, please check with individual businesses as requirements vary.</p>
      </div>
    </div>
  </div>
</section>

<!-- WHATSAPP HOOK -->
<section class="whatsapp-section" data-scroll>
  <div class="container">
    <div class="whatsapp-content">
      <div class="whatsapp-icon">
        <i class="fa-brands fa-whatsapp"></i>
      </div>
      <div class="whatsapp-text">
        <h3>Get updates via WhatsApp status</h3>
        <p>Join our community and stay updated with the latest businesses and events</p>
        <a href="#" class="btn-whatsapp" data-track="whatsapp_join" data-category="conversion">Join Here</a>
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

<footer class="footer mt-auto py-3 bg-light">
  <div class="container text-center">
    <span class="text-muted">© <?= date('Y') ?> JShuk. All rights reserved.</span>
  </div>
</footer>

<!-- Bootstrap JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom JavaScript -->
<script src="/js/main.js" defer></script>

</body>
</html> 