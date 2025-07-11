# JShuk Lost & Found System

A community-powered, halachically sound Lost & Found board for the Jewish community.

## 🎯 Overview

The Lost & Found system allows users to:
- Post lost or found items with full halachic sensitivity
- Browse items by category, location, and date
- Reunite items via a simanim-based claim process
- Stay anonymous if preferred
- Share items via WhatsApp

## 📁 File Structure

```
├── lostfound.php                    # Main Lost & Found page
├── post_lostfound.php              # Post an item form
├── actions/
│   └── submit_claim.php            # Handle claim submissions
├── admin/
│   └── lostfound.php               # Admin management panel
├── css/pages/
│   ├── lostfound.css               # Main page styling
│   └── post_lostfound.css          # Post form styling
├── sql/
│   └── create_lostfound_tables.sql # Database migration
└── scripts/
    └── run_lostfound_migration.php # Migration runner
```

## 🗄️ Database Schema

### Tables

#### `lostfound_posts`
- `id` - Primary key
- `post_type` - 'lost' or 'found'
- `title` - Item title
- `category` - Item category
- `location` - Where lost/found
- `date_lost_found` - Date of incident
- `description` - Item description
- `image_paths` - JSON array of image paths
- `is_blurred` - Whether images are blurred
- `contact_*` - Contact information
- `is_anonymous` - Anonymous posting
- `hide_contact_until_verified` - Privacy setting
- `status` - 'active', 'reunited', 'archived'
- `user_id` - Optional user association
- `created_at`, `updated_at` - Timestamps

#### `lostfound_claims`
- `id` - Primary key
- `post_id` - Reference to post
- `claimant_name` - Claimant's name
- `simanim` - Identifying signs
- `claim_description` - Detailed description
- `claim_date` - When item was lost
- `contact_*` - Contact information
- `status` - 'pending', 'approved', 'rejected'
- `created_at`, `updated_at` - Timestamps

#### `lostfound_categories`
- `id` - Primary key
- `name` - Category name
- `icon` - FontAwesome icon class
- `description` - Category description
- `is_active` - Whether category is active
- `sort_order` - Display order

#### `lostfound_locations`
- `id` - Primary key
- `name` - Location name
- `area` - General area
- `is_active` - Whether location is active
- `sort_order` - Display order

## 🚀 Installation

### 1. Run Database Migration

```bash
php scripts/run_lostfound_migration.php
```

This will:
- Create all necessary tables
- Insert default categories and locations
- Verify the installation

### 2. Verify Installation

Check these URLs:
- `/lostfound.php` - Main Lost & Found page
- `/post_lostfound.php` - Post an item form
- `/admin/lostfound.php` - Admin management panel

### 3. Configure Email (Optional)

For email notifications, ensure your SMTP settings are configured in `config/config.php`:

```php
define('SMTP_HOST', 'your-smtp-host');
define('SMTP_USERNAME', 'your-username');
define('SMTP_PASSWORD', 'your-password');
```

## 🎨 Features

### Main Page (`/lostfound.php`)

#### Hero Section
- Compelling headline and description
- Call-to-action buttons
- Mitzvah counter showing community impact

#### Filter Bar
- Keyword search
- Post type filter (Lost/Found)
- Category dropdown
- Location dropdown
- Date range picker
- Toggle filters (recent only, unresolved only)

#### Listing Cards
- Status badges (Lost/Found/Reunited)
- Item images (with blur option)
- Location and category info
- Contact buttons (WhatsApp, Email, Phone)
- "I think this is mine" claim button

### Post Form (`/post_lostfound.php`)

#### Form Fields
- Post type selection (Lost/Found)
- Item title and category
- Location and date
- Description (with character counter)
- Image upload (up to 3 images)
- Contact information
- Privacy options

#### Halachic Features
- Automatic reminder for "Found" posts
- Option to blur images for privacy
- Contact hiding until simanim verification
- Anonymous posting option

### Claim System

#### Claim Modal
- Claimant information
- Detailed item description
- Simanim (identifying signs)
- Contact information
- CAPTCHA protection

#### Email Notifications
- Notification to post owner
- Confirmation to claimant
- HTML email templates

### Admin Panel (`/admin/lostfound.php`)

#### Dashboard
- Statistics overview
- Recent activity
- Quick actions

