<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/config.php';

// Authentication check
if (!isset($_SESSION['user_id'])) {
    header('Location: /auth/login.php?redirect=/users/dashboard.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'User';
$user_email = $_SESSION['user_email'] ?? '';
$user_created_at = $_SESSION['user_created_at'] ?? date('Y-m-d'); 

// --- Data Fetching ---
try {
    // Fetch user's businesses with image
    $stmt = $pdo->prepare("
        SELECT b.*, b.subscription_tier, i.file_path as main_image 
        FROM businesses b
        LEFT JOIN (
            SELECT file_path, business_id FROM business_images WHERE sort_order = 0
        ) i ON b.id = i.business_id
        WHERE b.user_id = ? 
        ORDER BY b.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $my_businesses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // --- Temporarily disable Job and Classifieds fetching to prevent errors ---
    $my_jobs = [];
    $my_classifieds = [];
    /*
    // Fetch user's jobs using contact_email as per original schema
    $stmt = $pdo->prepare("SELECT * FROM recruitment WHERE contact_email = ? ORDER BY created_at DESC");
    $stmt->execute([$user_email]);
    $my_jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch user's classifieds
    $stmt = $pdo->prepare("
        SELECT c.*, cat.name AS category_name 
        FROM classifieds c 
        LEFT JOIN classifieds_categories cat ON c.category_id = cat.id 
        WHERE c.user_id = ? 
        ORDER BY c.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $my_classifieds = $stmt->fetchAll(PDO::FETCH_ASSOC);
    */

} catch (PDOException $e) {
    // Log error and show a user-friendly message
    error_log("Dashboard Error: " . $e->getMessage());
    die("A database error occurred. Please try again later.");
}

$pageTitle = "Dashboard";
$page_css = "dashboard.css";
include '../includes/header_main.php';

// Helper function to render status badges
function getStatusBadge($status) {
    $map = [
        'active' => ['class' => 'success', 'text' => 'Active'],
        'pending' => ['class' => 'warning', 'text' => 'Pending'],
        'expired' => ['class' => 'secondary', 'text' => 'Expired'],
        'rejected' => ['class' => 'danger', 'text' => 'Rejected'],
    ];
    $badge = $map[$status] ?? ['class' => 'secondary', 'text' => ucfirst($status)];
    return "<span class=\"badge bg-{$badge['class']}\">{$badge['text']}</span>";
}

// Helper function to get tier display name
function getTierDisplayName($tier) {
    $tiers = [
        'basic' => 'Basic',
        'premium' => 'Premium',
        'premium_plus' => 'Premium+',
    ];
    return $tiers[$tier] ?? ucfirst($tier);
}

// Helper function to get subscription tier limits
function getSubscriptionTierLimits($tier) {
    $limits = [
        'basic' => [
            'images' => 5,
            'testimonials' => 5,
            'unlimited_testimonials' => false,
        ],
        'premium' => [
            'images' => 5,
            'testimonials' => 5,
            'unlimited_testimonials' => false,
        ],
        'premium_plus' => [
            'images' => null,
            'testimonials' => null,
            'unlimited_testimonials' => true,
        ],
    ];
    return $limits[$tier] ?? $limits['basic'];
}

// Helper function to get business image count
function getBusinessImageCount($business_id, $pdo) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM business_images WHERE business_id = ? AND sort_order = 0");
        $stmt->execute([$business_id]);
        return $stmt->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

// Helper function to get business testimonial count
function getBusinessTestimonialCount($business_id, $pdo) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM testimonials WHERE business_id = ? AND status = 'approved'");
        $stmt->execute([$business_id]);
        return $stmt->fetchColumn();
    } catch (PDOException $e) {
        // Try reviews table as fallback
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM reviews WHERE business_id = ? AND is_approved = 1");
            $stmt->execute([$business_id]);
            return $stmt->fetchColumn();
        } catch (PDOException $e2) {
            return 0;
        }
    }
}
?>

