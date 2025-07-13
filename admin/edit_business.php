<?php
require_once '../config/config.php';
require_once '../includes/subscription_functions.php';

// Check admin access (reuse logic from businesses.php)
function checkAdminAccess() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../index.php');
        exit();
    }
    global $pdo;
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    if ($user['role'] !== 'admin') {
        header('Location: ../index.php');
        exit();
    }
}

session_start();
checkAdminAccess();

$business_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$message = '';
$error = '';

if (!$business_id) {
    header('Location: businesses.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $subscription_tier = $_POST['subscription_tier'] ?? 'basic';
        $tagline = $_POST['tagline'] ?? '';
        $location = $_POST['location'] ?? '';
        $business_hours_summary = $_POST['business_hours_summary'] ?? '';
        $is_elite = isset($_POST['is_elite']) ? 1 : 0;
        $is_pinned = isset($_POST['is_pinned']) ? 1 : 0;
        
        // Validate tier
        if (!in_array($subscription_tier, ['basic', 'premium', 'premium_plus'])) {
            throw new Exception('Invalid subscription tier');
        }
        
        // Update business fields
        $stmt = $pdo->prepare("
            UPDATE businesses 
            SET subscription_tier = ?, tagline = ?, location = ?, business_hours_summary = ?, 
                is_elite = ?, is_pinned = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$subscription_tier, $tagline, $location, $business_hours_summary, 
                       $is_elite, $is_pinned, $business_id]);
        
        $message = "✅ Business updated successfully!";
        
    } catch (Exception $e) {
        $error = "❌ Error updating business: " . $e->getMessage();
    }
}

