<?php
session_start();
header('Content-Type: application/json');
require_once '../config/config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

// Get JSON data
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data || !is_array($data)) {
    echo json_encode(['success' => false, 'error' => 'Invalid data']);
    exit;
}

try {
    // Start transaction
    $pdo->beginTransaction();

    // Update each image's sort order
    $stmt = $pdo->prepare("UPDATE business_images SET sort_order = ? WHERE id = ?");
    
    foreach ($data as $image) {
        if (!isset($image['id']) || !isset($image['order'])) {
            throw new Exception('Invalid image data');
        }
        
        $stmt->execute([$image['order'], $image['id']]);
    }

    // Commit transaction
    $pdo->commit();
    
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?> 