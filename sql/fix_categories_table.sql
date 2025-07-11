-- Fix classifieds_categories table structure
-- This script ensures the table has all required columns

-- First, let's drop and recreate the table to ensure proper structure
DROP TABLE IF EXISTS `classifieds_categories`;

CREATE TABLE `classifieds_categories` (
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

-- Now insert the categories
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