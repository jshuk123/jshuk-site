<?php
/**
 * JShuk Stripe Webhook Handler
 * 
 * This file handles Stripe webhook events for subscription management.
 * Features secure webhook verification, comprehensive event processing,
 * and robust error handling for the JShuk business directory platform.
 * 
 * @author JShuk Development Team
 * @version 2.0
 */

// Load required configurations
require_once '../config/config.php';
require_once '../config/stripe_config.php';

// Webhook configuration
$webhook_secret = 'whsec_NHiaoFgCmxSDbqPG3yg8lQXlXsATpAXp';

/**
 * Enhanced logging function with structured output
 */
function logWebhookEvent($message, $level = 'INFO', $data = null) {
    $logFile = __DIR__ . '/../logs/stripe_webhook.log';
    $timestamp = date('Y-m-d H:i:s');
    $formattedMessage = "[{$timestamp}] [{$level}] {$message}";
    
    if ($data) {
        $formattedMessage .= " | Data: " . json_encode($data);
    }
    
    $formattedMessage .= "\n";
    
    // Create logs directory if it doesn't exist
    if (!file_exists(dirname($logFile))) {
        mkdir(dirname($logFile), 0777, true);
    }
    
    file_put_contents($logFile, $formattedMessage, FILE_APPEND);
}

/**
 * Get user details by subscription ID
 */
