<?php
/**
 * Enhanced Carousel Section
 * JShuk Advanced Carousel Management System
 * Phase 5: Enhanced Frontend Display - MOBILE FIXED VERSION
 * 
 * NOTE: This carousel code is being moved to a new "Featured Showcase" section in Step 2
 */

require_once __DIR__ . '/../includes/enhanced_carousel_functions.php';

// 🔥 BULLETPROOF CAROUSEL SLIDE LOADER
$location = $_SESSION['user_location'] ?? 'all';
$today = date('Y-m-d');
$zone = 'homepage';

try {
    // Check if sponsored column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM carousel_slides LIKE 'sponsored'");
    $hasSponsoredColumn = $stmt->rowCount() > 0;
    
    // Build ORDER BY clause based on available columns
    $orderBy = "sort_order DESC";
    if ($hasSponsoredColumn) {
        $orderBy .= ", sponsored DESC";
    }
    $orderBy .= ", id DESC";
    
    // Start with the simplest possible query to eliminate parameter binding issues
    $query = $pdo->prepare("
        SELECT * FROM carousel_slides
        WHERE is_active = 1
          AND zone = :zone
        ORDER BY {$orderBy}
    ");

    $query->execute([
        ':zone' => $zone
    ]);

    $slides = $query->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo "<div style='background:#f8d7da;color:#721c24;padding:10px;z-index:9999;position:relative;'>
        ❌ SQL Error: " . htmlspecialchars($e->getMessage()) . "</div>";
    $slides = [];
}

// Filter slides to only those with a valid image file
$valid_slides = array_filter($slides, function($slide) {
    return !empty($slide['image_url']) && strpos($slide['image_url'], 'data:') === false && file_exists(__DIR__ . '/../' . $slide['image_url']);
});
$numSlides = count($valid_slides);

// If no valid slides, show placeholder
if ($numSlides === 0) {
    $valid_slides = [
        [
            'id' => 'placeholder',
            'title' => 'Welcome to JShuk',
            'subtitle' => 'Your Jewish Community Hub',
            'image_url' => 'images/jshuk-logo.png',
            'cta_text' => 'Explore Now',
            'cta_link' => 'businesses.php'
        ]
    ];
    $numSlides = 1;
}

$loop = $numSlides > 1;
?>

<!-- STATIC HERO SECTION (Converted from Carousel) -->
<section class="hero-section">
  <div class="hero-content">
    <h1 class="hero-title">Find Trusted Jewish Businesses in London. Instantly.</h1>
    
    <!-- INTEGRATED SEARCH FORM -->
    <div class="hero-search-container">
      <?php
      $location_filter = $_GET['location'] ?? '';
      $category_filter = $_GET['category'] ?? '';
      $search_query = $_GET['search'] ?? '';
      ?>
      <form action="/businesses.php" method="GET" class="hero-search-form" role="search">
        <select name="location" class="form-select" aria-label="Select location">
          <option value="" disabled selected>📍 Select a Location</option>
          <option value="manchester" <?= $location_filter === 'manchester' ? 'selected' : '' ?>>Manchester</option>
          <option value="london" <?= $location_filter === 'london' ? 'selected' : '' ?>>London</option>
          <option value="stamford-hill" <?= $location_filter === 'stamford-hill' ? 'selected' : '' ?>>Stamford Hill</option>
        </select>
        <select name="category" class="form-select" aria-label="Select category">
          <option value="" disabled selected>🗂 Select a Category</option>
          <?php if (!empty($categories)): ?>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= $cat['id'] ?>" <?= $category_filter == $cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
            <?php endforeach; ?>
          <?php endif; ?>
        </select>
        <input type="text" name="search" class="form-control" placeholder="🔍 Search businesses..." value="<?= htmlspecialchars($search_query) ?>" />
        <button type="submit" class="btn btn-search" aria-label="Search">
          <i class="fa fa-search"></i>
          <span class="d-none d-md-inline">Search</span>
        </button>
      </form>
    </div>
  </div>
</section>

<style>
/* Hero Section Styles */
.hero-section {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 70vh;
  color: white;
  text-align: center;
  padding: 2rem;
}

.hero-section::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-image: linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)), url('/images/jshuk-logo.png');
  background-size: cover;
  background-position: center;
  z-index: -1;
}

.hero-content {
  max-width: 800px;
  z-index: 2;
}

.hero-title {
  font-size: 3rem;
  font-weight: 700;
  margin-bottom: 2rem;
  text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.7);
  line-height: 1.2;
}

.hero-search-container {
  margin-top: 2rem;
}

.hero-search-form {
  display: flex;
  gap: 1rem;
  max-width: 600px;
  margin: 0 auto;
  background: rgba(255, 255, 255, 0.95);
  padding: 1.5rem;
  border-radius: 12px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
}

