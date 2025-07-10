<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit();
}

// Check if image ID is provided
if (!isset($_POST['image_id'])) {
    echo json_encode(['success' => false, 'message' => 'No image ID provided']);
    exit();
}

try {
    // Get image info and verify ownership
    $stmt = $pdo->prepare("
        SELECT pi.*, bp.business_id 
        FROM product_images pi
        JOIN business_products bp ON pi.product_id = bp.id
        JOIN businesses b ON bp.business_id = b.id
        WHERE pi.id = ? AND b.user_id = ?
    ");
    $stmt->execute([$_POST['image_id'], $_SESSION['user_id']]);
    $image = $stmt->fetch();

    if (!$image) {
        echo json_encode(['success' => false, 'message' => 'Image not found or access denied']);
        exit();
    }

    // Start transaction
    $pdo->beginTransaction();

    // Delete the image file
    if (file_exists($image['image_path'])) {
        unlink($image['image_path']);
    }

    // Delete the database record
    $delete_stmt = $pdo->prepare("DELETE FROM product_images WHERE id = ?");
    $delete_stmt->execute([$_POST['image_id']]);

    $pdo->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 