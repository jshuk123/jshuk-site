<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/db_connect.php';

// Include helper functions
if (file_exists(__DIR__ . '/helpers.php')) {
    require_once __DIR__ . '/helpers.php';
}

$is_logged_in = isset($_SESSION['user_id']);
$user_id = $_SESSION['user_id'] ?? null;
$user_name = $_SESSION['user_name'] ?? 'Guest';
$is_admin = $_SESSION['is_admin'] ?? false;

$current_location = $_SESSION['location'] ?? 'Manchester';

// ✅ Added: Detect current page for active navigation highlighting
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$current_page = str_replace('index', 'home', $current_page); // Handle index.php as home

// Fetch categories for the dropdown
try {
    $stmt = $pdo->query("SELECT id, name, icon FROM business_categories ORDER BY name");
    $all_categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Handle DB error gracefully
    $all_categories = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) : 'JShuk - Your Jewish Business Hub' ?></title>
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="<?= isset($metaDescription) ? htmlspecialchars($metaDescription) : 'Find trusted Jewish businesses in London, Manchester, and across the UK. Discover kosher restaurants, Jewish services, local businesses, and community resources. Your complete Jewish business directory.' ?>">
    <meta name="keywords" content="<?= isset($metaKeywords) ? htmlspecialchars($metaKeywords) : 'jewish business london, jewish directory, kosher restaurants london, jewish services uk, local jewish business, community marketplace, manchester, gateshead, jewish professionals, kosher caterers' ?>">
    <meta name="author" content="JShuk">
    
    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="<?= isset($pageTitle) ? htmlspecialchars($pageTitle) : 'JShuk - Jewish Business Directory | London & UK' ?>">
    <meta property="og:description" content="<?= isset($metaDescription) ? htmlspecialchars($metaDescription) : 'Find trusted Jewish businesses in London, Manchester, and across the UK. Discover kosher restaurants, Jewish services, and community resources.' ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://jshuk.com<?= $_SERVER['REQUEST_URI'] ?>">
    <meta property="og:image" content="https://jshuk.com/images/jshuk-logo.png">
    <meta property="og:site_name" content="JShuk">
    
    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= isset($pageTitle) ? htmlspecialchars($pageTitle) : 'JShuk - Jewish Business Directory' ?>">
    <meta name="twitter:description" content="<?= isset($metaDescription) ? htmlspecialchars($metaDescription) : 'Find trusted Jewish businesses in London, Manchester, and across the UK.' ?>">
    <meta name="twitter:image" content="https://jshuk.com/images/jshuk-logo.png">
    
    <!-- Additional SEO Meta Tags -->
    <meta name="robots" content="index, follow">
    <meta name="googlebot" content="index, follow">
    <link rel="canonical" href="https://jshuk.com<?= $_SERVER['REQUEST_URI'] ?>">
    
    <!-- Google Analytics (replace GA_MEASUREMENT_ID with your actual ID) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=GA_MEASUREMENT_ID"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
      gtag('config', 'GA_MEASUREMENT_ID');
    </script>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    
    <!-- Font Awesome CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom Site-wide Stylesheet -->
    <link rel="stylesheet" href="/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="/css/components/header.css">
    <link rel="stylesheet" href="/css/components/subscription-badges.css">
    <link rel="stylesheet" href="/css/components/search-bar.css?v=<?= time() ?>">
    
    <!-- Page-specific CSS -->
    <?php if (isset($page_css)): ?>
        <link rel="stylesheet" href="/css/pages/<?= htmlspecialchars($page_css) ?>?v=<?php echo time(); ?>">
    <?php endif; ?>
    
    <!-- Favicon -->
    <link rel="icon" href="/images/jshuk-logo.png" type="image/png">
    
    <!-- Tippy.js Tooltip Library (local theme) -->
    <link rel="stylesheet" href="/css/tippy-light.css" />
    <script src="/js/vendor/popper.min.js"></script>
    <script src="/js/vendor/tippy.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
      if (window.tippy) {
        tippy('[data-tippy-content]', {
          theme: 'jshuk-elite',
          animation: 'shift-away',
          arrow: true,
          delay: [100, 30],
          duration: [250, 180],
          maxWidth: 320,
          interactive: true,
          placement: 'top',
        });
      }
    });
    </script>

    <!-- FORCE MOBILE MENU CONTRAST FIX -->
    <style>
    @media (max-width: 1023px) {
      .mobile-nav-menu {
        background: #fffbe6 !important;
      }
      .mobile-nav-link, .mobile-nav-link i {
        color: #1a3353 !important;
        font-weight: 600 !important;
        background: none !important;
      }
      .mobile-nav-link:hover,
      .mobile-nav-link:focus {
        color: #ffcc00 !important;
        background: rgba(255,255,255,0.1) !important;
      }
      .mobile-nav-link.active {
        color: #ffcc00 !important;
        background: rgba(255, 215, 0, 0.15) !important;
        border-left: 3px solid #ffcc00 !important;
      }
      .mobile-nav-link.active i,
      .mobile-nav-link:hover i,
      .mobile-nav-link:focus i {
        color: #ffcc00 !important;
      }
    }
    </style>

    <!-- Removed unused ad preloads to fix browser warnings -->
