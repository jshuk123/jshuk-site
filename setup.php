<?php
/**
 * JShuk Setup Script
 * Helps users configure the environment and check system requirements
 */

// Prevent direct access in production
if (getenv('APP_ENV') === 'production') {
    die('Setup script is disabled in production environment.');
}

// Check if .env file exists
$env_file = __DIR__ . '/.env';
$env_exists = file_exists($env_file);

// Check system requirements
$requirements = [
    'php_version' => version_compare(PHP_VERSION, '7.4.0', '>='),
    'pdo_mysql' => extension_loaded('pdo_mysql'),
    'gd' => extension_loaded('gd'),
    'curl' => extension_loaded('curl'),
    'openssl' => extension_loaded('openssl'),
    'mbstring' => extension_loaded('mbstring'),
    'json' => extension_loaded('json'),
    'fileinfo' => extension_loaded('fileinfo')
];

$all_requirements_met = !in_array(false, $requirements);

// Check directory permissions
$directories = [
    'uploads' => is_writable(__DIR__ . '/uploads/'),
    'cache' => is_writable(__DIR__ . '/cache/'),
    'logs' => is_writable(__DIR__ . '/logs/'),
    'config' => is_writable(__DIR__ . '/config/')
];

$all_directories_writable = !in_array(false, $directories);

