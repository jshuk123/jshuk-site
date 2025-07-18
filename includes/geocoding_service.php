<?php
/**
 * JShuk Geocoding Service
 * 
 * Converts business addresses to latitude and longitude coordinates
 * Uses free geocoding services (OpenStreetMap Nominatim)
 * 
 * Usage:
 * $geocoder = new GeocodingService();
 * $coordinates = $geocoder->geocodeAddress("123 Main St, London, UK");
 */

class GeocodingService {
    private $pdo;
    private $cache_table = 'geocoding_cache';
    
    public function __construct($pdo = null) {
        $this->pdo = $pdo;
        $this->createCacheTable();
    }
    
    /**
     * Create cache table for geocoding results
     */
    private function createCacheTable() {
        if (!$this->pdo) return;
        
        $sql = "CREATE TABLE IF NOT EXISTS {$this->cache_table} (
            id INT AUTO_INCREMENT PRIMARY KEY,
            address_hash VARCHAR(64) NOT NULL,
            address TEXT NOT NULL,
            latitude DECIMAL(10, 8) NOT NULL,
            longitude DECIMAL(11, 8) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_address (address_hash),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        try {
            $this->pdo->exec($sql);
        } catch (PDOException $e) {
            error_log("Failed to create geocoding cache table: " . $e->getMessage());
        }
    }
    
    /**
     * Geocode an address to get latitude and longitude
     * 
     * @param string $address The address to geocode
     * @param bool $use_cache Whether to use cached results (default: true)
     * @return array|null Array with 'lat' and 'lng' keys, or null if failed
     */
    public function geocodeAddress($address, $use_cache = true) {
        if (empty($address)) {
            return null;
        }
        
        // Clean the address
        $address = $this->cleanAddress($address);
        
        // Check cache first
        if ($use_cache && $this->pdo) {
            $cached = $this->getFromCache($address);
            if ($cached) {
                return $cached;
            }
        }
        
        // Geocode using OpenStreetMap Nominatim (free service)
        $coordinates = $this->geocodeWithNominatim($address);
        
        // Cache the result
        if ($coordinates && $use_cache && $this->pdo) {
            $this->saveToCache($address, $coordinates);
        }
        
        return $coordinates;
    }
    
    /**
     * Clean and normalize an address
     */
    private function cleanAddress($address) {
        // Remove extra whitespace
        $address = preg_replace('/\s+/', ' ', trim($address));
        
        // Add "UK" if not present and looks like a UK address
        if (!preg_match('/\b(UK|United Kingdom|England|Scotland|Wales|Northern Ireland)\b/i', $address)) {
            $address .= ', UK';
        }
        
        return $address;
    }
    
    /**
     * Get geocoding result from cache
     */
    private function getFromCache($address) {
        try {
            $address_hash = hash('sha256', $address);
            $stmt = $this->pdo->prepare("
                SELECT latitude, longitude 
                FROM {$this->cache_table} 
                WHERE address_hash = ? 
                AND created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
            ");
            $stmt->execute([$address_hash]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                return [
                    'lat' => (float) $result['latitude'],
                    'lng' => (float) $result['longitude']
                ];
            }
        } catch (PDOException $e) {
            error_log("Cache lookup failed: " . $e->getMessage());
        }
        
        return null;
    }
    
    /**
     * Save geocoding result to cache
     */
    private function saveToCache($address, $coordinates) {
        try {
            $address_hash = hash('sha256', $address);
            $stmt = $this->pdo->prepare("
                INSERT INTO {$this->cache_table} 
                (address_hash, address, latitude, longitude) 
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                latitude = VALUES(latitude),
                longitude = VALUES(longitude),
                created_at = CURRENT_TIMESTAMP
            ");
            $stmt->execute([
                $address_hash,
                $address,
                $coordinates['lat'],
                $coordinates['lng']
            ]);
        } catch (PDOException $e) {
            error_log("Failed to save to cache: " . $e->getMessage());
        }
    }
    
    /**
     * Geocode using OpenStreetMap Nominatim (free service)
     */
    private function geocodeWithNominatim($address) {
        $url = 'https://nominatim.openstreetmap.org/search';
        $params = [
            'q' => $address,
            'format' => 'json',
            'limit' => 1,
            'addressdetails' => 1,
            'countrycodes' => 'gb' // Limit to UK
        ];
        
        $full_url = $url . '?' . http_build_query($params);
        
        // Set up cURL request
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $full_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_USERAGENT => 'JShuk/1.0 (https://jshuk.com)',
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Accept-Language: en-GB,en;q=0.9'
            ]
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code !== 200 || !$response) {
            error_log("Nominatim geocoding failed for address: $address (HTTP: $http_code)");
            return null;
        }
        
        $data = json_decode($response, true);
        
        if (empty($data) || !isset($data[0]['lat']) || !isset($data[0]['lon'])) {
            error_log("No coordinates found for address: $address");
            return null;
        }
        
        return [
            'lat' => (float) $data[0]['lat'],
            'lng' => (float) $data[0]['lon']
        ];
    }
    
