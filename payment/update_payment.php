<?php
session_start();
require_once '../config/config.php';
require_once '../config/stripe_config.php';
require_once '../includes/header_main.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /jshuk/auth/login.php');
    exit();
}

// Get user details
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

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
                'user_id' => $user['id']
            ]
        ]);

        // Update user with Stripe customer ID
        $stmt = $pdo->prepare("UPDATE users SET stripe_customer_id = ? WHERE id = ?");
        $stmt->execute([$customer->id, $user['id']]);
    }

    // Create Stripe Checkout Session for updating payment method
    $checkout_session = \Stripe\Checkout\Session::create([
        'customer' => $customer->id,
        'payment_method_types' => ['card'],
        'mode' => 'setup',
        'success_url' => 'https://' . $_SERVER['HTTP_HOST'] . '/jshuk/payment/payment_success.php?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url' => 'https://' . $_SERVER['HTTP_HOST'] . '/jshuk/users/settings.php',
        'metadata' => [
            'user_id' => $user['id']
        ]
    ]);

    // Redirect to Stripe Checkout
    header('Location: ' . $checkout_session->url);
    exit();

} catch (\Stripe\Exception\ApiErrorException $e) {
    error_log('Stripe Error: ' . $e->getMessage());
    header('Location: /jshuk/users/settings.php?error=payment_update_failed');
    exit();
}
?> 