</head>
<body>

<div class="header-ad-bar">
<?php include($_SERVER['DOCUMENT_ROOT'].'/partials/ads/header_ad.php'); ?>
</div>

<!-- Desktop Header -->
<header class="header-main shadow-sm d-none d-xl-block">
    <nav class="navbar navbar-expand-xl">
        <div class="navbar-inner d-flex align-items-center justify-content-between">
            <a class="navbar-brand d-flex align-items-center" href="/">
                <img src="/images/jshuk-logo.png" alt="JShuk Logo" class="logo">
                <span class="fw-bold text-white ms-2">JShuk</span>
            </a>
            <div class="d-flex align-items-center flex-grow-1 justify-content-end">
                <ul class="navbar-nav ms-auto mb-2 mb-xl-0 align-items-center">
                    <li class="nav-item">
                        <a class="nav-link <?= ($current_page == 'home' || $current_page == 'index') ? 'active' : '' ?>" href="/index.php" aria-current="<?= ($current_page == 'home' || $current_page == 'index') ? 'page' : 'false' ?>">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($current_page == 'businesses') ? 'active' : '' ?>" href="/businesses.php" aria-current="<?= ($current_page == 'businesses') ? 'page' : 'false' ?>">Browse Businesses</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($current_page == 'london') ? 'active' : '' ?>" href="/london.php" aria-current="<?= ($current_page == 'london') ? 'page' : 'false' ?>">London</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($current_page == 'recruitment') ? 'active' : '' ?>" href="/recruitment.php" aria-current="<?= ($current_page == 'recruitment') ? 'page' : 'false' ?>">Jobs</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= ($current_page == 'classifieds') ? 'active' : '' ?>" href="/classifieds.php" aria-current="<?= ($current_page == 'classifieds') ? 'page' : 'false' ?>">Classifieds</a>
                    </li>
                    
                    <!-- User Authentication Section -->
                    <?php if ($is_logged_in): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false" aria-haspopup="true">
                                <i class="fas fa-user-circle me-1"></i>
                                <?= htmlspecialchars($user_name) ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="/users/dashboard.php">
                                    <i class="fas fa-tachometer-alt me-2"></i>My Dashboard
                                </a></li>
                                <li><a class="dropdown-item" href="/users/profile.php">
                                    <i class="fas fa-user-edit me-2"></i>Edit Profile
                                </a></li>
                                <li><a class="dropdown-item" href="/users/my_businesses.php">
                                    <i class="fas fa-store me-2"></i>My Businesses
                                </a></li>
                                <?php if ($is_admin): ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="/admin/index.php">
                                        <i class="fas fa-cog me-2"></i>Admin
                                    </a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="/auth/logout.php">
                                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                                </a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link <?= ($current_page == 'login') ? 'active' : '' ?>" href="/auth/login.php" aria-current="<?= ($current_page == 'login') ? 'page' : 'false' ?>">
                                <i class="fas fa-sign-in-alt me-1"></i>Login
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link btn btn-warning btn-sm ms-2 <?= ($current_page == 'register') ? 'active' : '' ?>" href="/auth/register.php" aria-current="<?= ($current_page == 'register') ? 'page' : 'false' ?>">
                                <i class="fas fa-user-plus me-1"></i>Sign Up
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
</header>

