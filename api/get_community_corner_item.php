<?php
/**
 * Get Community Corner Item API
 * Returns a single community corner item by ID
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/config.php';

// Check if user is admin
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid item_id']);
    exit;
}

$itemId = (int)$_GET['id'];

try {
    $stmt = $pdo->prepare("
        SELECT id, title, body_text, type, emoji, link_url, link_text, 
               is_featured, is_active, priority, expire_date, date_added
        FROM community_corner 
        WHERE id = ?
    ");
    $stmt->execute([$itemId]);
    
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($item) {
        echo json_encode(['success' => true, 'item' => $item]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Item not found']);
    }
    
} catch (Exception $e) {
    error_log("Error fetching community corner item: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
?> 