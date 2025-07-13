<?php
require_once '../../config/config.php';
require_once '../admin_auth_check.php';

// Get all pricing data
$subscriptions = $pdo->query("SELECT * FROM subscription_plans ORDER BY price ASC")->fetchAll();
$ads = $pdo->query("SELECT * FROM advertising_slots ORDER BY monthly_price ASC")->fetchAll();

// Get users with active subscriptions for free premium management
$premium_users = $pdo->query("
    SELECT u.id, u.email, u.first_name, u.last_name, u.subscription_status, u.subscription_plan_id, u.current_period_end,
           sp.name as plan_name
    FROM users u 
    LEFT JOIN subscription_plans sp ON u.subscription_plan_id = sp.id 
    WHERE u.subscription_status = 'active' 
    ORDER BY u.current_period_end DESC
")->fetchAll();

$pageTitle = "Pricing Control Panel";
include '../admin_header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">
                    <i class="fas fa-tags me-2"></i>
                    Pricing Control Panel
                </h1>
                <div>
                    <a href="grant_free_premium.php" class="btn btn-success">
                        <i class="fas fa-gift me-2"></i>
                        Grant Free Premium
                    </a>
                </div>
            </div>

            <!-- Subscription Plans Section -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-credit-card me-2"></i>
                        Subscription Plans
                    </h5>
                    <a href="edit_subscription.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus me-1"></i>
                        Add Plan
                    </a>
                </div>
                <div class="card-body">
                    <?php if (empty($subscriptions)): ?>
                        <p class="text-muted">No subscription plans found.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Price</th>
                                        <th>Annual Price</th>
                                        <th>Stripe Price ID</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($subscriptions as $plan): ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($plan['name']) ?></strong>
                                                <?php if ($plan['description']): ?>
                                                    <br><small class="text-muted"><?= htmlspecialchars($plan['description']) ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>£<?= number_format($plan['price'], 2) ?>/month</td>
                                            <td>
                                                <?php if ($plan['annual_price']): ?>
                                                    £<?= number_format($plan['annual_price'], 2) ?>/year
                                                <?php else: ?>
                                                    <span class="text-muted">Not set</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($plan['stripe_price_id']): ?>
                                                    <code class="small"><?= htmlspecialchars($plan['stripe_price_id']) ?></code>
                                                <?php else: ?>
                                                    <span class="badge bg-warning">Not configured</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?= $plan['status'] === 'active' ? 'success' : 'secondary' ?>">
                                                    <?= ucfirst($plan['status']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="edit_subscription.php?id=<?= $plan['id'] ?>" class="btn btn-outline-primary btn-sm">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Advertising Slots Section -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-ad me-2"></i>
                        Advertising Slots
                    </h5>
                    <a href="edit_ad_slot.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus me-1"></i>
                        Add Slot
                    </a>
                </div>
                <div class="card-body">
                    <?php if (empty($ads)): ?>
                        <p class="text-muted">No advertising slots found.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Monthly Price</th>
                                        <th>Annual Price</th>
                                        <th>Position</th>
                                        <th>Max Slots</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($ads as $ad): ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($ad['name']) ?></strong>
                                                <?php if ($ad['description']): ?>
                                                    <br><small class="text-muted"><?= htmlspecialchars($ad['description']) ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>£<?= number_format($ad['monthly_price'], 2) ?>/month</td>
                                            <td>
                                                <?php if ($ad['annual_price']): ?>
                                                    £<?= number_format($ad['annual_price'], 2) ?>/year
                                                <?php else: ?>
                                                    <span class="text-muted">Not set</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-info"><?= htmlspecialchars($ad['position']) ?></span>
                                            </td>
                                            <td><?= $ad['max_slots'] ?></td>
                                            <td>
                                                <span class="badge bg-<?= $ad['status'] === 'active' ? 'success' : 'secondary' ?>">
                                                    <?= ucfirst($ad['status']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="edit_ad_slot.php?id=<?= $ad['id'] ?>" class="btn btn-outline-primary btn-sm">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Premium Users Management Section -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-crown me-2"></i>
                        Premium Users Management
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($premium_users)): ?>
                        <p class="text-muted">No premium users found.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Email</th>
                                        <th>Plan</th>
                                        <th>Expires</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($premium_users as $user): ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></strong>
                                            </td>
                                            <td><?= htmlspecialchars($user['email']) ?></td>
                                            <td>
                                                <span class="badge bg-primary"><?= htmlspecialchars($user['plan_name'] ?? 'Unknown') ?></span>
                                            </td>
                                            <td>
                                                <?php if ($user['current_period_end']): ?>
                                                    <?php 
                                                    $expires = new DateTime($user['current_period_end']);
                                                    $now = new DateTime();
                                                    $is_expired = $expires < $now;
                                                    ?>
                                                    <span class="<?= $is_expired ? 'text-danger' : 'text-success' ?>">
                                                        <?= $expires->format('M j, Y') ?>
                                                        <?php if ($is_expired): ?>
                                                            <span class="badge bg-danger">Expired</span>
                                                        <?php endif; ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">No expiry set</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="edit_user_subscription.php?id=<?= $user['id'] ?>" class="btn btn-outline-warning btn-sm">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="revoke_premium.php?id=<?= $user['id'] ?>" class="btn btn-outline-danger btn-sm" 
                                                   onclick="return confirm('Are you sure you want to revoke premium access for this user?')">
                                                    <i class="fas fa-times"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../admin_footer.php'; ?> 