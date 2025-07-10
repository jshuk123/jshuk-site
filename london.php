<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config/config.php';
require_once 'includes/subscription_functions.php';
if (file_exists('includes/cache.php')) {
    require_once 'includes/cache.php';
}
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load London-specific businesses
try {
    if (isset($pdo) && $pdo) {
        // Load London businesses
        $stmt = $pdo->prepare("SELECT b.*, c.name as category_name, u.subscription_tier 
                              FROM businesses b 
                              LEFT JOIN business_categories c ON b.category_id = c.id 
                              LEFT JOIN users u ON b.user_id = u.id 
                              WHERE b.status = 'active' 
                              AND (b.location LIKE '%london%' OR b.address LIKE '%london%' OR b.address LIKE '%London%')
                              ORDER BY 
                                CASE u.subscription_tier 
                                    WHEN 'premium_plus' THEN 1 
                                    WHEN 'premium' THEN 2 
                                    ELSE 3 
                                END,
                                b.created_at DESC 
                              LIMIT 12");
        $stmt->execute();
        $london_businesses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Load categories
        $stmt = $pdo->query("SELECT id, name FROM business_categories ORDER BY name ASC");
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Load stats
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM businesses WHERE status = 'active' AND (location LIKE '%london%' OR address LIKE '%london%' OR address LIKE '%London%')");
        $stmt->execute();
        $london_business_count = $stmt->fetchColumn();
        
    }
} catch (PDOException $e) {
    $london_businesses = [];
    $categories = [];
    $london_business_count = 0;
}

$pageTitle = "Jewish Businesses in London | JShuk - Find Trusted Jewish Services";
$page_css = "london.css";
$metaDescription = "Discover trusted Jewish businesses in London. Find kosher restaurants, Jewish services, local businesses, and community resources across London. Your complete Jewish business directory for London.";
$metaKeywords = "jewish business london, jewish directory london, kosher restaurants london, jewish services london, local jewish business london, jewish community london, kosher caterers london, jewish professionals london";

include 'includes/header_main.php';
require_once 'includes/ad_renderer.php';
?>

<!-- HERO SECTION -->
<section class="london-hero" data-scroll>
  <div class="container">
    <div class="hero-content text-center">
      <h1 class="hero-title">Jewish Businesses in London</h1>
      <p class="hero-subtitle">Discover trusted Jewish services, kosher restaurants, and local businesses across London</p>
      <div class="hero-stats">
        <div class="stat-item">
          <span class="stat-number"><?= $london_business_count ?></span>
          <span class="stat-label">London Businesses</span>
        </div>
        <div class="stat-item">
          <span class="stat-number"><?= count($categories) ?></span>
          <span class="stat-label">Categories</span>
        </div>
        <div class="stat-item">
          <span class="stat-number">24/7</span>
          <span class="stat-label">Available</span>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- SEARCH BAR -->
<section class="search-section" data-scroll>
  <div class="container">
    <div class="search-container">
      <form action="/businesses.php" method="GET" class="london-search-form">
        <input type="hidden" name="location" value="london">
        <div class="search-row">
          <select name="category" class="form-select">
            <option value="">All Categories</option>
            <?php foreach ($categories as $category): ?>
              <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
            <?php endforeach; ?>
          </select>
          <input type="text" name="search" class="form-control" placeholder="Search Jewish businesses in London...">
          <button type="submit" class="btn btn-primary">Search</button>
        </div>
      </form>
    </div>
  </div>
</section>

<!-- FEATURED LONDON BUSINESSES -->
<?php if (!empty($london_businesses)): ?>
<section class="london-businesses" data-scroll>
  <div class="container">
    <div class="section-header text-center">
      <h2>Featured Jewish Businesses in London</h2>
      <p>Discover trusted local businesses serving the Jewish community</p>
    </div>
    
    <div class="businesses-grid">
      <?php foreach ($london_businesses as $business): ?>
        <div class="business-card">
          <div class="business-image">
            <img src="/images/jshuk-logo.png" alt="<?= htmlspecialchars($business['business_name']) ?>" class="img-fluid">
          </div>
          <div class="business-content">
            <h3 class="business-name"><?= htmlspecialchars($business['business_name']) ?></h3>
            <p class="business-category"><?= htmlspecialchars($business['category_name'] ?? 'General') ?></p>
            <p class="business-description"><?= htmlspecialchars(substr($business['description'] ?? '', 0, 100)) ?>...</p>
            <a href="/business.php?id=<?= $business['id'] ?>&slug=<?= urlencode($business['slug']) ?>" class="btn btn-outline-primary">View Details</a>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    
    <div class="text-center mt-4">
      <a href="/businesses.php?location=london" class="btn btn-primary btn-lg">View All London Businesses</a>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- LONDON AREAS -->
<section class="london-areas" data-scroll>
  <div class="container">
    <div class="section-header text-center">
      <h2>Jewish Communities Across London</h2>
      <p>Find Jewish businesses in key areas of London</p>
    </div>
    
    <div class="areas-grid">
      <div class="area-card">
        <h3>Golders Green</h3>
        <p>Home to a large Jewish community with kosher restaurants, synagogues, and Jewish schools</p>
        <a href="/businesses.php?location=london&search=golders+green" class="btn btn-sm btn-outline-primary">Find Businesses</a>
      </div>
      
      <div class="area-card">
        <h3>Stamford Hill</h3>
        <p>Traditional Jewish neighborhood with kosher shops, bakeries, and community services</p>
        <a href="/businesses.php?location=stamford-hill" class="btn btn-sm btn-outline-primary">Find Businesses</a>
      </div>
      
      <div class="area-card">
        <h3>Hendon</h3>
        <p>Modern Jewish community with schools, restaurants, and professional services</p>
        <a href="/businesses.php?location=london&search=hendon" class="btn btn-sm btn-outline-primary">Find Businesses</a>
      </div>
      
      <div class="area-card">
        <h3>Edgware</h3>
        <p>Growing Jewish area with kosher food options and community facilities</p>
        <a href="/businesses.php?location=london&search=edgware" class="btn btn-sm btn-outline-primary">Find Businesses</a>
      </div>
      
      <div class="area-card">
        <h3>Finchley</h3>
        <p>Established Jewish community with synagogues, schools, and local businesses</p>
        <a href="/businesses.php?location=london&search=finchley" class="btn btn-sm btn-outline-primary">Find Businesses</a>
      </div>
      
      <div class="area-card">
        <h3>Central London</h3>
        <p>Jewish businesses in the heart of London, including kosher restaurants and professional services</p>
        <a href="/businesses.php?location=london&search=central" class="btn btn-sm btn-outline-primary">Find Businesses</a>
      </div>
    </div>
  </div>
</section>

<!-- CATEGORIES SECTION -->
<section class="categories-section" data-scroll>
  <div class="container">
    <div class="section-header text-center">
      <h2>Popular Categories in London</h2>
      <p>Find Jewish businesses by category</p>
    </div>
    
    <div class="categories-grid">
      <?php foreach (array_slice($categories, 0, 8) as $category): ?>
        <div class="category-card">
          <div class="category-icon">
            <i class="fas fa-store"></i>
          </div>
          <h3><?= htmlspecialchars($category['name']) ?></h3>
          <a href="/businesses.php?location=london&category=<?= $category['id'] ?>" class="btn btn-sm btn-outline-primary">Browse</a>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- CTA SECTION -->
<section class="cta-section" data-scroll>
  <div class="container">
    <div class="cta-content text-center">
      <h2>Are You a Jewish Business in London?</h2>
      <p>Join our directory and connect with the local Jewish community</p>
      <div class="cta-buttons">
        <a href="/users/post_business.php" class="btn btn-primary btn-lg">List Your Business</a>
        <a href="/about.php" class="btn btn-outline-primary btn-lg">Learn More</a>
      </div>
    </div>
  </div>
</section>

<?php include 'includes/footer_main.php'; ?>

<!-- Structured Data for London Page -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "WebPage",
  "name": "Jewish Businesses in London",
  "description": "Find trusted Jewish businesses, kosher restaurants, and services across London. Your complete Jewish business directory for London.",
  "url": "https://jshuk.com/london.php",
  "mainEntity": {
    "@type": "ItemList",
    "name": "Jewish Businesses in London",
    "numberOfItems": <?= $london_business_count ?>,
    "itemListElement": [
      <?php foreach (array_slice($london_businesses, 0, 5) as $index => $business): ?>
      {
        "@type": "ListItem",
        "position": <?= $index + 1 ?>,
        "item": {
          "@type": "LocalBusiness",
          "name": "<?= htmlspecialchars($business['business_name']) ?>",
          "description": "<?= htmlspecialchars(substr($business['description'] ?? '', 0, 200)) ?>",
          "url": "https://jshuk.com/business.php?id=<?= $business['id'] ?>&slug=<?= urlencode($business['slug']) ?>",
          "address": {
            "@type": "PostalAddress",
            "addressLocality": "London",
            "addressCountry": "GB"
          }
        }
      }<?= $index < min(4, count($london_businesses) - 1) ? ',' : '' ?>
      <?php endforeach; ?>
    ]
  }
}
</script>

