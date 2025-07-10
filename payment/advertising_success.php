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

    // Get subscription details from Stripe
    $subscription = \Stripe\Subscription::retrieve($session->subscription);
    
    // Get slot details from session
    $slot_id = $_SESSION['selected_slot_id'] ?? null;
    if (!$slot_id) {
        throw new Exception('Selected slot not found in session.');
    }

    // Get slot details
    $stmt = $pdo->prepare("SELECT * FROM advertising_slots WHERE id = ?");
    $stmt->execute([$slot_id]);
    $slot = $stmt->fetch();

    if (!$slot) {
        throw new Exception('Invalid advertising slot.');
    }

    // Start transaction
    $pdo->beginTransaction();

    // Calculate subscription dates
    $start_date = date('Y-m-d H:i:s', $subscription->current_period_start);
    $end_date = date('Y-m-d H:i:s', $subscription->current_period_end);

    // Deactivate any existing active slots for this user
    $stmt = $pdo->prepare("
        UPDATE user_advertising_slots 
        SET payment_status = 'cancelled',
            end_date = NOW(),
            updated_at = NOW()
        WHERE user_id = ? AND payment_status = 'paid'
    ");
    $stmt->execute([$user_id]);

    // Create new advertising slot subscription
    $stmt = $pdo->prepare("
        INSERT INTO user_advertising_slots (
            user_id,
            slot_id,
            start_date,
            end_date,
            payment_status,
            created_at,
            updated_at
        ) VALUES (
            ?, ?, ?, ?, 'paid', NOW(), NOW()
        )
    ");

    $stmt->execute([
        $user_id,
        $slot_id,
        $start_date,
        $end_date
    ]);

    // Update available slots count
    $stmt = $pdo->prepare("
        UPDATE advertising_slots 
        SET current_slots = current_slots + 1 
        WHERE id = ?
    ");
    $stmt->execute([$slot_id]);

    $pdo->commit();

    // Clear the selected slot from session
    unset($_SESSION['selected_slot_id']);

    // Redirect to success page with slot details
    header('Location: subscription.php?status=success&slot=' . urlencode($slot['name']));
    exit();

} catch(Exception $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    error_log('Advertising Purchase Error: ' . $e->getMessage());
    header('Location: subscription.php?error=payment_failed&message=' . urlencode($e->getMessage()));
    exit();
}
?> 