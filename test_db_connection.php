<?php
echo "<!DOCTYPE html>";
echo "<html><head><title>Database Connection Test</title>";
echo "<style>";
echo "body{font-family:Arial,sans-serif;margin:20px;background:#f5f5f5;}";
echo ".section{background:white;margin:10px 0;padding:15px;border-radius:5px;}";
echo ".success{color:green;} .error{color:red;} .info{color:blue;}";
echo "</style>";
echo "</head><body>";

echo "<h2>🔍 Database Connection Test</h2>";

// Test 1: Check if config file exists
echo "<div class='section'>";
echo "<h3>Test 1: Configuration Files</h3>";
if (file_exists('config/config.php')) {
    echo "<p class='success'>✅ config/config.php exists</p>";
} else {
    echo "<p class='error'>❌ config/config.php missing</p>";
}

if (file_exists('config/environment.php')) {
    echo "<p class='success'>✅ config/environment.php exists</p>";
} else {
    echo "<p class='error'>❌ config/environment.php missing</p>";
}
echo "</div>";

// Test 2: Try to load config
echo "<div class='section'>";
echo "<h3>Test 2: Loading Configuration</h3>";
try {
    require_once 'config/config.php';
    echo "<p class='success'>✅ Configuration loaded successfully</p>";
    
    // Check environment variables
    echo "<h4>Environment Variables:</h4>";
    $env_vars = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS'];
    foreach ($env_vars as $var) {
        $value = getenv($var);
        if ($value) {
            echo "<p class='success'>✅ {$var}: " . substr($value, 0, 3) . "***</p>";
        } else {
            echo "<p class='error'>❌ {$var}: NOT SET</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Configuration error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
echo "</div>";

// Test 3: Database connection
echo "<div class='section'>";
echo "<h3>Test 3: Database Connection</h3>";
if (isset($pdo) && $pdo) {
    echo "<p class='success'>✅ Database connection available</p>";
    
    try {
        $stmt = $pdo->query('SELECT 1 as test');
        $result = $stmt->fetch();
        if ($result) {
            echo "<p class='success'>✅ Database query successful</p>";
        } else {
            echo "<p class='error'>❌ Database query failed</p>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>❌ Database query error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
} else {
    echo "<p class='error'>❌ Database connection not available</p>";
    echo "<p class='info'>This could be because:</p>";
    echo "<ul>";
    echo "<li>Database password is not set in environment variables</li>";
    echo "<li>Database server is down</li>";
    echo "<li>Incorrect database credentials</li>";
    echo "</ul>";
}
echo "</div>";

// Test 4: Check if tables exist
echo "<div class='section'>";
echo "<h3>Test 4: Database Tables</h3>";
if (isset($pdo) && $pdo) {
    $tables = ['classifieds', 'classifieds_categories', 'users'];
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE '{$table}'");
            if ($stmt->rowCount() > 0) {
                echo "<p class='success'>✅ Table '{$table}' exists</p>";
            } else {
                echo "<p class='error'>❌ Table '{$table}' missing</p>";
            }
        } catch (Exception $e) {
            echo "<p class='error'>❌ Error checking table '{$table}': " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
} else {
    echo "<p class='error'>❌ Cannot check tables - no database connection</p>";
}
echo "</div>";

echo "<div class='section'>";
echo "<h3>🔧 Next Steps</h3>";
echo "<p>If you see database connection errors:</p>";
echo "<ol>";
echo "<li>Check your hosting control panel for database credentials</li>";
echo "<li>Verify environment variables are set correctly</li>";
echo "<li>Contact your hosting provider if database server is down</li>";
echo "</ol>";
echo "<p><a href='verify_fix.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🔄 Try Verification Again</a></p>";
echo "</div>";

echo "</body></html>";
?> 