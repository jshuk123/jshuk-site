<?php
/**
 * Business About Component
 * Displays detailed business information, mission, and company details
 */

// Get business data from the parent scope
$business_id = $business['id'] ?? null;
$business_name = $business['name'] ?? 'Business Name';
$business_description = $business['description'] ?? '';
$business_about = $business['about'] ?? '';
$business_mission = $business['mission'] ?? '';
$business_vision = $business['vision'] ?? '';
$business_values = $business['values'] ?? '';
$business_established = $business['established'] ?? '';
$business_employees = $business['employees'] ?? '';
$business_services = $business['services'] ?? '';
$business_specialties = $business['specialties'] ?? '';

// Parse values if they exist
$values_array = [];
if (!empty($business_values)) {
    $values_array = array_filter(array_map('trim', explode(',', $business_values)));
}

// Parse specialties if they exist
$specialties_array = [];
if (!empty($business_specialties)) {
    $specialties_array = array_filter(array_map('trim', explode(',', $business_specialties)));
}

// Calculate years in business
$years_in_business = '';
if (!empty($business_established)) {
    $established_year = intval($business_established);
    $current_year = date('Y');
    $years_in_business = $current_year - $established_year;
}
?>

<!-- Business About Section -->
<section class="business-about py-5">
    <div class="container">
        <div class="row">
            <!-- Main About Content -->
            <div class="col-lg-8">
                <div class="about-content-card">
                    <div class="section-header mb-4">
                        <h2 class="section-title">
                            <i class="fas fa-info-circle text-primary me-2"></i>
                            About <?php echo htmlspecialchars($business_name); ?>
                        </h2>
                        <div class="section-divider"></div>
                    </div>

                    <!-- Main Description -->
                    <?php if (!empty($business_description)): ?>
                    <div class="about-description mb-4">
                        <p class="lead text-muted">
                            <?php echo nl2br(htmlspecialchars($business_description)); ?>
                        </p>
                    </div>
                    <?php endif; ?>

                    <!-- Detailed About -->
                    <?php if (!empty($business_about)): ?>
                    <div class="about-detailed mb-4">
                        <h5 class="about-subtitle mb-3">
                            <i class="fas fa-file-alt text-secondary me-2"></i>
                            Our Story
                        </h5>
                        <div class="about-text">
                            <?php echo nl2br(htmlspecialchars($business_about)); ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Mission & Vision -->
                    <div class="row mb-4">
                        <?php if (!empty($business_mission)): ?>
                        <div class="col-md-6 mb-3">
                            <div class="mission-card">
                                <div class="mission-icon">
                                    <i class="fas fa-bullseye text-primary"></i>
                                </div>
                                <h6 class="mission-title">Our Mission</h6>
                                <p class="mission-text">
                                    <?php echo htmlspecialchars($business_mission); ?>
                                </p>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($business_vision)): ?>
                        <div class="col-md-6 mb-3">
                            <div class="vision-card">
                                <div class="vision-icon">
                                    <i class="fas fa-eye text-success"></i>
                                </div>
                                <h6 class="vision-title">Our Vision</h6>
                                <p class="vision-text">
                                    <?php echo htmlspecialchars($business_vision); ?>
                                </p>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Core Values -->
                    <?php if (!empty($values_array)): ?>
                    <div class="values-section mb-4">
                        <h5 class="about-subtitle mb-3">
                            <i class="fas fa-heart text-danger me-2"></i>
                            Our Core Values
                        </h5>
                        <div class="values-grid">
                            <?php foreach ($values_array as $value): ?>
                            <div class="value-item">
                                <i class="fas fa-check-circle text-success me-2"></i>
                                <span><?php echo htmlspecialchars($value); ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Services Overview -->
                    <?php if (!empty($business_services)): ?>
                    <div class="services-overview mb-4">
                        <h5 class="about-subtitle mb-3">
                            <i class="fas fa-cogs text-info me-2"></i>
                            What We Do
                        </h5>
                        <div class="services-text">
                            <?php echo nl2br(htmlspecialchars($business_services)); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Sidebar Information -->
            <div class="col-lg-4">
                <div class="about-sidebar">
                    <!-- Company Stats -->
                    <div class="stats-card mb-4">
                        <h6 class="stats-title mb-3">
                            <i class="fas fa-chart-bar text-primary me-2"></i>
                            Company Overview
                        </h6>
                        <div class="stats-grid">
                            <?php if (!empty($business_established)): ?>
                            <div class="stat-item">
                                <div class="stat-icon">
                                    <i class="fas fa-calendar-alt text-primary"></i>
                                </div>
                                <div class="stat-content">
                                    <div class="stat-number"><?php echo $years_in_business; ?>+</div>
                                    <div class="stat-label">Years in Business</div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <?php if (!empty($business_employees)): ?>
                            <div class="stat-item">
                                <div class="stat-icon">
                                    <i class="fas fa-users text-success"></i>
                                </div>
                                <div class="stat-content">
                                    <div class="stat-number"><?php echo htmlspecialchars($business_employees); ?></div>
                                    <div class="stat-label">Team Members</div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Specialties -->
                    <?php if (!empty($specialties_array)): ?>
                    <div class="specialties-card mb-4">
                        <h6 class="specialties-title mb-3">
                            <i class="fas fa-star text-warning me-2"></i>
                            Our Specialties
                        </h6>
                        <div class="specialties-list">
                            <?php foreach ($specialties_array as $specialty): ?>
                            <div class="specialty-item">
                                <i class="fas fa-award text-warning me-2"></i>
                                <span><?php echo htmlspecialchars($specialty); ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Call to Action -->
                    <div class="about-cta">
                        <div class="cta-content text-center">
                            <h6 class="cta-title mb-3">Ready to Work Together?</h6>
                            <p class="cta-text mb-3">
                                Let's discuss how we can help you achieve your goals.
                            </p>
                            <a href="#contact" class="btn btn-primary btn-lg w-100">
                                <i class="fas fa-envelope me-2"></i>
                                Get in Touch
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section> 