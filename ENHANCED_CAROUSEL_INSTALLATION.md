# 🎠 Enhanced Carousel System Installation Guide

## Overview

The JShuk Enhanced Carousel System is a comprehensive carousel management solution with advanced features including:

- **Location-based targeting** (London, Manchester, Gateshead, etc.)
- **Multi-zone support** (homepage, businesses, post-business)
- **Analytics tracking** (impressions, clicks, CTR)
- **Scheduling system** (start/end dates)
- **Sponsored content** management
- **Priority-based ordering**
- **Admin control panel**
- **API endpoints** for data access

## 📋 Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- Existing JShuk installation
- Admin access to database

## 🚀 Installation Steps

### Step 1: Database Migration

1. **Backup your database** before proceeding
2. **Run the migration script**:
   ```bash
   php scripts/migrate_to_enhanced_carousel.php
   ```
3. **Follow the prompts** to migrate existing data
4. **Verify the migration** was successful

### Step 2: File Structure

Ensure these files are in place:

```
Jshuk/
├── admin/
│   └── enhanced_carousel_manager.php    # Admin control panel
├── api/
│   └── carousel-analytics.php           # Analytics API
├── includes/
│   └── enhanced_carousel_functions.php  # Backend functions
├── sections/
│   └── enhanced_carousel.php            # Frontend display
├── sql/
│   └── enhanced_carousel_schema.sql     # Database schema
├── scripts/
│   └── migrate_to_enhanced_carousel.php # Migration script
└── test_enhanced_carousel_system.php    # Test page
```

### Step 3: Update Homepage

The homepage (`index.php`) has been updated to use the enhanced carousel system. The change replaces:

```php
// Old
<?php include 'sections/carousel.php'; ?>

// New
<?php 
$zone = 'homepage';
$location = null; // Auto-detect user location
include 'sections/enhanced_carousel.php'; 
?>
```

### Step 4: Test the System

1. **Visit the test page**: `/test_enhanced_carousel_system.php`
2. **Check all features** are working
3. **Verify analytics** are being tracked
4. **Test admin panel**: `/admin/enhanced_carousel_manager.php`

## 🔧 Configuration

### Location Settings

Default locations are configured in the database:

- **London**: 51.3-51.7°N, -0.5-0.3°E
- **Manchester**: 53.4-53.5°N, -2.3-2.1°E  
- **Gateshead**: 54.9-55.0°N, -1.7-1.5°E
- **All**: Global targeting

### Zone Configuration

Available zones:
- `homepage` - Main homepage carousel
- `businesses` - Businesses page carousel
- `post-business` - Post-business page carousel

### Analytics Settings

Analytics are automatically tracked for:
- **Impressions**: When slides are viewed
- **Clicks**: When CTA buttons are clicked
- **Hover**: When users hover over slides (optional)

## 📊 Admin Panel Features

### Dashboard
- **Total slides** count
- **Active slides** count
- **Sponsored content** count
- **Zone distribution**

### Slide Management
- **Add/Edit/Delete** slides
- **Image upload** with validation
- **Location targeting** selection
- **Zone assignment**
- **Scheduling** (start/end dates)
- **Priority** setting (0-100)
- **Sponsored** content flag

### Analytics
- **Performance metrics** (impressions, clicks, CTR)
- **Location-based** analytics
- **Time-based** reporting
- **Export capabilities**

### Filters
- **Location-based** filtering
- **Zone-based** filtering
- **Status** filtering (active/inactive)
- **Sponsored** content filtering

## 🔌 API Endpoints

### Analytics Tracking
```http
POST /api/carousel-analytics.php
Content-Type: application/json

{
    "slide_id": 1,
    "event_type": "click"
}
```

### Performance Data
```http
GET /api/carousel-analytics.php?action=performance&days=30
```

### Slide Data
```http
GET /api/carousel-analytics.php?action=slides&zone=homepage&limit=10
```

### Statistics
```http
GET /api/carousel-analytics.php?action=stats
```

### Expiring Slides
```http
GET /api/carousel-analytics.php?action=expiring&days=7
```

## 🎨 Customization

### Styling
The enhanced carousel uses CSS classes that can be customized:

```css
.carousel-section          /* Main container */
.carousel-slide           /* Individual slide */
.carousel-content         /* Text content */
.carousel-cta            /* Call-to-action button */
.sponsored-badge         /* Sponsored content indicator */
.carousel-nav-prev       /* Previous button */
.carousel-nav-next       /* Next button */
.carousel-pagination     /* Pagination dots */
```

### JavaScript Configuration
Swiper configuration can be modified in `sections/enhanced_carousel.php`:

