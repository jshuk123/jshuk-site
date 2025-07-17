<?php
/**
 * Featured Showcase Section
 * This section will contain the carousel moved from the hero section
 * Implementation will be completed in Step 2
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
            'title' => 'Featured Businesses',
            'subtitle' => 'Discover trusted Jewish businesses in your area',
            'image_url' => 'images/jshuk-logo.png',
            'cta_text' => 'Explore Now',
            'cta_link' => 'businesses.php'
        ]
    ];
    $numSlides = 1;
}

$loop = $numSlides > 1;
?>

<!-- FEATURED SHOWCASE SECTION -->
<section class="featured-showcase-section">
  <div class="container">
    <div class="section-header">
      <h2 class="section-title">Featured Businesses</h2>
      <p class="section-subtitle">Discover trusted Jewish businesses in your community</p>
    </div>
    
    <!-- CAROUSEL SECTION (Moved from Hero) -->
    <div class="carousel-section">
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
                    <h3 class="carousel-title"><?= htmlspecialchars($slide['title']) ?></h3>
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
    </div>
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
/* Featured Showcase Section Styles */
.featured-showcase-section {
  padding: 4rem 0;
  background: #f8f9fa;
}

.section-header {
  text-align: center;
  margin-bottom: 3rem;
}

.section-title {
  font-size: 2.5rem;
  font-weight: 700;
  color: #2c3e50;
  margin-bottom: 1rem;
}

.section-subtitle {
  font-size: 1.2rem;
  color: #6c757d;
  max-width: 600px;
  margin: 0 auto;
}

/* Carousel Styles */
.carousel-section {
  max-width: 1000px;
  margin: 0 auto;
}

.swiper-container {
  width: 100%;
  height: 500px;
  position: relative;
  overflow: hidden;
  border-radius: 12px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
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
  font-size: 2rem;
  font-weight: 700;
  margin-bottom: 15px;
  text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.7);
  line-height: 1.2;
}

.carousel-subtitle {
  font-size: 1.1rem;
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
  .featured-showcase-section {
    padding: 2rem 0;
  }
  
  .section-title {
    font-size: 2rem;
  }
  
  .swiper-container {
    height: 350px;
  }
  
  .carousel-title {
    font-size: 1.5rem;
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