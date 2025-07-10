<?php
/**
 * Helper Functions for JShuk
 * Provides utility functions for common operations
 */

// Prevent direct access
if (!defined('APP_DEBUG')) {
    die('Direct access not allowed');
}

/**
 * Get the appropriate icon class for a business category
 */
function getCategoryIcon($category_name) {
    $icons = [
        'Restaurant' => 'fa-utensils',
        'Catering' => 'fa-birthday-cake',
        'Retail' => 'fa-shopping-bag',
        'Education' => 'fa-graduation-cap',
        'Healthcare' => 'fa-heartbeat',
        'Professional Services' => 'fa-briefcase',
        'Real Estate' => 'fa-home',
        'Events' => 'fa-calendar-alt',
        'Travel' => 'fa-plane',
        'Technology' => 'fa-laptop',
        'Automotive' => 'fa-car',
        'Beauty & Wellness' => 'fa-spa',
        'Legal Services' => 'fa-balance-scale',
        'Financial Services' => 'fa-money-bill-wave',
        'Construction' => 'fa-hammer',
        'Entertainment' => 'fa-film',
        'Sports & Recreation' => 'fa-futbol',
        'Religious Services' => 'fa-synagogue',
        'Charity' => 'fa-hands-helping',
        'Other' => 'fa-store'
    ];

    return $icons[$category_name] ?? 'fa-store';
}

/**
 * Get a description for a business category
 */
function getCategoryDescription($category_name) {
    $descriptions = [
        'Home Services' => 'Find trusted plumbers, electricians, handymen, and HVAC experts for all your home needs.',
        'Health & Beauty' => 'Discover doctors, therapists, gyms, and wellness experts who cater to your lifestyle.',
        'Food & Beverages' => 'Explore the best kosher restaurants, caterers, and grocery stores in your area.',
        'Legal & Financial Services' => 'Connect with lawyers, accountants, and financial advisors you can trust.',
        'Education & Training' => 'Find tutors for GCSE, A level, and bar/bat mitzvah prep for all ages.',
        'Restaurant' => 'Discover delicious kosher dining options in your community.',
        'Catering' => 'Find professional caterers for your special events and celebrations.',
        'Retail' => 'Shop at local Jewish-owned retail stores and boutiques.',
        'Education' => 'Access quality educational services and tutoring programs.',
        'Healthcare' => 'Connect with healthcare providers who understand your needs.',
        'Professional Services' => 'Find trusted professionals for your business and personal needs.',
        'Real Estate' => 'Work with real estate professionals who know your community.',
        'Events' => 'Plan your special occasions with local event professionals.',
        'Travel' => 'Discover travel services tailored to your preferences.',
        'Technology' => 'Get tech support and services from local professionals.',
        'Automotive' => 'Find reliable automotive services in your area.',
        'Beauty & Wellness' => 'Pamper yourself with beauty and wellness services.',
        'Legal Services' => 'Get legal advice and representation you can trust.',
        'Financial Services' => 'Manage your finances with trusted advisors.',
        'Construction' => 'Build and renovate with reliable contractors.',
        'Entertainment' => 'Find entertainment options for your events.',
        'Sports & Recreation' => 'Stay active with local sports and recreation facilities.',
        'Religious Services' => 'Connect with religious services and organizations.',
        'Charity' => 'Support and get involved with charitable organizations.',
        'Other' => 'Explore other local services and businesses.'
    ];

    return $descriptions[$category_name] ?? 'Browse businesses in this category.';
}

/**
 * Format a date in a user-friendly way
 */
function formatDate($date, $format = 'F j, Y') {
    if (!$date) return '';
    
    try {
        $timestamp = is_numeric($date) ? $date : strtotime($date);
        if ($timestamp === false) return '';
        
        return date($format, $timestamp);
    } catch (Exception $e) {
        return '';
    }
}

/**
 * Format a date with relative time (e.g., "2 hours ago")
 */
function formatRelativeDate($date) {
    if (!$date) return '';
    
    $timestamp = is_numeric($date) ? $date : strtotime($date);
    if ($timestamp === false) return '';
    
    $now = time();
    $diff = $now - $timestamp;
    
    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 2592000) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return formatDate($date);
    }
}

