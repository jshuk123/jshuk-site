-- Add subscription_tier column to businesses table
ALTER TABLE businesses ADD COLUMN IF NOT EXISTS subscription_tier ENUM('basic', 'premium', 'premium_plus') NOT NULL DEFAULT 'basic';

-- Add index for better performance on tier-based queries
ALTER TABLE businesses ADD INDEX idx_subscription_tier (subscription_tier);

-- Update existing businesses to have basic tier by default
UPDATE businesses SET subscription_tier = 'basic' WHERE subscription_tier IS NULL;

-- Add subscription_tier to the composite index for featured businesses
ALTER TABLE businesses DROP INDEX IF EXISTS idx_status_featured;
ALTER TABLE businesses ADD INDEX idx_status_featured_tier (status, is_featured, subscription_tier);

-- Create a view for premium and premium_plus businesses for homepage display
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
    b.created_at DESC; 