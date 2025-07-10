-- Update subscription_plans table
ALTER TABLE subscription_plans
MODIFY COLUMN trial_period_days int(11) NOT NULL DEFAULT 0;

-- Update user_subscriptions table
ALTER TABLE user_subscriptions
ADD COLUMN trial_start datetime NULL AFTER status,
ADD COLUMN trial_end datetime NULL AFTER trial_start,
ADD COLUMN trial_period_days int(11) NULL AFTER trial_end;

-- Update existing Premium plan to have 90 days trial
UPDATE subscription_plans 
SET trial_period_days = 90 
WHERE name = 'Premium';

-- Update existing Basic plan to have no trial
UPDATE subscription_plans 
SET trial_period_days = 0 
WHERE name = 'Basic'; 