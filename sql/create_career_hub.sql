-- Career Hub Database Schema
-- This script creates the necessary tables for salary guides and career advice features

-- Disable foreign key checks
SET FOREIGN_KEY_CHECKS = 0;

-- Create salary_data table for salary guides
CREATE TABLE IF NOT EXISTS `salary_data` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `sector` VARCHAR(100) NOT NULL,
  `job_title` VARCHAR(255) NOT NULL,
  `location` VARCHAR(100) NOT NULL,
  `salary_low` DECIMAL(10,2) NOT NULL,
  `salary_average` DECIMAL(10,2) NOT NULL,
  `salary_high` DECIMAL(10,2) NOT NULL,
  `currency` VARCHAR(3) DEFAULT 'GBP',
  `experience_level` ENUM('entry', 'mid', 'senior', 'executive') DEFAULT 'mid',
  `data_source` VARCHAR(255),
  `last_updated` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_active` BOOLEAN DEFAULT TRUE,
  
  -- Indexes for performance
  INDEX `idx_sector_location` (`sector`, `location`),
  INDEX `idx_job_title` (`job_title`),
  INDEX `idx_location` (`location`),
  INDEX `idx_experience_level` (`experience_level`),
  INDEX `idx_is_active` (`is_active`),
  
  -- Fulltext search
  FULLTEXT `idx_search` (`sector`, `job_title`, `location`),
  
  -- Unique constraint to prevent duplicates
  UNIQUE KEY `unique_salary_entry` (`sector`, `job_title`, `location`, `experience_level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create career_advice_articles table
CREATE TABLE IF NOT EXISTS `career_advice_articles` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) UNIQUE NOT NULL,
  `excerpt` TEXT,
  `content` LONGTEXT NOT NULL,
  `featured_image` VARCHAR(255),
  `author_id` INT,
  `status` ENUM('draft', 'published', 'archived') DEFAULT 'draft',
  `published_at` TIMESTAMP NULL,
  `meta_title` VARCHAR(255),
  `meta_description` TEXT,
  `tags` VARCHAR(500),
  `views_count` INT DEFAULT 0,
  `is_featured` BOOLEAN DEFAULT FALSE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  -- Indexes for performance
  INDEX `idx_slug` (`slug`),
  INDEX `idx_status` (`status`),
  INDEX `idx_published_at` (`published_at`),
  INDEX `idx_is_featured` (`is_featured`),
  INDEX `idx_views_count` (`views_count`),
  
  -- Fulltext search
  FULLTEXT `idx_search` (`title`, `excerpt`, `content`),
  
  -- Foreign key constraints
  FOREIGN KEY (`author_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create article_categories table for better organization
CREATE TABLE IF NOT EXISTS `article_categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(100) UNIQUE NOT NULL,
  `description` TEXT,
  `parent_id` INT NULL,
  `sort_order` INT DEFAULT 0,
  `is_active` BOOLEAN DEFAULT TRUE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  -- Indexes for performance
  INDEX `idx_slug` (`slug`),
  INDEX `idx_parent_id` (`parent_id`),
  INDEX `idx_sort_order` (`sort_order`),
  INDEX `idx_is_active` (`is_active`),
  
  -- Self-referencing foreign key for parent categories
  FOREIGN KEY (`parent_id`) REFERENCES `article_categories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create article_category_relations table for many-to-many relationship
CREATE TABLE IF NOT EXISTS `article_category_relations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `article_id` INT NOT NULL,
  `category_id` INT NOT NULL,
  
  -- Indexes for performance
  INDEX `idx_article_id` (`article_id`),
  INDEX `idx_category_id` (`category_id`),
  
  -- Unique constraint to prevent duplicates
  UNIQUE KEY `unique_article_category` (`article_id`, `category_id`),
  
  -- Foreign key constraints
  FOREIGN KEY (`article_id`) REFERENCES `career_advice_articles`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`category_id`) REFERENCES `article_categories`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create article_tags table for flexible tagging