<style>
.london-hero {
  background: linear-gradient(135deg, #1a3353 0%, #2c5aa0 100%);
  color: white;
  padding: 80px 0;
}

.hero-title {
  font-size: 3rem;
  font-weight: 700;
  margin-bottom: 1rem;
}

.hero-subtitle {
  font-size: 1.25rem;
  margin-bottom: 2rem;
  opacity: 0.9;
}

.hero-stats {
  display: flex;
  justify-content: center;
  gap: 3rem;
  margin-top: 2rem;
}

.stat-item {
  text-align: center;
}

.stat-number {
  display: block;
  font-size: 2.5rem;
  font-weight: 700;
  color: #ffd000;
}

.stat-label {
  font-size: 0.9rem;
  opacity: 0.8;
}

.search-section {
  background: #f8f9fa;
  padding: 40px 0;
}

.search-container {
  max-width: 600px;
  margin: 0 auto;
}

.london-search-form .search-row {
  display: flex;
  gap: 10px;
  align-items: center;
}

.london-search-form .form-select,
.london-search-form .form-control {
  flex: 1;
}

.london-businesses {
  padding: 60px 0;
}

.businesses-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 2rem;
  margin-top: 2rem;
}

.business-card {
  background: white;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 4px 20px rgba(0,0,0,0.1);
  transition: transform 0.3s ease;
}

