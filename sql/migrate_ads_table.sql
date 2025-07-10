-- Migration script to update existing ads table to new structure
-- This script safely migrates from the old ads table to the new enhanced structure

-- Disable foreign key checks temporarily
SET FOREIGN_KEY_CHECKS = 0;

-- Create backup of existing ads table
CREATE TABLE IF NOT EXISTS `ads_backup` AS SELECT * FROM `ads`;

-- Drop existing triggers if they exist
DROP TRIGGER IF EXISTS `update_ad_status_expired`;
DROP TRIGGER IF EXISTS `log_ad_changes`;

-- Drop existing ad_stats table if it exists (will be recreated)
DROP TABLE IF EXISTS `ad_stats`;

-- Rename existing ads table
RENAME TABLE `ads` TO `ads_old`;

-- Create new enhanced ads table
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

-- Migrate data from old table to new table with dynamic column detection
INSERT INTO `ads` (
    `id`, 
    `title`, 
    `image_url`, 
    `link_url`, 
    `zone`, 
    `category_id`, 
    `location`, 
    `start_date`, 
    `end_date`, 
    `status`, 
    `priority`, 
    `business_id`, 
    `cta_text`, 
    `created_at`
)
SELECT 
    `id`,
    COALESCE(
        CASE WHEN EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'ads_old' AND COLUMN_NAME = 'title') THEN `title` END,
        CONCAT('Ad ', `id`)
    ) as `title`,
    COALESCE(
        CASE WHEN EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'ads_old' AND COLUMN_NAME = 'image_path') THEN `image_path` END,
        CASE WHEN EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'ads_old' AND COLUMN_NAME = 'image_url') THEN `image_url` END,
        'default_ad.jpg'
    ) as `image_url`,
    COALESCE(`link_url`, '#') as `link_url`,
    CASE 
        WHEN EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'ads_old' AND COLUMN_NAME = 'is_carousel') AND `is_carousel` = 1 THEN 'carousel'
        WHEN EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'ads_old' AND COLUMN_NAME = 'is_sidebar') AND `is_sidebar` = 1 THEN 'sidebar'
        ELSE 'header'
    END as `zone`,
    NULL as `category_id`,
    NULL as `location`,
    CURDATE() as `start_date`,
    COALESCE(
        CASE WHEN EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'ads_old' AND COLUMN_NAME = 'expires_at') THEN `expires_at` END,
        DATE_ADD(CURDATE(), INTERVAL 30 DAY)
    ) as `end_date`,
    CASE 
        WHEN EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'ads_old' AND COLUMN_NAME = 'is_active') AND `is_active` = 1 THEN 'active'
        ELSE 'paused'
    END as `status`,
    5 as `priority`,
    COALESCE(`business_id`, NULL) as `business_id`,
    NULL as `cta_text`,
    COALESCE(`created_at`, CURRENT_TIMESTAMP) as `created_at`
FROM `ads_old`;

-- Create ad statistics table
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

-- Create admin logs table if it doesn't exist
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

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Insert sample data for testing (only if no ads exist)
INSERT INTO `ads` (`title`, `image_url`, `link_url`, `zone`, `category_id`, `location`, `start_date`, `end_date`, `status`, `priority`, `cta_text`) 
SELECT * FROM (
    SELECT 'Kosher Restaurant Special', 'sample_ad1.jpg', 'https://example.com/restaurant', 'header', NULL, 'London', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'active', 8, 'Book Now'
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM `ads` LIMIT 1);

-- Clean up old table (optional - uncomment if you want to remove the old table)
-- DROP TABLE `ads_old`;

-- Show migration results
SELECT 'Migration completed successfully!' as status;
SELECT COUNT(*) as 'Total ads migrated' FROM `ads`; 