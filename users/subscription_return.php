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

// Validate session_id parameter
if (!isset($_GET['session_id'])) {
    header('Location: subscription.php?error=missing_session');
    exit();
}

$user_id = $_SESSION['user_id'];
$session_id = $_GET['session_id'];

try {
    // Retrieve the checkout session
    $session = \Stripe\Checkout\Session::retrieve($session_id);
    
    // Verify payment status
    if (!$session || $session->payment_status !== 'paid') {
        throw new Exception('Payment was not successful.');
    }

    // Get subscription details
    $subscription = \Stripe\Subscription::retrieve($session->subscription);
    
    // Get plan details from subscription
    $stmt = $pdo->prepare("SELECT * FROM subscription_plans WHERE stripe_product_id = ?");
    $stmt->execute([$subscription->items->data[0]->price->product]);
    $plan = $stmt->fetch();

    if (!$plan) {
        throw new Exception('Invalid subscription plan.');
    }

    // Start transaction
    $pdo->beginTransaction();

    // Cancel any existing active subscriptions in Stripe
    $customer = \Stripe\Customer::retrieve($session->customer);
    $existing_subscriptions = \Stripe\Subscription::all([
        'customer' => $customer->id,
        'status' => 'active'
    ]);

    foreach ($existing_subscriptions->data as $existing_sub) {
        if ($existing_sub->id !== $subscription->id) {
            $existing_sub->cancel();
        }
    }

    // Deactivate any current active subscriptions in our database
    $stmt = $pdo->prepare("
        UPDATE user_subscriptions 
        SET status = 'inactive', 
            end_date = NOW(),
            updated_at = NOW() 
        WHERE user_id = ? AND status = 'active'
    ");
    $stmt->execute([$user_id]);

    // Create new subscription record
    $stmt = $pdo->prepare("
        INSERT INTO user_subscriptions (
            user_id, plan_id, stripe_subscription_id, stripe_customer_id,
            status, current_period_start, current_period_end, end_date,
            cancel_at_period_end, created_at, updated_at
        ) VALUES (
            ?, ?, ?, ?, 'active', 
            FROM_UNIXTIME(?), FROM_UNIXTIME(?), FROM_UNIXTIME(?),
            ?, NOW(), NOW()
        )
    ");

    $stmt->execute([
        $user_id,
        $plan['id'],
        $subscription->id,
        $session->customer,
        $subscription->current_period_start,
        $subscription->current_period_end,
        $subscription->current_period_end, // end_date matches period_end for active subscriptions
        $subscription->cancel_at_period_end ? 1 : 0
    ]);

    $pdo->commit();

    // Redirect to success page with subscription details
    header('Location: subscription.php?status=success&plan=' . urlencode($plan['name']));
    exit();

} catch(\Stripe\Exception\ApiErrorException $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    error_log('Stripe API Error: ' . $e->getMessage());
    header('Location: subscription.php?error=stripe_error&message=' . urlencode($e->getMessage()));
    exit();
} catch(Exception $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    error_log('Subscription Error: ' . $e->getMessage());
    header('Location: subscription.php?error=subscription_failed&message=' . urlencode($e->getMessage()));
    exit();
}
?> 