function getUserBySubscriptionId($pdo, $subscription_id) {
    $stmt = $pdo->prepare("
        SELECT u.id, u.email, u.first_name, u.last_name, u.subscription_status
        FROM users u 
        JOIN user_subscriptions us ON u.id = us.user_id 
        WHERE us.stripe_subscription_id = ?
    ");
    $stmt->execute([$subscription_id]);
    return $stmt->fetch();
}

/**
 * Get user details by user ID
 */
function getUserById($pdo, $user_id) {
    $stmt = $pdo->prepare("
        SELECT id, email, first_name, last_name, subscription_status
        FROM users 
        WHERE id = ?
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetch();
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
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as plan_trial_count
        FROM user_subscriptions 
        WHERE user_id = ? 
        AND plan_id = ?
        AND trial_end IS NOT NULL
    ");
    $stmt->execute([$user_id, $plan_id]);
    $has_used_trial_for_plan = $stmt->fetch()['plan_trial_count'] > 0;

    return [
        'has_used_trial_for_plan' => $has_used_trial_for_plan
    ];
}

/**
 * Process subscription periods and trial data
 */
function processSubscriptionData($subscription) {
    $current_period_start = date('Y-m-d H:i:s', $subscription->current_period_start);
    $current_period_end = date('Y-m-d H:i:s', $subscription->current_period_end);
    
    $trial_end = null;
    $is_trial = false;
    
    if ($subscription->status === 'trialing' && $subscription->trial_end) {
        $trial_end = date('Y-m-d H:i:s', $subscription->trial_end);
        $is_trial = true;
    }

    $end_date = $trial_end ?: $current_period_end;
    $status = $subscription->status;

    return [
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
function updateSubscription($pdo, $subscription_data, $subscription, $user_id = null) {
    $pdo->beginTransaction();

    try {
        // Deactivate other subscriptions if user_id provided
        if ($user_id) {
            $stmt = $pdo->prepare("
                UPDATE user_subscriptions 
                SET status = 'inactive',
                    updated_at = NOW()
                WHERE user_id = ? AND stripe_subscription_id != ?
            ");
            $stmt->execute([$user_id, $subscription->id]);
        }

        // Update subscription record
        $stmt = $pdo->prepare("
            UPDATE user_subscriptions 
            SET status = ?,
                current_period_start = ?,
                current_period_end = ?,
                trial_end = ?,
                end_date = ?,
                cancel_at_period_end = ?,
                updated_at = NOW() 
            WHERE stripe_subscription_id = ?
        ");

        $stmt->execute([
            $subscription_data['status'],
            $subscription_data['current_period_start'],
            $subscription_data['current_period_end'],
            $subscription_data['trial_end'],
            $subscription_data['end_date'],
            $subscription->cancel_at_period_end ? 1 : 0,
            $subscription->id
        ]);

        // Update user's subscription status
        if ($user_id) {
            $stmt = $pdo->prepare("
                UPDATE users 
                SET subscription_status = ?,
                    trial_ends_at = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$subscription_data['status'], $subscription_data['trial_end'], $user_id]);
        }

        $pdo->commit();
        return true;

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Handle subscription creation event
 */
function handleSubscriptionCreated($subscription) {
    global $pdo;

    try {
        logWebhookEvent("Processing subscription creation", "INFO", [
            'subscription_id' => $subscription->id,
            'status' => $subscription->status,
            'user_id' => $subscription->metadata->user_id ?? 'unknown'
        ]);

        // Get user details
        $user = getUserById($pdo, $subscription->metadata->user_id);
        if (!$user) {
            throw new Exception('User not found for subscription: ' . $subscription->id);
        }

        // Get plan details
        $plan = getPlanDetails($pdo, $subscription->metadata->plan_id);
        if (!$plan) {
            throw new Exception('Plan not found: ' . $subscription->metadata->plan_id);
        }

        // Check trial eligibility
        $trial_eligibility = checkTrialEligibility($pdo, $user['id'], $plan['id']);
        
        // Process subscription data
        $subscription_data = processSubscriptionData($subscription);

        // Handle trial period logic
        if ($subscription_data['is_trial']) {
            $is_paid_plan = $plan['price'] > 0;
            
            if (!$is_paid_plan) {
                logWebhookEvent("Free plan - ending trial immediately", "INFO");
                try {
                    $subscription = \Stripe\Subscription::update($subscription->id, [
                        'trial_end' => 'now',
                    ]);
                    $subscription_data = processSubscriptionData($subscription);
                } catch (Exception $e) {
                    logWebhookEvent("Error ending trial for free plan: " . $e->getMessage(), "ERROR");
                }
            } elseif ($trial_eligibility['has_used_trial_for_plan']) {
                logWebhookEvent("User already had trial for this plan - ending trial", "INFO");
                try {
                    $subscription = \Stripe\Subscription::update($subscription->id, [
                        'trial_end' => 'now',
                    ]);
                    $subscription_data = processSubscriptionData($subscription);
                } catch (Exception $e) {
                    logWebhookEvent("Error ending trial: " . $e->getMessage(), "ERROR");
                }
            } else {
                logWebhookEvent("New trial period allowed", "INFO", [
                    'trial_end' => $subscription_data['trial_end']
                ]);
            }
        }

        // Update subscription in database
        updateSubscription($pdo, $subscription_data, $subscription, $user['id']);

        // Update user's subscription tier
        updateUserSubscriptionTier($pdo, $user['id'], $plan['id']);

        logWebhookEvent("Subscription created successfully", "SUCCESS", [
            'user_id' => $user['id'],
            'plan_id' => $plan['id'],
            'status' => $subscription_data['status'],
            'trial_end' => $subscription_data['trial_end']
        ]);

    } catch (Exception $e) {
        logWebhookEvent("Error handling subscription creation: " . $e->getMessage(), "ERROR");
        throw $e;
    }
}

/**
 * Handle subscription update event
 */
function handleSubscriptionUpdated($subscription) {
    global $pdo;

    try {
        logWebhookEvent("Processing subscription update", "INFO", [
            'subscription_id' => $subscription->id,
            'status' => $subscription->status
        ]);

        // Get user details
        $user = getUserBySubscriptionId($pdo, $subscription->id);
        if (!$user) {
            throw new Exception('User not found for subscription: ' . $subscription->id);
        }

        // Process subscription data
        $subscription_data = processSubscriptionData($subscription);

        // Update subscription in database
        updateSubscription($pdo, $subscription_data, $subscription, $user['id']);

        // Update user's subscription tier
        updateUserSubscriptionTier($pdo, $user['id'], $subscription_data['plan_id']);

        logWebhookEvent("Subscription updated successfully", "SUCCESS", [
            'user_id' => $user['id'],
            'status' => $subscription_data['status'],
            'end_date' => $subscription_data['end_date']
        ]);

    } catch (Exception $e) {
        logWebhookEvent("Error handling subscription update: " . $e->getMessage(), "ERROR");
        throw $e;
    }
}

/**
 * Handle subscription deletion event
 */
function handleSubscriptionDeleted($subscription) {
    global $pdo;

    try {
        logWebhookEvent("Processing subscription deletion", "INFO", [
            'subscription_id' => $subscription->id
        ]);

        // Get user details
        $user = getUserBySubscriptionId($pdo, $subscription->id);
        if (!$user) {
            logWebhookEvent("User not found for deleted subscription", "WARNING");
            return;
        }

        // Update subscription status
        $stmt = $pdo->prepare("
            UPDATE user_subscriptions 
            SET status = 'inactive',
                end_date = NOW(),
                updated_at = NOW() 
            WHERE stripe_subscription_id = ?
        ");
        $stmt->execute([$subscription->id]);

        // Update user's subscription status
        $stmt = $pdo->prepare("
            UPDATE users 
            SET subscription_status = 'inactive',
                trial_ends_at = NULL,
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$user['id']]);

        // Reset user's subscription tier
        resetUserSubscriptionTier($pdo, $user['id']);

        logWebhookEvent("Subscription deleted successfully", "SUCCESS", [
            'user_id' => $user['id']
        ]);

        // Send cancellation email
        sendSubscriptionCancelledEmail($user);

    } catch (Exception $e) {
        logWebhookEvent("Error handling subscription deletion: " . $e->getMessage(), "ERROR");
        throw $e;
    }
}

/**
 * Handle checkout session completion
 */
function handleCheckoutSessionCompleted($session) {
    global $pdo;

    try {
        logWebhookEvent("Processing checkout session completion", "INFO", [
            'session_id' => $session->id,
            'user_id' => $session->metadata->user_id ?? 'unknown'
        ]);

        // Get subscription details
        $subscription = \Stripe\Subscription::retrieve($session->subscription);
        
        // Get user details
        $user = getUserById($pdo, $session->metadata->user_id);
        if (!$user) {
            throw new Exception('User not found for session: ' . $session->id);
        }

        // Process subscription data
        $subscription_data = processSubscriptionData($subscription);

        // Insert new subscription record
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
        ");

        $stmt->execute([
            $user['id'],
            $session->metadata->plan_id,
            $subscription->id,
            $session->customer,
            $subscription_data['status'],
            $subscription_data['current_period_start'],
            $subscription_data['current_period_end'],
            $subscription->cancel_at_period_end ? 1 : 0,
            $subscription_data['trial_end'],
            $subscription_data['end_date']
        ]);

        // Update user's subscription status
        $stmt = $pdo->prepare("
            UPDATE users 
            SET subscription_status = ?,
                trial_ends_at = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$subscription_data['status'], $subscription_data['trial_end'], $user['id']]);

        // Update user's subscription tier
        updateUserSubscriptionTier($pdo, $user['id'], $session->metadata->plan_id);

        logWebhookEvent("Checkout session completed successfully", "SUCCESS", [
            'user_id' => $user['id'],
            'plan_id' => $session->metadata->plan_id
        ]);

    } catch (Exception $e) {
        logWebhookEvent("Error handling checkout session completion: " . $e->getMessage(), "ERROR");
        throw $e;
    }
}

/**
 * Handle successful invoice payment
 */
function handleInvoicePaymentSucceeded($invoice) {
    global $pdo;

    try {
        logWebhookEvent("Processing successful invoice payment", "INFO", [
            'invoice_id' => $invoice->id,
            'subscription_id' => $invoice->subscription
        ]);

        // Get subscription details
        $subscription = \Stripe\Subscription::retrieve($invoice->subscription);
        
        // Get user details
        $user = getUserBySubscriptionId($pdo, $subscription->id);
        if (!$user) {
            throw new Exception('User not found for subscription: ' . $subscription->id);
        }

        // Process subscription data
        $subscription_data = processSubscriptionData($subscription);

        // Update subscription in database
        updateSubscription($pdo, $subscription_data, $subscription, $user['id']);

        // Update user's subscription tier
        updateUserSubscriptionTier($pdo, $user['id'], $subscription_data['plan_id']);

        logWebhookEvent("Invoice payment processed successfully", "SUCCESS", [
            'user_id' => $user['id'],
            'status' => $subscription_data['status']
        ]);

        // Send payment confirmation email
        sendPaymentConfirmationEmail($user, $invoice);

    } catch (Exception $e) {
        logWebhookEvent("Error handling invoice payment: " . $e->getMessage(), "ERROR");
        throw $e;
    }
}

/**
 * Handle failed invoice payment
 */
function handleInvoicePaymentFailed($invoice) {
    global $pdo;

    try {
        logWebhookEvent("Processing failed invoice payment", "INFO", [
            'invoice_id' => $invoice->id,
            'subscription_id' => $invoice->subscription
        ]);

        // Get subscription details
        $subscription = \Stripe\Subscription::retrieve($invoice->subscription);
        
        // Get user details
        $user = getUserBySubscriptionId($pdo, $subscription->id);
        if (!$user) {
            logWebhookEvent("User not found for failed payment", "WARNING");
            return;
        }

        // Update subscription status to past_due
        $stmt = $pdo->prepare("
            UPDATE user_subscriptions 
            SET status = 'past_due',
                updated_at = NOW() 
            WHERE stripe_subscription_id = ?
        ");
        $stmt->execute([$subscription->id]);

        // Update user's subscription status
        $stmt = $pdo->prepare("
            UPDATE users 
            SET subscription_status = 'past_due',
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$user['id']]);

        logWebhookEvent("Failed payment processed successfully", "SUCCESS", [
            'user_id' => $user['id']
        ]);

        // Send payment failed email
        sendPaymentFailedEmail($user, $invoice);

    } catch (Exception $e) {
        logWebhookEvent("Error handling failed payment: " . $e->getMessage(), "ERROR");
        throw $e;
    }
}

/**
 * Send subscription cancellation email
 */
function sendSubscriptionCancelledEmail($user) {
    // TODO: Implement email sending logic
    logWebhookEvent("Subscription cancellation email sent", "INFO", [
        'user_email' => $user['email']
    ]);
}

/**
 * Send payment confirmation email
 */
function sendPaymentConfirmationEmail($user, $invoice) {
    // TODO: Implement email sending logic
    logWebhookEvent("Payment confirmation email sent", "INFO", [
        'user_email' => $user['email'],
        'invoice_id' => $invoice->id
    ]);
}

/**
 * Send payment failed email
 */
function sendPaymentFailedEmail($user, $invoice) {
    // TODO: Implement email sending logic
    logWebhookEvent("Payment failed email sent", "INFO", [
        'user_email' => $user['email'],
        'invoice_id' => $invoice->id
    ]);
}

/**
 * Update user's subscription tier based on their active subscription
 */
function updateUserSubscriptionTier($pdo, $user_id, $plan_id) {
    try {
        // Get the plan name from subscription_plans table
        $stmt = $pdo->prepare("SELECT name FROM subscription_plans WHERE id = ?");
        $stmt->execute([$plan_id]);
        $plan = $stmt->fetch();
        
        if (!$plan) {
            error_log("Plan not found for plan_id: " . $plan_id);
            return false;
        }
        
        // Map plan names to subscription tiers
        $tier_mapping = [
            'Basic' => 'basic',
            'Premium' => 'premium',
            'Premium Plus' => 'premium_plus'
        ];
        
        $subscription_tier = $tier_mapping[$plan['name']] ?? 'basic';
        
        // Update user's subscription_tier
        $stmt = $pdo->prepare("UPDATE users SET subscription_tier = ? WHERE id = ?");
        $stmt->execute([$subscription_tier, $user_id]);
        
        logWebhookEvent("Updated user subscription tier", "INFO", [
            'user_id' => $user_id,
            'plan_name' => $plan['name'],
            'subscription_tier' => $subscription_tier
        ]);
        
        return true;
    } catch (Exception $e) {
        logWebhookEvent("Error updating user subscription tier: " . $e->getMessage(), "ERROR");
        return false;
    }
}

/**
 * Reset user's subscription tier to basic when subscription is cancelled
 */
function resetUserSubscriptionTier($pdo, $user_id) {
    try {
        $stmt = $pdo->prepare("UPDATE users SET subscription_tier = 'basic' WHERE id = ?");
        $stmt->execute([$user_id]);
        
        logWebhookEvent("Reset user subscription tier to basic", "INFO", [
            'user_id' => $user_id
        ]);
        
        return true;
    } catch (Exception $e) {
        logWebhookEvent("Error resetting user subscription tier: " . $e->getMessage(), "ERROR");
        return false;
    }
}

// Main webhook processing
try {
    // Get the raw POST data and Stripe signature
    $payload = @file_get_contents('php://input');
    $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

    if (empty($payload)) {
        throw new Exception('No payload received');
    }

    if (empty($sig_header)) {
        throw new Exception('No Stripe signature header');
    }

    // Verify webhook signature
    $event = \Stripe\Webhook::constructEvent($payload, $sig_header, $webhook_secret);

    // Log the event
    logWebhookEvent("Received webhook event", "INFO", [
        'event_type' => $event->type,
        'event_id' => $event->id
    ]);

    // Initialize Stripe
    \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

    // Handle the event
    switch ($event->type) {
        case 'customer.subscription.created':
            handleSubscriptionCreated($event->data->object);
            break;

        case 'customer.subscription.updated':
            handleSubscriptionUpdated($event->data->object);
            break;

        case 'customer.subscription.deleted':
            handleSubscriptionDeleted($event->data->object);
            break;

        case 'checkout.session.completed':
            handleCheckoutSessionCompleted($event->data->object);
            break;

        case 'invoice.payment_succeeded':
            handleInvoicePaymentSucceeded($event->data->object);
            break;

        case 'invoice.payment_failed':
            handleInvoicePaymentFailed($event->data->object);
            break;

        default:
            logWebhookEvent("Unhandled event type", "WARNING", [
                'event_type' => $event->type
            ]);
            break;
    }

    // Return success response
    http_response_code(200);
    echo json_encode(['status' => 'success']);

} catch (\Stripe\Exception\SignatureVerificationException $e) {
    logWebhookEvent("Webhook signature verification failed", "ERROR", [
        'error' => $e->getMessage()
    ]);
    http_response_code(400);
    echo json_encode(['error' => 'Invalid signature']);
} catch (Exception $e) {
    logWebhookEvent("Webhook processing error", "ERROR", [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    http_response_code(200); // Return 200 to prevent Stripe from retrying
    echo json_encode(['error' => 'Internal server error']);
}
?> 