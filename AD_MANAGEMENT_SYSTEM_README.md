# 🧩 Complete Ad Management System for JShuk

## 📋 Overview

This is a comprehensive **Ad Management System** for JShuk that allows administrators to manage advertisements across different zones of the website. The system includes backend structure, frontend rendering, admin dashboard, and analytics tracking.

## 🚀 Features

### ✅ Core Features
- **Multi-zone Ad Placement**: Header, Sidebar, Footer, Carousel, Inline
- **Advanced Targeting**: Category-based, Location-based, Business-specific
- **Priority System**: Control ad display order (1-10 scale)
- **Date Scheduling**: Start and end dates for campaigns
- **Status Management**: Active, Paused, Expired states
- **Click Tracking**: Analytics for views and clicks
- **Admin Logging**: Complete audit trail of admin actions

### 🎯 Targeting Options
- **Category Targeting**: Show ads only on specific category pages
- **Location Targeting**: Target users by geographic location
- **Business Association**: Link ads to specific businesses
- **Global Ads**: Show across all pages/locations

### 📊 Analytics & Reporting
- **View Tracking**: Automatic impression counting
- **Click Tracking**: Click-through rate calculation
- **Performance Metrics**: CTR, views, clicks per ad
- **Date-based Reporting**: Filter by time periods

## 📁 File Structure

```
/admin/
├── ads.php              # Main dashboard - manage all ads
├── add_ad.php           # Add new advertisement
└── edit_ad.php          # Edit existing advertisement

/partials/ads/
├── header_ad.php        # Header zone ad renderer
├── sidebar_ads.php      # Sidebar zone ad renderer
├── footer_ad.php        # Footer zone ad renderer
├── home_carousel_ads.php # Carousel zone ad renderer
└── inline_offers.php    # Inline zone ad renderer

/includes/
└── ad_renderer.php      # Core ad rendering logic

/css/
└── admin_ads.css        # Admin interface styling

/js/
└── ad_preview.js        # Live preview functionality

/track_ad_click.php      # Click tracking and redirect

/sql/
└── enhanced_ads_system.sql # Database structure
```

## 🗄️ Database Structure

### Main Tables

#### `ads` Table
```sql
CREATE TABLE `ads` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `image_url` TEXT NOT NULL,
  `link_url` TEXT NOT NULL,
  `zone` ENUM('header', 'sidebar', 'footer', 'carousel', 'inline') NOT NULL,
  `category_id` INT NULL,
  `location` VARCHAR(255) NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `status` ENUM('active', 'paused', 'expired') DEFAULT 'paused',
  `priority` INT DEFAULT 5,
  `business_id` INT NULL,
  `cta_text` VARCHAR(100) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

#### `ad_stats` Table
```sql
CREATE TABLE `ad_stats` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `ad_id` INT NOT NULL,
  `date` DATE NOT NULL,
  `views` INT DEFAULT 0,
  `clicks` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

#### `admin_logs` Table
```sql
CREATE TABLE `admin_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `admin_id` INT NOT NULL,
  `action` VARCHAR(100) NOT NULL,
  `table_name` VARCHAR(50) NOT NULL,
  `record_id` INT NULL,
  `details` TEXT NULL,
  `ip_address` VARCHAR(45) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

## 🔧 Installation & Setup

### 1. Database Setup
```bash
# Run the SQL script to create/update tables
mysql -u your_username -p your_database < sql/enhanced_ads_system.sql
```

### 2. File Permissions
```bash
# Ensure upload directory is writable
chmod 755 uploads/ads/
```

### 3. Configuration
- Ensure your database connection is properly configured in `config/config.php`
- Verify that the `BASE_PATH` constant is set correctly

## 🎮 Usage Guide

### For Administrators

#### Accessing the Ad Management Dashboard
1. Log in as an admin user
2. Navigate to `/admin/ads.php`
3. You'll see the comprehensive dashboard with all ads

#### Adding a New Advertisement
1. Click "Add New Ad" button
2. Fill in the required fields:
   - **Title**: Descriptive name for the ad
   - **Image**: Upload ad image (max 5MB)
   - **Link URL**: Target destination
   - **Zone**: Where to display the ad
   - **Targeting**: Category, location, business (optional)
   - **Scheduling**: Start and end dates
   - **Priority**: Display order (1-10)
   - **CTA Text**: Call-to-action button text (optional)

