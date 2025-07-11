-- =========================
-- Retreats & Simcha Rentals System
-- =========================
-- This script creates the database tables for the JShuk Retreats & Simcha Rentals Directory

-- Disable foreign key checks
SET FOREIGN_KEY_CHECKS = 0;

-- Create retreat categories table
CREATE TABLE IF NOT EXISTS `retreat_categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(100) NOT NULL,
  `description` TEXT,
  `icon_class` VARCHAR(100) DEFAULT 'fas fa-home',
  `emoji` VARCHAR(10) DEFAULT '🏠',
  `sort_order` INT DEFAULT 0,
  `is_active` BOOLEAN DEFAULT TRUE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  -- Indexes
  UNIQUE KEY `unique_slug` (`slug`),
  INDEX `idx_active_sort` (`is_active`, `sort_order`),
  INDEX `idx_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create retreat locations table
CREATE TABLE IF NOT EXISTS `retreat_locations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(100) NOT NULL,
  `region` VARCHAR(100),
  `country` VARCHAR(100) DEFAULT 'UK',
  `is_active` BOOLEAN DEFAULT TRUE,
  `sort_order` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  -- Indexes
  UNIQUE KEY `unique_slug` (`slug`),
  INDEX `idx_active_sort` (`is_active`, `sort_order`),
  INDEX `idx_region` (`region`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create retreats table
CREATE TABLE IF NOT EXISTS `retreats` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `category_id` INT,
  `location_id` INT,
  `host_id` INT,
  `description` TEXT,
  `short_description` VARCHAR(500),
  `image_paths` TEXT COMMENT 'JSON array of image paths',
  `price_per_night` DECIMAL(10,2),
  `price_shabbos_package` DECIMAL(10,2),
  `price_yt_package` DECIMAL(10,2),
  `currency` VARCHAR(3) DEFAULT 'GBP',
  `guest_capacity` INT DEFAULT 1,
  `bedrooms` INT DEFAULT 1,
  `bathrooms` INT DEFAULT 1,
  `address` TEXT,
  `postcode` VARCHAR(20),
  `latitude` DECIMAL(10,8),
  `longitude` DECIMAL(11,8),
  `distance_to_shul` DECIMAL(5,2) COMMENT 'Distance in meters',
  `nearest_shul` VARCHAR(255),
  `private_entrance` BOOLEAN DEFAULT FALSE,
  `kosher_kitchen` BOOLEAN DEFAULT FALSE,
  `kitchen_type` ENUM('meat', 'dairy', 'parve', 'separate') DEFAULT 'parve',
  `shabbos_equipped` BOOLEAN DEFAULT FALSE,
  `plata_available` BOOLEAN DEFAULT FALSE,
  `wifi_available` BOOLEAN DEFAULT TRUE,
  `air_conditioning` BOOLEAN DEFAULT FALSE,
  `baby_cot_available` BOOLEAN DEFAULT FALSE,
  `no_stairs` BOOLEAN DEFAULT FALSE,
  `accessible` BOOLEAN DEFAULT FALSE,
  `mikveh_nearby` BOOLEAN DEFAULT FALSE,
  `mikveh_distance` DECIMAL(5,2) COMMENT 'Distance in meters',
  `parking_available` BOOLEAN DEFAULT FALSE,
  `garden_access` BOOLEAN DEFAULT FALSE,
  `min_stay_nights` INT DEFAULT 1,
  `max_stay_nights` INT DEFAULT 30,
  `available_this_shabbos` BOOLEAN DEFAULT FALSE,
  `instant_booking` BOOLEAN DEFAULT FALSE,
  `verified` BOOLEAN DEFAULT FALSE,
  `featured` BOOLEAN DEFAULT FALSE,
  `trusted_host` BOOLEAN DEFAULT FALSE,
  `status` ENUM('active', 'pending', 'inactive', 'booked') DEFAULT 'pending',
  `views_count` INT DEFAULT 0,
  `bookings_count` INT DEFAULT 0,
  `rating_average` DECIMAL(3,2) DEFAULT 0,
  `rating_count` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  -- Indexes
  INDEX `idx_category` (`category_id`),
  INDEX `idx_location` (`location_id`),
  INDEX `idx_host` (`host_id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_verified` (`verified`),
  INDEX `idx_featured` (`featured`),
  INDEX `idx_trusted` (`trusted_host`),
  INDEX `idx_available_shabbos` (`available_this_shabbos`),
  INDEX `idx_price` (`price_per_night`),
  INDEX `idx_capacity` (`guest_capacity`),
  INDEX `idx_rating` (`rating_average`),
  INDEX `idx_created_at` (`created_at`),
  INDEX `idx_coordinates` (`latitude`, `longitude`),
  
  -- Foreign keys
  FOREIGN KEY (`category_id`) REFERENCES `retreat_categories`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`location_id`) REFERENCES `retreat_locations`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`host_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create retreat amenities table
