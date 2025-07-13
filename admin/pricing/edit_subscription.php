<?php
require_once '../../config/config.php';
require_once '../admin_auth_check.php';

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
include '../admin_header.php';
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

<?php include '../admin_footer.php'; ?> 