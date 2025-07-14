-- Add is_featured column to recruitment table
-- This column is required by the recruitment.php page but missing from the original table structure

ALTER TABLE `recruitment` 
ADD COLUMN `is_featured` TINYINT(1) NOT NULL DEFAULT 0 
AFTER `is_active`;

-- Add index for better performance when filtering by featured status
ALTER TABLE `recruitment` 
ADD INDEX `idx_featured_active` (`is_featured`, `is_active`);

-- Update existing records to have at least one featured job (optional)
-- Uncomment the line below if you want to mark the most recent job as featured
-- UPDATE `recruitment` SET `is_featured` = 1 WHERE `id` = (SELECT MAX(`id`) FROM `recruitment` WHERE `is_active` = 1); 