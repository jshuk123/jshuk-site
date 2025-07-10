<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Debug logging
error_log("delete_image.php started");
error_log("POST data: " . print_r($_POST, true));
error_log("Session data: " . print_r($_SESSION, true));

header('Content-Type: application/json');

// Define APP_DEBUG constant
define('APP_DEBUG', true);

// Include database connection
require_once(__DIR__ . '/../config/db_connect.php');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    error_log("User not logged in");
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

$image_id = $_POST['id'] ?? 0;
$user_id = $_SESSION['user_id'];

error_log("Image ID: " . $image_id);
error_log("User ID: " . $user_id);

// Get the image and check ownership
$stmt = $pdo->prepare("
    SELECT bi.image_path, b.user_id
    FROM business_images bi
    JOIN businesses b ON bi.business_id = b.id
    WHERE bi.id = ?
");
$stmt->execute([$image_id]);
$image = $stmt->fetch();

error_log("Image data: " . print_r($image, true));

if (!$image || $image['user_id'] != $user_id) {
    error_log("Not allowed - User ID mismatch or image not found");
    echo json_encode(['success' => false, 'error' => 'Not allowed']);
    exit;
}

// Delete the image record
$del_stmt = $pdo->prepare("DELETE FROM business_images WHERE id = ?");
$delete_result = $del_stmt->execute([$image_id]);
error_log("Database delete result: " . ($delete_result ? "success" : "failed"));

// Optionally, delete the file from the server
$image_path = $image['image_path'];
error_log("Image path: " . $image_path);

if ($image_path && file_exists($_SERVER['DOCUMENT_ROOT'] . $image_path)) {
    error_log("Attempting to delete file: " . $_SERVER['DOCUMENT_ROOT'] . $image_path);
    $file_delete_result = @unlink($_SERVER['DOCUMENT_ROOT'] . $image_path);
    error_log("File deletion result: " . ($file_delete_result ? "success" : "failed"));
} else {
    error_log("File does not exist or image_path is empty");
}

echo json_encode(['success' => true]);
?>