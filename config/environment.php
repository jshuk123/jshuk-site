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
    'APP_ENV' => 'production',
    'DB_HOST' => 'localhost',
    'DB_NAME' => 'u544457429_jshuk_db',
    'DB_USER' => 'u544457429_jshuk01',
    'DB_PASS' => 'Jshuk613!', // Use the correct password
    'SITE_URL' => 'https://jshuk.com',
    'GOOGLE_MAPS_API_KEY' => '',
    'STRIPE_PUBLISHABLE_KEY' => '',
    'STRIPE_SECRET_KEY' => '',
    'SMTP_HOST' => 'smtp.gmail.com',
    'SMTP_PORT' => '587',
    'SMTP_USERNAME' => '',
    'SMTP_PASSWORD' => '',
    'SMTP_ENCRYPTION' => 'tls'
];

foreach ($defaults as $key => $default_value) {
    if (!getenv($key)) {
        putenv("$key=$default_value");
        $_ENV[$key] = $default_value;
    }
} 