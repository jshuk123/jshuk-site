# JShuk Community Corner Implementation

## Overview

The Community Corner is a modular, dynamic section on the JShuk homepage that showcases meaningful community content like gemachim, lost & found, Torah thoughts, simchas, and more. It's built to support long-term modular growth and provides a clean, emotional, mobile-friendly, and SEO-considerate format.

## Features Implemented

### ✅ Core Features
- **Dynamic Content Management**: Database-driven content with admin interface
- **Multiple Content Types**: Gemachim, Lost & Found, Simchas, Charity Alerts, Divrei Torah, Ask the Rabbi, Volunteer Opportunities, Photo of the Week
- **Responsive Design**: Mobile-first approach with clean, modern UI
- **Analytics Tracking**: View and click tracking for content performance
- **Admin Interface**: Full CRUD operations for managing community content
- **Fallback Content**: Graceful degradation when no content is available

### ✅ Content Types Supported
1. **Gemachim** (🍼) - Show meaningful chessed activity
2. **Lost & Found** (🎒) - Help users reunite with lost items
3. **Simcha Notices** (🎉) - Celebrate lifecycle events
4. **Charity Alerts** (❤️) - Display urgent tzedakah needs
5. **Divrei Torah** (🕯️) - Share short weekly insights
6. **Ask the Rabbi** (📜) - Feature Q&A content
7. **Volunteer Opportunities** (🤝) - Share mitzvah requests
8. **Photo of the Week** (📸) - Highlight community life visually

## Database Structure

### `community_corner` Table
```sql
CREATE TABLE `community_corner` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `body_text` TEXT NOT NULL,
  `type` ENUM('gemach', 'lost_found', 'simcha', 'charity_alert', 'divrei_torah', 'ask_rabbi', 'volunteer', 'photo_week') NOT NULL,
  `emoji` VARCHAR(10) DEFAULT '❤️',
  `link_url` VARCHAR(255),
  `link_text` VARCHAR(100) DEFAULT 'Learn More →',
  `is_featured` BOOLEAN DEFAULT FALSE,
  `is_active` BOOLEAN DEFAULT TRUE,
  `priority` INT DEFAULT 0,
  `date_added` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `expire_date` DATE NULL,
  `created_by` INT,
  `approved_by` INT,
  `approved_at` TIMESTAMP NULL,
  `views_count` INT DEFAULT 0,
  `clicks_count` INT DEFAULT 0
);
```

## File Structure

```
├── sql/
│   └── create_community_corner_table.sql          # Database schema
├── includes/
│   └── community_corner_functions.php             # Core functions
├── api/
│   ├── track_community_corner_view.php            # View tracking
│   ├── track_community_corner_click.php           # Click tracking
│   └── get_community_corner_item.php              # Get item for editing
├── admin/
│   └── community_corner.php                       # Admin interface
├── css/
│   └── pages/
│       └── lost_and_found.css                     # Page-specific styles
├── lost_and_found.php                             # Example community page
├── scripts/
│   └── run_community_corner_migration.php         # Migration script
└── index.php                                      # Updated homepage
```

## Installation & Setup

### 1. Run Database Migration
```bash
php scripts/run_community_corner_migration.php
```

### 2. Verify Installation
- Visit the homepage to see the Community Corner section
- Access `/admin/community_corner.php` to manage content
- Visit `/lost_and_found.php` to see an example community page

## Usage

### For Admins

#### Managing Content
1. Navigate to `/admin/community_corner.php`
2. Add new items using the form at the top
3. Edit existing items by clicking the "Edit" button
4. Delete items using the "Delete" button
5. Control visibility with "Featured" and "Active" checkboxes

#### Content Guidelines
- **Title**: Keep it concise and descriptive
- **Content**: Write engaging, community-focused text
- **Emoji**: Use relevant emojis for visual appeal
- **Priority**: Higher numbers = higher display priority
- **Expire Date**: Set for time-sensitive content

### For Developers

#### Adding New Content Types
1. Update the `type` ENUM in the database
2. Add type info to `getCommunityCornerTypeInfo()` function
3. Create corresponding page (optional)
4. Update admin interface if needed

#### Customizing Display
- Modify `getFeaturedCommunityCornerItems()` for different selection logic
- Update CSS in `css/pages/homepage.css` for styling changes
- Customize the homepage section in `index.php`

## API Endpoints

### Track View
```
POST /api/track_community_corner_view.php
Content-Type: application/json
{"item_id": 123}
```

### Track Click
```
POST /api/track_community_corner_click.php
Content-Type: application/json
{"item_id": 123}
```

### Get Item (Admin)
```
GET /api/get_community_corner_item.php?id=123
```

## Key Functions

### `getFeaturedCommunityCornerItems($limit = 4)`
Returns featured items for homepage display, with fallback to active items.

### `getCommunityCornerItemsByType($type, $limit = 10)`
Returns items filtered by type for specific community pages.

### `incrementCommunityCornerViews($itemId)`
Tracks view count for analytics.

### `incrementCommunityCornerClicks($itemId)`
Tracks click count for analytics.

### `getCommunityCornerTypeInfo()`
Returns metadata for all content types including display names, emojis, and colors.

## Styling

### CSS Classes
- `.community-section` - Main container
- `.community-cards` - Grid layout for cards
- `.community-card` - Individual content cards
- `.community-emoji` - Emoji display
- `.community-cta-link` - Call-to-action links

### Responsive Design
- Mobile-first approach
- Grid adapts from 1 column (mobile) to 4 columns (desktop)
- Touch-friendly tap targets (>44px)
- Optimized typography for readability

## Analytics & Performance

### Tracking
- View counts are tracked automatically when cards are visible
- Click counts are tracked when users click CTA links
- Data is stored in the database for admin review

### Performance
- Content is loaded efficiently with the main page
- Fallback content prevents empty states
- Minimal JavaScript for tracking
- Optimized database queries with proper indexing

## Future Enhancements

### Phase 2 Features
- [ ] User submission forms for community content
- [ ] Email notifications for urgent items
- [ ] WhatsApp integration for real-time updates
- [ ] Advanced filtering and search
- [ ] Content moderation workflow
- [ ] Social sharing integration
- [ ] Location-based content filtering

### Additional Pages
- [ ] `/ask-the-rabbi.php` - Q&A interface
- [ ] `/divrei-torah.php` - Torah insights archive
- [ ] `/simchas.php` - Celebration announcements
- [ ] `/charity_alerts.php` - Tzedakah needs
- [ ] `/volunteer.php` - Volunteer opportunities

## Troubleshooting

### Common Issues

1. **No content showing on homepage**
   - Check if items are marked as `is_featured = 1`
   - Verify `is_active = 1` for items
   - Check if `expire_date` is in the future

2. **Admin interface not accessible**
   - Ensure user has admin privileges
   - Check session authentication

3. **Database errors**
   - Run migration script again
   - Check database connection
   - Verify table structure

### Debug Mode
Enable debug mode in `config/config.php` to see detailed error messages.

## Support

For technical support or feature requests, please refer to the main JShuk documentation or contact the development team.

---

**Version**: 1.0  
**Last Updated**: December 2024  
**Compatibility**: PHP 7.4+, MySQL 5.7+ 