-- JShuk Testimonials + Star Ratings System
-- Complete database schema for the new feedback system
-- Version: 1.2

-- =====================================================
-- 1. UPDATE EXISTING TABLES
-- =====================================================

-- Update businesses table to ensure subscription_tier column exists
ALTER TABLE businesses 
ADD COLUMN IF NOT EXISTS subscription_tier ENUM('basic', 'premium', 'premium_plus') NOT NULL DEFAULT 'basic';

-- Add index for subscription tier queries
ALTER TABLE businesses 
ADD INDEX IF NOT EXISTS idx_subscription_tier (subscription_tier);

-- =====================================================
-- 2. DROP DEPENDENT TABLES FIRST (to handle foreign keys)
-- =====================================================

-- Drop tables in reverse dependency order
DROP TABLE IF EXISTS `reviews_log`;
DROP TABLE IF EXISTS `testimonials`;
DROP TABLE IF EXISTS `reviews`;

-- =====================================================
-- 3. CREATE NEW REVIEWS TABLE (Star Ratings Only)
-- =====================================================

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `business_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL CHECK (rating BETWEEN 1 AND 5),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `modified_by_admin` boolean DEFAULT FALSE,
  `submitted_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `business_id` (`business_id`),
  KEY `idx_ip_business` (`ip_address`, `business_id`),
  KEY `idx_submitted_at` (`submitted_at`),
  CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 4. CREATE TESTIMONIALS TABLE
-- =====================================================

CREATE TABLE `testimonials` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `business_id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `testimonial` text NOT NULL,
  `photo_url` varchar(255) DEFAULT NULL,
  `rating` int(11) DEFAULT NULL,
  `status` ENUM('pending', 'approved', 'hidden') DEFAULT 'pending',
  `featured` boolean DEFAULT FALSE,
  `submitted_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `business_id` (`business_id`),
  KEY `idx_status` (`status`),
  KEY `idx_featured` (`featured`),
  KEY `idx_submitted_at` (`submitted_at`),
  CONSTRAINT `testimonials_ibfk_1` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 5. CREATE REVIEWS LOG TABLE (Admin Audit)
-- =====================================================

CREATE TABLE `reviews_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `review_id` int(11) DEFAULT NULL,
  `admin_user` varchar(255) NOT NULL,
  `old_rating` int(11) DEFAULT NULL,
  `new_rating` int(11) DEFAULT NULL,
  `modified_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `review_id` (`review_id`),
  KEY `idx_modified_at` (`modified_at`),
  CONSTRAINT `reviews_log_ibfk_1` FOREIGN KEY (`review_id`) REFERENCES `reviews` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 6. CREATE INDEXES FOR PERFORMANCE
-- =====================================================

-- Reviews table indexes
ALTER TABLE `reviews` ADD INDEX `idx_business_rating` (`business_id`, `rating`);
ALTER TABLE `reviews` ADD INDEX `idx_ip_time_limit` (`ip_address`, `business_id`, `submitted_at`);

-- Testimonials table indexes
ALTER TABLE `testimonials` ADD INDEX `idx_business_status` (`business_id`, `status`);
ALTER TABLE `testimonials` ADD INDEX `idx_featured_status` (`featured`, `status`);

-- =====================================================
-- 7. CREATE VIEWS FOR COMMON QUERIES
-- =====================================================

-- View for business rating statistics
CREATE OR REPLACE VIEW `business_rating_stats` AS
SELECT 
    b.id as business_id,
    b.business_name,
    COUNT(r.id) as total_reviews,
    COALESCE(AVG(r.rating), 0) as average_rating,
    COUNT(CASE WHEN r.rating = 5 THEN 1 END) as five_star,
    COUNT(CASE WHEN r.rating = 4 THEN 1 END) as four_star,
    COUNT(CASE WHEN r.rating = 3 THEN 1 END) as three_star,
    COUNT(CASE WHEN r.rating = 2 THEN 1 END) as two_star,
    COUNT(CASE WHEN r.rating = 1 THEN 1 END) as one_star
FROM businesses b
LEFT JOIN reviews r ON b.id = r.business_id
WHERE b.status = 'active'
GROUP BY b.id, b.business_name;

-- View for approved testimonials with business info
CREATE OR REPLACE VIEW `approved_testimonials` AS
SELECT 
    t.*,
    b.business_name,
    b.subscription_tier
FROM testimonials t
JOIN businesses b ON t.business_id = b.id
WHERE t.status = 'approved'
ORDER BY t.featured DESC, t.submitted_at DESC;

-- =====================================================
-- 8. INSERT SAMPLE DATA (Only if businesses exist)
-- =====================================================

-- Insert sample reviews for testing (only if businesses exist)
INSERT INTO `reviews` (`business_id`, `rating`, `ip_address`, `user_agent`) 
SELECT 
    b.id as business_id,
    FLOOR(3 + RAND() * 3) as rating, -- Random rating between 3-5
    CONCAT('192.168.1.', FLOOR(1 + RAND() * 254)) as ip_address,
    'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36' as user_agent
FROM businesses b 
WHERE b.status = 'active' 
LIMIT 5; -- Only insert for first 5 active businesses

-- Insert sample testimonials for testing (only if businesses exist)
INSERT INTO `testimonials` (`business_id`, `name`, `testimonial`, `rating`, `status`, `featured`) 
SELECT 
    b.id as business_id,
    CASE FLOOR(RAND() * 4)
        WHEN 0 THEN 'Sarah L.'
        WHEN 1 THEN 'David M.'
        WHEN 2 THEN 'Rachel G.'
        ELSE 'Anonymous'
    END as name,
    CASE FLOOR(RAND() * 3)
        WHEN 0 THEN 'Amazing service and great quality! Highly recommend.'
        WHEN 1 THEN 'Very professional and reliable. Will use again.'
        ELSE 'Excellent experience from start to finish.'
    END as testimonial,
    FLOOR(4 + RAND() * 2) as rating, -- Random rating between 4-5
    'approved' as status,
    CASE WHEN RAND() > 0.7 THEN 1 ELSE 0 END as featured -- 30% chance of being featured
FROM businesses b 
WHERE b.status = 'active' 
AND b.subscription_tier IN ('premium', 'premium_plus') -- Only for premium businesses
LIMIT 3; -- Only insert for first 3 premium businesses

-- =====================================================
-- 9. FINAL CLEANUP AND VERIFICATION
-- =====================================================

-- Update existing businesses to have basic tier if not set
UPDATE businesses SET subscription_tier = 'basic' WHERE subscription_tier IS NULL;

-- Verify all tables were created successfully
SELECT 'Database schema updated successfully!' as status; 