-- SIMPLE RECRUITMENT SYSTEM FIX (MariaDB Compatible)
-- Run this script to add missing columns without foreign key constraints

-- 1. Ensure job_sectors table exists and has data
CREATE TABLE IF NOT EXISTS `job_sectors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(110) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Insert job sectors if they don't exist
INSERT IGNORE INTO `job_sectors` (`name`, `slug`) VALUES
('Accounting', 'accounting'),
('Administration', 'administration'),
('Customer Service', 'customer-service'),
('Engineering', 'engineering'),
('Healthcare', 'healthcare'),
('Hospitality', 'hospitality'),
('IT & Technology', 'it-technology'),
('Marketing & Sales', 'marketing-sales'),
('Retail', 'retail-jobs'),
('Skilled Trades', 'skilled-trades'),
('Education', 'education'),
('Finance', 'finance'),
('Legal', 'legal'),
('Non-Profit', 'non-profit'),
('Real Estate', 'real-estate'),
('Transportation', 'transportation');

-- 3. Add missing columns to recruitment table (one by one to avoid errors)
ALTER TABLE `recruitment` ADD COLUMN IF NOT EXISTS `requirements` TEXT NULL AFTER `job_description`;
ALTER TABLE `recruitment` ADD COLUMN IF NOT EXISTS `skills` TEXT NULL AFTER `requirements`;
ALTER TABLE `recruitment` ADD COLUMN IF NOT EXISTS `salary` VARCHAR(200) NULL AFTER `skills`;
ALTER TABLE `recruitment` ADD COLUMN IF NOT EXISTS `benefits` TEXT NULL AFTER `salary`;
ALTER TABLE `recruitment` ADD COLUMN IF NOT EXISTS `company` VARCHAR(255) NULL AFTER `benefits`;
ALTER TABLE `recruitment` ADD COLUMN IF NOT EXISTS `contact_email` VARCHAR(255) NULL AFTER `company`;
ALTER TABLE `recruitment` ADD COLUMN IF NOT EXISTS `contact_phone` VARCHAR(20) NULL AFTER `contact_email`;
ALTER TABLE `recruitment` ADD COLUMN IF NOT EXISTS `application_method` VARCHAR(50) NULL AFTER `contact_phone`;
ALTER TABLE `recruitment` ADD COLUMN IF NOT EXISTS `additional_info` TEXT NULL AFTER `application_method`;
ALTER TABLE `recruitment` ADD COLUMN IF NOT EXISTS `kosher_environment` TINYINT(1) DEFAULT 0 AFTER `additional_info`;
ALTER TABLE `recruitment` ADD COLUMN IF NOT EXISTS `flexible_schedule` TINYINT(1) DEFAULT 0 AFTER `kosher_environment`;
ALTER TABLE `recruitment` ADD COLUMN IF NOT EXISTS `community_focused` TINYINT(1) DEFAULT 0 AFTER `flexible_schedule`;
ALTER TABLE `recruitment` ADD COLUMN IF NOT EXISTS `remote_friendly` TINYINT(1) DEFAULT 0 AFTER `community_focused`;
ALTER TABLE `recruitment` ADD COLUMN IF NOT EXISTS `is_featured` TINYINT(1) DEFAULT 0 AFTER `remote_friendly`;
ALTER TABLE `recruitment` ADD COLUMN IF NOT EXISTS `views_count` INT DEFAULT 0 AFTER `is_featured`;
ALTER TABLE `recruitment` ADD COLUMN IF NOT EXISTS `applications_count` INT DEFAULT 0 AFTER `views_count`;

-- 4. Add performance indexes (one by one)
ALTER TABLE `recruitment` ADD INDEX IF NOT EXISTS `idx_job_type` (`job_type`);
ALTER TABLE `recruitment` ADD INDEX IF NOT EXISTS `idx_is_active` (`is_active`);
ALTER TABLE `recruitment` ADD INDEX IF NOT EXISTS `idx_is_featured` (`is_featured`);
ALTER TABLE `recruitment` ADD INDEX IF NOT EXISTS `idx_created_at` (`created_at`);
ALTER TABLE `recruitment` ADD INDEX IF NOT EXISTS `idx_sector_id` (`sector_id`);
ALTER TABLE `recruitment` ADD INDEX IF NOT EXISTS `idx_user_id` (`user_id`);

-- 5. Update existing records with default values
UPDATE `recruitment` SET `is_active` = 1 WHERE `is_active` IS NULL;
UPDATE `recruitment` SET `is_featured` = 0 WHERE `is_featured` IS NULL;
UPDATE `recruitment` SET `views_count` = 0 WHERE `views_count` IS NULL;
UPDATE `recruitment` SET `applications_count` = 0 WHERE `applications_count` IS NULL;
UPDATE `recruitment` SET `kosher_environment` = 0 WHERE `kosher_environment` IS NULL;
UPDATE `recruitment` SET `flexible_schedule` = 0 WHERE `flexible_schedule` IS NULL;
UPDATE `recruitment` SET `community_focused` = 0 WHERE `community_focused` IS NULL;
UPDATE `recruitment` SET `remote_friendly` = 0 WHERE `remote_friendly` IS NULL;

-- 6. Show success message and table structure
SELECT '✅ Recruitment system setup completed successfully!' as result;
SELECT 'Current recruitment table structure:' as info;
DESCRIBE `recruitment`; 