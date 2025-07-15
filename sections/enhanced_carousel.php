<?php
/**
 * Enhanced Carousel Section
 * JShuk Advanced Carousel Management System
 * Phase 5: Enhanced Frontend Display - MOBILE FIXED VERSION
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
            'id' => 0,
            'title' => 'Welcome to JShuk',
            'subtitle' => 'Your Jewish Community Hub - Add your first carousel slide in the admin panel',
            'image_url' => 'data:image/svg+xml;base64,' . base64_encode('<svg width="1920" height="600" xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="grad" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" style="stop-color:#667eea;stop-opacity:1" /><stop offset="100%" style="stop-color:#764ba2;stop-opacity:1" /></linearGradient></defs><rect width="100%" height="100%" fill="url(#grad)"/><text x="50%" y="45%" font-family="Arial, sans-serif" font-size="48" fill="white" text-anchor="middle">Welcome to JShuk</text><text x="50%" y="55%" font-family="Arial, sans-serif" font-size="24" fill="white" text-anchor="middle">Your Jewish Community Hub</text></svg>'),
            'cta_text' => 'Add Your First Slide',
            'cta_link' => 'admin/enhanced_carousel_manager.php',
            'sponsored' => 0
        ]
    ];
    $numSlides = 1;
}

// Set loop mode based on slide count
$loop = count($valid_slides) >= 3 ? 'true' : 'false';

// Generate carousel HTML with enhanced features
// Add .swiper-container wrapper
?>
<link
  rel="stylesheet"
  href="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.css"
/>

<section class="carousel-section">
  <div class="swiper-container enhanced-homepage-carousel">
    <div class="swiper-wrapper">
      <?php foreach ($valid_slides as $slide): ?>
        <?php if (!empty($slide['image_url'])): ?>
          <div class="swiper-slide">
            <!-- ✅ FIXED: Proper container structure for mobile -->
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
      effect: 'slide' // or 'fade'
    });
  });
</script>

<style>
/* ✅ FIXED: Mobile-first responsive carousel styles */

/* Base carousel container */
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
  /* fallback background */
}

/* ✅ FIXED: Proper carousel content structure - OVERLAY LAYOUT */
.carousel-content {
  position: relative;
  height: 100%;
  width: 100%;
  overflow: hidden;
}

/* ✅ FIXED: Image container - FULL WIDTH */
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

/* ✅ FIXED: Text overlay - POSITIONED OVER IMAGE */
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

/* Text styling */
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
  opacity: 0.95;
  text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.7);
  line-height: 1.4;
}

