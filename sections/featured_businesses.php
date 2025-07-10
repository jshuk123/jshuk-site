<?php if (!empty($featured)): ?>
<section class="featured-businesses-section" data-scroll>
  <div class="container">
    <h2 class="section-title">Premium Businesses</h2>
    <p class="section-subtitle">Featured businesses with enhanced visibility and premium features</p>
    <div class="businesses-slider">
      <div class="slider-container">
        <div class="slider-track">
          <?php foreach ($featured as $biz): ?>
            <div class="slider-item">
              <div class="premium-business-card">
                <!-- Premium Badge -->
                <div class="premium-badge">
                  <?php if (($biz['subscription_tier'] ?? '') === 'premium_plus'): ?>
                    <span class="badge-elite">👑 ELITE</span>
                  <?php else: ?>
                    <span class="badge-featured">⭐ FEATURED</span>
                  <?php endif; ?>
                </div>
                <!-- Business Logo/Image -->
                <div class="business-logo">
                  <?php
                  if (isset($pdo)) {
                      $stmt = $pdo->prepare("SELECT file_path FROM business_images WHERE business_id = ? AND sort_order = 0 LIMIT 1");
                      $stmt->execute([$biz['id']]);
                      $image = $stmt->fetchColumn();
                      $img_src = $image ? $image : '/images/jshuk-logo.png';
                  } else {
                      $img_src = '/images/jshuk-logo.png';
                  }
                  ?>
                  <img src="<?= htmlspecialchars($img_src) ?>" 
                       alt="<?= htmlspecialchars($biz['business_name']) ?> Logo" 
                       loading="lazy"
                       onerror="this.onerror=null;this.src='/images/jshuk-logo.png';">
                </div>
                <!-- Business Content -->
                <div class="business-content">
                  <h3 class="business-title" title="<?= htmlspecialchars($biz['business_name']) ?>">
                    <?= htmlspecialchars($biz['business_name']) ?>
                  </h3>
                  <p class="business-tagline">
                    <?php if (!empty($biz['description'])): ?>
                      <?= htmlspecialchars(mb_strimwidth($biz['description'], 0, 80, '...')) ?>
                    <?php else: ?>
                      Verified premium business
                    <?php endif; ?>
                  </p>
                  <div class="business-category">
                    <i class="fas fa-tag"></i>
                    <span><?= htmlspecialchars($biz['category_name'] ?? 'Business') ?></span>
                  </div>
                </div>
                <!-- Clickable overlay -->
                <a href="<?= BASE_PATH ?>business.php?id=<?= urlencode($biz['id']) ?>" 
                   class="card-overlay" 
                   aria-label="View <?= htmlspecialchars($biz['business_name']) ?> details">
                </a>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        <button class="slider-control prev" aria-label="Previous">
          <i class="fa-solid fa-chevron-left"></i>
        </button>
        <button class="slider-control next" aria-label="Next">
          <i class="fa-solid fa-chevron-right"></i>
        </button>
      </div>
    </div>
    <div class="section-actions">
      <a href="<?= BASE_PATH ?>search.php?featured=1" class="btn-section">View All Premium</a>
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