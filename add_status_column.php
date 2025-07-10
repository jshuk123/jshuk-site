<?php
/**
 * Add Missing Status Column
 * Quick fix to add the status column to the ads table
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

// Check if status column exists
$hasStatusColumn = false;
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM ads LIKE 'status'");
    $hasStatusColumn = $stmt->rowCount() > 0;
} catch (PDOException $e) {
    echo "<h2>❌ Error checking table</h2>";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

if ($hasStatusColumn) {
    echo "<h2>✅ Status column already exists!</h2>";
    echo "<p>The ads table already has the status column.</p>";
    echo "<p><a href='admin/ads.php' class='btn btn-primary'>Go to Ad Management</a></p>";
    exit;
}

// Handle adding the column
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_column'])) {
    try {
        // Add the status column
        $pdo->exec("ALTER TABLE ads ADD COLUMN `status` ENUM('active', 'paused', 'expired') DEFAULT 'paused' AFTER `end_date`");
        
        // Update existing ads to have a status
        $pdo->exec("UPDATE ads SET status = 'active' WHERE status IS NULL");
        
        echo "<h2>✅ Status column added successfully!</h2>";
        echo "<p>The status column has been added to the ads table.</p>";
        echo "<p><a href='admin/ads.php' class='btn btn-primary'>Go to Ad Management</a></p>";
        
    } catch (PDOException $e) {
        echo "<h2>❌ Failed to add column</h2>";
        echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p>Please try running the SQL manually:</p>";
        echo "<code>ALTER TABLE ads ADD COLUMN `status` ENUM('active', 'paused', 'expired') DEFAULT 'paused' AFTER `end_date`;</code>";
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Add Status Column - JShuk</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-warning text-dark">
                    <h4 class="mb-0"><i class="fas fa-exclamation-triangle"></i> Add Missing Status Column</h4>
                </div>
                <div class="card-body">
                    <h5>Issue Detected:</h5>
                    <p>The <code>status</code> column is missing from the ads table, which is causing the error in the admin panel.</p>
                    
                    <div class="alert alert-info">
                        <strong>Solution:</strong> This script will add the missing status column to fix the error.
                    </div>
                    
                    <h5>What this will do:</h5>
                    <ul>
                        <li>✅ Add the <code>status</code> column to the ads table</li>
                        <li>✅ Set default status as 'paused' for new ads</li>
                        <li>✅ Update existing ads to have 'active' status</li>
                        <li>✅ Fix the admin panel error</li>
                    </ul>
                    
                    <form method="POST" onsubmit="return confirm('Are you sure you want to add the status column?');">
                        <button type="submit" name="add_column" class="btn btn-warning btn-lg">
                            <i class="fas fa-plus"></i> Add Status Column
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