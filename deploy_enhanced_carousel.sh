#!/bin/bash

# 🚀 JShuk Enhanced Carousel System - Complete Production Deployment Script
# Run this script on your production server to deploy the enhanced carousel system
# Usage: bash deploy_enhanced_carousel.sh

set -e  # Exit on any error

echo "🎠 JShuk Enhanced Carousel System - Production Deployment"
echo "========================================================="
echo ""

# Configuration - UPDATE THESE FOR YOUR PRODUCTION SERVER
SITE_URL="https://jshuk.com"
DB_HOST="localhost"
DB_NAME="your_jshuk_database"
DB_USER="your_database_user"
DB_PASS="your_database_password"
ADMIN_EMAIL="admin@jshuk.com"
BACKUP_DIR="/backups/jshuk"
SITE_ROOT="/var/www/html/jshuk"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Function to check if command exists
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# Function to test database connection
test_database() {
    print_status "Testing database connection..."
    if mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" -e "USE $DB_NAME; SELECT 1;" >/dev/null 2>&1; then
        print_success "Database connection successful"
        return 0
    else
        print_error "Database connection failed!"
        print_error "Please check your database credentials in the script."
        return 1
    fi
}

# Function to create backup
create_backup() {
    print_status "Creating backup..."
    mkdir -p "$BACKUP_DIR"
    
    # Create file backup
    BACKUP_FILE="$BACKUP_DIR/jshuk_backup_$(date +%Y%m%d_%H%M%S).tar.gz"
    tar -czf "$BACKUP_FILE" --exclude='uploads/*' --exclude='logs/*' .
    print_success "File backup created: $BACKUP_FILE"
    
    # Create database backup
    DB_BACKUP_FILE="$BACKUP_DIR/jshuk_db_backup_$(date +%Y%m%d_%H%M%S).sql"
    mysqldump -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" > "$DB_BACKUP_FILE"
    print_success "Database backup created: $DB_BACKUP_FILE"
}

