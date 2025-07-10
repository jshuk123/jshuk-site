<?php
/**
 * Run Subscription Tier Database Updates
 * 
 * This script applies all the necessary database changes for the subscription tier system.
 */

require_once '../config/config.php';

echo "<h1>Running Subscription Tier Database Updates</h1>";
echo "<p>Applying database changes for the subscription tier system...</p>";

try {
    // 1. Add subscription_tier column to businesses table
    echo "<h3>1. Adding subscription_tier column to businesses table...</h3>";
    $pdo->exec("ALTER TABLE businesses ADD COLUMN IF NOT EXISTS subscription_tier ENUM('basic', 'premium', 'premium_plus') NOT NULL DEFAULT 'basic'");
    echo "<p>✅ subscription_tier column added to businesses table</p>";
    
    // 2. Add index for better performance on tier-based queries
    echo "<h3>2. Adding performance indexes...</h3>";
    $pdo->exec("ALTER TABLE businesses ADD INDEX IF NOT EXISTS idx_subscription_tier (subscription_tier)");
    echo "<p>✅ subscription_tier index added</p>";
    
    // 3. Update existing businesses to have basic tier by default
    echo "<h3>3. Setting default tiers for existing businesses...</h3>";
    $result = $pdo->exec("UPDATE businesses SET subscription_tier = 'basic' WHERE subscription_tier IS NULL");
    echo "<p>✅ Updated {$result} businesses to basic tier</p>";
    
    // 4. Add subscription_tier to the composite index for featured businesses
    echo "<h3>4. Updating composite indexes...</h3>";
    try {
        $pdo->exec("ALTER TABLE businesses DROP INDEX idx_status_featured");
    } catch (Exception $e) {
        // Index might not exist, that's okay
    }
    $pdo->exec("ALTER TABLE businesses ADD INDEX IF NOT EXISTS idx_status_featured_tier (status, is_featured, subscription_tier)");
    echo "<p>✅ Updated composite index for status, featured, and tier</p>";
    
    // 5. Create a view for premium and premium_plus businesses for homepage display
    echo "<h3>5. Creating premium businesses view...</h3>";
    $pdo->exec("
        CREATE OR REPLACE VIEW premium_businesses AS
        SELECT b.*, c.name AS category_name 
        FROM businesses b 
        LEFT JOIN business_categories c ON b.category_id = c.id 
        WHERE b.status = 'active' 
        AND b.subscription_tier IN ('premium', 'premium_plus')
        ORDER BY 
            CASE b.subscription_tier 
                WHEN 'premium_plus' THEN 1 
                WHEN 'premium' THEN 2 
                ELSE 3 
            END,
            b.created_at DESC
    ");
    echo "<p>✅ premium_businesses view created</p>";
    
    // 6. Ensure users table has subscription_tier column
    echo "<h3>6. Verifying users table subscription_tier column...</h3>";
    $pdo->exec("
        ALTER TABLE users 
        ADD COLUMN IF NOT EXISTS subscription_tier 
        ENUM('basic', 'premium', 'premium_plus') 
        NOT NULL DEFAULT 'basic'
    ");
    echo "<p>✅ Users table subscription_tier column verified</p>";
    
    // 7. Update existing users to have basic tier if not set
    $result = $pdo->exec("UPDATE users SET subscription_tier = 'basic' WHERE subscription_tier IS NULL");
    echo "<p>✅ Updated {$result} users to basic tier</p>";
    
    // 8. Verify the subscription_plans table exists
    echo "<h3>7. Verifying subscription_plans table...</h3>";
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
    echo "<p>✅ subscription_plans table verified</p>";
    
    // 9. Show current status
    echo "<h3>8. Current Database Status:</h3>";
    
    // Count businesses by tier
    $stmt = $pdo->query("SELECT subscription_tier, COUNT(*) as count FROM businesses GROUP BY subscription_tier");
    $business_tiers = $stmt->fetchAll();
    
    echo "<h4>Businesses by Tier:</h4>";
    echo "<ul>";
    foreach ($business_tiers as $tier) {
        echo "<li><strong>" . ucfirst($tier['subscription_tier']) . ":</strong> {$tier['count']} businesses</li>";
    }
    echo "</ul>";
    
    // Count users by tier
    $stmt = $pdo->query("SELECT subscription_tier, COUNT(*) as count FROM users GROUP BY subscription_tier");
    $user_tiers = $stmt->fetchAll();
    
    echo "<h4>Users by Tier:</h4>";
    echo "<ul>";
    foreach ($user_tiers as $tier) {
        echo "<li><strong>" . ucfirst($tier['subscription_tier']) . ":</strong> {$tier['count']} users</li>";
    }
    echo "</ul>";
    
    // Check subscription plans
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM subscription_plans");
    $plan_count = $stmt->fetchColumn();
    echo "<h4>Subscription Plans:</h4>";
    echo "<p><strong>{$plan_count} plans</strong> configured</p>";
    
    echo "<h2>✅ All Database Updates Completed Successfully!</h2>";
    
    echo "<h3>Next Steps:</h3>";
    echo "<ul>";
    echo "<li><a href='/scripts/setup_subscription_plans.php' target='_blank'>Setup Subscription Plans</a> - Configure the actual subscription plans</li>";
    echo "<li><a href='/scripts/test_premium_features.php' target='_blank'>Test Premium Features</a> - Test the subscription tier system</li>";
    echo "<li><a href='/businesses.php' target='_blank'>View Business Listings</a> - See premium features in action</li>";
    echo "<li><a href='/index.php' target='_blank'>View Homepage</a> - See premium businesses on homepage</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<p><small>Database update script completed.</small></p>";
?> 