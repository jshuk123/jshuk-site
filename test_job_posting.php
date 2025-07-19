<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'config/config.php';

// Enable PDO error reporting
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "<h2>Testing Job Posting Database Connection</h2>";

try {
    // Test 1: Check if recruitment table exists
    echo "<h3>Test 1: Checking recruitment table</h3>";
    $stmt = $pdo->query("SHOW TABLES LIKE 'recruitment'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Recruitment table exists<br>";
    } else {
        echo "❌ Recruitment table does not exist<br>";
        exit;
    }
    
    // Test 2: Check table structure
    echo "<h3>Test 2: Checking table structure</h3>";
    $stmt = $pdo->query("DESCRIBE recruitment");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Current columns:<br>";
    echo "<ul>";
    foreach ($columns as $column) {
        echo "<li>" . htmlspecialchars($column) . "</li>";
    }
    echo "</ul>";
    
    // Test 3: Check if required columns exist
    echo "<h3>Test 3: Checking required columns</h3>";
    $required_columns = [
        'user_id', 'job_title', 'job_description', 'requirements', 'skills', 
        'salary', 'benefits', 'job_location', 'company', 'job_type', 'sector_id',
        'contact_email', 'contact_phone', 'application_method', 'additional_info',
        'kosher_environment', 'flexible_schedule', 'community_focused', 'remote_friendly',
        'is_active', 'is_featured', 'views_count', 'applications_count'
    ];
    
    $missing_columns = [];
    foreach ($required_columns as $required_column) {
        if (in_array($required_column, $columns)) {
            echo "✅ {$required_column} - EXISTS<br>";
        } else {
            echo "❌ {$required_column} - MISSING<br>";
            $missing_columns[] = $required_column;
        }
    }
    
    // Test 4: Try to insert a test record
    echo "<h3>Test 4: Testing INSERT statement</h3>";
    
    if (empty($missing_columns)) {
        // Try the exact INSERT statement from process_job.php
        $test_sql = "
            INSERT INTO recruitment (
                user_id, job_title, job_description, requirements, skills, salary, benefits,
                job_location, company, job_type, sector_id, contact_email, contact_phone,
                application_method, additional_info, kosher_environment, flexible_schedule,
                community_focused, remote_friendly, is_active, created_at, updated_at
            )
            VALUES (
                1, 'Test Job', 'Test Description', 'Test Requirements', 'Test Skills', 'Test Salary', 'Test Benefits',
                'Test Location', 'Test Company', 'full-time', 1, 'test@example.com', '1234567890',
                'email', 'Test Additional Info', 0, 0, 0, 0, 1, NOW(), NOW()
            )
        ";
        
        try {
            $pdo->exec($test_sql);
            echo "✅ Test INSERT successful!<br>";
            
            // Clean up test record
            $pdo->exec("DELETE FROM recruitment WHERE job_title = 'Test Job'");
            echo "✅ Test record cleaned up<br>";
            
        } catch (PDOException $e) {
            echo "❌ INSERT failed: " . $e->getMessage() . "<br>";
        }
    } else {
        echo "❌ Cannot test INSERT - missing columns: " . implode(', ', $missing_columns) . "<br>";
    }
    
    // Test 5: Check job_sectors table
    echo "<h3>Test 5: Checking job_sectors table</h3>";
    $stmt = $pdo->query("SELECT COUNT(*) FROM job_sectors");
    $sector_count = $stmt->fetchColumn();
    echo "Job sectors count: {$sector_count}<br>";
    
    if ($sector_count == 0) {
        echo "⚠️ No job sectors found - this might cause issues<br>";
    } else {
        echo "✅ Job sectors table has data<br>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Database Error: " . $e->getMessage() . "</p>";
}
?> 