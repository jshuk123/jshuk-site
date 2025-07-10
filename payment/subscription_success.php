<?php
/**
 * JShuk Subscription Success Handler
 * 
 * This file handles successful subscription payments and processes Stripe webhooks.
 * Features secure payment verification, trial management, and subscription activation.
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

// Validate session_id parameter
$session_id = filter_input(INPUT_GET, 'session_id', FILTER_SANITIZE_STRING);
if (!$session_id) {
    header('Location: ' . BASE_PATH . 'payment/subscription.php?error=invalid_session');
    exit();
}

/**
 * Get plan details with validation
 */
function getPlanDetails($pdo, $plan_id) {
    $stmt = $pdo->prepare("
        SELECT * FROM subscription_plans 
        WHERE id = ? AND status = 'active'
    ");
    $stmt->execute([$plan_id]);
    return $stmt->fetch();
}

/**
 * Check trial eligibility for specific plan
 */
function checkTrialEligibility($pdo, $user_id, $plan_id) {
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
}

/**
 * Process subscription data and determine periods
 */
function processSubscriptionData($subscription, $plan) {
    $is_paid_plan = $plan['price'] > 0;
    
    // Calculate subscription periods
    $current_period_start = date('Y-m-d H:i:s', $subscription->current_period_start);
    
    // For monthly subscriptions, ensure the period is one month
    $period_end_timestamp = strtotime('+1 month', $subscription->current_period_start);
    $current_period_end = date('Y-m-d H:i:s', $period_end_timestamp);
    
    // Handle trial period
    $trial_end = null;
    $is_trial = false;

    if ($subscription->status === 'trialing' && $subscription->trial_end) {
        $trial_end = date('Y-m-d H:i:s', $subscription->trial_end);
        $is_trial = true;
        error_log("Existing trial period found. Trial ends at: " . $trial_end);
    }

    // Set end date based on trial or subscription period
    $end_date = $trial_end ?: $current_period_end;

    // Set status based on subscription state
    $status = $subscription->status;
    if ($is_trial) {
        $status = 'trialing';
    } elseif (!in_array($status, ['active', 'past_due', 'canceled'])) {
        $status = 'inactive';
    }

    return [
        'is_paid_plan' => $is_paid_plan,
        'current_period_start' => $current_period_start,
        'current_period_end' => $current_period_end,
        'trial_end' => $trial_end,
        'is_trial' => $is_trial,
        'end_date' => $end_date,
        'status' => $status
    ];
}

/**
 * Update subscription in database
 */
function updateSubscription($pdo, $user_id, $plan_id, $subscription, $session, $subscription_data) {
    // Begin transaction
    $pdo->beginTransaction();

    try {
        // First, deactivate any existing subscriptions for this user
        $stmt = $pdo->prepare("
            UPDATE user_subscriptions 
            SET status = 'inactive',
                updated_at = NOW()
            WHERE user_id = ? AND stripe_subscription_id != ?
        ");
        $stmt->execute([$user_id, $subscription->id]);

        // Insert or update subscription record
        $stmt = $pdo->prepare("
            INSERT INTO user_subscriptions (
                user_id, 
                plan_id, 
                stripe_subscription_id, 
                stripe_customer_id,
                status,
                current_period_start,
                current_period_end,
                cancel_at_period_end,
                trial_end,
                created_at,
                end_date,
                updated_at
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, NOW()
            )
            ON DUPLICATE KEY UPDATE
                status = VALUES(status),
                current_period_start = VALUES(current_period_start),
                current_period_end = VALUES(current_period_end),
                cancel_at_period_end = VALUES(cancel_at_period_end),
                trial_end = VALUES(trial_end),
                end_date = VALUES(end_date),
                updated_at = NOW()
        ");

        $stmt->execute([
            $user_id,
            $plan_id,
            $subscription->id,
            $session->customer,
            $subscription_data['status'],
            $subscription_data['current_period_start'],
            $subscription_data['current_period_end'],
            $subscription->cancel_at_period_end ? 1 : 0,
            $subscription_data['trial_end'],
            $subscription_data['end_date']
        ]);

        // Update user's subscription status and trial information
        $stmt = $pdo->prepare("
            UPDATE users 
            SET subscription_status = ?,
                trial_ends_at = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$subscription_data['status'], $subscription_data['trial_end'], $user_id]);

        // Update user's subscription tier based on the plan
        $tier_mapping = [
            'Basic' => 'basic',
            'Premium' => 'premium',
            'Premium Plus' => 'premium_plus'
        ];
        
        $subscription_tier = $tier_mapping[$plan['name']] ?? 'basic';
        $stmt = $pdo->prepare("UPDATE users SET subscription_tier = ? WHERE id = ?");
        $stmt->execute([$subscription_tier, $user_id]);

        $pdo->commit();
        return true;

    } catch (PDOException $e) {
        $pdo->rollBack();
        
        // If it's a duplicate entry, consider it successful
        if ($e->getCode() == '23000' && strpos($e->getMessage(), 'Duplicate entry') !== false) {
            error_log("Duplicate subscription entry - subscription already exists for user {$user_id}");
            return true;
        }
        
        throw $e;
    }
}

/**
 * Update session variables
 */
function updateSessionVariables($subscription_data, $plan_id) {
    $_SESSION['subscription_status'] = $subscription_data['status'];
    $_SESSION['current_plan_id'] = $plan_id;
    $_SESSION['subscription_end_date'] = $subscription_data['end_date'];
    $_SESSION['is_trial'] = $subscription_data['is_trial'];
    $_SESSION['trial_end_date'] = $subscription_data['trial_end'];
}

try {
    // Initialize Stripe
    \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

    // Retrieve the checkout session
    $session = \Stripe\Checkout\Session::retrieve($session_id);
    
    // Verify the session belongs to the current user
    if ($session->metadata->user_id != $user_id) {
        throw new Exception('Invalid session - user mismatch');
    }

    // Get the subscription
    $subscription = \Stripe\Subscription::retrieve($session->subscription);
    
    // Log subscription details for debugging
    error_log("Processing subscription: " . $subscription->id);
    error_log("Subscription status: " . $subscription->status);
    error_log("Subscription metadata: " . json_encode($subscription->metadata));
    
    // Get the plan details
    $plan = getPlanDetails($pdo, $session->metadata->plan_id);
    if (!$plan) {
        throw new Exception('Invalid plan - plan not found or inactive');
    }

    // Check trial eligibility
    $trial_eligibility = checkTrialEligibility($pdo, $user_id, $plan['id']);
    
    // Log trial eligibility for debugging
    error_log("Plan ID: {$plan['id']}, Is Paid: " . ($plan['price'] > 0 ? 'yes' : 'no'));
    error_log("Has used trial for this plan: " . ($trial_eligibility['has_used_trial_for_plan'] ? 'yes' : 'no'));
    error_log("Has active trial: " . ($trial_eligibility['has_active_trial'] ? 'yes' : 'no'));

    // Process subscription data
    $subscription_data = processSubscriptionData($subscription, $plan);
    
    // Handle trial period logic for new subscriptions
    if ($subscription_data['is_paid_plan'] && 
        !$trial_eligibility['has_used_trial_for_plan'] && 
        !$subscription_data['is_trial']) {
        
        // Check if this should have a trial period
        $should_have_trial = $trial_eligibility['has_active_trial'] && 
                           $trial_eligibility['current_trial_plan_id'] != $plan['id'];
        
        if ($should_have_trial) {
            try {
                error_log("Setting new trial period for subscription: " . $subscription->id);
                $trial_end_timestamp = strtotime('+90 days');
                
                $subscription = \Stripe\Subscription::update($subscription->id, [
                    'trial_end' => $trial_end_timestamp,
                    'metadata' => array_merge($subscription->metadata->toArray(), [
                        'has_trial' => 'true',
                        'has_used_trial_for_plan' => 'false'
                    ])
                ]);
                
                $subscription_data['trial_end'] = date('Y-m-d H:i:s', $trial_end_timestamp);
                $subscription_data['is_trial'] = true;
                $subscription_data['status'] = 'trialing';
                $subscription_data['end_date'] = $subscription_data['trial_end'];
                
                error_log("Trial period set. Trial ends at: " . $subscription_data['trial_end']);
            } catch (Exception $e) {
                error_log("Error setting trial period: " . $e->getMessage());
                // Continue without trial if setting fails
            }
        }
    }

    // Update subscription in database
    $update_success = updateSubscription($pdo, $user_id, $plan['id'], $subscription, $session, $subscription_data);
    
    if ($update_success) {
        // Update session variables
        updateSessionVariables($subscription_data, $plan['id']);

        // Log successful subscription
        error_log("Subscription processed successfully for user {$user_id}");
        error_log("Status: {$subscription_data['status']}, Period End: {$subscription_data['current_period_end']}, Trial End: {$subscription_data['trial_end']}");

        // Store success data in session
        $_SESSION['subscription_success'] = [
            'plan_name' => $plan['name'],
            'subscription_tier' => $subscription_tier,
            'trial_end' => $trial_end
        ];

        // Redirect to subscription page with success message
        header('Location: ' . BASE_PATH . 'payment/subscription.php?success=subscription_created');
        exit();
    } else {
        throw new Exception('Failed to update subscription in database');
    }

} catch (\Stripe\Exception\ApiErrorException $e) {
    error_log('Stripe API Error: ' . $e->getMessage());
    header('Location: ' . BASE_PATH . 'payment/subscription.php?error=stripe_error&message=' . urlencode($e->getMessage()));
    exit();
} catch (PDOException $e) {
    error_log('Database Error: ' . $e->getMessage());
    header('Location: ' . BASE_PATH . 'payment/subscription.php?error=database_error&message=' . urlencode('Database error occurred'));
    exit();
} catch (Exception $e) {
    error_log('Subscription Success Error: ' . $e->getMessage());
    header('Location: ' . BASE_PATH . 'payment/subscription.php?error=payment_failed&message=' . urlencode($e->getMessage()));
    exit();
}
?> 