<?php
/**
 * Input Validation System for JShuk
 * Provides comprehensive validation and sanitization
 */

class Validator {
    private $errors = [];
    private $data = [];
    
    public function __construct($data = []) {
        $this->data = $data;
    }
    
    /**
     * Validate email address
     */
    public function email($field, $required = true) {
        $value = $this->getValue($field);
        
        if ($required && empty($value)) {
            $this->addError($field, 'Email is required');
            return false;
        }
        
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->addError($field, 'Invalid email format');
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate password
     */
    public function password($field, $min_length = 8, $require_special = true) {
        $value = $this->getValue($field);
        
        if (empty($value)) {
            $this->addError($field, 'Password is required');
            return false;
        }
        
        if (strlen($value) < $min_length) {
            $this->addError($field, "Password must be at least {$min_length} characters long");
            return false;
        }
        
        if ($require_special && !preg_match('/[!@#$%^&*(),.?":{}|<>]/', $value)) {
            $this->addError($field, 'Password must contain at least one special character');
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate required field
     */
    public function required($field, $label = null) {
        $value = $this->getValue($field);
        $label = $label ?: ucfirst(str_replace('_', ' ', $field));
        
        if (empty($value)) {
            $this->addError($field, "{$label} is required");
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate minimum length
     */
    public function minLength($field, $min_length, $label = null) {
        $value = $this->getValue($field);
        $label = $label ?: ucfirst(str_replace('_', ' ', $field));
        
        if (strlen($value) < $min_length) {
            $this->addError($field, "{$label} must be at least {$min_length} characters long");
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate maximum length
     */
    public function maxLength($field, $max_length, $label = null) {
        $value = $this->getValue($field);
        $label = $label ?: ucfirst(str_replace('_', ' ', $field));
        
        if (strlen($value) > $max_length) {
            $this->addError($field, "{$label} must be no more than {$max_length} characters long");
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate numeric value
     */
    public function numeric($field, $label = null) {
        $value = $this->getValue($field);
        $label = $label ?: ucfirst(str_replace('_', ' ', $field));
        
        if (!is_numeric($value)) {
            $this->addError($field, "{$label} must be a number");
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate integer value
     */
    public function integer($field, $label = null) {
        $value = $this->getValue($field);
        $label = $label ?: ucfirst(str_replace('_', ' ', $field));
        
        if (!filter_var($value, FILTER_VALIDATE_INT)) {
            $this->addError($field, "{$label} must be a whole number");
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate URL
     */
    public function url($field, $label = null) {
        $value = $this->getValue($field);
        $label = $label ?: ucfirst(str_replace('_', ' ', $field));
        
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
            $this->addError($field, "{$label} must be a valid URL");
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate phone number
     */
    public function phone($field, $label = null) {
        $value = $this->getValue($field);
        $label = $label ?: ucfirst(str_replace('_', ' ', $field));
        
        if (!empty($value) && !preg_match('/^[\+]?[1-9][\d]{0,15}$/', preg_replace('/[^0-9+]/', '', $value))) {
            $this->addError($field, "{$label} must be a valid phone number");
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate file upload
     */
    public function file($field, $allowed_types = [], $max_size = null, $label = null) {
        $file = $this->getValue($field);
        $label = $label ?: ucfirst(str_replace('_', ' ', $field));
        
        if (empty($file) || !isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            $this->addError($field, "{$label} is required");
            return false;
        }
        
        if (!empty($allowed_types) && !in_array($file['type'], $allowed_types)) {
            $this->addError($field, "{$label} must be one of: " . implode(', ', $allowed_types));
            return false;
        }
        
        if ($max_size && $file['size'] > $max_size) {
            $this->addError($field, "{$label} must be smaller than " . formatBytes($max_size));
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate date
     */
    public function date($field, $format = 'Y-m-d', $label = null) {
        $value = $this->getValue($field);
        $label = $label ?: ucfirst(str_replace('_', ' ', $field));
        
        if (!empty($value)) {
            $date = DateTime::createFromFormat($format, $value);
            if (!$date || $date->format($format) !== $value) {
                $this->addError($field, "{$label} must be a valid date");
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Validate against regex pattern
     */
    public function pattern($field, $pattern, $message, $label = null) {
        $value = $this->getValue($field);
        $label = $label ?: ucfirst(str_replace('_', ' ', $field));
        
        if (!empty($value) && !preg_match($pattern, $value)) {
            $this->addError($field, $message ?: "{$label} format is invalid");
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate unique value in database
     */
    public function unique($field, $table, $column, $exclude_id = null, $label = null) {
        global $pdo;
        $value = $this->getValue($field);
        $label = $label ?: ucfirst(str_replace('_', ' ', $field));
        
        if (!empty($value)) {
            $sql = "SELECT COUNT(*) FROM {$table} WHERE {$column} = ?";
            $params = [$value];
            
            if ($exclude_id) {
                $sql .= " AND id != ?";
                $params[] = $exclude_id;
            }
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $count = $stmt->fetchColumn();
            
            if ($count > 0) {
                $this->addError($field, "{$label} already exists");
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Sanitize input
     */
    public function sanitize($field, $type = 'string') {
        $value = $this->getValue($field);
        
        switch ($type) {
            case 'string':
                return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
            case 'email':
                return filter_var(trim($value), FILTER_SANITIZE_EMAIL);
            case 'url':
                return filter_var(trim($value), FILTER_SANITIZE_URL);
            case 'int':
                return (int) $value;
            case 'float':
                return (float) $value;
            case 'boolean':
                return (bool) $value;
            case 'filename':
                return preg_replace('/[^a-zA-Z0-9._-]/', '', $value);
            default:
                return trim($value);
        }
    }
    
    /**
     * Get value from data array
     */
    private function getValue($field) {
        return $this->data[$field] ?? '';
    }
    
    /**
     * Add validation error
     */
    private function addError($field, $message) {
        $this->errors[$field] = $message;
    }
    
    /**
     * Get all errors
     */
    public function getErrors() {
        return $this->errors;
    }
    
    /**
     * Check if validation passed
     */
    public function passes() {
        return empty($this->errors);
    }
    
    /**
     * Check if validation failed
     */
    public function fails() {
        return !empty($this->errors);
    }
    
    /**
     * Get first error for field
     */
    public function getError($field) {
        return $this->errors[$field] ?? null;
    }
    
    /**
     * Clear all errors
     */
    public function clearErrors() {
        $this->errors = [];
    }
}

/**
 * Helper functions for validation
 */
function validate_input($data, $rules) {
    $validator = new Validator($data);
    
    foreach ($rules as $field => $rule_list) {
        foreach ($rule_list as $rule) {
            if (is_string($rule)) {
                $validator->$rule($field);
            } elseif (is_array($rule)) {
                $method = array_shift($rule);
                call_user_func_array([$validator, $method], array_merge([$field], $rule));
            }
        }
    }
    
    return $validator;
}

function sanitize_input($data, $rules = []) {
    $sanitized = [];
    
    foreach ($data as $field => $value) {
        $type = $rules[$field] ?? 'string';
        $sanitized[$field] = (new Validator())->sanitize($field, $type);
    }
    
    return $sanitized;
}

function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
} 