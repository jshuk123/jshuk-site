<?php
/**
 * Business Contact Component
 * Displays contact information, hours, location, and contact form
 */

// Get business data from the parent scope
$business_id = $business['id'] ?? null;
$business_name = $business['name'] ?? 'Business Name';
$business_phone = $business['phone'] ?? '';
$business_email = $business['email'] ?? '';
$business_website = $business['website'] ?? '';
$business_address = $business['address'] ?? '';
$business_location = $business['location'] ?? '';
$business_hours = $business['hours'] ?? '';
$business_owner = $business['owner'] ?? '';
$business_owner_phone = $business['owner_phone'] ?? '';
$business_owner_email = $business['owner_email'] ?? '';

// Parse business hours if they exist
$hours_array = [];
if (!empty($business_hours)) {
    $hours_array = json_decode($business_hours, true) ?: [];
}

// Get current day and time
$current_day = strtolower(date('l'));
$current_time = date('H:i');

// Function to check if business is currently open
function isBusinessOpen($hours_array, $current_day, $current_time) {
    if (empty($hours_array) || !isset($hours_array[$current_day])) {
        return false;
    }
    
    $day_hours = $hours_array[$current_day];
    if ($day_hours['status'] !== 'open') {
        return false;
    }
    
    $open_time = $day_hours['open'];
    $close_time = $day_hours['close'];
    
    return $current_time >= $open_time && $current_time <= $close_time;
}

$is_open = isBusinessOpen($hours_array, $current_day, $current_time);
?>