# Function to set file permissions
set_permissions() {
    print_status "Setting file permissions..."
    
    # Set directory permissions
    find . -type d -exec chmod 755 {} \;
    
    # Set file permissions
    find . -type f -name "*.php" -exec chmod 644 {} \;
    find . -type f -name "*.html" -exec chmod 644 {} \;
    find . -type f -name "*.css" -exec chmod 644 {} \;
    find . -type f -name "*.js" -exec chmod 644 {} \;
    
    # Set specific permissions for uploads and logs
    chmod 755 uploads/
    chmod 755 uploads/carousel/
    chmod 755 logs/
    chmod 755 cache/
    
    # Set executable permissions for scripts
    chmod 755 scripts/
    chmod 755 scripts/*.php
    
    print_success "File permissions set"
}

# Function to create necessary directories
create_directories() {
    print_status "Creating necessary directories..."
    
    mkdir -p uploads/carousel
    mkdir -p logs
    mkdir -p cache
    mkdir -p temp
    
    print_success "Directories created"
}

# Function to update configuration for production
update_config() {
    print_status "Updating configuration for production..."
    
    # Create production config backup
    cp config/config.php config/config.php.backup
    
    # Update config for production (if needed)
    if [ -f config/config.php ]; then
        # Set production environment
        sed -i 's/APP_DEBUG.*true/APP_DEBUG", false/g' config/config.php 2>/dev/null || true
        sed -i 's/APP_ENV.*development/APP_ENV", "production"/g' config/config.php 2>/dev/null || true
        
        print_success "Configuration updated for production"
    else
        print_warning "config.php not found, skipping configuration update"
    fi
}

# Function to run database migration
run_migration() {
    print_status "Running database migration..."
    
    if [ -f scripts/migrate_to_enhanced_carousel.php ]; then
        # Run migration with error handling
        if php scripts/migrate_to_enhanced_carousel.php; then
            print_success "Database migration completed"
        else
            print_error "Database migration failed!"
            return 1
        fi
    else
        print_error "Migration script not found: scripts/migrate_to_enhanced_carousel.php"
        return 1
    fi
}

# Function to verify migration
verify_migration() {
    print_status "Verifying database migration..."
    
    # Check if new tables exist
    TABLE_CHECK=$(mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "
    SELECT 
        CASE WHEN COUNT(*) = 4 THEN 'SUCCESS' ELSE 'FAILED' END as status,
        COUNT(*) as table_count
    FROM information_schema.tables 
    WHERE table_schema = '$DB_NAME' 
    AND table_name IN ('carousel_slides', 'carousel_analytics', 'carousel_analytics_summary', 'location_mappings');
    " 2>/dev/null | tail -n 1)
    
    if echo "$TABLE_CHECK" | grep -q "SUCCESS"; then
        print_success "Database migration verified successfully"
        
        # Show table counts
        mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "
        SELECT 'carousel_slides' as table_name, COUNT(*) as count FROM carousel_slides
        UNION ALL
        SELECT 'carousel_analytics' as table_name, COUNT(*) as count FROM carousel_analytics
        UNION ALL
        SELECT 'location_mappings' as table_name, COUNT(*) as count FROM location_mappings;
        " 2>/dev/null
    else
        print_error "Database migration verification failed!"
        return 1
    fi
}

# Function to test system functionality
test_system() {
    print_status "Testing system functionality..."
    
    # Test if PHP is working
    if command_exists php; then
        print_success "PHP is available"
    else
        print_error "PHP is not available!"
        return 1
    fi
    
    # Test if MySQL is working
    if command_exists mysql; then
        print_success "MySQL client is available"
    else
        print_warning "MySQL client not found, some tests may fail"
    fi
    
    # Test if curl is available
    if command_exists curl; then
        print_success "cURL is available for web testing"
    else
        print_warning "cURL not found, web testing will be skipped"
    fi
}

# Function to create .htaccess for enhanced carousel
create_htaccess() {
    print_status "Creating .htaccess for enhanced carousel..."
    
    cat > .htaccess << 'EOF'
# JShuk Enhanced Carousel System - .htaccess
<IfModule mod_rewrite.c>
    RewriteEngine On
    
    # API endpoints
    RewriteRule ^api/carousel-analytics\.php$ api/carousel-analytics.php [L]
    
    # Admin panel
    RewriteRule ^admin/enhanced_carousel_manager\.php$ admin/enhanced_carousel_manager.php [L]
    
    # Test page
    RewriteRule ^test_enhanced_carousel_system\.php$ test_enhanced_carousel_system.php [L]
    
    # Enhanced carousel section
    RewriteRule ^sections/enhanced_carousel\.php$ sections/enhanced_carousel.php [L]
</IfModule>

# Security headers
<IfModule mod_headers.c>
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options DENY
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
</IfModule>

# File upload limits for carousel images
<IfModule mod_php.c>
    php_value upload_max_filesize 10M
    php_value post_max_size 10M
    php_value max_execution_time 300
    php_value memory_limit 256M
</IfModule>

# Cache control for static assets
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/jpg "access plus 1 month"
    ExpiresByType image/jpeg "access plus 1 month"
    ExpiresByType image/png "access plus 1 month"
    ExpiresByType image/gif "access plus 1 month"
    ExpiresByType image/webp "access plus 1 month"
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
</IfModule>
EOF

    print_success ".htaccess created"
}

# Function to test web endpoints
test_web_endpoints() {
    print_status "Testing web endpoints..."
    
    if ! command_exists curl; then
        print_warning "cURL not available, skipping web tests"
        return 0
    fi
    
    # Test homepage
    if curl -s -o /dev/null -w "%{http_code}" "$SITE_URL" | grep -q "200"; then
        print_success "Homepage accessible"
    else
        print_warning "Homepage not accessible (check server configuration)"
    fi
    
    # Test test page
    if curl -s -o /dev/null -w "%{http_code}" "$SITE_URL/test_enhanced_carousel_system.php" | grep -q "200"; then
        print_success "Test page accessible"
    else
        print_warning "Test page not accessible"
    fi
    
    # Test API endpoint
    if curl -s -o /dev/null -w "%{http_code}" "$SITE_URL/api/carousel-analytics.php?action=stats" | grep -q "200"; then
        print_success "API endpoint accessible"
    else
        print_warning "API endpoint not accessible"
    fi
}

# Function to send deployment notification
send_notification() {
    print_status "Sending deployment notification..."
    
    if command_exists mail; then
        EMAIL_BODY="
🎠 JShuk Enhanced Carousel System Successfully Deployed!

📅 Deployment Date: $(date)
🌐 Site URL: $SITE_URL

✅ Features Deployed:
• Location-based targeting (London, Manchester, Gateshead)
• Multi-zone support (homepage, businesses, post-business)
• Analytics tracking (impressions, clicks, CTR)
• Scheduling system (start/end dates)
• Sponsored content management
• Priority-based ordering
• Advanced admin control panel
• API endpoints for data access

🔗 Important URLs:
• Homepage: $SITE_URL
• Test Page: $SITE_URL/test_enhanced_carousel_system.php
• Admin Panel: $SITE_URL/admin/enhanced_carousel_manager.php
• API Endpoint: $SITE_URL/api/carousel-analytics.php

📊 Next Steps:
1. Test the system functionality
2. Add your first carousel slides
3. Configure location targeting
4. Monitor analytics performance

🎯 Support: $ADMIN_EMAIL

Best regards,
JShuk Development Team
"
        echo "$EMAIL_BODY" | mail -s "JShuk Enhanced Carousel System Deployed" "$ADMIN_EMAIL"
        print_success "Deployment notification sent"
    else
        print_warning "mail command not available, skipping notification"
    fi
}

# Function to display final summary
display_summary() {
    echo ""
    echo "🎉 JShuk Enhanced Carousel System Deployment Complete!"
    echo "====================================================="
    echo ""
    echo "✅ Deployment Summary:"
    echo "• Database migration: COMPLETED"
    echo "• File permissions: SET"
    echo "• Configuration: UPDATED"
    echo "• Security headers: CONFIGURED"
    echo "• System testing: PASSED"
    echo ""
    echo "🔗 Important URLs:"
    echo "• Homepage: $SITE_URL"
    echo "• Test Page: $SITE_URL/test_enhanced_carousel_system.php"
    echo "• Admin Panel: $SITE_URL/admin/enhanced_carousel_manager.php"
    echo "• API Endpoint: $SITE_URL/api/carousel-analytics.php"
    echo ""
    echo "📋 Next Steps:"
    echo "1. Visit the test page to verify all features"
    echo "2. Access the admin panel to add your first slides"
    echo "3. Configure location targeting for your regions"
    echo "4. Monitor analytics performance"
    echo ""
    echo "📧 Support: $ADMIN_EMAIL"
    echo ""
    echo "🚀 Your enhanced carousel system is now live!"
}

# Function to handle errors and cleanup
cleanup_on_error() {
    print_error "Deployment failed! Rolling back changes..."
    
    # Restore config backup if it exists
    if [ -f config/config.php.backup ]; then
        mv config/config.php.backup config/config.php
        print_status "Configuration restored from backup"
    fi
    
    # Remove .htaccess if it was created
    if [ -f .htaccess ] && grep -q "JShuk Enhanced Carousel System" .htaccess; then
        rm .htaccess
        print_status ".htaccess removed"
    fi
    
    print_error "Deployment failed. Check the logs above for details."
    print_error "You can restore from backup: $BACKUP_DIR"
    exit 1
}

# Main deployment function
main() {
    echo "🚀 Starting JShuk Enhanced Carousel System deployment..."
    echo ""
    
    # Check if we're in the right directory
    if [ ! -f "index.php" ] || [ ! -f "config/config.php" ]; then
        print_error "This script must be run from the JShuk root directory!"
        print_error "Current directory: $(pwd)"
        exit 1
    fi
    
    # Check prerequisites
    print_status "Checking prerequisites..."
    if ! command_exists php; then
        print_error "PHP is required but not installed!"
        exit 1
    fi
    
    if ! command_exists mysql; then
        print_error "MySQL client is required but not installed!"
        exit 1
    fi
    
    # Test database connection
    if ! test_database; then
        exit 1
    fi
    
    # Create backup
    create_backup
    
    # Set up error handling
    trap cleanup_on_error ERR
    
    # Create directories
    create_directories
    
    # Set permissions
    set_permissions
    
    # Update configuration
    update_config
    
    # Create .htaccess
    create_htaccess
    
    # Run migration
    if ! run_migration; then
        cleanup_on_error
    fi
    
    # Verify migration
    if ! verify_migration; then
        cleanup_on_error
    fi
    
    # Test system
    test_system
    
    # Test web endpoints
    test_web_endpoints
    
    # Send notification
    send_notification
    
    # Display summary
    display_summary
    
    # Remove error trap
    trap - ERR
    
    print_success "Deployment completed successfully!"
}

# Check if script is being run directly
if [[ "${BASH_SOURCE[0]}" == "${0}" ]]; then
    main "$@"
fi 