.carousel-cta {
  display: inline-block;
  background: linear-gradient(45deg, #ff6b6b, #ff8e53);
  color: white;
  padding: 15px 25px;
  border-radius: 50px;
  text-decoration: none;
  font-weight: 600;
  font-size: 1.1rem;
  transition: all 0.3s ease;
  box-shadow: 0 4px 15px rgba(255, 107, 107, 0.3);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.carousel-cta:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(255, 107, 107, 0.4);
  color: white;
  text-decoration: none;
}

/* Navigation and pagination */
.swiper-button-prev,
.swiper-button-next {
  color: white;
  background: rgba(255, 255, 255, 0.2);
  width: 50px;
  height: 50px;
  border-radius: 50%;
  backdrop-filter: blur(10px);
  transition: all 0.3s ease;
}

.swiper-button-prev:hover,
.swiper-button-next:hover {
  background: rgba(255, 255, 255, 0.3);
  transform: scale(1.1);
}

.swiper-button-prev::after,
.swiper-button-next::after {
  font-size: 20px;
  font-weight: bold;
}

.swiper-pagination {
  bottom: 20px;
}

.swiper-pagination .swiper-pagination-bullet {
  width: 12px;
  height: 12px;
  background: rgba(255, 255, 255, 0.5);
  opacity: 1;
  transition: all 0.3s ease;
}

.swiper-pagination .swiper-pagination-bullet-active {
  background: #ff6b6b;
  transform: scale(1.2);
}

/* ✅ FIXED: Desktop layout - side by side */
@media (min-width: 768px) {
  .carousel-content {
    flex-direction: row; /* Side-by-side on desktop */
  }
  
  .image-container {
    flex: 1;
    aspect-ratio: auto;
    height: 100%;
  }
  
  .text-block {
    flex: 1;
    position: relative;
    background: rgba(0, 0, 0, 0.6);
    padding: 40px;
  }
  
  .carousel-title {
    font-size: 3.5rem;
    margin-bottom: 20px;
  }
  
  .carousel-subtitle {
    font-size: 1.4rem;
    margin-bottom: 30px;
  }
  
  .carousel-cta {
    padding: 18px 35px;
    font-size: 1.2rem;
  }
  
  .swiper-button-prev,
  .swiper-button-next {
    width: 60px;
    height: 60px;
  }
  
  .swiper-button-prev::after,
  .swiper-button-next::after {
    font-size: 24px;
  }
  
  .swiper-pagination {
    bottom: 30px;
  }
  
  .swiper-pagination .swiper-pagination-bullet {
    width: 14px;
    height: 14px;
  }
}

/* ✅ FIXED: Tablet adjustments */
@media (max-width: 767px) {
  .swiper-container {
    height: 450px;
  }
  
  .carousel-title {
    font-size: 2rem;
  }
  
  .carousel-subtitle {
    font-size: 1.1rem;
  }
}

/* ✅ FIXED: Small mobile adjustments */
@media (max-width: 480px) {
  .swiper-container {
    height: 400px;
  }
  
  .carousel-title {
    font-size: 1.8rem;
  }
  
  .carousel-subtitle {
    font-size: 1rem;
  }
  
  .carousel-cta {
    padding: 12px 20px;
    font-size: 1rem;
  }
  
  .text-block {
    padding: 15px;
  }
}

/* Loading state */
.carousel-section.loading {
  background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
  background-size: 200% 100%;
  animation: loading 1.5s infinite;
}

@keyframes loading {
  0% {
    background-position: 200% 0;
  }
  100% {
    background-position: -200% 0;
  }
}

/* Accessibility improvements */
.carousel-cta:focus {
  outline: 2px solid #fff;
  outline-offset: 2px;
}

.swiper-button-prev:focus,
.swiper-button-next:focus {
  outline: 2px solid #fff;
  outline-offset: 2px;
}

/* High contrast mode support */
@media (prefers-contrast: high) {
  .text-block {
    background: rgba(0, 0, 0, 0.8);
  }
  
  .carousel-cta {
    background: #fff;
    color: #000;
  }
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
  .carousel-cta:hover {
    transform: none;
  }
  
  .swiper-button-prev:hover,
  .swiper-button-next:hover {
    transform: none;
  }
}

/* ✅ FIXED: Remove conflicting styles */
.carousel-item img, .swiper-slide img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

#carousel-loader {
  position: absolute;
  top: 200px;
  left: 50%;
  transform: translateX(-50%);
  z-index: 9999;
  color: white;
}

.carousel-ready #carousel-loader {
  display: none;
}

/* ✅ FIX: Mobile-specific carousel optimizations */
@media (max-width: 768px) {
  .swiper-container {
    height: 300px !important;
  }
  
  .swiper-slide,
  .carousel-content {
    height: 300px !important;
  }
  
  .carousel-title {
    font-size: 1.8rem !important;
    line-height: 1.2 !important;
  }
  
  .carousel-subtitle {
    font-size: 1rem !important;
    line-height: 1.3 !important;
  }
  
  .carousel-cta {
    padding: 12px 20px !important;
    font-size: 1rem !important;
  }
}
</style>

