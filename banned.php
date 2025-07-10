<?php
session_start();
require_once 'config/config.php';

// Check if user is logged in and banned
$isBanned = false;
$banReason = '';

if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT is_banned, ban_reason FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if ($user && $user['is_banned']) {
        $isBanned = true;
        $banReason = $user['ban_reason'];
    }
}

// If not banned, redirect to home
if (!$isBanned) {
    header('Location: /index.php');
    exit();
}

$pageTitle = "Account Suspended";
$page_css = "banned.css";
include 'includes/header_main.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow-lg border-danger">
                <div class="card-body text-center p-5">
                    <div class="mb-4">
                        <i class="fas fa-user-slash fa-4x text-danger"></i>
                    </div>
                    <h2 class="text-danger mb-3">Account Suspended</h2>
                    <p class="lead mb-4">Your account has been suspended due to a violation of our terms of service.</p>
                    
                    <?php if ($banReason): ?>
                        <div class="alert alert-warning">
                            <strong>Reason:</strong> <?php echo htmlspecialchars($banReason); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="mb-4">
                        <p>If you believe this was a mistake or would like to appeal this decision, please contact our support team.</p>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-block">
                        <a href="mailto:support@jshuk.com" class="btn btn-primary">
                            <i class="fas fa-envelope me-2"></i>Contact Support
                        </a>
                        <a href="/auth/logout.php" class="btn btn-outline-secondary">
                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                        </a>
                    </div>
                    
                    <hr class="my-4">
                    <small class="text-muted">
                        <strong>Support Email:</strong> support@jshuk.com<br>
                        <strong>Response Time:</strong> Within 24-48 hours
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.card {
    border-radius: 15px;
}

.card-body {
    border-radius: 15px;
}

.fa-user-slash {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
}
</style>

<?php include 'includes/footer_main.php'; ?> 