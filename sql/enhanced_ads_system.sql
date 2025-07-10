-- Enhanced Ad Management System for JShuk
-- This replaces the existing ads table with a more comprehensive structure

-- Disable foreign key checks temporarily
SET FOREIGN_KEY_CHECKS = 0;

-- Drop existing triggers first (if they exist)
DROP TRIGGER IF EXISTS `update_ad_status_expired`;
DROP TRIGGER IF EXISTS `log_ad_changes`;

-- Drop existing tables in correct order
DROP TABLE IF EXISTS `ad_stats`;
DROP TABLE IF EXISTS `ads`;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Create enhanced ads table
CREATE TABLE `ads` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `image_url` TEXT NOT NULL,
  `link_url` TEXT NOT NULL,
  `zone` ENUM('header', 'sidebar', 'footer', 'carousel', 'inline') NOT NULL,
  `category_id` INT NULL,
  `location` VARCHAR(255) NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `status` ENUM('active', 'paused', 'expired') DEFAULT 'paused',
  `priority` INT DEFAULT 5,
  `business_id` INT NULL,
  `cta_text` VARCHAR(100) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  -- Indexes for performance
  INDEX `idx_zone_status` (`zone`, `status`),
  INDEX `idx_dates` (`start_date`, `end_date`),
  INDEX `idx_category` (`category_id`),
  INDEX `idx_location` (`location`),
  INDEX `idx_priority` (`priority`),
  INDEX `idx_business` (`business_id`),
  
  -- Foreign key constraints
  FOREIGN KEY (`category_id`) REFERENCES `business_categories`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`business_id`) REFERENCES `businesses`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create ad statistics table for future analytics
CREATE TABLE `ad_stats` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `ad_id` INT NOT NULL,
  `date` DATE NOT NULL,
  `views` INT DEFAULT 0,
  `clicks` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  -- Indexes
  INDEX `idx_ad_date` (`ad_id`, `date`),
  INDEX `idx_date` (`date`),
  
  -- Foreign key
  FOREIGN KEY (`ad_id`) REFERENCES `ads`(`id`) ON DELETE CASCADE,
  
  -- Unique constraint to prevent duplicate entries
  UNIQUE KEY `unique_ad_date` (`ad_id`, `date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create admin logs table for tracking ad management actions (if it doesn't exist)
CREATE TABLE IF NOT EXISTS `admin_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `admin_id` INT NOT NULL,
  `action` VARCHAR(100) NOT NULL,
  `table_name` VARCHAR(50) NOT NULL,
  `record_id` INT NULL,
  `details` TEXT NULL,
  `ip_address` VARCHAR(45) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  -- Indexes
  INDEX `idx_admin_action` (`admin_id`, `action`),
  INDEX `idx_table_record` (`table_name`, `record_id`),
  INDEX `idx_created_at` (`created_at`),
  
  -- Foreign key
  FOREIGN KEY (`admin_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample data for testing
INSERT INTO `ads` (`title`, `image_url`, `link_url`, `zone`, `category_id`, `location`, `start_date`, `end_date`, `status`, `priority`, `cta_text`) VALUES
('Kosher Restaurant Special', 'sample_ad1.jpg', 'https://example.com/restaurant', 'header', NULL, 'London', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'active', 8, 'Book Now'),
('Jewish Community Event', 'sample_ad2.jpg', 'https://example.com/event', 'sidebar', NULL, 'Manchester', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 60 DAY), 'active', 6, 'Learn More'),
('Kosher Grocery Deals', 'sample_ad3.jpg', 'https://example.com/grocery', 'carousel', NULL, NULL, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 45 DAY), 'active', 7, 'Shop Now'),
('Synagogue Services', 'sample_ad4.jpg', 'https://example.com/synagogue', 'footer', NULL, 'Birmingham', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 90 DAY), 'active', 5, 'Join Us'),
('Jewish Education Center', 'sample_ad5.jpg', 'https://example.com/education', 'inline', NULL, 'Leeds', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 75 DAY), 'active', 9, 'Enroll Today');

-- Create trigger to automatically update status to 'expired' when end_date passes
DELIMITER //
CREATE TRIGGER update_ad_status_expired
BEFORE UPDATE ON ads
FOR EACH ROW
BEGIN
    IF NEW.end_date < CURDATE() THEN
        SET NEW.status = 'expired';
    END IF;
END//
DELIMITER ; 