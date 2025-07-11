-- Add Free Stuff / Chessed Giveaway System to JShuk Classifieds
-- This script adds the necessary database structure for the new feature

-- 1. Add category_id column to classifieds table if it doesn't exist
ALTER TABLE `classifieds` 
ADD COLUMN `category_id` INT NULL AFTER `user_id`,
ADD COLUMN `pickup_method` ENUM('porch_pickup', 'contact_arrange', 'collection_code') NULL AFTER `location`,
ADD COLUMN `collection_deadline` DATETIME NULL AFTER `pickup_method`,
ADD COLUMN `is_anonymous` TINYINT(1) DEFAULT 0 AFTER `collection_deadline`,
ADD COLUMN `is_chessed` TINYINT(1) DEFAULT 0 AFTER `is_anonymous`,
ADD COLUMN `is_bundle` TINYINT(1) DEFAULT 0 AFTER `is_chessed`,
ADD COLUMN `status` ENUM('available', 'pending_pickup', 'claimed', 'expired') DEFAULT 'available' AFTER `is_bundle`,
ADD COLUMN `pickup_code` VARCHAR(10) NULL AFTER `status`,
ADD COLUMN `contact_method` ENUM('whatsapp', 'email', 'phone') DEFAULT 'whatsapp' AFTER `pickup_code`,
ADD COLUMN `contact_info` VARCHAR(255) NULL AFTER `contact_method`;

-- 2. Create classifieds_categories table
CREATE TABLE IF NOT EXISTS `classifieds_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(110) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Insert default categories including Free Stuff
INSERT INTO `classifieds_categories` (`name`, `slug`, `description`, `icon`, `sort_order`) VALUES
('Free Stuff', 'free-stuff', 'Free items and chessed giveaways', '♻️', 1),
('Furniture', 'furniture', 'Furniture and home furnishings', '🛋️', 2),
('Electronics', 'electronics', 'Electronics and gadgets', '💻', 3),
('Books & Seforim', 'books-seforim', 'Books, seforim, and educational materials', '📚', 4),
('Clothing', 'clothing', 'Clothing and accessories', '👕', 5),
('Toys & Games', 'toys-games', 'Toys, games, and children\'s items', '🧸', 6),
('Kitchen Items', 'kitchen-items', 'Kitchen appliances and utensils', '🍽️', 7),
('Jewelry', 'jewelry', 'Jewelry and accessories', '💎', 8),
('Judaica', 'judaica', 'Jewish religious items and books', '🕯️', 9),
('Office & School', 'office-school', 'Office supplies and school materials', '💼', 10),
('Baby & Kids', 'baby-kids', 'Baby and children\'s items', '👶', 11),
('Miscellaneous', 'miscellaneous', 'Other items', '📦', 12);

-- 4. Create free_stuff_requests table for tracking item requests
CREATE TABLE IF NOT EXISTS `free_stuff_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `classified_id` int(11) NOT NULL,
  `requester_id` int(11) NOT NULL,
  `requester_name` varchar(255) NOT NULL,
  `requester_contact` varchar(255) NOT NULL,
  `message` text DEFAULT NULL,
  `status` ENUM('pending', 'approved', 'rejected', 'completed') DEFAULT 'pending',
  `requested_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `responded_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `classified_id` (`classified_id`),
  KEY `requester_id` (`requester_id`),
  CONSTRAINT `free_stuff_requests_ibfk_1` FOREIGN KEY (`classified_id`) REFERENCES `classifieds` (`id`) ON DELETE CASCADE,
  CONSTRAINT `free_stuff_requests_ibfk_2` FOREIGN KEY (`requester_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Add foreign key constraint for category_id
ALTER TABLE `classifieds` 
ADD CONSTRAINT `classifieds_category_fk` 
FOREIGN KEY (`category_id`) REFERENCES `classifieds_categories` (`id`) ON DELETE SET NULL;

-- 6. Create indexes for better performance
CREATE INDEX `idx_classifieds_category` ON `classifieds` (`category_id`);
CREATE INDEX `idx_classifieds_status` ON `classifieds` (`status`);
CREATE INDEX `idx_classifieds_price` ON `classifieds` (`price`);
CREATE INDEX `idx_classifieds_created` ON `classifieds` (`created_at`);
CREATE INDEX `idx_classifieds_free` ON `classifieds` (`price`, `status`) WHERE `price` = 0;

-- 7. Add some sample free items for testing (optional)
INSERT INTO `classifieds` (`user_id`, `category_id`, `title`, `description`, `price`, `location`, `pickup_method`, `is_chessed`, `status`, `contact_method`) VALUES
(1, 1, 'Free Baby Clothes Bundle', 'Bag of gently used baby clothes, sizes 0-6 months. Perfect condition, just outgrown.', 0.00, 'Manchester', 'porch_pickup', 1, 'available', 'whatsapp'),
(1, 1, 'Free Books - Jewish History', 'Collection of Jewish history books. Moving house and need to declutter.', 0.00, 'London', 'contact_arrange', 1, 'available', 'email'),
(1, 1, 'Free Kitchen Items', 'Various kitchen items including pots, pans, and utensils. All in good condition.', 0.00, 'Leeds', 'collection_code', 1, 'available', 'whatsapp');

-- 8. Update existing classifieds to have a default category if they don't have one
UPDATE `classifieds` SET `category_id` = 12 WHERE `category_id` IS NULL; 