<!-- Airbnb-style search form -->
<section class="search-banner bg-white py-4 shadow-sm">
  <div class="container">
    <?php
    $location_filter = $_GET['location'] ?? '';
    $category_filter = $_GET['category'] ?? '';
    $search_query = $_GET['search'] ?? '';
    ?>
    <form action="/businesses.php" method="GET" class="airbnb-search-bar" role="search">
      <select name="location" class="form-select" aria-label="Select location">
        <option value="" disabled selected>📍 Select a Location</option>
        <option value="manchester" <?= $location_filter === 'manchester' ? 'selected' : '' ?>>Manchester</option>
        <option value="london" <?= $location_filter === 'london' ? 'selected' : '' ?>>London</option>
        <option value="stamford-hill" <?= $location_filter === 'stamford-hill' ? 'selected' : '' ?>>Stamford Hill</option>
      </select>
      <select name="category" class="form-select" aria-label="Select category">
        <option value="" disabled selected>🗂 Select a Category</option>
        <?php if (!empty($categories)): ?>
          <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat['id'] ?>" <?= $category_filter == $cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
          <?php endforeach; ?>
        <?php endif; ?>
      </select>
      <input type="text" name="search" class="form-control" placeholder="🔍 Search businesses..." value="<?= htmlspecialchars($search_query) ?>" />
      <button type="submit" class="btn btn-search" aria-label="Search">
        <i class="fa fa-search"></i>
        <span class="d-none d-md-inline">Search</span>
      </button>
    </form>
  </div>
</section> 