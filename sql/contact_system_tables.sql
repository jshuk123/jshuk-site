-- Contact System Database Tables
-- Run this SQL to create the necessary tables for the contact form and review system

-- Contact Inquiries Table
CREATE TABLE IF NOT EXISTS `contact_inquiries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `business_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `newsletter_subscription` tinyint(1) DEFAULT 0,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `status` enum('new','read','replied','archived') DEFAULT 'new',
  `admin_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `business_id` (`business_id`),
  KEY `status` (`status`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `contact_inquiries_ibfk_1` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Newsletter Subscribers Table
CREATE TABLE IF NOT EXISTS `newsletter_subscribers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `source` varchar(100) DEFAULT 'website',
  `status` enum('active','unsubscribed','bounced') DEFAULT 'active',
  `subscription_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `unsubscribe_date` timestamp NULL DEFAULT NULL,
  `last_email_sent` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `status` (`status`),
  KEY `source` (`source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- First, add rating column to testimonials if it doesn't exist
ALTER TABLE `testimonials` 
ADD COLUMN IF NOT EXISTS `rating` int(11) DEFAULT 5 AFTER `user_id`;

-- Then add the other columns to testimonials table
ALTER TABLE `testimonials` 
ADD COLUMN IF NOT EXISTS `reviewer_name` varchar(255) DEFAULT NULL AFTER `user_id`,
ADD COLUMN IF NOT EXISTS `reviewer_email` varchar(255) DEFAULT NULL AFTER `reviewer_name`,
ADD COLUMN IF NOT EXISTS `title` varchar(255) DEFAULT NULL AFTER `reviewer_email`,
ADD COLUMN IF NOT EXISTS `ip_address` varchar(45) DEFAULT NULL AFTER `rating`,
ADD COLUMN IF NOT EXISTS `user_agent` text DEFAULT NULL AFTER `ip_address`,
ADD COLUMN IF NOT EXISTS `is_approved` tinyint(1) DEFAULT 0 AFTER `user_agent`,
ADD COLUMN IF NOT EXISTS `admin_notes` text DEFAULT NULL AFTER `is_approved`,
ADD COLUMN IF NOT EXISTS `response` text DEFAULT NULL AFTER `admin_notes`,
ADD COLUMN IF NOT EXISTS `response_date` timestamp NULL DEFAULT NULL AFTER `response`;

-- Add indexes for better performance
ALTER TABLE `testimonials` 
ADD INDEX IF NOT EXISTS `idx_business_approved` (`business_id`, `is_approved`),
ADD INDEX IF NOT EXISTS `idx_created_at` (`created_at`),
ADD INDEX IF NOT EXISTS `idx_user_id` (`user_id`);

-- Update businesses table if rating columns don't exist
ALTER TABLE `businesses` 
ADD COLUMN IF NOT EXISTS `rating` decimal(3,1) DEFAULT 0.0 AFTER `description`,
ADD COLUMN IF NOT EXISTS `review_count` int(11) DEFAULT 0 AFTER `rating`;

-- Create business_gallery table if it doesn't exist
CREATE TABLE IF NOT EXISTS `business_gallery` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `business_id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_featured` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `business_id` (`business_id`),
  KEY `sort_order` (`sort_order`),
  KEY `is_featured` (`is_featured`),
  CONSTRAINT `business_gallery_ibfk_1` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample data for testing (optional)
-- INSERT INTO `newsletter_subscribers` (`email`, `name`, `source`) VALUES 
-- ('test@example.com', 'Test User', 'contact_form');

-- Create logs directory if it doesn't exist (this is handled by PHP, but good to note)
-- The PHP code will automatically create log files in the logs/ directory 