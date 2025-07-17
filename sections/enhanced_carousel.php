<?php
/**
 * Enhanced Carousel Section
 * JShuk Advanced Carousel Management System
 * Phase 5: Enhanced Frontend Display - MOBILE FIXED VERSION
 * 
 * NOTE: This carousel code is being moved to a new "Featured Showcase" section in Step 2
 */

require_once __DIR__ . '/../includes/enhanced_carousel_functions.php';

// 🔥 BULLETPROOF CAROUSEL SLIDE LOADER
$location = $_SESSION['user_location'] ?? 'all';
$today = date('Y-m-d');
$zone = 'homepage';

try {
    // Check if sponsored column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM carousel_slides LIKE 'sponsored'");
    $hasSponsoredColumn = $stmt->rowCount() > 0;
    
    // Build ORDER BY clause based on available columns
    $orderBy = "sort_order DESC";
    if ($hasSponsoredColumn) {
        $orderBy .= ", sponsored DESC";
    }
    $orderBy .= ", id DESC";
    
    // Start with the simplest possible query to eliminate parameter binding issues
    $query = $pdo->prepare("
        SELECT * FROM carousel_slides
        WHERE is_active = 1
          AND zone = :zone
        ORDER BY {$orderBy}
    ");

    $query->execute([
        ':zone' => $zone
    ]);

    $slides = $query->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo "<div style='background:#f8d7da;color:#721c24;padding:10px;z-index:9999;position:relative;'>
        ❌ SQL Error: " . htmlspecialchars($e->getMessage()) . "</div>";
    $slides = [];
}

// Filter slides to only those with a valid image file
$valid_slides = array_filter($slides, function($slide) {
    return !empty($slide['image_url']) && strpos($slide['image_url'], 'data:') === false && file_exists(__DIR__ . '/../' . $slide['image_url']);
});
$numSlides = count($valid_slides);

// If no valid slides, show placeholder
if ($numSlides === 0) {
    $valid_slides = [
        [
            'id' => 'placeholder',
            'title' => 'Welcome to JShuk',
            'subtitle' => 'Your Jewish Community Hub',
            'image_url' => 'images/jshuk-logo.png',
            'cta_text' => 'Explore Now',
            'cta_link' => 'businesses.php'
        ]
    ];
    $numSlides = 1;
}

$loop = $numSlides > 1;
?>

<!-- STATIC HERO SECTION (Converted from Carousel) -->
<section class="hero-section">
  <div class="hero-content">
    <h1 class="hero-title">Connect with Your Jewish Community</h1>
    <p class="hero-subtitle">Discover trusted businesses, services, and opportunities across the UK</p>
    
    <!-- ENHANCED SEARCH FORM WITH LIVE SEARCH -->
    <div class="hero-search-container">
      <?php
      $location_filter = $_GET['location'] ?? '';
      $category_filter = $_GET['category'] ?? '';
      $search_query = $_GET['search'] ?? '';
      ?>
      <form action="/businesses.php" method="GET" class="hero-search-form" role="search">
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
        <div class="search-input-container">
          <input type="text" name="search" id="heroSearchInput" class="form-control" placeholder="🔍 Search businesses..." value="<?= htmlspecialchars($search_query) ?>" autocomplete="off" />
          <div id="heroSearchResults" class="search-results-dropdown"></div>
        </div>
        <button type="submit" class="btn btn-search" aria-label="Search">
          <i class="fa fa-search"></i>
          <span class="d-none d-md-inline">Search</span>
        </button>
      </form>
    </div>
  </div>
</section>

<style>
/* Hero Section Styles - Stage 1 Implementation */
.hero-section {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 70vh;
  color: white;
  text-align: center;
  padding: 2rem;
}

/* ACTION 1.1: Background Image & Overlay */
.hero-section::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-image: linear-gradient(rgba(26, 51, 83, 0.7), rgba(26, 51, 83, 0.8)), url('/images/hero-background.jpg');
  background-size: cover;
  background-position: center;
  background-attachment: fixed;
  z-index: -1;
}

/* Fallback background for debugging */
.hero-section {
  background-color: #1a3353;
  background-image: linear-gradient(rgba(26, 51, 83, 0.7), rgba(26, 51, 83, 0.8)), url('/images/hero-background.jpg');
  background-size: cover;
  background-position: center;
}

