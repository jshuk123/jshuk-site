<?php
/**
 * Featured Showcase Section
 * Combined carousel for sponsored slides AND featured businesses
 * Step 2: Enhanced to show both sponsored slides and featured businesses
 */

require_once __DIR__ . '/../includes/enhanced_carousel_functions.php';

$location = $_SESSION['user_location'] ?? 'all';
$today = date('Y-m-d');
$zone = 'homepage';

// Initialize combined slides array
$all_slides = [];

try {
    // QUERY A: Get sponsored slides from Enhanced Carousel Manager
    // First check if carousel_slides table exists
    $tableExists = $pdo->query("SHOW TABLES LIKE 'carousel_slides'")->rowCount() > 0;
    
    if ($tableExists) {
        // Check if the required columns exist
        $columns = $pdo->query("SHOW COLUMNS FROM carousel_slides")->fetchAll(PDO::FETCH_COLUMN);
        $hasRequiredColumns = in_array('active', $columns) && in_array('zone', $columns);
        
        if ($hasRequiredColumns) {
            $stmt = $pdo->prepare("
                SELECT 
                    id,
                    title,
                    subtitle,
                    image_url,
                    cta_text,
                    cta_link,
                    priority,
                    sponsored,
                    'carousel_slide' as slide_type
                FROM carousel_slides
                WHERE active = 1
                  AND zone = :zone
                  AND (start_date IS NULL OR start_date <= :today)
                  AND (end_date IS NULL OR end_date >= :today)
                ORDER BY priority DESC, id DESC
            ");
            $stmt->execute([':zone' => $zone, ':today' => $today]);
            $sponsored_slides = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Add sponsored slides to combined array with their priority
            foreach ($sponsored_slides as $slide) {
                $slide['priority'] = $slide['priority'] ?? 0;
                $all_slides[] = $slide;
            }
        } else {
            $sponsored_slides = [];
        }
    } else {
        $sponsored_slides = [];
    }
    
    // If no sponsored slides found, try a simpler query without parameters
    if (empty($sponsored_slides) && $tableExists) {
        try {
            $stmt = $pdo->query("
                SELECT 
                    id,
                    title,
                    subtitle,
                    image_url,
                    cta_text,
                    cta_link,
                    priority,
                    sponsored,
                    'carousel_slide' as slide_type
                FROM carousel_slides
                WHERE active = 1
                ORDER BY priority DESC, id DESC
                LIMIT 5
            ");
            $sponsored_slides = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Add sponsored slides to combined array with their priority
            foreach ($sponsored_slides as $slide) {
                $slide['priority'] = $slide['priority'] ?? 0;
                $all_slides[] = $slide;
            }
        } catch (PDOException $e) {
            // If even the simple query fails, just continue with empty sponsored slides
            $sponsored_slides = [];
        }
    }
    
    // QUERY B: Get featured businesses from directory
    try {
        $stmt = $pdo->prepare("
            SELECT 
                b.id,
                b.business_name as title,
                c.name as subtitle,
                COALESCE(bi.file_path, 'images/jshuk-logo.png') as image_url,
                'View Profile' as cta_text,
                CONCAT('business.php?id=', b.id) as cta_link,
                CASE 
                    WHEN u.subscription_tier = 'premium_plus' THEN 6
                    WHEN u.subscription_tier = 'premium' THEN 5
                    ELSE 4
                END as priority,
                1 as sponsored,
                'featured_business' as slide_type,
                u.subscription_tier
            FROM businesses b 
            LEFT JOIN business_categories c ON b.category_id = c.id 
            LEFT JOIN users u ON b.user_id = u.id
            LEFT JOIN business_images bi ON b.id = bi.business_id AND bi.sort_order = 0
            WHERE b.status = 'active' 
            AND u.subscription_tier IN ('premium', 'premium_plus')
            ORDER BY 
                CASE u.subscription_tier 
                    WHEN 'premium_plus' THEN 1 
                    WHEN 'premium' THEN 2 
                    ELSE 3 
                END,
                b.created_at DESC 
            LIMIT 10
        ");
        $stmt->execute();
        $featured_businesses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Add featured businesses to combined array
        foreach ($featured_businesses as $business) {
            $all_slides[] = $business;
        }
    } catch (PDOException $e) {
        // If featured businesses query fails, just continue with empty array
        $featured_businesses = [];
    }
    
} catch (PDOException $e) {
    echo "<div style='background:#f8d7da;color:#721c24;padding:10px;z-index:9999;position:relative;'>
        ❌ SQL Error: " . htmlspecialchars($e->getMessage()) . "<br>
        File: " . basename(__FILE__) . "<br>
        Line: " . $e->getLine() . "</div>";
    $all_slides = [];
}

// Sort the combined array by priority (highest first)
usort($all_slides, function($a, $b) {
    return ($b['priority'] ?? 0) - ($a['priority'] ?? 0);
});

// Filter slides to only those with a valid image file
$valid_slides = array_filter($all_slides, function($slide) {
    if (empty($slide['image_url'])) return false;
    if (strpos($slide['image_url'], 'data:') !== false) return false;
    
    // For featured businesses, check if the image exists
    if ($slide['slide_type'] === 'featured_business') {
        $image_path = __DIR__ . '/../' . ltrim($slide['image_url'], '/');
        return file_exists($image_path);
    }
    
    // For carousel slides, check if the image exists
    return file_exists(__DIR__ . '/../' . ltrim($slide['image_url'], '/'));
});

$numSlides = count($valid_slides);

// If no valid slides, show placeholder
if ($numSlides === 0) {
    $valid_slides = [
        [
            'id' => 'placeholder',
            'title' => 'No Featured Businesses Yet',
            'subtitle' => 'Featured businesses will appear here soon!',
            'image_url' => 'images/jshuk-logo.png',
            'cta_text' => 'Explore Now',
            'cta_link' => 'businesses.php',
            'slide_type' => 'placeholder'
        ]
    ];
    $numSlides = 1;
}

$loop = $numSlides > 1;
?>

<section id="featured-showcase" class="featured-showcase-section">
    <div class="container">
        <h2 class="section-title">Community Highlights</h2>
        <div class="carousel-wrapper">
            <div class="swiper-container featured-showcase-carousel">
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
                                        <?php if ($slide['slide_type'] === 'featured_business'): ?>
                                            <span class="featured-tag">Featured</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-block">
                                        <h3 class="carousel-title"><?= htmlspecialchars($slide['title']) ?></h3>
                                        <?php if (!empty($slide['subtitle']) && $slide['subtitle'] !== $slide['title']): ?>
                                            <p class="carousel-subtitle"><?= htmlspecialchars($slide['subtitle']) ?></p>
                                        <?php endif; ?>
                                        <?php if (!empty($slide['cta_link'])): ?>
                                            <a href="<?= htmlspecialchars($slide['cta_link']) ?>" class="carousel-cta">
                                                <?= htmlspecialchars($slide['cta_text'] ?? 'View Profile') ?>
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
    new Swiper('.featured-showcase-carousel', {
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
.featured-showcase-section {
  padding: 4rem 0;
  background: #f8f9fa;
}
.section-title {
  text-align: center;
  font-size: 2.5rem;
  font-weight: 700;
  color: #2c3e50;
  margin-bottom: 2rem;
}
.carousel-wrapper {
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
.featured-tag {
  position: absolute;
  top: 16px;
  left: 16px;
  background: #ffc107;
  color: #222;
  font-weight: 700;
  padding: 6px 16px;
  border-radius: 20px;
  font-size: 1rem;
  box-shadow: 0 2px 8px rgba(0,0,0,0.08);
  z-index: 3;
  letter-spacing: 1px;
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
  margin-top: 10px;
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
    height: 400px;
  }
  .carousel-title {
    font-size: 1.5rem;
  }
  .carousel-subtitle {
    font-size: 1rem;
  }
}
</style> 