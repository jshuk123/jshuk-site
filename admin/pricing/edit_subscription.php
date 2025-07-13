<?php
// Start output buffering
ob_start();

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../logs/php_errors.log');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    // Load configuration
    require_once '../../config/config.php';
    
    // Check admin access
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../../auth/login.php');
        ob_end_clean();
        exit();
    }
    
    // Verify admin role
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if (!$user || $user['role'] !== 'admin') {
        header('Location: ../../index.php');
        ob_end_clean();
        exit();
    }

$id = $_GET['id'] ?? null;
$editing = false;
$message = '';

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM subscription_plans WHERE id = ?");
    $stmt->execute([$id]);
    $plan = $stmt->fetch();
    if ($plan) {
        $editing = true;
    } else {
        header("Location: index.php?error=plan_not_found");
        exit();
    }
} else {
    $plan = [
        'name' => '', 
        'price' => '', 
        'annual_price' => '',
        'billing_interval' => 'monthly', 
        'stripe_price_id' => '', 
        'stripe_annual_price_id' => '',
        'status' => 'active', 
        'description' => '',
        'image_limit' => '',
        'testimonial_limit' => '',
        'trial_period_days' => 0
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $price = floatval($_POST['price']);
    $annual_price = !empty($_POST['annual_price']) ? floatval($_POST['annual_price']) : null;
    $billing_interval = $_POST['billing_interval'];
    $stripe_price_id = trim($_POST['stripe_price_id']);
    $stripe_annual_price_id = trim($_POST['stripe_annual_price_id']);
    $status = $_POST['status'];
    $description = trim($_POST['description']);
    $image_limit = !empty($_POST['image_limit']) ? intval($_POST['image_limit']) : null;
    $testimonial_limit = !empty($_POST['testimonial_limit']) ? intval($_POST['testimonial_limit']) : null;
    $trial_period_days = intval($_POST['trial_period_days']);

    try {
        if ($editing) {
            $stmt = $pdo->prepare("
                UPDATE subscription_plans 
                SET name = ?, price = ?, annual_price = ?, billing_interval = ?, 
                    stripe_price_id = ?, stripe_annual_price_id = ?, status = ?, 
                    description = ?, image_limit = ?, testimonial_limit = ?, trial_period_days = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $name, $price, $annual_price, $billing_interval, 
                $stripe_price_id, $stripe_annual_price_id, $status, 
                $description, $image_limit, $testimonial_limit, $trial_period_days, $id
            ]);
            $message = "Subscription plan updated successfully!";
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO subscription_plans 
                (name, price, annual_price, billing_interval, stripe_price_id, 
                 stripe_annual_price_id, status, description, image_limit, 
                 testimonial_limit, trial_period_days) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $name, $price, $annual_price, $billing_interval, 
                $stripe_price_id, $stripe_annual_price_id, $status, 
                $description, $image_limit, $testimonial_limit, $trial_period_days
            ]);
            $message = "Subscription plan created successfully!";
        }
        
        header("Location: index.php?success=1");
        exit();
    } catch (PDOException $e) {
        $message = "Error: " . $e->getMessage();
    }
}

