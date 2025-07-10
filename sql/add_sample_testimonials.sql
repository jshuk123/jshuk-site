-- Add Sample Testimonials for Homepage
-- This script adds sample testimonials to populate the homepage testimonials section

-- First, ensure the testimonials table exists with proper structure
CREATE TABLE IF NOT EXISTS `testimonials` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `business_id` int(11) DEFAULT NULL,
  `content` text NOT NULL,
  `rating` int(11) DEFAULT 5,
  `reviewer_name` varchar(255) DEFAULT NULL,
  `is_approved` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `business_id` (`business_id`),
  KEY `is_approved` (`is_approved`),
  CONSTRAINT `testimonials_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `testimonials_ibfk_2` FOREIGN KEY (`business_id`) REFERENCES `businesses` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample testimonials (only if they don't already exist)
INSERT IGNORE INTO `testimonials` (`user_id`, `business_id`, `content`, `rating`, `reviewer_name`, `is_approved`) VALUES
(NULL, NULL, 'JShuk helped me discover incredible local Jewish businesses I never knew existed. It\'s an essential resource for our community!', 5, 'Sarah L.', 1),
(NULL, NULL, 'Listing my business on JShuk was the best decision! I\'ve seen a significant increase in local customers. Highly recommend!', 5, 'M. Cohen', 1),
(NULL, NULL, 'Finding jobs in the Jewish community used to be difficult, but JShuk\'s job board made it so easy. I found my dream role here!', 5, 'David S.', 1),
(NULL, NULL, 'The classifieds section is fantastic. I found exactly what I was looking for and the community is so helpful.', 5, 'Rachel G.', 1),
(NULL, NULL, 'As a business owner, JShuk has been invaluable for connecting with our local Jewish community. The platform is user-friendly and effective.', 5, 'A. Goldstein', 1),
(NULL, NULL, 'I love how easy it is to find kosher restaurants and services in my area. JShuk has become my go-to directory.', 5, 'Leah M.', 1);

-- Update any existing testimonials to be approved if they're not already
UPDATE `testimonials` SET `is_approved` = 1 WHERE `is_approved` = 0 OR `is_approved` IS NULL;

-- Add indexes for better performance (if they don't exist)
ALTER TABLE `testimonials` ADD INDEX IF NOT EXISTS `idx_created_at` (`created_at`);
ALTER TABLE `testimonials` ADD INDEX IF NOT EXISTS `idx_rating` (`rating`);
ALTER TABLE `testimonials` ADD INDEX IF NOT EXISTS `idx_is_approved` (`is_approved`); 