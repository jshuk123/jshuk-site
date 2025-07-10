<?php
session_start();
require_once '../config/config.php';
require_once '../config/stripe_config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /jshuk/auth/login.php');
    exit();
}

// Check if session_id is provided
if (!isset($_GET['session_id'])) {
    header('Location: /jshuk/users/settings.php');
    exit();
}

// Initialize Stripe
\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

try {
    // Retrieve the checkout session
    $session = \Stripe\Checkout\Session::retrieve($_GET['session_id']);
    
    // Verify the session belongs to the current user
    if ($session->metadata->user_id != $_SESSION['user_id']) {
        throw new Exception('Invalid session');
    }

    // Get the setup intent
    $setup_intent = \Stripe\SetupIntent::retrieve($session->setup_intent);

    // Get the payment method
    $payment_method = \Stripe\PaymentMethod::retrieve($setup_intent->payment_method);

    // Get user details
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    // Update the customer's default payment method
    \Stripe\Customer::update($user['stripe_customer_id'], [
        'invoice_settings' => [
            'default_payment_method' => $payment_method->id
        ]
    ]);

    // Set success message
    $_SESSION['success_message'] = "Your payment method has been successfully updated!";

    // Redirect to settings page
    header('Location: /jshuk/users/settings.php');
    exit();

} catch (Exception $e) {
    error_log('Payment Update Error: ' . $e->getMessage());
    header('Location: /jshuk/users/settings.php?error=payment_update_failed');
    exit();
}
?> 