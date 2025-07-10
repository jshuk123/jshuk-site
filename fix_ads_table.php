<?php
/**
 * Direct Fix for Ads Table
 * Ensures the ads table has the correct structure
 */

require_once __DIR__ . '/config/config.php';

// Check if user is admin
if (!isset($_SESSION['user_id'])) {
    die("Please log in as admin first.");
}

$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user || $user['role'] !== 'admin') {
    die("Admin access required.");
}

// Check current table structure
$currentColumns = [];
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM ads");
    while ($row = $stmt->fetch()) {
        $currentColumns[] = $row['Field'];
    }
} catch (PDOException $e) {
    echo "<h2>❌ Error checking table structure</h2>";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

echo "<h2>Current Ads Table Structure</h2>";
echo "<p>Columns found: " . implode(', ', $currentColumns) . "</p>";

// Check if status column exists
$hasStatusColumn = in_array('status', $currentColumns);
$hasZoneColumn = in_array('zone', $currentColumns);

if ($hasStatusColumn && $hasZoneColumn) {
    echo "<h2>✅ Table structure is correct!</h2>";
    echo "<p>The ads table already has the required columns.</p>";
    echo "<p><a href='admin/ads.php' class='btn btn-primary'>Go to Ad Management</a></p>";
    exit;
}

// Handle the fix
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fix_table'])) {
    try {
        // Read the simple migration SQL
        $migrationSQL = file_get_contents(__DIR__ . '/sql/simple_migration.sql');
        
        // Split SQL into individual statements
        $statements = array_filter(array_map('trim', explode(';', $migrationSQL)));
        
        $pdo->beginTransaction();
        
        foreach ($statements as $statement) {
            if (!empty($statement)) {
                $pdo->exec($statement);
            }
        }
        
        $pdo->commit();
        
        echo "<h2>✅ Table structure fixed successfully!</h2>";
        echo "<p>The ads table has been updated with the correct structure.</p>";
        echo "<p><a href='admin/ads.php' class='btn btn-primary'>Go to Ad Management</a></p>";
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        echo "<h2>❌ Fix failed</h2>";
        echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p>Please check your database connection and try again.</p>";
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Fix Ads Table - JShuk</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h4 class="mb-0"><i class="fas fa-exclamation-triangle"></i> Fix Required: Ads Table Structure</h4>
                </div>
                <div class="card-body">
                    <h5>Current Status:</h5>
                    <ul>
                        <li><strong>Status Column:</strong> <?= $hasStatusColumn ? '✅ Present' : '❌ Missing' ?></li>
                        <li><strong>Zone Column:</strong> <?= $hasZoneColumn ? '✅ Present' : '❌ Missing' ?></li>
                    </ul>
                    
                    <div class="alert alert-warning">
                        <strong>Issue:</strong> The ads table is missing required columns for the new ad management system.
                    </div>
                    
                    <h5>This fix will:</h5>
                    <ul>
                        <li>✅ Create a backup of your existing ads table</li>
                        <li>✅ Create a new ads table with the correct structure</li>
                        <li>✅ Add sample data for testing</li>
                        <li>✅ Create analytics and logging tables</li>
                    </ul>
                    
                    <div class="alert alert-info">
                        <strong>Note:</strong> This will create a fresh ads table. If you have existing ads data you want to preserve, 
                        please contact support for a data migration solution.
                    </div>
                    
                    <form method="POST" onsubmit="return confirm('This will recreate the ads table. Are you sure you want to continue?');">
                        <button type="submit" name="fix_table" class="btn btn-danger btn-lg">
                            <i class="fas fa-wrench"></i> Fix Table Structure
                        </button>
                        <a href="admin/ads.php" class="btn btn-outline-secondary btn-lg ms-2">
                            <i class="fas fa-arrow-left"></i> Cancel
                        </a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 