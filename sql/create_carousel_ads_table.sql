-- Create carousel_ads table for JShuk homepage carousel
-- This table stores carousel advertisements that appear on the homepage

CREATE TABLE IF NOT EXISTS `carousel_ads` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(100) NOT NULL,
    `subtitle` VARCHAR(255),
    `image_path` VARCHAR(255) NOT NULL,
    `cta_text` VARCHAR(50),
    `cta_url` VARCHAR(255),
    `active` BOOLEAN DEFAULT TRUE,
    `is_auto_generated` BOOLEAN DEFAULT FALSE,
    `business_id` INT,
    `position` INT DEFAULT 1,
    `expires_at` DATETIME,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes for performance
    INDEX `idx_active_position` (`active`, `position`),
    INDEX `idx_business_id` (`business_id`),
    INDEX `idx_expires_at` (`expires_at`),
    INDEX `idx_created_at` (`created_at`),
    
    -- Foreign key constraint
    FOREIGN KEY (`business_id`) REFERENCES `businesses`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample carousel ads for testing
INSERT INTO `carousel_ads` (`title`, `subtitle`, `image_path`, `cta_text`, `cta_url`, `active`, `position`, `created_at`) VALUES
('Welcome to JShuk', 'Your Jewish Community Hub - Discover Local Businesses', 'uploads/carousel/sample_ad1.jpg', 'Explore Now', 'businesses.php', 1, 1, NOW()),
('Kosher Restaurants', 'Find the best kosher dining in your area', 'uploads/carousel/sample_ad2.jpg', 'Find Restaurants', 'businesses.php?category=restaurants', 1, 2, NOW()),
('Community Events', 'Stay connected with your local Jewish community', 'uploads/carousel/sample_ad3.jpg', 'View Events', 'events.php', 1, 3, NOW());

-- Create trigger to automatically update status when expires_at passes
DELIMITER //
CREATE TRIGGER update_carousel_ad_expired
BEFORE UPDATE ON carousel_ads
FOR EACH ROW
BEGIN
    IF NEW.expires_at IS NOT NULL AND NEW.expires_at < NOW() THEN
        SET NEW.active = FALSE;
    END IF;
END//
DELIMITER ; 