/**
 * Truncate text to a specified length with proper UTF-8 support
 */
function truncateText($text, $length = 100, $append = '...') {
    if (empty($text)) return '';
    
    if (mb_strlen($text, 'UTF-8') <= $length) {
        return $text;
    }
    
    return mb_substr($text, 0, $length, 'UTF-8') . $append;
}

/**
 * Generate a slug from a string
 */
function generateSlug($string) {
    if (empty($string)) return '';
    
    // Convert to lowercase and remove special characters
    $string = mb_strtolower($string, 'UTF-8');
    $string = preg_replace('/[^a-z0-9\s-]/', '', $string);
    $string = preg_replace('/[\s-]+/', ' ', $string);
    $string = trim($string);
    $string = str_replace(' ', '-', $string);
    
    return $string;
}

/**
 * Sanitize output for HTML
 */
function h($string) {
    if (is_array($string)) {
        return array_map('h', $string);
    }
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitize input data
 */
function sanitizeInput($input) {
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    return trim(htmlspecialchars($input, ENT_QUOTES, 'UTF-8'));
}

/**
 * Validate and sanitize email address
 */
function validateEmail($email) {
    if (empty($email)) return false;
    
    $email = filter_var(trim($email), FILTER_VALIDATE_EMAIL);
    return $email !== false;
}

/**
 * Validate and sanitize URL
 */
function validateUrl($url) {
    if (empty($url)) return false;
    
    $url = filter_var(trim($url), FILTER_VALIDATE_URL);
    return $url !== false;
}

/**
 * Validate phone number format
 */
function validatePhone($phone) {
    if (empty($phone)) return false;
    
    // Remove all non-numeric characters
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // Check if it's a valid length (7-15 digits)
    return strlen($phone) >= 7 && strlen($phone) <= 15;
}

/**
 * Format phone number for display
 */
function formatPhoneNumber($phone) {
    if (empty($phone)) return '';
    
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    if (strlen($phone) === 10) {
        return '(' . substr($phone, 0, 3) . ') ' . substr($phone, 3, 3) . '-' . substr($phone, 6);
    } elseif (strlen($phone) === 11 && substr($phone, 0, 1) === '1') {
        return '+1 (' . substr($phone, 1, 3) . ') ' . substr($phone, 4, 3) . '-' . substr($phone, 7);
    }
    
    return $phone;
}

/**
 * Generate a secure random token
 */
function generateToken($length = 32) {
    try {
        return bin2hex(random_bytes($length));
    } catch (Exception $e) {
        // Fallback for older PHP versions
        return bin2hex(openssl_random_pseudo_bytes($length));
    }
}

/**
 * Generate a secure password hash
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_ARGON2ID, [
        'memory_cost' => 65536,
        'time_cost' => 4,
        'threads' => 3
    ]);
}

/**
 * Verify a password against its hash
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Check if password needs rehashing
 */
function passwordNeedsRehash($hash) {
    return password_needs_rehash($hash, PASSWORD_ARGON2ID, [
        'memory_cost' => 65536,
        'time_cost' => 4,
        'threads' => 3
    ]);
}

/**
 * Get the current page URL
 */
function getCurrentUrl() {
    return 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

/**
 * Get the base URL
 */
function getBaseUrl() {
    return 'https://' . $_SERVER['HTTP_HOST'];
}

/**
 * Check if a request is AJAX
 */
function isAjaxRequest() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Redirect to a URL
 */
function redirect($url, $status_code = 302) {
    if (headers_sent()) {
        echo '<script>window.location.href = "' . h($url) . '";</script>';
        echo '<noscript><meta http-equiv="refresh" content="0;url=' . h($url) . '"></noscript>';
    } else {
        http_response_code($status_code);
        header("Location: $url");
    }
    exit;
}

/**
 * Get client IP address
 */
function getClientIp() {
    $ip_keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
    
    foreach ($ip_keys as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = $_SERVER[$key];
            if ($key === 'HTTP_X_FORWARDED_FOR') {
                // Handle multiple IPs in X-Forwarded-For header
                $ip = trim(explode(',', $ip)[0]);
            }
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/**
 * Check if a string contains any profanity
 */
function containsProfanity($string) {
    $profanity_list = [
        // Add your profanity list here
        // This should be loaded from a configuration file in production
    ];
    
    if (empty($profanity_list)) return false;
    
    $string = mb_strtolower($string, 'UTF-8');
    
    foreach ($profanity_list as $word) {
        if (mb_strpos($string, mb_strtolower($word, 'UTF-8')) !== false) {
            return true;
        }
    }
    return false;
}

/**
 * Validate file upload
 */
function validateFileUpload($file, $allowed_types = null, $max_size = null) {
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['valid' => false, 'error' => 'Invalid file parameter'];
    }
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'File too large (server limit)',
            UPLOAD_ERR_FORM_SIZE => 'File too large (form limit)',
            UPLOAD_ERR_PARTIAL => 'File upload incomplete',
            UPLOAD_ERR_NO_FILE => 'No file uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
        ];
        return ['valid' => false, 'error' => $errors[$file['error']] ?? 'Unknown upload error'];
    }
    
    if (!is_uploaded_file($file['tmp_name'])) {
        return ['valid' => false, 'error' => 'File not uploaded via HTTP POST'];
    }
    
    // Check file size
    $max_size = $max_size ?: MAX_FILE_SIZE;
    if ($file['size'] > $max_size) {
        return ['valid' => false, 'error' => 'File too large'];
    }
    
    // Check file type
    $allowed_types = $allowed_types ?: ALLOWED_IMAGE_TYPES;
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime_type, $allowed_types)) {
        return ['valid' => false, 'error' => 'Invalid file type'];
    }
    
    return ['valid' => true, 'mime_type' => $mime_type];
}

