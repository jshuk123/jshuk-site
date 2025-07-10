<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /jshuk/auth/login.php');
    exit();
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /jshuk/index.php');
    exit();
}

// Get testimonial ID
$testimonial_id = $_POST['testimonial_id'] ?? null;

if (!$testimonial_id) {
    $_SESSION['error'] = 'Invalid testimonial ID';
    header('Location: /jshuk/users/profile.php');
    exit();
}

// Verify testimonial ownership
$stmt = $pdo->prepare("
    SELECT t.*, b.user_id 
    FROM testimonials t
    JOIN businesses b ON t.business_id = b.id
    WHERE t.id = ?
");
$stmt->execute([$testimonial_id]);
$testimonial = $stmt->fetch();

if (!$testimonial || $testimonial['user_id'] != $_SESSION['user_id']) {
    $_SESSION['error'] = 'Unauthorized access';
    header('Location: /jshuk/users/profile.php');
    exit();
}

try {
    $pdo->beginTransaction();

    // Delete image if exists
    if ($testimonial['image_path'] && file_exists('../' . ltrim($testimonial['image_path'], '/jshuk/'))) {
        unlink('../' . ltrim($testimonial['image_path'], '/jshuk/'));
    }

    // Delete testimonial
    $stmt = $pdo->prepare("DELETE FROM testimonials WHERE id = ?");
    $stmt->execute([$testimonial_id]);

    $pdo->commit();
    $_SESSION['success'] = 'Testimonial deleted successfully!';
    
} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['error'] = 'Error deleting testimonial: ' . $e->getMessage();
}

header('Location: /jshuk/users/profile.php');
exit(); 