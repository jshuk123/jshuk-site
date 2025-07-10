<?php
session_start();
require_once '../config/config.php';
require_once '../config/stripe_config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /jshuk/auth/login.php');
    exit();
}

// Get user details
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Get user's current subscription
$current_subscription = getUserSubscription($_SESSION['user_id']);

$pageTitle = "Settings";
$page_css = "settings.css";
include '../includes/header_main.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-md-3">
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <div class="nav flex-column nav-pills" id="v-pills-tab" role="tablist">
                        <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#profile" type="button">
                            <i class="fas fa-user"></i> Profile Settings
                        </button>
                        <button class="nav-link" data-bs-toggle="pill" data-bs-target="#security" type="button">
                            <i class="fas fa-lock"></i> Security
                        </button>
                        <button class="nav-link" data-bs-toggle="pill" data-bs-target="#notifications" type="button">
                            <i class="fas fa-bell"></i> Notifications
                        </button>
                        <?php if ($current_subscription): ?>
                        <button class="nav-link" data-bs-toggle="pill" data-bs-target="#subscription" type="button">
                            <i class="fas fa-crown"></i> Subscription
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-9">
            <div class="tab-content" id="v-pills-tabContent">
                <!-- Profile Settings -->
                <div class="tab-pane fade show active" id="profile">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">Profile Settings</h5>
                            <form action="update_profile.php" method="POST" enctype="multipart/form-data">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="first_name" class="form-label">First Name</label>
                                        <input type="text" class="form-control" id="first_name" name="first_name" 
                                               value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="last_name" class="form-label">Last Name</label>
                                        <input type="text" class="form-control" id="last_name" name="last_name" 
                                               value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="profile_image" class="form-label">Profile Image</label>
                                    <input type="file" class="form-control" id="profile_image" name="profile_image" 
                                           accept="image/*">
                                </div>
                                <button type="submit" class="btn btn-primary">Save Changes</button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Security Settings -->
                <div class="tab-pane fade" id="security">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">Security Settings</h5>
                            <form action="update_password.php" method="POST">
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Current Password</label>
                                    <input type="password" class="form-control" id="current_password" 
                                           name="current_password" required>
                                </div>
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">New Password</label>
                                    <input type="password" class="form-control" id="new_password" 
                                           name="new_password" required>
                                </div>
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                                    <input type="password" class="form-control" id="confirm_password" 
                                           name="confirm_password" required>
                                </div>
                                <button type="submit" class="btn btn-primary">Update Password</button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Notification Settings -->
                <div class="tab-pane fade" id="notifications">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">Notification Settings</h5>
                            <form action="update_notifications.php" method="POST">
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="email_notifications" 
                                               name="email_notifications" <?php echo $user['email_notifications'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="email_notifications">
                                            Email Notifications
                                        </label>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="marketing_emails" 
                                               name="marketing_emails" <?php echo $user['marketing_emails'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="marketing_emails">
                                            Marketing Emails
                                        </label>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary">Save Preferences</button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Subscription Settings -->
                <?php if ($current_subscription): ?>
                <div class="tab-pane fade" id="subscription">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">Subscription Management</h5>
                            <div class="mb-4">
                                <h6>Current Plan: <?php echo htmlspecialchars($current_subscription['plan_name']); ?></h6>
                                <p class="text-muted">
                                    Status: <span class="badge bg-<?php echo $current_subscription['status'] === 'active' ? 'success' : 'warning'; ?>">
                                        <?php echo ucfirst($current_subscription['status']); ?>
                                    </span>
                                </p>
                                <p>Next billing date: <?php echo date('F j, Y', strtotime($current_subscription['next_billing_date'])); ?></p>
                            </div>

                            <div class="mb-4">
                                <h6>Payment Method</h6>
                                <?php if ($user['stripe_customer_id']): ?>
                                    <p>Your payment method is securely stored with Stripe.</p>
                                    <a href="/jshuk/payment/update_payment.php" class="btn btn-outline-primary">
                                        Update Payment Method
                                    </a>
                                <?php else: ?>
                                    <p>No payment method on file.</p>
                                    <a href="/jshuk/payment/update_payment.php" class="btn btn-primary">
                                        Add Payment Method
                                    </a>
                                <?php endif; ?>
                            </div>

                            <div class="mb-4">
                                <h6>Billing History</h6>
                                <a href="/jshuk/payment/billing_history.php" class="btn btn-outline-primary">
                                    View Billing History
                                </a>
                            </div>

                            <?php if ($current_subscription['status'] === 'active'): ?>
                            <div class="alert alert-warning">
                                <h6>Cancel Subscription</h6>
                                <p class="mb-0">Canceling your subscription will end your access to premium features at the end of your current billing period.</p>
                                <a href="/jshuk/payment/cancel_subscription.php" class="btn btn-danger mt-3">
                                    Cancel Subscription
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer_main.php'; ?> 