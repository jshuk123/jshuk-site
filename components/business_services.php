<?php
/**
 * Business Services Component
 * Displays business services, features, and offerings
 */

// Get business data from the parent scope
$business_id = $business['id'] ?? null;
$business_name = $business['name'] ?? 'Business Name';
$business_services = $business['services'] ?? '';
$business_features = $business['features'] ?? '';
$business_specialties = $business['specialties'] ?? '';
$business_products = $business['products'] ?? '';

// Parse services if they exist
$services_array = [];
if (!empty($business_services)) {
    $services_array = array_filter(array_map('trim', explode(',', $business_services)));
}

// Parse features if they exist
$features_array = [];
if (!empty($business_features)) {
    $features_array = array_filter(array_map('trim', explode(',', $business_features)));
}

// Parse specialties if they exist
$specialties_array = [];
if (!empty($business_specialties)) {
    $specialties_array = array_filter(array_map('trim', explode(',', $business_specialties)));
}

// Parse products if they exist
$products_array = [];
if (!empty($business_products)) {
    $products_array = array_filter(array_map('trim', explode(',', $business_products)));
}

// Service icons mapping
$service_icons = [
    'consulting' => 'fas fa-comments',
    'design' => 'fas fa-palette',
    'development' => 'fas fa-code',
    'marketing' => 'fas fa-bullhorn',
    'support' => 'fas fa-headset',
    'training' => 'fas fa-graduation-cap',
    'maintenance' => 'fas fa-tools',
    'installation' => 'fas fa-wrench',
    'repair' => 'fas fa-hammer',
    'cleaning' => 'fas fa-broom',
    'delivery' => 'fas fa-truck',
    'catering' => 'fas fa-utensils',
    'photography' => 'fas fa-camera',
    'video' => 'fas fa-video',
    'audio' => 'fas fa-microphone',
    'printing' => 'fas fa-print',
    'legal' => 'fas fa-balance-scale',
    'accounting' => 'fas fa-calculator',
    'insurance' => 'fas fa-shield-alt',
    'real estate' => 'fas fa-home',
    'healthcare' => 'fas fa-heartbeat',
    'fitness' => 'fas fa-dumbbell',
    'beauty' => 'fas fa-spa',
    'automotive' => 'fas fa-car',
    'technology' => 'fas fa-laptop',
    'finance' => 'fas fa-chart-line',
    'education' => 'fas fa-book',
    'restaurant' => 'fas fa-utensils',
    'retail' => 'fas fa-shopping-bag',
    'manufacturing' => 'fas fa-industry',
    'construction' => 'fas fa-hard-hat',
    'transportation' => 'fas fa-bus',
    'entertainment' => 'fas fa-music',
    'default' => 'fas fa-star'
];

// Function to get icon for service
function getServiceIcon($service_name) {
    global $service_icons;
    $service_lower = strtolower(trim($service_name));
    
    foreach ($service_icons as $keyword => $icon) {
        if ($keyword !== 'default' && strpos($service_lower, $keyword) !== false) {
            return $icon;
        }
    }
    
    return $service_icons['default'];
}
?>

<!-- Business Services Section -->
<section class="business-services py-5" id="services">
    <div class="container">
        <div class="section-header text-center mb-5">
            <h2 class="section-title">
                <i class="fas fa-cogs text-primary me-2"></i>
                Our Services & Features
            </h2>
            <p class="section-subtitle text-muted">
                Discover what makes <?php echo htmlspecialchars($business_name); ?> the right choice for you
            </p>
            <div class="section-divider mx-auto"></div>
        </div>

        <!-- Services Grid -->
        <?php if (!empty($services_array)): ?>
        <div class="services-section mb-5">
            <h3 class="subsection-title mb-4">
                <i class="fas fa-handshake text-primary me-2"></i>
                What We Offer
            </h3>
            <div class="row g-4">
                <?php foreach ($services_array as $service): ?>
                <div class="col-lg-4 col-md-6">
                    <div class="service-card">
                        <div class="service-icon">
                            <i class="<?php echo getServiceIcon($service); ?>"></i>
                        </div>
                        <div class="service-content">
                            <h5 class="service-title"><?php echo htmlspecialchars($service); ?></h5>
                            <p class="service-description">
                                Professional <?php echo strtolower(htmlspecialchars($service)); ?> services tailored to your needs.
                            </p>
                        </div>
                        <div class="service-action">
                            <a href="#contact" class="btn btn-outline-primary btn-sm">
                                Learn More
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Features Grid -->
        <?php if (!empty($features_array)): ?>
        <div class="features-section mb-5">
            <h3 class="subsection-title mb-4">
                <i class="fas fa-star text-warning me-2"></i>
                Why Choose Us
            </h3>
            <div class="row g-4">
                <?php foreach ($features_array as $feature): ?>
                <div class="col-lg-6 col-md-6">
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-check-circle text-success"></i>
                        </div>
                        <div class="feature-content">
                            <h6 class="feature-title"><?php echo htmlspecialchars($feature); ?></h6>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Specialties Grid -->
        <?php if (!empty($specialties_array)): ?>
        <div class="specialties-section mb-5">
            <h3 class="subsection-title mb-4">
                <i class="fas fa-award text-warning me-2"></i>
                Our Specialties
            </h3>
            <div class="row g-4">
                <?php foreach ($specialties_array as $specialty): ?>
                <div class="col-lg-4 col-md-6">
                    <div class="specialty-card">
                        <div class="specialty-icon">
                            <i class="fas fa-medal text-warning"></i>
                        </div>
                        <div class="specialty-content">
                            <h6 class="specialty-title"><?php echo htmlspecialchars($specialty); ?></h6>
                            <p class="specialty-description">
                                Expert specialization in <?php echo strtolower(htmlspecialchars($specialty)); ?>.
                            </p>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Products Grid -->
        <?php if (!empty($products_array)): ?>
        <div class="products-section mb-5">
            <h3 class="subsection-title mb-4">
                <i class="fas fa-box text-info me-2"></i>
                Our Products
            </h3>
            <div class="row g-4">
                <?php foreach ($products_array as $product): ?>
                <div class="col-lg-3 col-md-6">
                    <div class="product-card">
                        <div class="product-icon">
                            <i class="fas fa-cube text-info"></i>
                        </div>
                        <div class="product-content">
                            <h6 class="product-title"><?php echo htmlspecialchars($product); ?></h6>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Call to Action -->
        <div class="services-cta text-center">
            <div class="cta-card">
                <h4 class="cta-title mb-3">Ready to Get Started?</h4>
                <p class="cta-text mb-4">
                    Contact us today to discuss your project and get a personalized quote.
                </p>
                <div class="cta-buttons">
                    <a href="#contact" class="btn btn-primary btn-lg me-3">
                        <i class="fas fa-envelope me-2"></i>
                        Get Quote
                    </a>
                    <a href="tel:<?php echo htmlspecialchars($business['phone'] ?? ''); ?>" 
                       class="btn btn-outline-primary btn-lg">
                        <i class="fas fa-phone me-2"></i>
                        Call Now
                    </a>
                </div>
            </div>
        </div>
    </div>
</section> 