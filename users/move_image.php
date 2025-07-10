<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

// Define APP_DEBUG constant
define('APP_DEBUG', true);

// Include database connection
require_once('../config/config.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

// Initialize database connection
try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Get JSON data
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!isset($data['image_id']) || !isset($data['direction']) || !in_array($data['direction'], ['up', 'down'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid request data']);
    exit;
}

try {
    // Start transaction
    $pdo->beginTransaction();

    // Get current image info
    $stmt = $pdo->prepare("SELECT * FROM business_images WHERE id = ?");
    $stmt->execute([$data['image_id']]);
    $current = $stmt->fetch();

    if (!$current) {
        throw new Exception('Image not found');
    }

    // Get adjacent image based on direction
    if ($data['direction'] === 'up') {
        $stmt = $pdo->prepare("
            SELECT * FROM business_images 
            WHERE business_id = ? AND sort_order < ? 
            ORDER BY sort_order DESC LIMIT 1
        ");
    } else {
        $stmt = $pdo->prepare("
            SELECT * FROM business_images 
            WHERE business_id = ? AND sort_order > ? 
            ORDER BY sort_order ASC LIMIT 1
        ");
    }
    $stmt->execute([$current['business_id'], $current['sort_order']]);
    $adjacent = $stmt->fetch();

    if ($adjacent) {
        // Swap sort orders
        $stmt = $pdo->prepare("
            UPDATE business_images 
            SET sort_order = CASE
                WHEN id = ? THEN ?
                WHEN id = ? THEN ?
                END
            WHERE id IN (?, ?)
        ");
        $stmt->execute([
            $current['id'], $adjacent['sort_order'],
            $adjacent['id'], $current['sort_order'],
            $current['id'], $adjacent['id']
        ]);
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