```javascript
const swiper = new Swiper('.enhanced-homepage-carousel', {
    loop: true,
    autoplay: {
        delay: 6000,
        disableOnInteraction: false,
        pauseOnMouseEnter: true,
    },
    effect: 'fade',
    speed: 1000,
    // ... more options
});
```

## 🔍 Troubleshooting

### Common Issues

1. **Carousel not loading**
   - Check if Swiper library is loaded
   - Verify database connection
   - Check browser console for errors

2. **Analytics not tracking**
   - Verify API endpoint is accessible
   - Check database permissions
   - Ensure session is started

3. **Images not displaying**
   - Check file permissions on uploads directory
   - Verify image paths in database
   - Check image file formats (jpg, png, gif, webp)

4. **Location targeting not working**
   - Verify location mappings in database
   - Check user location detection
   - Test with different IP addresses

### Debug Mode

Enable debug mode in `config/config.php`:
```php
define('APP_DEBUG', true);
```

### Log Files

Check error logs for detailed information:
- PHP error log
- Web server error log
- Application-specific logs

## 📈 Performance Optimization

### Database Optimization
- **Indexes** are automatically created for performance
- **Analytics cleanup** runs automatically (90-day retention)
- **Views** provide optimized queries

### Caching
- **Session-based** location caching
- **Database query** optimization
- **Image optimization** recommendations

### Monitoring
- **Analytics tracking** for performance monitoring
- **Error logging** for issue detection
- **Performance metrics** in admin panel

## 🔒 Security Considerations

### File Upload Security
- **File type validation** (jpg, png, gif, webp only)
- **File size limits** (5MB max)
- **Secure file naming** (timestamp + unique ID)

### API Security
- **Input validation** on all endpoints
- **SQL injection** prevention
- **XSS protection** in output

### Admin Access
- **Session-based** authentication
- **Admin role** verification
- **CSRF protection** on forms

## 📚 Advanced Features

### Geolocation Integration
To enhance location detection, integrate with a geolocation service:

```php
// In enhanced_carousel_functions.php
function detectUserLocation() {
    $ip = $_SERVER['REMOTE_ADDR'];
    
    // Example: ipapi.co integration
    $response = file_get_contents("http://ip-api.com/json/{$ip}");
    $data = json_decode($response, true);
    
    if ($data && $data['status'] === 'success') {
        // Map coordinates to your location names
        return mapCoordinatesToLocation($data['lat'], $data['lon']);
    }
    
    return 'all'; // Default fallback
}
```

### Custom Analytics
Extend analytics tracking with custom events:

```javascript
// Track custom events
fetch('/api/carousel-analytics.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({
        slide_id: slideId,
        event_type: 'custom_event',
        custom_data: 'additional_info'
    })
});
```

### Multi-language Support
Add language-specific content:

```php
// In carousel_slides table
ALTER TABLE carousel_slides ADD COLUMN language VARCHAR(10) DEFAULT 'en';

// In enhanced_carousel_functions.php
function getCarouselSlides($pdo, $zone = 'homepage', $limit = 10, $location = null, $language = 'en') {
    // Add language filter to query
    $query = $pdo->prepare("
        SELECT * FROM carousel_slides
        WHERE active = 1 
          AND (location = :loc OR location = 'all')
          AND zone = :zone
          AND (language = :lang OR language = 'all')
          AND (start_date IS NULL OR start_date <= :today)
          AND (end_date IS NULL OR end_date >= :today)
        ORDER BY priority DESC, sponsored DESC, created_at DESC
        LIMIT :limit
    ");
}
```

## 🆘 Support

### Documentation
- **This installation guide**
- **Code comments** in source files
- **Test page** for verification

### Testing
- **Comprehensive test page**: `/test_enhanced_carousel_system.php`
- **API endpoint testing** included
- **Feature verification** tools

### Updates
- **Database migrations** for future updates
- **Backward compatibility** maintained
- **Version control** recommended

## 🎉 Success Checklist

- [ ] Database migration completed successfully
- [ ] All files in correct locations
- [ ] Homepage updated to use enhanced carousel
- [ ] Admin panel accessible and functional
- [ ] Analytics tracking working
- [ ] Location targeting functional
- [ ] Test page shows all features working
- [ ] API endpoints responding correctly
- [ ] No errors in browser console
- [ ] Performance acceptable

## 📞 Need Help?

If you encounter issues:

1. **Check the test page** first
2. **Review error logs** for details
3. **Verify database** structure
4. **Test API endpoints** manually
5. **Check browser console** for JavaScript errors

The enhanced carousel system is designed to be robust and self-documenting. Most issues can be resolved by following this guide and using the built-in testing tools. 