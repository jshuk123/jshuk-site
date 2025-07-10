<?php
/**
 * Environment Configuration Example
 * Copy this file to environment.php and update with your values
 */

return [
    // Application Environment
    'APP_ENV' => 'production', // 'development', 'staging', 'production'
    'SITE_URL' => 'https://jshuk.com',
    
    // Database Configuration
    'DB_HOST' => 'localhost',
    'DB_NAME' => 'jshuk_db',
    'DB_USER' => 'jshuk_user',
    'DB_PASS' => 'your_secure_password_here',
    
    // Google Maps API Key
    'GOOGLE_MAPS_API_KEY' => 'your_google_maps_api_key_here',
    
    // Stripe Configuration (for payments)
    'STRIPE_PUBLISHABLE_KEY' => 'pk_test_your_stripe_publishable_key',
    'STRIPE_SECRET_KEY' => 'sk_test_your_stripe_secret_key',
    
    // Email Configuration (SMTP)
    'SMTP_HOST' => 'smtp.gmail.com',
    'SMTP_PORT' => 587,
    'SMTP_USERNAME' => 'your_email@gmail.com',
    'SMTP_PASSWORD' => 'your_app_password',
    'SMTP_ENCRYPTION' => 'tls',
    
    // Security Settings
    'SESSION_SECRET' => 'your_random_session_secret_here',
    'CSRF_SECRET' => 'your_random_csrf_secret_here',
    
    // File Upload Settings
    'MAX_FILE_SIZE' => 5242880, // 5MB in bytes
    'UPLOAD_PATH' => '/path/to/uploads/directory',
    
    // Redis Configuration (for caching - optional)
    'REDIS_HOST' => 'localhost',
    'REDIS_PORT' => 6379,
    'REDIS_PASSWORD' => '',
    
    // External Services
    'RECAPTCHA_SITE_KEY' => 'your_recaptcha_site_key',
    'RECAPTCHA_SECRET_KEY' => 'your_recaptcha_secret_key',
    
    // Analytics (optional)
    'GOOGLE_ANALYTICS_ID' => 'GA_MEASUREMENT_ID',
    'FACEBOOK_PIXEL_ID' => 'your_facebook_pixel_id',
    
    // Social Media
    'FACEBOOK_APP_ID' => 'your_facebook_app_id',
    'TWITTER_CARD_TYPE' => 'summary_large_image',
    
    // Development Settings (set to false in production)
    'APP_DEBUG' => false,
    'DISPLAY_ERRORS' => false,
    
    // Rate Limiting
    'RATE_LIMIT_ENABLED' => true,
    'RATE_LIMIT_MAX_ATTEMPTS' => 10,
    'RATE_LIMIT_TIME_WINDOW' => 3600, // 1 hour in seconds
    
    // Session Settings
    'SESSION_LIFETIME' => 86400, // 24 hours in seconds
    'SESSION_SECURE' => true,
    'SESSION_HTTPONLY' => true,
    'SESSION_SAMESITE' => 'Lax',
    
    // File Upload Allowed Types
    'ALLOWED_IMAGE_TYPES' => [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp'
    ],
    
    // Logging
    'LOG_LEVEL' => 'error', // 'debug', 'info', 'warning', 'error'
    'LOG_FILE' => '/path/to/logs/app.log',
    
    // Cache Settings
    'CACHE_ENABLED' => true,
    'CACHE_TTL' => 3600, // 1 hour in seconds
    
    // SEO Settings
    'DEFAULT_META_TITLE' => 'JShuk - Jewish Local Directory',
    'DEFAULT_META_DESCRIPTION' => 'Discover and support local Jewish businesses through JShuk – your friendly directory for trusted services, simchas, classifieds and more.',
    'DEFAULT_META_KEYWORDS' => 'JShuk, Jewish business, local directory, kosher, simcha, jobs, classifieds, events, support',
    
    // Feature Flags
    'FEATURE_REGISTRATION' => true,
    'FEATURE_BUSINESS_SUBMISSION' => true,
    'FEATURE_TESTIMONIALS' => true,
    'FEATURE_CLASSIFIEDS' => true,
    'FEATURE_JOBS' => true,
    'FEATURE_ADS' => true,
    'FEATURE_PAYMENTS' => true,
    
    // Maintenance Mode
    'MAINTENANCE_MODE' => false,
    'MAINTENANCE_MESSAGE' => 'We are currently performing maintenance. Please check back soon.',
    
    // Backup Settings
    'BACKUP_ENABLED' => true,
    'BACKUP_FREQUENCY' => 'daily', // 'hourly', 'daily', 'weekly'
    'BACKUP_RETENTION' => 30, // days to keep backups
    
    // Performance
    'ENABLE_COMPRESSION' => true,
    'ENABLE_CACHING' => true,
    'ENABLE_MINIFICATION' => true,
    
    // Security Headers
    'SECURITY_HEADERS' => [
        'X-Frame-Options' => 'SAMEORIGIN',
        'X-XSS-Protection' => '1; mode=block',
        'X-Content-Type-Options' => 'nosniff',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'Permissions-Policy' => 'geolocation=(), microphone=(), camera=()'
    ]
]; 