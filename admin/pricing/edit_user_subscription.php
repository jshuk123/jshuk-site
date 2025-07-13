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

$user_id = $_GET['id'] ?? null;
$message = '';
$message_type = '';

if (!$user_id) {
    header("Location: index.php?error=no_user_id");
    exit();
}

// Get user details
$stmt = $pdo->prepare("
    SELECT u.*, sp.name as plan_name, sp.price as plan_price
    FROM users u 
    LEFT JOIN subscription_plans sp ON u.subscription_plan_id = sp.id 
    WHERE u.id = ?
");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    header("Location: index.php?error=user_not_found");
    exit();
}

// Get available plans
$plans = $pdo->query("SELECT * FROM subscription_plans WHERE status = 'active' ORDER BY price ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subscription_status = $_POST['subscription_status'];
    $subscription_plan_id = !empty($_POST['subscription_plan_id']) ? intval($_POST['subscription_plan_id']) : null;
    $current_period_end = !empty($_POST['current_period_end']) ? $_POST['current_period_end'] : null;
    $notes = trim($_POST['notes']);
    
    try {
        // Update user subscription
        $stmt = $pdo->prepare("
            UPDATE users 
            SET subscription_status = ?,
                subscription_plan_id = ?,
                current_period_end = ?
            WHERE id = ?
        ");
        $stmt->execute([$subscription_status, $subscription_plan_id, $current_period_end, $user_id]);
        
        // Log the admin action
        $stmt = $pdo->prepare("
            INSERT INTO admin_actions (admin_id, action_type, target_user_id, details, created_at)
            VALUES (?, 'edit_user_subscription', ?, ?, ?)
        ");
        $stmt->execute([
            $_SESSION['admin_id'] ?? 1,
            $user_id,
            json_encode([
                'old_status' => $user['subscription_status'],
                'new_status' => $subscription_status,
                'old_plan_id' => $user['subscription_plan_id'],
                'new_plan_id' => $subscription_plan_id,
                'old_expiry' => $user['current_period_end'],
                'new_expiry' => $current_period_end,
                'notes' => $notes
            ]),
            date('Y-m-d H:i:s')
        ]);
        
        $message = "User subscription updated successfully!";
        $message_type = 'success';
        
        // Refresh user data
        $stmt = $pdo->prepare("
            SELECT u.*, sp.name as plan_name, sp.price as plan_price
            FROM users u 
            LEFT JOIN subscription_plans sp ON u.subscription_plan_id = sp.id 
            WHERE u.id = ?
        ");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
    } catch (PDOException $e) {
        $message = "Database error: " . $e->getMessage();
        $message_type = 'danger';
    }
}

$pageTitle = "Edit User Subscription";
include '../admin_header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">
                    <i class="fas fa-user-edit me-2"></i>
                    Edit User Subscription
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
                                <i class="fas fa-user me-2"></i>
                                User Information
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Name:</strong> <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></p>
                                    <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
                                    <p><strong>User ID:</strong> <?= $user['id'] ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Account Status:</strong> 
                                        <span class="badge bg-<?= $user['status'] === 'active' ? 'success' : 'secondary' ?>">
                                            <?= ucfirst($user['status']) ?>
                                        </span>
                                    </p>
                                    <p><strong>Joined:</strong> <?= date('M j, Y', strtotime($user['created_at'])) ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mt-4">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-credit-card me-2"></i>
                                Subscription Details
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="post" class="needs-validation" novalidate>
                                <div class="mb-3">
                                    <label for="subscription_status" class="form-label">Subscription Status</label>
                                    <select class="form-select" id="subscription_status" name="subscription_status">
                                        <option value="" <?= empty($user['subscription_status']) ? 'selected' : '' ?>>No Subscription</option>
                                        <option value="active" <?= $user['subscription_status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                        <option value="inactive" <?= $user['subscription_status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                        <option value="cancelled" <?= $user['subscription_status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                        <option value="past_due" <?= $user['subscription_status'] === 'past_due' ? 'selected' : '' ?>>Past Due</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="subscription_plan_id" class="form-label">Subscription Plan</label>
                                    <select class="form-select" id="subscription_plan_id" name="subscription_plan_id">
                                        <option value="">No Plan</option>
                                        <?php foreach ($plans as $plan): ?>
                                            <option value="<?= $plan['id'] ?>" <?= $user['subscription_plan_id'] == $plan['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($plan['name']) ?> - £<?= number_format($plan['price'], 2) ?>/month
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="current_period_end" class="form-label">Expiry Date</label>
                                    <input type="datetime-local" class="form-control" id="current_period_end" name="current_period_end" 
                                           value="<?= $user['current_period_end'] ? date('Y-m-d\TH:i', strtotime($user['current_period_end'])) : '' ?>">
                                    <small class="form-text text-muted">Leave empty for no expiry</small>
                                </div>

                                <div class="mb-3">
                                    <label for="notes" class="form-label">Admin Notes</label>
                                    <textarea class="form-control" id="notes" name="notes" rows="3" 
                                              placeholder="Optional notes about this change"></textarea>
                                </div>

                                <div class="d-flex justify-content-between">
                                    <a href="index.php" class="btn btn-secondary">Cancel</a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>
                                        Update Subscription
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
                                Current Status
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <strong>Current Plan:</strong><br>
                                <?php if ($user['subscription_plan_id'] && $user['plan_name']): ?>
                                    <span class="badge bg-primary"><?= htmlspecialchars($user['plan_name']) ?></span>
                                    <br><small class="text-muted">£<?= number_format($user['plan_price'], 2) ?>/month</small>
                                <?php else: ?>
                                    <span class="text-muted">No plan assigned</span>
                                <?php endif; ?>
                            </div>

                            <div class="mb-3">
                                <strong>Subscription Status:</strong><br>
                                <?php if ($user['subscription_status']): ?>
                                    <span class="badge bg-<?= $user['subscription_status'] === 'active' ? 'success' : 'warning' ?>">
                                        <?= ucfirst($user['subscription_status']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">No subscription</span>
                                <?php endif; ?>
                            </div>

                            <div class="mb-3">
                                <strong>Expires:</strong><br>
                                <?php if ($user['current_period_end']): ?>
                                    <?php 
                                    $expires = new DateTime($user['current_period_end']);
                                    $now = new DateTime();
                                    $is_expired = $expires < $now;
                                    ?>
                                    <span class="<?= $is_expired ? 'text-danger' : 'text-success' ?>">
                                        <?= $expires->format('M j, Y g:i A') ?>
                                        <?php if ($is_expired): ?>
                                            <br><span class="badge bg-danger">Expired</span>
                                        <?php endif; ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">No expiry set</span>
                                <?php endif; ?>
                            </div>

                            <hr>

                            <div class="d-grid gap-2">
                                <a href="grant_free_premium.php?user_id=<?= $user['id'] ?>" class="btn btn-success btn-sm">
                                    <i class="fas fa-gift me-2"></i>
                                    Grant Free Premium
                                </a>
                                <a href="revoke_premium.php?id=<?= $user['id'] ?>" class="btn btn-danger btn-sm" 
                                   onclick="return confirm('Are you sure you want to revoke premium access for this user?')">
                                    <i class="fas fa-times me-2"></i>
                                    Revoke Premium
                                </a>
                            </div>
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

<?php include '../admin_footer.php'; ?> 