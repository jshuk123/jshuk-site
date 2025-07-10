<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start logging
$log_file = '../logs/testimonial_submission.log';
function log_message($message) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] $message\n";
    file_put_contents($log_file, $log_entry, FILE_APPEND);
}

// Log initial request
log_message("=== Starting new testimonial submission ===");
log_message("Request Method: " . $_SERVER['REQUEST_METHOD']);
log_message("POST data: " . print_r($_POST, true));
log_message("FILES data: " . print_r($_FILES, true));

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/config.php';

// Test database connection
try {
    $pdo->query('SELECT 1');
    log_message("Database connection successful");
} catch (PDOException $e) {
    log_message("Database connection failed: " . $e->getMessage());
    $_SESSION['error'] = 'Database connection error. Please try again later.';
    header('Location: /jshuk/business.php?id=' . $_POST['business_id']);
    exit();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    log_message("Error: User not logged in");
    $_SESSION['error'] = 'Please login to add a testimonial.';
    header('Location: /jshuk/auth/login.php');
    exit();
}

log_message("User ID: " . $_SESSION['user_id']);

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    log_message("Error: Invalid request method - " . $_SERVER['REQUEST_METHOD']);
    $_SESSION['error'] = 'Invalid request method';
    header('Location: /jshuk/index.php');
    exit();
}

// Validate required fields
if (empty($_POST['business_id']) || empty($_POST['author_name']) || 
    empty($_POST['content']) || empty($_POST['rating'])) {
    log_message("Error: Missing required fields");
    $_SESSION['error'] = 'Please fill in all required fields';
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit();
}

$business_id = $_POST['business_id'];
log_message("Business ID: $business_id");

// Verify business ownership
$stmt = $pdo->prepare("SELECT user_id FROM businesses WHERE id = ?");
$stmt->execute([$business_id]);
$business = $stmt->fetch();

if (!$business || $business['user_id'] != $_SESSION['user_id']) {
    log_message("Error: Unauthorized access - Business user_id: " . ($business['user_id'] ?? 'not found'));
    $_SESSION['error'] = 'You do not have permission to add testimonials to this business';
    header('Location: /jshuk/business.php?id=' . $business_id);
    exit();
}

log_message("Business ownership verified");

// Validate rating
$rating = intval($_POST['rating']);
if ($rating < 1 || $rating > 5) {
    log_message("Error: Invalid rating value - $rating");
    $_SESSION['error'] = 'Invalid rating value';
    header('Location: /jshuk/business.php?id=' . $business_id);
    exit();
}

// Debug logging
file_put_contents('../debug_log.txt', "Business ID: $business_id\n", FILE_APPEND);