CREATE TABLE IF NOT EXISTS `retreat_amenities` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `icon_class` VARCHAR(100),
  `category` ENUM('essential', 'comfort', 'luxury', 'accessibility', 'kosher') DEFAULT 'essential',
  `is_active` BOOLEAN DEFAULT TRUE,
  `sort_order` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  -- Indexes
  INDEX `idx_category` (`category`),
  INDEX `idx_active_sort` (`is_active`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create retreat-amenity relationship table
CREATE TABLE IF NOT EXISTS `retreat_amenity_relations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `retreat_id` INT NOT NULL,
  `amenity_id` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  -- Indexes
  UNIQUE KEY `unique_retreat_amenity` (`retreat_id`, `amenity_id`),
  INDEX `idx_retreat` (`retreat_id`),
  INDEX `idx_amenity` (`amenity_id`),
  
  -- Foreign keys
  FOREIGN KEY (`retreat_id`) REFERENCES `retreats`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`amenity_id`) REFERENCES `retreat_amenities`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create retreat tags table
CREATE TABLE IF NOT EXISTS `retreat_tags` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `color` VARCHAR(7) DEFAULT '#1a3353',
  `is_active` BOOLEAN DEFAULT TRUE,
  `sort_order` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  -- Indexes
  INDEX `idx_active_sort` (`is_active`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create retreat-tag relationship table
CREATE TABLE IF NOT EXISTS `retreat_tag_relations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `retreat_id` INT NOT NULL,
  `tag_id` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  -- Indexes
  UNIQUE KEY `unique_retreat_tag` (`retreat_id`, `tag_id`),
  INDEX `idx_retreat` (`retreat_id`),
  INDEX `idx_tag` (`tag_id`),
  
  -- Foreign keys
  FOREIGN KEY (`retreat_id`) REFERENCES `retreats`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`tag_id`) REFERENCES `retreat_tags`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create retreat availability table
CREATE TABLE IF NOT EXISTS `retreat_availability` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `retreat_id` INT NOT NULL,
  `date` DATE NOT NULL,
  `is_available` BOOLEAN DEFAULT TRUE,
  `price_override` DECIMAL(10,2),
  `notes` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  -- Indexes
  UNIQUE KEY `unique_retreat_date` (`retreat_id`, `date`),
  INDEX `idx_retreat` (`retreat_id`),
  INDEX `idx_date` (`date`),
  INDEX `idx_available` (`is_available`),
  
  -- Foreign key
  FOREIGN KEY (`retreat_id`) REFERENCES `retreats`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create retreat bookings table
CREATE TABLE IF NOT EXISTS `retreat_bookings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `retreat_id` INT NOT NULL,
  `guest_id` INT,
  `host_id` INT NOT NULL,
  `check_in_date` DATE NOT NULL,
  `check_out_date` DATE NOT NULL,
  `guest_count` INT DEFAULT 1,
  `total_price` DECIMAL(10,2) NOT NULL,
  `currency` VARCHAR(3) DEFAULT 'GBP',
  `status` ENUM('pending', 'confirmed', 'cancelled', 'completed') DEFAULT 'pending',
  `guest_notes` TEXT,
  `host_notes` TEXT,
  `contact_phone` VARCHAR(50),
  `contact_email` VARCHAR(100),
  `whatsapp_link` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  -- Indexes
  INDEX `idx_retreat` (`retreat_id`),
  INDEX `idx_guest` (`guest_id`),
  INDEX `idx_host` (`host_id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_dates` (`check_in_date`, `check_out_date`),
  INDEX `idx_created_at` (`created_at`),
  
  -- Foreign keys
  FOREIGN KEY (`retreat_id`) REFERENCES `retreats`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`guest_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`host_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create retreat reviews table
CREATE TABLE IF NOT EXISTS `retreat_reviews` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `retreat_id` INT NOT NULL,
  `booking_id` INT,
  `guest_id` INT,
  `host_id` INT NOT NULL,
  `rating` TINYINT CHECK (`rating` >= 1 AND `rating` <= 5),
  `review_text` TEXT,
  `cleanliness_rating` TINYINT CHECK (`cleanliness_rating` >= 1 AND `cleanliness_rating` <= 5),
  `communication_rating` TINYINT CHECK (`communication_rating` >= 1 AND `communication_rating` <= 5),
  `location_rating` TINYINT CHECK (`location_rating` >= 1 AND `location_rating` <= 5),
  `value_rating` TINYINT CHECK (`value_rating` >= 1 AND `value_rating` <= 5),
  `is_approved` BOOLEAN DEFAULT FALSE,
  `is_public` BOOLEAN DEFAULT TRUE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  -- Indexes
  INDEX `idx_retreat` (`retreat_id`),
  INDEX `idx_booking` (`booking_id`),
  INDEX `idx_guest` (`guest_id`),
  INDEX `idx_host` (`host_id`),
  INDEX `idx_rating` (`rating`),
  INDEX `idx_approved` (`is_approved`),
  INDEX `idx_public` (`is_public`),
  
  -- Foreign keys
  FOREIGN KEY (`retreat_id`) REFERENCES `retreats`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`booking_id`) REFERENCES `retreat_bookings`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`guest_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`host_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create retreat views tracking table
CREATE TABLE IF NOT EXISTS `retreat_views` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `retreat_id` INT NOT NULL,
  `ip_address` VARCHAR(45),
  `user_agent` TEXT,
  `user_id` INT,
  `viewed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  -- Indexes
  INDEX `idx_retreat` (`retreat_id`),
  INDEX `idx_user` (`user_id`),
  INDEX `idx_viewed_at` (`viewed_at`),
  
  -- Foreign keys
  FOREIGN KEY (`retreat_id`) REFERENCES `retreats`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default retreat categories
