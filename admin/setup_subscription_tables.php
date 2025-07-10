<?php
/**
 * Web-based Setup Subscription Tables Script
 * 
 * This script creates the necessary subscription tables in the database.
 * Access this page to set up the subscription system.
 */

// Load configuration
require_once __DIR__ . '/../config/config.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Read and execute the subscription tables SQL
        $sql_file = __DIR__ . '/../sql/subscription_tables.sql';
        if (!file_exists($sql_file)) {
            throw new Exception("SQL file not found: $sql_file");
        }
        
        $sql_content = file_get_contents($sql_file);
        
        // Split SQL into individual statements
        $statements = array_filter(array_map('trim', explode(';', $sql_content)));
        
        foreach ($statements as $statement) {
            if (!empty($statement)) {
                $pdo->exec($statement);
            }
        }
        
        $message = "✅ Subscription tables created successfully!";
        
        // Now run the update script to add additional columns
        $update_sql_file = __DIR__ . '/../sql/update_subscription_plans.sql';
        if (file_exists($update_sql_file)) {
            $update_sql_content = file_get_contents($update_sql_file);
            $update_statements = array_filter(array_map('trim', explode(';', $update_sql_content)));
            
            foreach ($update_statements as $statement) {
                if (!empty($statement)) {
                    $pdo->exec($statement);
                }
            }
            
            $message .= "<br>✅ Subscription plans updated successfully!";
        }
        
        $message .= "<br><br>🎉 All subscription tables and plans have been set up successfully!<br>You can now use the Google authentication and subscription features.";
        
    } catch (Exception $e) {
        $error = "❌ Error setting up subscription tables: " . $e->getMessage();
    }
}

// Check if tables already exist
$tables_exist = false;
try {
    $pdo->query("SELECT 1 FROM subscription_plans LIMIT 1");
    $tables_exist = true;
} catch (PDOException $e) {
    $tables_exist = false;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Subscription Tables - JShuk</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="mb-0">Setup Subscription Tables</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($tables_exist): ?>
                            <div class="alert alert-success">
                                <strong>✅ Tables Already Exist!</strong><br>
                                The subscription tables are already set up in your database.
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                <strong>⚠️ Tables Missing!</strong><br>
                                The subscription tables are not set up in your database. 
                                This is why Google authentication is failing.
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($message): ?>
                            <div class="alert alert-success">
                                <?php echo $message; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <?php echo $error; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!$tables_exist): ?>
                            <form method="POST">
                                <p>Click the button below to create the necessary subscription tables:</p>
                                <button type="submit" class="btn btn-primary">Setup Subscription Tables</button>
                            </form>
                        <?php endif; ?>
                        
                        <hr>
                        
                        <h5>What this will do:</h5>
                        <ul>
                            <li>Create <code>subscription_plans</code> table</li>
                            <li>Create <code>user_subscriptions</code> table</li>
                            <li>Create <code>advertising_slots</code> table</li>
                            <li>Create <code>user_advertising_slots</code> table</li>
                            <li>Add subscription plans (Basic, Premium, Premium Plus)</li>
                            <li>Add <code>stripe_customer_id</code> column to users table</li>
                        </ul>
                        
                        <div class="mt-3">
                            <a href="/auth/login.php" class="btn btn-secondary">Back to Login</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 