.hero-content {
  max-width: 800px;
  z-index: 2;
}

/* ACTION 1.2: Hero Headline Styling */
.hero-title {
  font-size: 3.5rem;
  font-weight: 700;
  margin-bottom: 1rem;
  color: #FFFFFF;
  text-shadow: 2px 2px 8px rgba(0, 0, 0, 0.6);
  line-height: 1.2;
}

.hero-subtitle {
  font-size: 1.3rem;
  font-weight: 400;
  margin-bottom: 2rem;
  color: #FFD700;
  text-shadow: 1px 1px 4px rgba(0, 0, 0, 0.6);
  line-height: 1.4;
}

/* ACTION 1.3: Modern Search Form Design */
.hero-search-container {
  margin-top: 2rem;
}

.hero-search-form {
  display: flex;
  gap: 1rem;
  max-width: 600px;
  margin: 0 auto;
  background-color: rgba(255, 255, 255, 0.2);
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  padding: 20px;
  border-radius: 15px;
  border: 1px solid rgba(255, 255, 255, 0.3);
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
}

.hero-search-form .form-select,
.hero-search-form .form-control {
  border: 1px solid rgba(255, 255, 255, 0.3);
  border-radius: 8px;
  padding: 0.75rem;
  font-size: 1rem;
  background-color: rgba(255, 255, 255, 0.9);
  color: #333;
}

.hero-search-form .form-select:focus,
.hero-search-form .form-control:focus {
  outline: none;
  border-color: #FFD700;
  box-shadow: 0 0 0 2px rgba(255, 215, 0, 0.3);
}

