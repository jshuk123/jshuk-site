<?php
/**
 * Carousel Analytics API Endpoint
 * JShuk Advanced Carousel Management System
 * Phase 4: Analytics API
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/config.php';
require_once '../includes/enhanced_carousel_functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$response = ['success' => false, 'message' => '', 'data' => null];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Get JSON input
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            throw new Exception('Invalid JSON input');
        }
        
        $slideId = $input['slide_id'] ?? null;
        $eventType = $input['event_type'] ?? null;
        
        if (!$slideId || !$eventType) {
            throw new Exception('Missing required parameters: slide_id and event_type');
        }
        
        // Validate event type
        $validEventTypes = ['impression', 'click', 'hover'];
        if (!in_array($eventType, $validEventTypes)) {
            throw new Exception('Invalid event type. Must be one of: ' . implode(', ', $validEventTypes));
        }
        
        // Log the event
        $success = logCarouselEvent($pdo, $slideId, $eventType);
        
        if ($success) {
            $response['success'] = true;
            $response['message'] = 'Event logged successfully';
            $response['data'] = [
                'slide_id' => $slideId,
                'event_type' => $eventType,
                'timestamp' => date('Y-m-d H:i:s')
            ];
        } else {
            throw new Exception('Failed to log event');
        }
        
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Get analytics data
        $action = $_GET['action'] ?? '';
        
        switch ($action) {
            case 'performance':
                $days = (int) ($_GET['days'] ?? 30);
                $data = getCarouselPerformance($pdo, $days);
                $response['success'] = true;
                $response['data'] = $data;
                break;
                
            case 'stats':
                $data = getCarouselStats($pdo);
                $response['success'] = true;
                $response['data'] = $data;
                break;
                
            case 'slides':
                $zone = $_GET['zone'] ?? 'homepage';
                $location = $_GET['location'] ?? null;
                $limit = (int) ($_GET['limit'] ?? 10);
                $data = getCarouselSlides($pdo, $zone, $limit, $location);
                $response['success'] = true;
                $response['data'] = $data;
                break;
                
            case 'expiring':
                $days = (int) ($_GET['days'] ?? 7);
                $data = getExpiringSlides($pdo, $days);
                $response['success'] = true;
                $response['data'] = $data;
                break;
                
            default:
                throw new Exception('Invalid action. Valid actions: performance, stats, slides, expiring');
        }
        
    } else {
        throw new Exception('Method not allowed');
    }
    
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    http_response_code(400);
} catch (PDOException $e) {
    $response['success'] = false;
    $response['message'] = 'Database error occurred';
    error_log("Carousel Analytics API Error: " . $e->getMessage());
    http_response_code(500);
}

// Return JSON response
echo json_encode($response, JSON_PRETTY_PRINT);
?> 