<!-- Mobile Header -->
<header class="header-main-mobile d-xl-none">
    <div class="mobile-header-inner">
        <div class="mobile-header-top">
            <a class="mobile-brand" href="/">
                <img src="/images/jshuk-logo.png" alt="JShuk Logo" class="mobile-logo">
                <span class="mobile-brand-text">JShuk</span>
            </a>
            <div class="mobile-header-actions">
                <?php if ($is_logged_in): ?>
                    <a href="/users/dashboard.php" class="mobile-user-btn" aria-label="My Dashboard">
                        <i class="fas fa-user-circle"></i>
                    </a>
                <?php else: ?>
                    <a href="/auth/login.php" class="mobile-login-btn" aria-label="Login">
                        <i class="fas fa-sign-in-alt"></i>
                    </a>
                <?php endif; ?>
                <button class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="Open menu" aria-expanded="false" aria-controls="mobileNavMenu">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>
        
        <!-- Mobile Navigation Menu (Side Drawer) -->
        <div class="mobile-nav-menu" id="mobileNavMenu" role="dialog" aria-modal="true" aria-label="Navigation menu">
            <div class="mobile-nav-header">
                <span class="mobile-nav-title">Menu</span>
                <button class="mobile-nav-close" id="mobileNavClose" aria-label="Close menu">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <ul class="mobile-nav-list">
                <li class="mobile-nav-item">
                    <a href="/index.php" class="mobile-nav-link <?= ($current_page == 'home' || $current_page == 'index') ? 'active' : '' ?>" aria-current="<?= ($current_page == 'home' || $current_page == 'index') ? 'page' : 'false' ?>">
                        <i class="fas fa-home"></i>
                        <span>Home</span>
                    </a>
                </li>
                <li class="mobile-nav-item">
                    <a href="/businesses.php" class="mobile-nav-link <?= ($current_page == 'businesses') ? 'active' : '' ?>" aria-current="<?= ($current_page == 'businesses') ? 'page' : 'false' ?>">
                        <i class="fas fa-store"></i>
                        <span>Browse Businesses</span>
                    </a>
                </li>
                <li class="mobile-nav-item">
                    <a href="/london.php" class="mobile-nav-link <?= ($current_page == 'london') ? 'active' : '' ?>" aria-current="<?= ($current_page == 'london') ? 'page' : 'false' ?>">
                        <i class="fas fa-map-marker-alt"></i>
                        <span>London</span>
                    </a>
                </li>
                <li class="mobile-nav-item">
                    <a href="/recruitment.php" class="mobile-nav-link <?= ($current_page == 'recruitment') ? 'active' : '' ?>" aria-current="<?= ($current_page == 'recruitment') ? 'page' : 'false' ?>">
                        <i class="fas fa-briefcase"></i>
                        <span>Jobs</span>
                    </a>
                </li>
                <li class="mobile-nav-item">
                    <a href="/classifieds.php" class="mobile-nav-link <?= ($current_page == 'classifieds') ? 'active' : '' ?>" aria-current="<?= ($current_page == 'classifieds') ? 'page' : 'false' ?>">
                        <i class="fas fa-tags"></i>
                        <span>Classifieds</span>
                    </a>
                </li>
                
                <?php if ($is_logged_in): ?>
                    <li class="mobile-nav-divider"></li>
                    <li class="mobile-nav-item">
                        <a href="/users/dashboard.php" class="mobile-nav-link">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>My Dashboard</span>
                        </a>
                    </li>
                    <li class="mobile-nav-item">
                        <a href="/users/profile.php" class="mobile-nav-link">
                            <i class="fas fa-user-edit"></i>
                            <span>Edit Profile</span>
                        </a>
                    </li>
                    <li class="mobile-nav-item">
                        <a href="/users/my_businesses.php" class="mobile-nav-link">
                            <i class="fas fa-store"></i>
                            <span>My Businesses</span>
                        </a>
                    </li>
                    <?php if ($is_admin): ?>
                        <li class="mobile-nav-item">
                            <a href="/admin/index.php" class="mobile-nav-link">
                                <i class="fas fa-cog"></i>
                                <span>Admin</span>
                            </a>
                        </li>
                    <?php endif; ?>
                    <li class="mobile-nav-divider"></li>
                    <li class="mobile-nav-item">
                        <a href="/auth/logout.php" class="mobile-nav-link">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Logout</span>
                        </a>
                    </li>
                <?php else: ?>
                    <li class="mobile-nav-divider"></li>
                    <li class="mobile-nav-item">
                        <a href="/auth/register.php" class="mobile-nav-link mobile-signup">
                            <i class="fas fa-user-plus"></i>
                            <span>Sign Up</span>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</header>

