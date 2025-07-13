<?php
// Stripe API configuration - Keys are defined in config.php
// Initialize Stripe
require_once __DIR__ . '/../vendor/autoload.php';
\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

// Subscription plan IDs
define('STRIPE_BASIC_PLAN_ID', 'prod_SGi1tu44EvsEt7');
define('STRIPE_PLUS_PLAN_ID', 'prod_SGi1nDM0Fje5L7');
define('STRIPE_PREMIUM_PLAN_ID', 'prod_SGi1qlGaTYpN0M');

// Helper function to get plan details
function getSubscriptionPlans() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM subscription_plans ORDER BY price ASC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Helper function to get user's active subscription
function getUserSubscription($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT us.*, sp.* 
        FROM user_subscriptions us 
        JOIN subscription_plans sp ON us.plan_id = sp.id 
        WHERE us.user_id = ? AND us.status = 'active'
        ORDER BY us.created_at DESC 
        LIMIT 1
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Helper function to check if user has active subscription
function hasActiveSubscription($user_id) {
    global $pdo;
    try {
        // Check if table exists
        $stmt = $pdo->prepare("SHOW TABLES LIKE 'user_subscriptions'");
        $stmt->execute();
        if (!$stmt->fetch()) {
            return false;
        }
        
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM user_subscriptions 
            WHERE user_id = ? AND status = 'active'
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        return false;
    }
}

// Helper function to get user's subscription limits
function getUserSubscriptionLimits($user_id) {
    global $pdo;
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
            SELECT sp.image_limit, sp.testimonial_limit
            FROM user_subscriptions us
            JOIN subscription_plans sp ON us.plan_id = sp.id
            WHERE us.user_id = ? AND us.status = 'active'
            ORDER BY us.created_at DESC
            LIMIT 1
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return null;
    }
}

// Helper function to get user's WhatsApp features
function getUserWhatsAppFeatures($user_id) {
    global $pdo;
    try {
        // Check if required tables exist
        $stmt = $pdo->prepare("SHOW TABLES LIKE 'user_subscriptions'");
        $stmt->execute();
        if (!$stmt->fetch()) {
            return [
                'status_feature' => 'none',
                'enabled' => false
            ];
        }
        
        $stmt = $pdo->prepare("SHOW TABLES LIKE 'subscription_plans'");
        $stmt->execute();
        if (!$stmt->fetch()) {
            return [
                'status_feature' => 'none',
                'enabled' => false
            ];
        }
        
        $stmt = $pdo->prepare("
            SELECT sp.whatsapp_features
            FROM user_subscriptions us
            JOIN subscription_plans sp ON us.plan_id = sp.id
            WHERE us.user_id = ? AND us.status IN ('active', 'trialing')
            ORDER BY us.created_at DESC
            LIMIT 1
        ");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Return default empty array if no features or null value
        if (!$result || $result['whatsapp_features'] === null) {
            return [
                'status_feature' => 'none',
                'enabled' => false
            ];
        }
        
        return json_decode($result['whatsapp_features'], true) ?: [
            'status_feature' => 'none',
            'enabled' => false
        ];
    } catch (PDOException $e) {
        return [
            'status_feature' => 'none',
            'enabled' => false
        ];
    }
}

// Helper function to get user's newsletter features
function getUserNewsletterFeatures($user_id) {
    global $pdo;
    try {
        // Check if required tables exist
        $stmt = $pdo->prepare("SHOW TABLES LIKE 'user_subscriptions'");
        $stmt->execute();
        if (!$stmt->fetch()) {
            return [
                'included' => false,
                'priority' => false
            ];
        }
        
        $stmt = $pdo->prepare("SHOW TABLES LIKE 'subscription_plans'");
        $stmt->execute();
        if (!$stmt->fetch()) {
            return [
                'included' => false,
                'priority' => false
            ];
        }
        
        $stmt = $pdo->prepare("
            SELECT sp.newsletter_features
            FROM user_subscriptions us
            JOIN subscription_plans sp ON us.plan_id = sp.id
            WHERE us.user_id = ? AND us.status IN ('active', 'trialing')
            ORDER BY us.created_at DESC
            LIMIT 1
        ");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Return default empty array if no features or null value
        if (!$result || $result['newsletter_features'] === null) {
            return [
                'included' => false,
                'priority' => false
            ];
        }
        
        return json_decode($result['newsletter_features'], true) ?: [
            'included' => false,
            'priority' => false
        ];
    } catch (PDOException $e) {
        return [
            'included' => false,
            'priority' => false
        ];
    }
} 