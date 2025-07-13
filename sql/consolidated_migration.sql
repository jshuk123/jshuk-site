-- =====================================================
-- JShuk Consolidated Database Migration
-- This file consolidates all table and trigger creation
-- with proper cleanup to prevent conflicts
-- =====================================================

-- Drop all existing triggers first to prevent conflicts
DROP TRIGGER IF EXISTS `update_ad_status_expired`;
DROP TRIGGER IF EXISTS `log_ad_changes`;
DROP TRIGGER IF EXISTS `log_carousel_impression`;
DROP TRIGGER IF EXISTS `update_carousel_ad_expired`;
DROP TRIGGER IF EXISTS `archive_old_lostfound_posts`;

-- Drop tables in reverse dependency order
DROP TABLE IF EXISTS `carousel_analytics_summary`;
DROP TABLE IF EXISTS `carousel_analytics`;
DROP TABLE IF EXISTS `carousel_slides`;
DROP TABLE IF EXISTS `location_mappings`;
DROP TABLE IF EXISTS `carousel_ads`;
DROP TABLE IF EXISTS `ad_stats`;
DROP TABLE IF EXISTS `ads`;
DROP TABLE IF EXISTS `lostfound_claims`;
DROP TABLE IF EXISTS `lostfound_posts`;
DROP TABLE IF EXISTS `lostfound_categories`;
DROP TABLE IF EXISTS `lostfound_locations`;
DROP TABLE IF EXISTS `gemach_donations`;
DROP TABLE IF EXISTS `gemach_testimonials`;
DROP TABLE IF EXISTS `gemach_views`;
DROP TABLE IF EXISTS `gemachim`;
DROP TABLE IF EXISTS `gemach_categories`;
DROP TABLE IF EXISTS `retreat_bookings`;
DROP TABLE IF EXISTS `retreat_reviews`;
DROP TABLE IF EXISTS `retreat_views`;
DROP TABLE IF EXISTS `retreat_availability`;
DROP TABLE IF EXISTS `retreat_tag_relations`;
DROP TABLE IF EXISTS `retreat_tags`;
DROP TABLE IF EXISTS `retreat_amenity_relations`;
DROP TABLE IF EXISTS `retreat_amenities`;
DROP TABLE IF EXISTS `retreats`;
DROP TABLE IF EXISTS `retreat_locations`;
DROP TABLE IF EXISTS `retreat_categories`;
DROP TABLE IF EXISTS `free_stuff_requests`;
DROP TABLE IF EXISTS `classifieds_categories`;
DROP TABLE IF EXISTS `advertising_bookings`;
DROP TABLE IF EXISTS `advertising_slots`;
DROP TABLE IF EXISTS `user_subscriptions`;
DROP TABLE IF EXISTS `subscription_plans`;
DROP TABLE IF EXISTS `reviews_log`;
DROP TABLE IF EXISTS `testimonials`;
DROP TABLE IF EXISTS `reviews`;
DROP TABLE IF EXISTS `contact_inquiries`;
DROP TABLE IF EXISTS `newsletter_subscribers`;
DROP TABLE IF EXISTS `business_gallery`;
DROP TABLE IF EXISTS `category_meta`;
DROP TABLE IF EXISTS `featured_stories`;

-- =====================================================
-- CORE TABLES
-- =====================================================

-- Users table (if not exists)
CREATE TABLE IF NOT EXISTS `users` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `username` varchar(50) NOT NULL,
    `email` varchar(100) NOT NULL,
    `password_hash` varchar(255) NOT NULL,
    `role` enum('user','admin') DEFAULT 'user',
    `subscription_tier` enum('basic','premium','premium_plus') DEFAULT 'basic',
    `subscription_status` enum('active','inactive','cancelled') DEFAULT 'active',
    `subscription_end_date` datetime NULL,
    `is_banned` tinyint(1) DEFAULT 0,
    `ban_reason` text NULL,
    `ban_date` datetime NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `username` (`username`),
    UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Business categories table
CREATE TABLE IF NOT EXISTS `business_categories` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL,
    `description` text,
    `icon` varchar(50) DEFAULT 'fa-store',
    `slug` varchar(100) NOT NULL,
    `sort_order` int(11) DEFAULT 0,
    `is_active` tinyint(1) DEFAULT 1,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Businesses table
CREATE TABLE IF NOT EXISTS `businesses` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `business_name` varchar(200) NOT NULL,
    `description` text,
    `category_id` int(11) NOT NULL,
    `user_id` int(11) NOT NULL,
    `slug` varchar(200) NOT NULL,
    `address` text,
    `phone` varchar(20),
    `email` varchar(100),
    `website` varchar(255),
    `status` enum('pending','active','inactive','rejected') DEFAULT 'pending',
    `is_featured` tinyint(1) DEFAULT 0,
    `featured_until` datetime NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `slug` (`slug`),
    KEY `category_id` (`category_id`),
    KEY `user_id` (`user_id`),
    KEY `status` (`status`),
    FOREIGN KEY (`category_id`) REFERENCES `business_categories` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Business images table