.hero-search-form .form-select,
.hero-search-form .form-control {
  border: 1px solid #ddd;
  border-radius: 8px;
  padding: 0.75rem;
  font-size: 1rem;
}

.hero-search-form .btn-search {
  background: #007bff;
  color: white;
  border: none;
  border-radius: 8px;
  padding: 0.75rem 1.5rem;
  font-weight: 600;
  transition: background-color 0.3s ease;
}

.hero-search-form .btn-search:hover {
  background: #0056b3;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .hero-title {
    font-size: 2rem;
  }
  
  .hero-search-form {
    flex-direction: column;
    gap: 0.75rem;
  }
}
</style>

<!-- BACKUP OF ORIGINAL CAROUSEL CODE (FOR STEP 2) -->
<!--
ORIGINAL CAROUSEL CODE - TO BE MOVED TO FEATURED SHOWCASE SECTION:

<section class="carousel-section">
  <div class="swiper-container enhanced-homepage-carousel">
    <div class="swiper-wrapper">
      <?php foreach ($valid_slides as $slide): ?>
        <?php if (!empty($slide['image_url'])): ?>
          <div class="swiper-slide">
            <div class="carousel-content">
              <div class="image-container">
                <img
                  src="/<?= ltrim($slide['image_url'], '/') ?>"
                  alt="<?= htmlspecialchars($slide['title']) ?>"
                  class="carousel-img"
                  loading="eager"
                />
              </div>
              <div class="text-block">
                <h2 class="carousel-title"><?= htmlspecialchars($slide['title']) ?></h2>
                <?php if (!empty($slide['subtitle']) && $slide['subtitle'] !== $slide['title']): ?>
                  <p class="carousel-subtitle"><?= htmlspecialchars($slide['subtitle']) ?></p>
                <?php endif; ?>
                <?php if (!empty($slide['cta_text']) && !empty($slide['cta_link'])): ?>
                  <a href="<?= htmlspecialchars($slide['cta_link']) ?>" class="carousel-cta">
                    <?= htmlspecialchars($slide['cta_text']) ?>
                  </a>
                <?php endif; ?>
              </div>
            </div>
          </div>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>

    <div class="swiper-button-prev"></div>
    <div class="swiper-button-next"></div>
    <div class="swiper-pagination"></div>
  </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.js"></script>
<script>
  document.addEventListener("DOMContentLoaded", function () {
    new Swiper('.enhanced-homepage-carousel', {
      loop: true,
      autoplay: {
        delay: 5000,
        disableOnInteraction: false
      },
      pagination: {
        el: '.swiper-pagination',
        clickable: true
      },
      navigation: {
        nextEl: '.swiper-button-next',
        prevEl: '.swiper-button-prev'
      },
      effect: 'slide'
    });
  });
</script>

<style>
/* Original carousel styles - to be moved to new location */
.swiper-container {
  width: 100%;
  height: 600px;
  position: relative;
  overflow: hidden;
}

.swiper-wrapper {
  display: flex;
  transition-property: transform;
  box-sizing: content-box;
}

.swiper-slide {
  flex-shrink: 0;
  width: 100%;
  height: 100%;
  position: relative;
  background: #fff;
}

.carousel-content {
  position: relative;
  height: 100%;
  width: 100%;
  overflow: hidden;
}

.image-container {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  overflow: hidden;
}

.carousel-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.text-block {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.4);
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  color: white;
  text-align: center;
  padding: 20px;
  z-index: 2;
}

.carousel-title {
  font-size: 2.5rem;
  font-weight: 700;
  margin-bottom: 15px;
  text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.7);
  line-height: 1.2;
}

.carousel-subtitle {
  font-size: 1.2rem;
  margin-bottom: 20px;
  text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.7);
}

.carousel-cta {
  display: inline-block;
  background: #007bff;
  color: white;
  padding: 12px 24px;
  text-decoration: none;
  border-radius: 6px;
  font-weight: 600;
  transition: background-color 0.3s ease;
}

.carousel-cta:hover {
  background: #0056b3;
  color: white;
  text-decoration: none;
}

.swiper-button-prev,
.swiper-button-next {
  color: white;
  background: rgba(0, 0, 0, 0.5);
  width: 50px;
  height: 50px;
  border-radius: 50%;
  margin-top: -25px;
}

.swiper-pagination-bullet {
  background: white;
  opacity: 0.7;
}

.swiper-pagination-bullet-active {
  opacity: 1;
}

@media (max-width: 768px) {
  .swiper-container {
    height: 400px;
  }
  
  .carousel-title {
    font-size: 1.8rem;
  }
  
  .carousel-subtitle {
    font-size: 1rem;
  }
  
  .swiper-button-prev,
  .swiper-button-next {
    width: 40px;
    height: 40px;
    margin-top: -20px;
  }
}
</style>
--> 