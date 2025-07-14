<?php
/**
 * Track Community Corner Click API
 * Increments click count for a community corner item
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/config.php';
require_once '../includes/community_corner_functions.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['item_id']) || !is_numeric($input['item_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid item_id']);
    exit;
}

$itemId = (int)$input['item_id'];

try {
    // Increment click count
    incrementCommunityCornerClicks($itemId);
    
    echo json_encode(['success' => true, 'message' => 'Click tracked successfully']);
    
} catch (Exception $e) {
    error_log("Error tracking community corner click: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
}
?> 