CREATE TABLE IF NOT EXISTS `business_images` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `business_id` int(11) NOT NULL,
    `file_path` varchar(500) NOT NULL,
    `caption` varchar(255),
    `sort_order` int(11) DEFAULT 0,
    `is_main` tinyint(1) DEFAULT 0,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `business_id` (`business_id`),
    KEY `sort_order` (`sort_order`),
    FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- ADVERTISING SYSTEM
-- =====================================================

-- Ads table
CREATE TABLE IF NOT EXISTS `ads` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `ad_name` varchar(200) NOT NULL,
    `ad_image` varchar(500) NOT NULL,
    `ad_url` varchar(500),
    `zone` enum('header','sidebar','footer','homepage') NOT NULL,
    `category_id` int(11) NULL,
    `location` varchar(100) NULL,
    `start_date` date NOT NULL,
    `end_date` date NOT NULL,
    `status` enum('pending','active','paused','expired','rejected') DEFAULT 'pending',
    `clicks` int(11) DEFAULT 0,
    `impressions` int(11) DEFAULT 0,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`),
    KEY `zone` (`zone`),
    KEY `status` (`status`),
    KEY `end_date` (`end_date`),
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`category_id`) REFERENCES `business_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ad stats table
CREATE TABLE IF NOT EXISTS `ad_stats` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `ad_id` int(11) NOT NULL,
    `event_type` enum('impression','click') NOT NULL,
    `ip_address` varchar(45),
    `user_agent` text,
    `referrer` varchar(500),
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `ad_id` (`ad_id`),
    KEY `event_type` (`event_type`),
    KEY `created_at` (`created_at`),
    FOREIGN KEY (`ad_id`) REFERENCES `ads` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- CAROUSEL SYSTEM
-- =====================================================

-- Carousel slides table
CREATE TABLE IF NOT EXISTS `carousel_slides` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `title` varchar(200) NOT NULL,
    `description` text,
    `image_path` varchar(500) NOT NULL,
    `link_url` varchar(500),
    `zone` enum('homepage','category','business') DEFAULT 'homepage',
    `location` varchar(100) NULL,
    `category_id` int(11) NULL,
    `sort_order` int(11) DEFAULT 0,
    `is_active` tinyint(1) DEFAULT 1,
    `start_date` datetime NULL,
    `end_date` datetime NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `zone` (`zone`),
    KEY `location` (`location`),
    KEY `is_active` (`is_active`),
    KEY `sort_order` (`sort_order`),
    FOREIGN KEY (`category_id`) REFERENCES `business_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Carousel analytics table
CREATE TABLE IF NOT EXISTS `carousel_analytics` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `slide_id` int(11) NOT NULL,
    `event_type` enum('impression','click','swipe') NOT NULL,
    `ip_address` varchar(45),
    `user_agent` text,
    `location` varchar(100),
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `slide_id` (`slide_id`),
    KEY `event_type` (`event_type`),
    KEY `created_at` (`created_at`),
    FOREIGN KEY (`slide_id`) REFERENCES `carousel_slides` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- SUBSCRIPTION SYSTEM
-- =====================================================

-- Subscription plans table
CREATE TABLE IF NOT EXISTS `subscription_plans` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL,
    `description` text,
    `price_monthly` decimal(10,2) NOT NULL,
    `price_annual` decimal(10,2) NOT NULL,
    `features` json,
    `is_active` tinyint(1) DEFAULT 1,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User subscriptions table
CREATE TABLE IF NOT EXISTS `user_subscriptions` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `plan_id` int(11) NOT NULL,
    `status` enum('active','cancelled','expired') DEFAULT 'active',
    `start_date` datetime NOT NULL,
    `end_date` datetime NOT NULL,
    `stripe_subscription_id` varchar(255),
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`),
    KEY `plan_id` (`plan_id`),
    KEY `status` (`status`),
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`plan_id`) REFERENCES `subscription_plans` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- REVIEWS & TESTIMONIALS
-- =====================================================

-- Reviews table
CREATE TABLE IF NOT EXISTS `reviews` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `business_id` int(11) NOT NULL,
    `user_id` int(11) NOT NULL,
    `rating` int(1) NOT NULL CHECK (rating >= 1 AND rating <= 5),
    `comment` text,
    `status` enum('pending','approved','rejected') DEFAULT 'pending',
    `modified_by_admin` tinyint(1) DEFAULT 0,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `business_id` (`business_id`),
    KEY `user_id` (`user_id`),
    KEY `rating` (`rating`),
    KEY `status` (`status`),
    FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Reviews log table
