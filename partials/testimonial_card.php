<?php
/**
 * Testimonial Card Component
 * Displays individual testimonials with rating, photo, and content
 * Version: 1.2
 */

// Get parameters
$testimonial = $testimonial ?? [];
$show_rating = $show_rating ?? true;
$show_photo = $show_photo ?? true;
$show_date = $show_date ?? true;
$show_featured_badge = $show_featured_badge ?? true;
$layout = $layout ?? 'card'; // card, compact, list

// Extract testimonial data
$id = $testimonial['id'] ?? 0;
$name = $testimonial['name'] ?? 'Anonymous';
$content = $testimonial['testimonial'] ?? '';
$rating = $testimonial['rating'] ?? 0;
$photo_url = $testimonial['photo_url'] ?? null;
$submitted_at = $testimonial['submitted_at'] ?? '';
$featured = $testimonial['featured'] ?? false;
$status = $testimonial['status'] ?? 'approved';

// Only show approved testimonials
if ($status !== 'approved') {
    return;
}

// Format date
$date_formatted = '';
if ($show_date && $submitted_at) {
    $date = new DateTime($submitted_at);
    $now = new DateTime();
    $diff = $date->diff($now);
    
    if ($diff->days == 0) {
        $date_formatted = 'Today';
    } elseif ($diff->days == 1) {
        $date_formatted = 'Yesterday';
    } elseif ($diff->days < 7) {
        $date_formatted = $diff->days . ' days ago';
    } elseif ($diff->days < 30) {
        $weeks = floor($diff->days / 7);
        $date_formatted = $weeks . ' week' . ($weeks > 1 ? 's' : '') . ' ago';
    } else {
        $date_formatted = $date->format('M j, Y');
    }
}

// Generate star rating HTML
function generateStars($rating) {
    $html = '';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $rating) {
            $html .= '<i class="fas fa-star text-warning"></i>';
        } else {
            $html .= '<i class="far fa-star text-muted"></i>';
        }
    }
    return $html;
}
?>

<?php if ($layout === 'card'): ?>
    <!-- Card Layout -->
    <div class="testimonial-card card h-100 shadow-sm">
        <div class="card-body">
            <div class="d-flex align-items-start mb-3">
                <?php if ($show_photo && $photo_url): ?>
                    <div class="testimonial-photo me-3">
                        <img src="<?php echo htmlspecialchars($photo_url); ?>" 
                             alt="<?php echo htmlspecialchars($name); ?>" 
                             class="rounded-circle" 
                             width="50" height="50"
                             style="object-fit: cover;">
                    </div>
                <?php endif; ?>
                
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="mb-1 fw-bold"><?php echo htmlspecialchars($name); ?></h6>
                            <?php if ($show_rating && $rating): ?>
                                <div class="testimonial-rating mb-1">
                                    <?php echo generateStars($rating); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="text-end">
                            <?php if ($show_featured_badge && $featured): ?>
                                <span class="badge bg-warning text-dark mb-1">
                                    <i class="fas fa-star me-1"></i>Featured
                                </span>
                            <?php endif; ?>
                            <?php if ($show_date && $date_formatted): ?>
                                <div class="text-muted small"><?php echo $date_formatted; ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="testimonial-content">
                <p class="mb-0 fst-italic">"<?php echo htmlspecialchars($content); ?>"</p>
            </div>
        </div>
    </div>

<?php elseif ($layout === 'compact'): ?>
    <!-- Compact Layout -->
    <div class="testimonial-compact d-flex align-items-start p-3 border-bottom">
        <?php if ($show_photo && $photo_url): ?>
            <div class="testimonial-photo me-3">
                <img src="<?php echo htmlspecialchars($photo_url); ?>" 
                     alt="<?php echo htmlspecialchars($name); ?>" 
                     class="rounded-circle" 
                     width="40" height="40"
                     style="object-fit: cover;">
            </div>
        <?php endif; ?>
        
        <div class="flex-grow-1">
            <div class="d-flex justify-content-between align-items-start mb-1">
                <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($name); ?></h6>
                <div class="text-end">
                    <?php if ($show_featured_badge && $featured): ?>
                        <span class="badge bg-warning text-dark me-2">
                            <i class="fas fa-star"></i>
                        </span>
                    <?php endif; ?>
                    <?php if ($show_date && $date_formatted): ?>
                        <small class="text-muted"><?php echo $date_formatted; ?></small>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($show_rating && $rating): ?>
                <div class="testimonial-rating mb-2">
                    <?php echo generateStars($rating); ?>
                </div>
            <?php endif; ?>
            
            <p class="mb-0 small">"<?php echo htmlspecialchars($content); ?>"</p>
        </div>
    </div>

<?php else: ?>
    <!-- List Layout -->
    <div class="testimonial-list-item list-group-item">
        <div class="d-flex justify-content-between align-items-start">
            <div class="flex-grow-1">
                <div class="d-flex align-items-center mb-2">
                    <h6 class="mb-0 me-2"><?php echo htmlspecialchars($name); ?></h6>
                    <?php if ($show_featured_badge && $featured): ?>
                        <span class="badge bg-warning text-dark">
                            <i class="fas fa-star me-1"></i>Featured
                        </span>
                    <?php endif; ?>
                </div>
                
                <?php if ($show_rating && $rating): ?>
                    <div class="testimonial-rating mb-2">
                        <?php echo generateStars($rating); ?>
                    </div>
                <?php endif; ?>
                
                <p class="mb-0">"<?php echo htmlspecialchars($content); ?>"</p>
            </div>
            
            <?php if ($show_date && $date_formatted): ?>
                <small class="text-muted ms-3"><?php echo $date_formatted; ?></small>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<style>
.testimonial-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.testimonial-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1) !important;
}

.testimonial-photo img {
    border: 2px solid #f8f9fa;
}

.testimonial-rating {
    line-height: 1;
}

.testimonial-content {
    line-height: 1.6;
}

.testimonial-compact:hover {
    background-color: #f8f9fa;
}

.testimonial-list-item:hover {
    background-color: #f8f9fa;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .testimonial-card .card-body {
        padding: 1rem;
    }
    
    .testimonial-compact {
        padding: 0.75rem;
    }
    
    .testimonial-photo img {
        width: 35px !important;
        height: 35px !important;
    }
}
</style> 