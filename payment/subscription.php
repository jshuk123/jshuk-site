<?php
/**
 * JShuk Subscription Management
 * 
 * This file handles subscription plan display, current subscription status,
 * and advertising slot management for the JShuk business directory platform.
 * 
 * Features:
 * - Secure session management
 * - Current subscription status display
 * - Plan comparison and upgrade/downgrade options
 * - Advertising slot booking
 * - Trial period management
 * - WhatsApp and newsletter feature integration
 * 
 * @author JShuk Development Team
 * @version 2.0
 */

// Initialize session securely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load required configurations
require_once '../config/config.php';
require_once '../config/stripe_config.php';

// Security: Redirect if not authenticated
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header('Location: /jshuk/auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

$user_id = (int)$_SESSION['user_id'];

// Verify user exists and is active
$stmt = $pdo->prepare("
    SELECT id, email, first_name, last_name, status, created_at 
    FROM users 
    WHERE id = ? AND status = 'active'
");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    // Invalid or inactive user session
    session_destroy();
    header('Location: /jshuk/auth/login.php?error=invalid_session');
    exit();
}

/**
 * Get current subscription with enhanced error handling
 */
function getCurrentSubscription($pdo, $user_id) {
    try {
        // Check if required tables exist
        $stmt = $pdo->prepare("SHOW TABLES LIKE 'user_subscriptions'");
        $stmt->execute();
        if (!$stmt->fetch()) {
            // Table doesn't exist, return null
            return null;
        }
        
        $stmt = $pdo->prepare("SHOW TABLES LIKE 'subscription_plans'");
        $stmt->execute();
        if (!$stmt->fetch()) {
            // Table doesn't exist, return null
            return null;
        }
        
        $stmt = $pdo->prepare("
            SELECT 
                s.*,
                p.name as plan_name, 
                p.price, 
                p.annual_price,
                p.description, 
                p.features, 
                p.whatsapp_features,
                p.newsletter_features,
                p.trial_period_days
            FROM user_subscriptions s
            JOIN subscription_plans p ON s.plan_id = p.id
            WHERE s.user_id = ? 
            AND s.status IN ('active', 'trialing', 'past_due')
            ORDER BY s.created_at DESC 
            LIMIT 1
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        // If there's any error, return null
        return null;
    }
}

/**
 * Calculate subscription status and days remaining
 */
function calculateSubscriptionStatus($subscription) {
    if (!$subscription) {
        return [
            'has_subscription' => false,
            'status' => 'none',
            'days_left' => 0,
            'is_expired' => false,
            'end_date' => null,
            'status_message' => 'No active subscription'
        ];
    }

    $now = new DateTime();
    $end_date = null;
    $days_left = 0;
    $is_expired = false;
    $status_message = '';

    if ($subscription['status'] === 'trialing' && $subscription['trial_end']) {
        $end_date = new DateTime($subscription['trial_end']);
        $days_left = max(0, $now->diff($end_date)->days);
        $status_message = "Trial ends in {$days_left} days";
        
        if ($end_date < $now) {
            $is_expired = true;
            $status_message = 'Trial period expired';
        }
    } elseif ($subscription['current_period_end']) {
        $end_date = new DateTime($subscription['current_period_end']);
        $days_left = max(0, $now->diff($end_date)->days);
        
        if ($end_date < $now && $subscription['status'] === 'active') {
            $is_expired = true;
            $status_message = 'Subscription expired';
        } else {
            $status_message = $days_left > 0 
                ? "Renews in {$days_left} days" 
                : "Renews today";
        }
    }

    return [
        'has_subscription' => true,
        'status' => $subscription['status'],
        'days_left' => $days_left,
        'is_expired' => $is_expired,
        'end_date' => $end_date,
        'status_message' => $status_message
    ];
}

/**
 * Get all available subscription plans
 */
function getActiveSubscriptionPlans($pdo) {
    try {
        // Check if subscription_plans table exists
        $stmt = $pdo->prepare("SHOW TABLES LIKE 'subscription_plans'");
        $stmt->execute();
        if (!$stmt->fetch()) {
            // Table doesn't exist, return empty array
            return [];
        }
        
        $stmt = $pdo->prepare("
            SELECT * FROM subscription_plans 
            ORDER BY price ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        // If there's any error, return empty array
        return [];
    }
}

/**
 * Get available advertising slots
 */
function getAdvertisingSlots($pdo) {
    try {
        // Check if advertising_slots table exists
        $stmt = $pdo->prepare("SHOW TABLES LIKE 'advertising_slots'");
        $stmt->execute();
        if (!$stmt->fetch()) {
            // Table doesn't exist, return empty array
            return [];
        }
        
        $stmt = $pdo->prepare("
            SELECT * FROM advertising_slots 
            WHERE current_slots < max_slots 
            ORDER BY monthly_price ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        // If there's any error, return empty array
        return [];
    }
}

/**
 * Format currency with proper locale
 */
function formatSubscriptionCurrency($amount, $currency = 'GBP') {
    return '£' . number_format($amount, 2);
}

/**
 * Calculate annual savings
 */
function calculateAnnualSavings($monthly_price, $annual_price) {
    return ($monthly_price * 12) - $annual_price;
}

// Get current subscription and calculate status
$current_subscription = getCurrentSubscription($pdo, $user_id);
$subscription_status = calculateSubscriptionStatus($current_subscription);

// Update expired subscription status in database
if ($subscription_status['is_expired'] && $current_subscription) {
    $stmt = $pdo->prepare("
        UPDATE user_subscriptions 
        SET status = 'inactive', updated_at = NOW() 
        WHERE id = ?
    ");
    $stmt->execute([$current_subscription['id']]);
    $current_subscription['status'] = 'inactive';
    $subscription_status['status'] = 'inactive';
}

// Get available plans and advertising slots
$plans = getActiveSubscriptionPlans($pdo);
$ad_slots = getAdvertisingSlots($pdo);

// Get user's subscription limits and features
$limits = getUserSubscriptionLimits($user_id);
$whatsapp_features = getUserWhatsAppFeatures($user_id);
$newsletter_features = getUserNewsletterFeatures($user_id);

$pageTitle = "Subscription";
$page_css = "subscription.css";
include '../includes/header_main.php';
?>

<!-- Hero Section -->
<div class="hero-section bg-gradient-primary text-white py-5 mb-5">
    <div class="container">
        <div class="row justify-content-center text-center">
            <div class="col-lg-8">
                <h1 class="display-4 fw-bold mb-3">
                    <i class="fas fa-crown me-3"></i>
                    Choose Your Elite Plan
                </h1>
                <p class="lead mb-0">
                    Unlock premium features and boost your business visibility with our exclusive subscription tiers
                </p>
            </div>
        </div>
    </div>
</div>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12">
            
            <!-- Current Subscription Status -->
            <?php if ($current_subscription): ?>
            <div class="current-subscription-card mb-5">
                <div class="card border-0 shadow-lg">
                    <div class="card-header bg-gradient-primary text-white py-4">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h4 class="mb-1">
                                    <i class="fas fa-star me-2"></i>
                                    Current Plan: <?php echo htmlspecialchars($current_subscription['plan_name']); ?>
                                </h4>
                                <p class="mb-0 opacity-75">
                                    <?php echo htmlspecialchars($subscription_status['status_message']); ?>
                                </p>
                            </div>
                            <div class="text-end">
                                <div class="h5 mb-1">
                                    <?php echo formatSubscriptionCurrency($current_subscription['price']); ?>
                                    <small class="opacity-75">/month</small>
                                </div>
                                <?php if ($current_subscription['annual_price']): ?>
                                <div class="small opacity-75">
                                    Annual: <?php echo formatSubscriptionCurrency($current_subscription['annual_price']); ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-body p-4">
                        <?php if ($subscription_status['is_expired']): ?>
                            <div class="alert alert-danger border-0 mb-0">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-exclamation-triangle fa-2x me-3"></i>
                                    <div>
                                        <h5 class="mb-1">Subscription Expired</h5>
                                        <p class="mb-0">
                                            Your subscription expired on 
                                            <strong><?php echo $subscription_status['end_date'] ? $subscription_status['end_date']->format('F j, Y') : 'Unknown date'; ?></strong>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        <?php elseif ($current_subscription['status'] === 'trialing'): ?>
                            <div class="alert alert-info border-0 mb-0">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-clock fa-2x me-3"></i>
                                    <div>
                                        <h5 class="mb-1">Trial Period Active</h5>
                                        <p class="mb-1">
                                            Trial ends in <strong><?php echo $subscription_status['days_left']; ?> days</strong>
                                            (<?php echo $subscription_status['end_date'] ? $subscription_status['end_date']->format('F j, Y') : 'Unknown date'; ?>)
                                        </p>
                                        <p class="mb-0 small opacity-75">
                                            After trial ends, you'll be charged <?php echo formatSubscriptionCurrency($current_subscription['price']); ?>/month
                                        </p>
                                    </div>
                                </div>
                            </div>
                        <?php elseif ($current_subscription['status'] === 'active'): ?>
                            <div class="alert alert-success border-0 mb-0">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-check-circle fa-2x me-3"></i>
                                    <div>
                                        <h5 class="mb-1">Active Subscription</h5>
                                        <p class="mb-0">
                                            <?php if ($subscription_status['days_left'] > 0): ?>
                                                Your subscription renews in <strong><?php echo $subscription_status['days_left']; ?> days</strong>
                                                (<?php echo $subscription_status['end_date'] ? $subscription_status['end_date']->format('F j, Y') : 'Unknown date'; ?>)
                                            <?php else: ?>
                                                Your subscription renews <strong>today</strong>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        <?php elseif ($current_subscription['status'] === 'past_due'): ?>
                            <div class="alert alert-warning border-0 mb-0">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-exclamation-triangle fa-2x me-3"></i>
                                    <div>
                                        <h5 class="mb-1">Payment Past Due</h5>
                                        <p class="mb-0">Your last payment was unsuccessful. Please update your payment method to continue service.</p>
                                    </div>
                                </div>
                            </div>
                        <?php elseif ($current_subscription['status'] === 'cancelled'): ?>
                            <div class="alert alert-secondary border-0 mb-0">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-ban fa-2x me-3"></i>
                                    <div>
                                        <h5 class="mb-1">Subscription Cancelled</h5>
                                        <p class="mb-0">
                                            Access available until 
                                            <strong><?php echo $subscription_status['end_date'] ? $subscription_status['end_date']->format('F j, Y') : 'Unknown date'; ?></strong>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Success/Error Messages -->
            <?php if (isset($_GET['success']) && $_GET['success'] === 'subscription_created' && $current_subscription): ?>
                <div class="alert alert-success border-0 text-center mb-4">
                    <div class="d-flex align-items-center justify-content-center">
                        <i class="fas fa-check-circle fa-2x me-3"></i>
                        <div>
                            <h5 class="mb-1">Success!</h5>
                            <p class="mb-0">
                                <?php echo $current_subscription['status'] === 'trialing' 
                                    ? 'Your trial period has been activated successfully.' 
                                    : 'Your subscription has been activated successfully.'; ?>
                            </p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['error']) && (!isset($_GET['message']) || strpos($_GET['message'], 'Duplicate entry') === false)): ?>
                <?php
                $error_messages = [
                    'payment_failed' => 'The payment process could not be completed. Please try again.',
                    'stripe_error' => 'There was an issue with the payment service. Please contact support.',
                    'invalid_plan' => 'The selected subscription plan is not valid.',
                    'insufficient_funds' => 'Insufficient funds. Please check your payment method.',
                    'card_declined' => 'Your card was declined. Please try a different payment method.',
                    'expired_card' => 'Your card has expired. Please update your payment information.'
                ];
                $error_message = $error_messages[$_GET['error']] ?? 'An unexpected error occurred. Please try again.';
                if (isset($_GET['message'])) {
                    $error_message .= ' Details: ' . htmlspecialchars($_GET['message']);
                }
                ?>
                <div class="alert alert-danger border-0 text-center mb-4">
                    <div class="d-flex align-items-center justify-content-center">
                        <i class="fas fa-exclamation-triangle fa-2x me-3"></i>
                        <div>
                            <h5 class="mb-1">Payment Error</h5>
                            <p class="mb-0"><?php echo $error_message; ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Subscription Plans -->
            <div class="subscription-plans mb-5">
                <h2 class="text-center mb-5">
                    <i class="fas fa-gem me-2"></i>
                    Choose Your Plan
                </h2>
                
                <div class="row row-cols-1 row-cols-lg-3 g-4">
                    <?php foreach ($plans as $plan): ?>
                    <div class="col">
                        <div class="plan-card card h-100 border-0 shadow-lg <?php echo $plan['name'] === 'Premium' ? 'premium-plan' : ''; ?>">
                            
                            <!-- Plan Header -->
                            <div class="card-header border-0 p-4 <?php echo $plan['name'] === 'Premium' ? 'bg-gradient-primary text-white' : 'bg-light'; ?>">
                                <?php if ($plan['name'] === 'Premium'): ?>
                                    <div class="premium-badge">
                                        <i class="fas fa-crown"></i>
                                        Most Popular
                                    </div>
                                <?php endif; ?>
                                
                                <h3 class="card-title text-center mb-3">
                                    <?php echo htmlspecialchars($plan['name']); ?>
                                </h3>
                                
                                <div class="text-center">
                                    <div class="price-display">
                                        <span class="currency">£</span>
                                        <span class="amount"><?php echo number_format($plan['price'], 0); ?></span>
                                        <span class="period">/month</span>
                                    </div>
                                    
                                    <?php if ($plan['annual_price'] > 0): ?>
                                        <?php $annual_savings = calculateAnnualSavings($plan['price'], $plan['annual_price']); ?>
                                        <div class="annual-pricing mt-2">
                                            <small class="text-muted">
                                                Annual: <?php echo formatSubscriptionCurrency($plan['annual_price']); ?>
                                                <?php if ($annual_savings > 0): ?>
                                                    <br>
                                                    <span class="badge bg-success">
                                                        Save <?php echo formatSubscriptionCurrency($annual_savings); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Plan Body -->
                            <div class="card-body p-4">
                                
                                <!-- Trial Period -->
                                <?php if ($plan['trial_period_days'] > 0): ?>
                                    <div class="trial-badge mb-4">
                                        <i class="fas fa-gift me-2"></i>
                                        <?php echo floor($plan['trial_period_days'] / 30); ?> months free trial
                                    </div>
                                <?php endif; ?>

                                <!-- Features List -->
                                <ul class="features-list list-unstyled mb-4">
                                    <!-- Images -->
                                    <li class="feature-item">
                                        <i class="fas fa-image text-success me-2"></i>
                                        <span>
                                            <?php echo $plan['image_limit'] === null ? 'Unlimited' : (int)$plan['image_limit']; ?> 
                                            <?php echo $plan['image_limit'] === 1 ? 'image' : 'images'; ?>
                                        </span>
                                    </li>
                                    
                                    <!-- Testimonials -->
                                    <li class="feature-item">
                                        <i class="fas fa-comment text-success me-2"></i>
                                        <span>
                                            <?php echo $plan['testimonial_limit'] === null ? 'Unlimited' : (int)$plan['testimonial_limit']; ?> 
                                            testimonials
                                        </span>
                                    </li>
                                    
                                    <!-- Plan Features -->
                                    <?php 
                                    $features = json_decode($plan['features'] ?? '[]', true);
                                    if (is_array($features)):
                                        foreach ($features as $feature): 
                                    ?>
                                        <li class="feature-item">
                                            <i class="fas fa-check text-success me-2"></i>
                                            <span><?php echo htmlspecialchars($feature); ?></span>
                                        </li>
                                    <?php 
                                        endforeach;
                                    endif;
                                    
                                    // WhatsApp Features
                                    $whatsapp = json_decode($plan['whatsapp_features'] ?? '{}', true);
                                    if ($whatsapp && !empty($whatsapp['status_feature'])):
                                    ?>
                                        <li class="feature-item">
                                            <i class="fab fa-whatsapp text-success me-2"></i>
                                            <span>Featured <?php echo htmlspecialchars($whatsapp['status_feature']); ?> on WhatsApp status</span>
                                        </li>
                                    <?php endif; 
                                    
                                    // Newsletter Features
                                    $newsletter = json_decode($plan['newsletter_features'] ?? '{}', true);
                                    if ($newsletter && isset($newsletter['included']) && $newsletter['included']):
                                    ?>
                                        <li class="feature-item">
                                            <i class="fas fa-envelope text-success me-2"></i>
                                            <span>
                                                <?php echo $newsletter['priority'] ? 'Priority placement in' : 'Included in'; ?> 
                                                monthly newsletter
                                            </span>
                                        </li>
                                    <?php endif; ?>
                                </ul>

                                <!-- Action Button -->
                                <div class="plan-action">
                                    <?php if ($current_subscription): ?>
                                        <?php if ($current_subscription['plan_id'] == $plan['id']): ?>
                                            <button class="btn btn-lg w-100 btn-outline-primary" disabled>
                                                <?php if ($current_subscription['status'] === 'active'): ?>
                                                    <i class="fas fa-check-circle me-2"></i>Current Plan
                                                <?php elseif ($current_subscription['status'] === 'trialing'): ?>
                                                    <i class="fas fa-clock me-2"></i>Trial Active
                                                <?php elseif ($current_subscription['status'] === 'cancelled'): ?>
                                                    <i class="fas fa-times-circle me-2"></i>Cancelled
                                                <?php elseif ($current_subscription['status'] === 'past_due'): ?>
                                                    <i class="fas fa-exclamation-circle me-2"></i>Payment Due
                                                <?php else: ?>
                                                    <i class="fas fa-times-circle me-2"></i>Inactive
                                                <?php endif; ?>
                                            </button>
                                        <?php elseif ($current_subscription['price'] < $plan['price']): ?>
                                            <a href="checkout.php?plan_id=<?php echo $plan['id']; ?>&action=upgrade" 
                                               class="btn btn-lg w-100 btn-primary">
                                                <i class="fas fa-arrow-up me-2"></i>Upgrade
                                            </a>
                                        <?php else: ?>
                                            <a href="checkout.php?plan_id=<?php echo $plan['id']; ?>&action=downgrade" 
                                               class="btn btn-lg w-100 btn-outline-secondary">
                                                <i class="fas fa-arrow-down me-2"></i>Downgrade
                                            </a>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <a href="checkout.php?plan_id=<?php echo $plan['id']; ?>" 
                                           class="btn btn-lg w-100 <?php echo $plan['name'] === 'Premium' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                            Get Started
                                            <?php if ($plan['trial_period_days'] > 0): ?>
                                                <small class="d-block mt-1">
                                                    <i class="fas fa-gift me-1"></i> 
                                                    <?php echo floor($plan['trial_period_days'] / 30); ?> months free trial
                                                </small>
                                            <?php endif; ?>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Advertising Slots -->
            <?php if (!empty($ad_slots)): ?>
            <div class="advertising-section mt-5">
                <h2 class="text-center mb-5">
                    <i class="fas fa-bullhorn me-2"></i>
                    Premium Advertising Opportunities
                </h2>
                <p class="text-center text-muted mb-5">Boost your visibility with our exclusive advertising slots</p>

                <!-- Banner Slots -->
                <?php 
                $banner_slots = array_filter($ad_slots, function($slot) {
                    return strpos($slot['name'], 'Banner') !== false;
                });
                if (!empty($banner_slots)):
                ?>
                <div class="banner-slots mb-5">
                    <h4 class="mb-4">
                        <i class="fas fa-image me-2"></i>
                        Homepage Banner Slots
                    </h4>
                    <div class="row g-4">
                        <?php 
                        usort($banner_slots, function($a, $b) {
                            return $b['monthly_price'] - $a['monthly_price'];
                        });
                        foreach ($banner_slots as $slot): 
                        ?>
                        <div class="col-md-4">
                            <div class="ad-slot-card card h-100 border-0 shadow <?php echo $slot['name'] === 'Top Homepage Banner' ? 'premium-slot' : ''; ?>">
                                <?php if ($slot['name'] === 'Top Homepage Banner'): ?>
                                <div class="card-header bg-gradient-primary text-white py-3">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <span class="badge bg-white text-primary">
                                            <i class="fas fa-star me-1"></i>Premium Spot
                                        </span>
                                        <small>Most Visible</small>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <div class="card-body p-4">
                                    <h5 class="card-title mb-3"><?php echo htmlspecialchars($slot['name']); ?></h5>
                                    <p class="card-text text-muted small mb-4">
                                        <?php echo htmlspecialchars($slot['description']); ?>
                                    </p>
                                    
                                    <div class="pricing-options mb-4">
                                        <div class="pricing-option">
                                            <div class="price-label">Monthly</div>
                                            <div class="price-value"><?php echo formatSubscriptionCurrency($slot['monthly_price']); ?></div>
                                        </div>
                                        <div class="pricing-option highlight">
                                            <div class="price-label">
                                                Annual 
                                                <span class="badge bg-success ms-1">
                                                    Save <?php echo formatSubscriptionCurrency(calculateAnnualSavings($slot['monthly_price'], $slot['annual_price'])); ?>
                                                </span>
                                            </div>
                                            <div class="price-value"><?php echo formatSubscriptionCurrency($slot['annual_price']); ?></div>
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="availability">
                                            <?php 
                                            $available = $slot['max_slots'] - $slot['current_slots'];
                                            ?>
                                            <i class="fas fa-clock me-1"></i>
                                            <span class="text-muted small">
                                                <?php echo $available; ?> slot<?php echo $available !== 1 ? 's' : ''; ?> left
                                            </span>
                                        </div>
                                        <a href="advertising_checkout.php?slot=<?php echo $slot['id']; ?>" 
                                           class="btn <?php echo $slot['name'] === 'Top Homepage Banner' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                            Book Now
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Other Advertising Options -->
                <?php 
                $other_slots = array_filter($ad_slots, function($slot) {
                    return strpos($slot['name'], 'Banner') === false;
                });
                if (!empty($other_slots)):
                ?>
                <div class="other-ad-slots">
                    <h4 class="mb-4">
                        <i class="fas fa-ad me-2"></i>
                        Additional Advertising Options
                    </h4>
                    <div class="row g-4">
                        <?php foreach ($other_slots as $slot): ?>
                        <div class="col-md-6">
                            <div class="ad-slot-card card h-100 border-0 shadow">
                                <div class="card-body p-4">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div class="flex-grow-1">
                                            <h5 class="card-title mb-2"><?php echo htmlspecialchars($slot['name']); ?></h5>
                                            <p class="card-text text-muted small mb-0">
                                                <?php echo htmlspecialchars($slot['description']); ?>
                                            </p>
                                        </div>
                                        <span class="badge bg-primary-subtle text-primary">
                                            <?php 
                                            $available = $slot['max_slots'] - $slot['current_slots'];
                                            echo $available . ' available';
                                            ?>
                                        </span>
                                    </div>
                                    
                                    <div class="pricing-info mb-3">
                                        <div class="row">
                                            <div class="col-6">
                                                <div class="pricing-item">
                                                    <div class="label text-muted small">Monthly</div>
                                                    <div class="value h5 mb-0"><?php echo formatSubscriptionCurrency($slot['monthly_price']); ?></div>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="pricing-item highlight">
                                                    <div class="label text-muted small">Annual</div>
                                                    <div class="value h5 mb-0 text-success">
                                                        <?php echo formatSubscriptionCurrency($slot['annual_price']); ?>
                                                        <span class="badge bg-success-subtle text-success ms-1">
                                                            Save <?php echo formatSubscriptionCurrency(calculateAnnualSavings($slot['monthly_price'], $slot['annual_price'])); ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <a href="advertising_checkout.php?slot=<?php echo $slot['id']; ?>" 
                                       class="btn btn-outline-primary w-100">
                                        Book This Slot
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Benefits Section -->
            <div class="benefits-section mt-5">
                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        <div class="card border-0 shadow-lg">
                            <div class="card-header bg-gradient-primary text-white py-4">
                                <h4 class="mb-0 text-center">
                                    <i class="fas fa-star me-2"></i>
                                    Why Choose a Premium Plan?
                                </h4>
                            </div>
                            <div class="card-body p-4">
                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <div class="benefit-item">
                                            <i class="fas fa-search text-primary me-3"></i>
                                            <div>
                                                <h6>Enhanced Visibility</h6>
                                                <p class="text-muted small mb-0">Priority placement in search results and category pages</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="benefit-item">
                                            <i class="fas fa-handshake text-primary me-3"></i>
                                            <div>
                                                <h6>B2B Networking</h6>
                                                <p class="text-muted small mb-0">Access to exclusive business networking opportunities</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="benefit-item">
                                            <i class="fas fa-envelope text-primary me-3"></i>
                                            <div>
                                                <h6>Newsletter Features</h6>
                                                <p class="text-muted small mb-0">Featured in our monthly community newsletter</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="benefit-item">
                                            <i class="fab fa-whatsapp text-primary me-3"></i>
                                            <div>
                                                <h6>WhatsApp Integration</h6>
                                                <p class="text-muted small mb-0">Enhanced customer engagement through WhatsApp</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="benefit-item">
                                            <i class="fas fa-credit-card text-primary me-3"></i>
                                            <div>
                                                <h6>Flexible Billing</h6>
                                                <p class="text-muted small mb-0">Choose between monthly or annual billing options</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="benefit-item">
                                            <i class="fas fa-headset text-primary me-3"></i>
                                            <div>
                                                <h6>Priority Support</h6>
                                                <p class="text-muted small mb-0">Dedicated customer support for premium members</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Enhanced Styling -->
<style>
/* Hero Section */
.hero-section {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    position: relative;
    overflow: hidden;
}

.hero-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/><circle cx="50" cy="10" r="0.5" fill="white" opacity="0.1"/><circle cx="10" cy="60" r="0.5" fill="white" opacity="0.1"/><circle cx="90" cy="40" r="0.5" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
    opacity: 0.3;
}

/* Current Subscription Card */
.current-subscription-card .card {
    border-radius: 1rem;
    overflow: hidden;
}

.bg-gradient-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

/* Plan Cards */
.plan-card {
    border-radius: 1rem;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.plan-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 1rem 3rem rgba(0,0,0,0.2) !important;
}

.premium-plan {
    border: 2px solid #667eea;
    transform: scale(1.05);
}

.premium-plan:hover {
    transform: scale(1.05) translateY(-10px);
}

.premium-badge {
    position: absolute;
    top: 1rem;
    right: 1rem;
    background: rgba(255,255,255,0.2);
    padding: 0.5rem 1rem;
    border-radius: 2rem;
    font-size: 0.875rem;
    font-weight: 600;
}

.price-display {
    font-size: 3rem;
    font-weight: 700;
    line-height: 1;
}

.price-display .currency {
    font-size: 1.5rem;
    vertical-align: top;
}

.price-display .amount {
    font-size: 3.5rem;
}

.price-display .period {
    font-size: 1rem;
    font-weight: 400;
    opacity: 0.8;
}

.trial-badge {
    background: linear-gradient(135deg, #28a745, #20c997);
    color: white;
    padding: 0.75rem 1rem;
    border-radius: 0.5rem;
    text-align: center;
    font-weight: 600;
}

.features-list {
    margin: 0;
    padding: 0;
}

.feature-item {
    padding: 0.75rem 0;
    border-bottom: 1px solid #f8f9fa;
    display: flex;
    align-items: center;
}

.feature-item:last-child {
    border-bottom: none;
}

.feature-item i {
    width: 1.5rem;
    text-align: center;
}

/* Advertising Slots */
.ad-slot-card {
    border-radius: 1rem;
    transition: all 0.3s ease;
}

.ad-slot-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.5rem 1.5rem rgba(0,0,0,0.15) !important;
}

.premium-slot {
    border: 2px solid #667eea;
}

.pricing-options {
    display: grid;
    gap: 0.75rem;
}

.pricing-option {
    padding: 1rem;
    border-radius: 0.5rem;
    background-color: #f8f9fa;
    text-align: center;
}

.pricing-option.highlight {
    background: linear-gradient(135deg, #e8f5e9, #c8e6c9);
    border: 1px solid #4caf50;
}

.price-label {
    font-size: 0.875rem;
    color: #6c757d;
    margin-bottom: 0.5rem;
}

.price-value {
    font-size: 1.25rem;
    font-weight: 600;
    color: #212529;
}

.pricing-option.highlight .price-value {
    color: #198754;
}

.pricing-info .pricing-item {
    text-align: center;
    padding: 1rem;
    border-radius: 0.5rem;
    background-color: #f8f9fa;
}

.pricing-info .pricing-item.highlight {
    background: linear-gradient(135deg, #e8f5e9, #c8e6c9);
}

/* Benefits Section */
.benefit-item {
    display: flex;
    align-items: flex-start;
    padding: 1rem;
    border-radius: 0.5rem;
    background-color: #f8f9fa;
    transition: all 0.3s ease;
}

.benefit-item:hover {
    background-color: #e9ecef;
    transform: translateX(5px);
}

.benefit-item i {
    font-size: 1.5rem;
    margin-top: 0.25rem;
}

/* Badges */
.badge.bg-primary-subtle {
    background-color: #cfe2ff !important;
    color: #0d6efd !important;
}

.badge.bg-success-subtle {
    background-color: #d1e7dd !important;
    color: #198754 !important;
}

/* Responsive Design */
@media (max-width: 768px) {
    .premium-plan {
        transform: none;
    }
    
    .premium-plan:hover {
        transform: translateY(-10px);
    }
    
    .price-display {
        font-size: 2.5rem;
    }
    
    .price-display .amount {
        font-size: 3rem;
    }
    
    .hero-section {
        padding: 3rem 0;
    }
}

/* Loading States */
.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

/* Animations */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.plan-card, .ad-slot-card {
    animation: fadeInUp 0.6s ease-out;
}

.plan-card:nth-child(2) {
    animation-delay: 0.1s;
}

.plan-card:nth-child(3) {
    animation-delay: 0.2s;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Logic for toggling monthly/annual pricing
    const priceToggle = document.getElementById('priceToggle');
    if (priceToggle) {
        priceToggle.addEventListener('change', function() {
            const isAnnual = this.checked;
            document.querySelectorAll('.plan-card').forEach(card => {
                const monthlyPrice = card.querySelector('.price-monthly');
                const annualPrice = card.querySelector('.price-annual');
                if (isAnnual) {
                    monthlyPrice.style.display = 'none';
                    annualPrice.style.display = 'block';
                } else {
                    monthlyPrice.style.display = 'block';
                    annualPrice.style.display = 'none';
                }
            });
        });
    }

    // Tooltip initialization
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Handle plan selection form submission
    const planForms = document.querySelectorAll('.plan-select-form');
    planForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const planId = this.querySelector('input[name="plan_id"]').value;
            const action = this.querySelector('input[name="action"]').value;
            window.location.href = `/jshuk/payment/checkout.php?plan_id=${planId}&action=${action}`;
        });
    });

    // Handle ad slot booking form submission
    const adForms = document.querySelectorAll('.ad-select-form');
    adForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const slotId = this.querySelector('input[name="slot_id"]').value;
            window.location.href = `/jshuk/payment/advertising_checkout.php?slot=${slotId}`;
        });
    });
});
</script>

<?php include '../includes/footer_main.php'; ?>