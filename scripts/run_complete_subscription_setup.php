<?php
/**
 * Complete Subscription Setup Migration
 * 
 * This script safely sets up all required database tables and columns for the JShuk
 * subscription and advertising platform. It includes error handling and progress reporting.
 */

require_once '../config/config.php';

echo "🚀 Starting Complete Subscription Setup Migration...\n\n";

$migration_steps = [
    'users_table_updates' => [
        'description' => 'Adding Stripe and subscription fields to users table',
        'sql' => [
            "ALTER TABLE users ADD COLUMN stripe_customer_id VARCHAR(255) DEFAULT NULL COMMENT 'Stripe customer ID for payment processing'",
            "ALTER TABLE users ADD COLUMN subscription_status VARCHAR(50) DEFAULT NULL COMMENT 'User subscription status'",
            "ALTER TABLE users ADD COLUMN subscription_plan_id INT DEFAULT NULL COMMENT 'Reference to subscription_plans table'",
            "ALTER TABLE users ADD COLUMN current_period_end DATETIME DEFAULT NULL COMMENT 'When current subscription period ends'"
        ]
    ],
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
    'user_subscriptions_table' => [
        'description' => 'Creating user_subscriptions table',
        'sql' => [
            "CREATE TABLE IF NOT EXISTS user_subscriptions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL COMMENT 'Reference to users table',
                plan_id INT NOT NULL COMMENT 'Reference to subscription_plans table',
                stripe_subscription_id VARCHAR(255) COMMENT 'Stripe subscription ID',
                stripe_customer_id VARCHAR(255) COMMENT 'Stripe customer ID',
                status VARCHAR(50) DEFAULT 'active' COMMENT 'active, trialing, past_due, canceled, incomplete, incomplete_expired, unpaid',
                current_period_start DATETIME COMMENT 'Start of current billing period',
                current_period_end DATETIME COMMENT 'End of current billing period',
                trial_start DATETIME COMMENT 'Start of trial period',
                trial_end DATETIME COMMENT 'End of trial period',
                canceled_at DATETIME COMMENT 'When subscription was canceled',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_user_id (user_id),
                INDEX idx_status (status),
                INDEX idx_current_period_end (current_period_end)
            )"
        ]
    ],
    'advertising_slots_table' => [
        'description' => 'Creating advertising_slots table',
        'sql' => [
            "CREATE TABLE IF NOT EXISTS advertising_slots (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL COMMENT 'Slot name (e.g., Top Banner, Sidebar Ad)',
                description TEXT COMMENT 'Slot description and benefits',
                monthly_price DECIMAL(10,2) NOT NULL COMMENT 'Monthly price in GBP',
                annual_price DECIMAL(10,2) DEFAULT NULL COMMENT 'Annual price in GBP',
                current_slots INT DEFAULT 0 COMMENT 'Number of slots currently occupied',
                max_slots INT DEFAULT 1 COMMENT 'Maximum number of slots available',
                duration_days INT DEFAULT 30 COMMENT 'How long the ad will run (days)',
                position VARCHAR(50) COMMENT 'Where the ad appears (header, sidebar, footer, etc.)',
                status VARCHAR(20) DEFAULT 'active' COMMENT 'active, inactive',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_status (status),
                INDEX idx_position (position)
            )"
        ]
    ],
    'advertising_bookings_table' => [
        'description' => 'Creating advertising_bookings table',
        'sql' => [
            "CREATE TABLE IF NOT EXISTS advertising_bookings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL COMMENT 'User who booked the slot',
                slot_id INT NOT NULL COMMENT 'Reference to advertising_slots table',
                stripe_subscription_id VARCHAR(255) COMMENT 'Stripe subscription ID for the booking',
                status VARCHAR(50) DEFAULT 'active' COMMENT 'active, canceled, expired',
                start_date DATETIME COMMENT 'When the ad starts running',
                end_date DATETIME COMMENT 'When the ad stops running',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_user_id (user_id),
                INDEX idx_slot_id (slot_id),
                INDEX idx_status (status),
                INDEX idx_dates (start_date, end_date)
            )"
        ]
    ],
    'default_data' => [
        'description' => 'Inserting default subscription plans and advertising slots',
        'sql' => [
            "INSERT INTO subscription_plans (name, price, annual_price, description, features, image_limit, testimonial_limit) VALUES
            ('Basic', 0.00, 0.00, 'Free plan for basic business listings', '[\"Basic business profile\", \"Contact information\", \"Business description\"]', 3, 2),
            ('Premium', 9.99, 99.99, 'Enhanced visibility and features', '[\"Priority search placement\", \"Unlimited images\", \"Unlimited testimonials\", \"WhatsApp integration\"]', NULL, NULL),
            ('Elite', 19.99, 199.99, 'Maximum exposure and premium features', '[\"Top search placement\", \"Featured in newsletter\", \"Premium WhatsApp status\", \"Priority support\", \"Analytics dashboard\"]', NULL, NULL)
            ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP",
            
            "INSERT IGNORE INTO advertising_slots (name, description, monthly_price, annual_price, max_slots, position, status) VALUES
            ('Top Homepage Banner', 'Premium banner at the top of the homepage for maximum visibility', 29.99, 299.99, 1, 'header', 'active'),
            ('Sidebar Featured', 'Featured listing in the sidebar on all pages', 19.99, 199.99, 3, 'sidebar', 'active'),
            ('Footer Banner', 'Banner advertisement in the footer area', 14.99, 149.99, 2, 'footer', 'active')"
        ]
    ],
    'indexes' => [
        'description' => 'Adding performance indexes',
        'sql' => [
            "CREATE INDEX IF NOT EXISTS idx_users_stripe_customer ON users(stripe_customer_id)",
            "CREATE INDEX IF NOT EXISTS idx_users_subscription_status ON users(subscription_status)",
            "CREATE INDEX IF NOT EXISTS idx_users_subscription_plan ON users(subscription_plan_id)"
        ]
    ],
    'final_updates' => [
        'description' => 'Final updates and cleanup',
        'sql' => [
            "UPDATE users SET status = 'active' WHERE status IS NULL",
            "ALTER TABLE users MODIFY COLUMN status VARCHAR(20) DEFAULT 'active' COMMENT 'User account status: active, inactive, banned'"
        ]
    ]
];