INSERT INTO `retreat_categories` (`name`, `slug`, `description`, `icon_class`, `emoji`, `sort_order`) VALUES
('Chosson/Kallah Flat', 'chosson-kallah', 'Perfect accommodations for newlyweds and wedding guests', 'fas fa-heart', '🏘', 1),
('Emchutanim Flat', 'emchutanim', 'Comfortable stays for in-laws and family visitors', 'fas fa-users', '🏠', 2),
('Shabbos Getaway', 'shabbos-getaway', 'Peaceful Shabbos retreats and weekend stays', 'fas fa-star-of-david', '🕍', 3),
('Ladies Retreat', 'ladies-retreat', 'Quiet accommodations for women and girls', 'fas fa-female', '🛌', 4),
('Yom Tov Home', 'yom-tov-home', 'Family homes for holiday celebrations', 'fas fa-home', '🌿', 5),
('Simcha Nearby', 'simcha-nearby', 'Convenient stays near simcha venues', 'fas fa-glass-cheers', '🎉', 6),
('Host Family', 'host-family', 'Warm family hosting experiences', 'fas fa-house-user', '🧳', 7);

-- Insert default retreat locations
INSERT INTO `retreat_locations` (`name`, `slug`, `region`, `sort_order`) VALUES
('Stamford Hill', 'stamford-hill', 'North London', 1),
('Golders Green', 'golders-green', 'North London', 2),
('Hendon', 'hendon', 'North London', 3),
('Edgware', 'edgware', 'North London', 4),
('Finchley', 'finchley', 'North London', 5),
('Manchester', 'manchester', 'North West', 6),
('Gateshead', 'gateshead', 'North East', 7),
('Leeds', 'leeds', 'Yorkshire', 8),
('Birmingham', 'birmingham', 'West Midlands', 9),
('Liverpool', 'liverpool', 'North West', 10),
('Glasgow', 'glasgow', 'Scotland', 11),
('Cardiff', 'cardiff', 'Wales', 12),
('Jerusalem', 'jerusalem', 'Israel', 13),
('Tel Aviv', 'tel-aviv', 'Israel', 14),
('Bnei Brak', 'bnei-brak', 'Israel', 15);

-- Insert default retreat amenities
INSERT INTO `retreat_amenities` (`name`, `icon_class`, `category`, `sort_order`) VALUES
-- Essential amenities
('WiFi', 'fas fa-wifi', 'essential', 1),
('Kosher Kitchen', 'fas fa-utensils', 'essential', 2),
('Private Entrance', 'fas fa-door-open', 'essential', 3),
('Parking', 'fas fa-parking', 'essential', 4),
('Heating', 'fas fa-thermometer-half', 'essential', 5),

-- Comfort amenities
('Air Conditioning', 'fas fa-snowflake', 'comfort', 6),
('Garden Access', 'fas fa-seedling', 'comfort', 7),
('Baby Cot', 'fas fa-baby', 'comfort', 8),
('Extra Bedding', 'fas fa-bed', 'comfort', 9),
('Washing Machine', 'fas fa-tshirt', 'comfort', 10),

-- Luxury amenities
('Plata/Shabbos Urn', 'fas fa-fire', 'luxury', 11),
('Shabbos Equipped', 'fas fa-star-of-david', 'luxury', 12),
('Mikveh Nearby', 'fas fa-water', 'luxury', 13),
('Near Minyan', 'fas fa-mosque', 'luxury', 14),
('Eruv Available', 'fas fa-circle', 'luxury', 15),

