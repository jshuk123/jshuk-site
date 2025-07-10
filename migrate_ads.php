<?php
/**
 * Ads Table Migration Script
 * Safely migrates the existing ads table to the new enhanced structure
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

// Check if migration is needed
$migrationNeeded = false;
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM ads LIKE 'status'");
    if ($stmt->rowCount() === 0) {
        $migrationNeeded = true;
    }
} catch (PDOException $e) {
    $migrationNeeded = true;
}

if (!$migrationNeeded) {
    echo "<h2>✅ Migration Not Needed</h2>";
    echo "<p>The ads table already has the new structure.</p>";
    echo "<a href='admin/ads.php'>Go to Ad Management</a>";
    exit;
}

// Handle migration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_migration'])) {
    try {
        // Read and execute the migration SQL
        $migrationSQL = file_get_contents(__DIR__ . '/sql/migrate_ads_table.sql');
        
        // Split SQL into individual statements
        $statements = array_filter(array_map('trim', explode(';', $migrationSQL)));
        
        $pdo->beginTransaction();
        
        foreach ($statements as $statement) {
            if (!empty($statement)) {
                $pdo->exec($statement);
            }
        }
        
        $pdo->commit();
        
        echo "<h2>✅ Migration Completed Successfully!</h2>";
        echo "<p>The ads table has been updated to the new structure.</p>";
        echo "<p><a href='admin/ads.php' class='btn btn-primary'>Go to Ad Management</a></p>";
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        echo "<h2>❌ Migration Failed</h2>";
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
    <title>Ads Table Migration - JShuk</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-warning text-dark">
                    <h4 class="mb-0"><i class="fas fa-exclamation-triangle"></i> Database Migration Required</h4>
                </div>
                <div class="card-body">
                    <h5>What this migration will do:</h5>
                    <ul>
                        <li>✅ Create a backup of your existing ads table</li>
                        <li>✅ Update the table structure to support the new ad management system</li>
                        <li>✅ Migrate existing ad data to the new format</li>
                        <li>✅ Create new tables for analytics and admin logging</li>
                        <li>✅ Preserve all existing ad data</li>
                    </ul>
                    
                    <div class="alert alert-info">
                        <strong>Note:</strong> This migration is safe and will not delete any existing data. 
                        A backup will be created automatically.
                    </div>
                    
                    <form method="POST" onsubmit="return confirm('Are you sure you want to run the migration? This will update your database structure.');">
                        <button type="submit" name="run_migration" class="btn btn-warning btn-lg">
                            <i class="fas fa-database"></i> Run Migration
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