CREATE TABLE IF NOT EXISTS `article_tags` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) NOT NULL,
  `slug` VARCHAR(50) UNIQUE NOT NULL,
  `description` TEXT,
  `usage_count` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  
  -- Indexes for performance
  INDEX `idx_slug` (`slug`),
  INDEX `idx_usage_count` (`usage_count`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create article_tag_relations table for many-to-many relationship
CREATE TABLE IF NOT EXISTS `article_tag_relations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `article_id` INT NOT NULL,
  `tag_id` INT NOT NULL,
  
  -- Indexes for performance
  INDEX `idx_article_id` (`article_id`),
  INDEX `idx_tag_id` (`tag_id`),
  
  -- Unique constraint to prevent duplicates
  UNIQUE KEY `unique_article_tag` (`article_id`, `tag_id`),
  
  -- Foreign key constraints
  FOREIGN KEY (`article_id`) REFERENCES `career_advice_articles`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`tag_id`) REFERENCES `article_tags`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Insert default categories
INSERT IGNORE INTO `article_categories` (`name`, `slug`, `description`, `sort_order`) VALUES
('Career Advice', 'career-advice', 'General career guidance and tips', 1),
('Interview Tips', 'interview-tips', 'Interview preparation and techniques', 2),
('Resume Writing', 'resume-writing', 'CV and resume writing advice', 3),
('Job Search', 'job-search', 'Job hunting strategies and tips', 4),
('Workplace Skills', 'workplace-skills', 'Professional development and skills', 5),
('Industry Insights', 'industry-insights', 'Industry trends and analysis', 6);

-- Insert sample salary data for UK locations
INSERT IGNORE INTO `salary_data` (`sector`, `job_title`, `location`, `salary_low`, `salary_average`, `salary_high`, `experience_level`) VALUES
-- Technology Sector
('Technology', 'Software Developer', 'London', 35000, 55000, 85000, 'mid'),
('Technology', 'Software Developer', 'Manchester', 30000, 45000, 65000, 'mid'),
('Technology', 'Software Developer', 'Birmingham', 28000, 42000, 60000, 'mid'),
('Technology', 'Data Scientist', 'London', 45000, 65000, 95000, 'mid'),
('Technology', 'Data Scientist', 'Manchester', 40000, 55000, 75000, 'mid'),
('Technology', 'Product Manager', 'London', 50000, 75000, 110000, 'mid'),
('Technology', 'Product Manager', 'Manchester', 45000, 65000, 90000, 'mid'),

-- Finance Sector
('Finance', 'Accountant', 'London', 30000, 45000, 65000, 'mid'),
('Finance', 'Accountant', 'Manchester', 25000, 38000, 55000, 'mid'),
('Finance', 'Financial Analyst', 'London', 35000, 55000, 80000, 'mid'),
('Finance', 'Financial Analyst', 'Manchester', 30000, 45000, 65000, 'mid'),
('Finance', 'Investment Banker', 'London', 60000, 90000, 150000, 'mid'),

-- Healthcare Sector
('Healthcare', 'Nurse', 'London', 25000, 35000, 50000, 'mid'),
('Healthcare', 'Nurse', 'Manchester', 22000, 30000, 45000, 'mid'),
('Healthcare', 'Doctor', 'London', 45000, 75000, 120000, 'mid'),
('Healthcare', 'Doctor', 'Manchester', 40000, 65000, 100000, 'mid'),

-- Marketing Sector
('Marketing', 'Marketing Manager', 'London', 35000, 55000, 80000, 'mid'),
('Marketing', 'Marketing Manager', 'Manchester', 30000, 45000, 65000, 'mid'),
('Marketing', 'Digital Marketing Specialist', 'London', 28000, 42000, 60000, 'mid'),
('Marketing', 'Digital Marketing Specialist', 'Manchester', 25000, 35000, 50000, 'mid'),

-- Education Sector
('Education', 'Teacher', 'London', 25000, 35000, 50000, 'mid'),
('Education', 'Teacher', 'Manchester', 22000, 30000, 45000, 'mid'),
('Education', 'Lecturer', 'London', 35000, 50000, 75000, 'mid'),
('Education', 'Lecturer', 'Manchester', 30000, 42000, 65000, 'mid'),

-- Legal Sector
('Legal', 'Solicitor', 'London', 35000, 60000, 100000, 'mid'),
('Legal', 'Solicitor', 'Manchester', 30000, 50000, 80000, 'mid'),
('Legal', 'Paralegal', 'London', 22000, 32000, 45000, 'mid'),
('Legal', 'Paralegal', 'Manchester', 20000, 28000, 40000, 'mid'),

-- Entry Level Positions
('Technology', 'Junior Developer', 'London', 22000, 32000, 45000, 'entry'),
('Technology', 'Junior Developer', 'Manchester', 20000, 28000, 38000, 'entry'),
('Finance', 'Junior Accountant', 'London', 20000, 28000, 38000, 'entry'),
('Finance', 'Junior Accountant', 'Manchester', 18000, 25000, 35000, 'entry'),

-- Senior Level Positions
('Technology', 'Senior Developer', 'London', 60000, 85000, 120000, 'senior'),
('Technology', 'Senior Developer', 'Manchester', 50000, 70000, 95000, 'senior'),
('Finance', 'Senior Accountant', 'London', 45000, 65000, 90000, 'senior'),
('Finance', 'Senior Accountant', 'Manchester', 38000, 55000, 75000, 'senior'); 