<?php
/**
 * Security Middleware for JShuk
 * Handles authentication, authorization, and security checks
 */

// Prevent direct access
if (!defined('APP_DEBUG')) {
    die('Direct access not allowed');
}

/**
 * Security class to handle authentication and authorization
 */
class Security {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Check if user is banned
     */
    public function checkBanStatus($user_id = null) {
        if (!$user_id) {
            $user_id = getCurrentUserId();
        }
        
        if (!$user_id) return false;
        
        try {
            $stmt = $this->pdo->prepare("SELECT is_banned FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
            
            return $user && $user['is_banned'];
        } catch (Exception $e) {
            logError('Error checking ban status: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Require authentication for a page
     */
    public function requireAuth($redirect_url = '/login.php') {
        if (!isLoggedIn()) {
            $_SESSION['redirect_after_login'] = getCurrentUrl();
            redirect($redirect_url);
        }
        
        // Check if user is banned
        if ($this->checkBanStatus()) {
            redirect('/banned.php');
        }
    }
    
    /**
     * Require admin privileges
     */
    public function requireAdmin($redirect_url = '/admin/login.php') {
        $this->requireAuth($redirect_url);
        
        if (!isAdmin()) {
            logError('Unauthorized admin access attempt', [
                'user_id' => getCurrentUserId(),
                'ip' => getClientIp(),
                'url' => getCurrentUrl()
            ]);
            redirect('/403.php');
        }
    }
    
    /**
     * Validate CSRF token
     */
    public function validateCsrf($token) {
        if (!validateCsrfToken($token)) {
            logError('CSRF token validation failed', [
                'ip' => getClientIp(),
                'url' => getCurrentUrl(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]);
            return false;
        }
        return true;
    }
    
    /**
     * Check rate limiting
     */
    public function checkRateLimit($action, $max_attempts = 10, $time_window = 3600) {
        return checkRateLimit($action, $max_attempts, $time_window);
    }
    
    /**
     * Validate and sanitize input
     */
    public function sanitizeInput($data) {
        return sanitizeInput($data);
    }
    
    /**
     * Validate email
     */
    public function validateEmail($email) {
        return validateEmail($email);
    }
    
    /**
     * Validate URL
     */
    public function validateUrl($url) {
        return validateUrl($url);
    }
    
    /**
     * Validate phone number
     */
    public function validatePhone($phone) {
        return validatePhone($phone);
    }
    
    /**
     * Check if user can edit a business
     */
    public function canEditBusiness($business_id) {
        if (!isLoggedIn()) return false;
        
        try {
            $stmt = $this->pdo->prepare("
                SELECT user_id FROM businesses 
                WHERE id = ? AND status != 'deleted'
            ");
            $stmt->execute([$business_id]);
            $business = $stmt->fetch();
            
            return $business && ($business['user_id'] == getCurrentUserId() || isAdmin());
        } catch (Exception $e) {
            logError('Error checking business edit permissions: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if user can delete a business
     */
    public function canDeleteBusiness($business_id) {
        if (!isLoggedIn()) return false;
        
        try {
            $stmt = $this->pdo->prepare("
                SELECT user_id FROM businesses 
                WHERE id = ? AND status != 'deleted'
            ");
            $stmt->execute([$business_id]);
            $business = $stmt->fetch();
            
            return $business && ($business['user_id'] == getCurrentUserId() || isAdmin());
        } catch (Exception $e) {
            logError('Error checking business delete permissions: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if user can edit a testimonial
     */
    public function canEditTestimonial($testimonial_id) {
        if (!isLoggedIn()) return false;
        
        try {
            $stmt = $this->pdo->prepare("
                SELECT user_id FROM testimonials 
                WHERE id = ? AND status != 'deleted'
            ");
            $stmt->execute([$testimonial_id]);
            $testimonial = $stmt->fetch();
            
            return $testimonial && ($testimonial['user_id'] == getCurrentUserId() || isAdmin());
        } catch (Exception $e) {
            logError('Error checking testimonial edit permissions: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if user can delete a testimonial
     */
    public function canDeleteTestimonial($testimonial_id) {
        if (!isLoggedIn()) return false;
        
        try {
            $stmt = $this->pdo->prepare("
                SELECT user_id FROM testimonials 
                WHERE id = ? AND status != 'deleted'
            ");
            $stmt->execute([$testimonial_id]);
            $testimonial = $stmt->fetch();
            
            return $testimonial && ($testimonial['user_id'] == getCurrentUserId() || isAdmin());
        } catch (Exception $e) {
            logError('Error checking testimonial delete permissions: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Log security event
     */
    public function logSecurityEvent($event, $details = []) {
        $details['ip'] = getClientIp();
        $details['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $details['url'] = getCurrentUrl();
        $details['user_id'] = getCurrentUserId();
        
        logError("Security event: $event", $details);
    }
    
    /**
     * Validate file upload
     */
    public function validateFileUpload($file, $allowed_types = null, $max_size = null) {
        return validateFileUpload($file, $allowed_types, $max_size);
    }
    
    /**
     * Generate secure filename
     */
    public function generateSecureFilename($original_name) {
        return generateUniqueFilename($original_name);
    }
    
    /**
     * Check request origin
     */
    public function checkOrigin() {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? null;
        $allowed_origins = [
            'https://jshuk.com',
            'https://www.jshuk.com'
        ];
        
        if (APP_DEBUG) {
            $allowed_origins[] = 'http://localhost';
            $allowed_origins[] = 'http://127.0.0.1';
        }
        
        return $origin && in_array($origin, $allowed_origins);
    }
    
    /**
     * Validate search query
     */
    public function validateSearchQuery($query) {
        return cleanSearchQuery($query);
    }
    
    /**
     * Check content for profanity
     */
    public function checkProfanity($content) {
        return containsProfanity($content);
    }
    
    /**
     * Get user permissions
     */
    public function getUserPermissions($user_id = null) {
        if (!$user_id && !isLoggedIn()) return [];
        
        $user_id = $user_id ?: getCurrentUserId();
        
        try {
            $stmt = $this->pdo->prepare("
                SELECT p.name 
                FROM user_permissions up 
                JOIN permissions p ON up.permission_id = p.id 
                WHERE up.user_id = ? AND up.is_active = 1
            ");
            $stmt->execute([$user_id]);
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (Exception $e) {
            logError('Error fetching user permissions: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Check if user has specific permission
     */
    public function hasPermission($permission, $user_id = null) {
        if (isAdmin()) return true;
        
        $permissions = $this->getUserPermissions($user_id);
        return in_array($permission, $permissions);
    }
    
    /**
     * Require specific permission
     */
    public function requirePermission($permission, $redirect_url = '/403.php') {
        if (!$this->hasPermission($permission)) {
            $this->logSecurityEvent('Permission denied', [
                'permission' => $permission,
                'user_id' => getCurrentUserId()
            ]);
            redirect($redirect_url);
        }
    }
}

// Initialize security instance
$security = new Security($pdo);

/**
 * Middleware functions for easy use
 */

 