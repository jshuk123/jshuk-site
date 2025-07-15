-- =========================
-- Volunteer Hub System
-- =========================
-- This script creates the database tables for the JShuk Volunteer Hub

-- Disable foreign key checks
SET FOREIGN_KEY_CHECKS = 0;

-- Create volunteer_opportunities table
CREATE TABLE IF NOT EXISTS `volunteer_opportunities` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT NOT NULL,
  `summary` VARCHAR(500) NOT NULL,
  `location` VARCHAR(255) NOT NULL,
  `tags` TEXT COMMENT 'JSON array of tags',
  `contact_method` ENUM('email', 'phone', 'whatsapp', 'internal') DEFAULT 'internal',
  `contact_info` VARCHAR(255),
  `frequency` ENUM('one_time', 'weekly', 'monthly', 'flexible') DEFAULT 'one_time',
  `preferred_times` TEXT COMMENT 'JSON array of preferred times',
  `date_needed` DATE NULL,
  `time_needed` TIME NULL,
  `chessed_hours` INT DEFAULT 0 COMMENT 'Estimated hours for this opportunity',
  `urgent` BOOLEAN DEFAULT FALSE,
  `status` ENUM('active', 'filled', 'expired', 'pending') DEFAULT 'pending',
  `posted_by` INT,
  `approved_by` INT,
  `approved_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `expires_at` TIMESTAMP NULL,
  `views_count` INT DEFAULT 0,
  `interests_count` INT DEFAULT 0,
  `slug` VARCHAR(255) UNIQUE,
  
  -- Indexes
  INDEX `idx_status` (`status`),
  INDEX `idx_location` (`location`),
  INDEX `idx_frequency` (`frequency`),
  INDEX `idx_urgent` (`urgent`),
  INDEX `idx_date_needed` (`date_needed`),
  INDEX `idx_posted_by` (`posted_by`),
  INDEX `idx_approved` (`approved_by`),
  INDEX `idx_created_at` (`created_at`),
  INDEX `idx_expires_at` (`expires_at`),
  INDEX `idx_slug` (`slug`),
  
  -- Full text search
  FULLTEXT(`title`, `description`, `summary`, `tags`),
  
  -- Foreign keys
  FOREIGN KEY (`posted_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`approved_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create volunteer_profiles table
CREATE TABLE IF NOT EXISTS `volunteer_profiles` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `display_name` VARCHAR(100),
  `bio` TEXT,
  `availability` TEXT COMMENT 'JSON array of availability times',
  `preferred_roles` TEXT COMMENT 'JSON array of preferred help types',
  `contact_method` ENUM('email', 'phone', 'whatsapp') DEFAULT 'email',
  `contact_info` VARCHAR(255),
  `experience_level` ENUM('beginner', 'intermediate', 'experienced') DEFAULT 'beginner',
  `badge_list` TEXT COMMENT 'JSON array of earned badges',
  `chessed_hours_total` INT DEFAULT 0,
  `is_public` BOOLEAN DEFAULT TRUE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  -- Indexes
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_is_public` (`is_public`),
  INDEX `idx_chessed_hours` (`chessed_hours_total`),
  
  -- Foreign keys
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_user_profile` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create volunteer_interests table
CREATE TABLE IF NOT EXISTS `volunteer_interests` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `opportunity_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `message` TEXT,
  `status` ENUM('pending', 'accepted', 'declined', 'completed') DEFAULT 'pending',
  `contact_revealed` BOOLEAN DEFAULT FALSE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  -- Indexes
  INDEX `idx_opportunity_id` (`opportunity_id`),
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_created_at` (`created_at`),
  
  -- Foreign keys
  FOREIGN KEY (`opportunity_id`) REFERENCES `volunteer_opportunities`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_interest` (`opportunity_id`, `user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create chessed_exchange table
CREATE TABLE IF NOT EXISTS `chessed_exchange` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `can_offer` TEXT NOT NULL,
  `needs_help_with` TEXT NOT NULL,
  `location` VARCHAR(255),
  `contact_method` ENUM('email', 'phone', 'whatsapp', 'internal') DEFAULT 'internal',
  `contact_info` VARCHAR(255),
  `status` ENUM('active', 'matched', 'expired', 'pending') DEFAULT 'pending',
  `approved_by` INT,
  `approved_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `expires_at` TIMESTAMP NULL,
  
  -- Indexes
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_location` (`location`),
  INDEX `idx_created_at` (`created_at`),
  INDEX `idx_expires_at` (`expires_at`),
  
  -- Foreign keys
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`approved_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create volunteer_badges table
CREATE TABLE IF NOT EXISTS `volunteer_badges` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT NOT NULL,
  `icon` VARCHAR(50) NOT NULL,
  `color` VARCHAR(7) DEFAULT '#007bff',
  `criteria` TEXT COMMENT 'JSON criteria for earning this badge',
  `is_active` BOOLEAN DEFAULT TRUE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  -- Indexes
  INDEX `idx_name` (`name`),
  INDEX `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create volunteer_badge_earnings table
CREATE TABLE IF NOT EXISTS `volunteer_badge_earnings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `badge_id` INT NOT NULL,
  `earned_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `earned_for` VARCHAR(255) COMMENT 'Description of what earned this badge',
  
  -- Indexes
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_badge_id` (`badge_id`),
  INDEX `idx_earned_at` (`earned_at`),
  
  -- Foreign keys
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`badge_id`) REFERENCES `volunteer_badges`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_badge_earning` (`user_id`, `badge_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create volunteer_hours table
CREATE TABLE IF NOT EXISTS `volunteer_hours` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `opportunity_id` INT,
  `hours` DECIMAL(5,2) NOT NULL,
  `description` TEXT,
  `confirmed_by` INT,
  `confirmed_at` TIMESTAMP NULL,
  `date_completed` DATE NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  -- Indexes
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_opportunity_id` (`opportunity_id`),
  INDEX `idx_date_completed` (`date_completed`),
  INDEX `idx_confirmed` (`confirmed_by`),
  
  -- Foreign keys
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`opportunity_id`) REFERENCES `volunteer_opportunities`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`confirmed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default badges
INSERT INTO `volunteer_badges` (`name`, `description`, `icon`, `color`, `criteria`) VALUES
('First-Time Volunteer', 'Completed your first volunteer opportunity', 'fa-star', '#28a745', '{"type": "first_opportunity"}'),
('Chesed Champion', 'Completed 5+ volunteer opportunities', 'fa-trophy', '#ffc107', '{"type": "opportunity_count", "count": 5}'),
('Urgent Responder', 'Responded to 3+ urgent volunteer requests', 'fa-exclamation-triangle', '#dc3545', '{"type": "urgent_responses", "count": 3}'),
('Homework Hero', 'Completed 2+ tutoring opportunities', 'fa-graduation-cap', '#17a2b8', '{"type": "category_count", "category": "tutoring", "count": 2}'),
('Elderly Care Expert', 'Completed 3+ elderly care opportunities', 'fa-heart', '#e83e8c', '{"type": "category_count", "category": "elderly", "count": 3}'),
('Community Builder', 'Completed 10+ volunteer opportunities', 'fa-users', '#6f42c1', '{"type": "opportunity_count", "count": 10}'),
('Weekend Warrior', 'Completed 5+ weekend volunteer opportunities', 'fa-calendar-weekend', '#fd7e14', '{"type": "weekend_opportunities", "count": 5}'),
('Consistent Helper', 'Volunteered for 3 consecutive months', 'fa-calendar-check', '#20c997', '{"type": "consecutive_months", "count": 3}');

