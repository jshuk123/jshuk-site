<?php
/**
 * Carousel Ads API Endpoint
 * Returns JSON data of active carousel slides
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Include configuration
require_once '../config/config.php';

try {
    // Check if sponsored column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM carousel_slides LIKE 'sponsored'");
    $hasSponsoredColumn = $stmt->rowCount() > 0;
    
    // Build ORDER BY clause based on available columns
    $orderBy = "priority DESC";
    if ($hasSponsoredColumn) {
        $orderBy .= ", sponsored DESC";
    }
    $orderBy .= ", created_at DESC";
    
    // Fetch active carousel slides
    $stmt = $pdo->prepare("
        SELECT id, title, subtitle, image_url, cta_text, cta_link, priority, created_at
        FROM carousel_slides 
        WHERE active = 1 AND (start_date IS NULL OR start_date <= CURDATE()) AND (end_date IS NULL OR end_date >= CURDATE())
        ORDER BY {$orderBy}
        LIMIT 10
    ");
    $stmt->execute();
    $slides = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return success response
    echo json_encode([
        'success' => true,
        'data' => $slides,
        'count' => count($slides),
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