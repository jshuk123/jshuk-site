<?php
require_once 'config/config.php';
require_once 'includes/subscription_functions.php';

if (session_status() === PHP_SESSION_NONE) {
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

// Get retreat ID
$retreat_id = (int)($_GET['id'] ?? 0);
$success_message = $_GET['success'] ?? '';

if (!$retreat_id) {
    header('Location: /retreats.php');
    exit;
}

// Initialize variables
$retreat = null;
$amenities = [];
$tags = [];
$reviews = [];
$related_retreats = [];
$host = null;

try {
    if (isset($pdo) && $pdo) {
        // Load retreat details
        $stmt = $pdo->prepare("
            SELECT r.*, rc.name as category_name, rc.icon_class as category_icon, rc.emoji as category_emoji,
                   rl.name as location_name, rl.region as location_region,
                   u.first_name, u.last_name, u.email, u.phone, u.profile_image
            FROM retreats r
            LEFT JOIN retreat_categories rc ON r.category_id = rc.id
            LEFT JOIN retreat_locations rl ON r.location_id = rl.id
            LEFT JOIN users u ON r.host_id = u.id
            WHERE r.id = ? AND r.status = 'active'
        ");
        $stmt->execute([$retreat_id]);
        $retreat = $stmt->fetch();
        
        if (!$retreat) {
            header('Location: /retreats.php');
            exit;
        }
        
        // Load retreat amenities
        $stmt = $pdo->prepare("
            SELECT ra.name, ra.icon_class, ra.category
            FROM retreat_amenity_relations rar
            JOIN retreat_amenities ra ON rar.amenity_id = ra.id
            WHERE rar.retreat_id = ?
            ORDER BY ra.category, ra.sort_order
        ");
        $stmt->execute([$retreat_id]);
        $amenities = $stmt->fetchAll();
        
        // Load retreat tags
        $stmt = $pdo->prepare("
            SELECT rt.name, rt.color
            FROM retreat_tag_relations rtr
            JOIN retreat_tags rt ON rtr.tag_id = rt.id
            WHERE rtr.retreat_id = ?
            ORDER BY rt.sort_order
        ");
        $stmt->execute([$retreat_id]);
        $tags = $stmt->fetchAll();
        
        // Load reviews
        $stmt = $pdo->prepare("
            SELECT rr.*, u.first_name, u.last_name, u.profile_image
            FROM retreat_reviews rr
            LEFT JOIN users u ON rr.guest_id = u.id
            WHERE rr.retreat_id = ? AND rr.is_approved = 1 AND rr.is_public = 1
            ORDER BY rr.created_at DESC
            LIMIT 10
        ");
        $stmt->execute([$retreat_id]);
        $reviews = $stmt->fetchAll();
        
        // Load related retreats
        $stmt = $pdo->prepare("
            SELECT r.*, rc.name as category_name, rc.emoji as category_emoji,
                   rl.name as location_name, rl.region as location_region
            FROM retreats r
            LEFT JOIN retreat_categories rc ON r.category_id = rc.id
            LEFT JOIN retreat_locations rl ON r.location_id = rl.id
            WHERE r.status = 'active' AND r.verified = 1 
            AND r.id != ? AND r.location_id = ?
            ORDER BY r.rating_average DESC, r.created_at DESC
            LIMIT 4
        ");
        $stmt->execute([$retreat_id, $retreat['location_id']]);
        $related_retreats = $stmt->fetchAll();
        
        // Increment view count
        $stmt = $pdo->prepare("UPDATE retreats SET views_count = views_count + 1 WHERE id = ?");
        $stmt->execute([$retreat_id]);
        
        // Track view
        $stmt = $pdo->prepare("
            INSERT INTO retreat_views (retreat_id, ip_address, user_agent, user_id) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $retreat_id,
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? '',
            $_SESSION['user_id'] ?? null
        ]);
        
    }
} catch (PDOException $e) {
    // Handle error
    $retreat = null;
}

if (!$retreat) {
    header('Location: /retreats.php');
    exit;
}

$pageTitle = htmlspecialchars($retreat['title']) . " | Retreats & Simcha Rentals - JShuk";
$page_css = "retreat_detail.css";
$metaDescription = htmlspecialchars($retreat['short_description']);
$metaKeywords = "jewish retreat, " . strtolower($retreat['category_name']) . ", " . strtolower($retreat['location_name']) . ", kosher accommodation";

include 'includes/header_main.php';
?>

<!-- SUCCESS MESSAGE -->
<?php if ($success_message): ?>
<div class="success-banner">
    <div class="container">
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <strong>Success!</strong> Your property has been listed successfully and is pending review.
        </div>
    </div>
</div>
<?php endif; ?>

<!-- RETREAT HEADER -->
<section class="retreat-header" data-scroll>
    <div class="container">
        <div class="retreat-breadcrumb">
            <a href="/retreats.php">Retreats</a>
            <i class="fas fa-chevron-right"></i>
            <a href="/retreats.php?category=<?= htmlspecialchars($retreat['category_name']) ?>"><?= htmlspecialchars($retreat['category_name']) ?></a>
            <i class="fas fa-chevron-right"></i>
            <span><?= htmlspecialchars($retreat['title']) ?></span>
        </div>
        
        <div class="retreat-title-section">
            <h1 class="retreat-title"><?= htmlspecialchars($retreat['title']) ?></h1>
            <div class="retreat-meta">
                <div class="retreat-location">
                    <i class="fas fa-map-marker-alt"></i>
                    <?= htmlspecialchars($retreat['location_name']) ?>, <?= htmlspecialchars($retreat['location_region']) ?>
                </div>
                <div class="retreat-category">
                    <span class="category-emoji"><?= htmlspecialchars($retreat['category_emoji']) ?></span>
                    <?= htmlspecialchars($retreat['category_name']) ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- RETREAT CONTENT -->
<section class="retreat-content" data-scroll>
    <div class="container">
        <div class="row">
            <!-- Main Content -->
            <div class="col-lg-8">
                <!-- Image Gallery -->
                <div class="retreat-gallery">
                    <?php if ($retreat['image_paths']): ?>
                        <?php $images = json_decode($retreat['image_paths'], true); ?>
                        <div class="gallery-main">
                            <img src="<?= htmlspecialchars($images[0] ?? '/images/elite-placeholder.svg') ?>" 
                                 alt="<?= htmlspecialchars($retreat['title']) ?>" id="main-image">
                        </div>
                        <?php if (count($images) > 1): ?>
                        <div class="gallery-thumbnails">
                            <?php foreach (array_slice($images, 0, 5) as $index => $image): ?>
                            <div class="thumbnail <?= $index === 0 ? 'active' : '' ?>" 
                                 onclick="changeMainImage('<?= htmlspecialchars($image) ?>', this)">
                                <img src="<?= htmlspecialchars($image) ?>" 
                                     alt="<?= htmlspecialchars($retreat['title']) ?> - Image <?= $index + 1 ?>">
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="gallery-main">
                            <img src="/images/elite-placeholder.svg" 
                                 alt="<?= htmlspecialchars($retreat['title']) ?>">
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Property Details -->
                <div class="retreat-details-section">
                    <h2 class="section-title">About this property</h2>
                    <p class="retreat-description"><?= nl2br(htmlspecialchars($retreat['description'])) ?></p>
                    
                    <!-- Key Features -->
                    <div class="key-features">
                        <div class="feature-item">
                            <i class="fas fa-users"></i>
                            <span>Sleeps <?= $retreat['guest_capacity'] ?></span>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-bed"></i>
                            <span><?= $retreat['bedrooms'] ?> bedroom<?= $retreat['bedrooms'] > 1 ? 's' : '' ?></span>
                        </div>
                        <div class="feature-item">
                            <i class="fas fa-bath"></i>
                            <span><?= $retreat['bathrooms'] ?> bathroom<?= $retreat['bathrooms'] > 1 ? 's' : '' ?></span>
                        </div>
                        <?php if ($retreat['distance_to_shul']): ?>
                        <div class="feature-item">
                            <i class="fas fa-mosque"></i>
                            <span><?= $retreat['distance_to_shul'] ?>m to shul</span>
                        </div>
                        <?php endif; ?>
                        <?php if ($retreat['mikveh_distance']): ?>
                        <div class="feature-item">
                            <i class="fas fa-water"></i>
                            <span><?= $retreat['mikveh_distance'] ?>m to mikveh</span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Amenities -->
                <?php if (!empty($amenities)): ?>
                <div class="amenities-section">
                    <h2 class="section-title">Amenities</h2>
                    <div class="amenities-grid">
                        <?php 
                        $amenity_categories = ['essential', 'comfort', 'luxury', 'accessibility', 'kosher'];
                        foreach ($amenity_categories as $category):
                            $category_amenities = array_filter($amenities, fn($a) => $a['category'] === $category);
                            if (!empty($category_amenities)):
                        ?>
                        <div class="amenity-category">
                            <h3 class="category-title"><?= ucfirst($category) ?> Amenities</h3>
                            <div class="amenity-list">
                                <?php foreach ($category_amenities as $amenity): ?>
                                <div class="amenity-item">
                                    <i class="<?= htmlspecialchars($amenity['icon_class']) ?>"></i>
                                    <span><?= htmlspecialchars($amenity['name']) ?></span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Tags -->
                <?php if (!empty($tags)): ?>
                <div class="tags-section">
                    <h2 class="section-title">Property Features</h2>
                    <div class="tags-grid">
                        <?php foreach ($tags as $tag): ?>
                        <span class="tag" style="--tag-color: <?= htmlspecialchars($tag['color']) ?>">
                            <?= htmlspecialchars($tag['name']) ?>
                        </span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Reviews -->
                <?php if (!empty($reviews)): ?>
                <div class="reviews-section">
                    <h2 class="section-title">
                        Reviews
                        <?php if ($retreat['rating_average'] > 0): ?>
                        <span class="rating-display">
                            <i class="fas fa-star"></i>
                            <?= number_format($retreat['rating_average'], 1) ?>
                            <span class="rating-count">(<?= $retreat['rating_count'] ?> reviews)</span>
                        </span>
                        <?php endif; ?>
                    </h2>
                    
                    <div class="reviews-grid">
                        <?php foreach ($reviews as $review): ?>
                        <div class="review-card">
                            <div class="review-header">
                                <div class="reviewer-info">
                                    <?php if ($review['profile_image']): ?>
                                    <img src="<?= htmlspecialchars($review['profile_image']) ?>" 
                                         alt="<?= htmlspecialchars($review['first_name'] . ' ' . $review['last_name']) ?>" 
                                         class="reviewer-avatar">
                                    <?php else: ?>
                                    <div class="reviewer-avatar-placeholder">
                                        <?= strtoupper(substr($review['first_name'], 0, 1)) ?>
                                    </div>
                                    <?php endif; ?>
                                    <div class="reviewer-details">
                                        <div class="reviewer-name">
                                            <?= htmlspecialchars($review['first_name'] . ' ' . $review['last_name']) ?>
                                        </div>
                                        <div class="review-date">
                                            <?= date('M Y', strtotime($review['created_at'])) ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="review-rating">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star <?= $i <= $review['rating'] ? 'filled' : '' ?>"></i>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <?php if ($review['review_text']): ?>
                            <div class="review-text">
                                <?= nl2br(htmlspecialchars($review['review_text'])) ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Location -->
                <div class="location-section">
                    <h2 class="section-title">Location</h2>
                    <div class="location-details">
                        <div class="address-info">
                            <h4>Address</h4>
                            <p><?= htmlspecialchars($retreat['address']) ?></p>
                            <?php if ($retreat['postcode']): ?>
                            <p><?= htmlspecialchars($retreat['postcode']) ?></p>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($retreat['nearest_shul']): ?>
                        <div class="nearby-info">
                            <h4>Nearby</h4>
                            <div class="nearby-item">
                                <i class="fas fa-mosque"></i>
                                <span><?= htmlspecialchars($retreat['nearest_shul']) ?></span>
                                <?php if ($retreat['distance_to_shul']): ?>
                                <span class="distance">(<?= $retreat['distance_to_shul'] ?>m)</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Placeholder for map -->
                        <div class="map-placeholder">
                            <i class="fas fa-map"></i>
                            <p>Interactive map coming soon...</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Sidebar -->
            <div class="col-lg-4">
                <div class="retreat-sidebar">
                    <!-- Booking Card -->
                    <div class="booking-card">
                        <div class="price-section">
                            <div class="price-main">
                                <span class="price-amount">£<?= number_format($retreat['price_per_night']) ?></span>
                                <span class="price-unit">per night</span>
                            </div>
                            <?php if ($retreat['price_shabbos_package']): ?>
                            <div class="price-alternative">
                                <span class="price-amount">£<?= number_format($retreat['price_shabbos_package']) ?></span>
                                <span class="price-unit">Shabbos package</span>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="booking-actions">
                            <button class="btn-jshuk-primary btn-lg w-100 mb-3" onclick="contactHost(<?= $retreat_id ?>)">
                                <i class="fas fa-envelope"></i>
                                Contact Host
                            </button>
                            <button class="btn btn-outline-primary btn-lg w-100 mb-3" onclick="checkAvailability(<?= $retreat_id ?>)">
                                <i class="fas fa-calendar"></i>
                                Check Availability
                            </button>
                            <button class="btn btn-outline-secondary w-100" onclick="shareRetreat(<?= $retreat_id ?>, '<?= htmlspecialchars($retreat['title']) ?>')">
                                <i class="fas fa-share"></i>
                                Share
                            </button>
                        </div>
                        
                        <!-- Availability Status -->
                        <?php if ($retreat['available_this_shabbos']): ?>
                        <div class="availability-badge available">
                            <i class="fas fa-calendar-check"></i>
                            Available this Shabbos
                        </div>
                        <?php endif; ?>
                        
                        <!-- Property Badges -->
                        <div class="property-badges">
                            <?php if ($retreat['verified']): ?>
                            <span class="badge verified-badge">
                                <i class="fas fa-check-circle"></i>
                                Verified
                            </span>
                            <?php endif; ?>
                            <?php if ($retreat['trusted_host']): ?>
                            <span class="badge trusted-badge">
                                <i class="fas fa-shield-alt"></i>
                                Trusted Host
                            </span>
                            <?php endif; ?>
                            <?php if ($retreat['featured']): ?>
                            <span class="badge featured-badge">
                                <i class="fas fa-star"></i>
                                Featured
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Host Information -->
                    <?php if ($retreat['host_id']): ?>
                    <div class="host-card">
                        <h3 class="card-title">About the Host</h3>
                        <div class="host-info">
                            <?php if ($retreat['profile_image']): ?>
                            <img src="<?= htmlspecialchars($retreat['profile_image']) ?>" 
                                 alt="<?= htmlspecialchars($retreat['first_name'] . ' ' . $retreat['last_name']) ?>" 
                                 class="host-avatar">
                            <?php else: ?>
                            <div class="host-avatar-placeholder">
                                <?= strtoupper(substr($retreat['first_name'], 0, 1)) ?>
                            </div>
                            <?php endif; ?>
                            <div class="host-details">
                                <div class="host-name">
                                    <?= htmlspecialchars($retreat['first_name'] . ' ' . $retreat['last_name']) ?>
                                </div>
                                <div class="host-stats">
                                    <span>Member since <?= date('M Y', strtotime($retreat['created_at'])) ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="host-actions">
                            <button class="btn btn-outline-primary btn-sm w-100 mb-2" onclick="contactHost(<?= $retreat_id ?>)">
                                <i class="fas fa-envelope"></i>
                                Message Host
                            </button>
                            <button class="btn btn-outline-secondary btn-sm w-100" onclick="viewHostProfile(<?= $retreat['host_id'] ?>)">
                                <i class="fas fa-user"></i>
                                View Profile
                            </button>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Property Rules -->
                    <div class="rules-card">
                        <h3 class="card-title">Property Rules</h3>
                        <ul class="rules-list">
                            <li>Minimum stay: <?= $retreat['min_stay_nights'] ?> night<?= $retreat['min_stay_nights'] > 1 ? 's' : '' ?></li>
                            <li>Maximum stay: <?= $retreat['max_stay_nights'] ?> nights</li>
                            <?php if ($retreat['private_entrance']): ?>
                            <li>Private entrance available</li>
                            <?php endif; ?>
                            <?php if ($retreat['kosher_kitchen']): ?>
                            <li>Kosher kitchen (<?= ucfirst($retreat['kitchen_type']) ?>)</li>
                            <?php endif; ?>
                            <?php if ($retreat['no_stairs']): ?>
                            <li>No stairs - ground floor access</li>
                            <?php endif; ?>
                            <?php if ($retreat['accessible']): ?>
                            <li>Wheelchair accessible</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- RELATED RETREATS -->
<?php if (!empty($related_retreats)): ?>
<section class="related-retreats" data-scroll>
    <div class="container">
        <h2 class="section-title">Similar Properties in <?= htmlspecialchars($retreat['location_name']) ?></h2>
        <div class="retreats-grid">
            <?php foreach ($related_retreats as $related): ?>
            <div class="retreat-card">
                <div class="retreat-image">
                    <?php if ($related['image_paths']): ?>
                        <?php $related_images = json_decode($related['image_paths'], true); ?>
                        <img src="<?= htmlspecialchars($related_images[0] ?? '/images/elite-placeholder.svg') ?>" 
                             alt="<?= htmlspecialchars($related['title']) ?>">
                    <?php else: ?>
                        <img src="/images/elite-placeholder.svg" 
                             alt="<?= htmlspecialchars($related['title']) ?>">
                    <?php endif; ?>
                    
                    <div class="category-badge">
                        <span class="category-emoji"><?= htmlspecialchars($related['category_emoji']) ?></span>
                        <?= htmlspecialchars($related['category_name']) ?>
                    </div>
                </div>
                
                <div class="retreat-content">
                    <h3 class="retreat-title"><?= htmlspecialchars($related['title']) ?></h3>
                    <div class="retreat-location">
                        <i class="fas fa-map-marker-alt"></i>
                        <?= htmlspecialchars($related['location_name']) ?>, <?= htmlspecialchars($related['location_region']) ?>
                    </div>
                    
                    <div class="retreat-details">
                        <div class="detail-item">
                            <i class="fas fa-users"></i>
                            <span>Sleeps <?= $related['guest_capacity'] ?></span>
                        </div>
                        <div class="detail-item">
                            <i class="fas fa-bed"></i>
                            <span><?= $related['bedrooms'] ?> bedroom<?= $related['bedrooms'] > 1 ? 's' : '' ?></span>
                        </div>
                    </div>
                    
                    <div class="retreat-footer">
                        <div class="retreat-price">
                            <span class="price-amount">£<?= number_format($related['price_per_night']) ?></span>
                            <span class="price-unit">per night</span>
                        </div>
                        <a href="/retreat.php?id=<?= $related['id'] ?>" class="btn-jshuk-primary btn-sm">
                            View Details
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<script>
// Gallery functionality
function changeMainImage(imageSrc, thumbnailElement) {
    document.getElementById('main-image').src = imageSrc;
    
    // Update active thumbnail
    document.querySelectorAll('.thumbnail').forEach(thumb => thumb.classList.remove('active'));
    thumbnailElement.classList.add('active');
}

// Contact host function
function contactHost(retreatId) {
    // This would open a contact modal or redirect to contact page
    alert('Contact functionality coming soon!');
}

// Check availability function
function checkAvailability(retreatId) {
    // This would open a calendar modal
    alert('Availability calendar coming soon!');
}

// Share retreat function
function shareRetreat(retreatId, retreatTitle) {
    if (navigator.share) {
        navigator.share({
            title: retreatTitle,
            url: `${window.location.origin}/retreat.php?id=${retreatId}`
        });
    } else {
        // Fallback to copying URL
        const url = `${window.location.origin}/retreat.php?id=${retreatId}`;
        navigator.clipboard.writeText(url).then(() => {
            alert('Link copied to clipboard!');
        });
    }
}

// View host profile function
function viewHostProfile(hostId) {
    // This would redirect to host profile page
    alert('Host profile page coming soon!');
}

// Auto-hide success message
document.addEventListener('DOMContentLoaded', function() {
    const successBanner = document.querySelector('.success-banner');
    if (successBanner) {
        setTimeout(() => {
            successBanner.style.opacity = '0';
            setTimeout(() => {
                successBanner.style.display = 'none';
            }, 300);
        }, 5000);
    }
});
</script>

<?php include 'includes/footer_main.php'; ?> 