<?php
/**
 * Check Recruitment Issues - Diagnostic Script
 * 
 * This script checks for various issues that might be causing the recruitment page to fail.
 */

// Load configuration
require_once __DIR__ . '/../config/config.php';

echo "🔍 Recruitment Page Diagnostic Check\n";
echo "====================================\n\n";

// Check 1: Database Connection
echo "1. Database Connection Check:\n";
if (!$pdo) {
    echo "   ❌ Database connection not available\n";
    echo "   💡 Check your database configuration in config/config.php\n";
    exit(1);
} else {
    echo "   ✅ Database connection available\n";
}

// Check 2: Required Tables
echo "\n2. Required Tables Check:\n";
$required_tables = ['recruitment', 'job_sectors', 'businesses', 'business_images', 'users'];

foreach ($required_tables as $table) {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "   ✅ Table '$table' exists\n";
        } else {
            echo "   ❌ Table '$table' missing\n";
        }
    } catch (PDOException $e) {
        echo "   ❌ Error checking table '$table': " . $e->getMessage() . "\n";
    }
}

// Check 3: Recruitment Table Structure
echo "\n3. Recruitment Table Structure:\n";
try {
    $stmt = $pdo->query("DESCRIBE recruitment");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $required_columns = [
        'id', 'user_id', 'business_id', 'job_title', 'job_description', 
        'job_location', 'job_type', 'sector_id', 'is_active', 'is_featured',
        'created_at', 'updated_at'
    ];
    
    $existing_columns = array_column($columns, 'Field');
    
    foreach ($required_columns as $column) {
        if (in_array($column, $existing_columns)) {
            echo "   ✅ Column '$column' exists\n";
        } else {
            echo "   ❌ Column '$column' missing\n";
        }
    }
} catch (PDOException $e) {
    echo "   ❌ Error checking recruitment table structure: " . $e->getMessage() . "\n";
}

// Check 4: Sample Data
echo "\n4. Sample Data Check:\n";
try {
    // Check recruitment records
    $stmt = $pdo->query("SELECT COUNT(*) FROM recruitment");
    $recruitment_count = $stmt->fetchColumn();
    echo "   📊 Recruitment records: $recruitment_count\n";
    
    // Check job sectors
    $stmt = $pdo->query("SELECT COUNT(*) FROM job_sectors");
    $sectors_count = $stmt->fetchColumn();
    echo "   📊 Job sectors: $sectors_count\n";
    
    // Check businesses
    $stmt = $pdo->query("SELECT COUNT(*) FROM businesses");
    $businesses_count = $stmt->fetchColumn();
    echo "   📊 Businesses: $businesses_count\n";
    
    // Check users
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $users_count = $stmt->fetchColumn();
    echo "   📊 Users: $users_count\n";
    
} catch (PDOException $e) {
    echo "   ❌ Error checking sample data: " . $e->getMessage() . "\n";
}

// Check 5: Test the actual query from recruitment.php
echo "\n5. Test Recruitment Query:\n";
try {
    $query = "
        SELECT r.*, s.name as sector_name, b.business_name,
               bi.file_path as business_logo, u.profile_image, u.first_name, u.last_name
        FROM recruitment r
        LEFT JOIN job_sectors s ON r.sector_id = s.id
        LEFT JOIN businesses b ON r.business_id = b.id
        LEFT JOIN business_images bi ON b.id = bi.business_id AND bi.sort_order = 0
        LEFT JOIN users u ON r.user_id = u.id
        WHERE r.is_active = 1
        ORDER BY r.created_at DESC
        LIMIT 5
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute();
    $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "   ✅ Query executed successfully\n";
    echo "   📊 Found " . count($jobs) . " job records\n";
    
    if (!empty($jobs)) {
        echo "   📋 Sample job data:\n";
        $sample_job = $jobs[0];
        echo "      - ID: " . $sample_job['id'] . "\n";
        echo "      - Title: " . ($sample_job['job_title'] ?? 'N/A') . "\n";
        echo "      - Company: " . ($sample_job['business_name'] ?? 'N/A') . "\n";
        echo "      - Featured: " . ($sample_job['is_featured'] ?? 'N/A') . "\n";
    }
    
} catch (PDOException $e) {
    echo "   ❌ Query failed: " . $e->getMessage() . "\n";
    echo "   💡 This is likely the cause of the recruitment page error\n";
}

// Check 6: Environment Configuration
echo "\n6. Environment Configuration:\n";
echo "   📊 APP_ENV: " . (defined('APP_ENV') ? APP_ENV : 'Not defined') . "\n";
echo "   📊 APP_DEBUG: " . (defined('APP_DEBUG') ? (APP_DEBUG ? 'true' : 'false') : 'Not defined') . "\n";
echo "   📊 Database Host: " . (getenv('DB_HOST') ?: 'localhost') . "\n";
echo "   📊 Database Name: " . (getenv('DB_NAME') ?: 'u544457429_jshuk_db') . "\n";

echo "\n🎯 Summary:\n";
echo "If you see any ❌ errors above, those need to be fixed.\n";
echo "The most common issue is the missing 'is_featured' column in the recruitment table.\n";
echo "Run the fix_recruitment_table.php script to resolve this.\n";
?> 