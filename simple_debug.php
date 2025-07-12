<?php
/**
 * Simple debug script - Basic database connectivity test
 */

echo "<h1>🔍 Simple Database Debug</h1>";
echo "<div style='font-family: monospace; background: #f8f9fa; padding: 20px; border-radius: 8px;'>";

// Step 1: Check if config file exists
echo "<h3>Step 1: Config File</h3>";
if (file_exists('config/config.php')) {
    echo "✅ config/config.php: EXISTS<br>";
} else {
    echo "❌ config/config.php: MISSING<br>";
    echo "This is the problem! The config file is missing.<br>";
    exit;
}

// Step 2: Try to include config
echo "<h3>Step 2: Include Config</h3>";
try {
    require_once 'config/config.php';
    echo "✅ Config file included successfully<br>";
} catch (Exception $e) {
    echo "❌ Error including config: " . $e->getMessage() . "<br>";
    exit;
}

// Step 3: Check if $pdo exists
echo "<h3>Step 3: Database Connection</h3>";
if (isset($pdo)) {
    echo "✅ \$pdo variable exists<br>";
} else {
    echo "❌ \$pdo variable is missing<br>";
    echo "The database connection wasn't established.<br>";
    exit;
}

// Step 4: Test basic database connection
echo "<h3>Step 4: Test Database Query</h3>";
try {
    $test = $pdo->query("SELECT 1");
    echo "✅ Basic database query: SUCCESS<br>";
} catch (PDOException $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "<br>";
    exit;
}

// Step 5: Check if classifieds table exists
echo "<h3>Step 5: Check Classifieds Table</h3>";
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'classifieds'");
    if ($stmt->rowCount() > 0) {
        echo "✅ classifieds table: EXISTS<br>";
    } else {
        echo "❌ classifieds table: MISSING<br>";
    }
} catch (PDOException $e) {
    echo "❌ Error checking classifieds table: " . $e->getMessage() . "<br>";
}

// Step 6: Check classifieds table structure
echo "<h3>Step 6: Classifieds Table Structure</h3>";
try {
    $stmt = $pdo->query("DESCRIBE classifieds");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "✅ Table structure retrieved (" . count($columns) . " columns)<br>";
    
    echo "📋 Columns found:<br>";
    foreach ($columns as $col) {
        echo "&nbsp;&nbsp;• {$col['Field']}<br>";
    }
} catch (PDOException $e) {
    echo "❌ Error getting table structure: " . $e->getMessage() . "<br>";
}

// Step 7: Test simple classifieds query
echo "<h3>Step 7: Test Simple Query</h3>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM classifieds");
    $count = $stmt->fetchColumn();
    echo "✅ Simple query: SUCCESS (Found $count classifieds)<br>";
} catch (PDOException $e) {
    echo "❌ Simple query failed: " . $e->getMessage() . "<br>";
}

// Step 8: Test the problematic query from classifieds.php
echo "<h3>Step 8: Test Complex Query (from classifieds.php)</h3>";
try {
    $stmt = $pdo->prepare("
        SELECT c.*, cc.name as category_name, cc.slug as category_slug, cc.icon as category_icon,
               u.username as user_name
        FROM classifieds c
        LEFT JOIN classifieds_categories cc ON c.category_id = cc.id
        LEFT JOIN users u ON c.user_id = u.id
        WHERE c.is_active = 1 
        ORDER BY c.created_at DESC
        LIMIT 5
    ");
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "✅ Complex query: SUCCESS (" . count($results) . " results)<br>";
} catch (PDOException $e) {
    echo "❌ Complex query failed: " . $e->getMessage() . "<br>";
    echo "This is likely the cause of the error in classifieds.php<br>";
}

echo "</div>";

echo "<br><h3>🎯 Summary:</h3>";
echo "<p>This script will help identify exactly where the problem is occurring.</p>";
echo "<p>Once you see the results, we can fix the specific issue.</p>";
?> 