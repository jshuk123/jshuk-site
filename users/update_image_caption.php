<?php
session_start();
header('Content-Type: application/json');
require_once '../config/config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$image_id = $_POST['image_id'] ?? 0;
$caption = $_POST['caption'] ?? '';
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

// Update caption
$upd = $pdo->prepare("UPDATE business_images SET caption = ? WHERE id = ?");
$upd->execute([$caption, $image_id]);

echo json_encode(['success' => true]); 