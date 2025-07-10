-- Fix Testimonials Table Structure
-- Run this SQL to properly update the testimonials table

-- Step 1: Add rating column first (if it doesn't exist)
ALTER TABLE `testimonials` 
ADD COLUMN IF NOT EXISTS `rating` int(11) DEFAULT 5 AFTER `user_id`;

-- Step 2: Add reviewer information columns
ALTER TABLE `testimonials` 
ADD COLUMN IF NOT EXISTS `reviewer_name` varchar(255) DEFAULT NULL AFTER `user_id`,
ADD COLUMN IF NOT EXISTS `reviewer_email` varchar(255) DEFAULT NULL AFTER `reviewer_name`;

-- Step 3: Add title column
ALTER TABLE `testimonials` 
ADD COLUMN IF NOT EXISTS `title` varchar(255) DEFAULT NULL AFTER `reviewer_email`;

-- Step 4: Add tracking columns
ALTER TABLE `testimonials` 
ADD COLUMN IF NOT EXISTS `ip_address` varchar(45) DEFAULT NULL AFTER `rating`,
ADD COLUMN IF NOT EXISTS `user_agent` text DEFAULT NULL AFTER `ip_address`;

-- Step 5: Add approval system columns
ALTER TABLE `testimonials` 
ADD COLUMN IF NOT EXISTS `is_approved` tinyint(1) DEFAULT 0 AFTER `user_agent`,
ADD COLUMN IF NOT EXISTS `admin_notes` text DEFAULT NULL AFTER `is_approved`;

-- Step 6: Add response system columns
ALTER TABLE `testimonials` 
ADD COLUMN IF NOT EXISTS `response` text DEFAULT NULL AFTER `admin_notes`,
ADD COLUMN IF NOT EXISTS `response_date` timestamp NULL DEFAULT NULL AFTER `response`;

-- Step 7: Add performance indexes
ALTER TABLE `testimonials` 
ADD INDEX IF NOT EXISTS `idx_business_approved` (`business_id`, `is_approved`),
ADD INDEX IF NOT EXISTS `idx_created_at` (`created_at`),
ADD INDEX IF NOT EXISTS `idx_user_id` (`user_id`);

-- Step 8: Update existing testimonials to be approved (optional)
-- Uncomment the line below if you want all existing testimonials to be automatically approved
-- UPDATE `testimonials` SET `is_approved` = 1 WHERE `is_approved` IS NULL;

-- Step 9: Set default reviewer names for existing testimonials (optional)
-- Uncomment the lines below if you want to set default reviewer names for existing testimonials
-- UPDATE `testimonials` SET `reviewer_name` = 'Anonymous' WHERE `reviewer_name` IS NULL;
-- UPDATE `testimonials` SET `rating` = 5 WHERE `rating` IS NULL; 