<!-- Business Contact Section -->
<section class="business-contact py-5" id="contact">
    <div class="container">
        <div class="section-header text-center mb-5">
            <h2 class="section-title">
                <i class="fas fa-envelope text-primary me-2"></i>
                Get in Touch
            </h2>
            <p class="section-subtitle text-muted">
                We'd love to hear from you. Contact us today!
            </p>
            <div class="section-divider mx-auto"></div>
        </div>

        <div class="row g-5">
            <!-- Contact Information -->
            <div class="col-lg-4">
                <div class="contact-info-card">
                    <h4 class="contact-info-title mb-4">
                        <i class="fas fa-info-circle text-primary me-2"></i>
                        Contact Information
                    </h4>

                    <!-- Phone -->
                    <?php if (!empty($business_phone)): ?>
                    <div class="contact-item mb-3">
                        <div class="contact-icon">
                            <i class="fas fa-phone text-primary"></i>
                        </div>
                        <div class="contact-content">
                            <h6 class="contact-label">Phone</h6>
                            <a href="tel:<?php echo htmlspecialchars($business_phone); ?>" 
                               class="contact-value">
                                <?php echo htmlspecialchars($business_phone); ?>
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Email -->
                    <?php if (!empty($business_email)): ?>
                    <div class="contact-item mb-3">
                        <div class="contact-icon">
                            <i class="fas fa-envelope text-primary"></i>
                        </div>
                        <div class="contact-content">
                            <h6 class="contact-label">Email</h6>
                            <a href="mailto:<?php echo htmlspecialchars($business_email); ?>" 
                               class="contact-value">
                                <?php echo htmlspecialchars($business_email); ?>
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Website -->
                    <?php if (!empty($business_website)): ?>
                    <div class="contact-item mb-3">
                        <div class="contact-icon">
                            <i class="fas fa-globe text-primary"></i>
                        </div>
                        <div class="contact-content">
                            <h6 class="contact-label">Website</h6>
                            <a href="<?php echo htmlspecialchars($business_website); ?>" 
                               target="_blank" 
                               class="contact-value">
                                Visit Website
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Address -->
                    <?php if (!empty($business_address)): ?>
                    <div class="contact-item mb-3">
                        <div class="contact-icon">
                            <i class="fas fa-map-marker-alt text-primary"></i>
                        </div>
                        <div class="contact-content">
                            <h6 class="contact-label">Address</h6>
                            <div class="contact-value">
                                <?php echo nl2br(htmlspecialchars($business_address)); ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Location -->
                    <?php if (!empty($business_location)): ?>
                    <div class="contact-item mb-3">
                        <div class="contact-icon">
                            <i class="fas fa-map text-primary"></i>
                        </div>
                        <div class="contact-content">
                            <h6 class="contact-label">Location</h6>
                            <div class="contact-value">
                                <?php echo htmlspecialchars($business_location); ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Owner Contact (if different from business) -->
                    <?php if (!empty($business_owner) && ($business_owner_phone || $business_owner_email)): ?>
                    <div class="owner-contact mt-4">
                        <h6 class="owner-title mb-3">
                            <i class="fas fa-user text-secondary me-2"></i>
                            Contact Owner
                        </h6>
                        <div class="owner-info">
                            <p class="owner-name mb-2">
                                <strong><?php echo htmlspecialchars($business_owner); ?></strong>
                            </p>
                            <?php if (!empty($business_owner_phone)): ?>
                            <div class="owner-item mb-2">
                                <i class="fas fa-phone text-secondary me-2"></i>
                                <a href="tel:<?php echo htmlspecialchars($business_owner_phone); ?>">
                                    <?php echo htmlspecialchars($business_owner_phone); ?>
                                </a>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($business_owner_email)): ?>
                            <div class="owner-item">
                                <i class="fas fa-envelope text-secondary me-2"></i>
                                <a href="mailto:<?php echo htmlspecialchars($business_owner_email); ?>">
                                    <?php echo htmlspecialchars($business_owner_email); ?>
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Business Hours -->
            <div class="col-lg-4">
                <div class="hours-card">
                    <h4 class="hours-title mb-4">
                        <i class="fas fa-clock text-primary me-2"></i>
                        Business Hours
                    </h4>

                    <!-- Current Status -->
                    <div class="current-status mb-4">
                        <div class="status-indicator <?php echo $is_open ? 'open' : 'closed'; ?>">
                            <i class="fas fa-circle me-2"></i>
                            <span class="status-text">
                                <?php echo $is_open ? 'Currently Open' : 'Currently Closed'; ?>
                            </span>
                        </div>
                    </div>

                    <!-- Hours List -->
                    <?php if (!empty($hours_array)): ?>
                    <div class="hours-list">
                        <?php
                        $days = [
                            'monday' => 'Monday',
                            'tuesday' => 'Tuesday', 
                            'wednesday' => 'Wednesday',
                            'thursday' => 'Thursday',
                            'friday' => 'Friday',
                            'saturday' => 'Saturday',
                            'sunday' => 'Sunday'
                        ];
                        
                        foreach ($days as $day_key => $day_name):
                            $day_data = $hours_array[$day_key] ?? ['status' => 'closed'];
                            $is_current_day = $current_day === $day_key;
                        ?>
                        <div class="hours-item <?php echo $is_current_day ? 'current-day' : ''; ?>">
                            <div class="day-name">
                                <?php echo $day_name; ?>
                                <?php if ($is_current_day): ?>
                                <span class="today-badge">Today</span>
                                <?php endif; ?>
                            </div>
                            <div class="day-hours">
                                <?php if ($day_data['status'] === 'open'): ?>
                                <span class="hours-time">
                                    <?php echo htmlspecialchars($day_data['open']); ?> - 
                                    <?php echo htmlspecialchars($day_data['close']); ?>
                                </span>
                                <?php else: ?>
                                <span class="hours-closed">Closed</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="no-hours">
                        <p class="text-muted">Business hours not available</p>
                    </div>
                    <?php endif; ?>

                    <!-- Quick Actions -->
                    <div class="quick-actions mt-4">
                        <h6 class="actions-title mb-3">Quick Actions</h6>
                        <div class="action-buttons">
                            <?php if (!empty($business_phone)): ?>
                            <a href="tel:<?php echo htmlspecialchars($business_phone); ?>" 
                               class="btn btn-primary btn-sm w-100 mb-2">
                                <i class="fas fa-phone me-2"></i>Call Now
                            </a>
                            <?php endif; ?>
                            
                            <?php if (!empty($business_email)): ?>
                            <a href="mailto:<?php echo htmlspecialchars($business_email); ?>" 
                               class="btn btn-outline-primary btn-sm w-100 mb-2">
                                <i class="fas fa-envelope me-2"></i>Send Email
                            </a>
                            <?php endif; ?>
                            
                            <?php if (!empty($business_website)): ?>
                            <a href="<?php echo htmlspecialchars($business_website); ?>" 
                               target="_blank" 
                               class="btn btn-outline-secondary btn-sm w-100">
                                <i class="fas fa-globe me-2"></i>Visit Website
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contact Form -->
            <div class="col-lg-4">
                <div class="contact-form-card">
                    <h4 class="form-title mb-4">
                        <i class="fas fa-paper-plane text-primary me-2"></i>
                        Send us a Message
                    </h4>

                    <form class="contact-form" id="contactForm">
                        <div class="mb-3">
                            <label for="contactName" class="form-label">Your Name *</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="contactName" 
                                   name="name" 
                                   required>
                        </div>

                        <div class="mb-3">
                            <label for="contactEmail" class="form-label">Email Address *</label>
                            <input type="email" 
                                   class="form-control" 
                                   id="contactEmail" 
                                   name="email" 
                                   required>
                        </div>

                        <div class="mb-3">
                            <label for="contactPhone" class="form-label">Phone Number</label>
                            <input type="tel" 
                                   class="form-control" 
                                   id="contactPhone" 
                                   name="phone">
                        </div>

                        <div class="mb-3">
                            <label for="contactSubject" class="form-label">Subject *</label>
                            <select class="form-select" id="contactSubject" name="subject" required>
                                <option value="">Select a subject</option>
                                <option value="General Inquiry">General Inquiry</option>
                                <option value="Service Request">Service Request</option>
                                <option value="Quote Request">Quote Request</option>
                                <option value="Appointment">Appointment</option>
                                <option value="Feedback">Feedback</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="contactMessage" class="form-label">Message *</label>
                            <textarea class="form-control" 
                                      id="contactMessage" 
                                      name="message" 
                                      rows="4" 
                                      required 
                                      placeholder="Tell us how we can help you..."></textarea>
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       id="contactNewsletter" 
                                       name="newsletter">
                                <label class="form-check-label" for="contactNewsletter">
                                    Subscribe to our newsletter for updates and special offers
                                </label>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg w-100">
                            <i class="fas fa-paper-plane me-2"></i>
                            Send Message
                        </button>
                    </form>

                    <div class="form-note mt-3">
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            We'll get back to you within 24 hours
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.getElementById('contactForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Get form data
    const formData = new FormData(this);
    formData.append('business_id', '<?php echo $business_id; ?>');
    formData.append('business_name', '<?php echo addslashes($business_name); ?>');
    
    // Show loading state
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Sending...';
    submitBtn.disabled = true;
    
    // Send form data (you'll need to create this endpoint)
    fetch('/actions/submit_contact.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Thank you! Your message has been sent successfully.');
            this.reset();
        } else {
            alert('Sorry, there was an error sending your message. Please try again.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Sorry, there was an error sending your message. Please try again.');
    })
    .finally(() => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
});
</script> 