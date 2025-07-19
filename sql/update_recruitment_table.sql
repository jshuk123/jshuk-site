-- Update recruitment table to add missing columns for enhanced job posting
-- This script adds all the columns needed for the new job posting form

ALTER TABLE `recruitment` 
ADD COLUMN `requirements` TEXT NULL AFTER `job_description`,
ADD COLUMN `skills` TEXT NULL AFTER `requirements`,
ADD COLUMN `salary` VARCHAR(200) NULL AFTER `skills`,
ADD COLUMN `benefits` TEXT NULL AFTER `salary`,
ADD COLUMN `company` VARCHAR(255) NULL AFTER `benefits`,
ADD COLUMN `contact_email` VARCHAR(255) NULL AFTER `company`,
ADD COLUMN `contact_phone` VARCHAR(20) NULL AFTER `contact_email`,
ADD COLUMN `application_method` VARCHAR(50) NULL AFTER `contact_phone`,
ADD COLUMN `additional_info` TEXT NULL AFTER `application_method`,
ADD COLUMN `kosher_environment` TINYINT(1) DEFAULT 0 AFTER `additional_info`,
ADD COLUMN `flexible_schedule` TINYINT(1) DEFAULT 0 AFTER `kosher_environment`,
ADD COLUMN `community_focused` TINYINT(1) DEFAULT 0 AFTER `flexible_schedule`,
ADD COLUMN `remote_friendly` TINYINT(1) DEFAULT 0 AFTER `community_focused`,
ADD COLUMN `is_featured` TINYINT(1) DEFAULT 0 AFTER `remote_friendly`,
ADD COLUMN `views_count` INT DEFAULT 0 AFTER `is_featured`,
ADD COLUMN `applications_count` INT DEFAULT 0 AFTER `views_count`;

-- Add indexes for better performance
ALTER TABLE `recruitment` 
ADD INDEX `idx_job_type` (`job_type`),
ADD INDEX `idx_is_active` (`is_active`),
ADD INDEX `idx_is_featured` (`is_featured`),
ADD INDEX `idx_created_at` (`created_at`);

-- Update existing records to have default values for new columns
UPDATE `recruitment` SET 
`is_active` = 1,
`is_featured` = 0,
`views_count` = 0,
`applications_count` = 0
WHERE `is_active` IS NULL OR `is_featured` IS NULL OR `views_count` IS NULL OR `applications_count` IS NULL; 