-- Insert sample volunteer opportunities
INSERT INTO `volunteer_opportunities` (`title`, `description`, `summary`, `location`, `tags`, `frequency`, `date_needed`, `chessed_hours`, `urgent`, `status`, `posted_by`, `approved_by`, `approved_at`, `slug`) VALUES
('Homework Help for GCSE Student', 'Looking for a patient tutor to help my 15-year-old daughter with GCSE Maths and English. She needs help 2-3 times per week for 1-2 hours each session. We live in Golders Green and can host or travel to you.', 'GCSE tutoring needed for Maths and English', 'Golders Green', '["tutoring", "education", "gcse", "maths", "english"]', 'weekly', '2025-01-15', 6, FALSE, 'active', 1, 1, NOW(), 'homework-help-gcse-golders-green'),
('Elderly Visit - Shabbat Company', 'My grandmother (85) would love some company on Shabbat afternoons. She lives in Hendon and enjoys talking about Jewish history and current events. 1-2 hours per visit would be wonderful.', 'Companionship needed for elderly grandmother on Shabbat', 'Hendon', '["elderly", "companionship", "shabbat", "visiting"]', 'weekly', '2025-01-20', 2, FALSE, 'active', 1, 1, NOW(), 'elderly-visit-shabbat-hendon'),
('Urgent: Food Delivery for Sick Family', 'URGENT: Family of 6 is sick with flu and needs kosher food delivered. They live in Stamford Hill and cannot leave the house. Any help with shopping and delivery would be greatly appreciated.', 'Urgent food delivery needed for sick family', 'Stamford Hill', '["urgent", "food", "delivery", "sick", "family"]', 'one_time', '2025-01-10', 3, TRUE, 'active', 1, 1, NOW(), 'urgent-food-delivery-stamford-hill'),
('Purim Mishloach Manot Assembly', 'Help assemble and deliver Mishloach Manot packages for our community. We need volunteers for 2-3 hours on Purim morning. Great opportunity for families with children!', 'Help assemble Purim packages for community', 'North West London', '["purim", "mishloach-manot", "community", "family-friendly"]', 'one_time', '2025-03-14', 3, FALSE, 'active', 1, 1, NOW(), 'purim-mishloach-manot-assembly'),
('Shabbat Meal Hosting', 'Looking for a family to host us for Shabbat dinner. We are new to the community and would love to meet other families. We can bring dessert and help with cleanup.', 'Shabbat hosting needed for new community members', 'North West London', '["shabbat", "hosting", "community", "new-members"]', 'one_time', '2025-01-25', 4, FALSE, 'active', 1, 1, NOW(), 'shabbat-meal-hosting-north-west-london');

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Show migration results
SELECT 'Volunteer Hub tables created successfully!' as status;
SELECT COUNT(*) as 'Total volunteer opportunities created' FROM `volunteer_opportunities`;
SELECT COUNT(*) as 'Total badges created' FROM `volunteer_badges`; 