<div class="container my-5">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-lg-3">
            <div class="card sticky-top" style="top: 2rem;">
                <div class="card-body text-center">
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($user_name) ?>&background=0d6efd&color=fff&size=100&rounded=true" alt="User Avatar" class="rounded-circle mb-3">
                    <h5 class="card-title mb-0"><?= htmlspecialchars($user_name) ?></h5>
                    <p class="card-text text-muted small"><?= htmlspecialchars($user_email) ?></p>
                    <p class="card-text text-muted small">Member since <?= date('M Y', strtotime($user_created_at)) ?></p>
                </div>
                <div class="list-group list-group-flush">
                    <a href="/users/edit_profile.php" class="list-group-item list-group-item-action"><i class="fa-solid fa-user-edit me-2"></i>Edit Profile</a>
                    <a href="/users/change_password.php" class="list-group-item list-group-item-action"><i class="fa-solid fa-key me-2"></i>Change Password</a>
                    <a href="/auth/logout.php" class="list-group-item list-group-item-action text-danger"><i class="fa-solid fa-sign-out-alt me-2"></i>Logout</a>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="col-lg-9">
            <h1 class="mb-4">Dashboard</h1>

            <!-- Quick Actions -->
            <div class="card mb-4">
                <div class="card-body d-flex flex-wrap gap-2 justify-content-center">
                    <a href="/users/post_business.php" class="btn btn-primary"><i class="fa-solid fa-plus me-2"></i>List New Business</a>
                    <a href="/submit_job.php" class="btn btn-secondary"><i class="fa-solid fa-briefcase me-2"></i>Post New Job</a>
                    <a href="/submit_classified.php" class="btn btn-secondary"><i class="fa-solid fa-bullhorn me-2"></i>Post New Classified</a>
                </div>
            </div>

            <!-- Tabs -->
            <ul class="nav nav-pills mb-3" id="dashboardTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="businesses-tab" data-bs-toggle="tab" data-bs-target="#businesses-content" type="button" role="tab">My Businesses <span class="badge bg-light text-dark ms-1"><?= count($my_businesses) ?></span></button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link position-relative" id="subscriptions-tab" data-bs-toggle="tab" data-bs-target="#subscriptions-content" type="button" role="tab">
                        Subscriptions
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning text-dark" style="font-size:0.8em;">New</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="jobs-tab" data-bs-toggle="tab" data-bs-target="#jobs-content" type="button" role="tab">My Jobs <span class="badge bg-light text-dark ms-1"><?= count($my_jobs) ?></span></button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="classifieds-tab" data-bs-toggle="tab" data-bs-target="#classifieds-content" type="button" role="tab">My Classifieds <span class="badge bg-light text-dark ms-1"><?= count($my_classifieds) ?></span></button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="testimonials-tab" data-bs-toggle="tab" data-bs-target="#testimonials-content" type="button" role="tab">Testimonials</button>
                </li>
            </ul>

            <!-- Tab Content -->
            <div class="tab-content" id="dashboardTabsContent">
                <!-- My Businesses -->
                <div class="tab-pane fade show active" id="businesses-content" role="tabpanel">
                    <?php if (empty($my_businesses)): ?>
                        <div class="text-center p-5 border rounded">
                            <p>You haven't listed any businesses yet.</p>
                            <a href="/users/post_business.php" class="btn btn-primary">List Your First Business</a>
                        </div>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($my_businesses as $biz): ?>
                                <div class="list-group-item list-group-item-action d-flex gap-3 py-3">
                                    <img src="/<?= htmlspecialchars($biz['main_image'] ?: 'images/jshuk-logo.png') ?>" alt="<?= htmlspecialchars($biz['business_name']) ?>" width="48" height="48" class="rounded-circle flex-shrink-0">
                                    <div class="d-flex gap-2 w-100 justify-content-between">
                                        <div>
                                            <h6 class="mb-0"><?= htmlspecialchars($biz['business_name']) ?></h6>
                                            <p class="mb-0 opacity-75 small">Created: <?= date('d M Y', strtotime($biz['created_at'])) ?></p>
                                        </div>
                                        <div class="text-end">
                                            <?= getStatusBadge($biz['status']) ?>
                                            <div class="mt-1">
                                                <!-- DEBUG: Business ID is <?= htmlspecialchars($biz['id']) ?> -->
                                                <a href="/business.php?id=<?= $biz['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-eye"></i></a>
                                                <a href="/users/edit_business.php?id=<?= $biz['id'] ?>" class="btn btn-sm btn-outline-primary ms-1"><i class="fa-solid fa-edit"></i> Edit</a>
                                                <form method="post" action="/users/delete_business.php" style="display:inline;">
                                                    <input type="hidden" name="business_id" value="<?= $biz['id'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this business?')">
                                                        <i class="fa-solid fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Moderate Testimonials Section -->
                <div class="tab-pane fade" id="testimonials-content" role="tabpanel">
                    <h3>Moderate Testimonials</h3>
                    <?php
                    // Fetch testimonials for all businesses owned by this user
                    $stmt = $pdo->prepare("
                        SELECT t.*, b.business_name
                        FROM testimonials t
                        JOIN businesses b ON t.business_id = b.id
                        WHERE b.user_id = ?
                        ORDER BY t.created_at DESC
                    ");
                    $stmt->execute([$user_id]);
                    $testimonials = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    ?>
                    <?php if (empty($testimonials)): ?>
                        <div class="alert alert-info">No testimonials to moderate yet.</div>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($testimonials as $t): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><?= htmlspecialchars($t['author_name']) ?></strong> on <em><?= htmlspecialchars($t['business_name']) ?></em><br>
                                        <span class="small text-muted"><?= date('d M Y', strtotime($t['created_at'])) ?></span>
                                        <p><?= nl2br(htmlspecialchars($t['content'])) ?></p>
                                        <span class="badge bg-<?= $t['status'] === 'approved' ? 'success' : ($t['status'] === 'pending' ? 'warning' : 'secondary') ?>">
                                            <?= ucfirst($t['status']) ?>
                                        </span>
                                    </div>
                                    <div>
                                        <?php if ($t['status'] !== 'approved'): ?>
                                            <form method="post" action="/actions/edit_testimonial.php" style="display:inline;">
                                                <input type="hidden" name="testimonial_id" value="<?= $t['id'] ?>">
                                                <input type="hidden" name="action" value="approve">
                                                <button type="submit" class="btn btn-sm btn-success">Approve</button>
                                            </form>
                                        <?php endif; ?>
                                        <?php if ($t['status'] !== 'hidden'): ?>
                                            <form method="post" action="/actions/edit_testimonial.php" style="display:inline;">
                                                <input type="hidden" name="testimonial_id" value="<?= $t['id'] ?>">
                                                <input type="hidden" name="action" value="hide">
                                                <button type="submit" class="btn btn-sm btn-warning">Hide</button>
                                            </form>
                                        <?php endif; ?>
                                        <form method="post" action="/actions/delete_testimonial.php" style="display:inline;">
                                            <input type="hidden" name="testimonial_id" value="<?= $t['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this testimonial?')">Delete</button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Subscriptions Tab -->
                <div class="tab-pane fade" id="subscriptions-content" role="tabpanel">
                    <h3>Upgrade Your Subscription</h3>
                    
                    <?php 
                    // Get user's current subscription tier
                    $user_tier = 'basic'; // default
                    try {
                        $stmt = $pdo->prepare("SELECT subscription_tier FROM users WHERE id = ?");
                        $stmt->execute([$user_id]);
                        $user_tier = $stmt->fetchColumn() ?: 'basic';
                    } catch (PDOException $e) {
                        // If column doesn't exist, use default
                        error_log("Subscription tier column may not exist: " . $e->getMessage());
                        $user_tier = 'basic';
                    }
                    ?>
                    
                    <div class="current-tier-info mb-4">
                        <h4>Current Plan: <?= getTierDisplayName($user_tier) ?></h4>
                        <p class="text-muted">Your subscription tier applies to all your businesses</p>
                    </div>
                    
                    <!-- Upgrade Options -->
                    <div class="upgrade-options">
                        <?php if ($user_tier === 'basic'): ?>
                            <!-- Basic to Premium -->
                            <div class="upgrade-card">
                                <div class="upgrade-header">
                                    <h5>Upgrade to Premium</h5>
                                    <div class="price">£15/month</div>
                                </div>
                                <ul class="upgrade-features">
                                    <li>Up to 5 gallery images per business</li>
                                    <li>Up to 5 testimonials per business</li>
                                    <li>Homepage carousel visibility</li>
                                    <li>Gold Premium badge</li>
                                    <li>Priority in search results</li>
                                    <li>WhatsApp-ready sign-up graphic</li>
                                    <li>Can offer promotions</li>
                                </ul>
                                <a href="upgrade_subscription.php?tier=premium" class="btn btn-warning">Upgrade to Premium</a>
                            </div>
                            
                            <!-- Basic to Premium+ -->
                            <div class="upgrade-card premium-plus">
                                <div class="upgrade-header">
                                    <h5>Upgrade to Premium+</h5>
                                    <div class="price">£30/month</div>
                                </div>
                                <ul class="upgrade-features">
                                    <li>Unlimited gallery images per business</li>
                                    <li>Unlimited testimonials per business</li>
                                    <li>Pinned in search results</li>
                                    <li>Animated glow/border on listings</li>
                                    <li>Top Pick/Elite ribbon</li>
                                    <li>Access to beta features</li>
                                    <li>Included in WhatsApp highlight messages</li>
                                </ul>
                                <a href="upgrade_subscription.php?tier=premium_plus" class="btn btn-primary">Upgrade to Premium+</a>
                            </div>
                            
                        <?php elseif ($user_tier === 'premium'): ?>
                            <!-- Premium to Premium+ -->
                            <div class="upgrade-card premium-plus">
                                <div class="upgrade-header">
                                    <h5>Upgrade to Premium+</h5>
                                    <div class="price">£30/month</div>
                                </div>
                                <ul class="upgrade-features">
                                    <li>Unlimited gallery images per business (vs 5)</li>
                                    <li>Unlimited testimonials per business (vs 5)</li>
                                    <li>Pinned in search results</li>
                                    <li>Blue Premium+ badge with crown (vs gold)</li>
                                    <li>Animated glow/border on listing</li>
                                    <li>Top Pick/Elite ribbon</li>
                                    <li>Access to beta features</li>
                                    <li>Included in WhatsApp highlight messages</li>
                                </ul>
                                <a href="upgrade_subscription.php?tier=premium_plus" class="btn btn-primary">Upgrade to Premium+</a>
                            </div>
                            
                        <?php else: ?>
                            <!-- Already Premium+ -->
                            <div class="alert alert-success">
                                <h5><i class="fas fa-crown"></i> You're already on Premium+!</h5>
                                <p>You have access to all features including unlimited images, testimonials, and beta features.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Your Businesses Summary -->
                    <div class="businesses-summary mt-5">
                        <h4>Your Businesses (<?= count($my_businesses) ?>)</h4>
                        <div class="row">
                            <?php foreach ($my_businesses as $business): ?>
                                <div class="col-md-6 col-lg-4 mb-3">
                                    <div class="business-summary-card">
                                        <div class="business-info">
                                            <h6><?= htmlspecialchars($business['business_name']) ?></h6>
                                            <p class="text-muted"><?= htmlspecialchars($business['category_name'] ?? 'No Category') ?></p>
                                        </div>
                                        <div class="business-stats">
                                            <small>
                                                Images: <?= getBusinessImageCount($business['id'], $pdo) ?>
                                                <?php if ($user_tier === 'premium_plus'): ?>
                                                    <span class="unlimited-indicator">∞</span>
                                                <?php else: ?>
                                                    /<?= getSubscriptionTierLimits($user_tier)['images'] ?>
                                                <?php endif; ?>
                                            </small>
                                            <br>
                                            <small>
                                                Reviews: <?= getBusinessTestimonialCount($business['id'], $pdo) ?>
                                                <?php if ($user_tier === 'premium_plus'): ?>
                                                    <span class="unlimited-indicator">∞</span>
                                                <?php else: ?>
                                                    /<?= getSubscriptionTierLimits($user_tier)['testimonials'] ?>
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- My Jobs -->
                <div class="tab-pane fade" id="jobs-content" role="tabpanel">
                    <div class="text-center p-5 border rounded">Coming soon!</div>
                </div>

                <!-- My Classifieds -->
                <div class="tab-pane fade" id="classifieds-content" role="tabpanel">
                    <div class="text-center p-5 border rounded">Coming soon!</div>
                </div>
            </div>

            <!-- Success Messages -->
            <?php if (isset($_GET['upgrade']) && $_GET['upgrade'] === 'success'): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <strong>Upgrade Successful!</strong> Your subscription has been upgraded. You now have access to premium features.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['subscription_success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-crown me-2"></i>
                    <strong>Welcome to <?= htmlspecialchars($_SESSION['subscription_success']['plan_name']) ?>!</strong>
                    Your subscription is now active. You have access to all premium features.
                    <?php if ($_SESSION['subscription_success']['trial_end']): ?>
                        <br><small class="text-muted">Trial ends: <?= date('F j, Y', strtotime($_SESSION['subscription_success']['trial_end'])) ?></small>
                    <?php endif; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['subscription_success']); ?>
            <?php endif; ?>

            <!-- Success/Error Message Area -->
            <?php if (!empty($_SESSION['success'])): ?>
                <div class="alert alert-success"> <?= htmlspecialchars($_SESSION['success']) ?> </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            <?php if (!empty($_SESSION['error'])): ?>
                <div class="alert alert-danger"> <?= htmlspecialchars($_SESSION['error']) ?> </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer_main.php'; ?>