// Get business data
try {
    $stmt = $pdo->prepare("
        SELECT b.*, c.name AS category_name, u.first_name, u.last_name, u.email 
        FROM businesses b 
        LEFT JOIN business_categories c ON b.category_id = c.id 
        LEFT JOIN users u ON b.user_id = u.id 
        WHERE b.id = ?
    ");
    $stmt->execute([$business_id]);
    $business = $stmt->fetch();
    
    if (!$business) {
        header('Location: businesses.php');
        exit();
    }
    
    // Get current counts
    $image_count = getBusinessImageCount($business_id, $pdo);
    $testimonial_count = getBusinessTestimonialCount($business_id, $pdo);
    
} catch (Exception $e) {
    $error = "❌ Error loading business: " . $e->getMessage();
    $business = null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Business Subscription - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../css/components/subscription-badges.css" rel="stylesheet">
</head>
<body class="container py-5">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Edit Business Subscription</h1>
                <a href="businesses.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Businesses
                </a>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-success"><?= $message ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>
            
            <?php if ($business): ?>
                <div class="row">
                    <!-- Business Information -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Business Information</h5>
                            </div>
                            <div class="card-body">
                                <h6><?= htmlspecialchars($business['business_name']) ?></h6>
                                <p class="text-muted mb-2">
                                    <i class="fas fa-tag"></i> 
                                    <?= htmlspecialchars($business['category_name'] ?? 'No Category') ?>
                                </p>
                                <p class="text-muted mb-2">
                                    <i class="fas fa-user"></i> 
                                    <?= htmlspecialchars($business['first_name'] . ' ' . $business['last_name']) ?>
                                </p>
                                <p class="text-muted mb-0">
                                    <i class="fas fa-envelope"></i> 
                                    <?= htmlspecialchars($business['email']) ?>
                                </p>
                                
                                <hr>
                                
                                <div class="d-flex align-items-center gap-2">
                                    <strong>Current Tier:</strong>
                                    <?= renderSubscriptionBadge($business['subscription_tier']) ?>
                                </div>
                                
                                <div class="mt-3">
                                    <small class="text-muted">
                                        <i class="fas fa-images"></i> Images: <?= $image_count ?> 
                                        (<?= $business['subscription_tier'] === 'premium_plus' ? 'Unlimited' : getSubscriptionTierLimits($business['subscription_tier'])['images'] ?> allowed)
                                    </small><br>
                                    <small class="text-muted">
                                        <i class="fas fa-star"></i> Testimonials: <?= $testimonial_count ?> 
                                        (<?= $business['subscription_tier'] === 'premium_plus' ? 'Unlimited' : getSubscriptionTierLimits($business['subscription_tier'])['testimonials'] ?> allowed)
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Subscription Tier Management -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Update Business Details</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="mb-3">
                                        <label for="subscription_tier" class="form-label">Subscription Tier:</label>
                                        <select name="subscription_tier" id="subscription_tier" class="form-select">
                                            <option value="basic" <?= $business['subscription_tier'] === 'basic' ? 'selected' : '' ?>>Basic</option>
                                            <option value="premium" <?= $business['subscription_tier'] === 'premium' ? 'selected' : '' ?>>Premium</option>
                                            <option value="premium_plus" <?= $business['subscription_tier'] === 'premium_plus' ? 'selected' : '' ?>>Premium+</option>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="tagline" class="form-label">Tagline:</label>
                                        <input type="text" name="tagline" id="tagline" class="form-control" 
                                               value="<?= htmlspecialchars($business['tagline'] ?? '') ?>" 
                                               placeholder="Brief business tagline">
                                        <small class="text-muted">Short description for business cards</small>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="location" class="form-label">Location:</label>
                                        <input type="text" name="location" id="location" class="form-control" 
                                               value="<?= htmlspecialchars($business['location'] ?? '') ?>" 
                                               placeholder="e.g., Golders Green, London">
                                        <small class="text-muted">City or area for display</small>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="business_hours_summary" class="form-label">Business Hours Summary:</label>
                                        <input type="text" name="business_hours_summary" id="business_hours_summary" class="form-control" 
                                               value="<?= htmlspecialchars($business['business_hours_summary'] ?? '') ?>" 
                                               placeholder="e.g., Mon-Fri 9-5">
                                        <small class="text-muted">Quick hours display for business cards</small>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input type="checkbox" name="is_elite" id="is_elite" class="form-check-input" 
                                                   value="1" <?= ($business['is_elite'] ?? 0) ? 'checked' : '' ?>>
                                            <label for="is_elite" class="form-check-label">
                                                <i class="fas fa-crown text-warning"></i> Elite Business
                                            </label>
                                        </div>
                                        <small class="text-muted">Mark as elite for special display</small>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <div class="form-check">
                                            <input type="checkbox" name="is_pinned" id="is_pinned" class="form-check-input" 
                                                   value="1" <?= ($business['is_pinned'] ?? 0) ? 'checked' : '' ?>>
                                            <label for="is_pinned" class="form-check-label">
                                                <i class="fas fa-thumbtack text-primary"></i> Pinned Business
                                            </label>
                                        </div>
                                        <small class="text-muted">Pin to top of listings</small>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Update Business
                                    </button>
                                </form>
                                
                                <hr>
                                
                                <!-- Tier Comparison -->
                                <h6>Tier Features:</h6>
                                <div class="tier-comparison">
                                    <?php 
                                    $current_tier = $business['subscription_tier'];
                                    $limits = getSubscriptionTierLimits($current_tier);
                                    ?>
                                    <ul class="tier-features">
                                        <li>Gallery Images: <?= $limits['images'] === null ? 'Unlimited' : $limits['images'] ?></li>
                                        <li>Testimonials: <?= $limits['unlimited_testimonials'] ? 'Unlimited' : $limits['testimonials'] ?></li>
                                        <li>Homepage Visibility: <?= $limits['homepage_visibility'] ? '✓' : '✗' ?></li>
                                        <li>Priority Search: <?= $limits['priority_search'] ? '✓' : '✗' ?></li>
                                        <li>WhatsApp Features: <?= $limits['whatsapp_features'] ? '✓' : '✗' ?></li>
                                        <li>Pinned Results: <?= $limits['pinned_results'] ? '✓' : '✗' ?></li>
                                        <li>Beta Features: <?= $limits['beta_features'] ? '✓' : '✗' ?></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Tier Upgrade Benefits -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Upgrade Benefits</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <?php if ($current_tier !== 'premium'): ?>
                                    <div class="col-md-6">
                                        <div class="tier-info-card premium">
                                            <h6><?= renderSubscriptionBadge('premium') ?> Upgrade Benefits</h6>
                                            <ul class="tier-features">
                                                <?php 
                                                $benefits = getTierUpgradeBenefits($current_tier, 'premium');
                                                foreach ($benefits as $benefit): ?>
                                                    <li><?= htmlspecialchars($benefit) ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                            <a href="#" class="tier-upgrade-btn premium">Upgrade to Premium</a>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($current_tier !== 'premium_plus'): ?>
                                    <div class="col-md-6">
                                        <div class="tier-info-card premium-plus">
                                            <h6><?= renderSubscriptionBadge('premium_plus') ?> Upgrade Benefits</h6>
                                            <ul class="tier-features">
                                                <?php 
                                                $benefits = getTierUpgradeBenefits($current_tier, 'premium_plus');
                                                foreach ($benefits as $benefit): ?>
                                                    <li><?= htmlspecialchars($benefit) ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                            <a href="#" class="tier-upgrade-btn premium-plus">Upgrade to Premium+</a>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
            <?php endif; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 