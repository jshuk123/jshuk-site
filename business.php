<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session first
if (session_status() === PHP_SESSION_NONE) session_start();

// Include security check
require_once 'config/security.php';

// Include configuration
require_once 'config/config.php';
require_once 'includes/ad_renderer.php';
require_once 'includes/subscription_functions.php';

$business_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$business_id) {
    header('Location: /businesses.php');
    exit;
}

$business = null;
$gallery_images = [];
$reviews = [];
$similar_businesses = [];

try {
    // Fetch business details with user subscription tier and rating data
    $stmt = $pdo->prepare("
        SELECT b.*, c.name as category_name, u.subscription_tier,
               COALESCE(AVG(r.rating), 0) as average_rating,
               COUNT(r.id) as review_count
        FROM businesses b 
        LEFT JOIN business_categories c ON b.category_id = c.id 
        LEFT JOIN users u ON b.user_id = u.id
        LEFT JOIN reviews r ON b.id = r.business_id 
        WHERE b.id = ? AND b.status = 'active'
        GROUP BY b.id
    ");
    $stmt->execute([$business_id]);
    $business = $stmt->fetch();

    if (!$business) {
        header('Location: /404.php');
        exit;
    }

    // Normalize business data for components
    $business['name'] = $business['business_name'];
    $business['category'] = $business['category_name'];
    $business['rating'] = $business['average_rating'] ?? 0;
    $business['review_count'] = $business['review_count'] ?? 0;
    
    // Parse contact info if it's JSON
    $contact_info = json_decode($business['contact_info'] ?? '{}', true);
    if (is_array($contact_info)) {
        $business['phone'] = $contact_info['phone'] ?? $business['phone'] ?? '';
        $business['email'] = $contact_info['email'] ?? $business['email'] ?? '';
        $business['website'] = $contact_info['website'] ?? $business['website'] ?? '';
        $business['address'] = $contact_info['address'] ?? $business['address'] ?? '';
    }
    
    // Parse social media if it's JSON
    $social_media = json_decode($business['social_media'] ?? '{}', true);
    if (is_array($social_media)) {
        $business['facebook'] = $social_media['facebook'] ?? '';
        $business['twitter'] = $social_media['twitter'] ?? '';
        $business['instagram'] = $social_media['instagram'] ?? '';
        $business['linkedin'] = $social_media['linkedin'] ?? '';
    }

    // Parse opening hours if it's JSON
    $opening_hours = json_decode($business['opening_hours'] ?? '{}', true);
    if (!is_array($opening_hours)) {
        $opening_hours = [];
    }

    // Get business images for gallery
    $img_stmt = $pdo->prepare("SELECT * FROM business_images WHERE business_id = ? ORDER BY sort_order ASC");
    $img_stmt->execute([$business_id]);
    $gallery_images = $img_stmt->fetchAll();

    // Get business reviews
    $review_stmt = $pdo->prepare("
        SELECT r.*, u.first_name, u.last_name, u.profile_image
        FROM reviews r 
        LEFT JOIN users u ON r.user_id = u.id
        WHERE r.business_id = ? AND r.is_approved = 1 
        ORDER BY r.created_at DESC
    ");
    $review_stmt->execute([$business_id]);
    $reviews = $review_stmt->fetchAll();

    // If no reviews, try testimonials table
    if (empty($reviews)) {
        $testimonial_stmt = $pdo->prepare("
            SELECT t.*, u.first_name, u.last_name, u.profile_image
            FROM testimonials t
            LEFT JOIN users u ON t.user_id = u.id
            WHERE t.business_id = ? AND t.is_approved = 1 
            ORDER BY t.created_at DESC
        ");
        $testimonial_stmt->execute([$business_id]);
        $reviews = $testimonial_stmt->fetchAll();
    }

    // Similar businesses
    $similar_businesses = [];
    if (!empty($business['category_id'])) {
        $similar_stmt = $pdo->prepare("
            SELECT b.id, b.business_name, b.description, c.name as category_name,
                   COALESCE(AVG(r.rating), 0) as average_rating,
                   COUNT(r.id) as review_count
            FROM businesses b 
            LEFT JOIN business_categories c ON b.category_id = c.id
            LEFT JOIN reviews r ON b.id = r.business_id
            WHERE b.category_id = ? AND b.id != ? AND b.status = 'active'
            GROUP BY b.id
            ORDER BY average_rating DESC, review_count DESC
            LIMIT 3
        ");
        $similar_stmt->execute([$business['category_id'], $business_id]);
        $similar_businesses = $similar_stmt->fetchAll();
    }

} catch (PDOException $e) {
    echo '<pre style="color:red;">PDO ERROR: ' . htmlspecialchars($e->getMessage()) . '</pre>';
    exit;
}

// Function to generate star rating HTML
function generateStars($rating) {
    if (!$rating || $rating == 0) {
        return '<span class="text-muted">No rating</span>';
    }
    
    $html = '<span class="stars">';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $rating) {
            $html .= '<i class="fas fa-star text-warning"></i>';
        } elseif ($i - $rating < 1) {
            $html .= '<i class="fas fa-star-half-alt text-warning"></i>';
        } else {
            $html .= '<i class="far fa-star text-muted"></i>';
        }
    }
    $html .= '</span>';
    
    return $html;
}

// Function to check if business is currently open
function isBusinessOpen($opening_hours) {
    if (empty($opening_hours)) return false;
    
    $current_day = strtolower(date('l'));
    $current_time = date('H:i');
    
    if (isset($opening_hours[$current_day])) {
        $day_hours = $opening_hours[$current_day];
        if ($day_hours['open'] && $day_hours['closed']) {
            return $current_time >= $day_hours['open'] && $current_time <= $day_hours['closed'];
        }
    }
    
    return false;
}

$pageTitle = htmlspecialchars($business['name']) . " | JShuk";
$page_css = "business.css";
include 'includes/header_main.php';
?>

<!-- Business Listing Page -->
<div class="business-listing-page">
    <!-- Part 3.1: Header & Photo Gallery -->
    <section class="business-header-gallery">
        <div class="container-fluid px-0">
            <div class="gallery-layout">
                <!-- Main Hero Image -->
                <div class="main-hero-image">
                    <?php if (!empty($gallery_images)): ?>
                        <img src="<?= htmlspecialchars($gallery_images[0]['file_path'] ?? '/images/default-business.jpg') ?>" 
                             alt="<?= htmlspecialchars($business['name']) ?>" 
                             class="hero-img">
                    <?php else: ?>
                        <img src="/images/default-business.jpg" 
                             alt="<?= htmlspecialchars($business['name']) ?>" 
                             class="hero-img">
                    <?php endif; ?>
                    
                    <!-- Overlay with Business Info -->
                    <div class="hero-overlay">
                        <div class="hero-content">
                            <h1 class="business-title"><?= htmlspecialchars($business['name']) ?></h1>
                            <div class="business-category"><?= htmlspecialchars($business['category']) ?></div>
                            <div class="business-rating">
                                <?= generateStars($business['rating']) ?>
                                <span class="rating-text"><?= number_format($business['rating'], 1) ?> (<?= $business['review_count'] ?> reviews)</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Thumbnail Gallery -->
                <div class="thumbnail-gallery">
                    <?php 
                    $thumbnail_count = 0;
                    for ($i = 1; $i < min(4, count($gallery_images)); $i++): 
                        $thumbnail_count++;
                    ?>
                        <div class="gallery-thumbnail" onclick="openLightbox(<?= $i ?>)">
                            <img src="<?= htmlspecialchars($gallery_images[$i]['file_path']) ?>" 
                                 alt="<?= htmlspecialchars($business['name']) ?> - Image <?= $i + 1 ?>" 
                                 class="thumb-img">
                            <div class="thumb-overlay">
                                <i class="fas fa-expand"></i>
                            </div>
                        </div>
                    <?php endfor; ?>
                    
                    <!-- View More Thumbnail -->
                    <?php if (count($gallery_images) > 4): ?>
                        <div class="gallery-thumbnail view-more-thumb" onclick="openLightbox(4)">
                            <div class="view-more-content">
                                <i class="fas fa-images"></i>
                                <span>+<?= count($gallery_images) - 4 ?> more</span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content Area -->
    <div class="container mt-4">
        <div class="business-content-layout">
            <!-- Part 3.2: Main Content Column -->
            <main class="main-content-column">
                <!-- About This Business Section -->
                <section class="about-business-section">
                    <div class="content-card">
                        <h2 class="section-title">
                            <i class="fas fa-info-circle text-primary me-2"></i>
                            About This Business
                        </h2>
                        <div class="about-content">
                            <?php if (!empty($business['description'])): ?>
                                <p class="lead"><?= nl2br(htmlspecialchars($business['description'])) ?></p>
                            <?php endif; ?>
                            
                            <?php if (!empty($business['about'])): ?>
                                <div class="about-details">
                                    <?= nl2br(htmlspecialchars($business['about'])) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>

                <!-- Services/Menu Section -->
                <section class="services-section">
                    <div class="content-card">
                        <h2 class="section-title">
                            <i class="fas fa-cogs text-success me-2"></i>
                            Services & Offerings
                        </h2>
                        <div class="services-content">
                            <?php if (!empty($business['services'])): ?>
                                <div class="services-list">
                                    <?= nl2br(htmlspecialchars($business['services'])) ?>
                                </div>
                            <?php else: ?>
                                <p class="text-muted">Services information coming soon...</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>

                <!-- Location Map Section -->
                <section class="location-section">
                    <div class="content-card">
                        <h2 class="section-title">
                            <i class="fas fa-map-marker-alt text-danger me-2"></i>
                            Location
                        </h2>
                        <div class="location-content">
                            <?php if (!empty($business['address'])): ?>
                                <div class="address-info mb-3">
                                    <p class="address-text">
                                        <i class="fas fa-map-marker-alt text-muted me-2"></i>
                                        <?= htmlspecialchars($business['address']) ?>
                                    </p>
                                </div>
                                
                                <!-- Google Maps Embed -->
                                <div class="map-container">
                                    <iframe 
                                        width="100%" 
                                        height="400" 
                                        frameborder="0" 
                                        style="border:0" 
                                        src="https://www.google.com/maps/embed/v1/place?key=YOUR_GOOGLE_MAPS_API_KEY&q=<?= urlencode($business['address']) ?>" 
                                        allowfullscreen>
                                    </iframe>
                                </div>
                            <?php else: ?>
                                <p class="text-muted">Location information coming soon...</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </section>
            </main>

            <!-- Part 3.3: Action Sidebar -->
            <aside class="action-sidebar">
                <!-- Call-to-Action Box -->
                <div class="cta-box">
                    <h3 class="cta-title">Get in Touch</h3>
                    <div class="cta-buttons">
                        <?php if (!empty($business['phone'])): ?>
                            <a href="tel:<?= htmlspecialchars($business['phone']) ?>" class="cta-btn cta-phone">
                                <i class="fas fa-phone"></i>
                                <span>Call Now</span>
                            </a>
                        <?php endif; ?>
                        
                        <?php if (!empty($business['website'])): ?>
                            <a href="<?= htmlspecialchars($business['website']) ?>" target="_blank" class="cta-btn cta-website">
                                <i class="fas fa-globe"></i>
                                <span>Visit Website</span>
                            </a>
                        <?php endif; ?>
                        
                        <?php if (!empty($business['address'])): ?>
                            <a href="https://maps.google.com/?q=<?= urlencode($business['address']) ?>" target="_blank" class="cta-btn cta-directions">
                                <i class="fas fa-directions"></i>
                                <span>Get Directions</span>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- At-a-Glance Info Box -->
                <div class="info-box">
                    <h3 class="info-title">At a Glance</h3>
                    
                    <!-- Address -->
                    <?php if (!empty($business['address'])): ?>
                        <div class="info-item">
                            <div class="info-icon">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <div class="info-content">
                                <div class="info-label">Address</div>
                                <div class="info-value"><?= htmlspecialchars($business['address']) ?></div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Opening Hours -->
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="info-content">
                            <div class="info-label">Opening Hours</div>
                            <div class="hours-status">
                                <span class="status-badge <?= isBusinessOpen($opening_hours) ? 'open' : 'closed' ?>">
                                    <?= isBusinessOpen($opening_hours) ? 'Open Now' : 'Closed Now' ?>
                                </span>
                            </div>
                            <?php if (!empty($opening_hours)): ?>
                                <div class="hours-list">
                                    <?php 
                                    $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
                                    $day_names = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                                    $current_day = strtolower(date('l'));
                                    
                                    foreach ($days as $index => $day): 
                                        $day_hours = $opening_hours[$day] ?? null;
                                        $is_current = $day === $current_day;
                                    ?>
                                        <div class="hours-item <?= $is_current ? 'current-day' : '' ?>">
                                            <span class="day-name"><?= $day_names[$index] ?></span>
                                            <span class="day-hours">
                                                <?php if ($day_hours && $day_hours['open'] && $day_hours['closed']): ?>
                                                    <?= htmlspecialchars($day_hours['open']) ?> - <?= htmlspecialchars($day_hours['closed']) ?>
                                                <?php else: ?>
                                                    <span class="closed-text">Closed</span>
                                                <?php endif; ?>
                                            </span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Features -->
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-star"></i>
                        </div>
                        <div class="info-content">
                            <div class="info-label">Features</div>
                            <div class="features-list">
                                <?php if ($business['subscription_tier'] === 'premium_plus'): ?>
                                    <span class="feature-badge elite">Elite Business</span>
                                <?php elseif ($business['subscription_tier'] === 'premium'): ?>
                                    <span class="feature-badge premium">Premium Business</span>
                                <?php endif; ?>
                                
                                <?php if (!empty($business['kosher_certified'])): ?>
                                    <span class="feature-badge kosher">Kosher Certified</span>
                                <?php endif; ?>
                                
                                <?php if (!empty($business['delivery_available'])): ?>
                                    <span class="feature-badge delivery">Offers Delivery</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </div>

    <!-- Part 3.4: User Reviews Section -->
    <section class="reviews-section">
        <div class="container">
            <div class="reviews-content">
                <!-- Review Summary -->
                <div class="review-summary">
                    <div class="summary-header">
                        <h2 class="section-title">Customer Reviews</h2>
                        <div class="rating-overview">
                            <div class="average-rating">
                                <div class="rating-number"><?= number_format($business['rating'], 1) ?></div>
                                <div class="rating-stars"><?= generateStars($business['rating']) ?></div>
                                <div class="total-reviews">Based on <?= $business['review_count'] ?> reviews</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Leave Review Button -->
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <div class="review-actions">
                            <button class="btn btn-warning btn-lg" onclick="openReviewModal()">
                                <i class="fas fa-star me-2"></i>
                                Leave a Review
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="review-actions">
                            <a href="/auth/login.php" class="btn btn-outline-warning btn-lg">
                                <i class="fas fa-sign-in-alt me-2"></i>
                                Sign in to Review
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Individual Reviews -->
                <div class="reviews-list">
                    <?php if (!empty($reviews)): ?>
                        <?php foreach ($reviews as $review): ?>
                            <div class="review-card">
                                <div class="review-header">
                                    <div class="reviewer-info">
                                        <div class="reviewer-avatar">
                                            <?php if (!empty($review['profile_image'])): ?>
                                                <img src="<?= htmlspecialchars($review['profile_image']) ?>" alt="Reviewer" class="avatar-img">
                                            <?php else: ?>
                                                <div class="avatar-placeholder">
                                                    <i class="fas fa-user"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="reviewer-details">
                                            <div class="reviewer-name">
                                                <?= htmlspecialchars($review['first_name'] . ' ' . $review['last_name']) ?>
                                            </div>
                                            <div class="review-date">
                                                <?= date('M j, Y', strtotime($review['created_at'])) ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="review-rating">
                                        <?= generateStars($review['rating'] ?? 5) ?>
                                    </div>
                                </div>
                                <div class="review-content">
                                    <?php if (!empty($review['title'])): ?>
                                        <h4 class="review-title"><?= htmlspecialchars($review['title']) ?></h4>
                                    <?php endif; ?>
                                    <p class="review-text"><?= nl2br(htmlspecialchars($review['comment'] ?? $review['testimonial'])) ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-reviews-state">
                            <div class="no-reviews-icon">
                                <i class="fas fa-star"></i>
                            </div>
                            <h3 class="no-reviews-title">No Reviews Yet</h3>
                            <p class="no-reviews-text">Be the first to share your experience with this business!</p>
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <button class="btn btn-warning" onclick="openReviewModal()">
                                    <i class="fas fa-star me-2"></i>
                                    Write First Review
                                </button>
                            <?php else: ?>
                                <a href="/auth/login.php" class="btn btn-outline-warning">
                                    <i class="fas fa-sign-in-alt me-2"></i>
                                    Sign in to Review
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- Lightbox Modal for Gallery -->
<div id="lightboxModal" class="lightbox-modal">
    <div class="lightbox-content">
        <div class="lightbox-header">
            <h3 class="lightbox-title"><?= htmlspecialchars($business['name']) ?> Gallery</h3>
            <button class="lightbox-close" onclick="closeLightbox()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="lightbox-body">
            <img id="lightboxImage" src="" alt="" class="lightbox-img">
        </div>
        <div class="lightbox-footer">
            <button class="lightbox-nav" onclick="previousImage()">
                <i class="fas fa-chevron-left"></i>
            </button>
            <div class="lightbox-counter">
                <span id="currentImageIndex">1</span> of <span id="totalImages"><?= count($gallery_images) ?></span>
            </div>
            <button class="lightbox-nav" onclick="nextImage()">
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>
    </div>
</div>

<?php include 'includes/footer_main.php'; ?>

<script>
// Gallery Lightbox functionality
let currentImageIndex = 0;
const images = <?= json_encode(array_column($gallery_images, 'file_path')) ?>;

function openLightbox(index) {
    currentImageIndex = index;
    updateLightboxImage();
    document.getElementById('lightboxModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeLightbox() {
    document.getElementById('lightboxModal').style.display = 'none';
    document.body.style.overflow = 'auto';
}

function updateLightboxImage() {
    const lightboxImg = document.getElementById('lightboxImage');
    const currentIndexSpan = document.getElementById('currentImageIndex');
    const totalImagesSpan = document.getElementById('totalImages');
    
    if (images[currentImageIndex]) {
        lightboxImg.src = images[currentImageIndex];
        currentIndexSpan.textContent = currentImageIndex + 1;
        totalImagesSpan.textContent = images.length;
    }
}

function nextImage() {
    currentImageIndex = (currentImageIndex + 1) % images.length;
    updateLightboxImage();
}

function previousImage() {
    currentImageIndex = (currentImageIndex - 1 + images.length) % images.length;
    updateLightboxImage();
}

// Close lightbox when clicking outside
document.getElementById('lightboxModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeLightbox();
    }
});

// Keyboard navigation
document.addEventListener('keydown', function(e) {
    if (document.getElementById('lightboxModal').style.display === 'flex') {
        if (e.key === 'Escape') {
            closeLightbox();
        } else if (e.key === 'ArrowRight') {
            nextImage();
        } else if (e.key === 'ArrowLeft') {
            previousImage();
        }
    }
});

// Review modal functionality (placeholder)
function openReviewModal() {
    // TODO: Implement review modal
    alert('Review functionality coming soon!');
}
</script>
