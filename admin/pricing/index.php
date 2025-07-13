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

// Get all pricing data
$subscriptions = $pdo->query("SELECT * FROM subscription_plans ORDER BY price ASC")->fetchAll();
$ads = $pdo->query("SELECT * FROM advertising_slots ORDER BY monthly_price ASC")->fetchAll();

// Get users with active subscriptions for free premium management
$premium_users = $pdo->query("
    SELECT u.id, u.email, u.first_name, u.last_name, u.subscription_status, u.subscription_plan_id, u.current_period_end,
           sp.name as plan_name
    FROM users u 
    LEFT JOIN subscription_plans sp ON u.subscription_plan_id = sp.id 
    WHERE u.subscription_status = 'active' 
    ORDER BY u.current_period_end DESC
")->fetchAll();

$pageTitle = "Pricing Control Panel";
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
                    <i class="fas fa-tags me-2"></i>
                    Pricing Control Panel
                </h1>
                <div>
                    <a href="grant_free_premium.php" class="btn btn-success">
                        <i class="fas fa-gift me-2"></i>
                        Grant Free Premium
                    </a>
                </div>
            </div>

            <!-- Subscription Plans Section -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-credit-card me-2"></i>
                        Subscription Plans
                    </h5>
                    <a href="edit_subscription.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus me-1"></i>
                        Add Plan
                    </a>
                </div>
                <div class="card-body">
                    <?php if (empty($subscriptions)): ?>
                        <p class="text-muted">No subscription plans found.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Price</th>
                                        <th>Annual Price</th>
                                        <th>Stripe Price ID</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($subscriptions as $plan): ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($plan['name']) ?></strong>
                                                <?php if ($plan['description']): ?>
                                                    <br><small class="text-muted"><?= htmlspecialchars($plan['description']) ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>£<?= number_format($plan['price'], 2) ?>/month</td>
                                            <td>
                                                <?php if ($plan['annual_price']): ?>
                                                    £<?= number_format($plan['annual_price'], 2) ?>/year
                                                <?php else: ?>
                                                    <span class="text-muted">Not set</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($plan['stripe_price_id']): ?>
                                                    <code class="small"><?= htmlspecialchars($plan['stripe_price_id']) ?></code>
                                                <?php else: ?>
                                                    <span class="badge bg-warning">Not configured</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?= $plan['status'] === 'active' ? 'success' : 'secondary' ?>">
                                                    <?= ucfirst($plan['status']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="edit_subscription.php?id=<?= $plan['id'] ?>" class="btn btn-outline-primary btn-sm">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Advertising Slots Section -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-ad me-2"></i>
                        Advertising Slots
                    </h5>
                    <a href="edit_ad_slot.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus me-1"></i>
                        Add Slot
                    </a>
                </div>
                <div class="card-body">
                    <?php if (empty($ads)): ?>
                        <p class="text-muted">No advertising slots found.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Monthly Price</th>
                                        <th>Annual Price</th>
                                        <th>Position</th>
                                        <th>Max Slots</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($ads as $ad): ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($ad['name']) ?></strong>
                                                <?php if ($ad['description']): ?>
                                                    <br><small class="text-muted"><?= htmlspecialchars($ad['description']) ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>£<?= number_format($ad['monthly_price'], 2) ?>/month</td>
                                            <td>
                                                <?php if ($ad['annual_price']): ?>
                                                    £<?= number_format($ad['annual_price'], 2) ?>/year
                                                <?php else: ?>
                                                    <span class="text-muted">Not set</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-info"><?= htmlspecialchars($ad['position']) ?></span>
                                            </td>
                                            <td><?= $ad['max_slots'] ?></td>
                                            <td>
                                                <span class="badge bg-<?= $ad['status'] === 'active' ? 'success' : 'secondary' ?>">
                                                    <?= ucfirst($ad['status']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="edit_ad_slot.php?id=<?= $ad['id'] ?>" class="btn btn-outline-primary btn-sm">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Premium Users Management Section -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-crown me-2"></i>
                        Premium Users Management
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($premium_users)): ?>
                        <p class="text-muted">No premium users found.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Email</th>
                                        <th>Plan</th>
                                        <th>Expires</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($premium_users as $user): ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></strong>
                                            </td>
                                            <td><?= htmlspecialchars($user['email']) ?></td>
                                            <td>
                                                <span class="badge bg-primary"><?= htmlspecialchars($user['plan_name'] ?? 'Unknown') ?></span>
                                            </td>
                                            <td>
                                                <?php if ($user['current_period_end']): ?>
                                                    <?php 
                                                    $expires = new DateTime($user['current_period_end']);
                                                    $now = new DateTime();
                                                    $is_expired = $expires < $now;
                                                    ?>
                                                    <span class="<?= $is_expired ? 'text-danger' : 'text-success' ?>">
                                                        <?= $expires->format('M j, Y') ?>
                                                        <?php if ($is_expired): ?>
                                                            <span class="badge bg-danger">Expired</span>
                                                        <?php endif; ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">No expiry set</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="edit_user_subscription.php?id=<?= $user['id'] ?>" class="btn btn-outline-warning btn-sm">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="revoke_premium.php?id=<?= $user['id'] ?>" class="btn btn-outline-danger btn-sm" 
                                                   onclick="return confirm('Are you sure you want to revoke premium access for this user?')">
                                                    <i class="fas fa-times"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
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