<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session first
if (session_status() === PHP_SESSION_NONE) session_start();

// Include security check
require_once 'config/security.php';

// Include configuration
require_once 'config/config.php';
require_once 'includes/ad_renderer.php';
require_once 'includes/subscription_functions.php';

$business_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$business_id) {
    header('Location: /businesses.php');
    exit;
}

$business = null;
$gallery_images = [];
$reviews = [];
$similar_businesses = [];

try {
    // Fetch business details with user subscription tier
    $stmt = $pdo->prepare("
        SELECT b.*, c.name as category_name, u.subscription_tier 
        FROM businesses b 
        LEFT JOIN business_categories c ON b.category_id = c.id 
        LEFT JOIN users u ON b.user_id = u.id
        WHERE b.id = ? AND b.status = 'active'
    ");
    $stmt->execute([$business_id]);
    $business = $stmt->fetch();

    if (!$business) {
        header('Location: /404.php');
        exit;
    }

    // Normalize business data for components
    $business['name'] = $business['business_name'];
    $business['category'] = $business['category_name'];
    $business['rating'] = $business['rating'] ?? 0;
    $business['review_count'] = $business['review_count'] ?? 0;
    
    // Parse contact info if it's JSON
    $contact_info = json_decode($business['contact_info'] ?? '{}', true);
    if (is_array($contact_info)) {
        $business['phone'] = $contact_info['phone'] ?? $business['phone'] ?? '';
        $business['email'] = $contact_info['email'] ?? $business['email'] ?? '';
        $business['website'] = $contact_info['website'] ?? $business['website'] ?? '';
        $business['address'] = $contact_info['address'] ?? $business['address'] ?? '';
    }
    
    // Parse social media if it's JSON
    $social_media = json_decode($business['social_media'] ?? '{}', true);
    if (is_array($social_media)) {
        $business['facebook'] = $social_media['facebook'] ?? '';
        $business['twitter'] = $social_media['twitter'] ?? '';
        $business['instagram'] = $social_media['instagram'] ?? '';
        $business['linkedin'] = $social_media['linkedin'] ?? '';
    }

    // Get business images
    $img_stmt = $pdo->prepare("SELECT * FROM business_images WHERE business_id = ? ORDER BY sort_order ASC");
    $img_stmt->execute([$business_id]);
    $business_images = $img_stmt->fetchAll();

    // Get business testimonials/reviews
    $testimonial_stmt = $pdo->prepare("
        SELECT * FROM testimonials 
        WHERE business_id = ? AND is_approved = 1 
        ORDER BY created_at DESC
    ");
    $testimonial_stmt->execute([$business_id]);
    $testimonials = $testimonial_stmt->fetchAll();

    // If no testimonials, try reviews table
    if (empty($testimonials)) {
        $review_stmt = $pdo->prepare("
            SELECT *, 'review' as type FROM reviews 
            WHERE business_id = ? AND is_approved = 1 
            ORDER BY created_at DESC
        ");
        $review_stmt->execute([$business_id]);
        $testimonials = $review_stmt->fetchAll();
    }

    // Fetch main image for this business
    $stmt = $pdo->prepare("SELECT file_path FROM business_images WHERE business_id = ? AND sort_order = 0 LIMIT 1");
    $stmt->execute([$business['id']]);
    $main_image = $stmt->fetchColumn();
    if ($main_image) {
        $business['main_image'] = basename($main_image);
    }

    // Similar businesses (with main image)
    $similar_businesses = [];
    if (!empty($business['category_id'])) {
        $similar_stmt = $pdo->prepare("SELECT b.id, b.business_name, b.description FROM businesses b WHERE b.category_id = ? AND b.id != ? AND b.status = 'active' LIMIT 3");
        $similar_stmt->execute([$business['category_id'], $business_id]);
        $similar_businesses = $similar_stmt->fetchAll();
        foreach ($similar_businesses as &$sim_biz) {
            $img_stmt = $pdo->prepare("SELECT file_path FROM business_images WHERE business_id = ? AND sort_order = 0 LIMIT 1");
            $img_stmt->execute([$sim_biz['id']]);
            $main_img = $img_stmt->fetchColumn();
            $sim_biz['main_image'] = $main_img ? BASE_PATH . ltrim($main_img, '/') : BASE_PATH . 'images/default-business.jpg';
        }
        unset($sim_biz);
    }

} catch (PDOException $e) {
    echo '<pre style="color:red;">PDO ERROR: ' . htmlspecialchars($e->getMessage()) . '</pre>';
    exit;
}

$pageTitle = htmlspecialchars($business['name']) . " | JShuk";
$page_css = "business.css";
include 'includes/header_main.php';
?>

<!-- Business Page Content -->
<div class="business-page">
    <!-- Include all business components -->
    <?php include 'components/business_hero.php'; ?>
    <?php include 'components/business_showcase.php'; ?>
    <?php include 'components/business_about.php'; ?>
    <?php include 'components/business_services.php'; ?>
    <?php include 'components/business_gallery.php'; ?>
    <?php include 'components/business_testimonials.php'; ?>
    <?php include 'components/business_contact.php'; ?>
</div>

<?php include 'includes/footer_main.php'; ?>
