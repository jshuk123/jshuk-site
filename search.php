<?php
// Start session and set page CSS before header
session_start();
$pageTitle = "Search Businesses";
$page_css = "search.css";
require_once 'config/security.php';
require_once 'config/config.php';
require_once 'includes/helpers.php';
require_once 'includes/subscription_functions.php';
include 'includes/header_main.php';
require_once 'includes/ad_renderer.php'; 

// DEBUG: Add debug output for ad system
if (isset($_GET['debug_ads'])) {
    echo "<div style='background: #f0f0f0; border: 2px solid #ff0000; padding: 10px; margin: 10px; font-family: monospace;'>";
    echo "<h3>🔍 AD SYSTEM DEBUG - SEARCH PAGE</h3>";
    
    // Test database connection
    try {
        $test = $pdo->query("SELECT 1");
        echo "<p>✅ Database connection: OK</p>";
    } catch (Exception $e) {
        echo "<p>❌ Database connection failed: " . $e->getMessage() . "</p>";
    }
    
    // Check ads table
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'ads'");
        if ($stmt->rowCount() > 0) {
            echo "<p>✅ Ads table exists</p>";
            
            // Count total ads
            $stmt = $pdo->query("SELECT COUNT(*) FROM ads");
            $total = $stmt->fetchColumn();
            echo "<p>📊 Total ads in database: $total</p>";
            
            // Check for header ads
            $now = date('Y-m-d');
            $stmt = $pdo->prepare("SELECT * FROM ads WHERE zone = 'header' AND status = 'active' AND start_date <= ? AND end_date >= ?");
            $stmt->execute([$now, $now]);
            $headerAds = $stmt->fetchAll();
            echo "<p>🎯 Header ads matching criteria: " . count($headerAds) . "</p>";
            
            if (!empty($headerAds)) {
                echo "<p>📋 Header ad details:</p>";
                foreach ($headerAds as $ad) {
                    echo "<ul>";
                    echo "<li>ID: " . $ad['id'] . "</li>";
                    echo "<li>Title: " . htmlspecialchars($ad['title'] ?? 'N/A') . "</li>";
                    echo "<li>Status: " . htmlspecialchars($ad['status'] ?? 'N/A') . "</li>";
                    echo "<li>Start Date: " . htmlspecialchars($ad['start_date'] ?? 'N/A') . "</li>";
                    echo "<li>End Date: " . htmlspecialchars($ad['end_date'] ?? 'N/A') . "</li>";
                    echo "<li>Image URL: " . htmlspecialchars($ad['image_url'] ?? 'N/A') . "</li>";
                    echo "</ul>";
                }
            }
        } else {
            echo "<p>❌ Ads table does not exist</p>";
        }
    } catch (Exception $e) {
        echo "<p>❌ Error checking ads table: " . $e->getMessage() . "</p>";
    }
    
    echo "</div>";
}

