-- Fix Map Coordinates - Simplified Version
-- This script adds the necessary columns for map functionality

-- Add latitude and longitude columns to businesses table
ALTER TABLE businesses 
ADD COLUMN IF NOT EXISTS latitude DECIMAL(10, 8) NULL AFTER address,
ADD COLUMN IF NOT EXISTS longitude DECIMAL(11, 8) NULL AFTER latitude;

-- Add a column to track geocoding status
ALTER TABLE businesses 
ADD COLUMN IF NOT EXISTS geocoded TINYINT(1) DEFAULT 0 AFTER longitude;

-- Add indexes for better performance on location-based queries
ALTER TABLE businesses 
ADD INDEX IF NOT EXISTS idx_coordinates (latitude, longitude),
ADD INDEX IF NOT EXISTS idx_location_search (latitude, longitude, status),
ADD INDEX IF NOT EXISTS idx_geocoded (geocoded);

-- Update existing businesses to mark them as not geocoded
UPDATE businesses SET geocoded = 0 WHERE geocoded IS NULL;

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