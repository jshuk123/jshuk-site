# JShuk Gemachim Directory System

## Overview

The JShuk Gemachim Directory is a comprehensive community lending platform that allows users to discover, borrow, and donate to local gemachim (Jewish community lending organizations). The system provides a modern, mobile-responsive interface for managing community resources and supporting mitzvahs.

## Features

### 🏠 Main Directory (`/gemachim.php`)
- **Hero Section**: Engaging introduction with progress counters
- **Category Grid**: 12 predefined categories with icons and descriptions
- **Advanced Filtering**: Search, category, location, and donation filters
- **Featured Gemach**: Weekly featured gemach showcase
- **Responsive Listings**: Card-based layout with contact information
- **Mobile-First Design**: Optimized for all device sizes

### ➕ Add Gemach (`/add_gemach.php`)
- **Multi-Step Form**: Organized sections for different information types
- **Image Upload**: Support for up to 5 images with preview
- **Admin Approval**: All submissions require admin review
- **Donation Settings**: Optional donation configuration
- **Memory Dedications**: Support for "In Memory Of" dedications

### 💰 Donation System (`/donate.php`)
- **Stripe Integration**: Secure payment processing
- **Amount Presets**: Quick selection buttons (£5, £10, £20, £50, £100)
- **Custom Amounts**: Flexible donation amounts
- **Trust Indicators**: Security badges and SSL encryption
- **Donation Tracking**: Complete donation history and analytics

### 🔧 Admin Panel (`/admin/gemachim.php`)
- **Dashboard Statistics**: Real-time metrics and analytics
- **Approval System**: Approve/reject pending submissions
- **Feature Management**: Feature/unfeature gemachim
- **Donation Controls**: Enable/disable donation functionality
- **Export Options**: CSV and JSON data export
- **Bulk Actions**: Mass operations for efficiency

## Database Schema

### Core Tables

#### `gemach_categories`
```sql
- id (INT, PRIMARY KEY)
- name (VARCHAR(100))
- slug (VARCHAR(100), UNIQUE)
- description (TEXT)
- icon_class (VARCHAR(100))
- sort_order (INT)
- is_active (BOOLEAN)
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)
```

#### `gemachim`
```sql
- id (INT, PRIMARY KEY)
- name (VARCHAR(255))
- category_id (INT, FOREIGN KEY)
- description (TEXT)
- location (VARCHAR(255))
- contact_phone (VARCHAR(50))
- contact_email (VARCHAR(100))
- whatsapp_link (TEXT)
- image_paths (TEXT, JSON)
- donation_enabled (BOOLEAN)
- donation_link (TEXT)
- in_memory_of (VARCHAR(255))
- verified (BOOLEAN)
- featured (BOOLEAN)
- urgent_need (BOOLEAN)
- status (ENUM: 'active', 'pending', 'inactive')
- submitted_by (INT, FOREIGN KEY)
- views_count (INT)
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)
```

#### `gemach_donations`
```sql
- id (INT, PRIMARY KEY)
- gemach_id (INT, FOREIGN KEY)
- donor_name (VARCHAR(255))
- donor_email (VARCHAR(100))
- amount (DECIMAL(10,2))
- payment_method (ENUM: 'stripe', 'paypal', 'bank_transfer', 'other')
- transaction_id (VARCHAR(255))
- status (ENUM: 'pending', 'completed', 'failed', 'refunded')
- notes (TEXT)
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)
```

#### `gemach_testimonials`
```sql
- id (INT, PRIMARY KEY)
- gemach_id (INT, FOREIGN KEY)
- user_id (INT, FOREIGN KEY)
- testimonial (TEXT)
- rating (TINYINT, 1-5)
- is_approved (BOOLEAN)
- created_at (TIMESTAMP)
```

#### `gemach_views`
```sql
- id (INT, PRIMARY KEY)
- gemach_id (INT, FOREIGN KEY)
- ip_address (VARCHAR(45))
- user_agent (TEXT)
- viewed_at (TIMESTAMP)
```

## Installation

### 1. Database Setup
Run the SQL migration script:
```bash
mysql -u username -p database_name < sql/create_gemachim_tables.sql
```

### 2. File Structure
Ensure the following files are in place:
```
/
├── gemachim.php              # Main directory page
├── add_gemach.php           # Add gemach form
├── donate.php               # Donation page
├── admin/
│   └── gemachim.php         # Admin management panel
├── css/pages/
│   ├── gemachim.css         # Main directory styles
│   ├── add_gemach.css       # Form styles
│   └── donate.css           # Donation page styles
├── uploads/
│   └── gemachim/            # Image upload directory
└── sql/
    └── create_gemachim_tables.sql
```

### 3. Configuration
Update your `config/config.php` with:
- Stripe API keys (for donations)
- Database connection details
- File upload settings

### 4. Permissions
Ensure the uploads directory is writable:
```bash
chmod 755 uploads/gemachim/
```