#### Managing Existing Ads
- **Edit**: Click the edit icon to modify an ad
- **Toggle Status**: Activate/pause ads with one click
- **Delete**: Remove ads permanently
- **Filter**: Use filters to find specific ads

### For Developers

#### Adding Ads to Pages

**Header Ads:**
```php
<?php include($_SERVER['DOCUMENT_ROOT'].'/partials/ads/header_ad.php'); ?>
```

**Sidebar Ads (with category targeting):**
```php
<?php include($_SERVER['DOCUMENT_ROOT'].'/partials/ads/sidebar_ads.php'); ?>
```

**Footer Ads:**
```php
<?php include($_SERVER['DOCUMENT_ROOT'].'/partials/ads/footer_ad.php'); ?>
```

**Carousel Ads:**
```php
<?php include($_SERVER['DOCUMENT_ROOT'].'/partials/ads/home_carousel_ads.php'); ?>
```

**Inline Ads:**
```php
<?php include($_SERVER['DOCUMENT_ROOT'].'/partials/ads/inline_offers.php'); ?>
```

#### Using the Ad Renderer Directly
```php
require_once($_SERVER['DOCUMENT_ROOT'].'/includes/ad_renderer.php');

// Render a specific ad
echo renderAd('header', $categoryId, $location);

// Render multiple ads for carousel
$carouselAds = renderMultipleAds('carousel', 5, $categoryId, $location);
```

## 🎨 Styling & Customization

### CSS Classes Available
- `.ad-container` - Main ad wrapper
- `.ad-header` - Header zone styling
- `.ad-sidebar` - Sidebar zone styling
- `.ad-footer` - Footer zone styling
- `.ad-carousel` - Carousel zone styling
- `.ad-inline` - Inline zone styling
- `.ad-label` - "Advertisement" label
- `.ad-cta` - Call-to-action button

### Custom Styling
Edit `css/admin_ads.css` to customize the admin interface appearance.

## 📈 Analytics & Reporting

### Viewing Ad Performance
1. Go to the Ad Management Dashboard
2. Each ad shows basic stats in the table
3. Click on individual ads to see detailed performance

### Key Metrics
- **Views**: Number of times ad was displayed
- **Clicks**: Number of times ad was clicked
- **CTR**: Click-through rate (clicks/views)
- **Status**: Current ad status
- **Performance**: Priority-based display order

## 🔒 Security Features

### Admin Access Control
- Only users with `role = 'admin'` can access ad management
- All actions are logged with IP addresses
- Session-based authentication required

### Input Validation
- File upload validation (type, size)
- URL validation
- Date range validation
- SQL injection prevention with prepared statements

### Audit Trail
- All admin actions are logged
- Includes user ID, action type, and details
- IP address tracking for security

## 🚀 Future Enhancements

### Planned Features
- **Self-serve Ad Manager**: Allow Premium Plus users to create ads
- **Advanced Analytics**: Detailed reporting dashboard
- **A/B Testing**: Test different ad variations
- **Auto-expiry Alerts**: Email notifications for expiring ads
- **Mobile Optimization**: Responsive ad previews
- **Tag System**: Categorize ads (e.g., "Shavuot Offers", "Back to School")

### Technical Improvements
- **Caching**: Implement ad caching for better performance
- **CDN Integration**: Serve ad images from CDN
- **API Endpoints**: RESTful API for ad management
- **Real-time Updates**: WebSocket integration for live stats

## 🐛 Troubleshooting

### Common Issues

**Ads not displaying:**
1. Check ad status is 'active'
2. Verify start/end dates are current
3. Ensure targeting criteria match current page
4. Check file permissions on uploads directory

**Image upload errors:**
1. Verify file size is under 5MB
2. Check file format (JPEG, PNG, GIF, WebP)
3. Ensure uploads/ads/ directory is writable

**Database errors:**
1. Check database connection in config.php
2. Verify all tables exist
3. Check for foreign key constraint issues

### Debug Mode
Enable debug mode in `config/config.php`:
```php
define('APP_DEBUG', true);
```

## 📞 Support

For technical support or questions about the Ad Management System:

1. Check the error logs in `/logs/php_errors.log`
2. Verify database connectivity
3. Test with a simple ad creation
4. Review the admin logs for recent actions

## 📄 License

This Ad Management System is part of the JShuk platform and follows the same licensing terms.

---

**Last Updated**: June 2025
**Version**: 1.0.0
**Compatibility**: PHP 7.4+, MySQL 5.7+ 