# 🏠 Retreats & Simcha Rentals System

A comprehensive directory system for Jewish retreats and simcha rentals, allowing hosts to list properties and guests to find accommodations for special occasions.

## 📋 Table of Contents

- [Features](#features)
- [Database Schema](#database-schema)
- [File Structure](#file-structure)
- [Setup Instructions](#setup-instructions)
- [Usage Guide](#usage-guide)
- [Admin Features](#admin-features)
- [API Endpoints](#api-endpoints)
- [Customization](#customization)
- [Troubleshooting](#troubleshooting)

## ✨ Features

### Core Features
- **Property Listings**: Hosts can list properties with detailed information
- **Advanced Filtering**: Search by location, category, price, amenities, and more
- **Image Galleries**: Multiple photo support with thumbnail navigation
- **Booking System**: Contact hosts and check availability
- **Reviews & Ratings**: Guest feedback system with moderation
- **Host Profiles**: Trusted host badges and verification system
- **Mobile Responsive**: Optimized for all device sizes

### Property Types
- 🏘 Chosson/Kallah Flat
- 🏠 Emchutanim Flat
- 🕍 Shabbos Getaway
- 🛌 Ladies Retreat / Quiet Stay
- 🌿 Yom Tov Home Rental
- 🎉 Simcha Nearby Flat
- 🧳 Host Family Option

### Amenities & Features
- Kosher kitchen (meat/dairy/parve/separate)
- Private entrance
- Shabbos equipment (plata, urn)
- Mikveh proximity
- Synagogue distance
- Accessibility features
- WiFi, parking, garden access
- Baby equipment

### Trust & Safety
- Host verification system
- Property review process
- Trusted host badges
- Community flagging system
- Admin moderation tools

## 🗄️ Database Schema

### Core Tables

#### `retreat_categories`
- Property type categories with emojis and icons
- Sort order and active status

#### `retreat_locations`
- Geographic locations and regions
- Support for UK and international locations

#### `retreats`
- Main property listings
- Pricing, capacity, amenities
- Location coordinates
- Status and verification flags

#### `retreat_amenities`
- Available amenities with categories
- Icon support for visual display

#### `retreat_amenity_relations`
- Many-to-many relationship between properties and amenities

#### `retreat_tags`
- Property feature tags with custom colors
- Trust and verification badges

#### `retreat_tag_relations`
- Many-to-many relationship between properties and tags

#### `retreat_availability`
- Calendar-based availability tracking
- Price overrides for specific dates

#### `retreat_bookings`
- Booking management system
- Guest and host information
- Status tracking

#### `retreat_reviews`
- Guest review system
- Rating categories (cleanliness, communication, etc.)
- Moderation controls

#### `retreat_views`
- Analytics tracking
- View count and user behavior

## 📁 File Structure

```
retreats.php                 # Main retreats listing page
retreat.php                  # Individual retreat detail page
add_retreat.php             # Host property submission form
admin/retreats.php          # Admin management interface
css/pages/retreats.css      # Main page styling
css/pages/retreat_detail.css # Detail page styling
css/pages/add_retreat.css   # Form page styling
css/admin_retreats.css      # Admin interface styling
sql/create_retreats_tables.sql # Database schema
```

## 🚀 Setup Instructions

### 1. Database Setup

Run the SQL migration to create all necessary tables:

```sql
-- Execute the complete schema
SOURCE sql/create_retreats_tables.sql;
```

### 2. File Permissions

Ensure upload directories are writable:

```bash
chmod 755 uploads/retreats/
chmod 755 uploads/retreats/images/
chmod 755 uploads/retreats/galleries/
```

### 3. Configuration

Update `config/config.php` to include retreat-specific settings:

```php
// Retreat system settings
define('RETREATS_UPLOAD_PATH', UPLOAD_PATH . 'retreats/');
define('MAX_RETREAT_IMAGES', 10);
define('RETREAT_IMAGE_MAX_SIZE', 5 * 1024 * 1024); // 5MB
```

### 4. Navigation Integration

Add retreats to your main navigation:

```php
// In includes/header_main.php
<a href="/retreats.php" class="nav-link">
    <i class="fas fa-home"></i>
    Retreats
</a>
```

## 📖 Usage Guide

### For Hosts

1. **Create Account**: Register or log in to your account
2. **List Property**: Visit `/add_retreat.php` to submit your property
3. **Add Details**: Fill in all required information and amenities
4. **Upload Photos**: Add high-quality images of your property
5. **Set Pricing**: Configure nightly, Shabbos, and Yom Tov rates
6. **Submit for Review**: Property will be reviewed by admin team
7. **Manage Bookings**: Respond to guest inquiries and manage calendar

### For Guests

1. **Browse Properties**: Use filters to find perfect accommodations
2. **View Details**: Check amenities, photos, and host information
3. **Contact Host**: Send inquiries through the platform
4. **Check Availability**: View calendar and pricing options
5. **Book Stay**: Coordinate directly with host for booking
6. **Leave Review**: Share your experience after your stay

### For Admins

1. **Review Submissions**: Approve or reject new property listings
2. **Moderate Content**: Manage reviews and flag inappropriate content
3. **Feature Properties**: Highlight exceptional properties
4. **Verify Hosts**: Award trusted host badges
5. **Analytics**: Track views, bookings, and user engagement

## 🔧 Admin Features

### Property Management
- **Approval System**: Review and approve new listings
- **Content Moderation**: Edit property details and remove inappropriate content
- **Feature Management**: Highlight properties on homepage
- **Bulk Operations**: Mass approve, reject, or delete listings

### Host Management
- **Verification**: Verify host identities and contact information
- **Trust Badges**: Award trusted host status based on reviews
- **Communication**: Direct messaging with hosts
- **Analytics**: Track host performance and guest satisfaction

### Analytics Dashboard
- **View Tracking**: Monitor property page views
- **Booking Analytics**: Track inquiry and booking rates
- **Revenue Reports**: Generate financial reports
- **User Behavior**: Analyze search patterns and preferences

## 🔌 API Endpoints

### Public Endpoints

```php
// Get retreats with filters
GET /api/retreats.php?category=chosson-kallah&location=golders-green&price_min=100&price_max=200

// Get individual retreat
GET /api/retreat.php?id=123

// Search retreats
GET /api/search_retreats.php?q=golders green kosher

// Get availability
GET /api/retreat_availability.php?id=123&start_date=2024-01-01&end_date=2024-01-07
```

### Admin Endpoints

```php
// Approve retreat
POST /admin/approve_retreat.php
{
    "retreat_id": 123,
    "action": "approve"
}

// Update retreat status
POST /admin/update_retreat_status.php
{
    "retreat_id": 123,
    "status": "active"
}

// Get analytics
GET /admin/retreat_analytics.php?period=30&type=views
```

## 🎨 Customization

### Styling Customization

Modify CSS variables in the stylesheets:

```css
:root {
    --primary-color: #1a3353;
    --secondary-color: #ffd700;
    --accent-color: #2C4E6D;
    --success-color: #28a745;
    --warning-color: #ffc107;
    --danger-color: #dc3545;
}
```

### Category Customization

Add new property types in the database:

```sql
INSERT INTO retreat_categories (name, slug, description, icon_class, emoji, sort_order) 
VALUES ('New Category', 'new-category', 'Description', 'fas fa-icon', '🏠', 8);
```

### Location Expansion

Add new locations:

```sql
INSERT INTO retreat_locations (name, slug, region, country, sort_order) 
VALUES ('New Location', 'new-location', 'Region', 'Country', 16);
```

### Amenity Management

Add custom amenities:

```sql
INSERT INTO retreat_amenities (name, icon_class, category, sort_order) 
VALUES ('Custom Amenity', 'fas fa-custom', 'comfort', 25);
```

## 🐛 Troubleshooting

### Common Issues

#### Images Not Uploading
- Check file permissions on upload directories
- Verify file size limits in PHP configuration
- Ensure proper image formats (JPEG, PNG, WebP)

#### Database Connection Errors
- Verify database credentials in config
- Check table existence with `SHOW TABLES;`
- Run database migration if tables missing

#### Filter Not Working
- Check JavaScript console for errors
- Verify filter parameters in URL
- Test database queries directly

#### Admin Access Issues
- Confirm user has admin privileges
- Check session management
- Verify admin login redirects

### Performance Optimization

#### Database Indexing
```sql
-- Add indexes for better performance
CREATE INDEX idx_retreats_status_location ON retreats(status, location_id);
CREATE INDEX idx_retreats_price_capacity ON retreats(price_per_night, guest_capacity);
CREATE INDEX idx_retreats_rating_views ON retreats(rating_average, views_count);
```

#### Image Optimization
- Implement image compression
- Use WebP format for modern browsers
- Implement lazy loading for galleries

#### Caching
- Enable page caching for listing pages
- Cache category and location data
- Implement Redis for session storage

### Security Considerations

#### Input Validation
- Sanitize all user inputs
- Validate file uploads
- Implement CSRF protection

#### Access Control
- Verify user permissions for all actions
- Implement rate limiting
- Log admin actions for audit trail

#### Data Protection
- Encrypt sensitive host information
- Implement GDPR compliance features
- Regular security audits

## 📈 Future Enhancements

### Planned Features
- **Payment Integration**: Direct booking with payment processing
- **Calendar Sync**: Integration with external calendar systems
- **Messaging System**: In-platform communication between hosts and guests
- **Mobile App**: Native iOS and Android applications
- **AI Recommendations**: Smart property matching based on preferences
- **Virtual Tours**: 360-degree property views
- **Insurance Integration**: Property and guest protection options

### Technical Improvements
- **API Versioning**: Structured API with version control
- **Microservices**: Break down into smaller, scalable services
- **Real-time Updates**: WebSocket integration for live availability
- **Advanced Analytics**: Machine learning for trend analysis
- **Multi-language Support**: Internationalization framework

## 📞 Support

For technical support or feature requests:

1. **Documentation**: Check this README and inline code comments
2. **Issues**: Report bugs through the project issue tracker
3. **Community**: Join the developer community for discussions
4. **Updates**: Follow the project for latest features and fixes

## 📄 License

This system is part of the JShuk platform. Please refer to the main project license for usage terms and conditions.

---

**Last Updated**: January 2024  
**Version**: 1.0.0  
**Compatibility**: PHP 8.0+, MySQL 8.0+ 