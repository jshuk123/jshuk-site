<?php
session_start();
require_once '../config/config.php';
require_once '../config/stripe_config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /jshuk/auth/login.php');
    exit();
}

// Get user's current subscription
$current_subscription = getUserSubscription($_SESSION['user_id']);

if (!$current_subscription || $current_subscription['status'] !== 'active') {
    header('Location: /jshuk/users/settings.php');
    exit();
}

// Initialize Stripe
\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

try {
    // Cancel the subscription at period end
    $subscription = \Stripe\Subscription::update($current_subscription['stripe_subscription_id'], [
        'cancel_at_period_end' => true
    ]);

    // Update subscription status in database
    $stmt = $pdo->prepare("
        UPDATE user_subscriptions 
        SET status = 'canceled', 
            updated_at = NOW() 
        WHERE stripe_subscription_id = ?
    ");
    $stmt->execute([$current_subscription['stripe_subscription_id']]);

    // Update user's subscription status
    $stmt = $pdo->prepare("
        UPDATE users 
        SET subscription_status = 'canceled' 
        WHERE id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);

    // Update session subscription info
    $_SESSION['subscription']['status'] = 'canceled';

    // Set success message
    $_SESSION['success_message'] = "Your subscription has been canceled. You'll continue to have access until " . 
                                 date('F j, Y', $subscription->current_period_end) . ".";

    // Redirect to settings page
    header('Location: /jshuk/users/settings.php');
    exit();

} catch (Exception $e) {
    error_log('Subscription Cancellation Error: ' . $e->getMessage());
    header('Location: /jshuk/users/settings.php?error=cancellation_failed');
    exit();
}
?> 