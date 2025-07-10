<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/config.php';
require_once '../config/upload_helper.php';

// DEBUG: Output POST and REQUEST_METHOD immediately for troubleshooting
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo '<pre>';
    echo 'POST: ' . print_r($_POST, true);
    echo 'REQUEST_METHOD: ' . $_SERVER['REQUEST_METHOD'] . PHP_EOL;
    echo '</pre>';
    // exit(); // Uncomment to stop execution here for debugging
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /jshuk/auth/login.php');
    exit();
}

// Check if business ID was provided
if (!isset($_POST['business_id'])) {
    $_SESSION['error'] = "No business ID provided.";
    header('Location: /users/dashboard.php');
    exit();
}

$business_id = $_POST['business_id'];

// DEBUG: Output POST and SESSION data to browser
if (isset($_POST['debug'])) {
    echo '<pre>';
    echo "==== DELETE BUSINESS DEBUG ====\n";
    echo 'POST: ' . print_r($_POST, true);
    echo 'SESSION: ' . print_r($_SESSION, true);
    echo 'REQUEST_METHOD: ' . print_r($_SERVER['REQUEST_METHOD'], true);
    echo 'SCRIPT: ' . print_r(__FILE__, true);
    echo 'TIME: ' . date('Y-m-d H:i:s') . "\n";
    echo '</pre>';
    exit();
}

// DEBUG: Output POST and SESSION data
file_put_contents(__DIR__ . '/../logs/delete_debug.log', "\n==== DELETE BUSINESS DEBUG ====".PHP_EOL.
    'POST: '.print_r($_POST, true).
    'SESSION: '.print_r($_SESSION, true).
    'REQUEST_METHOD: '.print_r($_SERVER['REQUEST_METHOD'], true).
    'SCRIPT: '.print_r(__FILE__, true).
    'TIME: '.date('Y-m-d H:i:s').PHP_EOL, FILE_APPEND);

try {
    // Start transaction
    $pdo->beginTransaction();
    
    // First, get the business details to verify ownership and get info for directory deletion
    $stmt = $pdo->prepare("
        SELECT b.*, u.email 
        FROM businesses b 
        JOIN users u ON b.user_id = u.id 
        WHERE b.id = ? AND b.user_id = ?
    ");
    $stmt->execute([$business_id, $_SESSION['user_id']]);
    $business = $stmt->fetch();
    
    if (!$business) {
        throw new Exception("Business not found or you don't have permission to delete it.");
    }
    
    // Delete associated images from storage (main image from business_images table)
    $img_stmt = $pdo->prepare("SELECT file_path FROM business_images WHERE business_id = ? AND sort_order = 0 LIMIT 1");
    $img_stmt->execute([$business_id]);
    $main_image = $img_stmt->fetchColumn();
    if ($main_image) {
        $image_path = $_SERVER['DOCUMENT_ROOT'] . $main_image;
        if (file_exists($image_path)) {
            unlink($image_path);
        }
    }
    
    // Delete gallery images
    $gallery_stmt = $pdo->prepare("SELECT file_path FROM business_images WHERE business_id = ?");
    $gallery_stmt->execute([$business_id]);
    while ($image = $gallery_stmt->fetch()) {
        $image_path = $_SERVER['DOCUMENT_ROOT'] . $image['file_path'];
        if (file_exists($image_path)) {
            unlink($image_path);
        }
    }
    
    // Delete product images
    $product_stmt = $pdo->prepare("
        SELECT pi.image_path 
        FROM product_images pi 
        JOIN business_products bp ON pi.product_id = bp.id 
        WHERE bp.business_id = ?
    ");
    $product_stmt->execute([$business_id]);
    while ($image = $product_stmt->fetch()) {
        $image_path = $_SERVER['DOCUMENT_ROOT'] . $image['image_path'];
        if (file_exists($image_path)) {
            unlink($image_path);
        }
    }
    
    // Delete records from database in correct order
    // Delete product images
    $pdo->prepare("
        DELETE pi FROM product_images pi 
        JOIN business_products bp ON pi.product_id = bp.id 
        WHERE bp.business_id = ?
    ")->execute([$business_id]);
    
    // Delete products
    $pdo->prepare("DELETE FROM business_products WHERE business_id = ?")->execute([$business_id]);
    
    // Delete gallery images
    $pdo->prepare("DELETE FROM business_images WHERE business_id = ?")->execute([$business_id]);
    
    // Delete reviews
    $pdo->prepare("DELETE FROM reviews WHERE business_id = ?")->execute([$business_id]);
    
    // Finally, delete the business
    $pdo->prepare("DELETE FROM businesses WHERE id = ? AND user_id = ?")->execute([$business_id, $_SESSION['user_id']]);
    
    // Delete business directories
    $directories = createBusinessDirectories($business['email'], $business['business_name']);
    foreach ($directories as $dir) {
        if (is_dir($dir)) {
            array_map('unlink', glob("$dir/*.*"));
            rmdir($dir);
        }
    }
    
    $pdo->commit();
    $_SESSION['success'] = "Business deleted successfully.";
    
} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['error'] = "Error deleting business: " . $e->getMessage();
}

header('Location: /users/dashboard.php');
exit(); 