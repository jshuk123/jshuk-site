# 🎠 JShuk Carousel Setup Guide

This guide will help you set up and troubleshoot the homepage carousel system for JShuk.

## 🚀 Quick Setup

### Option 1: Automated Setup (Recommended)
1. **Run the setup script**: Visit `scripts/setup_carousel.php` in your browser
   - This will automatically create the database table, generate sample images, and add test data
   - Follow the on-screen instructions

### Option 2: Manual Setup
If the automated setup doesn't work, follow these manual steps:

## 📋 Manual Setup Steps

### Step 1: Create Database Table
Run this SQL query in your database:

```sql
CREATE TABLE IF NOT EXISTS `carousel_ads` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(100) NOT NULL,
    `subtitle` VARCHAR(255),
    `image_path` VARCHAR(255) NOT NULL,
    `cta_text` VARCHAR(50),
    `cta_url` VARCHAR(255),
    `active` BOOLEAN DEFAULT TRUE,
    `is_auto_generated` BOOLEAN DEFAULT FALSE,
    `business_id` INT,
    `position` INT DEFAULT 1,
    `expires_at` DATETIME,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX `idx_active_position` (`active`, `position`),
    INDEX `idx_business_id` (`business_id`),
    INDEX `idx_expires_at` (`expires_at`),
    INDEX `idx_created_at` (`created_at`),
    
    FOREIGN KEY (`business_id`) REFERENCES `businesses`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Step 2: Create Uploads Directory
```bash
mkdir -p uploads/carousel/
chmod 755 uploads/carousel/
```

### Step 3: Generate Sample Images
Run the image generation script:
```bash
php scripts/create_carousel_images.php
```

### Step 4: Add Sample Data
Insert sample carousel ads:

```sql
INSERT INTO `carousel_ads` (`title`, `subtitle`, `image_path`, `cta_text`, `cta_url`, `active`, `position`, `created_at`) VALUES
('Welcome to JShuk', 'Your Jewish Community Hub - Discover Local Businesses', 'uploads/carousel/sample_ad1.jpg', 'Explore Now', 'businesses.php', 1, 1, NOW()),
('Kosher Restaurants', 'Find the best kosher dining in your area', 'uploads/carousel/sample_ad2.jpg', 'Find Restaurants', 'businesses.php?category=restaurants', 1, 2, NOW()),
('Community Events', 'Stay connected with your local Jewish community', 'uploads/carousel/sample_ad3.jpg', 'View Events', 'events.php', 1, 3, NOW());
```

## 🔧 Testing & Debugging

### Test the Carousel
1. **Visit the test page**: Go to `carousel_test.html` in your browser
2. **Check the homepage**: Visit `index.php` to see the carousel in action
3. **Open browser console**: Press F12 and check for any JavaScript errors

### Debug Information
The carousel now includes comprehensive debugging:

- **PHP Debugging**: Check `logs/php_errors.log` for PHP errors
- **JavaScript Debugging**: Open browser console to see carousel initialization logs
- **Database Debugging**: Use `debug_carousel.php` to check database status

### Common Issues & Solutions

#### Issue 1: Carousel Not Appearing
**Symptoms**: No carousel visible on homepage
**Solutions**:
- Check if `carousel_ads` table exists
- Verify there are active ads in the database
- Check browser console for JavaScript errors
- Ensure Swiper library is loading

#### Issue 2: Images Not Loading
**Symptoms**: Carousel shows but images are broken
**Solutions**:
- Verify `uploads/carousel/` directory exists and is writable
- Check image file paths in database
- Ensure images are actually uploaded to the directory

#### Issue 3: Swiper Not Initializing
**Symptoms**: Carousel appears but doesn't rotate
**Solutions**:
- Check browser console for Swiper errors
- Verify Swiper CSS and JS are loading
- Check for JavaScript conflicts

#### Issue 4: Database Connection Issues
**Symptoms**: Fallback SVG shows instead of database ads
**Solutions**:
- Verify database connection in `config/config.php`
- Check if `$pdo` variable is available
- Ensure database credentials are correct

## 📁 File Structure

```
public_html/
├── sections/
│   └── carousel.php              # Main carousel component
├── scripts/
│   ├── setup_carousel.php        # Complete setup script
│   ├── create_carousel_images.php # Image generation script
│   └── generate_carousel_ads.php  # Auto-generate ads from businesses
├── admin/
│   └── carousel_manager.php      # Admin interface for managing ads
├── api/
│   └── carousel_ads.php          # API endpoint for carousel data
├── uploads/
│   └── carousel/                 # Carousel image storage
├── sql/
│   └── create_carousel_ads_table.sql # Database table creation
├── test_carousel.php             # Basic carousel test
├── debug_carousel.php            # Comprehensive debugging
└── carousel_test.html            # HTML test page
```

## 🎛️ Admin Management

### Access Carousel Manager
Visit `admin/carousel_manager.php` to:
- Add new carousel ads
- Upload images
- Manage ad positions
- Toggle ad visibility
- Delete ads

### Features
- **Image Upload**: Supports JPG, PNG, GIF, WebP
- **Position Management**: Drag and drop to reorder
- **Active/Inactive Toggle**: Enable/disable ads
- **Business Linking**: Link ads to specific businesses
- **Expiration Dates**: Set automatic expiration

## 🔄 Auto-Generation

The system can automatically generate carousel ads from Premium Plus businesses:

```bash
php scripts/generate_carousel_ads.php
```

This script will:
- Check for Premium Plus businesses without carousel ads
- Create carousel ads for them automatically
- Maintain a minimum number of active ads

## 📊 Monitoring

### Check Carousel Status
```sql
-- Count active ads
SELECT COUNT(*) FROM carousel_ads WHERE active = 1;

-- View all ads
SELECT title, active, position, created_at FROM carousel_ads ORDER BY position;

-- Check for expired ads
SELECT title, expires_at FROM carousel_ads WHERE expires_at < NOW() AND active = 1;
```

### Log Files
- `logs/php_errors.log` - PHP errors and carousel debugging
- Browser console - JavaScript debugging information

## 🚨 Troubleshooting Checklist

- [ ] Database connection working
- [ ] `carousel_ads` table exists
- [ ] Active ads in database
- [ ] `uploads/carousel/` directory exists and writable
- [ ] Images uploaded to directory
- [ ] Swiper library loading (check browser console)
- [ ] No JavaScript errors in console
- [ ] Carousel component included in homepage
- [ ] CSS styles loading correctly

## 📞 Support

If you're still having issues:

1. **Run the debug script**: `debug_carousel.php`
2. **Check the test page**: `carousel_test.html`
3. **Review error logs**: `logs/php_errors.log`
4. **Check browser console**: For JavaScript errors

## 🎉 Success Indicators

When everything is working correctly, you should see:
- ✅ Carousel rotating automatically on homepage
- ✅ Navigation arrows working
- ✅ Pagination dots showing
- ✅ Images loading properly
- ✅ Console logs showing successful initialization
- ✅ No error messages in logs

---

**Last Updated**: December 2024
**Version**: 1.0 