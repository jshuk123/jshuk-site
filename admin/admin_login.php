<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
// Uncomment the next line to test admin link visibility:
// $_SESSION['is_admin'] = true;
$userLoggedIn = isset($_SESSION['user_id']);
$userName = $_SESSION['user_name'] ?? 'My Account';
$isAdmin = !empty($_SESSION['is_admin']) && $_SESSION['is_admin'];
?>
<header class="main-header">
  <div class="container header-container">
    <a class="logo" href="/">
      <img src="/images/jshuk-logo.png" alt="JShuk Logo" width="36" height="36">
      <span>JShuk</span>
    </a>
    <nav class="main-nav" id="mainNav">
      <ul class="nav-list" id="navList">
        <li><a href="/">Home</a></li>
        <li><a href="/search.php">Browse Businesses</a></li>
        <li class="has-dropdown">
          <button type="button" class="dropdown-toggle" aria-haspopup="true" aria-expanded="false" aria-controls="pagesDropdown">Pages <i class="fa fa-chevron-down"></i></button>
          <ul class="dropdown-menu" id="pagesDropdown">
            <li><a href="/auth/register.php">List Your Business</a></li>
            <li><a href="/submit_job.php">Post a Job</a></li>
            <li><a href="/recruitment.php">Job Board</a></li>
            <li class="divider"></li>
            <li><a href="/contact.php">Contact Us</a></li>
          </ul>
        </li>
        <?php if ($userLoggedIn): ?>
        <li class="has-dropdown">
          <button type="button" class="dropdown-toggle" aria-haspopup="true" aria-expanded="false" aria-controls="userDropdown">
            <i class="fa-solid fa-user-circle"></i> <?= htmlspecialchars($userName) ?> <i class="fa fa-chevron-down"></i>
          </button>
          <ul class="dropdown-menu" id="userDropdown">
            <li><a href="/users/dashboard.php">Dashboard</a></li>
            <li><a href="/users/my_businesses.php">My Listings</a></li>
            <li class="divider"></li>
            <li><a href="/auth/logout.php" class="text-danger">Logout</a></li>
          </ul>
        </li>
        <?php else: ?>
        <li><a href="/auth/login.php" class="btn-header"><i class="fa-solid fa-sign-in-alt"></i> Login</a></li>
        <li><a href="/auth/register.php" class="btn-header btn-header-primary"><i class="fa-solid fa-user-plus"></i> Register</a></li>
        <?php endif; ?>
        <?php if ($isAdmin): ?>
        <li><a href="/admin/index.php" class="admin-link"><i class="fa fa-crown"></i> Admin</a></li>
        <?php endif; ?>
      </ul>
    </nav>
    <form class="header-search" role="search" autocomplete="off">
      <input type="search" id="liveSearch" placeholder="Search..." aria-label="Search">
      <button type="submit" aria-label="Search"><i class="fa fa-search"></i></button>
    </form>
    <button class="hamburger" id="hamburger" aria-label="Open menu" aria-controls="mainNav" aria-expanded="false">
      <span></span><span></span><span></span>
    </button>
  </div>
</header>
<!-- Floating CTAs -->
<a href="/auth/register.php" class="floating-cta"><i class="fa fa-plus"></i> Add Business</a>
<a href="/submit_job.php" class="floating-job"><i class="fa fa-file-alt"></i> Post Job</a>
<div id="searchResults" class="search-results"></div>
<script src="/assets/js/header.js"></script>