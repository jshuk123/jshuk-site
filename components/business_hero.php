<?php
/**
 * Business Hero Component
 * Displays the hero section for a business page
 */

// Use $business array for all data
$business_name = $business['name'] ?? '';
$subscription_tier = $business['subscription_tier'] ?? '';
$category = $business['category'] ?? '';
$main_image = $business['main_image'] ?? '';

?>
<div class="business-hero-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-2 text-center">
                <!-- Business Logo or Placeholder -->
                <div class="business-logo">
                    <img src="<?= htmlspecialchars($main_image ?: '/images/elite-placeholder.svg') ?>" alt="<?= htmlspecialchars($business_name) ?> Logo" class="img-fluid rounded shadow-sm" style="max-height: 80px; background: #fff;">
                </div>
            </div>
            <div class="col-md-7">
                <h1 class="mb-1" style="font-weight:800; color:#ffe066; text-shadow:2px 2px 0 #1a3353;">
                    <?= htmlspecialchars($business_name) ?>
                </h1>
                <?php if ($subscription_tier === 'premium_plus'): ?>
                    <span class="badge-premium"><i class="fas fa-star"></i> PREMIUM+</span>
                <?php elseif ($subscription_tier === 'premium'): ?>
                    <span class="badge-premium" style="background:#ffc107;color:#23272b;"><i class="fas fa-star"></i> PREMIUM</span>
                <?php endif; ?>
                <div class="category-label mt-2">
                    Category: <span style="color:#007bff;"> <?= htmlspecialchars($category) ?> </span>
                </div>
            </div>
            <div class="col-md-3 text-end">
                <!-- Optional: Add quick action buttons here -->
            </div>
        </div>
    </div>
</div> 