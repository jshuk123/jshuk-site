#!/bin/bash

# 🚀 JShuk Enhanced Carousel - Git Deployment Script
# Run this on your production server after you've done the SQL import

echo "🎠 JShuk Enhanced Carousel - Git Deployment"
echo "==========================================="
echo ""

# Check if we're in the right directory
if [ ! -f "index.php" ]; then
    echo "❌ Error: This script must be run from the JShuk root directory!"
    echo "Current directory: $(pwd)"
    exit 1
fi

echo "✅ Found JShuk installation in: $(pwd)"
echo ""

# Pull latest changes from Git
echo "📥 Pulling latest changes from Git..."
git pull origin main

if [ $? -eq 0 ]; then
    echo "✅ Git pull successful"
else
    echo "❌ Git pull failed!"
    exit 1
fi

echo ""

# Set file permissions
echo "🔐 Setting file permissions..."
find . -type f -name "*.php" -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;
chmod 755 uploads/carousel/ 2>/dev/null || mkdir -p uploads/carousel && chmod 755 uploads/carousel/
chmod 755 logs/ 2>/dev/null || mkdir -p logs && chmod 755 logs/
chmod 755 cache/ 2>/dev/null || mkdir -p cache && chmod 755 cache/

echo "✅ File permissions set"
echo ""

# Test if files exist
echo "🔍 Checking if enhanced carousel files exist..."
FILES_TO_CHECK=(
    "admin/enhanced_carousel_manager.php"
    "api/carousel-analytics.php"
    "includes/enhanced_carousel_functions.php"
    "sections/enhanced_carousel.php"
    "test_enhanced_carousel_system.php"
)

for file in "${FILES_TO_CHECK[@]}"; do
    if [ -f "$file" ]; then
        echo "✅ $file"
    else
        echo "❌ $file - MISSING!"
    fi
done

echo ""

# Test database connection (if config exists)
if [ -f "config/config.php" ]; then
    echo "🗄️ Testing database connection..."
    php -r "
    require_once 'config/config.php';
    try {
        \$pdo = new PDO(\"mysql:host=\$DB_HOST;dbname=\$DB_NAME\", \$DB_USER, \$DB_PASS);
        echo '✅ Database connection successful\n';
    } catch (PDOException \$e) {
        echo '❌ Database connection failed: ' . \$e->getMessage() . '\n';
    }
    " 2>/dev/null || echo "⚠️ Could not test database connection"
else
    echo "⚠️ config.php not found, skipping database test"
fi

echo ""

# Create .htaccess if it doesn't exist
if [ ! -f ".htaccess" ]; then
    echo "📝 Creating .htaccess file..."
    cat > .htaccess << 'EOF'
# JShuk Enhanced Carousel System
<IfModule mod_rewrite.c>
    RewriteEngine On
    
    # API endpoints
    RewriteRule ^api/carousel-analytics\.php$ api/carousel-analytics.php [L]
    
    # Admin panel
    RewriteRule ^admin/enhanced_carousel_manager\.php$ admin/enhanced_carousel_manager.php [L]
    
    # Test page
    RewriteRule ^test_enhanced_carousel_system\.php$ test_enhanced_carousel_system.php [L]
</IfModule>

# Security headers
<IfModule mod_headers.c>
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options DENY
    Header always set X-XSS-Protection "1; mode=block"
</IfModule>

# File upload limits
<IfModule mod_php.c>
    php_value upload_max_filesize 10M
    php_value post_max_size 10M
</IfModule>
EOF
    echo "✅ .htaccess created"
else
    echo "✅ .htaccess already exists"
fi

echo ""

echo "🎉 Deployment completed!"
echo ""
echo "🔗 Test these URLs:"
echo "• Homepage: https://jshuk.com"
echo "• Test Page: https://jshuk.com/test_enhanced_carousel_system.php"
echo "• Admin Panel: https://jshuk.com/admin/enhanced_carousel_manager.php"
echo "• API: https://jshuk.com/api/carousel-analytics.php?action=stats"
echo ""
echo "📋 Next steps:"
echo "1. Visit the test page to verify everything works"
echo "2. Access the admin panel to add your first slides"
echo "3. Check your homepage for the enhanced carousel"
echo ""
echo "🚀 Your enhanced carousel system should now be live!" 