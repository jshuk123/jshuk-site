-- Add User Ban Feature Migration
-- This script adds the necessary fields to support user banning functionality

-- Disable foreign key checks
SET FOREIGN_KEY_CHECKS = 0;

-- Add is_active field to users table (if it doesn't exist)
ALTER TABLE users ADD COLUMN IF NOT EXISTS is_active TINYINT(1) DEFAULT 1;

-- Add is_banned field to users table
ALTER TABLE users ADD COLUMN IF NOT EXISTS is_banned TINYINT(1) DEFAULT 0;

-- Add suspended_at field to track when user was suspended
ALTER TABLE users ADD COLUMN IF NOT EXISTS suspended_at TIMESTAMP NULL DEFAULT NULL;

-- Add banned_at field to track when user was banned
ALTER TABLE users ADD COLUMN IF NOT EXISTS banned_at TIMESTAMP NULL DEFAULT NULL;

-- Add ban_reason field to store the reason for banning
ALTER TABLE users ADD COLUMN IF NOT EXISTS ban_reason TEXT NULL;

-- Add indexes for better performance
ALTER TABLE users ADD INDEX IF NOT EXISTS idx_user_status (is_active, is_banned);
ALTER TABLE users ADD INDEX IF NOT EXISTS idx_banned_users (is_banned, banned_at);

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Show migration results
SELECT 'User ban feature migration completed successfully!' as status;
SELECT COUNT(*) as 'Total users in database' FROM users; 