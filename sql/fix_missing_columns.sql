-- Fix Missing Columns for Free Stuff System
-- Run this script to add the missing columns to the classifieds table

-- Add missing columns one by one to avoid conflicts
ALTER TABLE classifieds 
ADD COLUMN pickup_method ENUM('pickup', 'delivery', 'meetup') DEFAULT 'pickup' AFTER location;

ALTER TABLE classifieds 
ADD COLUMN collection_deadline DATETIME NULL AFTER pickup_method;

ALTER TABLE classifieds 
ADD COLUMN is_anonymous TINYINT(1) DEFAULT 0 AFTER collection_deadline;

ALTER TABLE classifieds 
ADD COLUMN is_chessed TINYINT(1) DEFAULT 0 AFTER is_anonymous;

ALTER TABLE classifieds 
ADD COLUMN is_bundle TINYINT(1) DEFAULT 0 AFTER is_chessed;

ALTER TABLE classifieds 
ADD COLUMN status ENUM('available', 'pending', 'taken', 'expired') DEFAULT 'available' AFTER is_bundle;

ALTER TABLE classifieds 
ADD COLUMN pickup_code VARCHAR(10) NULL AFTER status;

ALTER TABLE classifieds 
ADD COLUMN contact_method ENUM('email', 'phone', 'whatsapp', 'telegram') DEFAULT 'email' AFTER pickup_code;

ALTER TABLE classifieds 
ADD COLUMN contact_info VARCHAR(255) NULL AFTER contact_method;

-- Add indexes for better performance
ALTER TABLE classifieds ADD INDEX idx_status (status);
ALTER TABLE classifieds ADD INDEX idx_is_chessed (is_chessed);
ALTER TABLE classifieds ADD INDEX idx_category_id (category_id);
ALTER TABLE classifieds ADD INDEX idx_created_at (created_at);

-- Update existing records to have proper status
UPDATE classifieds SET status = 'available' WHERE status IS NULL;

-- Set default values for existing records
UPDATE classifieds SET 
    pickup_method = 'pickup',
    is_anonymous = 0,
    is_chessed = 0,
    is_bundle = 0,
    contact_method = 'email'
WHERE pickup_method IS NULL;

SELECT '✅ All missing columns added successfully!' as result; 