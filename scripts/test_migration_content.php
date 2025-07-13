<?php
/**
 * Test script to verify migration content
 */

require_once '../config/config.php';

echo "🔍 Testing Migration Script Content...\n\n";

$migration_steps = [
    'subscription_plans_table' => [
        'description' => 'Creating subscription_plans table',
        'sql' => [
            "CREATE TABLE IF NOT EXISTS subscription_plans (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL COMMENT 'Plan name (e.g., Basic, Premium, Elite)',
                price DECIMAL(10,2) NOT NULL COMMENT 'Monthly price in GBP',
                annual_price DECIMAL(10,2) DEFAULT NULL COMMENT 'Annual price in GBP (for discounts)',
                billing_interval VARCHAR(20) NOT NULL DEFAULT 'monthly' COMMENT 'billing interval: monthly, yearly',
                description TEXT COMMENT 'Plan description',
                features JSON COMMENT 'Plan features as JSON array',
                image_limit INT DEFAULT NULL COMMENT 'Number of images allowed (NULL = unlimited)',
                testimonial_limit INT DEFAULT NULL COMMENT 'Number of testimonials allowed (NULL = unlimited)',
                whatsapp_features JSON COMMENT 'WhatsApp integration features',
                newsletter_features JSON COMMENT 'Newsletter features',
                trial_period_days INT DEFAULT 0 COMMENT 'Free trial period in days',
                stripe_price_id VARCHAR(255) COMMENT 'Stripe Price ID for monthly billing',
                stripe_annual_price_id VARCHAR(255) COMMENT 'Stripe Price ID for annual billing',
                status VARCHAR(20) DEFAULT 'active' COMMENT 'active, inactive',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_status (status),
                INDEX idx_price (price)
            )"
        ]
    ],
    'default_data' => [
        'description' => 'Inserting default subscription plans',
        'sql' => [
            "INSERT INTO subscription_plans (name, price, annual_price, description, features, image_limit, testimonial_limit) VALUES
            ('Basic', 0.00, 0.00, 'Free plan for basic business listings', '[\"Basic business profile\", \"Contact information\", \"Business description\"]', 3, 2),
            ('Premium', 9.99, 99.99, 'Enhanced visibility and features', '[\"Priority search placement\", \"Unlimited images\", \"Unlimited testimonials\", \"WhatsApp integration\"]', NULL, NULL),
            ('Elite', 19.99, 199.99, 'Maximum exposure and premium features', '[\"Top search placement\", \"Featured in newsletter\", \"Premium WhatsApp status\", \"Priority support\", \"Analytics dashboard\"]', NULL, NULL)
            ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP"
        ]
    ]
];

foreach ($migration_steps as $step_name => $step) {
    echo "📋 {$step['description']}...\n";
    
    foreach ($step['sql'] as $sql) {
        echo "  SQL: " . substr($sql, 0, 100) . "...\n";
        
        // Check for the problematic keywords
        if (strpos($sql, 'interval VARCHAR') !== false) {
            echo "  ❌ Found old 'interval VARCHAR' - this should be 'billing_interval'\n";
        } else {
            echo "  ✅ No 'interval VARCHAR' found\n";
        }
        
        if (strpos($sql, 'INSERT INTO subscription_plans') !== false && strpos($sql, ', status)') !== false) {
            echo "  ❌ Found INSERT with status column\n";
        } else {
            echo "  ✅ INSERT statement looks correct\n";
        }
    }
    echo "\n";
}

echo "🎯 Test completed!\n";
?> 