    /**
     * Bulk geocode multiple addresses
     */
    public function bulkGeocode($addresses, $delay = 1) {
        $results = [];
        
        foreach ($addresses as $index => $address) {
            $results[] = $this->geocodeAddress($address);
            
            // Respect rate limits (1 request per second for Nominatim)
            if ($delay > 0 && $index < count($addresses) - 1) {
                sleep($delay);
            }
        }
        
        return $results;
    }
    
    /**
     * Geocode a business and update the database
     */
    public function geocodeBusiness($business_id) {
        if (!$this->pdo) {
            throw new Exception("Database connection required for business geocoding");
        }
        
        // Get business address
        $stmt = $this->pdo->prepare("
            SELECT id, business_name, address 
            FROM businesses 
            WHERE id = ? AND status = 'active'
        ");
        $stmt->execute([$business_id]);
        $business = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$business || empty($business['address'])) {
            return false;
        }
        
        // Geocode the address
        $coordinates = $this->geocodeAddress($business['address']);
        
        if (!$coordinates) {
            return false;
        }
        
        // Update the business record
        $update_stmt = $this->pdo->prepare("
            UPDATE businesses 
            SET latitude = ?, longitude = ?, geocoded = 1 
            WHERE id = ?
        ");
        $result = $update_stmt->execute([
            $coordinates['lat'],
            $coordinates['lng'],
            $business_id
        ]);
        
        if ($result) {
            error_log("Successfully geocoded business {$business['business_name']} (ID: $business_id)");
            return true;
        } else {
            error_log("Failed to update business coordinates for ID: $business_id");
            return false;
        }
    }
    
    /**
     * Get businesses that need geocoding
     */
    public function getBusinessesNeedingGeocoding($limit = 100) {
        if (!$this->pdo) {
            return [];
        }
        
        $stmt = $this->pdo->prepare("
            SELECT id, business_name, address 
            FROM businesses 
            WHERE (geocoded = 0 OR latitude IS NULL OR longitude IS NULL)
              AND address IS NOT NULL 
              AND address != ''
              AND status = 'active'
            ORDER BY created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get geocoding statistics
     */
    public function getGeocodingStats() {
        if (!$this->pdo) {
            return [];
        }
        
        $stats = [];
        
        // Total businesses
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM businesses WHERE status = 'active'");
        $stats['total_businesses'] = $stmt->fetchColumn();
        
        // Geocoded businesses
        $stmt = $this->pdo->query("
            SELECT COUNT(*) 
            FROM businesses 
            WHERE geocoded = 1 
              AND latitude IS NOT NULL 
              AND longitude IS NOT NULL 
              AND status = 'active'
        ");
        $stats['geocoded_businesses'] = $stmt->fetchColumn();
        
        // Businesses needing geocoding
        $stmt = $this->pdo->query("
            SELECT COUNT(*) 
            FROM businesses 
            WHERE (geocoded = 0 OR latitude IS NULL OR longitude IS NULL)
              AND address IS NOT NULL 
              AND address != ''
              AND status = 'active'
        ");
        $stats['needing_geocoding'] = $stmt->fetchColumn();
        
        // Calculate percentage
        $stats['geocoding_percentage'] = $stats['total_businesses'] > 0 
            ? round(($stats['geocoded_businesses'] / $stats['total_businesses']) * 100, 1)
            : 0;
        
        return $stats;
    }
}
?> 