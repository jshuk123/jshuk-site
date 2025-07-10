<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/config.php';

$user_id = $_GET['id'] ?? null;

if (!$user_id) {
    header('Location: /jshuk/index.php');
    exit();
}

// Get user's basic info
$stmt = $pdo->prepare("
    SELECT id, username, first_name, last_name, profile_image, business_name
    FROM users 
    WHERE id = ?
");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: /jshuk/index.php');
    exit();
}

// Get user's businesses
$stmt = $pdo->prepare("
    SELECT b.*, c.name as category_name,
           (SELECT COUNT(*) FROM reviews r WHERE r.business_id = b.id) as review_count,
           (SELECT COALESCE(AVG(rating), 0) FROM reviews r WHERE r.business_id = b.id) as average_rating
    FROM businesses b
    LEFT JOIN business_categories c ON b.category_id = c.id
    WHERE b.user_id = ? AND b.status = 'active'
    ORDER BY b.created_at DESC
");
$stmt->execute([$user_id]);
$businesses = $stmt->fetchAll();

// Fetch main image for each business
$img_stmt = $pdo->prepare("SELECT file_path FROM business_images WHERE business_id = ? AND sort_order = 0 LIMIT 1");
foreach ($businesses as &$business) {
    $img_stmt->execute([$business['id']]);
    $main_image_path = $img_stmt->fetchColumn();
    $business['main_image'] = $main_image_path ? $main_image_path : '/images/default-business.jpg';
}
unset($business);

// Get user's subscription info
$stmt = $pdo->prepare("
    SELECT s.*, p.name as plan_name
    FROM user_subscriptions s
    JOIN subscription_plans p ON s.plan_id = p.id
    WHERE s.user_id = ? 
    AND s.status IN ('active', 'trialing')
    ORDER BY s.created_at DESC 
    LIMIT 1
");
$stmt->execute([$user['id']]);
$subscription = $stmt->fetch();

// Set default visibility flags
$show_phone = false;
$show_address = false;
$show_whatsapp = false;
$show_full_description = false;

// Update visibility based on subscription
if ($subscription) {
    if ($subscription['plan_name'] === 'Premium' || $subscription['plan_name'] === 'Premium Plus') {
        $show_phone = true;
        $show_address = true;
        $show_whatsapp = true;
        $show_full_description = true;
    }
}

$pageTitle = htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) . "'s Profile";
$page_css = "public_profile.css";
include '../includes/header_main.php';
?>

<div class="container py-5">
    <!-- User Profile Header -->
    <div class="profile-header mb-5">
        <div class="text-center">
            <img src="<?php echo htmlspecialchars($user['profile_image'] ?? '/jshuk/images/default-avatar.jpg'); ?>" 
                 alt="Profile Image" 
                 class="profile-image mb-3">
            <h2><?php echo htmlspecialchars($user['business_name']); ?></h2>
            <p class="text-muted">
                <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
            </p>
        </div>
    </div>

    <!-- Businesses Grid -->
    <h3 class="mb-4">Businesses</h3>
    <div class="row g-4">
        <?php foreach ($businesses as $business): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 business-card">
                    <img src="<?php echo htmlspecialchars($business['main_image']); ?>" 
                         class="card-img-top business-image" 
                         alt="<?php echo htmlspecialchars($business['business_name']); ?>">
                    <div class="card-body">
                        <h5 class="card-title">
                            <a href="/jshuk/business.php?id=<?php echo urlencode($business['id']); ?>" 
                               class="text-decoration-none text-dark">
                                <?php echo htmlspecialchars($business['business_name']); ?>
                            </a>
                        </h5>
                        <span class="badge bg-primary-subtle text-primary mb-2">
                            <i class="fas fa-tag me-1"></i>
                            <?php echo htmlspecialchars($business['category_name']); ?>
                        </span>
                        <div class="business-details">
                            <?php if ($show_full_description): ?>
                                <p class="description"><?php echo nl2br(htmlspecialchars($business['description'])); ?></p>
                            <?php else: ?>
                                <p class="description"><?php echo nl2br(htmlspecialchars(substr($business['description'], 0, 500))); ?></p>
                            <?php endif; ?>

                            <div class="contact-info">
                                <!-- Always show email and website -->
                                <?php if (!empty($business['email'])): ?>
                                    <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($business['email']); ?></p>
                                <?php endif; ?>
                                
                                <?php if (!empty($business['website'])): ?>
                                    <p><i class="fas fa-globe"></i> <a href="<?php echo htmlspecialchars($business['website']); ?>" target="_blank"><?php echo htmlspecialchars($business['website']); ?></a></p>
                                <?php endif; ?>

                                <!-- Show phone and address only for Premium and above -->
                                <?php if ($show_phone && !empty($business['phone'])): ?>
                                    <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($business['phone']); ?></p>
                                <?php endif; ?>
                                
                                <?php if ($show_address && !empty($business['address'])): ?>
                                    <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($business['address']); ?></p>
                                <?php endif; ?>

                                <!-- Show WhatsApp button only for Premium and above -->
                                <?php if ($show_whatsapp && !empty($business['contact_info'])): ?>
                                    <?php $contact_info = json_decode($business['contact_info'], true); ?>
                                    <?php if (!empty($contact_info['whatsapp'])): ?>
                                        <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $contact_info['whatsapp']); ?>" 
                                           class="btn btn-success btn-sm" target="_blank">
                                            <i class="fab fa-whatsapp"></i> WhatsApp
                                        </a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="rating">
                                <i class="fas fa-star text-warning"></i>
                                <span><?php echo number_format($business['average_rating'], 1); ?></span>
                                <small class="text-muted">(<?php echo $business['review_count']; ?>)</small>
                            </div>
                            <a href="/jshuk/business.php?id=<?php echo urlencode($business['id']); ?>" 
                               class="btn btn-sm btn-outline-primary">
                                View Details
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include '../includes/footer_main.php'; ?> 