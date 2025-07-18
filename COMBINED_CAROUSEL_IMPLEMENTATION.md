# 🎠 Combined Carousel Implementation Guide

## Overview

The **Community Highlights** carousel on the JShuk homepage now combines two powerful data sources:

1. **🎠 Sponsored Slides** from the Enhanced Carousel Manager
2. **🏢 Featured Businesses** from the business directory

This creates a dynamic, priority-based showcase that automatically displays your most important content.

## 🎯 How It Works

### Data Sources

#### Query A: Sponsored Slides
- **Source**: `carousel_slides` table
- **Filter**: Active slides in "homepage" zone within date range
- **Priority**: Uses custom priority values (0-15+)
- **Content**: Custom titles, subtitles, images, and CTAs

#### Query B: Featured Businesses  
- **Source**: `businesses` table + related tables
- **Filter**: Active businesses with Premium/Premium+ subscription
- **Priority**: Mapped from subscription tier
- **Content**: Business name, category, profile image, business link

### Priority System

| Content Type | Priority | Description |
|--------------|----------|-------------|
| Carousel Slides | 0-15+ | Custom priority set in Carousel Manager |
| Premium+ Businesses | 6 | Elite tier businesses |
| Premium Businesses | 5 | Premium tier businesses |
| Basic Businesses | 4 | Basic tier (not shown) |

### Sorting Logic

1. **Merge**: Both data sources are combined into a single array
2. **Sort**: Array is sorted by priority (highest first)
3. **Filter**: Only slides with valid images are displayed
4. **Display**: Carousel renders with conditional "Featured" tags

## 🔧 Technical Implementation

### File: `sections/featured_showcase.php`

```php
// QUERY A: Get sponsored slides from Enhanced Carousel Manager
$stmt = $pdo->prepare("
    SELECT id, title, subtitle, image_url, cta_text, cta_link, 
           priority, sponsored, 'carousel_slide' as slide_type
    FROM carousel_slides
    WHERE active = 1 AND zone = :zone
      AND (start_date IS NULL OR start_date <= :today)
      AND (end_date IS NULL OR end_date >= :today)
    ORDER BY priority DESC, id DESC
");

// QUERY B: Get featured businesses from directory
$stmt = $pdo->prepare("
    SELECT b.id, b.business_name as title, c.name as subtitle,
           COALESCE(bi.file_path, 'images/jshuk-logo.png') as image_url,
           'View Profile' as cta_text, CONCAT('business.php?id=', b.id) as cta_link,
           CASE 
               WHEN u.subscription_tier = 'premium_plus' THEN 6
               WHEN u.subscription_tier = 'premium' THEN 5
               ELSE 4
           END as priority,
           1 as sponsored, 'featured_business' as slide_type
    FROM businesses b 
    LEFT JOIN business_categories c ON b.category_id = c.id 
    LEFT JOIN users u ON b.user_id = u.id
    LEFT JOIN business_images bi ON b.id = bi.business_id AND bi.sort_order = 0
    WHERE b.status = 'active' 
    AND u.subscription_tier IN ('premium', 'premium_plus')
    ORDER BY subscription_tier, b.created_at DESC 
    LIMIT 10
");

// Sort combined array by priority
usort($all_slides, function($a, $b) {
    return ($b['priority'] ?? 0) - ($a['priority'] ?? 0);
});
```

### Conditional Display Logic

```php
<?php if ($slide['slide_type'] === 'featured_business'): ?>
    <span class="featured-tag">Featured</span>
<?php endif; ?>
```

## 📊 Database Schema

### Required Tables