CREATE TABLE IF NOT EXISTS `reviews_log` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `review_id` int(11) NOT NULL,
    `admin_user` varchar(100) NOT NULL,
    `old_rating` int(1),
    `new_rating` int(1),
    `notes` text,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `review_id` (`review_id`),
    FOREIGN KEY (`review_id`) REFERENCES `reviews` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TRIGGERS
-- =====================================================

-- Trigger to automatically update ad status to 'expired' when end_date passes
DELIMITER //
CREATE TRIGGER update_ad_status_expired
BEFORE UPDATE ON ads
FOR EACH ROW
BEGIN
    IF NEW.end_date < CURDATE() AND NEW.status = 'active' THEN
        SET NEW.status = 'expired';
    END IF;
END//
DELIMITER ;

-- Trigger to log ad changes
DELIMITER //
CREATE TRIGGER log_ad_changes
AFTER UPDATE ON ads
FOR EACH ROW
BEGIN
    IF OLD.status != NEW.status OR OLD.clicks != NEW.clicks OR OLD.impressions != NEW.impressions THEN
        INSERT INTO ad_stats (ad_id, event_type, ip_address, user_agent, referrer)
        VALUES (NEW.id, 'impression', 'system', 'trigger', 'status_change');
    END IF;
END//
DELIMITER ;

-- Trigger to log carousel impressions
DELIMITER //
CREATE TRIGGER log_carousel_impression
AFTER INSERT ON carousel_analytics
FOR EACH ROW
BEGIN
    -- Update slide statistics if needed
    -- This can be extended for more complex analytics
END//
DELIMITER ;

-- =====================================================
-- SAMPLE DATA
-- =====================================================

-- Insert default subscription plans
INSERT IGNORE INTO `subscription_plans` (`id`, `name`, `description`, `price_monthly`, `price_annual`, `features`) VALUES
(1, 'Basic', 'Free basic listing', 0.00, 0.00, '["basic_listing", "contact_info"]'),
(2, 'Premium', 'Enhanced visibility and features', 19.99, 199.99, '["basic_listing", "contact_info", "featured_position", "analytics", "priority_support"]'),
(3, 'Premium Plus', 'Maximum visibility and all features', 39.99, 399.99, '["basic_listing", "contact_info", "featured_position", "analytics", "priority_support", "custom_domain", "advanced_analytics", "dedicated_support"]');

-- Insert default business categories
INSERT IGNORE INTO `business_categories` (`id`, `name`, `description`, `icon`, `slug`, `sort_order`) VALUES
(1, 'Restaurant', 'Kosher restaurants and dining', 'fa-utensils', 'restaurant', 1),
(2, 'Catering', 'Kosher catering services', 'fa-birthday-cake', 'catering', 2),
(3, 'Retail', 'Jewish retail stores', 'fa-shopping-bag', 'retail', 3),
(4, 'Education', 'Educational services', 'fa-graduation-cap', 'education', 4),
(5, 'Healthcare', 'Healthcare services', 'fa-heartbeat', 'healthcare', 5),
(6, 'Professional Services', 'Professional services', 'fa-briefcase', 'professional-services', 6),
(7, 'Real Estate', 'Real estate services', 'fa-home', 'real-estate', 7),
(8, 'Events', 'Event planning and services', 'fa-calendar-alt', 'events', 8),
(9, 'Travel', 'Travel services', 'fa-plane', 'travel', 9),
(10, 'Technology', 'Technology services', 'fa-laptop', 'technology', 10),
(11, 'Automotive', 'Automotive services', 'fa-car', 'automotive', 11),
(12, 'Beauty & Wellness', 'Beauty and wellness services', 'fa-spa', 'beauty-wellness', 12),
(13, 'Legal Services', 'Legal services', 'fa-balance-scale', 'legal-services', 13),
(14, 'Financial Services', 'Financial services', 'fa-money-bill-wave', 'financial-services', 14),
(15, 'Construction', 'Construction services', 'fa-hammer', 'construction', 15),
(16, 'Entertainment', 'Entertainment services', 'fa-film', 'entertainment', 16),
(17, 'Sports & Recreation', 'Sports and recreation', 'fa-futbol', 'sports-recreation', 17),
(18, 'Religious Services', 'Religious services', 'fa-synagogue', 'religious-services', 18),
(19, 'Charity', 'Charitable organizations', 'fa-hands-helping', 'charity', 19),
(20, 'Other', 'Other services', 'fa-store', 'other', 20);

-- =====================================================
-- MIGRATION COMPLETE
-- ===================================================== 