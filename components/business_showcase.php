<?php
/**
 * Business Showcase Component
 * Displays business main image, highlights, and CTAs
 */

// Get business data from the parent scope
$business_id = $business['id'] ?? null;
$business_name = $business['name'] ?? 'Business Name';
$business_description = $business['description'] ?? '';
$business_category = $business['category'] ?? '';
$business_location = $business['location'] ?? '';
$business_phone = $business['phone'] ?? '';
$business_email = $business['email'] ?? '';
$business_website = $business['website'] ?? '';
$business_rating = $business['rating'] ?? 0;
$business_review_count = $business['review_count'] ?? 0;

// Get main business image
$main_image = '';
if (!empty($business['main_image'])) {
    $main_image = '/uploads/businesses/' . $business_id . '/images/' . $business['main_image'];
} else {
    $main_image = '/images/elite-placeholder.svg';
}

// Get business highlights/features
$highlights = [];
if (!empty($business['features'])) {
    $features_array = explode(',', $business['features']);
    $highlights = array_slice($features_array, 0, 4); // Show max 4 highlights
}

// Generate star rating HTML
$star_rating = '';
for ($i = 1; $i <= 5; $i++) {
    if ($i <= $business_rating) {
        $star_rating .= '<i class="fas fa-star text-warning"></i>';
    } elseif ($i - 0.5 <= $business_rating) {
        $star_rating .= '<i class="fas fa-star-half-alt text-warning"></i>';
    } else {
        $star_rating .= '<i class="far fa-star text-muted"></i>';
    }
}
?>

<!-- Business Showcase Section -->
<section class="business-showcase py-5">
    <div class="container">
        <div class="row align-items-center">
            <!-- Main Image Column -->
            <div class="col-lg-6 mb-4 mb-lg-0">
                <div class="showcase-image-container">
                    <img src="<?php echo htmlspecialchars($main_image); ?>" 
                         alt="<?php echo htmlspecialchars($business_name); ?>" 
                         class="showcase-main-image">
                    <div class="image-overlay">
                        <div class="overlay-content">
                            <i class="fas fa-camera text-white"></i>
                            <span class="text-white ms-2">View Gallery</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Content Column -->
            <div class="col-lg-6">
                <div class="showcase-content">
                    <!-- Business Title and Rating -->
                    <div class="business-header mb-3">
                        <h1 class="business-title mb-2"><?php echo htmlspecialchars($business_name); ?></h1>
                        <div class="business-rating d-flex align-items-center">
                            <div class="stars me-2">
                                <?php echo $star_rating; ?>
                            </div>
                            <span class="rating-text">
                                <?php echo number_format($business_rating, 1); ?> 
                                (<?php echo $business_review_count; ?> reviews)
                            </span>
                        </div>
                    </div>
                    
                    <!-- Category and Location -->
                    <div class="business-meta mb-3">
                        <span class="badge bg-primary me-2">
                            <i class="fas fa-tag me-1"></i><?php echo htmlspecialchars($business_category); ?>
                        </span>
                        <span class="location-text">
                            <i class="fas fa-map-marker-alt text-danger me-1"></i>
                            <?php echo htmlspecialchars($business_location); ?>
                        </span>
                    </div>
                    
                    <!-- Description -->
                    <div class="business-description mb-4">
                        <p class="text-muted">
                            <?php 
                            $short_desc = strlen($business_description) > 200 
                                ? substr($business_description, 0, 200) . '...' 
                                : $business_description;
                            echo htmlspecialchars($short_desc); 
                            ?>
                        </p>
                    </div>
                    
                    <!-- Highlights/Features -->
                    <?php if (!empty($highlights)): ?>
                    <div class="business-highlights mb-4">
                        <h6 class="highlights-title mb-3">
                            <i class="fas fa-star text-warning me-2"></i>What We Offer
                        </h6>
                        <div class="highlights-grid">
                            <?php foreach ($highlights as $highlight): ?>
                            <div class="highlight-item">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                <span><?php echo htmlspecialchars(trim($highlight)); ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Action Buttons -->
                    <div class="action-buttons">
                        <div class="row g-3">
                            <?php if (!empty($business_phone)): ?>
                            <div class="col-sm-6">
                                <a href="tel:<?php echo htmlspecialchars($business_phone); ?>" 
                                   class="btn btn-primary btn-lg w-100">
                                    <i class="fas fa-phone me-2"></i>Call Now
                                </a>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($business_website)): ?>
                            <div class="col-sm-6">
                                <a href="<?php echo htmlspecialchars($business_website); ?>" 
                                   target="_blank" 
                                   class="btn btn-outline-primary btn-lg w-100">
                                    <i class="fas fa-globe me-2"></i>Visit Website
                                </a>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (empty($business_phone) && empty($business_website)): ?>
                            <div class="col-12">
                                <button class="btn btn-primary btn-lg w-100" disabled>
                                    <i class="fas fa-info-circle me-2"></i>Contact Info Unavailable
                                </button>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Additional Actions -->
                        <div class="additional-actions mt-3">
                            <div class="row g-2">
                                <div class="col-6">
                                    <button class="btn btn-outline-secondary w-100" 
                                            onclick="shareBusiness()">
                                        <i class="fas fa-share-alt me-1"></i>Share
                                    </button>
                                </div>
                                <div class="col-6">
                                    <button class="btn btn-outline-secondary w-100" 
                                            onclick="saveBusiness()">
                                        <i class="fas fa-bookmark me-1"></i>Save
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
function shareBusiness() {
    if (navigator.share) {
        navigator.share({
            title: '<?php echo addslashes($business_name); ?>',
            text: 'Check out this amazing business!',
            url: window.location.href
        });
    } else {
        // Fallback: copy to clipboard
        navigator.clipboard.writeText(window.location.href).then(() => {
            alert('Link copied to clipboard!');
        });
    }
}

function saveBusiness() {
    // TODO: Implement save functionality
    alert('Save feature coming soon!');
}
</script> 