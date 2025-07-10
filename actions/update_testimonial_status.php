<?php
/**
 * Update Testimonial Status AJAX Handler
 * Approves or hides testimonials with subscription tier validation
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
    $status = $input['status'] ?? '';

    if (!$testimonial_id) {
        throw new Exception('Testimonial ID is required');
    }

    if (!in_array($status, ['pending', 'approved', 'hidden'])) {
        throw new Exception('Invalid status');
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

    // If trying to approve, check subscription limits
    if ($status === 'approved') {
        // Get user's subscription info
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

        // Check if limit applies and if we're at the limit
        if ($testimonial_limit !== null) {
            $stmt = $pdo->prepare("
                SELECT COUNT(*) FROM testimonials 
                WHERE business_id = ? AND status = 'approved'
            ");
            $stmt->execute([$testimonial['business_id']]);
            $current_approved = $stmt->fetchColumn();

            // If this testimonial is already approved, don't count it twice
            if ($testimonial['status'] !== 'approved') {
                $current_approved++;
            }

            if ($current_approved > $testimonial_limit) {
                throw new Exception("Cannot approve testimonial. You have reached your limit of {$testimonial_limit} approved testimonials.");
            }
        }
    }

    // Update testimonial status
    $stmt = $pdo->prepare("
        UPDATE testimonials 
        SET status = ? 
        WHERE id = ?
    ");
    $stmt->execute([$status, $testimonial_id]);

    $response['success'] = true;
    $response['message'] = 'Testimonial status updated successfully';

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
?> 