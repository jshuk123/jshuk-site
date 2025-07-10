<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
$userLoggedIn = isset($_SESSION['user_id']);
$userName = $_SESSION['user_name'] ?? 'My Account';
$isAdmin = !empty($_SESSION['is_admin']) && $_SESSION['is_admin'];

// Region logic for header dropdown
$region_centers = [
    'london' => ['label' => 'London', 'icon' => 'fa-city'],
    'manchester' => ['label' => 'Manchester', 'icon' => 'fa-building'],
    'gateshead' => ['label' => 'Gateshead', 'icon' => 'fa-church'],
    'jerusalem' => ['label' => 'Jerusalem', 'icon' => 'fa-star-of-david']
];
$current_region = $_COOKIE['region'] ?? null;
$current_region_label = $current_region && isset($region_centers[$current_region]) ? $region_centers[$current_region]['label'] : 'Set Location';
?>
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
  <div class="container">
    <a class="navbar-brand elite-logo-container d-flex align-items-center" href="/">
      <div style="position:relative;display:inline-block;">
        <div class="logo-pin"></div>
        <div class="logo-string"></div>
        <span class="swing-logo">
          <img src="/images/jshuk-logo.png" alt="JShuk Logo" class="elite-logo-img">
          <div class="glimmer"></div>
          <div class="logo-shadow"></div>
        </span>
      </div>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="mainNavbar">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link" href="/">Home</a></li>
        <li class="nav-item"><a class="nav-link" href="/search.php">Browse Businesses</a></li>
        <!-- Region Dropdown -->
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="regionDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fa-solid fa-map-marker-alt me-1"></i> <span id="currentRegionLabel"><?= htmlspecialchars($current_region_label) ?></span>
          </a>
          <ul class="dropdown-menu" aria-labelledby="regionDropdown">
            <?php foreach ($region_centers as $key => $info): ?>
              <li><a class="dropdown-item region-select" href="#" data-region="<?= $key ?>"><i class="fa-solid <?= $info['icon'] ?> me-2"></i><?= htmlspecialchars($info['label']) ?></a></li>
            <?php endforeach; ?>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item text-danger" href="#" id="changeRegion">Change My Location</a></li>
          </ul>
        </li>
        <!-- End Region Dropdown -->
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="pagesDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">Pages</a>
          <ul class="dropdown-menu" aria-labelledby="pagesDropdown">
            <?php if ($userLoggedIn): ?>
              <li><a class="dropdown-item" href="/users/post_business.php">List Your Business</a></li>
            <?php else: ?>
              <li><a class="dropdown-item" href="/auth/register.php">List Your Business</a></li>
            <?php endif; ?>
            <li><a class="dropdown-item" href="/submit_job.php">Post a Job</a></li>
            <li><a class="dropdown-item" href="/recruitment.php">Job Board</a></li>
            <li><a class="dropdown-item" href="/classifieds.php">Classifieds</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="/contact.php">Contact Us</a></li>
          </ul>
        </li>
        <?php if ($userLoggedIn): ?>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fa-solid fa-user-circle"></i> <?= htmlspecialchars($userName) ?>
          </a>
          <ul class="dropdown-menu" aria-labelledby="userDropdown">
            <?php if ($isAdmin): ?>
              <li><a class="dropdown-item" href="/admin/">Dashboard</a></li>
            <?php else: ?>
              <li><a class="dropdown-item" href="/users/dashboard.php">Dashboard</a></li>
            <?php endif; ?>
            <li><a class="dropdown-item" href="/users/dashboard.php#businesses">My Listings</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item text-danger" href="/auth/logout.php">Logout</a></li>
          </ul>
        </li>
        <?php else: ?>
        <li class="nav-item"><a class="nav-link" href="/auth/login.php"><i class="fa-solid fa-sign-in-alt"></i> Login</a></li>
        <li class="nav-item"><a class="nav-link btn btn-primary text-white ms-2" href="/auth/register.php"><i class="fa-solid fa-user-plus"></i> Register</a></li>
        <?php endif; ?>
        <?php if ($isAdmin): ?>
        <li class="nav-item"><a class="nav-link text-warning" href="/admin/"><i class="fa fa-crown"></i> Admin</a></li>
        <?php endif; ?>
      </ul>
      <form class="d-flex ms-lg-3 my-2 my-lg-0" role="search" autocomplete="off">
        <input class="form-control me-2" type="search" id="liveSearch" placeholder="Search..." aria-label="Search">
        <button class="btn btn-outline-primary" type="submit" aria-label="Search"><i class="fa fa-search"></i></button>
      </form>
    </div>
  </div>
</nav>
<div id="searchResults" class="search-results"></div> 