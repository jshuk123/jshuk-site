<?php
session_start();
require_once '../config/db_connect.php';

// Check if user is logged in as admin
if (!isset($_SESSION['admin_id']) || empty($_SESSION['admin_id'])) {
    die("Access denied. Admin login required.");
}

// Check if this is a POST request with confirmation
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['confirm_migration'])) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Add Ads Table Columns - JShuk Admin</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body>
        <div class="container mt-5">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h4>Database Migration: Add Ads Table Columns</h4>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-warning">
                                <h5>⚠️ Important Notice</h5>
                                <p>This script will add the following columns to your <code>ads</code> table:</p>
                                <ul>
                                    <li><code>status</code> - VARCHAR(20) DEFAULT 'active'</li>
                                    <li><code>zone</code> - VARCHAR(50) DEFAULT 'header'</li>
                                    <li><code>priority</code> - INT DEFAULT 1</li>
                                    <li><code>category_id</code> - INT NULL</li>
                                    <li><code>location</code> - VARCHAR(100) NULL</li>
                                    <li><code>start_date</code> - DATE NULL</li>
                                    <li><code>end_date</code> - DATE NULL</li>
                                    <li><code>clicks</code> - INT DEFAULT 0</li>
                                    <li><code>impressions</code> - INT DEFAULT 0</li>
                                    <li><code>targeting_options</code> - TEXT NULL</li>
                                    <li><code>admin_notes</code> - TEXT NULL</li>
                                </ul>
                                <p><strong>This operation is safe and will only add columns that don't already exist.</strong></p>
                            </div>
                            
                            <form method="POST">
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="confirm" required>
                                    <label class="form-check-label" for="confirm">
                                        I understand this will modify the database structure
                                    </label>
                                </div>
                                <button type="submit" name="confirm_migration" class="btn btn-primary" disabled id="submitBtn">
                                    Run Migration
                                </button>
                                <a href="../admin/index.php" class="btn btn-secondary">Cancel</a>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
            document.getElementById('confirm').addEventListener('change', function() {
                document.getElementById('submitBtn').disabled = !this.checked;
            });
        </script>
    </body>
    </html>
    <?php
    exit;
}

// Proceed with migration
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $results = [];
    
    // Array of columns to add with their definitions
    $columns = [
        'status' => "VARCHAR(20) DEFAULT 'active'",
        'zone' => "VARCHAR(50) DEFAULT 'header'",
        'priority' => "INT DEFAULT 1",
        'category_id' => "INT NULL",
        'location' => "VARCHAR(100) NULL",
        'start_date' => "DATE NULL",
        'end_date' => "DATE NULL",
        'clicks' => "INT DEFAULT 0",
        'impressions' => "INT DEFAULT 0",
        'targeting_options' => "TEXT NULL",
        'admin_notes' => "TEXT NULL"
    ];
    
    foreach ($columns as $column => $definition) {
        try {
            // Check if column exists
            $checkStmt = $pdo->query("SHOW COLUMNS FROM ads LIKE '$column'");
            if ($checkStmt->rowCount() == 0) {
                // Column doesn't exist, add it
                $sql = "ALTER TABLE ads ADD COLUMN $column $definition";
                $pdo->exec($sql);
                $results[] = "✅ Added column: $column";
            } else {
                $results[] = "⏭️ Column already exists: $column";
            }
        } catch (PDOException $e) {
            $results[] = "❌ Error adding column $column: " . $e->getMessage();
        }
    }
    
    // Add foreign key constraint for category_id if it doesn't exist
    try {
        $pdo->exec("ALTER TABLE ads ADD CONSTRAINT fk_ads_category 
                   FOREIGN KEY (category_id) REFERENCES business_categories(id) 
                   ON DELETE SET NULL");
        $results[] = "✅ Added foreign key constraint for category_id";
    } catch (PDOException $e) {
        $results[] = "⏭️ Foreign key constraint already exists or error: " . $e->getMessage();
    }
    
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Migration Complete - JShuk Admin</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body>
        <div class="container mt-5">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h4>🎉 Migration Completed Successfully!</h4>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-success">
                                <h5>Database migration completed!</h5>
                                <p>The ads table has been updated with all required columns.</p>
                            </div>
                            
                            <h6>Migration Results:</h6>
                            <div class="bg-light p-3 rounded">
                                <?php foreach ($results as $result): ?>
                                    <div><?= htmlspecialchars($result) ?></div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="mt-4">
                                <a href="../admin/ads.php" class="btn btn-primary">
                                    <i class="fas fa-ad"></i> Go to Ad Management
                                </a>
                                <a href="../admin/index.php" class="btn btn-secondary">
                                    <i class="fas fa-home"></i> Back to Admin Dashboard
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    
} catch (PDOException $e) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Migration Error - JShuk Admin</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body>
        <div class="container mt-5">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header bg-danger text-white">
                            <h4>❌ Migration Failed</h4>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-danger">
                                <h5>Database connection error:</h5>
                                <p><?= htmlspecialchars($e->getMessage()) ?></p>
                            </div>
                            <a href="../admin/index.php" class="btn btn-secondary">Back to Admin Dashboard</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
}
?> 