<?php
require_once __DIR__ . '/../includes/subscription_functions.php';

// Get featured businesses (premium and premium+ only)
$featured_businesses = getHomepageBusinesses($pdo, 6);

// Debug: Check if featured_businesses variable is available
if (!isset($featured_businesses)) {
  echo "<div style='color:red'>[DEBUG] \$featured_businesses not set</div>";
} elseif (empty($featured_businesses)) {
  echo "<div style='color:orange'>[DEBUG] \$featured_businesses is empty</div>";
}

if (!empty($featured_businesses)):
?>
<section class="featured-businesses-section">
    <div class="container">
        <div class="section-header text-center mb-5">
            <h2 class="section-title">
                <i class="fas fa-star text-warning"></i>
                Featured Businesses
                <small class="text-muted d-block">Premium & Elite Members</small>
            </h2>
            <p class="section-subtitle">Discover our premium business partners</p>
        </div>
        
        <div class="featured-carousel">
            <div class="row">
                <?php foreach ($featured_businesses as $business): 
                    $contact_info = json_decode($business['contact_info'] ?? '{}', true);
                    $business_url = '/business.php?id=' . urlencode($business['id']);
                    $main_image = htmlspecialchars($business['main_image'] ?: 'images/jshuk-logo.png');
                    $business_name = htmlspecialchars($business['business_name']);
                    $subscription_tier = $business['subscription_tier'] ?? 'basic';
                    $card_class = 'featured-card ' . getPremiumCssClasses($subscription_tier);
                ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="<?= $card_class ?>">
                        <div class="card-image">
                            <img src="<?= $main_image ?>" alt="<?= $business_name ?>" loading="lazy">
                            
                            <!-- Elite Ribbon for Premium+ -->
                            <?= renderEliteRibbon($subscription_tier) ?>
                            
                            <!-- Subscription Badge -->
                            <?= renderSubscriptionBadge($subscription_tier, false) ?>
                            
                            <!-- Featured Ribbon -->
                            <?php if ($subscription_tier !== 'basic'): ?>
                                <?= renderFeaturedRibbon($subscription_tier, true) ?>
                            <?php endif; ?>
                        </div>
                        
                        <div class="card-content">
                            <h3 class="card-title">
                                <?= $business_name ?>
                                <?= renderSubscriptionBadge($subscription_tier, true) ?>
                            </h3>
                            
                            <div class="card-category">
                                <i class="fas fa-tag"></i>
                                <?= htmlspecialchars($business['category_name']) ?>
                            </div>
                            
                            <?php if (!empty($business['description'])): ?>
                                <p class="card-description">
                                    <?= htmlspecialchars(substr($business['description'], 0, 100)) ?>...
                                </p>
                            <?php endif; ?>
                            
                            <div class="card-meta">
                                <?php if (!empty($contact_info['phone'])): ?>
                                    <div class="meta-item">
                                        <i class="fas fa-phone"></i>
                                        <span><?= htmlspecialchars($contact_info['phone']) ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($business['email'])): ?>
                                    <div class="meta-item">
                                        <i class="fas fa-envelope"></i>
                                        <span><?= htmlspecialchars($business['email']) ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="card-actions">
                                <a href="<?= $business_url ?>" class="btn btn-primary btn-sm">
                                    View Details
                                    <i class="fas fa-arrow-right ms-1"></i>
                                </a>
                                
                                <?php if (!empty($contact_info['phone'])): ?>
                                    <a href="tel:<?= htmlspecialchars($contact_info['phone']) ?>" class="btn btn-outline-success btn-sm">
                                        <i class="fas fa-phone"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="text-center mt-4">
            <a href="/businesses.php" class="btn btn-outline-primary">
                View All Businesses
                <i class="fas fa-arrow-right ms-1"></i>
            </a>
        </div>
    </div>
</section>
<?php else: ?>
<section class="featured-businesses-section">
    <div class="container text-center py-5">
        <h3>Want your business featured here?</h3>
        <p class="mb-3">Upgrade to Premium or Premium Plus for maximum visibility.</p>
        <a href="<?= BASE_PATH ?>auth/register.php" class="btn-jshuk-primary">Get Featured</a>
    </div>
</section>
<?php endif; ?>