.business-card:hover {
  transform: translateY(-5px);
}

.business-image {
  height: 200px;
  background: #f8f9fa;
  display: flex;
  align-items: center;
  justify-content: center;
}

.business-content {
  padding: 1.5rem;
}

.business-name {
  font-size: 1.25rem;
  font-weight: 600;
  margin-bottom: 0.5rem;
}

.business-category {
  color: #6c757d;
  font-size: 0.9rem;
  margin-bottom: 0.5rem;
}

.business-description {
  color: #495057;
  margin-bottom: 1rem;
  line-height: 1.5;
}

.london-areas {
  background: #f8f9fa;
  padding: 60px 0;
}

.areas-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 2rem;
  margin-top: 2rem;
}

.area-card {
  background: white;
  padding: 2rem;
  border-radius: 12px;
  text-align: center;
  box-shadow: 0 4px 20px rgba(0,0,0,0.1);
}

.area-card h3 {
  color: #1a3353;
  margin-bottom: 1rem;
}

.categories-section {
  padding: 60px 0;
}

.categories-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 2rem;
  margin-top: 2rem;
}

.category-card {
  background: white;
  padding: 2rem;
  border-radius: 12px;
  text-align: center;
  box-shadow: 0 4px 20px rgba(0,0,0,0.1);
  transition: transform 0.3s ease;
}

.category-card:hover {
  transform: translateY(-5px);
}

.category-icon {
  font-size: 2rem;
  color: #1a3353;
  margin-bottom: 1rem;
}

.cta-section {
  background: linear-gradient(135deg, #1a3353 0%, #2c5aa0 100%);
  color: white;
  padding: 60px 0;
  text-align: center;
}

.cta-buttons {
  display: flex;
  gap: 1rem;
  justify-content: center;
  margin-top: 2rem;
}

.section-header {
  margin-bottom: 3rem;
}

.section-header h2 {
  color: #1a3353;
  margin-bottom: 1rem;
}

@media (max-width: 768px) {
  .hero-title {
    font-size: 2rem;
  }
  
  .hero-stats {
    flex-direction: column;
    gap: 1rem;
  }
  
  .london-search-form .search-row {
    flex-direction: column;
  }
  
  .cta-buttons {
    flex-direction: column;
    align-items: center;
  }
}
</style> 