<?php
if (!isset($newBusinesses)) {
  echo "<div style='color:red'>[DEBUG] \$newBusinesses not set</div>";
} elseif (empty($newBusinesses)) {
  echo "<div style='color:orange'>[DEBUG] \$newBusinesses is empty</div>";
}
?>
<section class="py-5 bg-light recent-listings" data-aos="fade-up">
  <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h3 class="mb-1">Recently Added</h3>
        <p class="text-muted mb-0">Meet the newest faces in our growing community</p>
      </div>
      <a href="businesses.php" class="btn btn-outline-dark">Explore All</a>
    </div>
    <div class="row g-4">
      <?php if (!empty($newBusinesses)): ?>
        <?php 
          // Fetch main image for each business
          $img_stmt = $pdo->prepare("SELECT file_path FROM business_images WHERE business_id = ? AND sort_order = 0 LIMIT 1");
          foreach ($newBusinesses as &$business) {
              $img_stmt->execute([$business['id']]);
              $main_image_path = $img_stmt->fetchColumn();
              $business['main_image'] = $main_image_path ? $main_image_path : 'images/default-business.jpg';
          }
          unset($business);
        ?>
        <?php foreach ($newBusinesses as $business): ?>
          <div class="col-md-4">
            <a href="business.php?id=<?php echo $business['id']; ?>" class="text-decoration-none">
              <div class="card h-100 border-0 shadow-md hover-highlight">
                <img src="<?php echo htmlspecialchars($business['main_image']); ?>" class="card-img-top rounded-top" alt="<?php echo htmlspecialchars($business['business_name']); ?>">
                <div class="card-body">
                  <h5 class="card-title mb-1 text-dark fw-semibold"><?php echo htmlspecialchars($business['business_name']); ?></h5>
                  <p class="card-text text-muted mb-1 fw-medium"><?php echo htmlspecialchars($business['category_name']); ?></p>
                  <p class="card-text small text-muted">
                    <?php 
                      $desc = strip_tags($business['description']);
                      echo strlen($desc) > 100 ? substr($desc, 0, 100) . '...' : $desc;
                    ?>
                  </p>
                </div>
              </div>
            </a>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="col-12 text-center py-4">
          <p>No recent businesses to display. <a href="<?= BASE_PATH ?>auth/register.php">Be the first to list!</a></p>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>
