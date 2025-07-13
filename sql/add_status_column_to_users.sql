-- Add status column to users table
-- This script adds a status column to track user account status (active/inactive/banned)

ALTER TABLE users ADD COLUMN status VARCHAR(20) DEFAULT 'active' AFTER email_verified;

-- Update existing users to have 'active' status
UPDATE users SET status = 'active' WHERE status IS NULL;

-- Add index for better performance on status queries
CREATE INDEX idx_users_status ON users(status);

-- Optional: Add a comment to document the column
ALTER TABLE users MODIFY COLUMN status VARCHAR(20) DEFAULT 'active' COMMENT 'User account status: active, inactive, banned'; 