/**
 * Generate a unique filename
 */
function generateUniqueFilename($original_name, $extension = null) {
    if (!$extension) {
        $extension = pathinfo($original_name, PATHINFO_EXTENSION);
    }
    
    $filename = uniqid() . '_' . time();
    if ($extension) {
        $filename .= '.' . $extension;
    }
    
    return $filename;
}

/**
 * Log an error message
 */
function logError($message, $context = []) {
    $log_entry = date('Y-m-d H:i:s') . ' - ' . $message;
    if (!empty($context)) {
        $log_entry .= ' - Context: ' . json_encode($context);
    }
    error_log($log_entry);
}

/**
 * Log user activity
 */
function logActivity($user_id, $action, $details = []) {
    try {
        global $pdo;
        
        // Check if $pdo is available
        if (!isset($pdo) || !$pdo) {
            // Log to error log if database is not available
            logError('Cannot log user activity: Database connection not available');
            return;
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO user_activity_log (user_id, action, details, ip_address, user_agent, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $user_id,
            $action,
            json_encode($details),
            getClientIp(),
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
        
    } catch (Exception $e) {
        logError('Failed to log user activity: ' . $e->getMessage());
    }
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if user is admin
 */
function isAdmin() {
    return isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Get current user ID
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Format currency
 */
function formatCurrency($amount, $currency = 'USD') {
    return '$' . number_format($amount, 2);
}

/**
 * Calculate percentage
 */
function calculatePercentage($part, $total) {
    if ($total == 0) return 0;
    return round(($part / $total) * 100, 2);
}

/**
 * Get file extension from MIME type
 */
function getExtensionFromMimeType($mime_type) {
    $mime_map = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
        'application/pdf' => 'pdf',
        'text/plain' => 'txt'
    ];
    
    return $mime_map[$mime_type] ?? '';
}

/**
 * Clean and validate search query
 */
function cleanSearchQuery($query) {
    $query = trim($query);
    $query = preg_replace('/[^\p{L}\p{N}\s\-_]/u', '', $query);
    $query = preg_replace('/\s+/', ' ', $query);
    
    return $query;
}

/**
 * Build pagination links
 */
function buildPagination($current_page, $total_pages, $base_url) {
    $pagination = [];
    
    // Previous page
    if ($current_page > 1) {
        $pagination['prev'] = $base_url . '?page=' . ($current_page - 1);
    }
    
    // Next page
    if ($current_page < $total_pages) {
        $pagination['next'] = $base_url . '?page=' . ($current_page + 1);
    }
    
    // Page numbers
    $start = max(1, $current_page - 2);
    $end = min($total_pages, $current_page + 2);
    
    for ($i = $start; $i <= $end; $i++) {
        $pagination['pages'][] = [
            'number' => $i,
            'url' => $base_url . '?page=' . $i,
            'current' => $i === $current_page
        ];
    }
    
    return $pagination;
}

/**
 * Security helper functions that use the Security class
 */

function requireAuth($redirect_url = '/login.php') {
    global $security;
    $security->requireAuth($redirect_url);
}

function requireAdmin($redirect_url = '/admin/login.php') {
    global $security;
    $security->requireAdmin($redirect_url);
}

function requirePermission($permission, $redirect_url = '/403.php') {
    global $security;
    $security->requirePermission($permission, $redirect_url);
}

function hasPermission($permission, $user_id = null) {
    global $security;
    return $security->hasPermission($permission, $user_id);
}

function canEditBusiness($business_id) {
    global $security;
    return $security->canEditBusiness($business_id);
}

function canDeleteBusiness($business_id) {
    global $security;
    return $security->canDeleteBusiness($business_id);
}

function canEditTestimonial($testimonial_id) {
    global $security;
    return $security->canEditTestimonial($testimonial_id);
}

function canDeleteTestimonial($testimonial_id) {
    global $security;
    return $security->canDeleteTestimonial($testimonial_id);
}

/**
 * Render a business card HTML
 */
function renderBusinessCard($biz) {
    global $pdo; // Make sure $pdo is available

    // Fetch main image for this business
    $stmt = $pdo->prepare("SELECT file_path FROM business_images WHERE business_id = ? AND sort_order = 0 LIMIT 1");
    $stmt->execute([$biz['id']]);
    $image = $stmt->fetchColumn();

    // Use jshuk-logo.png as the placeholder if no image found
    $img_src = $image ? $image : '/images/jshuk-logo.png';

    // Use database icon or fallback to default
    $icon = $biz['icon'] ?? 'fa-store';
    $name = htmlspecialchars($biz['business_name']);
    $desc = htmlspecialchars(mb_strimwidth($biz['description'], 0, 120, '...'));
    $cat = htmlspecialchars($biz['category_name']);
    $id = urlencode($biz['id']);
    
    // Get subscription tier for styling and badges
    $subscription_tier = $biz['subscription_tier'] ?? 'basic';
    $card_class = 'business-card business-card-link ' . getPremiumCssClasses($subscription_tier);
    
    // Generate subscription badge HTML
    $subscription_badge = '';
    if ($subscription_tier !== 'basic') {
        $subscription_badge = renderSubscriptionBadge($subscription_tier, false);
    }
    
    // Generate featured ribbon for premium tiers
    $featured_ribbon = '';
    if (($biz['is_featured'] ?? false) && $subscription_tier !== 'basic') {
        $featured_ribbon = renderFeaturedRibbon($subscription_tier, true);
    }
    
    // Generate elite ribbon for Premium+
    $elite_ribbon = renderEliteRibbon($subscription_tier);

    return <<<HTML
    <a href="/business.php?id={$id}" class="{$card_class}" aria-label="View details for {$name}">
        <div class="business-image">
            <img src="{$img_src}" alt="{$name} Logo" loading="lazy" onerror="this.onerror=null;this.src='/images/jshuk-logo.png';">
            
            <!-- Subscription Tier Badge -->
            {$subscription_badge}
            
            <!-- Featured Ribbon for Premium Tiers -->
            {$featured_ribbon}
            
            <!-- Elite Ribbon for Premium+ -->
            {$elite_ribbon}
        </div>
        <div class="business-content">
            <div class="business-header">
                <h3 class="business-title">
                    {$name}
                    {$subscription_badge}
                </h3>
                <div class="business-category">
                    <i class="fas fa-tag"></i>
                    <span>{$cat}</span>
                </div>
            </div>
            <p class="business-description truncate">{$desc}</p>
        </div>
    </a>
    HTML;
}