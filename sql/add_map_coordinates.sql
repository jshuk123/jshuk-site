-- Add Map Coordinates Support to Businesses Table
-- This script adds latitude and longitude columns for geocoding

-- Add latitude and longitude columns to businesses table
ALTER TABLE businesses 
ADD COLUMN IF NOT EXISTS latitude DECIMAL(10, 8) NULL AFTER address,
ADD COLUMN IF NOT EXISTS longitude DECIMAL(11, 8) NULL AFTER latitude;

-- Add indexes for better performance on location-based queries
ALTER TABLE businesses 
ADD INDEX IF NOT EXISTS idx_coordinates (latitude, longitude),
ADD INDEX IF NOT EXISTS idx_location_search (latitude, longitude, status);

-- Add a column to track geocoding status
ALTER TABLE businesses 
ADD COLUMN IF NOT EXISTS geocoded TINYINT(1) DEFAULT 0 AFTER longitude;

-- Add index for geocoding status
ALTER TABLE businesses 
ADD INDEX IF NOT EXISTS idx_geocoded (geocoded);

-- Update existing businesses to mark them as not geocoded
UPDATE businesses SET geocoded = 0 WHERE geocoded IS NULL;

-- Create a function to calculate distance between two points (Haversine formula)
DELIMITER //
CREATE FUNCTION IF NOT EXISTS calculate_distance(
    lat1 DECIMAL(10, 8), 
    lon1 DECIMAL(11, 8), 
    lat2 DECIMAL(10, 8), 
    lon2 DECIMAL(11, 8)
) 
RETURNS DECIMAL(10, 2)
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE R DECIMAL(10, 2) DEFAULT 6371; -- Earth's radius in kilometers
    DECLARE dlat DECIMAL(10, 8);
    DECLARE dlon DECIMAL(11, 8);
    DECLARE a DECIMAL(10, 8);
    DECLARE c DECIMAL(10, 8);
    
    SET dlat = RADIANS(lat2 - lat1);
    SET dlon = RADIANS(lon2 - lon1);
    SET a = SIN(dlat/2) * SIN(dlat/2) + 
            COS(RADIANS(lat1)) * COS(RADIANS(lat2)) * 
            SIN(dlon/2) * SIN(dlon/2);
    SET c = 2 * ATAN2(SQRT(a), SQRT(1-a));
    
    RETURN R * c;
END //
DELIMITER ;

-- Create a view for businesses with valid coordinates
CREATE OR REPLACE VIEW businesses_with_coordinates AS
SELECT 
    b.*,
    c.name as category_name,
    COALESCE(AVG(r.rating), 0) as average_rating,
    COUNT(r.id) as calculated_review_count
FROM businesses b
LEFT JOIN business_categories c ON b.category_id = c.id
LEFT JOIN users u ON b.user_id = u.id
LEFT JOIN reviews r ON b.id = r.business_id
WHERE b.latitude IS NOT NULL 
  AND b.longitude IS NOT NULL 
  AND b.status = 'active'
GROUP BY b.id;

-- Insert sample coordinates for testing (London area)
-- This will be replaced by real geocoding
UPDATE businesses 
SET 
    latitude = 51.5074 + (RAND() - 0.5) * 0.1,
    longitude = -0.1278 + (RAND() - 0.5) * 0.1,
    geocoded = 1
WHERE geocoded = 0 
  AND status = 'active'
LIMIT 50; -- Limit to avoid overwhelming the system

-- Create a stored procedure for bulk geocoding
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS geocode_businesses()
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE business_id INT;
    DECLARE business_address TEXT;
    DECLARE cur CURSOR FOR 
        SELECT id, address 
        FROM businesses 
        WHERE geocoded = 0 
          AND address IS NOT NULL 
          AND address != ''
          AND status = 'active'
        LIMIT 100; -- Process in batches
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    OPEN cur;
    
    read_loop: LOOP
        FETCH cur INTO business_id, business_address;
        IF done THEN
            LEAVE read_loop;
        END IF;
        
        -- Here you would call your geocoding service
        -- For now, we'll use sample coordinates
        UPDATE businesses 
        SET 
            latitude = 51.5074 + (RAND() - 0.5) * 0.1,
            longitude = -0.1278 + (RAND() - 0.5) * 0.1,
            geocoded = 1
        WHERE id = business_id;
        
    END LOOP;
    
    CLOSE cur;
END //
DELIMITER ;

-- Create a function to get businesses within a radius
DELIMITER //
CREATE FUNCTION IF NOT EXISTS get_businesses_nearby(
    center_lat DECIMAL(10, 8),
    center_lon DECIMAL(11, 8),
    radius_km DECIMAL(10, 2)
) 
RETURNS TEXT
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE result TEXT DEFAULT '';
    DECLARE business_count INT DEFAULT 0;
    
    SELECT COUNT(*) INTO business_count
    FROM businesses 
    WHERE geocoded = 1 
      AND status = 'active'
      AND calculate_distance(latitude, longitude, center_lat, center_lon) <= radius_km;
    
    SET result = CONCAT('Found ', business_count, ' businesses within ', radius_km, 'km');
    
    RETURN result;
END //
DELIMITER ;

-- Add comments to document the new columns
ALTER TABLE businesses 
MODIFY COLUMN latitude DECIMAL(10, 8) NULL COMMENT 'Latitude coordinate for map display',
MODIFY COLUMN longitude DECIMAL(11, 8) NULL COMMENT 'Longitude coordinate for map display',
MODIFY COLUMN geocoded TINYINT(1) DEFAULT 0 COMMENT 'Whether this business has been geocoded';

-- Show summary of changes
SELECT 
    'Businesses with coordinates' as status,
    COUNT(*) as count
FROM businesses 
WHERE geocoded = 1 AND latitude IS NOT NULL AND longitude IS NOT NULL
UNION ALL
SELECT 
    'Businesses needing geocoding' as status,
    COUNT(*) as count
FROM businesses 
WHERE geocoded = 0 OR latitude IS NULL OR longitude IS NULL; 