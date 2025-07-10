<?php
/**
 * Get Business Testimonials AJAX Handler
 * Returns testimonials and statistics for a specific business
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/config.php';

// Initialize response
$response = [
    'success' => false,
    'message' => '',
    'testimonials' => [],
    'stats' => [
        'pending' => 0,
        'approved' => 0,
        'hidden' => 0
    ]
];

try {
    // Validate request
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        throw new Exception('Invalid request method');
    }

    $business_id = (int)($_GET['business_id'] ?? 0);
    if (!$business_id) {
        throw new Exception('Business ID is required');
    }

    // Verify user owns this business
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('User not authenticated');
    }

    $stmt = $pdo->prepare("SELECT user_id FROM businesses WHERE id = ? AND status = 'active'");
    $stmt->execute([$business_id]);
    $business = $stmt->fetch();

    if (!$business || $business['user_id'] != $_SESSION['user_id']) {
        throw new Exception('Unauthorized access to business');
    }

    // Get testimonials
    $stmt = $pdo->prepare("
        SELECT id, name, testimonial, photo_url, rating, status, featured, submitted_at
        FROM testimonials 
        WHERE business_id = ? 
        ORDER BY featured DESC, submitted_at DESC
    ");
    $stmt->execute([$business_id]);
    $testimonials = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get statistics
    $stmt = $pdo->prepare("
        SELECT 
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
            SUM(CASE WHEN status = 'hidden' THEN 1 ELSE 0 END) as hidden
        FROM testimonials 
        WHERE business_id = ?
    ");
    $stmt->execute([$business_id]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    $response['success'] = true;
    $response['testimonials'] = $testimonials;
    $response['stats'] = [
        'pending' => (int)$stats['pending'],
        'approved' => (int)$stats['approved'],
        'hidden' => (int)$stats['hidden']
    ];

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
?> 