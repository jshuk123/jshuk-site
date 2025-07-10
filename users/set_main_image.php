<?php
session_start();
header('Content-Type: application/json');
require_once '../config/config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$image_id = $_POST['image_id'] ?? 0;
if (!$image_id) {
    echo json_encode(['success' => false, 'error' => 'No image id']);
    exit;
}

// Get the image and check ownership
$stmt = $pdo->prepare("SELECT bi.business_id, b.user_id FROM business_images bi JOIN businesses b ON bi.business_id = b.id WHERE bi.id = ?");
$stmt->execute([$image_id]);
$image = $stmt->fetch();
if (!$image || $image['user_id'] != $_SESSION['user_id']) {
    echo json_encode(['success' => false, 'error' => 'Not allowed']);
    exit;
}

// Set all images for this business to sort_order=1, then set this one to sort_order=0
$pdo->prepare("UPDATE business_images SET sort_order = 1 WHERE business_id = ?")->execute([$image['business_id']]);
$pdo->prepare("UPDATE business_images SET sort_order = 0 WHERE id = ?")->execute([$image_id]);

// Get the file_path for the selected image
$image_stmt = $pdo->prepare("SELECT file_path FROM business_images WHERE id = ?");
$image_stmt->execute([$image_id]);
$image_row = $image_stmt->fetch();

if ($image_row) {
    // Update the main_image field in businesses (if still used)
    $pdo->prepare("UPDATE businesses SET main_image = ? WHERE id = ?")->execute([$image_row['file_path'], $image['business_id']]);
}

echo json_encode(['success' => true]); 