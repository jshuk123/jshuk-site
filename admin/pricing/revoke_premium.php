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
    SELECT u.*, sp.name as plan_name
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reason = trim($_POST['reason']);
    $immediate = isset($_POST['immediate']);
    
    try {
        // Store old subscription data for logging
        $old_subscription_status = $user['subscription_status'];
        $old_subscription_plan_id = $user['subscription_plan_id'];
        $old_current_period_end = $user['current_period_end'];
        
        // Revoke premium access
        $stmt = $pdo->prepare("
            UPDATE users 
            SET subscription_status = 'inactive',
                subscription_plan_id = NULL,
                current_period_end = ?
            WHERE id = ?
        ");
        
        // If immediate, set expiry to now, otherwise keep current expiry
        $new_expiry = $immediate ? date('Y-m-d H:i:s') : $user['current_period_end'];
        $stmt->execute([$new_expiry, $user_id]);
        
        // Log the admin action
        $stmt = $pdo->prepare("
            INSERT INTO admin_actions (admin_id, action_type, target_user_id, details, created_at)
            VALUES (?, 'revoke_premium', ?, ?, ?)
        ");
        $stmt->execute([
            $_SESSION['admin_id'] ?? 1,
            $user_id,
            json_encode([
                'old_status' => $old_subscription_status,
                'old_plan_id' => $old_subscription_plan_id,
                'old_expiry' => $old_current_period_end,
                'new_expiry' => $new_expiry,
                'immediate' => $immediate,
                'reason' => $reason
            ]),
            date('Y-m-d H:i:s')
        ]);
        
        $message = "Premium access revoked successfully for " . htmlspecialchars($user['email']);
        $message_type = 'success';
        
        // Refresh user data
        $stmt = $pdo->prepare("
            SELECT u.*, sp.name as plan_name
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

$pageTitle = "Revoke Premium Access";
include '../admin_header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">
                    <i class="fas fa-times-circle me-2"></i>
                    Revoke Premium Access
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
                                    <p><strong>Current Plan:</strong> 
                                        <?php if ($user['subscription_plan_id'] && $user['plan_name']): ?>
                                            <span class="badge bg-primary"><?= htmlspecialchars($user['plan_name']) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">No plan</span>
                                        <?php endif; ?>
                                    </p>
                                    <p><strong>Status:</strong> 
                                        <span class="badge bg-<?= $user['subscription_status'] === 'active' ? 'success' : 'secondary' ?>">
                                            <?= ucfirst($user['subscription_status'] ?? 'none') ?>
                                        </span>
                                    </p>
                                    <?php if ($user['current_period_end']): ?>
                                        <p><strong>Expires:</strong> <?= date('M j, Y g:i A', strtotime($user['current_period_end'])) ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if ($user['subscription_status'] === 'active'): ?>
                        <div class="card mt-4">
                            <div class="card-header bg-warning text-dark">
                                <h5 class="mb-0">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    Revoke Premium Access
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-warning">
                                    <strong>Warning:</strong> This action will revoke premium access for this user. 
                                    They will lose access to premium features immediately or at the end of their current period.
                                </div>

                                <form method="post" class="needs-validation" novalidate>
                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="immediate" name="immediate">
                                            <label class="form-check-label" for="immediate">
                                                <strong>Revoke immediately</strong>
                                            </label>
                                            <div class="form-text">
                                                If checked, access will be revoked immediately. 
                                                If unchecked, access will continue until the current expiry date.
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="reason" class="form-label">Reason for Revoking *</label>
                                        <textarea class="form-control" id="reason" name="reason" rows="3" 
                                                  placeholder="Please provide a reason for revoking premium access..." required></textarea>
                                        <div class="invalid-feedback">Please provide a reason for revoking access.</div>
                                    </div>

                                    <div class="d-flex justify-content-between">
                                        <a href="index.php" class="btn btn-secondary">Cancel</a>
                                        <button type="submit" class="btn btn-danger">
                                            <i class="fas fa-times me-2"></i>
                                            Revoke Premium Access
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="card mt-4">
                            <div class="card-header bg-info text-white">
                                <h5 class="mb-0">
                                    <i class="fas fa-info-circle me-2"></i>
                                    No Active Premium Access
                                </h5>
                            </div>
                            <div class="card-body">
                                <p>This user does not have active premium access to revoke.</p>
                                <a href="index.php" class="btn btn-primary">Back to Pricing</a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-info-circle me-2"></i>
                                What Happens When You Revoke
                            </h5>
                        </div>
                        <div class="card-body">
                            <h6>Immediate Revocation:</h6>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-times text-danger me-2"></i>Access ends immediately</li>
                                <li><i class="fas fa-times text-danger me-2"></i>User loses premium features</li>
                                <li><i class="fas fa-times text-danger me-2"></i>No refunds processed</li>
                            </ul>
                            
                            <hr>
                            
                            <h6>End of Period Revocation:</h6>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-clock text-warning me-2"></i>Access continues until expiry</li>
                                <li><i class="fas fa-clock text-warning me-2"></i>No automatic renewal</li>
                                <li><i class="fas fa-clock text-warning me-2"></i>User notified of change</li>
                            </ul>
                            
                            <hr>
                            
                            <h6>Action Logging:</h6>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-check text-success me-2"></i>All actions are logged</li>
                                <li><i class="fas fa-check text-success me-2"></i>Reason is recorded</li>
                                <li><i class="fas fa-check text-success me-2"></i>Admin audit trail maintained</li>
                            </ul>
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
                } else {
                    // Additional confirmation for immediate revocation
                    var immediate = document.getElementById('immediate').checked;
                    if (immediate) {
                        if (!confirm('Are you sure you want to revoke premium access IMMEDIATELY? This action cannot be undone.')) {
                            event.preventDefault();
                            return false;
                        }
                    }
                }
                form.classList.add('was-validated');
            }, false);
        });
    }, false);
})();
</script>

<?php include '../admin_footer.php'; ?> 