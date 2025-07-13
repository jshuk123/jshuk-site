<?php
require_once '../../config/config.php';
require_once '../admin_auth_check.php';

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
include '../admin_header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
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

<?php include '../admin_footer.php'; ?> 