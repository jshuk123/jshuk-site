-- Employer's Dashboard Database Schema
-- This script creates the necessary tables for company profiles and job applications

-- Disable foreign key checks
SET FOREIGN_KEY_CHECKS = 0;

-- Create company_profiles table
CREATE TABLE IF NOT EXISTS `company_profiles` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `company_name` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) UNIQUE NOT NULL,
  `description` TEXT,
  `about_us` LONGTEXT,
  `industry` VARCHAR(100),
  `website` VARCHAR(255),
  `company_size` ENUM('1-10', '11-50', '51-200', '201-500', '501-1000', '1000+') DEFAULT '1-10',
  `founded_year` INT,
  `location` VARCHAR(255),
  `logo_path` VARCHAR(255),
  `banner_path` VARCHAR(255),
  `contact_email` VARCHAR(255),
  `contact_phone` VARCHAR(50),
  `social_linkedin` VARCHAR(255),
  `social_twitter` VARCHAR(255),
  `social_facebook` VARCHAR(255),
  `is_verified` BOOLEAN DEFAULT FALSE,
  `is_active` BOOLEAN DEFAULT TRUE,
  `views_count` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  -- Indexes for performance
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_slug` (`slug`),
  INDEX `idx_company_name` (`company_name`),
  INDEX `idx_industry` (`industry`),
  INDEX `idx_is_active` (`is_active`),
  INDEX `idx_is_verified` (`is_verified`),
  
  -- Fulltext search
  FULLTEXT `idx_search` (`company_name`, `description`, `about_us`, `industry`),
  
  -- Foreign key constraints
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_user_profile` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create job_applications table
CREATE TABLE IF NOT EXISTS `job_applications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `job_id` INT NOT NULL,
  `applicant_id` INT NOT NULL,
  `cover_letter` TEXT,
  `resume_path` VARCHAR(255),
  `status` ENUM('pending', 'reviewed', 'shortlisted', 'interviewed', 'hired', 'rejected') DEFAULT 'pending',
  `notes` TEXT,
  `applied_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  -- Indexes for performance
  INDEX `idx_job_id` (`job_id`),
  INDEX `idx_applicant_id` (`applicant_id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_applied_at` (`applied_at`),
  
  -- Unique constraint to prevent duplicate applications
  UNIQUE KEY `unique_job_applicant` (`job_id`, `applicant_id`),
  
  -- Foreign key constraints
  FOREIGN KEY (`job_id`) REFERENCES `recruitment`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`applicant_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add company_profile_id to recruitment table
ALTER TABLE `recruitment` 
ADD COLUMN IF NOT EXISTS `company_profile_id` INT NULL AFTER `business_id`,
ADD INDEX IF NOT EXISTS `idx_company_profile` (`company_profile_id`),
ADD CONSTRAINT `fk_recruitment_company_profile` 
FOREIGN KEY (`company_profile_id`) REFERENCES `company_profiles`(`id`) ON DELETE SET NULL;

-- Create application_status_history table for tracking application status changes
CREATE TABLE IF NOT EXISTS `application_status_history` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `application_id` INT NOT NULL,
  `status` ENUM('pending', 'reviewed', 'shortlisted', 'interviewed', 'hired', 'rejected') NOT NULL,
  `notes` TEXT,
  `changed_by` INT NOT NULL,
  `changed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  -- Indexes for performance
  INDEX `idx_application_id` (`application_id`),
  INDEX `idx_changed_at` (`changed_at`),
  
  -- Foreign key constraints
  FOREIGN KEY (`application_id`) REFERENCES `job_applications`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`changed_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Add indexes to existing tables for better performance
ALTER TABLE `recruitment` 
ADD INDEX IF NOT EXISTS `idx_user_active` (`user_id`, `is_active`);

-- Create a function to generate company slugs
DELIMITER //
CREATE FUNCTION IF NOT EXISTS generate_company_slug(company_name VARCHAR(255)) 
RETURNS VARCHAR(255)
DETERMINISTIC
BEGIN
    DECLARE slug VARCHAR(255);
    SET slug = LOWER(company_name);
    SET slug = REGEXP_REPLACE(slug, '[^a-z0-9\\s-]', '');
    SET slug = REGEXP_REPLACE(slug, '\\s+', '-');
    SET slug = TRIM(BOTH '-' FROM slug);
    RETURN slug;
END //
DELIMITER ; 