<?php
/**
 * Enhanced Carousel Section
 * JShuk Advanced Carousel Management System
 * Phase 5: Enhanced Frontend Display
 */

require_once __DIR__ . '/../includes/enhanced_carousel_functions.php';

// 🔥 BULLETPROOF CAROUSEL SLIDE LOADER
$location = $_SESSION['user_location'] ?? 'all';
$today = date('Y-m-d');
$zone = 'homepage';

try {
    // Start with the simplest possible query to eliminate parameter binding issues
    $query = $pdo->prepare("
        SELECT * FROM carousel_slides
        WHERE active = 1
          AND zone = :zone
        ORDER BY priority DESC, sponsored DESC, id DESC
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
echo '<section class="carousel-section" data-scroll>';
echo '<div class="carousel-wrapper">';
echo '<div class="swiper enhanced-homepage-carousel">';
echo '<div class="swiper-wrapper">';

// 💡 DYNAMIC SWIPER-COMPATIBLE HTML RENDERING
foreach ($valid_slides as $index => $slide) {
    $image = '/' . ltrim($slide['image_url'], '/');
    $title = htmlspecialchars($slide['title']);
    $subtitle = htmlspecialchars($slide['subtitle'] ?? '');
    $cta = trim($slide['cta_text'] ?? '');
    $link = trim($slide['cta_link'] ?? '');
    $sponsored = $slide['sponsored'] == 1;
    
    echo '<div class="swiper-slide carousel-slide" style="background-image: url(\'' . $image . '\')" data-slide-id="' . $slide['id'] . '">';
    // Preload background image
    echo '<img src="' . htmlspecialchars($image) . '" alt="" style="display:none;" loading="eager" />';
    echo '<div class="carousel-overlay">';
    echo '<div class="carousel-content">';
    echo '<h2 class="carousel-title">' . $title . '</h2>';
    
    if (!empty($subtitle)) {
        echo '<p class="carousel-subtitle">' . $subtitle . '</p>';
    }
    
    if (!empty($cta) && !empty($link)) {
        echo '<a href="' . htmlspecialchars($link) . '" class="carousel-cta" data-slide-id="' . $slide['id'] . '">';
        echo htmlspecialchars($cta);
        echo '</a>';
    }
    
    if ($sponsored) {
        echo '<span class="sponsored-badge">Sponsored</span>';
    }
    
    echo '</div>'; // carousel-content
    echo '</div>'; // carousel-overlay
    echo '</div>'; // swiper-slide
}

echo '</div>'; // swiper-wrapper

// Add navigation and pagination
echo '<div class="swiper-button-prev carousel-nav-prev"></div>';
echo '<div class="swiper-button-next carousel-nav-next"></div>';
echo '<div class="swiper-pagination carousel-pagination"></div>';

echo '</div>'; // swiper
echo '</div>'; // carousel-wrapper
echo '</section>';
?>

<style>
/* Enhanced Carousel Styles */
.carousel-section {
    margin: 0;
    padding: 0;
    background: #000;
    position: relative;
    overflow: hidden;
    min-height: 600px;
}

.carousel-wrapper {
    position: relative;
    width: 100%;
    height: 100%;
}

.enhanced-homepage-carousel {
    width: 100%;
    height: 600px;
    border-radius: 0;
}

.carousel-slide {
    height: 600px;
    background-size: cover !important;
    background-repeat: no-repeat !important;
    background-position: center !important;
    background-color: #111;
    opacity: 0;
    transition: opacity 0.5s ease-in-out;
}

.carousel-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(
        135deg,
        rgba(0, 0, 0, 0.4) 0%,
        rgba(0, 0, 0, 0.2) 50%,
        rgba(0, 0, 0, 0.6) 100%
    );
    display: flex;
    align-items: center;
    justify-content: center;
}

.carousel-content {
    max-width: 800px;
    padding: 40px;
    color: white;
    text-align: center;
    z-index: 2;
    position: relative;
}

.carousel-title {
    font-size: 3.5rem;
    font-weight: 700;
    margin-bottom: 20px;
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
    line-height: 1.2;
    animation: fadeInUp 0.8s ease-out;
}

.carousel-subtitle {
    font-size: 1.4rem;
    margin-bottom: 30px;
    opacity: 0.95;
    text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
    line-height: 1.4;
    animation: fadeInUp 0.8s ease-out 0.2s both;
}

.carousel-cta {
    display: inline-block;
    background: linear-gradient(45deg, #ff6b6b, #ff8e53);
    color: white;
    padding: 18px 35px;
    border-radius: 50px;
    text-decoration: none;
    font-weight: 600;
    font-size: 1.2rem;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(255, 107, 107, 0.3);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    animation: fadeInUp 0.8s ease-out 0.4s both;
}

.carousel-cta:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(255, 107, 107, 0.4);
    color: white;
    text-decoration: none;
}

/* Sponsored Badge */
.sponsored-badge {
    position: absolute;
    top: 20px;
    right: 20px;
    background: linear-gradient(45deg, #ff6b6b, #ff8e53);
    color: white;
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    animation: fadeIn 0.8s ease-out 0.6s both;
}

/* Navigation Buttons */
.carousel-nav-prev,
.carousel-nav-next {
    color: white;
    background: rgba(255, 255, 255, 0.2);
    width: 60px;
    height: 60px;
    border-radius: 50%;
    backdrop-filter: blur(10px);
    transition: all 0.3s ease;
}

.carousel-nav-prev:hover,
.carousel-nav-next:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: scale(1.1);
}

.carousel-nav-prev::after,
.carousel-nav-next::after {
    font-size: 24px;
    font-weight: bold;
}

/* Pagination */
.carousel-pagination {
    bottom: 30px;
}

.carousel-pagination .swiper-pagination-bullet {
    width: 14px;
    height: 14px;
    background: rgba(255, 255, 255, 0.5);
    opacity: 1;
    transition: all 0.3s ease;
}

.carousel-pagination .swiper-pagination-bullet-active {
    background: #ff6b6b;
    transform: scale(1.2);
}

/* Progress Bar */
.carousel-progress {
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    height: 3px;
    background: rgba(255, 255, 255, 0.2);
    z-index: 10;
}

.carousel-progress-bar {
    height: 100%;
    background: linear-gradient(45deg, #ff6b6b, #ff8e53);
    width: 0%;
    transition: width 0.1s linear;
}

/* Animations */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

/* Responsive Design */
@media (max-width: 768px) {
    .enhanced-homepage-carousel {
        height: 450px;
    }
    
    .carousel-slide {
        min-height: 450px;
    }
    
    .carousel-content {
        padding: 20px;
    }
    
    .carousel-title {
        font-size: 2.5rem;
        margin-bottom: 15px;
    }
    
    .carousel-subtitle {
        font-size: 1.2rem;
        margin-bottom: 20px;
    }
    
    .carousel-cta {
        padding: 15px 25px;
        font-size: 1.1rem;
    }
    
    .carousel-nav-prev,
    .carousel-nav-next {
        width: 50px;
        height: 50px;
    }
    
    .carousel-nav-prev::after,
    .carousel-nav-next::after {
        font-size: 20px;
    }
    
    .sponsored-badge {
        top: 10px;
        right: 10px;
        padding: 6px 12px;
        font-size: 0.7rem;
    }
}

@media (max-width: 480px) {
    .enhanced-homepage-carousel {
        height: 400px;
    }
    
    .carousel-slide {
        min-height: 400px;
    }
    
    .carousel-title {
        font-size: 2rem;
    }
    
    .carousel-subtitle {
        font-size: 1.1rem;
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

/* Enhanced Swiper Configuration */
.enhanced-homepage-carousel .swiper-slide {
    transition: transform 0.3s ease;
}

.enhanced-homepage-carousel .swiper-slide-active {
    transform: scale(1.02);
}

/* Accessibility improvements */
.carousel-cta:focus {
    outline: 2px solid #fff;
    outline-offset: 2px;
}

.carousel-nav-prev:focus,
.carousel-nav-next:focus {
    outline: 2px solid #fff;
    outline-offset: 2px;
}

/* High contrast mode support */
@media (prefers-contrast: high) {
    .carousel-overlay {
        background: rgba(0, 0, 0, 0.8);
    }
    
    .carousel-cta {
        background: #fff;
        color: #000;
    }
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
    .carousel-title,
    .carousel-subtitle,
    .carousel-cta,
    .sponsored-badge {
        animation: none;
    }
    
    .carousel-cta:hover {
        transform: none;
    }
    
    .carousel-nav-prev:hover,
    .carousel-nav-next:hover {
        transform: none;
    }
}

.carousel-item img, .swiper-slide img {
  width: 100%;
  height: auto;
  display: block;
}
</style>

<script>
// Enhanced carousel initialization
document.addEventListener('DOMContentLoaded', function() {
    console.log('🎠 Enhanced carousel initialization started...');
    
    // Check if Swiper is available
    if (typeof Swiper !== 'undefined') {
        console.log('✅ Swiper available, initializing enhanced carousel...');
        initEnhancedCarousel();
    } else {
        console.log('❌ Swiper not available, waiting...');
        setTimeout(function() {
            if (typeof Swiper !== 'undefined') {
                console.log('✅ Swiper found after delay, initializing...');
                initEnhancedCarousel();
            } else {
                console.error('❌ Swiper still not available');
            }
        }, 1000);
    }
});

function initEnhancedCarousel() {
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
            effect: 'fade',
            fadeEffect: {
                crossFade: true
            },
            speed: 1000,
            pagination: {
                el: '.carousel-pagination',
                clickable: true,
                dynamicBullets: true,
            },
            navigation: {
                nextEl: '.carousel-nav-next',
                prevEl: '.carousel-nav-prev',
            },
            on: {
                init: function() {
                    console.log('✅ Enhanced Swiper initialized successfully');
                    // Remove loading state
                    const carouselSection = document.querySelector('.carousel-section');
                    if (carouselSection) {
                        carouselSection.classList.remove('loading');
                    }
                    
                    // Add progress bar
                    addProgressBar();
                },
                slideChange: function() {
                    console.log('🔄 Enhanced slide changed to: ' + this.activeIndex);
                    updateProgressBar();
                },
                slideChangeTransitionStart: function() {
                    // Add slide transition effects
                    const activeSlide = this.slides[this.activeIndex];
                    if (activeSlide) {
                        activeSlide.style.transform = 'scale(1.02)';
                    }
                },
                slideChangeTransitionEnd: function() {
                    // Reset slide scale
                    this.slides.forEach(slide => {
                        slide.style.transform = 'scale(1)';
                    });
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

function addProgressBar() {
    const carousel = document.querySelector('.carousel-section');
    if (carousel) {
        const progressBar = document.createElement('div');
        progressBar.className = 'carousel-progress';
        progressBar.innerHTML = '<div class="carousel-progress-bar"></div>';
        carousel.appendChild(progressBar);
    }
}

function updateProgressBar() {
    const progressBar = document.querySelector('.carousel-progress-bar');
    if (progressBar) {
        progressBar.style.width = '0%';
        progressBar.style.transition = 'none';
        
        setTimeout(() => {
            progressBar.style.transition = 'width 6s linear';
            progressBar.style.width = '100%';
        }, 100);
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
                        'slide_title': this.closest('.carousel-content').querySelector('.carousel-title')?.textContent || 'Unknown'
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
    
    document.querySelectorAll('.carousel-slide').forEach(function(slide) {
        observer.observe(slide);
    });
});

// Fade in slides only after background image is loaded
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.swiper-slide').forEach(slide => {
        const bgUrlMatch = slide.style.backgroundImage.match(/url\(["']?(.*?)["']?\)/);
        if (bgUrlMatch && bgUrlMatch[1]) {
            const img = new Image();
            img.src = bgUrlMatch[1];
            img.onload = () => {
                slide.style.opacity = '1';
            };
        } else {
            // If no background image, show the slide anyway
            slide.style.opacity = '1';
        }
    });
});
</script> 