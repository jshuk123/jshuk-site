<?php
/**
 * Fix Subscription Migration - Simplified Version
 * 
 * This script fixes the subscription setup issues step by step
 */

require_once '../config/config.php';

echo "🔧 Fixing Subscription Migration Issues...\n\n";

$success_count = 0;
$error_count = 0;

// Step 1: Drop the problematic subscription_plans table if it exists
echo "📋 Step 1: Dropping existing subscription_plans table...\n";
try {
    $pdo->exec("DROP TABLE IF EXISTS subscription_plans");
    echo "  ✅ Success\n";
    $success_count++;
} catch (PDOException $e) {
    echo "  ❌ Error: " . $e->getMessage() . "\n";
    $error_count++;
}
echo "\n";

// Step 2: Create the subscription_plans table with correct column names
echo "📋 Step 2: Creating subscription_plans table with correct columns...\n";
$create_table_sql = "CREATE TABLE subscription_plans (
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
)";

try {
    $pdo->exec($create_table_sql);
    echo "  ✅ Success\n";
    $success_count++;
} catch (PDOException $e) {
    echo "  ❌ Error: " . $e->getMessage() . "\n";
    $error_count++;
}
echo "\n";

// Step 3: Insert default subscription plans without status column
echo "📋 Step 3: Inserting default subscription plans...\n";
$insert_plans_sql = "INSERT INTO subscription_plans (name, price, annual_price, description, features, image_limit, testimonial_limit) VALUES
('Basic', 0.00, 0.00, 'Free plan for basic business listings', '[\"Basic business profile\", \"Contact information\", \"Business description\"]', 3, 2),
('Premium', 9.99, 99.99, 'Enhanced visibility and features', '[\"Priority search placement\", \"Unlimited images\", \"Unlimited testimonials\", \"WhatsApp integration\"]', NULL, NULL),
('Elite', 19.99, 199.99, 'Maximum exposure and premium features', '[\"Top search placement\", \"Featured in newsletter\", \"Premium WhatsApp status\", \"Priority support\", \"Analytics dashboard\"]', NULL, NULL)";

try {
    $pdo->exec($insert_plans_sql);
    echo "  ✅ Success\n";
    $success_count++;
} catch (PDOException $e) {
    echo "  ❌ Error: " . $e->getMessage() . "\n";
    $error_count++;
}
echo "\n";

// Step 4: Create other tables if they don't exist
echo "📋 Step 4: Creating other required tables...\n";

$tables = [
    'user_subscriptions' => "CREATE TABLE IF NOT EXISTS user_subscriptions (
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
    )",
    
    'advertising_slots' => "CREATE TABLE IF NOT EXISTS advertising_slots (
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
    )",
    
    'advertising_bookings' => "CREATE TABLE IF NOT EXISTS advertising_bookings (
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
];

foreach ($tables as $table_name => $sql) {
    try {
        $pdo->exec($sql);
        echo "  ✅ {$table_name} table created successfully\n";
        $success_count++;
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'already exists') !== false) {
            echo "  ⚠️  {$table_name} table already exists\n";
            $success_count++;
        } else {
            echo "  ❌ Error creating {$table_name}: " . $e->getMessage() . "\n";
            $error_count++;
        }
    }
}
echo "\n";

// Step 5: Insert default advertising slots
echo "📋 Step 5: Inserting default advertising slots...\n";
$insert_slots_sql = "INSERT INTO advertising_slots (name, description, monthly_price, annual_price, max_slots, position, status) VALUES
('Top Homepage Banner', 'Premium banner at the top of the homepage for maximum visibility', 29.99, 299.99, 1, 'header', 'active'),
('Sidebar Featured', 'Featured listing in the sidebar on all pages', 19.99, 199.99, 3, 'sidebar', 'active'),
('Footer Banner', 'Banner advertisement in the footer area', 14.99, 149.99, 2, 'footer', 'active')
ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP";

try {
    $pdo->exec($insert_slots_sql);
    echo "  ✅ Success\n";
    $success_count++;
} catch (PDOException $e) {
    echo "  ❌ Error: " . $e->getMessage() . "\n";
    $error_count++;
}
echo "\n";

echo "🎉 Migration completed!\n";
echo "✅ Successful operations: {$success_count}\n";
echo "❌ Errors: {$error_count}\n\n";

if ($error_count === 0) {
    echo "🎯 All subscription tables are now properly set up!\n";
    echo "📝 You can now test the subscription system.\n";
} else {
    echo "⚠️  Some operations failed. Please check the errors above.\n";
}

echo "\n";
?> 