<!-- Sticky Mobile Bottom Navigation -->
<nav class="mobile-bottom-nav d-xl-none" role="navigation" aria-label="Bottom navigation">
    <a href="/index.php" class="mobile-bottom-nav-item <?= ($current_page == 'home' || $current_page == 'index') ? 'active' : '' ?>" aria-current="<?= ($current_page == 'home' || $current_page == 'index') ? 'page' : 'false' ?>">
        <i class="fas fa-home"></i>
        <span>Home</span>
    </a>
    <a href="/businesses.php" class="mobile-bottom-nav-item <?= ($current_page == 'businesses') ? 'active' : '' ?>" aria-current="<?= ($current_page == 'businesses') ? 'page' : 'false' ?>">
        <i class="fas fa-store"></i>
        <span>Businesses</span>
    </a>
    <a href="/recruitment.php" class="mobile-bottom-nav-item <?= ($current_page == 'recruitment') ? 'active' : '' ?>" aria-current="<?= ($current_page == 'recruitment') ? 'page' : 'false' ?>">
        <i class="fas fa-briefcase"></i>
        <span>Jobs</span>
    </a>
    <a href="/classifieds.php" class="mobile-bottom-nav-item <?= ($current_page == 'classifieds') ? 'active' : '' ?>" aria-current="<?= ($current_page == 'classifieds') ? 'page' : 'false' ?>">
        <i class="fas fa-tags"></i>
        <span>Classifieds</span>
    </a>
</nav>

<!-- Debug: Print confirmation -->
<?php /* if (isset($_GET['debug_ads'])) {
    echo "<div style='background:#ffe;border:1px solid #fc0;padding:10px;margin:10px;font-family:monospace;'>";
    echo "<h3>🔍 AD SYSTEM DEBUG - HEADER</h3>";
    echo "<p>✅ Header loaded successfully</p>";
    echo "<p>📄 Current page: " . $current_page . "</p>";
    echo "<p>👤 User logged in: " . ($is_logged_in ? 'Yes' : 'No') . "</p>";
    echo "</div>";
} */ ?>

<!-- Mobile JavaScript is handled in main.js -->
<script>
// Immediate mobile menu validation
document.addEventListener('DOMContentLoaded', function() {
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    const mobileNavMenu = document.getElementById('mobileNavMenu');
    const mobileNavClose = document.getElementById('mobileNavClose');
    
    if (!mobileMenuToggle || !mobileNavMenu) {
        console.warn('Mobile menu elements not found - check HTML structure');
    } else {
        console.log('Mobile menu elements found and ready');
    }
});
</script> 