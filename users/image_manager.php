<?php
// Prevent any output before our JSON response
ob_start();

// Set JSON content type
header('Content-Type: application/json');

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__DIR__) . '/logs/php_errors.log');

require_once '../config/config.php';
require_once '../includes/ImageManager.php';

// Function to send JSON response and exit
function send_json_response($data) {
    ob_clean(); // Clear any previous output
    echo json_encode($data);
    exit;
}

// Error handler to catch any PHP errors/warnings
function error_handler($errno, $errstr, $errfile, $errline) {
    $error_message = "PHP Error: [$errno] $errstr in $errfile on line $errline";
    error_log($error_message);
    
    // Only send error response for actual errors, not warnings/notices
    if ($errno == E_ERROR || $errno == E_USER_ERROR) {
        send_json_response([
            'success' => false,
            'message' => 'Internal server error',
            'debug' => $error_message
        ]);
    }
    return true;
}

// Set custom error handler
set_error_handler('error_handler');

try {
    // Check if user is logged in
    session_start();
    if (!isset($_SESSION['user_id'])) {
        error_log("Unauthorized access attempt - no user_id in session");
        send_json_response(['success' => false, 'message' => 'Not authorized']);
    }

    // Get request data
    $input = file_get_contents('php://input');
    $jsonData = json_decode($input, true);
    
    if ($input && json_last_error() !== JSON_ERROR_NONE) {
        error_log("JSON decode error: " . json_last_error_msg());
        send_json_response(['success' => false, 'message' => 'Invalid JSON data']);
    }

    // Merge POST and JSON data
    $requestData = array_merge($_POST, $jsonData ?? []);
    
    error_log("Request data: " . print_r($requestData, true));
    if (isset($_FILES)) {
        error_log("Files data: " . print_r($_FILES, true));
    }

    // Get business ID
    $business_id = isset($requestData['business_id']) ? (int)$requestData['business_id'] : null;
    if (!$business_id) {
        error_log("Missing business_id in request");
        send_json_response(['success' => false, 'message' => 'Business ID required']);
    }

    // Verify business ownership
    try {
        $stmt = $pdo->prepare("SELECT id FROM businesses WHERE id = ? AND user_id = ?");
        $stmt->execute([$business_id, $_SESSION['user_id']]);
        if (!$stmt->fetch()) {
            send_json_response(['success' => false, 'message' => 'Not authorized']);
        }
    } catch (Exception $e) {
        error_log("Business ownership verification error: " . $e->getMessage());
        send_json_response(['success' => false, 'message' => 'Database error']);
    }

    // Initialize image manager with user context
    $imageManager = new ImageManager($pdo, $_SESSION['user_id']);

    // Handle different actions
    $action = $requestData['action'] ?? '';
    error_log("Processing action: $action with data: " . print_r($requestData, true));

    switch ($action) {
        case 'upload':
            // Handle file upload
            if (!isset($_FILES['image'])) {
                send_json_response(['success' => false, 'message' => 'No file uploaded']);
            }

            $type = $requestData['type'] ?? 'gallery';
            $result = $imageManager->uploadImage($_FILES['image'], $business_id, $type);
            send_json_response($result);
            break;

        case 'update':
            // Handle image update
            if (!isset($_FILES['image'])) {
                send_json_response(['success' => false, 'message' => 'No file uploaded']);
            }

            $image_id = isset($requestData['image_id']) ? (int)$requestData['image_id'] : null;
            if (!$image_id) {
                send_json_response(['success' => false, 'message' => 'Image ID required']);
            }

            $result = $imageManager->updateImage($_FILES['image'], $image_id, $business_id);
            send_json_response($result);
            break;

        case 'delete':
            // Handle image deletion
            $image_id = isset($requestData['image_id']) ? (int)$requestData['image_id'] : null;
            if (!$image_id) {
                send_json_response(['success' => false, 'message' => 'Image ID required']);
            }

            $result = $imageManager->deleteImage($image_id, $business_id);
            send_json_response($result);
            break;

        case 'update_order':
            // Handle order update
            $order_data = $requestData['order_data'] ?? null;
            if (!$order_data) {
                send_json_response(['success' => false, 'message' => 'Invalid order data']);
            }

            $result = $imageManager->updateOrder($business_id, $order_data);
            send_json_response($result);
            break;

        case 'set_main':
            // Handle setting main image
            $image_id = isset($requestData['image_id']) ? (int)$requestData['image_id'] : null;
            if (!$image_id) {
                send_json_response(['success' => false, 'message' => 'Image ID required']);
            }

            $result = $imageManager->setMainImage($image_id, $business_id);
            send_json_response($result);
            break;

        case 'get_gallery':
            // Handle getting gallery images
            $result = $imageManager->getGalleryImages($business_id);
            send_json_response($result);
            break;

        case 'get_main':
            // Handle getting main image
            $result = $imageManager->getMainImage($business_id);
            send_json_response($result);
            break;

        default:
            send_json_response(['success' => false, 'message' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    error_log("Unhandled exception: " . $e->getMessage());
    send_json_response([
        'success' => false,
        'message' => 'Internal server error'
    ]);
} 