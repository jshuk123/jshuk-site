-- Enhanced Carousel System Database Schema
-- JShuk Advanced Carousel Management System
-- Phase 1: Database Setup

-- Drop existing table if it exists (backup first!)
-- CREATE TABLE carousel_ads_backup AS SELECT * FROM carousel_ads;
-- DROP TABLE IF EXISTS carousel_ads;

-- Enhanced carousel_slides table with all roadmap features
CREATE TABLE carousel_slides (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    subtitle TEXT,
    image_url VARCHAR(255) NOT NULL,
    cta_text VARCHAR(100),
    cta_link VARCHAR(255),
    priority INT DEFAULT 0,
    location VARCHAR(100) DEFAULT 'all', -- 'all', 'london', 'manchester', etc.
    sponsored TINYINT(1) DEFAULT 0,
    start_date DATE,
    end_date DATE,
    active TINYINT(1) DEFAULT 1,
    zone VARCHAR(100) DEFAULT 'homepage', -- 'homepage', 'businesses', 'post-business'
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes for performance
    INDEX idx_active_priority (active, priority),
    INDEX idx_location (location),
    INDEX idx_zone (zone),
    INDEX idx_dates (start_date, end_date),
    INDEX idx_sponsored (sponsored),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Analytics table for tracking carousel performance
CREATE TABLE carousel_analytics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slide_id INT NOT NULL,
    event_type ENUM('impression', 'click', 'hover') NOT NULL,
    user_location VARCHAR(100),
    user_agent TEXT,
    ip_address VARCHAR(45),
    session_id VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign key
    FOREIGN KEY (slide_id) REFERENCES carousel_slides(id) ON DELETE CASCADE,
    
    -- Indexes
    INDEX idx_slide_event (slide_id, event_type),
    INDEX idx_created_at (created_at),
    INDEX idx_location (user_location)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Location mapping table for geolocation support
CREATE TABLE location_mappings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    location_name VARCHAR(100) NOT NULL,
    latitude_min DECIMAL(10, 8),
    latitude_max DECIMAL(10, 8),
    longitude_min DECIMAL(10, 8),
    longitude_max DECIMAL(10, 8),
    display_name VARCHAR(100) NOT NULL,
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_location (location_name),
    INDEX idx_coordinates (latitude_min, latitude_max, longitude_min, longitude_max)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default location mappings
INSERT INTO location_mappings (location_name, latitude_min, latitude_max, longitude_min, longitude_max, display_name) VALUES
('london', 51.3, 51.7, -0.5, 0.3, 'London'),
('manchester', 53.4, 53.5, -2.3, -2.1, 'Manchester'),
('gateshead', 54.9, 55.0, -1.7, -1.5, 'Gateshead'),
('all', NULL, NULL, NULL, NULL, 'All Locations');

-- Migration script to move existing carousel_ads data
-- Uncomment and run this if you want to migrate existing data
/*
INSERT INTO carousel_slides (
    title, subtitle, image_url, cta_text, cta_link, 
    priority, location, sponsored, active, zone, created_at
)
SELECT 
    title, subtitle, image_path, cta_text, cta_url,
    position, 'all', is_auto_generated, active, 'homepage', created_at
FROM carousel_ads;
*/

-- Sample data for testing
INSERT INTO carousel_slides (
    title, subtitle, image_url, cta_text, cta_link, 
    priority, location, sponsored, start_date, end_date, active, zone
) VALUES
(
    'Welcome to JShuk',
    'Your Jewish Community Hub - Discover Local Businesses',
    'uploads/carousel/sample_ad1.jpg',
    'Explore Now',
    'businesses.php',
    10, 'all', 0, NULL, NULL, 1, 'homepage'
),
(
    'Kosher Restaurants in London',
    'Find the best kosher dining in London',
    'uploads/carousel/sample_ad2.jpg',
    'Find Restaurants',
    'businesses.php?category=restaurants&location=london',
    8, 'london', 0, NULL, NULL, 1, 'homepage'
),
(
    'Community Events',
    'Stay connected with your local Jewish community',
    'uploads/carousel/sample_ad3.jpg',
    'View Events',
    'events.php',
    5, 'all', 0, NULL, NULL, 1, 'homepage'
),
(
    'Sponsored: Premium Business',
    'Featured business promotion',
    'uploads/carousel/sponsored_ad.jpg',
    'Learn More',
    'businesses.php?id=123',
    15, 'all', 1, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 1, 'homepage'
);

-- Create views for easier querying
CREATE VIEW active_carousel_slides AS
SELECT * FROM carousel_slides 
WHERE active = 1 
  AND (start_date IS NULL OR start_date <= CURDATE())
  AND (end_date IS NULL OR end_date >= CURDATE())
ORDER BY priority DESC, sponsored DESC, created_at DESC;

CREATE VIEW carousel_performance AS
SELECT 
    cs.id,
    cs.title,
    cs.location,
    cs.sponsored,
    COUNT(CASE WHEN ca.event_type = 'impression' THEN 1 END) as impressions,
    COUNT(CASE WHEN ca.event_type = 'click' THEN 1 END) as clicks,
    ROUND(
        (COUNT(CASE WHEN ca.event_type = 'click' THEN 1 END) / 
         NULLIF(COUNT(CASE WHEN ca.event_type = 'impression' THEN 1 END), 0)) * 100, 2
    ) as ctr_percentage
FROM carousel_slides cs
LEFT JOIN carousel_analytics ca ON cs.id = ca.slide_id
GROUP BY cs.id, cs.title, cs.location, cs.sponsored;

-- Stored procedure for getting slides by location
DELIMITER //
CREATE PROCEDURE GetCarouselSlides(
    IN p_location VARCHAR(100),
    IN p_zone VARCHAR(100),
    IN p_limit INT
)
BEGIN
    SELECT * FROM carousel_slides 
    WHERE active = 1 
      AND (location = p_location OR location = 'all')
      AND zone = p_zone
      AND (start_date IS NULL OR start_date <= CURDATE())
      AND (end_date IS NULL OR end_date >= CURDATE())
    ORDER BY priority DESC, sponsored DESC, created_at DESC
    LIMIT p_limit;
END //
DELIMITER ;

-- Trigger to log analytics
DELIMITER //
CREATE TRIGGER log_carousel_impression
AFTER INSERT ON carousel_analytics
FOR EACH ROW
BEGIN
    -- You can add additional logging logic here
    -- For example, updating a summary table
    INSERT INTO carousel_analytics_summary (slide_id, event_type, count, date)
    VALUES (NEW.slide_id, NEW.event_type, 1, DATE(NEW.created_at))
    ON DUPLICATE KEY UPDATE count = count + 1;
END //
DELIMITER ;

-- Summary table for analytics (optional, for performance)
CREATE TABLE carousel_analytics_summary (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slide_id INT NOT NULL,
    event_type ENUM('impression', 'click', 'hover') NOT NULL,
    count INT DEFAULT 1,
    date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_summary (slide_id, event_type, date),
    FOREIGN KEY (slide_id) REFERENCES carousel_slides(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add some helpful indexes
CREATE INDEX idx_analytics_summary_date ON carousel_analytics_summary(date);
CREATE INDEX idx_analytics_summary_slide ON carousel_analytics_summary(slide_id);

-- Grant permissions (adjust as needed)
-- GRANT SELECT, INSERT, UPDATE, DELETE ON carousel_slides TO 'jshuk_user'@'localhost';
-- GRANT SELECT, INSERT ON carousel_analytics TO 'jshuk_user'@'localhost';

-- Final status check
SELECT 'Enhanced Carousel Schema Created Successfully!' as status;
SELECT COUNT(*) as total_slides FROM carousel_slides;
SELECT COUNT(*) as total_locations FROM location_mappings; 