// Handle form submission
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_env'])) {
        // Create .env file
        $env_content = "# JShuk Environment Configuration\n";
        $env_content .= "# Generated on " . date('Y-m-d H:i:s') . "\n\n";
        $env_content .= "# Application Environment\n";
        $env_content .= "APP_ENV=development\n\n";
        $env_content .= "# Database Configuration\n";
        $env_content .= "DB_HOST=" . ($_POST['db_host'] ?? 'localhost') . "\n";
        $env_content .= "DB_NAME=" . ($_POST['db_name'] ?? 'jshuk_db') . "\n";
        $env_content .= "DB_USER=" . ($_POST['db_user'] ?? 'jshuk_user') . "\n";
        $env_content .= "DB_PASS=" . ($_POST['db_pass'] ?? '') . "\n\n";
        $env_content .= "# Site Configuration\n";
        $env_content .= "SITE_URL=" . ($_POST['site_url'] ?? 'http://localhost') . "\n\n";
        $env_content .= "# Email Configuration\n";
        $env_content .= "SMTP_HOST=" . ($_POST['smtp_host'] ?? 'smtp.gmail.com') . "\n";
        $env_content .= "SMTP_PORT=" . ($_POST['smtp_port'] ?? '587') . "\n";
        $env_content .= "SMTP_USERNAME=" . ($_POST['smtp_username'] ?? '') . "\n";
        $env_content .= "SMTP_PASSWORD=" . ($_POST['smtp_password'] ?? '') . "\n";
        $env_content .= "SMTP_ENCRYPTION=" . ($_POST['smtp_encryption'] ?? 'tls') . "\n\n";
        $env_content .= "# Security Settings\n";
        $env_content .= "SESSION_SECRET=" . bin2hex(random_bytes(32)) . "\n";
        $env_content .= "CSRF_SECRET=" . bin2hex(random_bytes(32)) . "\n";
        
        if (file_put_contents($env_file, $env_content)) {
            $message = "Environment file created successfully!";
            $message_type = "success";
            $env_exists = true;
        } else {
            $message = "Failed to create environment file. Check directory permissions.";
            $message_type = "error";
        }
    }
    
    if (isset($_POST['test_connection'])) {
        // Test database connection
        try {
            $host = $_POST['db_host'] ?? 'localhost';
            $dbname = $_POST['db_name'] ?? 'jshuk_db';
            $username = $_POST['db_user'] ?? 'jshuk_user';
            $password = $_POST['db_pass'] ?? '';
            
            $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
            $pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
            
            $message = "Database connection successful!";
            $message_type = "success";
        } catch (PDOException $e) {
            $message = "Database connection failed: " . $e->getMessage();
            $message_type = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JShuk Setup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .setup-card { max-width: 800px; margin: 2rem auto; }
        .requirement-item { padding: 0.5rem 0; }
        .requirement-item i { width: 20px; }
        .status-success { color: #28a745; }
        .status-error { color: #dc3545; }
        .status-warning { color: #ffc107; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card setup-card">
            <div class="card-header bg-primary text-white">
                <h1 class="h3 mb-0"><i class="fas fa-cog me-2"></i>JShuk Setup</h1>
            </div>
            <div class="card-body">
                <?php if ($message): ?>
                    <div class="alert alert-<?= $message_type === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show">
                        <?= htmlspecialchars($message) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- System Requirements -->
                <h2 class="h4 mb-3"><i class="fas fa-check-circle me-2"></i>System Requirements</h2>
                <div class="row">
                    <div class="col-md-6">
                        <?php foreach ($requirements as $requirement => $met): ?>
                            <div class="requirement-item">
                                <i class="fas fa-<?= $met ? 'check' : 'times' ?> status-<?= $met ? 'success' : 'error' ?>"></i>
                                <strong><?= ucfirst(str_replace('_', ' ', $requirement)) ?>:</strong>
                                <?= $met ? 'Available' : 'Missing' ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="col-md-6">
                        <?php foreach ($directories as $directory => $writable): ?>
                            <div class="requirement-item">
                                <i class="fas fa-<?= $writable ? 'check' : 'times' ?> status-<?= $writable ? 'success' : 'error' ?>"></i>
                                <strong><?= ucfirst($directory) ?> directory:</strong>
                                <?= $writable ? 'Writable' : 'Not writable' ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <?php if (!$all_requirements_met): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Some system requirements are not met. Please install missing extensions or fix directory permissions.
                    </div>
                <?php endif; ?>

                <!-- Environment Configuration -->
                <h2 class="h4 mb-3 mt-4"><i class="fas fa-cogs me-2"></i>Environment Configuration</h2>
                
                <?php if (!$env_exists): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        No environment file found. Create one to configure your application.
                    </div>
                    
                    <form method="POST" class="needs-validation" novalidate>
                        <div class="row">
                            <div class="col-md-6">
                                <h5>Database Configuration</h5>
                                <div class="mb-3">
                                    <label for="db_host" class="form-label">Database Host</label>
                                    <input type="text" class="form-control" id="db_host" name="db_host" value="localhost" required>
                                </div>
                                <div class="mb-3">
                                    <label for="db_name" class="form-label">Database Name</label>
                                    <input type="text" class="form-control" id="db_name" name="db_name" value="jshuk_db" required>
                                </div>
                                <div class="mb-3">
                                    <label for="db_user" class="form-label">Database User</label>
                                    <input type="text" class="form-control" id="db_user" name="db_user" value="jshuk_user" required>
                                </div>
                                <div class="mb-3">
                                    <label for="db_pass" class="form-label">Database Password</label>
                                    <input type="password" class="form-control" id="db_pass" name="db_pass" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h5>Site Configuration</h5>
                                <div class="mb-3">
                                    <label for="site_url" class="form-label">Site URL</label>
                                    <input type="url" class="form-control" id="site_url" name="site_url" value="http://localhost" required>
                                </div>
                                
                                <h5>Email Configuration</h5>
                                <div class="mb-3">
                                    <label for="smtp_host" class="form-label">SMTP Host</label>
                                    <input type="text" class="form-control" id="smtp_host" name="smtp_host" value="smtp.gmail.com">
                                </div>
                                <div class="mb-3">
                                    <label for="smtp_port" class="form-label">SMTP Port</label>
                                    <input type="number" class="form-control" id="smtp_port" name="smtp_port" value="587">
                                </div>
                                <div class="mb-3">
                                    <label for="smtp_username" class="form-label">SMTP Username</label>
                                    <input type="email" class="form-control" id="smtp_username" name="smtp_username">
                                </div>
                                <div class="mb-3">
                                    <label for="smtp_password" class="form-label">SMTP Password</label>
                                    <input type="password" class="form-control" id="smtp_password" name="smtp_password">
                                </div>
                                <div class="mb-3">
                                    <label for="smtp_encryption" class="form-label">SMTP Encryption</label>
                                    <select class="form-select" id="smtp_encryption" name="smtp_encryption">
                                        <option value="tls">TLS</option>
                                        <option value="ssl">SSL</option>
                                        <option value="">None</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="submit" name="create_env" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Create Environment File
                            </button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i>
                        Environment file exists. You can edit it manually or use the form below to test database connection.
                    </div>
                    
                    <form method="POST" class="row g-3">
                        <div class="col-md-4">
                            <label for="test_db_host" class="form-label">Database Host</label>
                            <input type="text" class="form-control" id="test_db_host" name="db_host" value="localhost">
                        </div>
                        <div class="col-md-4">
                            <label for="test_db_name" class="form-label">Database Name</label>
                            <input type="text" class="form-control" id="test_db_name" name="db_name" value="jshuk_db">
                        </div>
                        <div class="col-md-4">
                            <label for="test_db_user" class="form-label">Database User</label>
                            <input type="text" class="form-control" id="test_db_user" name="db_user" value="jshuk_user">
                        </div>
                        <div class="col-md-8">
                            <label for="test_db_pass" class="form-label">Database Password</label>
                            <input type="password" class="form-control" id="test_db_pass" name="db_pass">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" name="test_connection" class="btn btn-outline-primary d-block w-100">
                                <i class="fas fa-database me-2"></i>Test Connection
                            </button>
                        </div>
                    </form>
                <?php endif; ?>

                <!-- Next Steps -->
                <?php if ($all_requirements_met && $all_directories_writable && $env_exists): ?>
                    <div class="alert alert-success mt-4">
                        <h5><i class="fas fa-rocket me-2"></i>Setup Complete!</h5>
                        <p class="mb-2">Your JShuk installation is ready. Next steps:</p>
                        <ol class="mb-0">
                            <li>Import the database schema from <code>database.sql</code></li>
                            <li>Configure your web server to point to this directory</li>
                            <li>Set up SSL certificate for HTTPS</li>
                            <li>Configure your email settings</li>
                            <li>Delete this setup file for security</li>
                        </ol>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="index.php" class="btn btn-success">
                            <i class="fas fa-home me-2"></i>Go to Homepage
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 