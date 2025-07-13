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

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_identifier = trim($_POST['user_identifier']);
    $plan_id = intval($_POST['plan_id']);
    $duration_months = intval($_POST['duration_months']);
    $reason = trim($_POST['reason']);
    
    try {
        // Find user by email or ID
        $user = null;
        if (filter_var($user_identifier, FILTER_VALIDATE_EMAIL)) {
            // Search by email
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$user_identifier]);
            $user = $stmt->fetch();
        } else {
            // Search by ID
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$user_identifier]);
            $user = $stmt->fetch();
        }
        
        if (!$user) {
            $message = "User not found. Please check the email or user ID.";
            $message_type = 'danger';
        } else {
            // Get plan details
            $stmt = $pdo->prepare("SELECT * FROM subscription_plans WHERE id = ?");
            $stmt->execute([$plan_id]);
            $plan = $stmt->fetch();
            
            if (!$plan) {
                $message = "Selected plan not found.";
                $message_type = 'danger';
            } else {
                // Calculate expiry date
                $expiry_date = date('Y-m-d H:i:s', strtotime("+{$duration_months} months"));
                
                // Update user subscription
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET subscription_status = 'active',
                        subscription_plan_id = ?,
                        current_period_end = ?
                    WHERE id = ?
                ");
                $stmt->execute([$plan_id, $expiry_date, $user['id']]);
                
                // Log the free premium grant
                $stmt = $pdo->prepare("
                    INSERT INTO admin_actions (admin_id, action_type, target_user_id, details, created_at)
                    VALUES (?, 'grant_free_premium', ?, ?, ?)
                ");
                $stmt->execute([
                    $_SESSION['admin_id'] ?? 1,
                    $user['id'],
                    json_encode([
                        'plan_id' => $plan_id,
                        'plan_name' => $plan['name'],
                        'duration_months' => $duration_months,
                        'expiry_date' => $expiry_date,
                        'reason' => $reason
                    ]),
                    date('Y-m-d H:i:s')
                ]);
                
                $message = "Successfully granted {$plan['name']} access to {$user['email']} until " . date('M j, Y', strtotime($expiry_date));
                $message_type = 'success';
            }
        }
    } catch (PDOException $e) {
        $message = "Database error: " . $e->getMessage();
        $message_type = 'danger';
    }
}

// Get available plans
$plans = $pdo->query("SELECT * FROM subscription_plans WHERE status = 'active' ORDER BY price ASC")->fetchAll();

$pageTitle = "Grant Free Premium Access";
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
                        <li class="nav-item mb-1"><a href="../enhanced_carousel_manager.php" class="nav-link"><i class="fas fa-images me-2"></i>Carousel</a></li>
                        <li class="nav-item mb-1"><a href="index.php" class="nav-link active"><i class="fas fa-tags me-2"></i>Pricing</a></li>
                    </ul>
                    <hr class="text-white w-100">
                    <a href="../../logout.php" class="btn btn-danger w-100"><i class="fa fa-sign-out-alt me-2"></i>Log out</a>
                </div>
            </nav>
            <!-- Main content -->
            <main class="col-lg-10 col-md-9 ms-sm-auto px-4 py-4">
?>
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">
                    <i class="fas fa-gift me-2"></i>
                    Grant Free Premium Access
                </h1>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>
                    Back to Pricing
                </a>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?= $message_type ?> alert-dismissible fade show">
                    <?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-user-plus me-2"></i>
                                Grant Premium Access
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="post" class="needs-validation" novalidate>
                                <div class="mb-3">
                                    <label for="user_identifier" class="form-label">User Email or ID *</label>
                                    <input type="text" class="form-control" id="user_identifier" name="user_identifier" 
                                           value="<?= htmlspecialchars($_POST['user_identifier'] ?? '') ?>" required>
                                    <div class="form-text">Enter the user's email address or user ID</div>
                                    <div class="invalid-feedback">Please provide a user email or ID.</div>
                                </div>

                                <div class="mb-3">
                                    <label for="plan_id" class="form-label">Premium Plan *</label>
                                    <select class="form-select" id="plan_id" name="plan_id" required>
                                        <option value="">Select a plan...</option>
                                        <?php foreach ($plans as $plan): ?>
                                            <option value="<?= $plan['id'] ?>" <?= (isset($_POST['plan_id']) && $_POST['plan_id'] == $plan['id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($plan['name']) ?> - £<?= number_format($plan['price'], 2) ?>/month
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="invalid-feedback">Please select a plan.</div>
                                </div>

                                <div class="mb-3">
                                    <label for="duration_months" class="form-label">Duration (Months) *</label>
                                    <input type="number" class="form-control" id="duration_months" name="duration_months" 
                                           value="<?= $_POST['duration_months'] ?? 12 ?>" min="1" max="60" required>
                                    <div class="form-text">How long should the premium access last?</div>
                                    <div class="invalid-feedback">Please provide a valid duration.</div>
                                </div>

                                <div class="mb-3">
                                    <label for="reason" class="form-label">Reason for Granting</label>
                                    <textarea class="form-control" id="reason" name="reason" rows="3" 
                                              placeholder="e.g., Promotional offer, Customer service, etc."><?= htmlspecialchars($_POST['reason'] ?? '') ?></textarea>
                                    <div class="form-text">Optional: Document why this access was granted</div>
                                </div>

                                <div class="d-flex justify-content-between">
                                    <a href="index.php" class="btn btn-secondary">Cancel</a>
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-gift me-2"></i>
                                        Grant Premium Access
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-info-circle me-2"></i>
                                Information
                            </h5>
                        </div>
                        <div class="card-body">
                            <h6>How it works:</h6>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-check text-success me-2"></i>User gets immediate premium access</li>
                                <li><i class="fas fa-check text-success me-2"></i>No payment required</li>
                                <li><i class="fas fa-check text-success me-2"></i>Access expires automatically</li>
                                <li><i class="fas fa-check text-success me-2"></i>Action is logged for audit</li>
                            </ul>
                            
                            <hr>
                            
                            <h6>Available Plans:</h6>
                            <?php foreach ($plans as $plan): ?>
                                <div class="mb-2">
                                    <strong><?= htmlspecialchars($plan['name']) ?></strong><br>
                                    <small class="text-muted">
                                        £<?= number_format($plan['price'], 2) ?>/month
                                        <?php if ($plan['description']): ?>
                                            - <?= htmlspecialchars($plan['description']) ?>
                                        <?php endif; ?>
                                    </small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
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
</body>
</html> 