.hero-search-form .btn-search {
  background: linear-gradient(90deg, #FFD700 0%, #FFCC00 100%);
  color: #1a3353;
  border: none;
  border-radius: 8px;
  padding: 0.75rem 1.5rem;
  font-weight: 600;
  transition: all 0.3s ease;
  box-shadow: 0 4px 16px rgba(255, 215, 0, 0.3);
}

.hero-search-form .btn-search:hover {
  background: linear-gradient(90deg, #FFCC00 0%, #FFD700 100%);
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(255, 215, 0, 0.4);
}

/* Live Search Dropdown Styles */
.search-input-container {
  position: relative;
  flex-grow: 1;
}

.search-results-dropdown {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  background: white;
  border-radius: 8px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
  border: 1px solid rgba(0, 0, 0, 0.1);
  z-index: 1000;
  max-height: 400px;
  overflow-y: auto;
  display: none;
  margin-top: 4px;
}

.search-results-dropdown.show {
  display: block;
}

.search-result-item {
  display: flex;
  align-items: center;
  padding: 12px 16px;
  border-bottom: 1px solid #f0f0f0;
  text-decoration: none;
  color: #333;
  transition: background-color 0.2s ease;
}

.search-result-item:last-child {
  border-bottom: none;
}

.search-result-item:hover {
  background-color: #f8f9fa;
  text-decoration: none;
  color: #333;
}

.search-result-item:focus {
  background-color: #e9ecef;
  outline: none;
}

.search-result-image {
  width: 40px;
  height: 40px;
  border-radius: 6px;
  object-fit: cover;
  margin-right: 12px;
  flex-shrink: 0;
}

.search-result-content {
  flex-grow: 1;
  min-width: 0;
}

.search-result-name {
  font-weight: 600;
  font-size: 14px;
  margin-bottom: 2px;
  color: #2c3e50;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.search-result-category {
  font-size: 12px;
  color: #6c757d;
  margin-bottom: 2px;
}

.search-result-description {
  font-size: 12px;
  color: #868e96;
  line-height: 1.3;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.search-result-tier {
  margin-left: 8px;
  padding: 2px 6px;
  border-radius: 4px;
  font-size: 10px;
  font-weight: 600;
  text-transform: uppercase;
}

.search-result-tier.premium_plus {
  background-color: #ffd700;
  color: #1a3353;
}

.search-result-tier.premium {
  background-color: #e9ecef;
  color: #495057;
}

.search-result-tier.basic {
  background-color: #f8f9fa;
  color: #6c757d;
}

.search-loading {
  padding: 20px;
  text-align: center;
  color: #6c757d;
}

.search-no-results {
  padding: 20px;
  text-align: center;
  color: #6c757d;
  font-style: italic;
}

.search-error {
  padding: 20px;
  text-align: center;
  color: #dc3545;
}
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(255, 215, 0, 0.4);
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .hero-title {
    font-size: 2.5rem;
  }
  
  .hero-subtitle {
    font-size: 1.1rem;
  }
  
  .hero-search-form {
    flex-direction: column;
    gap: 0.75rem;
    padding: 15px;
  }
  
  .hero-search-form .btn-search {
    padding: 0.75rem 1rem;
  }
}
</style>

<!-- BACKUP OF ORIGINAL CAROUSEL CODE (FOR STEP 2) -->
<!--
ORIGINAL CAROUSEL CODE - TO BE MOVED TO FEATURED SHOWCASE SECTION:

<section class="carousel-section">
  <div class="swiper-container enhanced-homepage-carousel">
    <div class="swiper-wrapper">
      <?php foreach ($valid_slides as $slide): ?>
        <?php if (!empty($slide['image_url'])): ?>
          <div class="swiper-slide">
            <div class="carousel-content">
              <div class="image-container">
                <img
                  src="/<?= ltrim($slide['image_url'], '/') ?>"
                  alt="<?= htmlspecialchars($slide['title']) ?>"
                  class="carousel-img"
                  loading="eager"
                />
              </div>
              <div class="text-block">
                <h2 class="carousel-title"><?= htmlspecialchars($slide['title']) ?></h2>
                <?php if (!empty($slide['subtitle']) && $slide['subtitle'] !== $slide['title']): ?>
                  <p class="carousel-subtitle"><?= htmlspecialchars($slide['subtitle']) ?></p>
                <?php endif; ?>
                <?php if (!empty($slide['cta_text']) && !empty($slide['cta_link'])): ?>
                  <a href="<?= htmlspecialchars($slide['cta_link']) ?>" class="carousel-cta">
                    <?= htmlspecialchars($slide['cta_text']) ?>
                  </a>
                <?php endif; ?>
              </div>
            </div>
          </div>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>

    <div class="swiper-button-prev"></div>
    <div class="swiper-button-next"></div>
    <div class="swiper-pagination"></div>
  </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.js"></script>
<script>
  document.addEventListener("DOMContentLoaded", function () {
    new Swiper('.enhanced-homepage-carousel', {
      loop: true,
      autoplay: {
        delay: 5000,
        disableOnInteraction: false
      },
      pagination: {
        el: '.swiper-pagination',
        clickable: true
      },
      navigation: {
        nextEl: '.swiper-button-next',
        prevEl: '.swiper-button-prev'
      },
      effect: 'slide'
    });
  });
</script>

<style>
/* Original carousel styles - to be moved to new location */
.swiper-container {
  width: 100%;
  height: 600px;
  position: relative;
  overflow: hidden;
}

.swiper-wrapper {
  display: flex;
  transition-property: transform;
  box-sizing: content-box;
}

.swiper-slide {
  flex-shrink: 0;
  width: 100%;
  height: 100%;
  position: relative;
  background: #fff;
}

.carousel-content {
  position: relative;
  height: 100%;
  width: 100%;
  overflow: hidden;
}

.image-container {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  overflow: hidden;
}

.carousel-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.text-block {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.4);
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  color: white;
  text-align: center;
  padding: 20px;
  z-index: 2;
}

.carousel-title {
  font-size: 2.5rem;
  font-weight: 700;
  margin-bottom: 15px;
  text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.7);
  line-height: 1.2;
}

.carousel-subtitle {
  font-size: 1.2rem;
  margin-bottom: 20px;
  text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.7);
}

.carousel-cta {
  display: inline-block;
  background: #007bff;
  color: white;
  padding: 12px 24px;
  text-decoration: none;
  border-radius: 6px;
  font-weight: 600;
  transition: background-color 0.3s ease;
}

.carousel-cta:hover {
  background: #0056b3;
  color: white;
  text-decoration: none;
}

.swiper-button-prev,
.swiper-button-next {
  color: white;
  background: rgba(0, 0, 0, 0.5);
  width: 50px;
  height: 50px;
  border-radius: 50%;
  margin-top: -25px;
}

.swiper-pagination-bullet {
  background: white;
  opacity: 0.7;
}

.swiper-pagination-bullet-active {
  opacity: 1;
}

