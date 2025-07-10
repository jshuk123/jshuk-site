-- Create subscription plans table
CREATE TABLE subscription_plans (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    stripe_product_id VARCHAR(100) NOT NULL,
    stripe_price_id VARCHAR(100) NOT NULL,
    document_limit INT,
    transaction_fee DECIMAL(4,2),
    features JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create user subscriptions table
CREATE TABLE user_subscriptions (
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

-- Insert the subscription plans
INSERT INTO subscription_plans (name, description, price, stripe_product_id, stripe_price_id, document_limit, transaction_fee, features) VALUES
('Basic', 'Perfect for small businesses', 8.99, 'prod_SCBRcg2Cz16ywG', '', 10, 1.5, '["Basic analytics", "Email support", "10 transactions per month", "Standard processing time"]'),
('Plus', 'Great for growing businesses', 32.99, 'prod_SCBe5rhAXqsrkO', '', 50, 1.0, '["Advanced analytics", "Priority support", "50 transactions per month", "Fast processing time", "6 months free subscription"]'),
('Premium', 'For established businesses', 95.99, 'prod_SCBeVvrJVFcGwR', '', NULL, 0.5, '["Custom analytics", "24/7 priority support", "Unlimited transactions", "Instant processing", "6 months free subscription", "Custom integration"]');

-- Add stripe_customer_id to users table if not exists
ALTER TABLE users ADD COLUMN IF NOT EXISTS stripe_customer_id VARCHAR(100) NULL;

-- Add end_date column to user_subscriptions if not exists
ALTER TABLE user_subscriptions ADD COLUMN IF NOT EXISTS end_date TIMESTAMP NULL DEFAULT NULL; 