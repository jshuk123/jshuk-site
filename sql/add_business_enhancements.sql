-- Add missing fields to businesses table for enhanced functionality
-- This script adds fields that might be missing for the new businesses.php design

-- Add tagline field if it doesn't exist
ALTER TABLE businesses ADD COLUMN IF NOT EXISTS tagline VARCHAR(255) DEFAULT NULL AFTER description;

-- Add location field if it doesn't exist (for city/area display)
ALTER TABLE businesses ADD COLUMN IF NOT EXISTS location VARCHAR(100) DEFAULT NULL AFTER address;

-- Add business_hours_summary field if it doesn't exist (for quick display)
ALTER TABLE businesses ADD COLUMN IF NOT EXISTS business_hours_summary VARCHAR(100) DEFAULT NULL AFTER opening_hours;

-- Add is_pinned field if it doesn't exist (for premium+ businesses)
ALTER TABLE businesses ADD COLUMN IF NOT EXISTS is_pinned TINYINT(1) DEFAULT 0 AFTER is_featured;

-- Add is_elite field if it doesn't exist (for premium+ businesses)
ALTER TABLE businesses ADD COLUMN IF NOT EXISTS is_elite TINYINT(1) DEFAULT 0 AFTER is_pinned;

-- Add indexes for better performance
ALTER TABLE businesses ADD INDEX IF NOT EXISTS idx_views_count (views_count);
ALTER TABLE businesses ADD INDEX IF NOT EXISTS idx_is_elite (is_elite);
ALTER TABLE businesses ADD INDEX IF NOT EXISTS idx_is_pinned (is_pinned);
ALTER TABLE businesses ADD INDEX IF NOT EXISTS idx_location (location);

-- Update existing premium_plus businesses to be marked as elite
UPDATE businesses b 
JOIN users u ON b.user_id = u.id 
SET b.is_elite = 1 
WHERE u.subscription_tier = 'premium_plus';

-- Update existing featured premium businesses to be pinned
UPDATE businesses b 
JOIN users u ON b.user_id = u.id 
SET b.is_pinned = 1 
WHERE b.is_featured = 1 AND u.subscription_tier IN ('premium', 'premium_plus');

-- Create a function to update business hours summary from opening_hours JSON
DELIMITER //
CREATE FUNCTION IF NOT EXISTS extract_business_hours_summary(opening_hours_json JSON) 
RETURNS VARCHAR(100)
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE summary VARCHAR(100) DEFAULT '';
    DECLARE monday_hours VARCHAR(50) DEFAULT '';
    DECLARE friday_hours VARCHAR(50) DEFAULT '';
    
    -- Extract Monday hours
    SET monday_hours = JSON_UNQUOTE(JSON_EXTRACT(opening_hours_json, '$.monday.open'));
    IF monday_hours IS NOT NULL THEN
        SET monday_hours = CONCAT(monday_hours, '-', JSON_UNQUOTE(JSON_EXTRACT(opening_hours_json, '$.monday.close')));
    END IF;
    
    -- Extract Friday hours
    SET friday_hours = JSON_UNQUOTE(JSON_EXTRACT(opening_hours_json, '$.friday.open'));
    IF friday_hours IS NOT NULL THEN
        SET friday_hours = CONCAT(friday_hours, '-', JSON_UNQUOTE(JSON_EXTRACT(opening_hours_json, '$.friday.close')));
    END IF;
    
    -- Create summary
    IF monday_hours != '' AND friday_hours != '' THEN
        SET summary = CONCAT('Mon-Fri ', monday_hours);
    ELSEIF monday_hours != '' THEN
        SET summary = CONCAT('Mon ', monday_hours);
    END IF;
    
    RETURN summary;
END //
DELIMITER ;

-- Update business_hours_summary for existing businesses
UPDATE businesses 
SET business_hours_summary = extract_business_hours_summary(opening_hours)
WHERE opening_hours IS NOT NULL AND opening_hours != '{}';

-- Create a view for elite businesses
CREATE OR REPLACE VIEW elite_businesses AS
SELECT b.*, c.name as category_name, u.subscription_tier,
       (SELECT COUNT(*) FROM testimonials t WHERE t.business_id = b.id AND t.is_approved = 1) as testimonials_count,
       (SELECT COUNT(*) FROM reviews r WHERE r.business_id = b.id AND r.is_approved = 1) as reviews_count
FROM businesses b 
LEFT JOIN business_categories c ON b.category_id = c.id 
LEFT JOIN business_images bi ON b.id = bi.business_id AND bi.sort_order = 0
LEFT JOIN users u ON b.user_id = u.id
WHERE b.status = 'active' AND u.subscription_tier = 'premium_plus'
ORDER BY b.is_pinned DESC, b.views_count DESC, b.created_at DESC;

-- Create a view for all active businesses with enhanced data
CREATE OR REPLACE VIEW enhanced_businesses AS
SELECT b.*, c.name as category_name, u.subscription_tier,
       (SELECT COUNT(*) FROM testimonials t WHERE t.business_id = b.id AND t.is_approved = 1) as testimonials_count,
       (SELECT COUNT(*) FROM reviews r WHERE r.business_id = b.id AND r.is_approved = 1) as reviews_count,
       bi.file_path as logo_path
FROM businesses b 
LEFT JOIN business_categories c ON b.category_id = c.id 
LEFT JOIN business_images bi ON b.id = bi.business_id AND bi.sort_order = 0
LEFT JOIN users u ON b.user_id = u.id
WHERE b.status = 'active'
ORDER BY 
    CASE u.subscription_tier 
        WHEN 'premium_plus' THEN 1 
        WHEN 'premium' THEN 2 
        ELSE 3 
    END,
    b.is_pinned DESC,
    b.views_count DESC,
    b.created_at DESC; 