<script>
// Enhanced carousel initialization
document.addEventListener('DOMContentLoaded', function() {
    // Gather all image URLs
    const slides = Array.from(document.querySelectorAll('.swiper-slide img'));
    const imageUrls = slides.map(img => img.src).filter(Boolean);

    let loaded = 0;
    if (imageUrls.length === 0) {
        showSlidesAndInit();
    } else {
        imageUrls.forEach(url => {
            const img = new Image();
            img.src = url;
            img.onload = img.onerror = () => {
                loaded++;
                if (loaded === imageUrls.length) {
                    showSlidesAndInit();
                }
            };
        });
    }

    function showSlidesAndInit() {
        // Add .carousel-ready to <body>
        document.body.classList.add('carousel-ready');
        // Now initialize Swiper
        initEnhancedCarousel();
    }

    // Move Swiper initialization into a function
    window.initEnhancedCarousel = function() {
        console.log('🔍 Initializing enhanced carousel...');
        const carousel = document.querySelector('.enhanced-homepage-carousel');
        if (!carousel) {
            console.error('❌ Enhanced carousel element not found');
            return;
        }
        
        console.log('✅ Enhanced carousel element found, creating Swiper instance...');
        
        try {
            const swiper = new Swiper('.enhanced-homepage-carousel', {
                loop: <?= $loop ?>,
                autoplay: {
                    delay: 6000,
                    disableOnInteraction: false,
                    pauseOnMouseEnter: true,
                },
                effect: 'slide',
                speed: 1000,
                pagination: {
                    el: '.swiper-pagination',
                    clickable: true,
                    dynamicBullets: true,
                },
                navigation: {
                    nextEl: '.swiper-button-next',
                    prevEl: '.swiper-button-prev',
                },
                on: {
                    init: function() {
                        console.log('✅ Enhanced Swiper initialized successfully');
                        // Remove loading state
                        const carouselSection = document.querySelector('.carousel-section');
                        if (carouselSection) {
                            carouselSection.classList.remove('loading');
                        }
                    },
                    slideChange: function() {
                        console.log('🔄 Enhanced slide changed to: ' + this.activeIndex);
                    }
                }
            });
            
            console.log('🎉 Enhanced carousel setup complete');
            
            // Add keyboard navigation
            document.addEventListener('keydown', function(e) {
                if (e.key === 'ArrowLeft') {
                    swiper.slidePrev();
                } else if (e.key === 'ArrowRight') {
                    swiper.slideNext();
                }
            });
            
            // Pause autoplay on hover
            carousel.addEventListener('mouseenter', function() {
                swiper.autoplay.stop();
            });
            
            carousel.addEventListener('mouseleave', function() {
                swiper.autoplay.start();
            });
            
            // Add touch gestures for mobile
            let touchStartX = 0;
            let touchEndX = 0;
            
            carousel.addEventListener('touchstart', function(e) {
                touchStartX = e.changedTouches[0].screenX;
            });
            
            carousel.addEventListener('touchend', function(e) {
                touchEndX = e.changedTouches[0].screenX;
                handleSwipe();
            });
            
            function handleSwipe() {
                const swipeThreshold = 50;
                const diff = touchStartX - touchEndX;
                
                if (Math.abs(diff) > swipeThreshold) {
                    if (diff > 0) {
                        swiper.slideNext();
                    } else {
                        swiper.slidePrev();
                    }
                }
            }
            
        } catch (error) {
            console.error('❌ Error initializing enhanced Swiper:', error);
        }
    }

    // Analytics tracking
    document.addEventListener('DOMContentLoaded', function() {
        // Track CTA clicks
        document.querySelectorAll('.carousel-cta').forEach(function(cta) {
            cta.addEventListener('click', function(e) {
                const slideId = this.getAttribute('data-slide-id');
                if (slideId) {
                    // Log click event
                    fetch('/api/carousel-analytics.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({
                            slide_id: slideId,
                            event_type: 'click'
                        })
                    }).catch(error => {
                        console.log('Analytics tracking failed:', error);
                    });
                    
                    // Google Analytics tracking (if available)
                    if (typeof gtag !== 'undefined') {
                        gtag('event', 'carousel_click', {
                            'slide_id': slideId,
                            'slide_title': this.closest('.text-block').querySelector('.carousel-title')?.textContent || 'Unknown'
                        });
                    }
                }
            });
        });
        
        // Track slide impressions
        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    const slideId = entry.target.getAttribute('data-slide-id');
                    if (slideId) {
                        fetch('/api/carousel-analytics.php', {
                            method: 'POST',
                            headers: {'Content-Type': 'application/json'},
                            body: JSON.stringify({
                                slide_id: slideId,
                                event_type: 'impression'
                            })
                        }).catch(error => {
                            console.log('Analytics tracking failed:', error);
                        });
                    }
                }
            });
        });
        
        document.querySelectorAll('.swiper-slide').forEach(function(slide) {
            observer.observe(slide);
        });
    });
});
</script> 