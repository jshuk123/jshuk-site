<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Debug logging
error_log("delete_main_image.php started");
error_log("POST data: " . print_r($_POST, true));
error_log("Session data: " . print_r($_SESSION, true));

header('Content-Type: application/json');
require_once '../config/config.php';

if (!isset($_SESSION['user_id'])) {
    error_log("User not logged in");
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$business_id = $_POST['business_id'] ?? 0;
error_log("Business ID: " . $business_id);

if (!$business_id) {
    error_log("No business ID provided");
    echo json_encode(['success' => false, 'error' => 'No business id']);
    exit;
}

try {
    // Get the business and check ownership
    $stmt = $pdo->prepare("SELECT user_id, main_image FROM businesses WHERE id = ?");
    $stmt->execute([$business_id]);
    $biz = $stmt->fetch();
    
    error_log("Business data: " . print_r($biz, true));

    if (!$biz || $biz['user_id'] != $_SESSION['user_id']) {
        error_log("Not allowed - User ID mismatch or business not found");
        echo json_encode(['success' => false, 'error' => 'Not allowed']);
        exit;
    }

    // Delete the main image file
    $main_image = $biz['main_image'];
    error_log("Main image path: " . $main_image);
    
    if ($main_image && file_exists($_SERVER['DOCUMENT_ROOT'] . $main_image)) {
        error_log("Attempting to delete file: " . $_SERVER['DOCUMENT_ROOT'] . $main_image);
        $delete_result = @unlink($_SERVER['DOCUMENT_ROOT'] . $main_image);
        error_log("File deletion result: " . ($delete_result ? "success" : "failed"));
    } else {
        error_log("File does not exist or main_image is empty");
    }

    // Remove the main_image reference from the business
    $update_stmt = $pdo->prepare("UPDATE businesses SET main_image = NULL WHERE id = ?");
    $update_result = $update_stmt->execute([$business_id]);
    error_log("Database update result: " . ($update_result ? "success" : "failed"));

    // Optionally, set sort_order=0 for one business_images for this business (main), >0 for others
    // Example: set all to 1 (gallery), you may want to set a new main image elsewhere
    $reset_stmt = $pdo->prepare("UPDATE business_images SET sort_order = 1 WHERE business_id = ?");
    $reset_result = $reset_stmt->execute([$business_id]);
    error_log("Reset sort_order result: " . ($reset_result ? "success" : "failed"));

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    error_log("Error in delete_main_image.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} 