#### `carousel_slides`
```sql
CREATE TABLE carousel_slides (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    subtitle TEXT,
    image_url VARCHAR(255) NOT NULL,
    cta_text VARCHAR(100),
    cta_link VARCHAR(255),
    priority INT DEFAULT 0,
    location VARCHAR(100) DEFAULT 'all',
    sponsored TINYINT(1) DEFAULT 0,
    start_date DATE,
    end_date DATE,
    active TINYINT(1) DEFAULT 1,
    zone VARCHAR(100) DEFAULT 'homepage',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

#### `businesses` + `users` + `business_images`
- Businesses with subscription tiers
- Business profile images
- Category information

## 🎨 Frontend Display

### Visual Elements

1. **Background Image**: Full-bleed carousel image
2. **Overlay**: Semi-transparent dark overlay for text readability
3. **Title**: Main headline (slide title or business name)
4. **Subtitle**: Secondary text (slide subtitle or business category)
5. **CTA Button**: Action button with custom text
6. **Featured Tag**: Yellow "Featured" badge (businesses only)

### Responsive Design

- **Desktop**: 500px height, full-width carousel
- **Mobile**: 400px height, optimized navigation
- **Navigation**: Swiper.js with autoplay and pagination

## 🚀 Usage Instructions

### For Administrators

#### Adding Sponsored Slides
1. Go to **Enhanced Carousel Manager** (`admin/enhanced_carousel_manager.php`)
2. Create new slide with:
   - **Zone**: "homepage"
   - **Priority**: Set high value (10-15) to appear first
   - **Image**: Upload high-quality image
   - **CTA**: Custom button text and link

#### Managing Featured Businesses
1. Go to **Business Directory** (`businesses.php`)
2. Find businesses with Premium/Premium+ subscription
3. Ensure they have profile images uploaded
4. Verify business status is "active"

### For Developers

#### Adding New Content Types
1. Add new query to fetch data
2. Map priority values appropriately
3. Add `slide_type` identifier
4. Update conditional display logic

#### Modifying Priority System
1. Update priority mapping in Query B
2. Adjust usort function if needed
3. Test with different subscription tiers

## 🧪 Testing

### Test Files
- `test_combined_carousel.php` - PHP test script
- `test_combined_carousel.html` - Browser test page

### Manual Testing Steps
1. **Check Carousel Manager**: Verify active slides exist
2. **Check Featured Businesses**: Confirm Premium/Premium+ businesses
3. **View Homepage**: Scroll to "Community Highlights" section
4. **Verify Display**: Both content types should appear
5. **Check Priority**: Higher priority items should appear first
6. **Test Navigation**: Carousel should loop and auto-play

### Expected Results
- ✅ Both sponsored slides and featured businesses display
- ✅ Priority sorting works correctly
- ✅ "Featured" tags only on business slides
- ✅ Links work properly (custom vs business profiles)
- ✅ Images load without errors
- ✅ Carousel navigation functions

## 🔧 Troubleshooting

### Common Issues

#### No Slides Appear
- Check `carousel_slides` table has active slides
- Verify businesses have Premium/Premium+ subscription
- Check image file paths exist
- Review database connection

#### Only One Type Appears
- Check zone setting in carousel_slides (should be 'homepage')
- Verify subscription_tier values in users table
- Check business status is 'active'

#### Priority Sorting Issues
- Verify priority values in carousel_slides table
- Check subscription_tier mapping logic
- Review usort function in featured_showcase.php

#### Image Loading Problems
- Check file permissions on upload directories
- Verify image paths are correct
- Ensure images exist on filesystem

### Debug Queries

```sql
-- Check carousel slides
SELECT * FROM carousel_slides 
WHERE active = 1 AND zone = 'homepage'
ORDER BY priority DESC;

-- Check featured businesses
SELECT b.business_name, u.subscription_tier, b.status
FROM businesses b 
JOIN users u ON b.user_id = u.id
WHERE u.subscription_tier IN ('premium', 'premium_plus')
AND b.status = 'active';
```

## 📈 Performance Considerations

### Optimization Tips
1. **Indexes**: Ensure proper database indexes exist
2. **Image Optimization**: Use compressed images for faster loading
3. **Caching**: Consider implementing slide caching
4. **Lazy Loading**: Images load as needed

### Monitoring
- Track carousel performance via analytics
- Monitor image loading times
- Check database query performance
- Review user engagement metrics

## 🎯 Future Enhancements

### Potential Improvements
1. **A/B Testing**: Test different priority algorithms
2. **Analytics**: Track click-through rates by content type
3. **Personalization**: Show content based on user preferences
4. **Dynamic Loading**: Load more content as user scrolls
5. **Mobile Optimization**: Enhanced mobile carousel experience

### Integration Opportunities
1. **Email Marketing**: Feature carousel content in newsletters
2. **Social Media**: Share carousel highlights on social platforms
3. **Search Integration**: Include carousel content in search results
4. **API Access**: Provide carousel data via API endpoints

## 📞 Support

For technical support or questions about the combined carousel implementation:

1. **Check Documentation**: Review this guide thoroughly
2. **Run Tests**: Use the provided test files
3. **Review Logs**: Check error logs for debugging information
4. **Database Queries**: Use the debug queries above

---

**Implementation Date**: December 2024  
**Version**: 1.0  
**Status**: ✅ Complete and Tested 