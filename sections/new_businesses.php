<!-- NEW BUSINESSES SECTION -->
<section class="new-businesses-section" data-scroll>
  <div class="container">
    <h2 class="section-title">New This Week</h2>
    <p class="section-subtitle">Recently added businesses to our community</p>
    <div class="businesses-grid">
      <?php if (!empty($newBusinesses)): ?>
        <?php foreach (array_slice($newBusinesses, 0, 6) as $biz): ?>
          <div class="business-card-wrapper">
            <div class="business-card new-business-card">
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
                <?php if ($image ?? false): ?>
                  <img src="<?= htmlspecialchars($img_src) ?>" 
                       alt="<?= htmlspecialchars($biz['business_name']) ?> Logo" 
                       loading="lazy"
                       onerror="this.onerror=null;this.src='/images/jshuk-logo.png';">
                <?php else: ?>
                  <div class="logo-placeholder"><?= strtoupper(substr($biz['business_name'], 0, 1)) ?></div>
                <?php endif; ?>
                <?php if (($biz['subscription_tier'] ?? '') === 'premium_plus'): ?>
                  <span class="badge-elite">Elite</span>
                <?php endif; ?>
              </div>
              <div class="business-info">
                <h3 class="business-title">
                  <a href="<?= BASE_PATH ?>business.php?id=<?= urlencode($biz['id']) ?>">
                    <?= htmlspecialchars($biz['business_name']) ?>
                  </a>
                </h3>
                <p class="business-meta">
                  <i class="fas fa-tag"></i> 
                  <?= htmlspecialchars($biz['category_name'] ?? 'Business') ?>
                </p>
                <p class="business-joined">🆕 Just Joined</p>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="no-businesses">No new businesses added recently. <a href="<?= BASE_PATH ?>auth/register.php">Be the first to list!</a></div>
      <?php endif; ?>
    </div>
    <div class="section-actions">
      <a href="<?= BASE_PATH ?>search.php?sort=newest" class="btn-section">Explore More New Listings</a>
    </div>
  </div>
</section> 