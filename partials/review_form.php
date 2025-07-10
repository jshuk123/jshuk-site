<?php
/**
 * Review Submission Form Component
 * Handles both star ratings and testimonials based on subscription tier
 * Version: 1.2
 */

// Get parameters
$business_id = $business_id ?? 0;
$business_name = $business_name ?? 'Business';
$subscription_tier = $subscription_tier ?? 'basic';
$testimonial_limit = $testimonial_limit ?? 0;
$current_testimonials = $current_testimonials ?? 0;

// Determine if testimonials are allowed
$testimonials_allowed = $subscription_tier !== 'basic';
$testimonials_available = $testimonial_limit === null || $current_testimonials < $testimonial_limit;
?>

<div class="review-form-container">
    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">
                <i class="fas fa-star me-2"></i>
                Leave a Review for <?php echo htmlspecialchars($business_name); ?>
            </h5>
        </div>
        <div class="card-body">
            
            <!-- Subscription Tier Info -->
            <?php if (!$testimonials_allowed): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Basic Plan:</strong> You can leave a star rating. Testimonials are available for Premium and Premium Plus plans.
                </div>
            <?php elseif (!$testimonials_available): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Testimonial Limit Reached:</strong> This business has reached their testimonial limit (<?php echo $testimonial_limit; ?>). You can still leave a star rating.
                </div>
            <?php endif; ?>

            <form id="review-form" action="/submit_review.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="business_id" value="<?php echo $business_id; ?>">
                
                <!-- Star Rating Section -->
                <div class="mb-4">
                    <label class="form-label fw-bold">Your Rating *</label>
                    <div class="star-rating-section">
                        <?php include 'star_rating.php'; ?>
                    </div>
                    <div class="form-text">Click on the stars to rate from 1 to 5</div>
                </div>

                <!-- Testimonial Section (if allowed) -->
                <?php if ($testimonials_allowed && $testimonials_available): ?>
                    <div class="mb-4">
                        <label for="name" class="form-label">Your Name (optional)</label>
                        <input type="text" name="name" id="name" class="form-control" 
                               placeholder="Enter your name or leave anonymous">
                    </div>

                    <div class="mb-4">
                        <label for="testimonial" class="form-label">Your Review (optional)</label>
                        <textarea name="testimonial" id="testimonial" class="form-control" rows="4" 
                                  placeholder="Share your experience with this business..."></textarea>
                        <div class="form-text">
                            Your testimonial will be reviewed by the business before being published.
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="photo" class="form-label">Photo (optional)</label>
                        <input type="file" name="photo" id="photo" class="form-control" 
                               accept="image/jpeg,image/png,image/gif,image/webp">
                        <div class="form-text">
                            Upload a photo to accompany your review (JPEG, PNG, GIF, WebP)
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Submit Button -->
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-paper-plane me-2"></i>
                        Submit Review
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.review-form-container {
    max-width: 600px;
    margin: 0 auto;
}

.star-rating-section {
    padding: 1rem;
    background-color: #f8f9fa;
    border-radius: 0.375rem;
    text-align: center;
}

.review-form-container .card {
    border: none;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.review-form-container .card-header {
    border-bottom: none;
}

.form-control:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

.btn-primary {
    background-color: #0d6efd;
    border-color: #0d6efd;
}

.btn-primary:hover {
    background-color: #0b5ed7;
    border-color: #0a58ca;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('review-form');
    const testimonialField = document.getElementById('testimonial');
    const photoField = document.getElementById('photo');
    
    // Form submission handler
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Get form data
        const formData = new FormData(form);
        
        // Validate rating
        const rating = formData.get('rating');
        if (!rating || rating < 1 || rating > 5) {
            alert('Please select a rating between 1 and 5 stars.');
            return;
        }
        
        // Show loading state
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Submitting...';
        submitBtn.disabled = true;
        
        // Submit form via AJAX
        fetch('/submit_review.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message
                showMessage('success', data.message);
                
                // Reset form
                form.reset();
                
                // Reload page after a delay to show updated ratings
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            } else {
                showMessage('error', data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showMessage('error', 'An error occurred while submitting your review. Please try again.');
        })
        .finally(() => {
            // Restore button state
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        });
    });
    
    // Character counter for testimonial
    if (testimonialField) {
        testimonialField.addEventListener('input', function() {
            const maxLength = 500;
            const currentLength = this.value.length;
            const remaining = maxLength - currentLength;
            
            // Update character counter
            let counter = this.parentNode.querySelector('.char-counter');
            if (!counter) {
                counter = document.createElement('div');
                counter.className = 'char-counter form-text text-end';
                this.parentNode.appendChild(counter);
            }
            
            counter.textContent = `${currentLength}/${maxLength} characters`;
            
            if (remaining < 0) {
                counter.classList.add('text-danger');
            } else {
                counter.classList.remove('text-danger');
            }
        });
    }
    
    // File size validation for photo
    if (photoField) {
        photoField.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const maxSize = 5 * 1024 * 1024; // 5MB
                if (file.size > maxSize) {
                    alert('Photo file size must be less than 5MB.');
                    this.value = '';
                }
            }
        });
    }
});

function showMessage(type, message) {
    // Remove existing messages
    const existingMessages = document.querySelectorAll('.alert-message');
    existingMessages.forEach(msg => msg.remove());
    
    // Create new message
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-message alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    // Insert before form
    const form = document.getElementById('review-form');
    form.parentNode.insertBefore(alertDiv, form);
    
    // Auto-dismiss success messages
    if (type === 'success') {
        setTimeout(() => {
            alertDiv.remove();
        }, 5000);
    }
}
</script> 