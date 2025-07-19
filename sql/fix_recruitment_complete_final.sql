-- COMPREHENSIVE RECRUITMENT SYSTEM FIX
-- Run this script to ensure all tables and columns are properly set up

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

-- 3. Ensure recruitment table has all required columns
ALTER TABLE `recruitment` 
ADD COLUMN IF NOT EXISTS `requirements` TEXT NULL AFTER `job_description`,
ADD COLUMN IF NOT EXISTS `skills` TEXT NULL AFTER `requirements`,
ADD COLUMN IF NOT EXISTS `salary` VARCHAR(200) NULL AFTER `skills`,
ADD COLUMN IF NOT EXISTS `benefits` TEXT NULL AFTER `salary`,
ADD COLUMN IF NOT EXISTS `company` VARCHAR(255) NULL AFTER `benefits`,
ADD COLUMN IF NOT EXISTS `contact_email` VARCHAR(255) NULL AFTER `company`,
ADD COLUMN IF NOT EXISTS `contact_phone` VARCHAR(20) NULL AFTER `contact_email`,
ADD COLUMN IF NOT EXISTS `application_method` VARCHAR(50) NULL AFTER `contact_phone`,
ADD COLUMN IF NOT EXISTS `additional_info` TEXT NULL AFTER `application_method`,
ADD COLUMN IF NOT EXISTS `kosher_environment` TINYINT(1) DEFAULT 0 AFTER `additional_info`,
ADD COLUMN IF NOT EXISTS `flexible_schedule` TINYINT(1) DEFAULT 0 AFTER `kosher_environment`,
ADD COLUMN IF NOT EXISTS `community_focused` TINYINT(1) DEFAULT 0 AFTER `flexible_schedule`,
ADD COLUMN IF NOT EXISTS `remote_friendly` TINYINT(1) DEFAULT 0 AFTER `community_focused`,
ADD COLUMN IF NOT EXISTS `is_featured` TINYINT(1) DEFAULT 0 AFTER `remote_friendly`,
ADD COLUMN IF NOT EXISTS `views_count` INT DEFAULT 0 AFTER `is_featured`,
ADD COLUMN IF NOT EXISTS `applications_count` INT DEFAULT 0 AFTER `views_count`;

-- 4. Add performance indexes
ALTER TABLE `recruitment` 
ADD INDEX IF NOT EXISTS `idx_job_type` (`job_type`),
ADD INDEX IF NOT EXISTS `idx_is_active` (`is_active`),
ADD INDEX IF NOT EXISTS `idx_is_featured` (`is_featured`),
ADD INDEX IF NOT EXISTS `idx_created_at` (`created_at`),
ADD INDEX IF NOT EXISTS `idx_sector_id` (`sector_id`),
ADD INDEX IF NOT EXISTS `idx_user_id` (`user_id`);

-- 5. Update existing records with default values
UPDATE `recruitment` SET 
    `is_active` = COALESCE(`is_active`, 1),
    `is_featured` = COALESCE(`is_featured`, 0),
    `views_count` = COALESCE(`views_count`, 0),
    `applications_count` = COALESCE(`applications_count`, 0),
    `kosher_environment` = COALESCE(`kosher_environment`, 0),
    `flexible_schedule` = COALESCE(`flexible_schedule`, 0),
    `community_focused` = COALESCE(`community_focused`, 0),
    `remote_friendly` = COALESCE(`remote_friendly`, 0)
WHERE `is_active` IS NULL 
   OR `is_featured` IS NULL 
   OR `views_count` IS NULL 
   OR `applications_count` IS NULL
   OR `kosher_environment` IS NULL
   OR `flexible_schedule` IS NULL
   OR `community_focused` IS NULL
   OR `remote_friendly` IS NULL;

-- 6. Ensure foreign key constraints exist (MariaDB compatible)
-- First, drop existing constraints if they exist
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'recruitment' 
     AND CONSTRAINT_NAME = 'recruitment_ibfk_1') > 0,
    'ALTER TABLE `recruitment` DROP FOREIGN KEY `recruitment_ibfk_1`',
    'SELECT "Constraint recruitment_ibfk_1 does not exist"'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'recruitment' 
     AND CONSTRAINT_NAME = 'recruitment_ibfk_2') > 0,
    'ALTER TABLE `recruitment` DROP FOREIGN KEY `recruitment_ibfk_2`',
    'SELECT "Constraint recruitment_ibfk_2 does not exist"'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
     WHERE TABLE_SCHEMA = DATABASE() 
     AND TABLE_NAME = 'recruitment' 
     AND CONSTRAINT_NAME = 'recruitment_ibfk_3') > 0,
    'ALTER TABLE `recruitment` DROP FOREIGN KEY `recruitment_ibfk_3`',
    'SELECT "Constraint recruitment_ibfk_3 does not exist"'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Now add the constraints
ALTER TABLE `recruitment` 
ADD CONSTRAINT `recruitment_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
ADD CONSTRAINT `recruitment_ibfk_2` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE SET NULL,
ADD CONSTRAINT `recruitment_ibfk_3` FOREIGN KEY (`sector_id`) REFERENCES `job_sectors` (`id`) ON DELETE SET NULL;

-- 7. Show final table structure
SELECT '✅ Recruitment system setup completed successfully!' as result;
SELECT 'Current recruitment table structure:' as info;
DESCRIBE `recruitment`; 