@media (max-width: 768px) {
  .swiper-container {
    height: 400px;
  }
  
  .carousel-title {
    font-size: 1.8rem;
  }
  
  .carousel-subtitle {
    font-size: 1rem;
  }
  
  .swiper-button-prev,
  .swiper-button-next {
    width: 40px;
    height: 40px;
    margin-top: -20px;
  }
}
</style>
-->

<script>
// Enhanced Live Search Functionality
document.addEventListener('DOMContentLoaded', function() {
  const searchInput = document.getElementById('heroSearchInput');
  const searchResults = document.getElementById('heroSearchResults');
  
  if (!searchInput || !searchResults) return;
  
  let searchTimeout;
  let currentRequest = null;
  
  // Debounce function
  function debounce(func, wait) {
    return function executedFunction(...args) {
      const later = () => {
        clearTimeout(searchTimeout);
        func(...args);
      };
      clearTimeout(searchTimeout);
      searchTimeout = setTimeout(later, wait);
    };
  }
  
  // Perform live search
  async function performLiveSearch(query) {
    // Cancel previous request if still pending
    if (currentRequest) {
      currentRequest.abort();
    }
    
    // Show loading state
    searchResults.innerHTML = '<div class="search-loading"><i class="fa fa-spinner fa-spin"></i> Searching...</div>';
    searchResults.classList.add('show');
    
    try {
      // Create abort controller for this request
      const controller = new AbortController();
      currentRequest = controller;
      
      const response = await fetch(`/api/live_search.php?q=${encodeURIComponent(query)}&limit=8`, {
        signal: controller.signal
      });
      
      if (!response.ok) {
        throw new Error('Search request failed');
      }
      
      const data = await response.json();
      
      if (data.success && data.results.length > 0) {
        let html = '';
        data.results.forEach(function(result) {
          html += `
            <a href="${result.url}" class="search-result-item">
              <img src="${result.image}" alt="${result.name}" class="search-result-image" onerror="this.src='/images/jshuk-logo.png'">
              <div class="search-result-content">
                <div class="search-result-name">${result.name}</div>
                <div class="search-result-category">${result.category}</div>
                <div class="search-result-description">${result.description}</div>
              </div>
              <span class="search-result-tier ${result.tier}">${result.tier.replace('_', ' ')}</span>
            </a>
          `;
        });
        searchResults.innerHTML = html;
      } else {
        searchResults.innerHTML = '<div class="search-no-results">No businesses found</div>';
      }
    } catch (error) {
      if (error.name === 'AbortError') {
        // Request was cancelled, do nothing
        return;
      }
      console.error('Search error:', error);
      searchResults.innerHTML = '<div class="search-error">Search error occurred</div>';
    } finally {
      currentRequest = null;
    }
  }
  
  // Debounced search function
  const debouncedSearch = debounce(performLiveSearch, 300);
  
  // Input event listener
  searchInput.addEventListener('input', function() {
    const query = this.value.trim();
    
    if (query.length < 2) {
      searchResults.classList.remove('show');
      return;
    }
    
    debouncedSearch(query);
  });
  
  // Focus event listener
  searchInput.addEventListener('focus', function() {
    const query = this.value.trim();
    if (query.length >= 2) {
      searchResults.classList.add('show');
    }
  });
  
  // Hide results when clicking outside
  document.addEventListener('click', function(e) {
    if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
      searchResults.classList.remove('show');
    }
  });
  
  // Keyboard navigation
  searchInput.addEventListener('keydown', function(e) {
    const visibleResults = searchResults.querySelectorAll('.search-result-item');
    
    if (e.key === 'ArrowDown') {
      e.preventDefault();
      const firstResult = visibleResults[0];
      if (firstResult) {
        firstResult.focus();
      }
    }
  });
  
  // Keyboard navigation for results
  searchResults.addEventListener('keydown', function(e) {
    const visibleResults = Array.from(this.querySelectorAll('.search-result-item'));
    const currentIndex = visibleResults.findIndex(item => item === document.activeElement);
    
    if (e.key === 'ArrowDown') {
      e.preventDefault();
      const nextIndex = (currentIndex + 1) % visibleResults.length;
      visibleResults[nextIndex].focus();
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      const prevIndex = currentIndex > 0 ? currentIndex - 1 : visibleResults.length - 1;
      visibleResults[prevIndex].focus();
    } else if (e.key === 'Escape') {
      searchResults.classList.remove('show');
      searchInput.focus();
    }
  });
});
</script> 