$success_count = 0;
$error_count = 0;

foreach ($migration_steps as $step_name => $step) {
    echo "📋 {$step['description']}...\n";
    
    foreach ($step['sql'] as $sql) {
        try {
            $pdo->exec($sql);
            echo "  ✅ Success\n";
            $success_count++;
        } catch (PDOException $e) {
            // Check if it's a "column already exists" error
            if (strpos($e->getMessage(), 'Duplicate column name') !== false || 
                strpos($e->getMessage(), 'Table') !== false && strpos($e->getMessage(), 'already exists') !== false) {
                echo "  ⚠️  Skipped (already exists)\n";
                $success_count++;
            } else {
                echo "  ❌ Error: " . $e->getMessage() . "\n";
                $error_count++;
            }
        }
    }
    echo "\n";
}

echo "🎉 Migration completed!\n";
echo "✅ Successful operations: {$success_count}\n";
echo "❌ Errors: {$error_count}\n\n";

if ($error_count === 0) {
    echo "🎯 All database tables and columns are now set up for the JShuk subscription system!\n";
    echo "📝 Next steps:\n";
    echo "   1. Test the subscription flow\n";
    echo "   2. Configure Stripe Price IDs in the subscription_plans table\n";
    echo "   3. Test the advertising slot booking system\n";
} else {
    echo "⚠️  Some operations failed. Please check the errors above and run the migration again.\n";
}

echo "\n";
?> 