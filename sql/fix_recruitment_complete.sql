-- Comprehensive Fix for Recruitment System
-- This script addresses all potential issues with the recruitment page

-- 1. Add missing is_featured column to recruitment table
ALTER TABLE `recruitment` 
ADD COLUMN IF NOT EXISTS `is_featured` TINYINT(1) NOT NULL DEFAULT 0 
AFTER `is_active`;

-- 2. Add index for better performance
ALTER TABLE `recruitment` 
ADD INDEX IF NOT EXISTS `idx_featured_active` (`is_featured`, `is_active`);

-- 3. Add index for sorting by creation date
ALTER TABLE `recruitment` 
ADD INDEX IF NOT EXISTS `idx_created_at` (`created_at`);

-- 4. Add index for sector filtering
ALTER TABLE `recruitment` 
ADD INDEX IF NOT EXISTS `idx_sector_active` (`sector_id`, `is_active`);

-- 5. Add index for location filtering
ALTER TABLE `recruitment` 
ADD INDEX IF NOT EXISTS `idx_location_active` (`job_location`, `is_active`);

-- 6. Add index for job type filtering
ALTER TABLE `recruitment` 
ADD INDEX IF NOT EXISTS `idx_job_type_active` (`job_type`, `is_active`);

-- 7. Ensure job_sectors table has required data
INSERT IGNORE INTO `job_sectors` (`name`, `slug`) VALUES
('Accounting', 'accounting'),
('Administration', 'administration'),
('Customer Service', 'customer-service'),
('Engineering', 'engineering'),
('Healthcare', 'healthcare'),
('Hospitality', 'hospitality'),
('IT & Technology', 'it-technology'),
('Marketing & Sales', 'marketing-sales'),
('Retail', 'retail-jobs'),
('Skilled Trades', 'skilled-trades'),
('Education', 'education'),
('Finance', 'finance'),
('Legal', 'legal'),
('Non-Profit', 'non-profit'),
('Real Estate', 'real-estate'),
('Transportation', 'transportation');

-- 8. Add sample featured job if no featured jobs exist
UPDATE `recruitment` 
SET `is_featured` = 1 
WHERE `id` = (
    SELECT `id` FROM (
        SELECT `id` FROM `recruitment` 
        WHERE `is_active` = 1 
        ORDER BY `created_at` DESC 
        LIMIT 1
    ) AS temp
) 
AND NOT EXISTS (
    SELECT 1 FROM `recruitment` WHERE `is_featured` = 1 AND `is_active` = 1
);

-- 9. Verify table structure
-- This will show the current structure after fixes
DESCRIBE `recruitment`; 