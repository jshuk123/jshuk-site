<?php
/**
 * Mark item as taken
 */

header('Content-Type: application/json');
session_start();

require_once '../config/config.php';

// Only allow logged-in users
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $classified_id = filter_var($input['classified_id'] ?? null, FILTER_VALIDATE_INT);
    
    if (!$classified_id) {
        throw new Exception('Invalid classified ID.');
    }
    
    // Check if the classified exists and user owns it
    $stmt = $pdo->prepare("SELECT * FROM classifieds WHERE id = ? AND user_id = ? AND is_active = 1");
    $stmt->execute([$classified_id, $_SESSION['user_id']]);
    $classified = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$classified) {
        throw new Exception('Item not found or you do not have permission to modify it.');
    }
    
    // Update the status to claimed
    $stmt = $pdo->prepare("UPDATE classifieds SET status = 'claimed' WHERE id = ?");
    
    if ($stmt->execute([$classified_id])) {
        echo json_encode([
            'success' => true, 
            'message' => 'Item marked as taken successfully!'
        ]);
    } else {
        throw new Exception('Failed to update item status. Please try again.');
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?> 