-- Accessibility amenities
('No Stairs', 'fas fa-wheelchair', 'accessibility', 16),
('Accessible Bathroom', 'fas fa-bath', 'accessibility', 17),
('Ground Floor', 'fas fa-level-down-alt', 'accessibility', 18),

-- Kosher amenities
('Separate Meat/Dairy', 'fas fa-cutlery', 'kosher', 19),
('Pesach Kitchen', 'fas fa-bread-slice', 'kosher', 20),
('Mashgiach Certified', 'fas fa-certificate', 'kosher', 21);

-- Insert default retreat tags
INSERT INTO `retreat_tags` (`name`, `color`, `sort_order`) VALUES
('Chosson/Kallah Ready', '#ff6b6b', 1),
('Kosher Setup', '#4ecdc4', 2),
('Mikveh Nearby', '#45b7d1', 3),
('Extra Bedding', '#96ceb4', 4),
('Shabbos Equipped', '#feca57', 5),
('Last-Minute Discount', '#ff9ff3', 6),
('Trusted Host', '#54a0ff', 7),
('Kallah-Teacher Endorsed', '#5f27cd', 8),
('Mashgiach Certified', '#00d2d3', 9),
('Family Friendly', '#ff9f43', 10),
('Luxury', '#ff6348', 11),
('No Stairs', '#2ed573', 12),
('Host Speaks Hebrew', '#1e90ff', 13),
('Host Speaks Yiddish', '#ffa502', 14),
('Host Speaks English', '#3742fa', 15);

-- Insert sample retreats for testing
INSERT INTO `retreats` (`title`, `category_id`, `location_id`, `host_id`, `description`, `short_description`, `price_per_night`, `price_shabbos_package`, `guest_capacity`, `bedrooms`, `bathrooms`, `address`, `postcode`, `private_entrance`, `kosher_kitchen`, `kitchen_type`, `shabbos_equipped`, `wifi_available`, `available_this_shabbos`, `verified`, `featured`, `status`) VALUES
('Beautiful Chosson/Kallah Flat in Golders Green', 1, 2, NULL, 'Perfect newlywed accommodation with separate entrance, kosher kitchen, and all amenities. Walking distance to shuls and shops.', 'Luxury flat perfect for chosson/kallah with private entrance and kosher kitchen.', 120.00, 200.00, 2, 1, 1, '123 Golders Green Road', 'NW11 8AB', TRUE, TRUE, 'separate', TRUE, TRUE, TRUE, TRUE, TRUE, 'active'),
('Family Home for Shabbos in Hendon', 3, 3, NULL, 'Spacious family home perfect for Shabbos getaways. Large kitchen, garden access, and near multiple shuls.', 'Comfortable family home with garden, perfect for Shabbos stays.', 150.00, 250.00, 6, 3, 2, '456 Hendon Way', 'NW4 2RS', TRUE, TRUE, 'meat', TRUE, TRUE, TRUE, TRUE, FALSE, 'active'),
('Emchutanim Flat in Stamford Hill', 2, 1, NULL, 'Comfortable ground floor flat ideal for in-laws. No stairs, accessible bathroom, and close to all amenities.', 'Accessible ground floor flat perfect for emchutanim with no stairs.', 100.00, 180.00, 4, 2, 1, '789 Stamford Hill', 'N16 5AB', TRUE, TRUE, 'dairy', FALSE, TRUE, FALSE, TRUE, FALSE, 'active'),
('Ladies Retreat in Edgware', 4, 4, NULL, 'Peaceful accommodation for women and girls. Quiet location, separate entrance, and mikveh nearby.', 'Quiet ladies retreat with mikveh nearby and separate entrance.', 80.00, 140.00, 2, 1, 1, '321 Edgware Road', 'HA8 8XY', TRUE, TRUE, 'parve', TRUE, TRUE, TRUE, TRUE, FALSE, 'active'),
('Yom Tov Home in Manchester', 5, 6, NULL, 'Large family home perfect for Yom Tov celebrations. Multiple bedrooms, large kitchen, and garden.', 'Spacious family home ideal for Yom Tov with large kitchen and garden.', 200.00, 350.00, 8, 4, 2, '654 Manchester Street', 'M1 1AA', TRUE, TRUE, 'separate', TRUE, TRUE, TRUE, TRUE, TRUE, 'active');

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Show migration results
SELECT 'Retreats tables created successfully!' as status;
SELECT COUNT(*) as 'Total categories created' FROM `retreat_categories`;
SELECT COUNT(*) as 'Total locations created' FROM `retreat_locations`;
SELECT COUNT(*) as 'Total amenities created' FROM `retreat_amenities`;
SELECT COUNT(*) as 'Total tags created' FROM `retreat_tags`;
SELECT COUNT(*) as 'Total sample retreats created' FROM `retreats`; 