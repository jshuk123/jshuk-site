<?php
// =========================
// JShuk Site Configuration
// =========================

// Load environment variables first
require_once __DIR__ . '/environment.php';

// Environment detection
$environment = getenv('APP_ENV') ?: 'production';
define('APP_ENV', $environment);

// Debug mode (automatically disabled in production)
if (!defined('APP_DEBUG')) {
    define('APP_DEBUG', $environment === 'development');
}

// Site constants
define('BASE_PATH', '/');
define('SITE_NAME', 'JShuk');
define('SITE_DESCRIPTION', 'Jewish Local Directory');
define('SITE_URL', getenv('SITE_URL') ?: 'https://jshuk.com');

// Security constants
define('CSRF_TOKEN_NAME', 'csrf_token');
define('SESSION_LIFETIME', 86400); // 24 hours
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_TIMEOUT', 900); // 15 minutes

// Google Maps API Key (should be set via environment variable)
define('GOOGLE_MAPS_API_KEY', getenv('GOOGLE_MAPS_API_KEY') ?: '');

// Stripe configuration (should be set via environment variables)
define('STRIPE_PUBLISHABLE_KEY', getenv('STRIPE_PUBLISHABLE_KEY') ?: '');
define('STRIPE_SECRET_KEY', getenv('STRIPE_SECRET_KEY') ?: '');

// Email configuration
define('SMTP_HOST', getenv('SMTP_HOST') ?: 'localhost');
define('SMTP_PORT', getenv('SMTP_PORT') ?: 587);
define('SMTP_USERNAME', getenv('SMTP_USERNAME') ?: '');
define('SMTP_PASSWORD', getenv('SMTP_PASSWORD') ?: '');
define('SMTP_ENCRYPTION', getenv('SMTP_ENCRYPTION') ?: 'tls');

// File upload settings
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('UPLOAD_PATH', __DIR__ . '/../uploads/');

// Now include security and helper files after constants are defined
if (file_exists(__DIR__ . '/security.php')) {
    require_once __DIR__ . '/security.php';
}
if (file_exists(__DIR__ . '/../includes/helpers.php')) {
    require_once __DIR__ . '/../includes/helpers.php';
}
if (file_exists(__DIR__ . '/../includes/cache.php')) {
    require_once __DIR__ . '/../includes/cache.php';
}
if (file_exists(__DIR__ . '/../includes/image_optimizer.php')) {
    require_once __DIR__ . '/../includes/image_optimizer.php';
}
if (file_exists(__DIR__ . '/../includes/validation.php')) {
    require_once __DIR__ . '/../includes/validation.php';
}

// =========================
// Database Configuration
// =========================
$db_config = [
    'host' => getenv('DB_HOST') ?: 'localhost',
    'dbname' => getenv('DB_NAME') ?: 'u544457429_jshuk_db',
    'username' => getenv('DB_USER') ?: 'u544457429_jshuk01',
    'password' => getenv('DB_PASS') ?: '',  // Must be set via environment variable
    'charset' => 'utf8mb4',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
        PDO::ATTR_PERSISTENT => false, // Disable persistent connections for security
        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true
    ]
];

// =========================
// Error Reporting
// =========================
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../logs/php_errors.log');
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../logs/php_errors.log');
}

// =========================
// Database Connection
// =========================
$pdo = null; // Initialize as null
try {
    // Only attempt database connection if password is provided
    if (!empty($db_config['password'])) {
        $dsn = "mysql:host={$db_config['host']};dbname={$db_config['dbname']};charset={$db_config['charset']}";
        $pdo = new PDO($dsn, $db_config['username'], $db_config['password'], $db_config['options']);
        
        // Set timezone
        $pdo->exec("SET time_zone = '+00:00'");
        
        if (APP_DEBUG) {
            error_log("Database connected successfully");
        }
    } else {
        if (APP_DEBUG) {
            error_log("Database password not set - running without database connection");
        }
    }
    
} catch (PDOException $e) {
    if (APP_DEBUG) {
        error_log("Database connection failed: " . $e->getMessage());
    }
    // Don't die - allow the site to work without database
    $pdo = null;
}

// =========================
// Session Configuration
// =========================
if (session_status() === PHP_SESSION_NONE) {
    // Secure session configuration
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 1);
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.use_strict_mode', 1);
    ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
    
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path' => '/',
        'domain' => '',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    
    session_start();
    
    // Regenerate session ID periodically for security
    if (!isset($_SESSION['last_regeneration'])) {
        $_SESSION['last_regeneration'] = time();
    } elseif (time() - $_SESSION['last_regeneration'] > 1800) { // 30 minutes
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
}

// =========================
// CSRF Protection
// =========================
if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
    $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
}

// =========================
// CSRF Functions
// =========================
function generateCsrfToken() {
    return $_SESSION[CSRF_TOKEN_NAME];
}

function validateCsrfToken($token) {
    return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

// =========================
// Rate Limiting
// =========================
function checkRateLimit($key, $max_attempts = 10, $time_window = 3600) {
    $current_time = time();
    $attempts_key = "rate_limit_{$key}";
    
    if (!isset($_SESSION[$attempts_key])) {
        $_SESSION[$attempts_key] = ['count' => 0, 'first_attempt' => $current_time];
    }
    
    $attempts = &$_SESSION[$attempts_key];
    
    // Reset if time window has passed
    if ($current_time - $attempts['first_attempt'] > $time_window) {
        $attempts = ['count' => 0, 'first_attempt' => $current_time];
    }
    
    if ($attempts['count'] >= $max_attempts) {
        return false;
    }
    
    $attempts['count']++;
    return true;
} 