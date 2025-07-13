<?php
/**
 * JShuk Advertising Checkout
 * 
 * This file handles the Stripe checkout process for advertising slot bookings.
 * Features secure payment processing, slot availability validation, and
 * professional UI for the JShuk business directory platform.
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

// Validate and sanitize slot_id parameter
$slot_id = filter_input(INPUT_GET, 'slot', FILTER_VALIDATE_INT);
if (!$slot_id) {
    header('Location: subscription.php?error=invalid_slot');
    exit();
}

/**
 * Get user details with validation
 */
function getUserDetails($pdo, $user_id) {
    $stmt = $pdo->prepare("
        SELECT id, email, first_name, last_name, stripe_customer_id, status 
        FROM users 
        WHERE id = ? AND status = 'active'
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

/**
 * Get advertising slot details with availability check
 */
function getAdvertisingSlot($pdo, $slot_id) {
    $stmt = $pdo->prepare("
        SELECT * FROM advertising_slots 
        WHERE id = ? 
        AND current_slots < max_slots 
        AND status = 'active'
    ");
    $stmt->execute([$slot_id]);
    return $stmt->fetch();
}

/**
 * Create or update Stripe customer
 */
function createOrUpdateStripeCustomer($user) {
    if (!$user['stripe_customer_id']) {
        $customer = \Stripe\Customer::create([
            'email' => $user['email'],
            'name' => $user['first_name'] . ' ' . $user['last_name'],
            'metadata' => [
                'user_id' => $user['id'],
                'created_at' => date('Y-m-d H:i:s')
            ]
        ]);
        
        // Save Stripe customer ID to database
        global $pdo;
        $stmt = $pdo->prepare("UPDATE users SET stripe_customer_id = ? WHERE id = ?");
        $stmt->execute([$customer->id, $user['id']]);
        
        return $customer;
    } else {
        // Update existing customer information
        $customer = \Stripe\Customer::update($user['stripe_customer_id'], [
            'email' => $user['email'],
            'name' => $user['first_name'] . ' ' . $user['last_name']
        ]);
        
        return $customer;
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
function formatAdvertisingCurrency($amount, $currency = 'GBP') {
    return '£' . number_format($amount, 2);
}

// Get user and slot details
$user = getUserDetails($pdo, $user_id);
$slot = getAdvertisingSlot($pdo, $slot_id);

if (!$user) {
    error_log('User not found or session invalid: ' . $user_id);
    session_destroy();
    header('Location: /jshuk/auth/login.php?error=invalid_session');
    exit();
}

if (!$slot) {
    error_log('Invalid or unavailable advertising slot requested: ' . $slot_id);
    header('Location: subscription.php?error=invalid_slot');
    exit();
}

// Save the selected slot ID to session for success page
$_SESSION['selected_slot_id'] = $slot_id;

try {
    // Initialize Stripe
    \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

    // Create or update Stripe customer
    $customer = createOrUpdateStripeCustomer($user);

    // Build checkout parameters
    $checkout_params = [
        'customer' => $customer->id,
        'payment_method_types' => ['card'],
        'line_items' => [[
            'price_data' => [
                'product_data' => [
                    'name' => $slot['name'],
                    'description' => $slot['description'],
                    'metadata' => [
                        'slot_id' => $slot['id'],
                        'slot_type' => 'advertising'
                    ]
                ],
                'unit_amount' => $slot['monthly_price'] * 100, // Convert to cents
                'currency' => 'gbp',
                'recurring' => [
                    'interval' => 'month'
                ]
            ],
            'quantity' => 1
        ]],
        'mode' => 'subscription',
        'ui_mode' => 'embedded',
        'return_url' => 'https://' . $_SERVER['HTTP_HOST'] . '/jshuk/payment/advertising_success.php?session_id={CHECKOUT_SESSION_ID}',
        'metadata' => [
            'slot_id' => $slot['id'],
            'user_id' => $user['id'],
            'slot_type' => 'advertising'
        ],
        'subscription_data' => [
            'metadata' => [
                'slot_id' => $slot['id'],
                'user_id' => $user['id'],
                'slot_type' => 'advertising'
            ]
        ]
    ];

    // Create Stripe Checkout Session
    $session = \Stripe\Checkout\Session::create($checkout_params);

    // Store session ID for success page
    $_SESSION['advertising_session_id'] = $session->id;

    $pageTitle = "Advertising Checkout";
    $page_css = "advertising_checkout.css";
    include '../includes/header_main.php';
?>

<!-- Hero Section -->
<div class="hero-section bg-gradient-primary text-white py-5 mb-5">
    <div class="container">
        <div class="row justify-content-center text-center">
            <div class="col-lg-8">
                <h1 class="display-5 fw-bold mb-3">
                    <i class="fas fa-bullhorn me-3"></i>
                    Book Your Advertising Slot
                </h1>
                <p class="lead mb-0">
                    Secure payment processing for premium advertising opportunities
                </p>
            </div>
        </div>
    </div>
</div>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="advertising-checkout-container">
                <div class="row g-5">
                    <!-- Slot Summary -->
                    <div class="col-lg-5">
                        <div class="slot-summary-card card border-0 shadow-lg h-100">
                            <div class="card-header bg-gradient-primary text-white py-4">
                                <h4 class="mb-0 text-center">
                                    <i class="fas fa-star me-2"></i>
                                    <?php echo htmlspecialchars($slot['name']); ?>
                                </h4>
                            </div>
                            <div class="card-body p-4">
                                
                                <!-- Slot Description -->
                                <div class="slot-description mb-4">
                                    <p class="text-muted mb-3">
                                        <?php echo htmlspecialchars($slot['description']); ?>
                                    </p>
                                </div>

                                <!-- Pricing Display -->
                                <div class="pricing-display text-center mb-4">
                                    <div class="monthly-pricing mb-3">
                                        <div class="h3 mb-1">
                                            <?php echo formatAdvertisingCurrency($slot['monthly_price']); ?>
                                            <small class="text-muted">/month</small>
                                        </div>
                                    </div>
                                    
                                    <?php if ($slot['annual_price']): ?>
                                        <div class="annual-pricing">
                                            <div class="text-success">
                                                <strong>Annual: <?php echo formatAdvertisingCurrency($slot['annual_price']); ?>/year</strong>
                                                <div class="badge bg-success mt-1">
                                                    Save <?php echo formatAdvertisingCurrency(calculateAnnualSavings($slot['monthly_price'], $slot['annual_price'])); ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Slot Features -->
                                <div class="slot-features">
                                    <h5 class="mb-3">
                                        <i class="fas fa-list-check me-2"></i>
                                        Slot Features
                                    </h5>
                                    <ul class="features-list list-unstyled">
                                        <li class="feature-item">
                                            <i class="fas fa-eye text-primary me-2"></i>
                                            <span>High visibility placement</span>
                                        </li>
                                        <li class="feature-item">
                                            <i class="fas fa-chart-line text-primary me-2"></i>
                                            <span>Track performance metrics</span>
                                        </li>
                                        <li class="feature-item">
                                            <i class="fas fa-users text-primary me-2"></i>
                                            <span>Target business audience</span>
                                        </li>
                                        <li class="feature-item">
                                            <i class="fas fa-clock text-primary me-2"></i>
                                            <span>24/7 exposure</span>
                                        </li>
                                        <li class="feature-item">
                                            <i class="fas fa-mobile-alt text-primary me-2"></i>
                                            <span>Mobile responsive</span>
                                        </li>
                                    </ul>
                                </div>

                                <!-- Availability -->
                                <div class="availability-info mt-4">
                                    <div class="alert alert-info border-0">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-info-circle me-2"></i>
                                            <div>
                                                <strong>Availability</strong>
                                                <div class="small">
                                                    <?php 
                                                    $available = $slot['max_slots'] - $slot['current_slots'];
                                                    echo $available . ' slot' . ($available !== 1 ? 's' : '') . ' remaining';
                                                    ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
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
                                <p class="text-muted mb-0 small">Enter your payment details to book this advertising slot</p>
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

/* Cards */
.slot-summary-card, .payment-card {
    border-radius: 1rem;
    overflow: hidden;
}

.bg-gradient-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

/* Pricing Display */
.pricing-display {
    padding: 1.5rem 0;
}

.pricing-display .h3 {
    font-weight: 700;
    color: #212529;
}

/* Features List */
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
    font-size: 1rem;
}

.feature-item span {
    flex: 1;
    font-size: 0.95rem;
}

/* Security Badge */
.security-badge {
    padding: 1rem;
    background-color: #f8f9fa;
    border-radius: 0.5rem;
    border: 1px solid #e9ecef;
}

/* Loading Spinner */
.loading-spinner {
    min-height: 300px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

/* Responsive Design */
@media (max-width: 991.98px) {
    .hero-section {
        padding: 3rem 0;
    }
    
    .slot-summary-card {
        margin-bottom: 2rem;
    }
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

.slot-summary-card, .payment-card {
    animation: fadeInUp 0.6s ease-out;
}

.payment-card {
    animation-delay: 0.1s;
}

/* Stripe Checkout Container */
#checkout {
    border-radius: 0.5rem;
    overflow: hidden;
}

/* Alert Styling */
.alert {
    border: none;
    border-radius: 0.5rem;
}

.alert-info {
    background-color: #d1ecf1;
    color: #0c5460;
}
</style>

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
    error_log('Advertising Checkout Error: ' . $e->getMessage());
    header('Location: subscription.php?error=payment_failed&message=' . urlencode('An unexpected error occurred'));
    exit();
}
?> 