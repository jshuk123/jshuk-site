<?php
/**
 * Setup Subscription Plans Script
 * 
 * This script sets up the subscription plans in the database to match
 * the new subscription tier system with proper pricing and features.
 */

require_once '../config/config.php';

echo "<h1>Setup Subscription Plans</h1>";
echo "<p>Setting up subscription plans to match the new tier system...</p>";

try {
    // First, ensure the subscription_plans table exists with the right structure
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS subscription_plans (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(50) NOT NULL,
            description TEXT,
            price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            annual_price DECIMAL(10,2) DEFAULT NULL,
            trial_period_days INT DEFAULT 0,
            image_limit INT DEFAULT NULL,
            testimonial_limit INT DEFAULT NULL,
            features JSON,
            whatsapp_features JSON DEFAULT NULL,
            newsletter_features JSON DEFAULT NULL,
            stripe_product_id VARCHAR(100) DEFAULT NULL,
            stripe_price_id VARCHAR(100) DEFAULT NULL,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
    
    echo "<p>✅ Subscription plans table created/verified</p>";
    
    // Clear existing plans
    $pdo->exec("DELETE FROM subscription_plans");
    echo "<p>✅ Cleared existing plans</p>";
    
    // Insert the new subscription plans
    $plans = [
        [
            'name' => 'Basic',
            'description' => 'Basic plan for small businesses',
            'price' => 0.00,
            'annual_price' => 0.00,
            'trial_period_days' => 0,
            'image_limit' => 1,
            'testimonial_limit' => 0,
            'features' => json_encode([
                'Basic short description',
                'Display email & website only',
                'Standard (non-featured) listing',
                '1 business image'
            ]),
            'whatsapp_features' => null,
            'newsletter_features' => null,
            'stripe_product_id' => 'prod_basic',
            'stripe_price_id' => 'price_basic'
        ],
        [
            'name' => 'Premium',
            'description' => 'Enhanced features for growing businesses',
            'price' => 15.00,
            'annual_price' => 150.00,
            'trial_period_days' => 90,
            'image_limit' => 5,
            'testimonial_limit' => 5,
            'features' => json_encode([
                'Up to 5 gallery images per business',
                'Up to 5 testimonials per business',
                'Homepage carousel visibility',
                'Gold Premium badge',
                'Priority in search results',
                'WhatsApp-ready sign-up graphic',
                'Can offer promotions',
                'Extended business description'
            ]),
            'whatsapp_features' => json_encode([
                'status_feature' => 'monthly',
                'message_button' => true,
                'auto_reminders' => false
            ]),
            'newsletter_features' => json_encode([
                'included' => true,
                'priority' => false
            ]),
            'stripe_product_id' => 'prod_premium',
            'stripe_price_id' => 'price_premium'
        ],
        [
            'name' => 'Premium Plus',
            'description' => 'Ultimate features for established businesses',
            'price' => 30.00,
            'annual_price' => 300.00,
            'trial_period_days' => 90,
            'image_limit' => null, // unlimited
            'testimonial_limit' => null, // unlimited
            'features' => json_encode([
                'Unlimited gallery images per business',
                'Unlimited testimonials per business',
                'Pinned in search results',
                'Animated glow/border on listings',
                'Top Pick/Elite ribbon',
                'Access to beta features',
                'Included in WhatsApp highlight messages',
                'Full detailed business description (no word limit)',
                'VIP access to B2B networking lunches and events'
            ]),
            'whatsapp_features' => json_encode([
                'status_feature' => 'weekly',
                'message_button' => true,
                'auto_reminders' => true
            ]),
            'newsletter_features' => json_encode([
                'included' => true,
                'priority' => true
            ]),
            'stripe_product_id' => 'prod_premium_plus',
            'stripe_price_id' => 'price_premium_plus'
        ]
    ];
    
    $stmt = $pdo->prepare("
        INSERT INTO subscription_plans (
            name, description, price, annual_price, trial_period_days,
            image_limit, testimonial_limit, features, whatsapp_features,
            newsletter_features, stripe_product_id, stripe_price_id
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    foreach ($plans as $plan) {
        $stmt->execute([
            $plan['name'],
            $plan['description'],
            $plan['price'],
            $plan['annual_price'],
            $plan['trial_period_days'],
            $plan['image_limit'],
            $plan['testimonial_limit'],
            $plan['features'],
            $plan['whatsapp_features'],
            $plan['newsletter_features'],
            $plan['stripe_product_id'],
            $plan['stripe_price_id']
        ]);
        
        echo "<p>✅ Added plan: {$plan['name']} - £{$plan['price']}/month</p>";
    }
    
    // Ensure users table has subscription_tier column
    $pdo->exec("
        ALTER TABLE users 
        ADD COLUMN IF NOT EXISTS subscription_tier 
        ENUM('basic', 'premium', 'premium_plus') 
        NOT NULL DEFAULT 'basic'
    ");
    
    echo "<p>✅ Users table subscription_tier column verified</p>";
    
    // Update existing users to have basic tier if not set
    $pdo->exec("UPDATE users SET subscription_tier = 'basic' WHERE subscription_tier IS NULL");
    echo "<p>✅ Updated existing users to basic tier</p>";
    
    echo "<h2>✅ Subscription Plans Setup Complete!</h2>";
    echo "<p>The following plans are now available:</p>";
    
    // Display the plans
    $stmt = $pdo->query("SELECT * FROM subscription_plans ORDER BY price ASC");
    $plans = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin-top: 20px;'>";
    echo "<tr><th>Plan</th><th>Price</th><th>Annual Price</th><th>Trial</th><th>Images</th><th>Testimonials</th></tr>";
    
    foreach ($plans as $plan) {
        $images = $plan['image_limit'] === null ? '∞' : $plan['image_limit'];
        $testimonials = $plan['testimonial_limit'] === null ? '∞' : $plan['testimonial_limit'];
        
        echo "<tr>";
        echo "<td><strong>{$plan['name']}</strong></td>";
        echo "<td>£{$plan['price']}/month</td>";
        echo "<td>£{$plan['annual_price']}/year</td>";
        echo "<td>{$plan['trial_period_days']} days</td>";
        echo "<td>{$images}</td>";
        echo "<td>{$testimonials}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>Next Steps:</h3>";
    echo "<ul>";
    echo "<li><a href='/payment/setup_stripe_products.php' target='_blank'>Setup Stripe Products</a> - Create the actual Stripe products and prices</li>";
    echo "<li><a href='/users/dashboard.php' target='_blank'>Test Dashboard</a> - Check the subscription upgrade options</li>";
    echo "<li><a href='/businesses.php' target='_blank'>Test Business Listings</a> - See premium features in action</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<p><small>Setup script completed.</small></p>";
?> 