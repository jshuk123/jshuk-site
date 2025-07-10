<?php
/**
 * Business Testimonials Component
 * Displays customer reviews, ratings, and testimonials
 */

// Get business data from the parent scope
$business_id = $business['id'] ?? null;
$business_name = $business['name'] ?? 'Business Name';

// Fetch testimonials from database
$testimonials = [];
$rating_stats = [
    'average' => 0,
    'total' => 0,
    'distribution' => [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0]
];

try {
    // Get testimonials for this business
    $testimonial_query = "SELECT t.*, u.name as reviewer_name, u.profile_image 
                         FROM testimonials t 
                         LEFT JOIN users u ON t.user_id = u.id 
                         WHERE t.business_id = ? AND t.is_approved = 1 
                         ORDER BY t.created_at DESC 
                         LIMIT 10";
    
    $testimonial_stmt = $pdo->prepare($testimonial_query);
    $testimonial_stmt->execute([$business_id]);
    $testimonials = $testimonial_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate rating statistics
    if (!empty($testimonials)) {
        $total_rating = 0;
        $total_reviews = count($testimonials);
        
        foreach ($testimonials as $testimonial) {
            $rating = intval($testimonial['rating']);
            $total_rating += $rating;
            
            if (isset($rating_stats['distribution'][$rating])) {
                $rating_stats['distribution'][$rating]++;
            }
        }
        
        $rating_stats['average'] = round($total_rating / $total_reviews, 1);
        $rating_stats['total'] = $total_reviews;
    }
    
} catch (Exception $e) {
    // Handle error silently - testimonials will show as empty
    error_log("Error fetching testimonials: " . $e->getMessage());
}

// Function to generate star rating HTML
function generateStarRating($rating) {
    $html = '';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $rating) {
            $html .= '<i class="fas fa-star text-warning"></i>';
        } elseif ($i - 0.5 <= $rating) {
            $html .= '<i class="fas fa-star-half-alt text-warning"></i>';
        } else {
            $html .= '<i class="far fa-star text-muted"></i>';
        }
    }
    return $html;
}

// Function to get time ago
function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    $time = ($time < 1) ? 1 : $time;
    $tokens = array(
        31536000 => 'year',
        2592000 => 'month',
        604800 => 'week',
        86400 => 'day',
        3600 => 'hour',
        60 => 'minute',
        1 => 'second'
    );

    foreach ($tokens as $unit => $text) {
        if ($time < $unit) continue;
        $numberOfUnits = floor($time / $unit);
        return $numberOfUnits . ' ' . $text . (($numberOfUnits > 1) ? 's' : '') . ' ago';
    }
}
?>

