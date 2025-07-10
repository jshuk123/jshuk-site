<?php
/**
 * Cache System for JShuk
 * Provides file-based caching for improved performance
 */

class Cache {
    private $cache_dir;
    private $default_ttl;
    
    public function __construct($cache_dir = null, $default_ttl = 3600) {
        $this->cache_dir = $cache_dir ?: __DIR__ . '/../cache/';
        $this->default_ttl = $default_ttl;
        
        // Create cache directory if it doesn't exist
        if (!is_dir($this->cache_dir)) {
            mkdir($this->cache_dir, 0755, true);
        }
    }
    
    /**
     * Generate cache key from data
     */
    private function generateKey($key) {
        return md5($key) . '.cache';
    }
    
    /**
     * Get cache file path
     */
    private function getCachePath($key) {
        return $this->cache_dir . $this->generateKey($key);
    }
    
    /**
     * Set cache value
     */
    public function set($key, $value, $ttl = null) {
        $ttl = $ttl ?: $this->default_ttl;
        $data = [
            'value' => $value,
            'expires' => time() + $ttl
        ];
        
        $cache_path = $this->getCachePath($key);
        return file_put_contents($cache_path, serialize($data)) !== false;
    }
    
    /**
     * Get cache value
     */
    public function get($key) {
        $cache_path = $this->getCachePath($key);
        
        if (!file_exists($cache_path)) {
            return null;
        }
        
        $data = unserialize(file_get_contents($cache_path));
        
        if (!$data || !isset($data['expires']) || time() > $data['expires']) {
            unlink($cache_path);
            return null;
        }
        
        return $data['value'];
    }
    
    /**
     * Delete cache value
     */
    public function delete($key) {
        $cache_path = $this->getCachePath($key);
        if (file_exists($cache_path)) {
            return unlink($cache_path);
        }
        return true;
    }
    
    /**
     * Clear all cache
     */
    public function clear() {
        $files = glob($this->cache_dir . '*.cache');
        foreach ($files as $file) {
            unlink($file);
        }
        return true;
    }
    
    /**
     * Check if cache exists and is valid
     */
    public function exists($key) {
        return $this->get($key) !== null;
    }
    
    /**
     * Get or set cache value (cache-aside pattern)
     */
    public function remember($key, $callback, $ttl = null) {
        $value = $this->get($key);
        
        if ($value !== null) {
            return $value;
        }
        
        $value = $callback();
        $this->set($key, $value, $ttl);
        
        return $value;
    }
    
    /**
     * Cache database query results
     */
    public function query($key, $callback, $ttl = null) {
        return $this->remember("query:$key", $callback, $ttl);
    }
    
    /**
     * Cache page content
     */
    public function page($key, $callback, $ttl = null) {
        return $this->remember("page:$key", $callback, $ttl);
    }
}

// Initialize cache instance
$cache = new Cache();

/**
 * Helper functions for easy cache access
 */
function cache_get($key) {
    global $cache;
    return $cache->get($key);
}

function cache_set($key, $value, $ttl = null) {
    global $cache;
    return $cache->set($key, $value, $ttl);
}

function cache_delete($key) {
    global $cache;
    return $cache->delete($key);
}

function cache_remember($key, $callback, $ttl = null) {
    global $cache;
    return $cache->remember($key, $callback, $ttl);
}

function cache_query($key, $callback, $ttl = null) {
    global $cache;
    return $cache->query($key, $callback, $ttl);
} 