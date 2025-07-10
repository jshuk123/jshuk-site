-- Disable foreign key checks
SET FOREIGN_KEY_CHECKS = 0;

-- Update subscription plans table to add new columns
ALTER TABLE subscription_plans 
ADD COLUMN IF NOT EXISTS image_limit INT DEFAULT NULL,
ADD COLUMN IF NOT EXISTS testimonial_limit INT DEFAULT NULL,
ADD COLUMN IF NOT EXISTS annual_price DECIMAL(10,2) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS trial_period_days INT DEFAULT NULL,
ADD COLUMN IF NOT EXISTS whatsapp_features JSON DEFAULT NULL,
ADD COLUMN IF NOT EXISTS newsletter_features JSON DEFAULT NULL,
ADD COLUMN IF NOT EXISTS stripe_product_id VARCHAR(100) NOT NULL,
ADD COLUMN IF NOT EXISTS stripe_price_id VARCHAR(100) NOT NULL;

-- Create user subscriptions table if not exists
CREATE TABLE IF NOT EXISTS user_subscriptions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    plan_id INT NOT NULL,
    stripe_subscription_id VARCHAR(100) NOT NULL,
    stripe_customer_id VARCHAR(100) NOT NULL,
    status ENUM('active', 'inactive', 'cancelled', 'past_due') DEFAULT 'inactive',
    current_period_start TIMESTAMP NULL DEFAULT NULL,
    current_period_end TIMESTAMP NULL DEFAULT NULL,
    end_date TIMESTAMP NULL DEFAULT NULL,
    cancel_at_period_end BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (plan_id) REFERENCES subscription_plans(id)
);

-- Delete existing subscription plans
DELETE FROM subscription_plans;

-- Insert new subscription plans
INSERT INTO subscription_plans (
    name, 
    description, 
    price, 
    annual_price,
    trial_period_days,
    image_limit,
    testimonial_limit,
    features,
    whatsapp_features,
    newsletter_features,
    stripe_product_id,
    stripe_price_id
) VALUES
(
    'Basic',
    'Basic plan for small businesses',
    0.00,
    0.00,
    0,
    1,
    0,
    JSON_ARRAY(
        'Basic short description',
        'Display email & website only',
        'Standard (non-featured) listing'
    ),
    NULL,
    NULL,
    'prod_basic',
    'price_basic'
),
(
    'Premium',
    'Enhanced features for growing businesses',
    7.50,
    70.00,
    90, -- 3 months free trial
    5,
    5,
    JSON_ARRAY(
        'Extended business description',
        'Display email, website, phone number & address',
        'WhatsApp message button on listing',
        'Access to private B2B forum and networking events'
    ),
    JSON_OBJECT(
        'status_feature', 'monthly',
        'message_button', true,
        'auto_reminders', false
    ),
    JSON_OBJECT(
        'included', true,
        'priority', false
    ),
    'prod_premium',
    'price_premium'
),
(
    'Premium Plus',
    'Ultimate features for established businesses',
    15.00,
    140.00,
    90, -- 3 months free trial
    NULL, -- Unlimited images
    NULL, -- Unlimited testimonials
    JSON_ARRAY(
        'Full detailed business description (no word limit)',
        'WhatsApp message button + automatic reminders',
        'Priority featured placement',
        'VIP access to B2B networking lunches and events'
    ),
    JSON_OBJECT(
        'status_feature', 'weekly',
        'message_button', true,
        'auto_reminders', true
    ),
    JSON_OBJECT(
        'included', true,
        'priority', true
    ),
    'prod_premium_plus',
    'price_premium_plus'
);

-- Create advertising slots table if not exists
CREATE TABLE IF NOT EXISTS advertising_slots (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    monthly_price DECIMAL(10,2) NOT NULL,
    annual_price DECIMAL(10,2) NOT NULL,
    max_slots INT NOT NULL,
    current_slots INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create user advertising slots table if not exists
CREATE TABLE IF NOT EXISTS user_advertising_slots (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    slot_id INT NOT NULL,
    start_date DATETIME NOT NULL,
    end_date DATETIME NOT NULL,
    payment_status ENUM('pending', 'paid', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (slot_id) REFERENCES advertising_slots(id)
);

-- Add stripe_customer_id to users table if not exists
ALTER TABLE users ADD COLUMN IF NOT EXISTS stripe_customer_id VARCHAR(100) NULL;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1; 