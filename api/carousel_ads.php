<?php
/**
 * Carousel Ads API Endpoint
 * Returns JSON data of active carousel ads
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Include configuration
require_once '../config/config.php';

try {
    // Fetch active carousel ads
    $stmt = $pdo->prepare("
        SELECT id, title, subtitle, image_path, cta_text, cta_url, position, created_at
        FROM carousel_ads 
        WHERE active = 1 AND (expires_at IS NULL OR expires_at > NOW())
        ORDER BY position ASC, created_at DESC
        LIMIT 10
    ");
    $stmt->execute();
    $ads = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return success response
    echo json_encode([
        'success' => true,
        'data' => $ads,
        'count' => count($ads),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (PDOException $e) {
    // Return error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error',
        'message' => defined('APP_DEBUG') && APP_DEBUG ? $e->getMessage() : 'Internal server error',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?> 