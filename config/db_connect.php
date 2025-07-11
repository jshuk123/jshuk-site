<?php
/**
 * Database Connection Handler
 * This file provides a secure and robust database connection
 */

// Prevent direct access
if (!defined('APP_DEBUG')) {
    define('APP_DEBUG', getenv('APP_ENV') === 'development');
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
            $host = getenv('DB_HOST');
            $dbname = getenv('DB_NAME');
            $username = getenv('DB_USER');
            $password = getenv('DB_PASS');
            $charset = 'utf8mb4';
            
            // Validate required parameters
            if (empty($host) || empty($dbname) || empty($username) || empty($password)) {
                throw new Exception('Database configuration incomplete. Please check environment variables.');
            }
            
            $dsn = "mysql:host={$host};dbname={$dbname};charset={$charset}";
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
                PDO::ATTR_PERSISTENT => false,
                PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true
            ];
            
            $this->pdo = new PDO($dsn, $username, $password, $options);
            
            // Set timezone
            $this->pdo->exec("SET time_zone = '+00:00'");
            
            // Test connection
            $this->pdo->query('SELECT 1');
            
            $this->connected = true;
            
            if (APP_DEBUG) {
                error_log("Database connected successfully");
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
    
    private function __clone() {}
    
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

// Initialize database connection
try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    if (APP_DEBUG) {
        error_log("Database connection established");
    }
    
} catch (Exception $e) {
    error_log("Database connection error: " . $e->getMessage());
    if (APP_DEBUG) {
        throw $e;
    } else {
        die("Database connection failed. Please try again later.");
    }
}
?>
