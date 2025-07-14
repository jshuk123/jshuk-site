-- =========================
-- Community Corner System
-- =========================
-- This script creates the database table for the JShuk Community Corner

-- Disable foreign key checks
SET FOREIGN_KEY_CHECKS = 0;

-- Create community_corner table
CREATE TABLE IF NOT EXISTS `community_corner` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `body_text` TEXT NOT NULL,
  `type` ENUM('gemach', 'lost_found', 'simcha', 'charity_alert', 'divrei_torah', 'ask_rabbi', 'volunteer', 'photo_week') NOT NULL,
  `emoji` VARCHAR(10) DEFAULT '❤️',
  `link_url` VARCHAR(255),
  `link_text` VARCHAR(100) DEFAULT 'Learn More →',
  `is_featured` BOOLEAN DEFAULT FALSE,
  `is_active` BOOLEAN DEFAULT TRUE,
  `priority` INT DEFAULT 0 COMMENT 'Higher numbers = higher priority',
  `date_added` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `expire_date` DATE NULL COMMENT 'Optional expiration date',
  `created_by` INT,
  `approved_by` INT,
  `approved_at` TIMESTAMP NULL,
  `views_count` INT DEFAULT 0,
  `clicks_count` INT DEFAULT 0,
  
  -- Indexes
  INDEX `idx_type` (`type`),
  INDEX `idx_featured` (`is_featured`),
  INDEX `idx_active` (`is_active`),
  INDEX `idx_priority` (`priority`),
  INDEX `idx_expire_date` (`expire_date`),
  INDEX `idx_created_by` (`created_by`),
  INDEX `idx_approved` (`approved_by`),
  INDEX `idx_date_added` (`date_added`),
  
  -- Foreign keys
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`approved_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample community corner content
INSERT INTO `community_corner` (`title`, `body_text`, `type`, `emoji`, `link_url`, `link_text`, `is_featured`, `is_active`, `priority`) VALUES
('Gemach Activity', '3 baby items borrowed via local Gemachs this week.', 'gemach', '🍼', '/gemachim.php', 'Explore Gemachim →', TRUE, TRUE, 10),
('Lost School Bag', 'Blue school bag lost in Golders Green — please contact if found.', 'lost_found', '🎒', '/lost_and_found.php', 'View Lost & Found →', TRUE, TRUE, 8),
('Ask the Rabbi', 'Can I pay my cleaner during the 9 Days?', 'ask_rabbi', '📜', '/ask-the-rabbi.php', 'See the answer →', TRUE, TRUE, 9),
('Divrei Torah', '"Words matter. Like the shevuah of Bnei Gad, promises are sacred."', 'divrei_torah', '🕯️', '/divrei-torah.php', 'More Torah Thoughts →', TRUE, TRUE, 7),
('Charity Alert', 'Local family needs a stairlift urgently.', 'charity_alert', '❤️', '/charity_alerts.php', 'See how to help →', TRUE, TRUE, 10),
('Simcha Notice', 'Mazal Tov to the Green family on Shoshana\'s Bat Mitzvah.', 'simcha', '🎉', '/simchas.php', 'Celebrate with them →', TRUE, TRUE, 6),
('Volunteer Opportunity', 'Volunteer this Sunday for Kisharon\'s food drive.', 'volunteer', '🤝', '/volunteer.php', 'Sign up to help →', TRUE, TRUE, 8),
('Photo of the Week', 'Lag B\'Omer in Hendon Park - community celebration.', 'photo_week', '📸', '/photo-of-the-week.php', 'View gallery →', TRUE, TRUE, 5);

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Show migration results
SELECT 'Community Corner table created successfully!' as status;
SELECT COUNT(*) as 'Total community corner items created' FROM `community_corner`; 