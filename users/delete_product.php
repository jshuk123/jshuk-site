<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit;
}

// Check if product ID was provided
if (!isset($_POST['product_id'])) {
    echo json_encode(['success' => false, 'message' => 'No product ID provided']);
    exit;
}

try {
    // Start transaction
    $pdo->beginTransaction();

    // Verify the product belongs to the user's business
    $verify_stmt = $pdo->prepare("
        SELECT p.id 
        FROM business_products p 
        JOIN businesses b ON p.business_id = b.id 
        WHERE p.id = ? AND b.user_id = ?
    ");
    $verify_stmt->execute([$_POST['product_id'], $_SESSION['user_id']]);
    
    if (!$verify_stmt->fetch()) {
        throw new Exception('Product not found or access denied');
    }

    // Get all image paths before deleting from database
    $get_images = $pdo->prepare("SELECT image_path FROM product_images WHERE product_id = ?");
    $get_images->execute([$_POST['product_id']]);
    $images = $get_images->fetchAll(PDO::FETCH_COLUMN);

    // Delete physical image files
    foreach ($images as $image_path) {
        if (file_exists($image_path)) {
            unlink($image_path);
        }
    }

    // Delete product images from database
    $delete_images = $pdo->prepare("DELETE FROM product_images WHERE product_id = ?");
    $delete_images->execute([$_POST['product_id']]);

    // Delete the product from database
    $delete_product = $pdo->prepare("DELETE FROM business_products WHERE id = ?");
    $delete_product->execute([$_POST['product_id']]);

    $pdo->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 