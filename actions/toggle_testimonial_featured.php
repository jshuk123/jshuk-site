<?php
/**
 * Toggle Testimonial Featured Status AJAX Handler
 * Toggles the featured status of approved testimonials
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/config.php';

// Initialize response
$response = [
    'success' => false,
    'message' => ''
];

try {
    // Validate request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }

    $testimonial_id = (int)($input['testimonial_id'] ?? 0);

    if (!$testimonial_id) {
        throw new Exception('Testimonial ID is required');
    }

    // Verify user is authenticated
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('User not authenticated');
    }

    // Get testimonial and verify ownership
    $stmt = $pdo->prepare("
        SELECT t.*, b.user_id, b.business_name
        FROM testimonials t
        JOIN businesses b ON t.business_id = b.id
        WHERE t.id = ?
    ");
    $stmt->execute([$testimonial_id]);
    $testimonial = $stmt->fetch();

    if (!$testimonial) {
        throw new Exception('Testimonial not found');
    }

    if ($testimonial['user_id'] != $_SESSION['user_id']) {
        throw new Exception('Unauthorized access to testimonial');
    }

    // Only allow featuring approved testimonials
    if ($testimonial['status'] !== 'approved') {
        throw new Exception('Only approved testimonials can be featured');
    }

    // Toggle featured status
    $new_featured = $testimonial['featured'] ? 0 : 1;
    
    $stmt = $pdo->prepare("
        UPDATE testimonials 
        SET featured = ? 
        WHERE id = ?
    ");
    $stmt->execute([$new_featured, $testimonial_id]);

    $response['success'] = true;
    $response['message'] = $new_featured ? 'Testimonial featured successfully' : 'Testimonial unfeatured successfully';

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
?> 