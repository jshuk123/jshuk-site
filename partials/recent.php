<section class="py-5 bg-light">
  <div class="container">
    <h2 class="text-center mb-4">🆕 Recently Added Businesses</h2>
    <div class="row g-4">
      <?php foreach ($new as $biz): ?>
        <div class="col-md-4">
          <a href="business.php?id=<?= $biz['id'] ?>" class="text-decoration-none">
            <div class="card h-100 shadow-sm">
              <img src="<?= htmlspecialchars($biz['main_image'] ?: 'images/default-business.jpg') ?>" class="card-img-top" alt="<?= htmlspecialchars($biz['business_name']) ?>">
              <div class="card-body">
                <h5 class="card-title text-dark fw-semibold"><?= htmlspecialchars($biz['business_name']) ?></h5>
                <p class="card-text text-muted small mb-0 fw-medium">Category: <?= htmlspecialchars($biz['category_name']) ?></p>
              </div>
            </div>
          </a>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
