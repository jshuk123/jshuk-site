-- Manual Setup Script for Job Alerts System
-- Run this in your database management tool (phpMyAdmin, MySQL Workbench, etc.)

-- Disable foreign key checks temporarily
SET FOREIGN_KEY_CHECKS = 0;

-- Create saved_jobs table
CREATE TABLE IF NOT EXISTS `saved_jobs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `job_id` INT NOT NULL,
  `saved_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  -- Indexes for performance
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_job_id` (`job_id`),
  INDEX `idx_saved_at` (`saved_at`),
  
  -- Unique constraint to prevent duplicate saves
  UNIQUE KEY `unique_user_job` (`user_id`, `job_id`),
  
  -- Foreign key constraints
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`job_id`) REFERENCES `recruitment`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create job_alerts table
CREATE TABLE IF NOT EXISTS `job_alerts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `name` VARCHAR(255) NOT NULL DEFAULT 'Job Alert',
  `sector_id` INT NULL,
  `location` VARCHAR(255) NULL,
  `job_type` ENUM('full-time','part-time','contract','temporary','internship') NULL,
  `keywords` TEXT NULL,
  `is_active` BOOLEAN DEFAULT TRUE,
  `email_frequency` ENUM('daily','weekly','monthly') DEFAULT 'daily',
  `last_sent_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  -- Indexes for performance
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_sector_id` (`sector_id`),
  INDEX `idx_location` (`location`),
  INDEX `idx_job_type` (`job_type`),
  INDEX `idx_is_active` (`is_active`),
  INDEX `idx_last_sent_at` (`last_sent_at`),
  
  -- Foreign key constraints
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`sector_id`) REFERENCES `job_sectors`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create job_alert_logs table to track sent alerts
CREATE TABLE IF NOT EXISTS `job_alert_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `alert_id` INT NOT NULL,
  `job_id` INT NOT NULL,
  `sent_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  -- Indexes for performance
  INDEX `idx_alert_id` (`alert_id`),
  INDEX `idx_job_id` (`job_id`),
  INDEX `idx_sent_at` (`sent_at`),
  
  -- Foreign key constraints
  FOREIGN KEY (`alert_id`) REFERENCES `job_alerts`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`job_id`) REFERENCES `recruitment`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Add indexes to recruitment table for better performance with job alerts
ALTER TABLE `recruitment` 
ADD INDEX IF NOT EXISTS `idx_created_at_active` (`created_at`, `is_active`);

-- Add fulltext search to recruitment table for keyword matching
ALTER TABLE `recruitment` 
ADD FULLTEXT IF NOT EXISTS `idx_job_search` (`job_title`, `job_description`, `job_location`);

-- Verify tables were created
SELECT 'saved_jobs' as table_name, COUNT(*) as row_count FROM saved_jobs
UNION ALL
SELECT 'job_alerts' as table_name, COUNT(*) as row_count FROM job_alerts
UNION ALL
SELECT 'job_alert_logs' as table_name, COUNT(*) as row_count FROM job_alert_logs; 