$pageTitle = ($editing ? 'Edit' : 'Add') . " Subscription Plan";
} catch (Exception $e) {
    error_log("Admin pricing panel error: " . $e->getMessage());
    ob_end_clean();
    
    if (defined('APP_DEBUG') && APP_DEBUG) {
        echo "<h1>Error</h1>";
        echo "<p>An error occurred: " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p>Check the error logs for more details.</p>";
    } else {
        header("Location: ../../500.php");
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f4f6fa; transition: background 0.3s, color 0.3s; }
        .sidebar { min-height: 100vh; background: #212529; }
        .sidebar .nav-link { color: #fff; }
        .sidebar .nav-link.active, .sidebar .nav-link:hover { background: #343a40; color: #ffc107; }
        .stat-card { border-radius: 1rem; transition: transform 0.2s; }
        .stat-card:hover { transform: translateY(-5px) scale(1.02); }
        .notification { border-radius: 0.5rem; margin-bottom: 0.5rem; }
        .recent-table th, .recent-table td { vertical-align: middle; }
        .dark-mode { background: #181a1b !important; color: #e0e0e0 !important; }
        .dark-mode .sidebar { background: #181a1b !important; }
        .dark-mode .card, .dark-mode .table, .dark-mode .modal-content { background: #23272b !important; color: #e0e0e0; }
        .dark-mode .card-header, .dark-mode .table th { background: #23272b !important; color: #ffc107; }
        .dark-mode .nav-link { color: #e0e0e0 !important; }
        .dark-mode .nav-link.active, .dark-mode .nav-link:hover { background: #23272b !important; color: #ffc107 !important; }
        .dark-mode .btn, .dark-mode .form-control { background: #23272b; color: #e0e0e0; border-color: #444; }
        .dark-mode .btn-primary { background: #ffc107; color: #23272b; border: none; }
        .dark-mode .btn-danger { background: #dc3545; color: #fff; border: none; }
        .dark-mode .alert { background: #23272b; color: #ffc107; border-color: #444; }
        @media (max-width: 991px) {
            .sidebar { min-height: auto; }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-lg-2 col-md-3 d-md-block sidebar py-4 px-3">
                <div class="d-flex flex-column align-items-start">
                    <a href="../index.php" class="mb-4 text-white text-decoration-none fs-4 fw-bold"><i class="fa fa-crown me-2"></i>Admin Panel</a>
                    <ul class="nav nav-pills flex-column w-100 mb-auto">
                        <li class="nav-item mb-1"><a href="../index.php" class="nav-link"><i class="fas fa-home me-2"></i>Dashboard</a></li>
                        <li class="nav-item mb-1"><a href="../businesses.php" class="nav-link"><i class="fas fa-store me-2"></i>Businesses</a></li>
                        <li class="nav-item mb-1"><a href="../users.php" class="nav-link"><i class="fas fa-users me-2"></i>Users</a></li>
                        <li class="nav-item mb-1"><a href="../categories.php" class="nav-link"><i class="fas fa-tags me-2"></i>Categories</a></li>
                        <li class="nav-item mb-1"><a href="../recruitment.php" class="nav-link"><i class="fas fa-briefcase me-2"></i>Jobs</a></li>
                        <li class="nav-item mb-1"><a href="../classifieds.php" class="nav-link"><i class="fas fa-list-alt me-2"></i>Classifieds</a></li>
                        <li class="nav-item mb-1"><a href="../reviews.php" class="nav-link"><i class="fas fa-star me-2"></i>Reviews</a></li>
                        <li class="nav-item mb-1"><a href="../ads.php" class="nav-link"><i class="fas fa-ad me-2"></i>Ads</a></li>
                        <li class="nav-item mb-1"><a href="../carousel_manager.php" class="nav-link"><i class="fas fa-images me-2"></i>Carousel</a></li>
                        <li class="nav-item mb-1"><a href="index.php" class="nav-link active"><i class="fas fa-tags me-2"></i>Pricing</a></li>
                    </ul>
                    <hr class="text-white w-100">
                    <a href="../../logout.php" class="btn btn-danger w-100"><i class="fa fa-sign-out-alt me-2"></i>Log out</a>
                </div>
            </nav>
            <!-- Main content -->
            <main class="col-lg-10 col-md-9 ms-sm-auto px-4 py-4">
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">
                    <i class="fas fa-credit-card me-2"></i>
                    <?= $editing ? 'Edit' : 'Add' ?> Subscription Plan
                </h1>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>
                    Back to Pricing
                </a>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-info">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <form method="post" class="needs-validation" novalidate>
                        <div class="row">
                            <div class="col-md-6">
                                <h5 class="mb-3">Basic Information</h5>
                                
                                <div class="mb-3">
                                    <label for="name" class="form-label">Plan Name *</label>
                                    <input type="text" class="form-control" id="name" name="name" 
                                           value="<?= htmlspecialchars($plan['name']) ?>" required>
                                    <div class="invalid-feedback">Please provide a plan name.</div>
                                </div>

                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control" id="description" name="description" rows="3"
                                              placeholder="Describe the plan features and benefits"><?= htmlspecialchars($plan['description']) ?></textarea>
                                </div>

                                <div class="mb-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="active" <?= $plan['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                        <option value="inactive" <?= $plan['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <h5 class="mb-3">Pricing</h5>
                                
                                <div class="mb-3">
                                    <label for="price" class="form-label">Monthly Price (£) *</label>
                                    <input type="number" class="form-control" id="price" name="price" 
                                           value="<?= $plan['price'] ?>" step="0.01" min="0" required>
                                    <div class="invalid-feedback">Please provide a valid price.</div>
                                </div>

                                <div class="mb-3">
                                    <label for="annual_price" class="form-label">Annual Price (£)</label>
                                    <input type="number" class="form-control" id="annual_price" name="annual_price" 
                                           value="<?= $plan['annual_price'] ?>" step="0.01" min="0">
                                    <small class="form-text text-muted">Leave empty if no annual option</small>
                                </div>

                                <div class="mb-3">
                                    <label for="billing_interval" class="form-label">Default Billing Interval</label>
                                    <select class="form-select" id="billing_interval" name="billing_interval">
                                        <option value="monthly" <?= $plan['billing_interval'] === 'monthly' ? 'selected' : '' ?>>Monthly</option>
                                        <option value="yearly" <?= $plan['billing_interval'] === 'yearly' ? 'selected' : '' ?>>Yearly</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="trial_period_days" class="form-label">Trial Period (Days)</label>
                                    <input type="number" class="form-control" id="trial_period_days" name="trial_period_days" 
                                           value="<?= $plan['trial_period_days'] ?>" min="0" max="365">
                                    <small class="form-text text-muted">0 = no trial period</small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <h5 class="mb-3">Stripe Configuration</h5>
                                
                                <div class="mb-3">
                                    <label for="stripe_price_id" class="form-label">Stripe Monthly Price ID</label>
                                    <input type="text" class="form-control" id="stripe_price_id" name="stripe_price_id" 
                                           value="<?= htmlspecialchars($plan['stripe_price_id']) ?>" 
                                           placeholder="price_1234567890abcdef">
                                    <small class="form-text text-muted">Get this from your Stripe Dashboard</small>
                                </div>

                                <div class="mb-3">
                                    <label for="stripe_annual_price_id" class="form-label">Stripe Annual Price ID</label>
                                    <input type="text" class="form-control" id="stripe_annual_price_id" name="stripe_annual_price_id" 
                                           value="<?= htmlspecialchars($plan['stripe_annual_price_id']) ?>" 
                                           placeholder="price_1234567890abcdef">
                                    <small class="form-text text-muted">Leave empty if no annual option</small>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <h5 class="mb-3">Feature Limits</h5>
                                
                                <div class="mb-3">
                                    <label for="image_limit" class="form-label">Image Limit</label>
                                    <input type="number" class="form-control" id="image_limit" name="image_limit" 
                                           value="<?= $plan['image_limit'] ?>" min="0">
                                    <small class="form-text text-muted">Leave empty for unlimited</small>
                                </div>

                                <div class="mb-3">
                                    <label for="testimonial_limit" class="form-label">Testimonial Limit</label>
                                    <input type="number" class="form-control" id="testimonial_limit" name="testimonial_limit" 
                                           value="<?= $plan['testimonial_limit'] ?>" min="0">
                                    <small class="form-text text-muted">Leave empty for unlimited</small>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="index.php" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>
                                <?= $editing ? 'Update' : 'Create' ?> Plan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Form validation
(function() {
    'use strict';
    window.addEventListener('load', function() {
        var forms = document.getElementsByClassName('needs-validation');
        var validation = Array.prototype.filter.call(forms, function(form) {
            form.addEventListener('submit', function(event) {
                if (form.checkValidity() === false) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    }, false);
})();
</script>

            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Dark mode toggle
        document.getElementById('toggleDarkMode')?.addEventListener('click', function() {
            document.body.classList.toggle('dark-mode');
            localStorage.setItem('darkMode', document.body.classList.contains('dark-mode'));
        });

        // Load dark mode preference
        if (localStorage.getItem('darkMode') === 'true') {
            document.body.classList.add('dark-mode');
        }
    </script>
</body>
</html> 