<?php
session_start();
require_once '../config/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /jshuk/auth/login.php');
    exit;
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $review_id = $_POST['review_id'] ?? null;
    $rating = $_POST['rating'] ?? null;
    $comment = $_POST['comment'] ?? null;
    $user_id = $_SESSION['user_id'];

    // Validate inputs
    if (!$review_id || !$rating || !$comment) {
        $_SESSION['error'] = "All fields are required";
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit;
    }

    try {
        // Check if the review belongs to the user
        $check_stmt = $pdo->prepare("SELECT business_id FROM reviews WHERE id = ? AND user_id = ?");
        $check_stmt->execute([$review_id, $user_id]);
        $review = $check_stmt->fetch();

        if (!$review) {
            $_SESSION['error'] = "You don't have permission to edit this review";
            header("Location: " . $_SERVER['HTTP_REFERER']);
            exit;
        }

        // Update the review
        $stmt = $pdo->prepare("
            UPDATE reviews 
            SET rating = ?, comment = ?, updated_at = NOW()
            WHERE id = ? AND user_id = ?
        ");
        
        $stmt->execute([
            $rating,
            $comment,
            $review_id,
            $user_id
        ]);

        // Update business average rating
        $update_rating = $pdo->prepare("
            UPDATE businesses b
            SET average_rating = (
                SELECT AVG(rating)
                FROM reviews
                WHERE business_id = ?
            )
            WHERE id = ?
        ");
        $update_rating->execute([$review['business_id'], $review['business_id']]);

        $_SESSION['success'] = "Your review has been updated!";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error updating review. Please try again.";
    }

    // Redirect back to business page
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit;
} else {
    // If not POST request, redirect to home
    header('Location: /jshuk/index.php');
    exit;
} 