// Check subscription limits
$stmt = $pdo->prepare("
    SELECT COUNT(*) as count 
    FROM testimonials 
    WHERE business_id = ?
");
$stmt->execute([$business_id]);
$current_count = $stmt->fetchColumn();

// Get user's subscription limit
$stmt = $pdo->prepare("
    SELECT p.testimonial_limit
    FROM user_subscriptions s
    JOIN subscription_plans p ON s.plan_id = p.id
    WHERE s.user_id = ? 
    AND s.status IN ('active', 'trialing')
    ORDER BY s.created_at DESC 
    LIMIT 1
");
$stmt->execute([$_SESSION['user_id']]);
$subscription = $stmt->fetch();

$testimonial_limit = $subscription['testimonial_limit'] ?? 0;

if ($testimonial_limit !== null && $current_count >= $testimonial_limit) {
    $_SESSION['error'] = 'You have reached your testimonial limit. Please upgrade your plan to add more testimonials.';
    header('Location: /jshuk/business.php?id=' . $business_id);
    exit();
}

// Debug logging
file_put_contents('../debug_log.txt', "Subscription check passed\n", FILE_APPEND);

try {
    $pdo->beginTransaction();
    log_message("Started database transaction");

    // Handle image upload if provided
    $image_path = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        log_message("Processing image upload");
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $file_type = $_FILES['image']['type'];
        
        log_message("File type: " . $file_type);
        if (!in_array($file_type, $allowed_types)) {
            throw new Exception('Invalid file type. Please upload a valid image (JPEG, PNG, GIF, or WebP).');
        }
        
        $upload_dir = __DIR__ . '/../uploads/testimonials/';
        log_message("Upload directory: " . $upload_dir);
        
        if (!file_exists($upload_dir)) {
            log_message("Creating upload directory");
            if (!mkdir($upload_dir, 0777, true)) {
                $error = error_get_last();
                log_message("Failed to create directory. Error: " . print_r($error, true));
                throw new Exception('Failed to create upload directory.');
            }
            log_message("Created upload directory successfully");
        }
        
        $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $file_name = uniqid('testimonial_') . '.' . $file_extension;
        $target_path = $upload_dir . $file_name;
        
        log_message("Moving uploaded file to: " . $target_path);
        if (!move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
            $error = error_get_last();
            log_message("Failed to move uploaded file. Error: " . print_r($error, true));
            throw new Exception('Failed to upload image. Please try again.');
        }
        
        $image_path = '/jshuk/uploads/testimonials/' . $file_name;
        log_message("Image uploaded successfully: " . $image_path);
    }

    // Debug logging
    file_put_contents('../debug_log.txt', "About to insert testimonial with data: " . print_r([
        'business_id' => $business_id,
        'author_name' => $_POST['author_name'] ?? 'not set',
        'author_title' => $_POST['author_title'] ?? 'not set',
        'content' => $_POST['content'] ?? 'not set',
        'rating' => $_POST['rating'] ?? 'not set'
    ], true) . "\n", FILE_APPEND);

    // Insert testimonial
    log_message("Preparing to insert testimonial");
    $stmt = $pdo->prepare("
        INSERT INTO testimonials (
            business_id, 
            author_name, 
            author_title, 
            content, 
            image_path, 
            rating, 
            is_featured,
            status,
            created_at,
            updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW(), NOW())
    ");
    
    $params = [
        $business_id,
        trim($_POST['author_name']),
        !empty($_POST['author_title']) ? trim($_POST['author_title']) : null,
        trim($_POST['content']),
        $image_path,
        $rating,
        isset($_POST['is_featured']) ? 1 : 0
    ];
    
    log_message("Executing insert with params: " . print_r($params, true));
    try {
        $stmt->execute($params);
        log_message("Database insert successful");
    } catch (PDOException $e) {
        log_message("Database error: " . $e->getMessage());
        log_message("SQL State: " . $e->errorInfo[0]);
        log_message("Error Code: " . $e->errorInfo[1]);
        log_message("Error Message: " . $e->errorInfo[2]);
        throw $e;
    }
    
    $pdo->commit();
    log_message("Transaction committed successfully");
    $_SESSION['success'] = 'Testimonial added successfully!';
    file_put_contents('../debug_log.txt', "Testimonial inserted successfully\n", FILE_APPEND);
    
} catch (Exception $e) {
    $pdo->rollBack();
    log_message("Error occurred: " . $e->getMessage());
    log_message("Stack trace: " . $e->getTraceAsString());
    
    // Delete uploaded image if exists
    if ($image_path && file_exists($target_path)) {
        if (unlink($target_path)) {
            log_message("Deleted uploaded image due to error: " . $target_path);
        } else {
            $error = error_get_last();
            log_message("Failed to delete uploaded image: " . $target_path);
            log_message("Delete error: " . print_r($error, true));
        }
    }
    
    $_SESSION['error'] = 'Error adding testimonial: ' . $e->getMessage();
    file_put_contents('../debug_log.txt', "Error: " . $e->getMessage() . "\n", FILE_APPEND);
}

// Log final status and redirect
log_message("Redirecting to business page");
log_message("=== End testimonial submission ===\n");

// Redirect back to business page
header('Location: /jshuk/business.php?id=' . $business_id);
exit(); 