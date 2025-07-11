-- Check the current structure of classifieds_categories table
-- Run this to see what columns actually exist

DESCRIBE `classifieds_categories`;

-- Also check if the table exists
SHOW TABLES LIKE 'classifieds_categories';

-- Check if there are any existing records
SELECT COUNT(*) as total_categories FROM `classifieds_categories`;

-- Show any existing categories
SELECT * FROM `classifieds_categories` LIMIT 5; 