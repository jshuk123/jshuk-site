<?php
/**
 * JShuk Subscription Checkout
 * 
 * This file handles the Stripe checkout process for subscription plans.
 * Features secure payment processing, trial management, and plan upgrades/downgrades.
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
    header('Location: ' . BASE_PATH . 'auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

$user_id = (int)$_SESSION['user_id'];

// Validate and sanitize plan_id
$plan_id = filter_input(INPUT_GET, 'plan_id', FILTER_VALIDATE_INT);
$action = htmlspecialchars($_GET['action'] ?? 'new', ENT_QUOTES, 'UTF-8');

if (!$plan_id) {
    header('Location: subscription.php?error=invalid_plan');
    exit();
}

/**
 * Get plan details with validation
 */
function getPlanDetails($pdo, $plan_id) {
    try {
        // Check if subscription_plans table exists
        $stmt = $pdo->prepare("SHOW TABLES LIKE 'subscription_plans'");
        $stmt->execute();
        if (!$stmt->fetch()) {
            return null;
        }
        
        $stmt = $pdo->prepare("
            SELECT * FROM subscription_plans 
            WHERE id = ?
        ");
        $stmt->execute([$plan_id]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        return null;
    }
}

/**
 * Get user details with validation
 */
function getUserDetails($pdo, $user_id) {
    try {
        // Check if users table exists
        $stmt = $pdo->prepare("SHOW TABLES LIKE 'users'");
        $stmt->execute();
        if (!$stmt->fetch()) {
            return null;
        }
        
        // Check if stripe_customer_id column exists
        $stmt = $pdo->prepare("SHOW COLUMNS FROM users LIKE 'stripe_customer_id'");
        $stmt->execute();
        $has_stripe_column = $stmt->fetch();
        
        if ($has_stripe_column) {
            $stmt = $pdo->prepare("
                SELECT id, email, first_name, last_name, stripe_customer_id, status 
                FROM users 
                WHERE id = ? AND status = 'active'
            ");
        } else {
            $stmt = $pdo->prepare("
                SELECT id, email, first_name, last_name, status 
                FROM users 
                WHERE id = ? AND status = 'active'
            ");
        }
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        // Add stripe_customer_id as null if column doesn't exist
        if ($user && !$has_stripe_column) {
            $user['stripe_customer_id'] = null;
        }
        
        return $user;
    } catch (PDOException $e) {
        return null;
    }
}

/**
 * Get current subscription details
 */
function getCurrentSubscription($pdo, $user_id) {
    try {
        // Check if required tables exist
        $stmt = $pdo->prepare("SHOW TABLES LIKE 'user_subscriptions'");
        $stmt->execute();
        if (!$stmt->fetch()) {
            return null;
        }
        
        $stmt = $pdo->prepare("SHOW TABLES LIKE 'subscription_plans'");
        $stmt->execute();
        if (!$stmt->fetch()) {
            return null;
        }
        
        $stmt = $pdo->prepare("
            SELECT s.*, p.name as plan_name, p.price, p.trial_period_days
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
        return null;
    }
}

/**
 * Check trial eligibility for specific plan
 */
function checkTrialEligibility($pdo, $user_id, $plan_id) {
    try {
        // Check if user_subscriptions table exists
        $stmt = $pdo->prepare("SHOW TABLES LIKE 'user_subscriptions'");
        $stmt->execute();
        if (!$stmt->fetch()) {
            return [
                'has_used_trial_for_plan' => false,
                'has_active_trial' => false,
                'current_trial_plan_id' => null,
                'trial_end' => null
            ];
        }
        
        // Check if user has had a trial for this specific plan
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as plan_trial_count
            FROM user_subscriptions 
            WHERE user_id = ? 
            AND plan_id = ?
            AND trial_end IS NOT NULL
        ");
        $stmt->execute([$user_id, $plan_id]);
        $has_used_trial_for_plan = $stmt->fetch()['plan_trial_count'] > 0;

        // Check if user has an active trial on any plan
        $stmt = $pdo->prepare("
            SELECT 1 as has_active_trial,
                   plan_id as current_trial_plan_id,
                   trial_end
            FROM user_subscriptions 
            WHERE user_id = ? 
            AND trial_end IS NOT NULL 
            AND status = 'trialing'
            AND trial_end > NOW()
        ");
        $stmt->execute([$user_id]);
        $active_trial = $stmt->fetch();

        return [
            'has_used_trial_for_plan' => $has_used_trial_for_plan,
            'has_active_trial' => $active_trial ? true : false,
            'current_trial_plan_id' => $active_trial ? $active_trial['current_trial_plan_id'] : null,
            'trial_end' => $active_trial ? $active_trial['trial_end'] : null
        ];
    } catch (PDOException $e) {
        return [
            'has_used_trial_for_plan' => false,
            'has_active_trial' => false,
            'current_trial_plan_id' => null,
            'trial_end' => null
        ];
    }
}

/**
 * Calculate annual savings
 */
function calculateAnnualSavings($monthly_price, $annual_price) {
    return ($monthly_price * 12) - $annual_price;
}

/**
 * Format currency with proper locale
 */
function formatCheckoutCurrency($amount, $currency = 'GBP') {
    return '£' . number_format($amount, 2);
}

// Get plan and user details
$plan = getPlanDetails($pdo, $plan_id);
$user = getUserDetails($pdo, $user_id);

if (!$plan || !$user) {
    header('Location: subscription.php?error=invalid_plan');
    exit();
}

// Get current subscription and trial eligibility
$current_subscription = getCurrentSubscription($pdo, $user_id);
$trial_eligibility = checkTrialEligibility($pdo, $user_id, $plan_id);

// Determine plan type and pricing
$is_paid_plan = $plan['price'] > 0;
$is_upgrade = false;
$is_downgrade = false;

if ($current_subscription) {
    $current_plan_price = $current_subscription['price'];
    $new_plan_price = $plan['price'];
    $is_upgrade = $new_plan_price > $current_plan_price;
    $is_downgrade = $new_plan_price < $current_plan_price;
}

// Initialize Stripe
\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

try {
    // Create or retrieve Stripe customer
    if ($user['stripe_customer_id']) {
        $customer = \Stripe\Customer::retrieve($user['stripe_customer_id']);
    } else {
        $customer = \Stripe\Customer::create([
            'email' => $user['email'],
            'name' => $user['first_name'] . ' ' . $user['last_name'],
            'metadata' => [
                'user_id' => $user['id'],
                'created_at' => date('Y-m-d H:i:s')
            ]
        ]);

        // Update user with Stripe customer ID (only if column exists)
        try {
            $stmt = $pdo->prepare("SHOW COLUMNS FROM users LIKE 'stripe_customer_id'");
            $stmt->execute();
            if ($stmt->fetch()) {
                $stmt = $pdo->prepare("UPDATE users SET stripe_customer_id = ? WHERE id = ?");
                $stmt->execute([$customer->id, $user['id']]);
            }
        } catch (PDOException $e) {
            // Column doesn't exist, skip the update
            error_log("stripe_customer_id column doesn't exist, skipping update");
        }
    }

    // Build checkout parameters
    $checkout_params = [
        'customer' => $customer->id,
        'payment_method_types' => ['card'],
        'line_items' => [[
            'price' => $plan['stripe_price_id'],
            'quantity' => 1,
        ]],
        'mode' => 'subscription',
        'ui_mode' => 'embedded',
        'return_url' => 'https://' . $_SERVER['HTTP_HOST'] . BASE_PATH . 'payment/subscription_success.php?session_id={CHECKOUT_SESSION_ID}',
        'metadata' => [
            'plan_id' => $plan['id'],
            'user_id' => $user['id'],
            'action' => $action,
            'is_paid_plan' => $is_paid_plan ? 'true' : 'false',
            'should_have_trial' => ($is_paid_plan && !$trial_eligibility['has_used_trial_for_plan']) ? 'true' : 'false',
            'has_used_trial_for_plan' => $trial_eligibility['has_used_trial_for_plan'] ? 'true' : 'false'
        ],
        'subscription_data' => [
            'metadata' => [
                'plan_id' => $plan['id'],
                'user_id' => $user['id'],
                'action' => $action,
                'is_paid_plan' => $is_paid_plan ? 'true' : 'false',
                'should_have_trial' => ($is_paid_plan && !$trial_eligibility['has_used_trial_for_plan']) ? 'true' : 'false',
                'has_used_trial_for_plan' => $trial_eligibility['has_used_trial_for_plan'] ? 'true' : 'false'
            ]
        ]
    ];

    // Handle trial period logic
    if ($trial_eligibility['has_active_trial'] && $trial_eligibility['trial_end']) {
        // User is currently in trial period
        if ($plan_id != $trial_eligibility['current_trial_plan_id'] && !$trial_eligibility['has_used_trial_for_plan']) {
            // Switching to a new plan that hasn't had a trial - give them a new 90-day trial
            $checkout_params['subscription_data']['trial_period_days'] = 90;
            error_log("New plan without previous trial - giving 90 days trial for user {$user_id}, plan {$plan_id}");
        } else {
            // Maintain existing trial period
            $trial_end = strtotime($trial_eligibility['trial_end']);
            $checkout_params['subscription_data']['trial_end'] = $trial_end;
            error_log("Maintaining existing trial period until: " . date('Y-m-d H:i:s', $trial_end));
        }
    } elseif ($is_paid_plan && !$trial_eligibility['has_used_trial_for_plan']) {
        // New paid plan subscription with 3-month trial (if never had trial for this plan)
        $checkout_params['subscription_data']['trial_period_days'] = 90;
        error_log("Setting new 90-day trial period for user {$user_id}, plan {$plan_id}");
    }

    // Create Stripe Checkout Session
    $session = \Stripe\Checkout\Session::create($checkout_params);

    // Store selected plan in session for success page
    $_SESSION['selected_plan'] = $plan['id'];
    $_SESSION['checkout_session_id'] = $session->id;

    $pageTitle = "Subscription Checkout";
    $page_css = "checkout.css";
    include '../includes/header_main.php';
?>

<!-- Hero Section -->
<div class="hero-section bg-gradient-primary text-white py-5 mb-5">
    <div class="container">
        <div class="row justify-content-center text-center">
            <div class="col-lg-8">
                <h1 class="display-5 fw-bold mb-3">
                    <i class="fas fa-credit-card me-3"></i>
                    Complete Your Subscription
                </h1>
                <p class="lead mb-0">
                    Secure payment processing powered by Stripe
                </p>
            </div>
        </div>
    </div>
</div>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="checkout-container">
                <div class="row g-5">
                    <!-- Plan Summary -->
                    <div class="col-lg-5">
                        <div class="plan-summary-card card border-0 shadow-lg h-100">
                            <div class="card-header bg-gradient-primary text-white py-4">
                                <h4 class="mb-0 text-center">
                                    <i class="fas fa-gem me-2"></i>
                                    <?php echo htmlspecialchars($plan['name']); ?> Plan
                                </h4>
                            </div>
                            <div class="card-body p-4">
                                
                                <!-- Pricing Display -->
                                <div class="pricing-display text-center mb-4">
                                    <?php if ($trial_eligibility['has_active_trial'] && $trial_eligibility['trial_end']): ?>
                                        <div class="trial-info mb-3">
                                            <div class="alert alert-info border-0">
                                                <i class="fas fa-clock me-2"></i>
                                                <strong>Trial Period Active</strong>
                                                <div class="small mt-1">
                                                    Until <?php echo date('F j, Y', strtotime($trial_eligibility['trial_end'])); ?>
                                                </div>
                                            </div>
                                            <div class="h4 mb-0">
                                                Then <?php echo formatCheckoutCurrency($plan['price']); ?>/month
                                            </div>
                                        </div>
                                    <?php elseif ($is_paid_plan && !$trial_eligibility['has_used_trial_for_plan']): ?>
                                        <div class="trial-info mb-3">
                                            <div class="alert alert-success border-0">
                                                <i class="fas fa-gift me-2"></i>
                                                <strong>3-Month Free Trial</strong>
                                            </div>
                                            <div class="h4 mb-0">
                                                Then <?php echo formatCheckoutCurrency($plan['price']); ?>/month
                                            </div>
                                        </div>
                                    <?php elseif ($is_upgrade): ?>
                                        <div class="upgrade-info mb-3">
                                            <div class="alert alert-primary border-0">
                                                <i class="fas fa-arrow-up me-2"></i>
                                                <strong>Upgrading</strong>
                                                <div class="small mt-1">
                                                    From <?php echo htmlspecialchars($current_subscription['plan_name']); ?>
                                                </div>
                                            </div>
                                            <div class="h4 mb-0">
                                                <?php echo formatCheckoutCurrency($plan['price']); ?>/month
                                            </div>
                                        </div>
                                    <?php elseif ($is_downgrade): ?>
                                        <div class="downgrade-info mb-3">
                                            <div class="alert alert-warning border-0">
                                                <i class="fas fa-arrow-down me-2"></i>
                                                <strong>Downgrading</strong>
                                                <div class="small mt-1">
                                                    From <?php echo htmlspecialchars($current_subscription['plan_name']); ?>
                                                </div>
                                            </div>
                                            <div class="h4 mb-0">
                                                <?php echo formatCheckoutCurrency($plan['price']); ?>/month
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="h3 mb-0">
                                            <?php echo formatCheckoutCurrency($plan['price']); ?>/month
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($plan['annual_price']): ?>
                                        <div class="annual-pricing mt-3">
                                            <div class="text-success">
                                                <strong>Annual: <?php echo formatCheckoutCurrency($plan['annual_price']); ?>/year</strong>
                                                <div class="badge bg-success mt-1">
                                                    Save <?php echo formatCheckoutCurrency(calculateAnnualSavings($plan['price'], $plan['annual_price'])); ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Plan Features -->
                                <div class="plan-features">
                                    <h5 class="mb-3">
                                        <i class="fas fa-list-check me-2"></i>
                                        Plan Features
                                    </h5>
                                    <ul class="features-list list-unstyled">
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
                                </div>

                                <!-- Security Badge -->
                                <div class="security-badge mt-4 text-center">
                                    <div class="d-flex align-items-center justify-content-center">
                                        <i class="fas fa-shield-alt text-success me-2"></i>
                                        <span class="small text-muted">Secure payment powered by Stripe</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Form -->
                    <div class="col-lg-7">
                        <div class="payment-card card border-0 shadow-lg">
                            <div class="card-header bg-light py-4">
                                <h4 class="mb-0">
                                    <i class="fas fa-credit-card me-2"></i>
                                    Payment Information
                                </h4>
                                <p class="text-muted mb-0 small">Enter your payment details to complete your subscription</p>
                            </div>
                            <div class="card-body p-4">
                                <div id="checkout" class="checkout-container" style="min-height: 500px;">
                                    <div class="loading-spinner text-center py-5">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                        <p class="mt-3 text-muted">Loading secure payment form...</p>
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

<!-- Stripe Integration -->
<script src="https://js.stripe.com/v3/"></script>
<script>
    const stripe = Stripe('<?php echo STRIPE_PUBLIC_KEY; ?>');
    stripe.redirectToCheckout({ sessionId: '<?php echo $session->id; ?>' });
</script>
<?php include '../includes/footer_main.php'; ?> 

<?php
} catch (\Stripe\Exception\ApiErrorException $e) {
    error_log('Stripe API Error: ' . $e->getMessage());
    header('Location: subscription.php?error=stripe_error&message=' . urlencode($e->getMessage()));
    exit();
} catch (\Exception $e) {
    error_log('General Error in checkout.php: ' . $e->getMessage());
    header('Location: subscription.php?error=payment_failed&message=' . urlencode('An unexpected error occurred'));
    exit();
}
?> 