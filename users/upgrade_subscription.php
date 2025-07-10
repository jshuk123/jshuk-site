<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../config/config.php';
require_once '../config/stripe_config.php';
require_once '../includes/subscription_functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$target_tier = isset($_GET['tier']) ? $_GET['tier'] : '';

// Get user's current subscription tier
$stmt = $pdo->prepare("SELECT subscription_tier FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$current_tier = $stmt->fetchColumn() ?: 'basic';

$valid_tiers = ['basic', 'premium', 'premium_plus'];
if (!in_array($target_tier, $valid_tiers) || $target_tier === $current_tier) {
    die('Invalid or same tier selected.');
}

// Get plan details from subscription_plans table
$plan_names = [
    'basic' => 'Basic',
    'premium' => 'Premium', 
    'premium_plus' => 'Premium Plus'
];

$plan_name = $plan_names[$target_tier];
$stmt = $pdo->prepare("SELECT * FROM subscription_plans WHERE name = ?");
$stmt->execute([$plan_name]);
$plan = $stmt->fetch();

if (!$plan) {
    die('Plan not found. Please contact support.');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_upgrade'])) {
    try {
        // Redirect to Stripe checkout
        $checkout_url = "/payment/checkout.php?plan_id=" . $plan['id'] . "&action=upgrade";
        header('Location: ' . $checkout_url);
        exit;
    } catch (Exception $e) {
        $error = 'Error processing upgrade: ' . $e->getMessage();
    }
}

$pageTitle = "Upgrade to " . getTierDisplayName($target_tier);
$page_css = "dashboard.css";
include '../includes/header_main.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="upgrade-confirmation-card">
                <div class="card border-0 shadow-lg">
                    <div class="card-header bg-primary text-white text-center py-4">
                        <h2 class="mb-0">
                            <i class="fas fa-arrow-up me-2"></i>
                            Upgrade to <?= getTierDisplayName($target_tier) ?>
                        </h2>
                    </div>
                    
                    <div class="card-body p-5">
                        <!-- Current vs New Plan Comparison -->
                        <div class="plan-comparison mb-4">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="current-plan">
                                        <h5 class="text-muted">Current Plan</h5>
                                        <h4><?= getTierDisplayName($current_tier) ?></h4>
                                        <div class="plan-features">
                                            <?php 
                                            $current_limits = getSubscriptionTierLimits($current_tier);
                                            $current_features = [
                                                'Images: ' . ($current_limits['images'] === null ? '∞' : $current_limits['images']),
                                                'Testimonials: ' . ($current_limits['unlimited_testimonials'] ? '∞' : $current_limits['testimonials']),
                                                $current_limits['homepage_visibility'] ? 'Homepage visibility' : 'Basic listing',
                                                $current_limits['priority_search'] ? 'Priority search' : 'Standard search',
                                                $current_limits['beta_features'] ? 'Beta features access' : 'Standard features'
                                            ];
                                            ?>
                                            <ul class="list-unstyled">
                                                <?php foreach ($current_features as $feature): ?>
                                                    <li><i class="fas fa-check text-muted me-2"></i><?= $feature ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="new-plan">
                                        <h5 class="text-primary">New Plan</h5>
                                        <h4 class="text-primary"><?= getTierDisplayName($target_tier) ?></h4>
                                        <div class="plan-features">
                                            <?php 
                                            $new_limits = getSubscriptionTierLimits($target_tier);
                                            $new_features = [
                                                'Images: ' . ($new_limits['images'] === null ? '∞' : $new_limits['images']),
                                                'Testimonials: ' . ($new_limits['unlimited_testimonials'] ? '∞' : $new_limits['testimonials']),
                                                $new_limits['homepage_visibility'] ? 'Homepage visibility' : 'Basic listing',
                                                $new_limits['priority_search'] ? 'Priority search' : 'Standard search',
                                                $new_limits['beta_features'] ? 'Beta features access' : 'Standard features',
                                                $new_limits['pinned_results'] ? 'Pinned in search results' : '',
                                                $new_limits['animated_effects'] ? 'Animated effects' : '',
                                                $new_limits['elite_ribbon'] ? 'Elite ribbon' : ''
                                            ];
                                            $new_features = array_filter($new_features); // Remove empty values
                                            ?>
                                            <ul class="list-unstyled">
                                                <?php foreach ($new_features as $feature): ?>
                                                    <li><i class="fas fa-check text-success me-2"></i><?= $feature ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Pricing Information -->
                        <div class="pricing-info text-center mb-4">
                            <div class="pricing-card">
                                <h3 class="price">£<?= number_format($plan['price'], 2) ?>/month</h3>
                                <?php if ($plan['annual_price']): ?>
                                    <p class="text-muted">
                                        Or £<?= number_format($plan['annual_price'], 2) ?>/year 
                                        <span class="badge bg-success">Save £<?= number_format(($plan['price'] * 12) - $plan['annual_price'], 2) ?></span>
                                    </p>
                                <?php endif; ?>
                                
                                <?php if ($plan['trial_period_days'] > 0): ?>
                                    <div class="trial-info">
                                        <i class="fas fa-gift text-warning"></i>
                                        <strong><?= $plan['trial_period_days'] ?>-day free trial</strong>
                                        <p class="text-muted small">No charge during trial period</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Upgrade Benefits -->
                        <div class="upgrade-benefits mb-4">
                            <h5>What you'll get:</h5>
                            <?php 
                            $benefits = getTierUpgradeBenefits($current_tier, $target_tier);
                            if (!empty($benefits)):
                            ?>
                                <ul class="benefits-list">
                                    <?php foreach ($benefits as $benefit): ?>
                                        <li><i class="fas fa-star text-warning me-2"></i><?= $benefit ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="action-buttons text-center">
                            <form method="POST" class="d-inline">
                                <button type="submit" name="confirm_upgrade" class="btn btn-primary btn-lg me-3">
                                    <i class="fas fa-credit-card me-2"></i>
                                    Upgrade Now
                                </button>
                            </form>
                            
                            <a href="dashboard.php" class="btn btn-outline-secondary btn-lg">
                                <i class="fas fa-arrow-left me-2"></i>
                                Back to Dashboard
                            </a>
                        </div>
                        
                        <!-- Security Notice -->
                        <div class="security-notice mt-4 text-center">
                            <p class="text-muted small">
                                <i class="fas fa-lock me-1"></i>
                                Secure payment processing by Stripe. Your payment information is encrypted and secure.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.upgrade-confirmation-card {
    max-width: 800px;
    margin: 0 auto;
}

.plan-comparison {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 2rem;
}

.current-plan, .new-plan {
    padding: 1rem;
}

.new-plan {
    border-left: 3px solid #007bff;
    background: rgba(0, 123, 255, 0.05);
    border-radius: 8px;
}

.pricing-card {
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    border-radius: 12px;
    padding: 2rem;
    border: 2px solid #dee2e6;
}

.price {
    font-size: 2.5rem;
    font-weight: bold;
    color: #007bff;
    margin-bottom: 0.5rem;
}

.trial-info {
    background: #fff3cd;
    border: 1px solid #ffeaa7;
    border-radius: 8px;
    padding: 1rem;
    margin-top: 1rem;
}

.benefits-list {
    list-style: none;
    padding: 0;
}

.benefits-list li {
    padding: 0.5rem 0;
    border-bottom: 1px solid #e9ecef;
}

.benefits-list li:last-child {
    border-bottom: none;
}

.action-buttons .btn {
    min-width: 150px;
}

.security-notice {
    border-top: 1px solid #e9ecef;
    padding-top: 1rem;
}
</style>

<?php include '../includes/footer_main.php'; ?> 