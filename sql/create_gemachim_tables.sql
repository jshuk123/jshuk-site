-- =========================
-- Gemachim Directory System
-- =========================
-- This script creates the database tables for the JShuk Gemachim Directory

-- Disable foreign key checks
SET FOREIGN_KEY_CHECKS = 0;

-- Create gemach categories table
CREATE TABLE IF NOT EXISTS `gemach_categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(100) NOT NULL,
  `description` TEXT,
  `icon_class` VARCHAR(100) DEFAULT 'fas fa-hands-helping',
  `sort_order` INT DEFAULT 0,
  `is_active` BOOLEAN DEFAULT TRUE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  -- Indexes
  UNIQUE KEY `unique_slug` (`slug`),
  INDEX `idx_active_sort` (`is_active`, `sort_order`),
  INDEX `idx_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create gemachim table
CREATE TABLE IF NOT EXISTS `gemachim` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `category_id` INT,
  `description` TEXT,
  `location` VARCHAR(255),
  `contact_phone` VARCHAR(50),
  `contact_email` VARCHAR(100),
  `whatsapp_link` TEXT,
  `image_paths` TEXT COMMENT 'JSON array of image paths',
  `donation_enabled` BOOLEAN DEFAULT FALSE,
  `donation_link` TEXT,
  `in_memory_of` VARCHAR(255),
  `verified` BOOLEAN DEFAULT FALSE,
  `featured` BOOLEAN DEFAULT FALSE,
  `urgent_need` BOOLEAN DEFAULT FALSE,
  `status` ENUM('active', 'pending', 'inactive') DEFAULT 'pending',
  `submitted_by` INT,
  `views_count` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  -- Indexes
  INDEX `idx_category` (`category_id`),
  INDEX `idx_location` (`location`),
  INDEX `idx_status` (`status`),
  INDEX `idx_verified` (`verified`),
  INDEX `idx_featured` (`featured`),
  INDEX `idx_urgent` (`urgent_need`),
  INDEX `idx_donation` (`donation_enabled`),
  INDEX `idx_submitted_by` (`submitted_by`),
  INDEX `idx_created_at` (`created_at`),
  
  -- Foreign keys
  FOREIGN KEY (`category_id`) REFERENCES `gemach_categories`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`submitted_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create gemach donations table
CREATE TABLE IF NOT EXISTS `gemach_donations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `gemach_id` INT NOT NULL,
  `donor_name` VARCHAR(255),
  `donor_email` VARCHAR(100),
  `amount` DECIMAL(10,2) NOT NULL,
  `payment_method` ENUM('stripe', 'paypal', 'bank_transfer', 'other') NOT NULL,
  `transaction_id` VARCHAR(255),
  `status` ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
  `notes` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  -- Indexes
  INDEX `idx_gemach` (`gemach_id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_created_at` (`created_at`),
  
  -- Foreign key
  FOREIGN KEY (`gemach_id`) REFERENCES `gemachim`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create gemach testimonials table
CREATE TABLE IF NOT EXISTS `gemach_testimonials` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `gemach_id` INT NOT NULL,
  `user_id` INT,
  `testimonial` TEXT NOT NULL,
  `rating` TINYINT CHECK (`rating` >= 1 AND `rating` <= 5),
  `is_approved` BOOLEAN DEFAULT FALSE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  -- Indexes
  INDEX `idx_gemach` (`gemach_id`),
  INDEX `idx_user` (`user_id`),
  INDEX `idx_approved` (`is_approved`),
  
  -- Foreign keys
  FOREIGN KEY (`gemach_id`) REFERENCES `gemachim`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create gemach views tracking table
CREATE TABLE IF NOT EXISTS `gemach_views` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `gemach_id` INT NOT NULL,
  `ip_address` VARCHAR(45),
  `user_agent` TEXT,
  `viewed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  -- Indexes
  INDEX `idx_gemach` (`gemach_id`),
  INDEX `idx_viewed_at` (`viewed_at`),
  
  -- Foreign key
  FOREIGN KEY (`gemach_id`) REFERENCES `gemachim`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default gemach categories
INSERT INTO `gemach_categories` (`name`, `slug`, `description`, `icon_class`, `sort_order`) VALUES
('Baby & Maternity', 'baby-maternity', 'Baby equipment, maternity items, and children\'s clothing', 'fas fa-baby', 1),
('Clothing', 'clothing', 'Clothing for all ages and occasions', 'fas fa-tshirt', 2),
('Medical Supplies', 'medical-supplies', 'Medical equipment, mobility aids, and health supplies', 'fas fa-medkit', 3),
('Kitchen Items', 'kitchen-items', 'Kitchen appliances, utensils, and cooking equipment', 'fas fa-utensils', 4),
('Simcha Decor', 'simcha-decor', 'Decorations and items for celebrations and events', 'fas fa-birthday-cake', 5),
('Moving & Storage', 'moving-storage', 'Boxes, packing materials, and storage solutions', 'fas fa-boxes', 6),
('Furniture', 'furniture', 'Furniture for home and office', 'fas fa-couch', 7),
('Sefarim/Books', 'sefarim-books', 'Jewish books, sefarim, and educational materials', 'fas fa-book-open', 8),
('Toiletries', 'toiletries', 'Personal care items, mikveh supplies, and hygiene products', 'fas fa-pump-soap', 9),
('Electronics', 'electronics', 'Computers, phones, and electronic devices', 'fas fa-laptop', 10),
('Tools & DIY', 'tools-diy', 'Tools, hardware, and DIY equipment', 'fas fa-tools', 11),
('Sports & Recreation', 'sports-recreation', 'Sports equipment and recreational items', 'fas fa-futbol', 12);

-- Insert sample gemachim for testing
INSERT INTO `gemachim` (`name`, `category_id`, `description`, `location`, `contact_phone`, `contact_email`, `whatsapp_link`, `donation_enabled`, `donation_link`, `in_memory_of`, `verified`, `featured`, `urgent_need`, `status`, `submitted_by`) VALUES
('Twin Pushchair Gemach', 1, 'High-quality twin pushchair available for families in need. Perfect condition, suitable for newborns to toddlers.', 'North London', '+44 20 7123 4567', 'twinpushchair@example.com', 'https://wa.me/442071234567', TRUE, 'https://donate.example.com/twin-pushchair', 'In memory of Sarah Cohen', TRUE, TRUE, FALSE, 'active', NULL),
('Medical Equipment Gemach', 3, 'Wheelchairs, crutches, and other medical equipment available for short-term loan. Clean and well-maintained.', 'Manchester', '+44 161 123 4567', 'medical@example.com', 'https://wa.me/441611234567', TRUE, 'https://donate.example.com/medical', NULL, TRUE, FALSE, TRUE, 'active', NULL),
('Simcha Decor Collection', 5, 'Beautiful decorations for weddings, bar/bat mitzvahs, and other celebrations. Includes tablecloths, centerpieces, and lighting.', 'South London', '+44 20 7987 6543', 'simcha@example.com', 'https://wa.me/442079876543', FALSE, NULL, 'In memory of David Levy', TRUE, FALSE, FALSE, 'active', NULL),
('Kosher Kitchen Equipment', 4, 'Complete set of kosher kitchen equipment including separate utensils, pots, and appliances for meat and dairy.', 'Birmingham', '+44 121 234 5678', 'kitchen@example.com', 'https://wa.me/441212345678', TRUE, 'https://donate.example.com/kitchen', NULL, TRUE, TRUE, FALSE, 'active', NULL),
('Sefarim Library', 8, 'Extensive collection of sefarim, siddurim, and Jewish educational books. Available for study and reference.', 'Gateshead', '+44 191 345 6789', 'sefarim@example.com', 'https://wa.me/441913456789', TRUE, 'https://donate.example.com/sefarim', 'In memory of Rabbi Goldstein', TRUE, FALSE, FALSE, 'active', NULL);

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Show migration results
SELECT 'Gemachim tables created successfully!' as status;
SELECT COUNT(*) as 'Total categories created' FROM `gemach_categories`;
SELECT COUNT(*) as 'Total sample gemachim created' FROM `gemachim`; 