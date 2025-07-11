<?php
/**
 * Handle item requests for free stuff
 */

header('Content-Type: application/json');
session_start();

require_once '../config/config.php';

// Only allow logged-in users
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in to request items.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

try {
    $classified_id = filter_input(INPUT_POST, 'classified_id', FILTER_VALIDATE_INT);
    $requester_name = trim($_POST['requester_name'] ?? '');
    $requester_contact = trim($_POST['requester_contact'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    // Validation
    if (!$classified_id) {
        throw new Exception('Invalid classified ID.');
    }
    
    if (empty($requester_name)) {
        throw new Exception('Name is required.');
    }
    
    if (empty($requester_contact)) {
        throw new Exception('Contact information is required.');
    }
    
    // Check if the classified exists and is free
    $stmt = $pdo->prepare("SELECT * FROM classifieds WHERE id = ? AND price = 0 AND is_active = 1");
    $stmt->execute([$classified_id]);
    $classified = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$classified) {
        throw new Exception('Item not found or not available for request.');
    }
    
    // Check if user is not requesting their own item
    if ($classified['user_id'] == $_SESSION['user_id']) {
        throw new Exception('You cannot request your own item.');
    }
    
    // Check if user has already requested this item
    $stmt = $pdo->prepare("SELECT id FROM free_stuff_requests WHERE classified_id = ? AND requester_id = ?");
    $stmt->execute([$classified_id, $_SESSION['user_id']]);
    if ($stmt->fetch()) {
        throw new Exception('You have already requested this item.');
    }
    
    // Insert the request
    $stmt = $pdo->prepare("
        INSERT INTO free_stuff_requests (classified_id, requester_id, requester_name, requester_contact, message, status, requested_at)
        VALUES (?, ?, ?, ?, ?, 'pending', NOW())
    ");
    
    if ($stmt->execute([$classified_id, $_SESSION['user_id'], $requester_name, $requester_contact, $message])) {
        echo json_encode([
            'success' => true, 
            'message' => 'Request sent successfully! The item owner will be notified.'
        ]);
    } else {
        throw new Exception('Failed to send request. Please try again.');
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?> 