## Usage

### For Users

#### Browsing Gemachim
1. Visit `/gemachim.php`
2. Use category filters or search functionality
3. Click on gemach cards for detailed information
4. Use contact information to reach gemach operators

#### Adding a Gemach
1. Visit `/add_gemach.php`
2. Fill out the comprehensive form
3. Upload relevant images
4. Submit for admin approval
5. Wait for approval notification

#### Making Donations
1. Click "Donate" on any gemach with donations enabled
2. Choose donation amount or enter custom amount
3. Fill in donor information
4. Complete payment via Stripe
5. Receive confirmation

### For Admins

#### Managing Gemachim
1. Access `/admin/gemachim.php`
2. Review pending submissions
3. Approve/reject gemachim
4. Feature/unfeature gemachim
5. Toggle donation settings
6. Export data for analytics

#### Key Actions
- **Approve**: Changes status to 'active' and marks as verified
- **Reject**: Changes status to 'inactive'
- **Feature**: Promotes gemach to featured status
- **Toggle Donations**: Enables/disables donation functionality
- **Delete**: Permanently removes gemach (use with caution)

## Categories

The system includes 12 predefined categories:

1. **Baby & Maternity** - Baby equipment, maternity items
2. **Clothing** - Clothing for all ages and occasions
3. **Medical Supplies** - Medical equipment, mobility aids
4. **Kitchen Items** - Kitchen appliances, utensils
5. **Simcha Decor** - Decorations for celebrations
6. **Moving & Storage** - Boxes, packing materials
7. **Furniture** - Home and office furniture
8. **Sefarim/Books** - Jewish books and educational materials
9. **Toiletries** - Personal care items, mikveh supplies
10. **Electronics** - Computers, phones, devices
11. **Tools & DIY** - Tools and hardware
12. **Sports & Recreation** - Sports equipment

## Security Features

- **CSRF Protection**: All forms include CSRF tokens
- **Input Validation**: Comprehensive server-side validation
- **File Upload Security**: Type and size restrictions
- **SQL Injection Prevention**: Prepared statements throughout
- **XSS Protection**: Output escaping on all user data
- **Admin Authentication**: Session-based admin verification

## Mobile Responsiveness

The system is fully responsive with:
- Mobile-first design approach
- Touch-friendly interface elements
- Collapsible filter menus
- Floating action buttons
- Optimized image loading
- Responsive typography

## Performance Optimizations

- **Database Indexing**: Optimized queries with proper indexes
- **Image Optimization**: Automatic image compression
- **Caching**: Session-based caching for frequently accessed data
- **Lazy Loading**: Images load as needed
- **CDN Ready**: Static assets optimized for CDN delivery

## Integration Points

### Payment Processing
- Stripe integration for secure donations
- Webhook support for payment confirmations
- Transaction tracking and reporting

### Email Notifications
- Admin approval notifications
- Donation confirmations
- System alerts and updates

### Analytics
- View tracking for gemachim
- Donation analytics
- User engagement metrics

## Customization

### Adding New Categories
1. Insert into `gemach_categories` table
2. Update category grid in `gemachim.php`
3. Add corresponding CSS styles

### Modifying Donation Amounts
Edit the amount buttons in `donate.php`:
```javascript
const amounts = [5, 10, 20, 50, 100]; // Modify as needed
```

### Styling Customization
All styles are in separate CSS files:
- `css/pages/gemachim.css` - Main directory styles
- `css/pages/add_gemach.css` - Form styles
- `css/pages/donate.css` - Donation page styles

## Troubleshooting

### Common Issues

#### Images Not Uploading
- Check upload directory permissions
- Verify file size limits in PHP configuration
- Ensure proper file types are allowed

#### Donations Not Processing
- Verify Stripe API keys are configured
- Check webhook endpoints are accessible
- Review payment method configuration

#### Admin Access Issues
- Verify admin session is active
- Check database user permissions
- Ensure admin flag is set in users table

### Debug Mode
Enable debug mode in `config/config.php`:
```php
define('APP_DEBUG', true);
```

## Future Enhancements

### Phase 2 Features
- **Map Integration**: Google Maps for gemach locations
- **WhatsApp Integration**: Direct messaging from listings
- **User Dashboard**: Personal gemach management
- **Multi-language Support**: Hebrew/English toggle
- **Advanced Analytics**: Detailed reporting and insights
- **Mobile App**: Native mobile application

### API Development
- RESTful API for third-party integrations
- Webhook system for real-time updates
- Developer documentation and SDK

## Support

For technical support or feature requests:
- Check the documentation
- Review the code comments
- Contact the development team

## License

This system is part of the JShuk platform and follows the same licensing terms.

---

**Version**: 1.0.0  
**Last Updated**: December 2024  
**Compatibility**: PHP 7.4+, MySQL 5.7+ 