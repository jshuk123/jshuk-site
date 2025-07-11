-- Lost & Found System Database Tables
-- This script creates the necessary tables for the JShuk Lost & Found feature

-- Disable foreign key checks
SET FOREIGN_KEY_CHECKS = 0;

-- Create lostfound_posts table
CREATE TABLE `lostfound_posts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `post_type` ENUM('lost', 'found') NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `category` VARCHAR(100) NOT NULL,
  `location` VARCHAR(100) NOT NULL,
  `date_lost_found` DATE NOT NULL,
  `description` TEXT NOT NULL,
  `image_paths` TEXT NULL, -- JSON or comma-separated
  `is_blurred` BOOLEAN DEFAULT FALSE,
  `contact_phone` VARCHAR(50) NULL,
  `contact_email` VARCHAR(100) NULL,
  `contact_whatsapp` VARCHAR(100) NULL,
  `is_anonymous` BOOLEAN DEFAULT FALSE,
  `hide_contact_until_verified` BOOLEAN DEFAULT FALSE,
  `status` ENUM('active', 'reunited', 'archived') DEFAULT 'active',
  `user_id` INT NULL, -- Optional: if user is logged in
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  -- Indexes for performance
  INDEX `idx_post_type` (`post_type`),
  INDEX `idx_category` (`category`),
  INDEX `idx_location` (`location`),
  INDEX `idx_status` (`status`),
  INDEX `idx_date_lost_found` (`date_lost_found`),
  INDEX `idx_created_at` (`created_at`),
  INDEX `idx_user` (`user_id`),
  
  -- Full-text search indexes
  FULLTEXT `idx_search` (`title`, `description`, `location`),
  
  -- Foreign key constraints
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create lostfound_claims table
CREATE TABLE `lostfound_claims` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `post_id` INT NOT NULL,
  `claimant_name` VARCHAR(255) NOT NULL,
  `simanim` TEXT NOT NULL,
  `claim_description` TEXT NOT NULL,
  `claim_date` DATE NOT NULL,
  `contact_email` VARCHAR(100) NOT NULL,
  `contact_phone` VARCHAR(50) NULL,
  `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  -- Indexes for performance
  INDEX `idx_post_id` (`post_id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_created_at` (`created_at`),
  
  -- Foreign key constraints
  FOREIGN KEY (`post_id`) REFERENCES `lostfound_posts`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create lostfound_categories table for predefined categories
CREATE TABLE `lostfound_categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL UNIQUE,
  `icon` VARCHAR(50) NOT NULL,
  `description` TEXT NULL,
  `is_active` BOOLEAN DEFAULT TRUE,
  `sort_order` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create lostfound_locations table for predefined locations
CREATE TABLE `lostfound_locations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL UNIQUE,
  `area` VARCHAR(100) NULL,
  `is_active` BOOLEAN DEFAULT TRUE,
  `sort_order` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default categories
INSERT INTO `lostfound_categories` (`name`, `icon`, `description`, `sort_order`) VALUES
('Keys', 'fas fa-key', 'House keys, car keys, office keys', 1),
('Phones', 'fas fa-mobile-alt', 'Mobile phones, smartphones', 2),
('Hats', 'fas fa-hat-cowboy', 'Kippot, hats, head coverings', 3),
('Jewelry', 'fas fa-gem', 'Rings, necklaces, watches', 4),
('Sefarim', 'fas fa-book', 'Books, prayer books, religious texts', 5),
('Bags', 'fas fa-briefcase', 'Handbags, backpacks, briefcases', 6),
('Clothing', 'fas fa-tshirt', 'Coats, jackets, clothing items', 7),
('Electronics', 'fas fa-laptop', 'Laptops, tablets, electronic devices', 8),
('Documents', 'fas fa-file-alt', 'ID cards, passports, important papers', 9),
('Other', 'fas fa-question-circle', 'Other miscellaneous items', 10);

-- Insert default locations
INSERT INTO `lostfound_locations` (`name`, `area`, `sort_order`) VALUES
('Golders Green', 'London', 1),
('Edgware', 'London', 2),
('Stamford Hill', 'London', 3),
('Hendon', 'London', 4),
('Finchley', 'London', 5),
('Manchester', 'Manchester', 6),
('Gateshead', 'Gateshead', 7),
('Leeds', 'Leeds', 8),
('Birmingham', 'Birmingham', 9),
('Liverpool', 'Liverpool', 10),
('Other', 'Various', 11);

-- Create trigger to automatically archive posts after 30 days
DELIMITER //
CREATE TRIGGER archive_old_lostfound_posts
BEFORE UPDATE ON lostfound_posts
FOR EACH ROW
BEGIN
    IF NEW.status = 'active' AND DATEDIFF(CURDATE(), NEW.created_at) > 30 THEN
        SET NEW.status = 'archived';
    END IF;
END//
DELIMITER ;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Show migration results
SELECT 'Lost & Found tables created successfully!' as status;
SELECT COUNT(*) as 'Total categories created' FROM `lostfound_categories`;
SELECT COUNT(*) as 'Total locations created' FROM `lostfound_locations`; 