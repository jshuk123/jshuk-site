<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/config.php';
require_once '../config/stripe_config.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /jshuk/auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Verify user exists in database
$stmt = $pdo->prepare("SELECT id, email, first_name, last_name FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    // Invalid session - user doesn't exist
    session_destroy();
    header('Location: /jshuk/auth/login.php?error=invalid_session');
    exit();
}

// Get user's current subscription with proper date handling
$stmt = $pdo->prepare("
    SELECT us.*, sp.name, sp.price, sp.features 
    FROM user_subscriptions us 
    JOIN subscription_plans sp ON us.plan_id = sp.id 
    WHERE us.user_id = ? AND us.status = 'active'
    ORDER BY us.created_at DESC 
    LIMIT 1
");
$stmt->execute([$user_id]);
$current_subscription = $stmt->fetch();

// Get all subscription plans
$stmt = $pdo->prepare("SELECT * FROM subscription_plans ORDER BY price ASC");
$stmt->execute();
$plans = $stmt->fetchAll();

$pageTitle = "Subscription Plans";
$page_css = "subscription.css";
include '../includes/header_main.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12">
            <h1 class="text-center mb-5">Choose Your Subscription Plan</h1>

            <?php if ($current_subscription): ?>
                <div class="alert alert-info text-center mb-4">
                    <h5 class="mb-1">Current Plan: <?php echo htmlspecialchars($current_subscription['name']); ?></h5>
                    <p class="mb-0">
                        Valid until: 
                        <?php 
                        if (!empty($current_subscription['end_date'])) {
                            echo date('F j, Y', strtotime($current_subscription['end_date']));
                        } else {
                            echo 'No expiration date';
                        }
                        ?>
                    </p>
                </div>
            <?php endif; ?>

            <?php
            // Display success message
            if (isset($_GET['status']) && $_GET['status'] === 'success') {
                $plan_name = isset($_GET['plan']) ? htmlspecialchars($_GET['plan']) : 'new';
                echo '<div class="alert alert-success text-center mb-4">
                    <h5 class="mb-1"><i class="fas fa-check-circle"></i> Subscription Activated!</h5>
                    <p class="mb-0">Your ' . $plan_name . ' plan subscription has been successfully activated.</p>
                </div>';
            }

            // Display error messages if any
            if (isset($_GET['error'])) {
                $error_message = '';
                switch ($_GET['error']) {
                    case 'payment_failed':
                        $error_message = 'The payment process could not be completed. Please try again.';
                        break;
                    case 'stripe_error':
                        $error_message = 'There was an issue with the payment service.';
                        break;
                    case 'invalid_plan':
                        $error_message = 'The selected subscription plan is not valid.';
                        break;
                    case 'user_not_found':
                        $error_message = 'Your session has expired. Please log in again.';
                        break;
                    default:
                        $error_message = 'An unknown error occurred.';
                        break;
                }
                if (isset($_GET['message'])) {
                    $error_message .= ' Details: ' . htmlspecialchars($_GET['message']);
                }
                if ($error_message) {
                    echo '<div class="alert alert-danger text-center mb-4">' . $error_message . '</div>';
                }
            }
            ?>

            <div class="row row-cols-1 row-cols-md-3 mb-3 text-center">
                <?php foreach ($plans as $plan): ?>
                <div class="col">
                    <div class="card mb-4 rounded-3 shadow-sm <?php echo $plan['name'] === 'Plus' ? 'border-primary' : ''; ?>">
                        <?php if ($plan['name'] === 'Plus'): ?>
                        <div class="card-header py-3 text-white bg-primary border-primary">
                            <h4 class="my-0 fw-normal">Most Popular</h4>
                        </div>
                        <?php else: ?>
                        <div class="card-header py-3">
                            <h4 class="my-0 fw-normal">&nbsp;</h4>
                        </div>
                        <?php endif; ?>

                        <div class="card-body">
                            <h1 class="card-title pricing-card-title">
                                £<?php echo number_format($plan['price'], 2); ?>
                                <small class="text-muted fw-light">/mo</small>
                            </h1>
                            <h5 class="mt-3 mb-4"><?php echo htmlspecialchars($plan['name']); ?></h5>

                            <ul class="list-unstyled mt-3 mb-4">
                                <?php 
                                $features = json_decode($plan['features'], true);
                                if (is_array($features)):
                                    foreach ($features as $feature): 
                                ?>
                                <li class="mb-2">
                                    <i class="fas fa-check text-success me-2"></i>
                                    <?php echo htmlspecialchars($feature); ?>
                                </li>
                                <?php 
                                    endforeach;
                                endif;
                                ?>
                            </ul>

                            <?php if ($current_subscription): ?>
                                <?php if ($current_subscription['plan_id'] == $plan['id']): ?>
                                    <button class="w-100 btn btn-lg btn-outline-primary" disabled>Current Plan</button>
                                <?php elseif ($current_subscription['price'] < $plan['price']): ?>
                                    <a href="checkout.php?plan=<?php echo $plan['id']; ?>&action=upgrade" class="w-100 btn btn-lg btn-primary">
                                        Upgrade
                                    </a>
                                <?php else: ?>
                                    <a href="checkout.php?plan=<?php echo $plan['id']; ?>&action=downgrade" class="w-100 btn btn-lg btn-outline-secondary">
                                        Downgrade
                                    </a>
                                <?php endif; ?>
                            <?php else: ?>
                                <a href="checkout.php?plan=<?php echo $plan['id']; ?>" 
                                   class="w-100 btn btn-lg <?php echo $plan['name'] === 'Plus' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                    Get Started
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Subscription Benefits</h5>
                            <ul class="list-unstyled">
                                <li class="mb-3">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    Access to premium features and tools
                                </li>
                                <li class="mb-3">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    Priority customer support
                                </li>
                                <li class="mb-3">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    Regular updates and new features
                                </li>
                                <li class="mb-3">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    Cancel anytime - no long-term commitment
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php include '../includes/footer_main.php'; ?>
