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

$action = $_POST['action'] ?? null;

// Process the testimonial update or moderation
try {
    $pdo->beginTransaction();

    if ($action === 'approve') {
        $stmt = $pdo->prepare("UPDATE testimonials SET status = 'approved', updated_at = NOW() WHERE id = ?");
        $stmt->execute([$testimonial_id]);
        $pdo->commit();
        $_SESSION['success'] = 'Testimonial approved!';
        header('Location: /jshuk/users/dashboard.php#testimonials-content');
        exit();
    } elseif ($action === 'hide') {
        $stmt = $pdo->prepare("UPDATE testimonials SET status = 'hidden', updated_at = NOW() WHERE id = ?");
        $stmt->execute([$testimonial_id]);
        $pdo->commit();
        $_SESSION['success'] = 'Testimonial hidden!';
        header('Location: /jshuk/users/dashboard.php#testimonials-content');
        exit();
    }

    // Handle image upload if provided
    $image_path = $testimonial['image_path'];
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $file_type = $_FILES['image']['type'];
        
        if (!in_array($file_type, $allowed_types)) {
            throw new Exception('Invalid file type. Please upload a valid image.');
        }
        
        $upload_dir = '../uploads/testimonials/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $file_name = uniqid('testimonial_') . '.' . $file_extension;
        $target_path = $upload_dir . $file_name;
        
        if (!move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
            throw new Exception('Failed to upload image.');
        }
        
        // Delete old image if exists
        if ($testimonial['image_path'] && file_exists('../' . ltrim($testimonial['image_path'], '/jshuk/'))) {
            unlink('../' . ltrim($testimonial['image_path'], '/jshuk/'));
        }
        
        $image_path = '/jshuk/uploads/testimonials/' . $file_name;
    }

    // Update testimonial
    $stmt = $pdo->prepare("
        UPDATE testimonials 
        SET author_name = ?,
            author_title = ?,
            content = ?,
            image_path = ?,
            rating = ?,
            is_featured = ?,
            updated_at = NOW()
        WHERE id = ?
    ");
    
    $stmt->execute([
        $_POST['author_name'],
        $_POST['author_title'] ?? null,
        $_POST['content'],
        $image_path,
        $_POST['rating'],
        isset($_POST['is_featured']) ? 1 : 0,
        $testimonial_id
    ]);

    $pdo->commit();
    $_SESSION['success'] = 'Testimonial updated successfully!';
    
} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['error'] = 'Error updating testimonial: ' . $e->getMessage();
    
    // Delete uploaded image if exists
    if (isset($target_path) && file_exists($target_path)) {
        unlink($target_path);
    }
}

header('Location: /jshuk/users/profile.php');
exit(); 