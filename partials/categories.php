<section class="py-5 bg-white">
  <div class="container">
    <h2 class="text-center mb-4">🔍 Popular Categories</h2>
    <div class="d-flex overflow-auto gap-3 category-carousel px-2">
      <?php foreach ($categories as $cat): ?>
        <a href="category.php?category_id=<?= $cat['id'] ?>" class="text-decoration-none flex-shrink-0" data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?= htmlspecialchars(getCategoryDescription($cat['name'])) ?>">
          <div class="card text-center shadow-sm" style="width: 160px;">
            <div class="card-body">
              <div class="mb-2">
                <i class="fa-solid <?= htmlspecialchars($cat['icon'] ?: 'fa-folder') ?> fa-2x" style="color: #ffd000;"></i>
              </div>
              <h6 class="fw-bold mb-1 text-dark"><?= htmlspecialchars($cat['name']) ?></h6>
              <small class="text-muted"><?= $cat['business_count'] ?> listings</small>
            </div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
