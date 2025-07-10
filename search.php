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
  <section class="mb-5 text-center">
    <h1 class="display-4 fw-bold mb-3">Find a Local Jewish Business</h1>
    <p class="lead mb-4">Search and support trusted businesses in your community.</p>
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

  <section>
    <?php if (empty($results)): ?>
      <div class="alert alert-info text-center py-5">
        <h4 class="mb-3">No businesses found.</h4>
        <p>Try a different search or browse another category.</p>
      </div>
    <?php else: ?>
      <div class="row g-4 justify-content-center">
        <?php foreach ($results as $biz): 
            $subscription_tier = $biz['subscription_tier'] ?? 'basic';
            $is_elite = $subscription_tier === 'premium_plus';
            $is_new = isset($biz['created_at']) && strtotime($biz['created_at']) >= strtotime('-7 days');
            $card_class = 'business-card flex-fill position-relative ' . getPremiumCssClasses($subscription_tier);
        ?>
          <div class="col-12 col-sm-6 col-lg-4 d-flex">
            <div class="<?= $card_class ?>" style="position:relative;">
              <!-- ELITE badge (top-left, only one, reusable) -->
              <?php if ($is_elite): ?>
                <span class="badge-elite" style="position:absolute;top:8px;left:8px;z-index:10;"> <i class="fa-solid fa-crown"></i> ELITE </span>
              <?php endif; ?>
              <!-- Trending/New badge (top-right) -->
              <?php if ($is_new): ?>
                <span class="badge badge-trending" style="position:absolute;top:8px;right:8px;z-index:10;background:#ff9800;color:#fff;font-weight:600;border-radius:8px;padding:4px 10px;font-size:0.85em;">New</span>
              <?php endif; ?>
              <div class="business-image mb-3" style="position:relative;">
                <img src="<?= htmlspecialchars($biz['main_image']) ?>" alt="<?= htmlspecialchars($biz['business_name']) ?> Logo" class="img-fluid business-logo-img" style="max-height:120px;object-fit:contain;width:100%;background:#f8fafc;border-radius:12px;">
                <span class="category-badge position-absolute top-0 start-0 m-2 px-3 py-1"> <?= htmlspecialchars($biz['category_name']) ?> </span>
              </div>
              <div class="card-body d-flex flex-column align-items-center text-center p-3" style="width:100%;">
                <div class="icon-circle mb-2"><i class="fa-solid fa-store fa-lg"></i></div>
                <h5 class="card-title text-primary fw-bold mb-1" style="width:100%;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"> 
                    <?= htmlspecialchars($biz['business_name']) ?>
                </h5>
                <p class="text-muted small mb-2" style="margin-bottom:8px;">Category: <?= htmlspecialchars($biz['category_name']) ?></p>
                <p class="flex-grow-1 text-secondary mb-3 business-description" style="max-height:3.6em;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;line-clamp:2;"> <?= htmlspecialchars($biz['description']) ?> </p>
                <a href="business.php?id=<?= urlencode($biz['id']) ?>" class="btn btn-elite mt-auto" aria-label="View listing for <?= htmlspecialchars($biz['business_name']) ?>">View Listing</a>
              </div>
            </div>
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
}
</style>
<?php include 'includes/footer_main.php'; ?> 