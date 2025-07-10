<?php
/**
 * Database Connection Handler
 * This file provides a secure and robust database connection
 */

// Prevent direct access
if (!defined('APP_DEBUG')) {
    die('Direct access not allowed');
}

class Database {
    private static $instance = null;
    private $pdo;
    private $connected = false;
    
    private function __construct() {
        $this->connect();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function connect() {
        try {
            // Get database configuration from environment variables
            $host = getenv('DB_HOST') ?: 'localhost';
            $dbname = getenv('DB_NAME') ?: 'u544457429_jshuk_db';
            $username = getenv('DB_USER') ?: 'u544457429_jshuk01';
            $password = getenv('DB_PASS') ?: 'Jshuk613!';  // Default password if env var not set
            $charset = 'utf8mb4';
            
            // Validate required parameters
            if (empty($dbname) || empty($username)) {
                throw new Exception('Database configuration incomplete');
            }
            
            $dsn = "mysql:host={$host};dbname={$dbname};charset={$charset}";
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
                PDO::ATTR_PERSISTENT => false, // Disable persistent connections for security
                PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true
            ];
            
            $this->pdo = new PDO($dsn, $username, $password, $options);
            
            // Set timezone
            $this->pdo->exec("SET time_zone = '+00:00'");
            
            // Test connection
            $this->pdo->query('SELECT 1');
            
            $this->connected = true;
            
            if (APP_DEBUG) {
                error_log("Database connection established successfully");
            }
            
        } catch (PDOException $e) {
            $this->connected = false;
            $error_message = "Database connection failed: " . $e->getMessage();
            
            if (APP_DEBUG) {
                error_log($error_message);
                throw new Exception($error_message);
            } else {
                error_log($error_message);
                throw new Exception("Database connection failed. Please try again later.");
            }
        } catch (Exception $e) {
            $this->connected = false;
            error_log("Database configuration error: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function getConnection() {
        if (!$this->connected) {
            $this->connect();
        }
        return $this->pdo;
    }
    
    public function isConnected() {
        return $this->connected;
    }
    
    public function testConnection() {
        try {
            $this->pdo->query('SELECT 1');
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    public function closeConnection() {
        $this->pdo = null;
        $this->connected = false;
    }
    
    // Prevent cloning
    private function __clone() {}
    
    // Prevent unserialization
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

// Initialize database connection
try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    if (APP_DEBUG && $db->isConnected()) {
        // echo "✅ Database connection established successfully.";
    }
    
} catch (Exception $e) {
    if (APP_DEBUG) {
        echo "❌ " . $e->getMessage();
    } else {
        echo "❌ Database connection failed. Please try again later.";
    }
    exit();
}

// Ensure $pdo is always available globally
if (!isset($pdo)) {
    try {
        $host = getenv('DB_HOST') ?: 'localhost';
        $dbname = getenv('DB_NAME') ?: 'u544457429_jshuk_db';
        $username = getenv('DB_USER') ?: 'u544457429_jshuk01';
        $password = getenv('DB_PASS') ?: 'Jshuk613!';
        
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        if (APP_DEBUG) {
            error_log("Fallback database connection established");
        }
    } catch (PDOException $e) {
        if (APP_DEBUG) {
            error_log("Fallback database connection failed: " . $e->getMessage());
        }
    }
}
?>