// Get search/filter parameters
$search_query = trim($_GET['q'] ?? '');
$category_filter = $_GET['cat'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 12;
$error_message = '';
$results = [];
$total_pages = 1;

try {
    // Fetch categories for dropdown
    $categories = $pdo->query("SELECT id, name FROM business_categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

    // Build WHERE clause and params
    $where = ["b.status = 'active'"];
    $params = [];
    if ($search_query) {
        $where[] = "(b.business_name LIKE :search OR b.description LIKE :search)";
        $params[':search'] = "%$search_query%";
    }
    if ($category_filter) {
        $where[] = "b.category_id = :cat";
        $params[':cat'] = $category_filter;
    }
    $where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

    // Count query
    $count_sql = "SELECT COUNT(*) FROM businesses b LEFT JOIN business_categories c ON b.category_id = c.id $where_sql";
    $count_stmt = $pdo->prepare($count_sql);
    foreach ($params as $key => $value) {
        $count_stmt->bindValue($key, $value);
    }
    $count_stmt->execute();
    $total_results = $count_stmt->fetchColumn();
    $total_pages = max(1, ceil($total_results / $per_page));
    $offset = ($page - 1) * $per_page;

    // Results query with subscription tier priority
    $results_sql = "
        SELECT b.id, b.business_name, b.description, b.category_id, 
               c.name as category_name, u.subscription_tier 
        FROM businesses b 
        LEFT JOIN business_categories c ON b.category_id = c.id 
        LEFT JOIN users u ON b.user_id = u.id
        $where_sql 
        ORDER BY 
            CASE u.subscription_tier 
                WHEN 'premium_plus' THEN 1 
                WHEN 'premium' THEN 2 
                ELSE 3 
            END,
            b.business_name ASC 
        LIMIT $offset, $per_page
    ";
    $stmt = $pdo->prepare($results_sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // After fetching $results:
    if (!empty($results)) {
        $img_stmt = $pdo->prepare("SELECT file_path FROM business_images WHERE business_id = ? AND sort_order = 0 LIMIT 1");
        foreach ($results as &$biz) {
            $img_stmt->execute([$biz['id']]);
            $main_image_path = $img_stmt->fetchColumn();
            $biz['main_image'] = $main_image_path ? $main_image_path : '/assets/images/placeholder-business.png';
        }
        unset($biz);
    }
} catch (PDOException $e) {
    $error_message = 'Database error: ' . htmlspecialchars($e->getMessage());
}

// --- SEO: Set dynamic title and meta description based on category ---
$categoryName = '';
if ($category_filter && !empty($categories)) {
    foreach ($categories as $cat) {
        if ($cat['id'] == $category_filter) {
            $categoryName = $cat['name'];
            break;
        }
    }
}
if ($categoryName) {
    $pageTitle = "Jewish {$categoryName} Services in Manchester | JShuk";
    $metaDescription = "Explore Jewish {$categoryName} providers near you.";
} else {
    $pageTitle = "Search Jewish Businesses & Services | JShuk";
    $metaDescription = "Find trusted Jewish businesses, services, and professionals in your community.";
}
// Insert meta tags in <head> (after header_main.php include):
echo "<title>" . htmlspecialchars(
    $pageTitle
) . "</title>\n<meta name=\"description\" content=\"" . htmlspecialchars($metaDescription) . "\">\n";
?>
<main class="container py-5">
  <!-- ENHANCED PAGE HEADER -->
  <section class="page-header mb-4">
    <div class="row align-items-center">
      <div class="col-md-8">
        <h1 class="page-title mb-2">
          <?php if ($category_filter && $categoryName): ?>
            Category: <?= htmlspecialchars($categoryName) ?>
          <?php elseif ($search_query): ?>
            Search Results for: "<?= htmlspecialchars($search_query) ?>"
          <?php else: ?>
            All Jewish Businesses
          <?php endif; ?>
        </h1>
        <div class="results-info">
          <?php if (!empty($results)): ?>
            <span class="results-count">
              Showing <?= ($offset + 1) ?>-<?= min($offset + $per_page, $total_results) ?> of <?= $total_results ?> businesses
            </span>
          <?php endif; ?>
        </div>
      </div>
      <div class="col-md-4 text-md-end">
        <div class="sorting-controls">
          <label for="sortSelect" class="form-label">Sort by:</label>
          <select id="sortSelect" class="form-select form-select-sm" onchange="updateSort(this.value)">
            <option value="relevance" <?= ($_GET['sort'] ?? 'relevance') === 'relevance' ? 'selected' : '' ?>>Relevance</option>
            <option value="newest" <?= ($_GET['sort'] ?? '') === 'newest' ? 'selected' : '' ?>>Newest</option>
            <option value="rating" <?= ($_GET['sort'] ?? '') === 'rating' ? 'selected' : '' ?>>Highest Rated</option>
            <option value="alphabetical" <?= ($_GET['sort'] ?? '') === 'alphabetical' ? 'selected' : '' ?>>Alphabetical (A-Z)</option>
          </select>
        </div>
      </div>
    </div>
  </section>

    <!-- SEARCH FORM SECTION -->
  <section class="mb-5 text-center">
    <form class="row g-2 justify-content-center mb-3 search-form" method="get" action="search.php" autocomplete="off">
      <div class="col-12 col-md-6 mb-2 mb-md-0">
        <div class="input-group input-group-lg">
          <span class="input-group-text bg-white border-end-0"><i class="fa fa-search text-muted"></i></span>
          <input type="text" class="form-control border-start-0" name="q" placeholder="Search by name, service, or keyword..." value="<?= htmlspecialchars($search_query) ?>">
        </div>
      </div>
      <div class="col-12 col-md-3 mb-2 mb-md-0">
        <select class="form-select form-select-lg" name="cat">
          <option value="">All Categories</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat['id'] ?>" <?= ($category_filter == $cat['id']) ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-12 col-md-2 d-grid">
        <button type="submit" class="btn btn-primary btn-lg"><i class="fa fa-search me-2"></i>Search</button>
      </div>
    </form>
  </section>

  <?php if ($error_message): ?>
    <div class="alert alert-danger text-center mb-4"> <?= $error_message ?> </div>
  <?php endif; ?>

  <!-- TWO-COLUMN LAYOUT: FILTERS + RESULTS -->
  <div class="row">
    <!-- FILTERING SIDEBAR -->
    <div class="col-lg-3 mb-4">
      <div class="filters-sidebar">
        <h3 class="filters-title mb-3">Filters</h3>
        
        <!-- Category Filter -->
        <div class="filter-section mb-4">
          <h4 class="filter-section-title">Category</h4>
          <div class="filter-options">
            <?php foreach ($categories as $cat): ?>
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="cat_<?= $cat['id'] ?>" 
                       name="cat_filter[]" value="<?= $cat['id'] ?>"
                       <?= in_array($cat['id'], explode(',', $_GET['cat_filter'] ?? '')) ? 'checked' : '' ?>>
                <label class="form-check-label" for="cat_<?= $cat['id'] ?>">
                  <?= htmlspecialchars($cat['name']) ?>
                </label>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
        
        <!-- Location Filter -->
        <div class="filter-section mb-4">
          <h4 class="filter-section-title">Location</h4>
          <div class="filter-options">
            <?php 
            $locations = ['London', 'Manchester', 'Stamford Hill', 'Hendon', 'Golders Green', 'Edgware'];
            foreach ($locations as $location): ?>
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="loc_<?= str_replace(' ', '_', $location) ?>" 
                       name="location_filter[]" value="<?= $location ?>"
                       <?= in_array($location, explode(',', $_GET['location_filter'] ?? '')) ? 'checked' : '' ?>>
                <label class="form-check-label" for="loc_<?= str_replace(' ', '_', $location) ?>">
                  <?= htmlspecialchars($location) ?>
                </label>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
        
        <!-- Rating Filter -->
        <div class="filter-section mb-4">
          <h4 class="filter-section-title">Rating</h4>
          <div class="filter-options">
            <div class="form-check">
              <input class="form-check-input" type="radio" id="rating_5" name="rating_filter" value="5"
                     <?= ($_GET['rating_filter'] ?? '') === '5' ? 'checked' : '' ?>>
              <label class="form-check-label" for="rating_5">
                ★★★★★ (5 stars)
              </label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" id="rating_4" name="rating_filter" value="4"
                     <?= ($_GET['rating_filter'] ?? '') === '4' ? 'checked' : '' ?>>
              <label class="form-check-label" for="rating_4">
                ★★★★☆ & up (4+ stars)
              </label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" id="rating_3" name="rating_filter" value="3"
                     <?= ($_GET['rating_filter'] ?? '') === '3' ? 'checked' : '' ?>>
              <label class="form-check-label" for="rating_3">
                ★★★☆☆ & up (3+ stars)
              </label>
            </div>
          </div>
        </div>
        
        <!-- Features Filter -->
        <div class="filter-section mb-4">
          <h4 class="filter-section-title">Features</h4>
          <div class="filter-options">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="feature_kosher" name="features[]" value="kosher"
                     <?= in_array('kosher', explode(',', $_GET['features'] ?? '')) ? 'checked' : '' ?>>
              <label class="form-check-label" for="feature_kosher">
                Kosher Certified
              </label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="feature_delivery" name="features[]" value="delivery"
                     <?= in_array('delivery', explode(',', $_GET['features'] ?? '')) ? 'checked' : '' ?>>
              <label class="form-check-label" for="feature_delivery">
                Offers Delivery
              </label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="feature_weekends" name="features[]" value="weekends"
                     <?= in_array('weekends', explode(',', $_GET['features'] ?? '')) ? 'checked' : '' ?>>
              <label class="form-check-label" for="feature_weekends">
                Open on Weekends
              </label>
            </div>
          </div>
        </div>
        
        <!-- Apply Filters Button -->
        <button type="button" class="btn btn-primary w-100" onclick="applyFilters()">
          <i class="fa fa-filter me-2"></i>Apply Filters
        </button>
        
        <!-- Clear Filters -->
        <button type="button" class="btn btn-outline-secondary w-100 mt-2" onclick="clearFilters()">
          Clear All Filters
        </button>
      </div>
    </div>
    
    <!-- RESULTS GRID -->
    <div class="col-lg-9">
      <section>
        <?php if (empty($results)): ?>
      <!-- ENHANCED NO RESULTS FOUND PAGE -->
      <div class="no-results-container text-center py-5">
        <div class="no-results-icon mb-4">
          <i class="fa fa-search fa-3x text-muted"></i>
        </div>
        
        <h2 class="no-results-title mb-3">
          Sorry, we couldn't find any results for "<?= htmlspecialchars($search_query) ?>"
        </h2>
        
        <p class="no-results-subtitle mb-4">
          Don't worry, it happens! You can try searching with different keywords, or explore some of our most popular categories below.
        </p>
        
        <!-- Popular Categories Section -->
        <div class="popular-categories-section mb-5">
          <h3 class="section-title mb-4">Popular Categories</h3>
          <div class="categories-grid">
            <?php 
            // Get popular categories (you can customize this list)
            $popular_categories = [
                ['id' => 1, 'name' => 'Restaurants', 'icon' => 'fa-utensils'],
                ['id' => 2, 'name' => 'Catering', 'icon' => 'fa-birthday-cake'],
                ['id' => 3, 'name' => 'Plumbing', 'icon' => 'fa-wrench'],
                ['id' => 4, 'name' => 'Electrical', 'icon' => 'fa-bolt'],
                ['id' => 5, 'name' => 'Cleaning', 'icon' => 'fa-broom'],
                ['id' => 6, 'name' => 'Tutoring', 'icon' => 'fa-graduation-cap'],
                ['id' => 7, 'name' => 'Photography', 'icon' => 'fa-camera'],
                ['id' => 8, 'name' => 'Transport', 'icon' => 'fa-car']
            ];
            
            foreach ($popular_categories as $cat): ?>
              <a href="search.php?cat=<?= $cat['id'] ?>" class="category-card">
                <div class="category-icon">
                  <i class="fa <?= $cat['icon'] ?>"></i>
                </div>
                <div class="category-name"><?= htmlspecialchars($cat['name']) ?></div>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
        
        <!-- Community Building CTA -->
        <div class="community-cta-section">
          <h3 class="cta-title mb-3">Know a business we should add?</h3>
          <p class="cta-subtitle mb-4">
            Help grow our community by suggesting a business that could benefit others.
          </p>
          <a href="/auth/register.php" class="btn btn-primary btn-lg">
            <i class="fa fa-plus me-2"></i>Suggest a Business
          </a>
        </div>
      </div>
    <?php else: ?>
      <div class="row g-4 justify-content-center">
        <?php foreach ($results as $biz): 
            $subscription_tier = $biz['subscription_tier'] ?? 'basic';
            $is_elite = $subscription_tier === 'premium_plus';
            $is_premium = $subscription_tier === 'premium';
            $is_new = isset($biz['created_at']) && strtotime($biz['created_at']) >= strtotime('-7 days');
            $card_class = 'business-card flex-fill position-relative ' . getPremiumCssClasses($subscription_tier);
            
            // Mock data for demonstration (replace with real data from database)
            $rating = 4.2; // Mock rating
            $review_count = 12; // Mock review count
            $location = 'Hendon, London'; // Mock location
        ?>
          <div class="col-12 col-sm-6 col-lg-4 d-flex">
            <a href="business.php?id=<?= urlencode($biz['id']) ?>" class="<?= $card_class ?>" style="position:relative; text-decoration: none; color: inherit;">
              <!-- Featured Badge (top-left) -->
              <?php if ($is_elite || $is_premium): ?>
                <span class="badge-featured" style="position:absolute;top:8px;left:8px;z-index:10;">
                  <i class="fa-solid fa-star"></i> Featured
                </span>
              <?php endif; ?>
              
              <!-- New Badge (top-right) -->
              <?php if ($is_new): ?>
                <span class="badge-new" style="position:absolute;top:8px;right:8px;z-index:10;">New</span>
              <?php endif; ?>
              
              <!-- Business Image -->
              <div class="business-image mb-3" style="position:relative;">
                <img src="<?= htmlspecialchars($biz['main_image']) ?>" alt="<?= htmlspecialchars($biz['business_name']) ?> Logo" class="img-fluid business-logo-img" style="height:160px;object-fit:cover;width:100%;background:#f8fafc;border-radius:12px 12px 0 0;">
                <span class="category-badge position-absolute top-0 start-0 m-2 px-3 py-1"> 
                  <?= htmlspecialchars($biz['category_name']) ?> 
                </span>
              </div>
              
              <!-- Business Info -->
              <div class="card-body d-flex flex-column p-3" style="width:100%;">
                <h5 class="card-title text-primary fw-bold mb-2" style="width:100%;white-space:nowrap;overflow:hidden;text-overflow:ellipsis; font-size: 1.1rem;"> 
                  <?= htmlspecialchars($biz['business_name']) ?>
                </h5>
                
                <!-- Location -->
                <p class="text-muted small mb-2">
                  <i class="fa fa-map-marker-alt me-1"></i><?= htmlspecialchars($location) ?>
                </p>
                
                <!-- Star Rating -->
                <div class="rating-section mb-2">
                  <div class="stars">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                      <i class="fa fa-star <?= $i <= $rating ? 'text-warning' : 'text-muted' ?>"></i>
                    <?php endfor; ?>
                  </div>
                  <span class="rating-text">(<?= $review_count ?> reviews)</span>
                </div>
                
                <!-- Description -->
                <p class="flex-grow-1 text-secondary mb-3 business-description" style="max-height:3.6em;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;line-clamp:2; font-size: 0.9rem;"> 
                  <?= htmlspecialchars($biz['description']) ?> 
                </p>
                
                <!-- View Details Button -->
                <div class="mt-auto">
                  <span class="btn btn-primary w-100">View Details</span>
                </div>
              </div>
            </a>
          </div>
        <?php endforeach; ?>
      </div>
      <?php if ($total_pages > 1): ?>
        <nav class="mt-5" aria-label="Business search results pages">
          <ul class="pagination justify-content-center">
            <?php if ($page > 1): ?>
              <li class="page-item"><a class="page-link" href="?q=<?= urlencode($search_query) ?>&cat=<?= urlencode($category_filter) ?>&page=<?= $page-1 ?>">&laquo; Prev</a></li>
            <?php endif; ?>
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
              <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                <a class="page-link" href="?q=<?= urlencode($search_query) ?>&cat=<?= urlencode($category_filter) ?>&page=<?= $i ?>"><?= $i ?></a>
              </li>
            <?php endfor; ?>
            <?php if ($page < $total_pages): ?>
              <li class="page-item"><a class="page-link" href="?q=<?= urlencode($search_query) ?>&cat=<?= urlencode($category_filter) ?>&page=<?= $page+1 ?>">Next &raquo;</a></li>
            <?php endif; ?>
          </ul>
        </nav>
      <?php endif; ?>
    </section>
    </div>
  </div>
          </ul>
        </nav>
      <?php endif; ?>
    <?php endif; ?>
  </section>
</main>
<style>
.business-card {
  position: relative;
  transition: box-shadow 0.3s, transform 0.3s;
  border-radius: 1.2rem;
  background: #fff;
  border: 1px solid #e3e6ed;
  box-shadow: 0 2px 10px rgba(13,110,253,0.05);
  display: flex;
  flex-direction: column;
  min-width: 0;
}
.business-card:hover {
  transform: scale(1.02);
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
  z-index: 2;
}
.badge-elite {
  position: absolute;
  top: 8px;
  left: 8px;
  background: linear-gradient(90deg, #FFD700 60%, #fffbe7 100%);
  color: #1d2a40;
  font-weight: 700;
  border-radius: 1em;
  padding: 0.4em 0.8em;
  font-size: 0.9em;
  box-shadow: 0 2px 8px rgba(255,215,0,0.12);
  display: flex;
  align-items: center;
  gap: 0.3em;
  z-index: 10;
}
.badge-trending {
  background: #ff9800;
  color: #fff;
  font-weight: 600;
  border-radius: 8px;
  padding: 4px 10px;
  font-size: 0.85em;
  position: absolute;
  top: 8px;
  right: 8px;
  z-index: 10;
}
.business-title {
  width: 100%;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.business-description {
  max-height: 3.6em;
  overflow: hidden;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  line-clamp: 2;
  margin-bottom: 0.5em;
}

/* Enhanced No Results Page Styles */
.no-results-container {
  max-width: 800px;
  margin: 0 auto;
  padding: 3rem 1rem;
}

.no-results-icon {
  color: #6c757d;
  opacity: 0.6;
}

.no-results-title {
  font-size: 2rem;
  font-weight: 600;
  color: #2c3e50;
  line-height: 1.3;
}

.no-results-subtitle {
  font-size: 1.1rem;
  color: #6c757d;
  line-height: 1.6;
  max-width: 600px;
  margin: 0 auto;
}

.section-title {
  font-size: 1.5rem;
  font-weight: 600;
  color: #2c3e50;
  margin-bottom: 1.5rem;
}

.categories-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  max-width: 600px;
  margin: 0 auto;
}

.category-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 1.5rem;
  background: white;
  border: 2px solid #e9ecef;
  border-radius: 12px;
  text-decoration: none;
  color: #495057;
  transition: all 0.3s ease;
  text-align: center;
}

.category-card:hover {
  border-color: #007bff;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 123, 255, 0.15);
  text-decoration: none;
  color: #007bff;
}

.category-icon {
  width: 60px;
  height: 60px;
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 1rem;
  font-size: 1.5rem;
  color: #6c757d;
  transition: all 0.3s ease;
}

.category-card:hover .category-icon {
  background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
  color: white;
}

.category-name {
  font-weight: 600;
  font-size: 1rem;
}

.community-cta-section {
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  border-radius: 16px;
  padding: 2rem;
  margin-top: 2rem;
  border: 1px solid #dee2e6;
}

.cta-title {
  font-size: 1.5rem;
  font-weight: 600;
  color: #2c3e50;
  margin-bottom: 0.5rem;
}

.cta-subtitle {
  font-size: 1rem;
  color: #6c757d;
  margin-bottom: 1.5rem;
  max-width: 500px;
  margin-left: auto;
  margin-right: auto;
}

.btn-primary {
  background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
  border: none;
  border-radius: 8px;
  font-weight: 600;
  padding: 0.75rem 1.5rem;
  transition: all 0.3s ease;
}

.btn-primary:hover {
  background: linear-gradient(135deg, #0056b3 0%, #004085 100%);
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
}

/* Stage 2: Enhanced Search Results Page Styles */
.page-header {
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  border-radius: 12px;
  padding: 2rem;
  margin-bottom: 2rem;
  border: 1px solid #dee2e6;
}

.page-title {
  font-size: 2rem;
  font-weight: 700;
  color: #2c3e50;
  margin-bottom: 0.5rem;
}

.results-info {
  color: #6c757d;
  font-size: 1rem;
}

.sorting-controls {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.sorting-controls .form-label {
  margin-bottom: 0;
  font-weight: 600;
  color: #495057;
}

/* Filters Sidebar */
.filters-sidebar {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  border: 1px solid #e9ecef;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
  position: sticky;
  top: 2rem;
}

.filters-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: #2c3e50;
  border-bottom: 2px solid #e9ecef;
  padding-bottom: 0.75rem;
  margin-bottom: 1.5rem;
}

.filter-section {
  border-bottom: 1px solid #f8f9fa;
  padding-bottom: 1rem;
}

.filter-section:last-child {
  border-bottom: none;
}

.filter-section-title {
  font-size: 1rem;
  font-weight: 600;
  color: #495057;
  margin-bottom: 0.75rem;
}

.filter-options {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.filter-options .form-check {
  margin-bottom: 0.25rem;
}

.filter-options .form-check-label {
  font-size: 0.9rem;
  color: #6c757d;
  cursor: pointer;
}

.filter-options .form-check-input:checked + .form-check-label {
  color: #007bff;
  font-weight: 500;
}

/* Enhanced Business Cards */
.business-card {
  background: white;
  border-radius: 12px;
  border: 1px solid #e9ecef;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
  transition: all 0.3s ease;
  height: 100%;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.business-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
  border-color: #007bff;
}

.business-card:hover .card-title {
  color: #0056b3 !important;
}

.badge-featured {
  background: linear-gradient(135deg, #ffd700 0%, #ffcc00 100%);
  color: #1a3353;
  font-weight: 700;
  border-radius: 8px;
  padding: 0.4em 0.8em;
  font-size: 0.8em;
  box-shadow: 0 2px 8px rgba(255, 215, 0, 0.3);
  display: flex;
  align-items: center;
  gap: 0.3em;
}

.badge-new {
  background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
  color: white;
  font-weight: 600;
  border-radius: 8px;
  padding: 0.4em 0.8em;
  font-size: 0.8em;
  box-shadow: 0 2px 8px rgba(40, 167, 69, 0.3);
}

.category-badge {
  background: rgba(0, 123, 255, 0.9);
  color: white;
  font-weight: 600;
  border-radius: 6px;
  font-size: 0.75em;
  backdrop-filter: blur(4px);
}

.rating-section {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.stars {
  display: flex;
  gap: 1px;
}

.stars .fa-star {
  font-size: 0.9rem;
}

.rating-text {
  font-size: 0.8rem;
  color: #6c757d;
}

/* Premium Card Enhancements */
.business-card.premium {
  border: 2px solid #ffd700;
  box-shadow: 0 4px 16px rgba(255, 215, 0, 0.2);
}

.business-card.premium_plus {
  border: 2px solid #ffd700;
  box-shadow: 0 6px 20px rgba(255, 215, 0, 0.3);
  background: linear-gradient(135deg, #fff 0%, #fffbf0 100%);
}
@media (max-width: 767px) {
  .business-card {
    flex-direction: column;
    min-width: 0;
    border-radius: 0.7rem;
  }
  .business-title {
    font-size: 1.1rem;
  }
  .business-description {
    font-size: 0.98rem;
    max-height: 3.2em;
  }
  .badge-elite, .badge-trending {
    font-size: 0.8em;
    padding: 0.3em 0.7em;
    top: 6px;
    left: 6px;
    right: 6px;
  }
  
  /* Mobile styles for no results page */
  .no-results-title {
    font-size: 1.5rem;
  }
  
  .no-results-subtitle {
    font-size: 1rem;
  }
  
  .categories-grid {
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 0.75rem;
  }
  
  .category-card {
    padding: 1rem;
  }
  
  .category-icon {
    width: 50px;
    height: 50px;
    font-size: 1.25rem;
  }
  
  .community-cta-section {
    padding: 1.5rem;
    margin-top: 1.5rem;
  }
  
  .cta-title {
    font-size: 1.25rem;
  }
}

/* Mobile responsive adjustments for Stage 2 */
@media (max-width: 991px) {
  .filters-sidebar {
    position: static;
    margin-bottom: 2rem;
  }
  
  .page-header {
    padding: 1.5rem;
  }
  
  .page-title {
    font-size: 1.5rem;
  }
  
  .sorting-controls {
    margin-top: 1rem;
    justify-content: flex-start;
  }
}
</style>

<script>
// Stage 2: Enhanced Search Functionality
document.addEventListener('DOMContentLoaded', function() {
  // Sorting functionality
  window.updateSort = function(sortValue) {
    const url = new URL(window.location);
    url.searchParams.set('sort', sortValue);
    url.searchParams.delete('page'); // Reset to first page when sorting
    window.location.href = url.toString();
  };
  
  // Filter functionality
  window.applyFilters = function() {
    const url = new URL(window.location);
    
    // Get category filters
    const categoryFilters = Array.from(document.querySelectorAll('input[name="cat_filter[]"]:checked'))
      .map(cb => cb.value);
    if (categoryFilters.length > 0) {
      url.searchParams.set('cat_filter', categoryFilters.join(','));
    } else {
      url.searchParams.delete('cat_filter');
    }
    
    // Get location filters
    const locationFilters = Array.from(document.querySelectorAll('input[name="location_filter[]"]:checked'))
      .map(cb => cb.value);
    if (locationFilters.length > 0) {
      url.searchParams.set('location_filter', locationFilters.join(','));
    } else {
      url.searchParams.delete('location_filter');
    }
    
    // Get rating filter
    const ratingFilter = document.querySelector('input[name="rating_filter"]:checked');
    if (ratingFilter) {
      url.searchParams.set('rating_filter', ratingFilter.value);
    } else {
      url.searchParams.delete('rating_filter');
    }
    
    // Get feature filters
    const featureFilters = Array.from(document.querySelectorAll('input[name="features[]"]:checked'))
      .map(cb => cb.value);
    if (featureFilters.length > 0) {
      url.searchParams.set('features', featureFilters.join(','));
    } else {
      url.searchParams.delete('features');
    }
    
    // Reset to first page when filtering
    url.searchParams.delete('page');
    
    window.location.href = url.toString();
  };
  
  // Clear all filters
  window.clearFilters = function() {
    const url = new URL(window.location);
    url.searchParams.delete('cat_filter');
    url.searchParams.delete('location_filter');
    url.searchParams.delete('rating_filter');
    url.searchParams.delete('features');
    url.searchParams.delete('page');
    window.location.href = url.toString();
  };
  
  // Auto-apply filters on checkbox change (optional)
  const filterCheckboxes = document.querySelectorAll('.filter-options input[type="checkbox"]');
  filterCheckboxes.forEach(checkbox => {
    checkbox.addEventListener('change', function() {
      // Uncomment the line below to auto-apply filters
      // applyFilters();
    });
  });
  
  // Auto-apply filters on radio button change
  const filterRadios = document.querySelectorAll('.filter-options input[type="radio"]');
  filterRadios.forEach(radio => {
    radio.addEventListener('change', function() {
      // Uncomment the line below to auto-apply filters
      // applyFilters();
    });
  });
});
</script>
<?php include 'includes/footer_main.php'; ?> 