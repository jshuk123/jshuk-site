# JShuk Carousel System

## Overview
The JShuk carousel system provides a dynamic, admin-managed carousel for the homepage that displays promotional content and business ads.

## Features
- ✅ **Admin Management**: Full CRUD operations for carousel ads
- ✅ **Dynamic Content**: Pulls from database, no hardcoded content
- ✅ **Responsive Design**: Mobile-friendly with SwiperJS
- ✅ **Auto-Generation**: Can auto-populate from Premium Plus businesses
- ✅ **Image Management**: Secure file uploads with validation
- ✅ **Position Control**: Reorder ads by position
- ✅ **Expiration Dates**: Set ads to expire automatically
- ✅ **Business Integration**: Link ads to specific businesses

## Files Created/Modified

### Core Files
- `admin/carousel_manager.php` - Admin panel for managing carousel ads
- `sections/carousel.php` - Frontend carousel component
- `api/carousel_ads.php` - API endpoint for carousel data
- `scripts/generate_carousel_ads.php` - Auto-generation script
- `uploads/carousel/` - Directory for carousel images

### Modified Files
- `index.php` - Updated to include dynamic carousel instead of static ads

## Database Schema

```sql
CREATE TABLE carousel_ads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    subtitle VARCHAR(255),
    image_path VARCHAR(255) NOT NULL,
    cta_text VARCHAR(50),
    cta_url VARCHAR(255),
    active BOOLEAN DEFAULT TRUE,
    is_auto_generated BOOLEAN DEFAULT FALSE,
    business_id INT,
    position INT DEFAULT 1,
    expires_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_active_position (active, position),
    INDEX idx_business_id (business_id),
    INDEX idx_expires_at (expires_at)
);
```

## Usage

### For Admins

1. **Access Admin Panel**
   ```
   /admin/carousel_manager.php
   ```

2. **Add New Ad**
   - Fill in title, subtitle, CTA details
   - Upload background image (1920x600px recommended)
   - Set position and active status
   - Optionally link to a business

3. **Manage Existing Ads**
   - Toggle active/inactive status
   - Delete ads (removes image file too)
   - View all ads in table format

### For Developers

1. **Include Carousel in Pages**
   ```php
   <?php include 'sections/carousel.php'; ?>
   ```

2. **Fetch Carousel Data via API**
   ```javascript
   fetch('/api/carousel_ads.php')
     .then(response => response.json())
     .then(data => console.log(data));
   ```

3. **Auto-Generate Ads**
   ```bash
   php scripts/generate_carousel_ads.php
   ```

## Configuration

### Image Requirements
- **Format**: JPG, PNG, GIF, WebP
- **Size**: 1920x600px recommended
- **Max Size**: 5MB
- **Storage**: `uploads/carousel/` directory

### Carousel Settings
- **Auto-rotation**: 6 seconds
- **Pause on hover**: Yes
- **Effect**: Fade transition
- **Navigation**: Arrows + pagination dots
- **Keyboard**: Arrow keys supported

### Auto-Generation Settings
- **Minimum ads**: 3
- **Max auto-ads**: 5
- **Source**: Premium Plus businesses only
- **Frequency**: Run via cron job

## Security Features

- ✅ **File Validation**: Only allowed image types
- ✅ **Path Sanitization**: Secure file paths
- ✅ **Admin Authentication**: Session-based admin check
- ✅ **CSRF Protection**: Built into admin forms
- ✅ **SQL Injection Prevention**: Prepared statements
- ✅ **XSS Prevention**: HTML escaping on output

## Performance Optimizations

- ✅ **Database Indexing**: Optimized queries
- ✅ **Image Optimization**: Automatic resizing (future)
- ✅ **Caching**: Database query caching
- ✅ **Lazy Loading**: SwiperJS built-in optimizations
- ✅ **CDN**: SwiperJS loaded from CDN

## Future Enhancements

### Planned Features
- [ ] **Stripe Integration**: Paid carousel slots
- [ ] **A/B Testing**: Multiple carousel versions
- [ ] **Analytics**: Click tracking and performance metrics
- [ ] **Scheduling**: Time-based ad display
- [ ] **Templates**: Pre-designed ad templates
- [ ] **Bulk Operations**: Import/export carousel ads

### Technical Improvements
- [ ] **Image Processing**: Automatic resizing and optimization
- [ ] **Cache Invalidation**: Smart cache management
- [ ] **API Rate Limiting**: Protect API endpoints
- [ ] **WebP Support**: Modern image format optimization

## Troubleshooting

### Common Issues

1. **Carousel Not Showing**
   - Check if `carousel_ads` table exists
   - Verify there are active ads in database
   - Check file permissions on `uploads/carousel/`

2. **Images Not Loading**
   - Verify image files exist in `uploads/carousel/`
   - Check file permissions (755 for directory, 644 for files)
   - Ensure image paths are correct in database

3. **Admin Panel Access Denied**
   - Verify user is logged in as admin
   - Check `$_SESSION['is_admin']` is set to true
   - Ensure admin session is active

4. **Upload Errors**
   - Check `upload_max_filesize` in PHP config
   - Verify `uploads/carousel/` directory is writable
   - Check file type validation

### Debug Mode
Enable debug mode in `config/config.php`:
```php
define('APP_DEBUG', true);
```

## Support

For technical support or feature requests, please contact the development team or create an issue in the project repository.

---

**Last Updated**: June 2025
**Version**: 1.0.0 