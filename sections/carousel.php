<?php
/**
 * JShuk Homepage Carousel Component
 * Displays carousel ads from the carousel_ads table
 */

// Fetch active carousel ads
$carousel_ads = [];

// Only try to fetch from database if $pdo is available
if (isset($pdo) && $pdo) {
    try {
        // First check if the table exists
        $table_check = $pdo->query("SHOW TABLES LIKE 'carousel_ads'");
        if ($table_check->rowCount() > 0) {
            $stmt = $pdo->prepare("
                SELECT * FROM carousel_ads 
                WHERE active = 1 AND (expires_at IS NULL OR expires_at > NOW())
                ORDER BY position ASC, created_at DESC
                LIMIT 10
            ");
            $stmt->execute();
            $carousel_ads = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (APP_DEBUG) {
                error_log("Carousel: Found " . count($carousel_ads) . " active ads");
            }
        } else {
            if (APP_DEBUG) {
                error_log("Carousel: carousel_ads table does not exist");
            }
        }
    } catch (PDOException $e) {
        // Log error if table doesn't exist yet or other database issues
        error_log("Carousel ads error: " . $e->getMessage());
    }
} else {
    if (APP_DEBUG) {
        error_log("Carousel: Database connection not available");
    }
}

// If no ads found, create a placeholder ad for testing
if (empty($carousel_ads)) {
    $carousel_ads = [
        [
            'title' => 'Welcome to JShuk',
            'subtitle' => 'Your Jewish Community Hub - Add your first carousel ad in the admin panel',
            'image_path' => 'data:image/svg+xml;base64,' . base64_encode('<svg width="1920" height="600" xmlns="http://www.w3.org/2000/svg"><defs><linearGradient id="grad" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" style="stop-color:#667eea;stop-opacity:1" /><stop offset="100%" style="stop-color:#764ba2;stop-opacity:1" /></linearGradient></defs><rect width="100%" height="100%" fill="url(#grad)"/><text x="50%" y="45%" font-family="Arial, sans-serif" font-size="48" fill="white" text-anchor="middle">Welcome to JShuk</text><text x="50%" y="55%" font-family="Arial, sans-serif" font-size="24" fill="white" text-anchor="middle">Your Jewish Community Hub</text></svg>'),
            'cta_text' => 'Add Your First Ad',
            'cta_url' => 'admin/carousel_manager.php'
        ]
    ];
}
?>

<!-- HOMEPAGE CAROUSEL SECTION -->
<section class="carousel-section" data-scroll>
    <div class="container">
        <div class="carousel-wrapper">
            <div class="swiper homepage-carousel">
                <div class="swiper-wrapper">
                    <?php foreach ($carousel_ads as $ad): ?>
                        <div class="swiper-slide carousel-slide" style="background-image: url('<?= htmlspecialchars($ad['image_path']) ?>')">
                            <div class="carousel-overlay">
                                <div class="carousel-content">
                                    <h2 class="carousel-title"><?= htmlspecialchars($ad['title']) ?></h2>
                                    <?php if (!empty($ad['subtitle'])): ?>
                                        <p class="carousel-subtitle"><?= htmlspecialchars($ad['subtitle']) ?></p>
                                    <?php endif; ?>
                                    <?php if (!empty($ad['cta_url'])): ?>
                                        <a href="<?= htmlspecialchars($ad['cta_url']) ?>" 
                                           class="carousel-cta" 
                                           <?= strpos($ad['cta_url'], 'admin/') === 0 ? '' : 'target="_blank" rel="noopener noreferrer"' ?>>
                                            <?= htmlspecialchars($ad['cta_text'] ?: 'Learn More') ?>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Navigation -->
                <div class="swiper-button-prev carousel-nav-prev"></div>
                <div class="swiper-button-next carousel-nav-next"></div>
                
                <!-- Pagination -->
                <div class="swiper-pagination carousel-pagination"></div>
            </div>
        </div>
    </div>
</section>

<style>
/* Carousel Styles */
.carousel-section {
    margin: 0;
    padding: 0;
    background: #000;
    position: relative;
    overflow: hidden;
}

.carousel-wrapper {
    position: relative;
    width: 100%;
}

.homepage-carousel {
    width: 100%;
    height: 500px;
    border-radius: 0;
}

.carousel-slide {
    background-size: cover !important;
    background-position: center !important;
    background-repeat: no-repeat !important;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
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
}

.carousel-title {
    font-size: 3rem;
    font-weight: 700;
    margin-bottom: 20px;
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
    line-height: 1.2;
}

.carousel-subtitle {
    font-size: 1.3rem;
    margin-bottom: 30px;
    opacity: 0.95;
    text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
    line-height: 1.4;
}

.carousel-cta {
    display: inline-block;
    background: linear-gradient(45deg, #ff6b6b, #ff8e53);
    color: white;
    padding: 15px 30px;
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

/* Navigation Buttons */
.carousel-nav-prev,
.carousel-nav-next {
    color: white;
    background: rgba(255, 255, 255, 0.2);
    width: 50px;
    height: 50px;
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
    font-size: 20px;
    font-weight: bold;
}

/* Pagination */
.carousel-pagination {
    bottom: 20px;
}

.carousel-pagination .swiper-pagination-bullet {
    width: 12px;
    height: 12px;
    background: rgba(255, 255, 255, 0.5);
    opacity: 1;
    transition: all 0.3s ease;
}

.carousel-pagination .swiper-pagination-bullet-active {
    background: #ff6b6b;
    transform: scale(1.2);
}

/* Responsive Design */
@media (max-width: 768px) {
    .homepage-carousel {
        height: 400px;
    }
    
    .carousel-content {
        padding: 20px;
    }
    
    .carousel-title {
        font-size: 2rem;
        margin-bottom: 15px;
    }
    
    .carousel-subtitle {
        font-size: 1.1rem;
        margin-bottom: 20px;
    }
    
    .carousel-cta {
        padding: 12px 24px;
        font-size: 1rem;
    }
    
    .carousel-nav-prev,
    .carousel-nav-next {
        width: 40px;
        height: 40px;
    }
    
    .carousel-nav-prev::after,
    .carousel-nav-next::after {
        font-size: 16px;
    }
}

@media (max-width: 480px) {
    .homepage-carousel {
        height: 350px;
    }
    
    .carousel-title {
        font-size: 1.8rem;
    }
    
    .carousel-subtitle {
        font-size: 1rem;
    }
}

/* Animation for content */
.carousel-content {
    animation: fadeInUp 0.8s ease-out;
}

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
</style>

<script>
// Initialize carousel when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('🔍 Carousel initialization started...');
    
    // Check if Swiper is already loaded
    if (typeof Swiper !== 'undefined') {
        console.log('✅ Swiper already loaded, initializing carousel...');
        initCarousel();
    } else {
        console.log('📦 Swiper not loaded, loading now...');
        // Load Swiper if not already loaded
        loadSwiper();
    }
});

function loadSwiper() {
    console.log('📥 Loading Swiper library...');
    
    // Load Swiper CSS
    if (!document.querySelector('link[href*="swiper"]')) {
        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = 'https://cdn.jsdelivr.net/npm/swiper/swiper-bundle.min.css';
        link.onload = () => console.log('✅ Swiper CSS loaded');
        link.onerror = () => console.error('❌ Failed to load Swiper CSS');
        document.head.appendChild(link);
    } else {
        console.log('✅ Swiper CSS already loaded');
    }
    
    // Load Swiper JS
    const script = document.createElement('script');
    script.src = 'https://cdn.jsdelivr.net/npm/swiper/swiper-bundle.min.js';
    script.onload = () => {
        console.log('✅ Swiper JS loaded, initializing carousel...');
        initCarousel();
    };
    script.onerror = () => {
        console.error('❌ Failed to load Swiper JS');
        // Fallback: try to initialize anyway in case Swiper is loaded elsewhere
        setTimeout(() => {
            if (typeof Swiper !== 'undefined') {
                console.log('✅ Swiper found after timeout, initializing...');
                initCarousel();
            } else {
                console.error('❌ Swiper not available after timeout');
            }
        }, 1000);
    };
    document.head.appendChild(script);
}

function initCarousel() {
    console.log('🔍 Initializing carousel...');
    const carousel = document.querySelector('.homepage-carousel');
    if (!carousel) {
        console.error('❌ Carousel element not found');
        return;
    }
    
    console.log('✅ Carousel element found, creating Swiper instance...');
    
    try {
        const swiper = new Swiper('.homepage-carousel', {
            loop: true,
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
                    console.log('✅ Swiper initialized successfully');
                    // Remove loading state
                    const carouselSection = document.querySelector('.carousel-section');
                    if (carouselSection) {
                        carouselSection.classList.remove('loading');
                    }
                },
                slideChange: function() {
                    console.log('🔄 Slide changed to: ' + this.activeIndex);
                },
                beforeInit: function() {
                    console.log('🔄 Swiper beforeInit event fired');
                },
                afterInit: function() {
                    console.log('🔄 Swiper afterInit event fired');
                }
            }
        });
        
        console.log('🎉 Carousel setup complete');
        
        // Pause autoplay on hover for better UX
        carousel.addEventListener('mouseenter', () => {
            console.log('🖱️ Mouse entered carousel, pausing autoplay');
            swiper.autoplay.stop();
        });
        
        carousel.addEventListener('mouseleave', () => {
            console.log('🖱️ Mouse left carousel, resuming autoplay');
            swiper.autoplay.start();
        });
        
        // Add keyboard navigation
        document.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowLeft') {
                swiper.slidePrev();
            } else if (e.key === 'ArrowRight') {
                swiper.slideNext();
            }
        });
        
    } catch (error) {
        console.error('❌ Error initializing Swiper:', error);
    }
}
</script> 