<!-- Business Testimonials Section -->
<section class="business-testimonials py-5" id="testimonials">
    <div class="container">
        <div class="section-header text-center mb-5">
            <h2 class="section-title">
                <i class="fas fa-star text-warning me-2"></i>
                What Our Customers Say
            </h2>
            <p class="section-subtitle text-muted">
                Real reviews from satisfied customers of <?php echo htmlspecialchars($business_name); ?>
            </p>
            <div class="section-divider mx-auto"></div>
        </div>

        <div class="row">
            <!-- Rating Overview -->
            <div class="col-lg-4 mb-4">
                <div class="rating-overview-card">
                    <div class="overview-header text-center mb-4">
                        <div class="average-rating">
                            <div class="rating-number"><?php echo $rating_stats['average']; ?></div>
                            <div class="rating-stars">
                                <?php echo generateStarRating($rating_stats['average']); ?>
                            </div>
                            <div class="total-reviews">
                                Based on <?php echo $rating_stats['total']; ?> reviews
                            </div>
                        </div>
                    </div>

                    <!-- Rating Distribution -->
                    <?php if ($rating_stats['total'] > 0): ?>
                    <div class="rating-distribution">
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                        <div class="rating-bar-row">
                            <div class="stars-count">
                                <?php echo $i; ?> <i class="fas fa-star text-warning"></i>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-warning" 
                                     style="width: <?php echo ($rating_stats['distribution'][$i] / $rating_stats['total']) * 100; ?>%">
                                </div>
                            </div>
                            <div class="count">
                                <?php echo $rating_stats['distribution'][$i]; ?>
                            </div>
                        </div>
                        <?php endfor; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Write Review Button -->
                    <div class="write-review-section mt-4 text-center">
                        <button class="btn btn-primary btn-lg w-100" 
                                onclick="openReviewModal()">
                            <i class="fas fa-edit me-2"></i>
                            Write a Review
                        </button>
                    </div>
                </div>
            </div>

            <!-- Testimonials List -->
            <div class="col-lg-8">
                <?php if (!empty($testimonials)): ?>
                <div class="testimonials-list">
                    <?php foreach ($testimonials as $testimonial): ?>
                    <div class="testimonial-card mb-4">
                        <div class="testimonial-header">
                            <div class="reviewer-info">
                                <div class="reviewer-avatar">
                                    <?php if (!empty($testimonial['profile_image'])): ?>
                                    <img src="/uploads/users/<?php echo $testimonial['user_id']; ?>/<?php echo htmlspecialchars($testimonial['profile_image']); ?>" 
                                         alt="<?php echo htmlspecialchars($testimonial['reviewer_name']); ?>"
                                         class="avatar-img">
                                    <?php else: ?>
                                    <div class="avatar-placeholder">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <div class="reviewer-details">
                                    <h6 class="reviewer-name">
                                        <?php echo htmlspecialchars($testimonial['reviewer_name'] ?? 'Anonymous'); ?>
                                    </h6>
                                    <div class="review-meta">
                                        <div class="review-rating">
                                            <?php echo generateStarRating($testimonial['rating']); ?>
                                        </div>
                                        <span class="review-date">
                                            <?php echo timeAgo($testimonial['created_at']); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <?php if ($testimonial['rating'] >= 4): ?>
                            <div class="verified-badge">
                                <i class="fas fa-check-circle text-success"></i>
                                Verified
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="testimonial-content">
                            <h6 class="testimonial-title">
                                <?php echo htmlspecialchars($testimonial['title'] ?? ''); ?>
                            </h6>
                            <p class="testimonial-text">
                                "<?php echo htmlspecialchars($testimonial['content']); ?>"
                            </p>
                        </div>

                        <?php if (!empty($testimonial['response'])): ?>
                        <div class="business-response">
                            <div class="response-header">
                                <i class="fas fa-reply text-primary me-2"></i>
                                <strong>Response from <?php echo htmlspecialchars($business_name); ?></strong>
                            </div>
                            <p class="response-text">
                                <?php echo htmlspecialchars($testimonial['response']); ?>
                            </p>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Load More Button -->
                <?php if (count($testimonials) >= 10): ?>
                <div class="load-more-section text-center">
                    <button class="btn btn-outline-primary" onclick="loadMoreReviews()">
                        <i class="fas fa-plus me-2"></i>
                        Load More Reviews
                    </button>
                </div>
                <?php endif; ?>

                <?php else: ?>
                <!-- No Reviews State -->
                <div class="no-reviews-state text-center py-5">
                    <div class="no-reviews-icon mb-3">
                        <i class="fas fa-star text-muted" style="font-size: 3rem;"></i>
                    </div>
                    <h5 class="no-reviews-title">No Reviews Yet</h5>
                    <p class="no-reviews-text text-muted mb-4">
                        Be the first to share your experience with <?php echo htmlspecialchars($business_name); ?>
                    </p>
                    <button class="btn btn-primary btn-lg" onclick="openReviewModal()">
                        <i class="fas fa-edit me-2"></i>
                        Write the First Review
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- Review Modal -->
<div class="modal fade" id="reviewModal" tabindex="-1" aria-labelledby="reviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reviewModalLabel">
                    <i class="fas fa-edit text-primary me-2"></i>
                    Write a Review for <?php echo htmlspecialchars($business_name); ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="reviewForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Rating *</label>
                        <div class="star-rating">
                            <input type="radio" name="rating" value="5" id="star5" required>
                            <label for="star5"><i class="fas fa-star"></i></label>
                            <input type="radio" name="rating" value="4" id="star4">
                            <label for="star4"><i class="fas fa-star"></i></label>
                            <input type="radio" name="rating" value="3" id="star3">
                            <label for="star3"><i class="fas fa-star"></i></label>
                            <input type="radio" name="rating" value="2" id="star2">
                            <label for="star2"><i class="fas fa-star"></i></label>
                            <input type="radio" name="rating" value="1" id="star1">
                            <label for="star1"><i class="fas fa-star"></i></label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="reviewTitle" class="form-label">Review Title</label>
                        <input type="text" class="form-control" id="reviewTitle" name="title" 
                               placeholder="Summarize your experience">
                    </div>

                    <div class="mb-3">
                        <label for="reviewContent" class="form-label">Your Review *</label>
                        <textarea class="form-control" id="reviewContent" name="content" rows="4" 
                                  required placeholder="Share your experience with this business..."></textarea>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="anonymousReview" name="anonymous">
                            <label class="form-check-label" for="anonymousReview">
                                Submit anonymously
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane me-2"></i>
                        Submit Review
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openReviewModal() {
    const modal = new bootstrap.Modal(document.getElementById('reviewModal'));
    modal.show();
}

function loadMoreReviews() {
    // TODO: Implement AJAX loading of more reviews
    alert('Load more functionality coming soon!');
}

// Star rating functionality
document.querySelectorAll('.star-rating input').forEach(input => {
    input.addEventListener('change', function() {
        const rating = this.value;
        const stars = this.parentElement.querySelectorAll('label i');
        
        stars.forEach((star, index) => {
            if (index < rating) {
                star.classList.remove('far');
                star.classList.add('fas', 'text-warning');
            } else {
                star.classList.remove('fas', 'text-warning');
                star.classList.add('far');
            }
        });
    });
});

// Review form submission
document.getElementById('reviewForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('business_id', '<?php echo $business_id; ?>');
    
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Submitting...';
    submitBtn.disabled = true;
    
    fetch('/actions/submit_review.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Thank you! Your review has been submitted and will be reviewed shortly.');
            bootstrap.Modal.getInstance(document.getElementById('reviewModal')).hide();
            this.reset();
            // Reload page to show new review
            setTimeout(() => location.reload(), 1000);
        } else {
            alert('Sorry, there was an error submitting your review. Please try again.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Sorry, there was an error submitting your review. Please try again.');
    })
    .finally(() => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
});
</script> 