#### Management Tools
- View all posts and claims
- Update post status
- Approve/reject claims
- Delete inappropriate content
- Filter and search functionality

## 🔧 Configuration

### Default Categories
- Keys, Phones, Hats, Jewelry, Sefarim
- Bags, Clothing, Electronics, Documents, Other

### Default Locations
- London areas: Golders Green, Edgware, Stamford Hill, Hendon, Finchley
- Other cities: Manchester, Gateshead, Leeds, Birmingham, Liverpool

### Settings
- Auto-archive posts after 30 days
- Maximum 3 images per post
- 5MB image size limit
- 1000 character description limit

## 🎯 Halachic Considerations

### Found Items
- Automatic reminder not to describe items in full detail
- Blur image option for privacy
- Contact hiding until simanim verification
- Emphasis on simanim (identifying signs)

### Lost Items
- Detailed description allowed
- Multiple contact methods
- Community assistance focus

### General
- Anonymous posting option
- Privacy controls
- Community-based verification

## 📱 Mobile Responsiveness

- Responsive design for all screen sizes
- Mobile-optimized forms
- Touch-friendly buttons
- Sticky "Post an Item" button on mobile

## 🔒 Security Features

- CSRF protection on all forms
- Input validation and sanitization
- File upload security
- Rate limiting
- Admin authentication

## 📧 Email System

### Templates
- HTML email templates
- Responsive design
- Branded styling
- Clear call-to-action buttons

### Notifications
- Post owner notification for new claims
- Claimant confirmation
- Admin alerts (optional)

## 🧪 Testing

### Manual Testing Checklist
- [ ] Post a lost item
- [ ] Post a found item
- [ ] Upload images
- [ ] Submit a claim
- [ ] Approve/reject claims
- [ ] Test email notifications
- [ ] Test mobile responsiveness
- [ ] Test admin panel

### Automated Testing
```bash
# Run migration test
php scripts/run_lostfound_migration.php

# Check database tables
mysql -u username -p database_name -e "SHOW TABLES LIKE 'lostfound_%';"
```

## 🚀 Deployment

### Production Checklist
- [ ] Run database migration
- [ ] Configure SMTP settings
- [ ] Test email notifications
- [ ] Verify admin access
- [ ] Check mobile responsiveness
- [ ] Test image uploads
- [ ] Verify privacy settings

### Environment Variables
```bash
# Database
DB_HOST=localhost
DB_NAME=your_database
DB_USER=your_username
DB_PASS=your_password

# Email
SMTP_HOST=your-smtp-host
SMTP_USERNAME=your-username
SMTP_PASSWORD=your-password
```

## 🔄 Maintenance

### Regular Tasks
- Monitor for inappropriate content
- Review and approve claims
- Archive old posts
- Update categories/locations as needed

### Database Maintenance
```sql
-- Archive old posts
UPDATE lostfound_posts 
SET status = 'archived' 
WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY) 
AND status = 'active';

-- Clean up old claims
DELETE FROM lostfound_claims 
WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY) 
AND status IN ('rejected', 'approved');
```

## 🐛 Troubleshooting

### Common Issues

#### Database Connection
- Check database credentials
- Verify table creation
- Run migration script

#### Email Notifications
- Check SMTP settings
- Verify email templates
- Test with simple mail() function

#### Image Uploads
- Check upload directory permissions
- Verify file size limits
- Test image formats

#### Admin Access
- Verify admin user permissions
- Check session configuration
- Test admin login

### Debug Mode
Enable debug mode in `config/config.php`:
```php
define('APP_DEBUG', true);
```

## 📈 Analytics

### Key Metrics
- Total posts created
- Items reunited
- Claims submitted
- User engagement

### Tracking
- Post creation rates
- Claim success rates
- Popular categories/locations
- User retention

## 🔮 Future Enhancements

### Phase 2 Features
- Smart matching algorithm
- WhatsApp integration
- Bar/Bat Mitzvah project integration
- Venue tagging
- Auto-expire notifications
- Advanced search filters

### API Development
- RESTful API endpoints
- Mobile app integration
- Third-party integrations

## 📞 Support

For technical support or questions:
- Email: support@jshuk.com
- Documentation: This README
- Issues: Create GitHub issue

## 📄 License

This system is part of the JShuk platform and follows the same licensing terms.

---

**Built with ❤️ for the Jewish community** 