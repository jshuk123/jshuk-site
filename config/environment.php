<?php
/**
 * Environment Configuration
 * Load environment variables from .env file or system environment
 */

// Load environment variables from .env file if it exists
$env_file = __DIR__ . '/../.env';
if (file_exists($env_file)) {
    $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value, '"\'');
            
            if (!getenv($key)) {
                putenv("$key=$value");
                $_ENV[$key] = $value;
            }
        }
    }
}

// Set default environment variables if not already set
$defaults = [
    'APP_ENV' => 'development',  // Changed to development for better error reporting
    'DB_HOST' => 'localhost',
    'DB_NAME' => 'u544457429_jshuk_db',
    'DB_USER' => 'u544457429_jshuk01',
    'DB_PASS' => '',  // Remove hardcoded password
    'SITE_URL' => 'http://localhost',  // Changed to localhost for development
    'GOOGLE_MAPS_API_KEY' => '',
    'STRIPE_PUBLISHABLE_KEY' => '',
    'STRIPE_SECRET_KEY' => '',
    'SMTP_HOST' => 'smtp.gmail.com',
    'SMTP_PORT' => '587',
    'SMTP_USERNAME' => '',
    'SMTP_PASSWORD' => '',
    'SMTP_ENCRYPTION' => 'tls'
];

// Only set defaults if not already set in environment
foreach ($defaults as $key => $default_value) {
    if (!getenv($key) && !isset($_ENV[$key])) {
        putenv("$key=$default_value");
        $_ENV[$key] = $default_value;
    }
}

// Enable error reporting in development
if (getenv('APP_ENV') === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../logs/php_errors.log');
} 