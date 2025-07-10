<?php
/**
 * JShuk Review Submission Handler
 * Handles both star ratings and testimonials based on subscription tier
 * Version: 1.2
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/config.php';
require_once 'includes/subscription_functions.php';

// Log function for debugging
function log_review_submission($message) {
    $log_file = __DIR__ . '/logs/review_submission.log';
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] $message\n";
    file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}

// Initialize response
$response = [
    'success' => false,
    'message' => '',
    'data' => null
];

try {
    // Validate request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Validate required fields
    $required_fields = ['business_id', 'rating'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    $business_id = (int)$_POST['business_id'];
    $rating = (int)$_POST['rating'];
    $name = trim($_POST['name'] ?? '');
    $testimonial = trim($_POST['testimonial'] ?? '');
    $photo = $_FILES['photo'] ?? null;

    // Validate rating
    if ($rating < 1 || $rating > 5) {
        throw new Exception('Rating must be between 1 and 5');
    }

    // Validate business exists
    $stmt = $pdo->prepare("SELECT id, business_name, user_id FROM businesses WHERE id = ? AND status = 'active'");
    $stmt->execute([$business_id]);
    $business = $stmt->fetch();

    if (!$business) {
        throw new Exception('Business not found or inactive');
    }

    // Get client IP and user agent
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

    log_review_submission("Review submission started for business ID: $business_id, IP: $ip_address");

    // Check for duplicate reviews from same IP within 12 hours
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM reviews 
        WHERE business_id = ? AND ip_address = ? 
        AND submitted_at > DATE_SUB(NOW(), INTERVAL 12 HOUR)
    ");
    $stmt->execute([$business_id, $ip_address]);
    $duplicate_count = $stmt->fetchColumn();

    if ($duplicate_count > 0) {
        throw new Exception('You have already submitted a review for this business in the last 12 hours');
    }

    // Start transaction
    $pdo->beginTransaction();

    // ALWAYS save the star rating (no subscription check needed)
    $stmt = $pdo->prepare("
        INSERT INTO reviews (business_id, rating, ip_address, user_agent, submitted_at)
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$business_id, $rating, $ip_address, $user_agent]);
    $review_id = $pdo->lastInsertId();

    log_review_submission("Star rating saved with ID: $review_id");

    // Handle testimonial submission (subscription-dependent)
    $testimonial_id = null;
    if (!empty($testimonial)) {
        // Get business owner's subscription tier
        $stmt = $pdo->prepare("
            SELECT u.subscription_tier, p.testimonial_limit
            FROM businesses b
            JOIN users u ON b.user_id = u.id
            LEFT JOIN user_subscriptions us ON u.id = us.user_id AND us.status IN ('active', 'trialing')
            LEFT JOIN subscription_plans p ON us.plan_id = p.id
            WHERE b.id = ?
        ");
        $stmt->execute([$business_id]);
        $subscription_info = $stmt->fetch();

        $subscription_tier = $subscription_info['subscription_tier'] ?? 'basic';
        $testimonial_limit = $subscription_info['testimonial_limit'] ?? 0;

        log_review_submission("Business subscription tier: $subscription_tier, limit: $testimonial_limit");

        // Check if testimonials are allowed for this tier
        if ($subscription_tier === 'basic') {
            log_review_submission("Basic tier - testimonials not allowed");
            $response['message'] = 'Your rating was submitted successfully. Testimonials are only available for Premium and Premium Plus plans.';
        } else {
            // Check testimonial limit
            $stmt = $pdo->prepare("
                SELECT COUNT(*) FROM testimonials 
                WHERE business_id = ? AND status = 'approved'
            ");
            $stmt->execute([$business_id]);
            $current_testimonials = $stmt->fetchColumn();

            if ($testimonial_limit !== null && $current_testimonials >= $testimonial_limit) {
                log_review_submission("Testimonial limit reached: $current_testimonials/$testimonial_limit");
                $response['message'] = 'Your rating was submitted successfully. This business has reached their testimonial limit.';
            } else {
                // Process testimonial submission
                $photo_url = null;

                // Handle photo upload if provided
                if ($photo && $photo['error'] === UPLOAD_ERR_OK) {
                    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                    $file_type = $photo['type'];
                    
                    if (!in_array($file_type, $allowed_types)) {
                        throw new Exception('Invalid file type. Please upload a valid image.');
                    }
                    
                    $upload_dir = __DIR__ . '/uploads/testimonials/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    $file_extension = strtolower(pathinfo($photo['name'], PATHINFO_EXTENSION));
                    $file_name = uniqid('testimonial_') . '.' . $file_extension;
                    $target_path = $upload_dir . $file_name;
                    
                    if (move_uploaded_file($photo['tmp_name'], $target_path)) {
                        $photo_url = '/uploads/testimonials/' . $file_name;
                    }
                }

                // Insert testimonial
                $stmt = $pdo->prepare("
                    INSERT INTO testimonials (business_id, name, testimonial, photo_url, rating, status, submitted_at)
                    VALUES (?, ?, ?, ?, ?, 'pending', NOW())
                ");
                $stmt->execute([$business_id, $name, $testimonial, $photo_url, $rating]);
                $testimonial_id = $pdo->lastInsertId();

                log_review_submission("Testimonial saved with ID: $testimonial_id");
                $response['message'] = 'Your rating and testimonial were submitted successfully. Testimonials appear once approved by the business.';
            }
        }
    } else {
        $response['message'] = 'Your rating was submitted successfully.';
    }

    // Commit transaction
    $pdo->commit();

    // Update business rating statistics
    updateBusinessRatingStats($business_id, $pdo);

    $response['success'] = true;
    $response['data'] = [
        'review_id' => $review_id,
        'testimonial_id' => $testimonial_id,
        'business_id' => $business_id
    ];

    log_review_submission("Review submission completed successfully");

} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    log_review_submission("Error: " . $e->getMessage());
    $response['message'] = $e->getMessage();
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);

/**
 * Update business rating statistics
 */
function updateBusinessRatingStats($business_id, $pdo) {
    try {
        // This could be implemented as a stored procedure or trigger
        // For now, we'll just log that it should be updated
        log_review_submission("Business rating stats should be updated for business ID: $business_id");
    } catch (Exception $e) {
        log_review_submission("Error updating rating stats: " . $e->getMessage());
    }
}
?> 