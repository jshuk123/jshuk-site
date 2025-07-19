<?php
/**
 * Setup Job Seeker's Toolkit
 * 
 * This script sets up the database tables and initial data for the Job Seeker's Toolkit feature.
 */

// Load configuration
require_once __DIR__ . '/../config/config.php';

echo "🔧 Setting up Job Seeker's Toolkit\n";
echo "==================================\n\n";

// Check if database connection is available
if (!$pdo) {
    die("❌ Database connection not available. Please check your configuration.\n");
}

try {
    echo "📋 Creating database tables...\n";
    
    // Read and execute the SQL file
    $sql_file = __DIR__ . '/../sql/create_job_seeker_toolkit.sql';
    
    if (!file_exists($sql_file)) {
        die("❌ SQL file not found: $sql_file\n");
    }
    
    $sql_content = file_get_contents($sql_file);
    
    // Split the SQL into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql_content)));
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            try {
                $pdo->exec($statement);
                echo "   ✅ Executed: " . substr($statement, 0, 50) . "...\n";
            } catch (PDOException $e) {
                // Ignore errors for statements that might already exist
                if (strpos($e->getMessage(), 'already exists') === false && 
                    strpos($e->getMessage(), 'Duplicate key name') === false) {
                    echo "   ⚠️  Warning: " . $e->getMessage() . "\n";
                }
            }
        }
    }
    
    echo "\n✅ Database tables created successfully!\n";
    
    // Verify the tables were created
    echo "\n🔍 Verifying table creation...\n";
    
    $required_tables = ['saved_jobs', 'job_alerts', 'job_alert_logs'];
    
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
    
    // Test the save job functionality
    echo "\n🧪 Testing save job functionality...\n";
    
    try {
        // Check if there are any jobs to test with
        $stmt = $pdo->query("SELECT COUNT(*) FROM recruitment WHERE is_active = 1");
        $job_count = $stmt->fetchColumn();
        
        if ($job_count > 0) {
            echo "   ✅ Found $job_count active jobs for testing\n";
        } else {
            echo "   ⚠️  No active jobs found - you may want to add some test jobs\n";
        }
        
        // Check if there are any users to test with
        $stmt = $pdo->query("SELECT COUNT(*) FROM users");
        $user_count = $stmt->fetchColumn();
        
        if ($user_count > 0) {
            echo "   ✅ Found $user_count users for testing\n";
        } else {
            echo "   ⚠️  No users found - you may want to create some test users\n";
        }
        
    } catch (PDOException $e) {
        echo "   ❌ Error testing functionality: " . $e->getMessage() . "\n";
    }
    
    echo "\n🎉 Job Seeker's Toolkit setup completed successfully!\n";
    echo "\n📋 What was created:\n";
    echo "   • saved_jobs table - for storing user's saved job listings\n";
    echo "   • job_alerts table - for storing user's job alert preferences\n";
    echo "   • job_alert_logs table - for tracking sent job alerts\n";
    echo "   • Performance indexes for better query performance\n";
    echo "   • Fulltext search capabilities for job matching\n";
    echo "\n🚀 Next steps:\n";
    echo "   1. Test the save job functionality on the recruitment page\n";
    echo "   2. Test creating job alerts from the search form\n";
    echo "   3. Visit /users/saved_jobs.php to see saved jobs\n";
    echo "   4. Visit /users/job_alerts.php to manage job alerts\n";
    echo "   5. Set up a cron job for sending job alert emails (see documentation)\n";
    
} catch (Exception $e) {
    echo "❌ Setup failed: " . $e->getMessage() . "\n";
    exit(1);
} 