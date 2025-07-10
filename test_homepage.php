<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$userLoggedIn = isset($_SESSION['user_id']);
$userName = $_SESSION['user_name'] ?? 'My Account';
$isAdmin = !empty($_SESSION['is_admin']) && $_SESSION['is_admin'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>JShuk | Test Page</title>
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
  <!-- Font Awesome -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <!-- Core CSS -->
  <link rel="stylesheet" href="<?= BASE_PATH ?>css/style.css">
  <link rel="stylesheet" href="<?= BASE_PATH ?>css/components/header.css">
</head>
<body>
  <!-- Test Navigation -->
  <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
    <div class="container">
      <a class="navbar-brand" href="/">
        <img src="/images/jshuk-logo.png" alt="JShuk Logo" width="36" height="36">
        <span>JShuk</span>
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="mainNavbar">
        <ul class="navbar-nav me-auto mb-2 mb-lg-0">
          <li class="nav-item"><a class="nav-link" href="/">Home</a></li>
          <li class="nav-item"><a class="nav-link" href="/search.php">Browse Businesses</a></li>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="pagesDropdown" role="button" data-bs-toggle="dropdown">Pages</a>
            <ul class="dropdown-menu" aria-labelledby="pagesDropdown">
              <li><a class="dropdown-item" href="/auth/register.php">List Your Business</a></li>
              <li><a class="dropdown-item" href="/submit_job.php">Post a Job</a></li>
              <li><a class="dropdown-item" href="/recruitment.php">Job Board</a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item" href="/contact.php">Contact Us</a></li>
            </ul>
          </li>
          <?php if ($userLoggedIn): ?>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
              <i class="fa-solid fa-user-circle"></i> <?= htmlspecialchars($userName) ?>
            </a>
            <ul class="dropdown-menu" aria-labelledby="userDropdown">
              <li><a class="dropdown-item" href="/dashboard.php">Dashboard</a></li>
              <li><a class="dropdown-item" href="/my-businesses.php">My Listings</a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item text-danger" href="/auth/logout.php">Logout</a></li>
            </ul>
          </li>
          <?php else: ?>
          <li class="nav-item"><a class="nav-link" href="/auth/login.php"><i class="fa-solid fa-sign-in-alt"></i> Login</a></li>
          <li class="nav-item"><a class="nav-link btn btn-primary text-white ms-2" href="/auth/register.php"><i class="fa-solid fa-user-plus"></i> Register</a></li>
          <?php endif; ?>
        </ul>
        <form class="d-flex ms-lg-3 my-2 my-lg-0" role="search">
          <input class="form-control me-2" type="search" placeholder="Search..." aria-label="Search">
          <button class="btn btn-outline-primary" type="submit" aria-label="Search"><i class="fa fa-search"></i></button>
        </form>
      </div>
    </div>
  </nav>

  <!-- Test Content -->
  <div class="container mt-5">
    <div class="row">
      <div class="col-12">
        <h1>Header Test Page</h1>
        <p>This is a test page to verify the header is working properly.</p>
        
        <div class="alert alert-info">
          <h5>Debug Information:</h5>
          <ul>
            <li>User Logged In: <?= $userLoggedIn ? 'Yes' : 'No' ?></li>
            <li>User Name: <?= htmlspecialchars($userName) ?></li>
            <li>Is Admin: <?= $isAdmin ? 'Yes' : 'No' ?></li>
            <li>BASE_PATH: <?= BASE_PATH ?></li>
            <li>Session Status: <?= session_status() ?></li>
          </ul>
        </div>
        
        <div class="mt-4">
          <a href="/" class="btn btn-primary">Back to Homepage</a>
        </div>
      </div>
    </div>
  </div>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 