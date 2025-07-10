<?php
require_once '../config/config.php';
session_start();

if (!isset($_GET['id'])) {
    die('No business ID specified.');
}

$id = (int)$_GET['id'];

// Get current is_featured value
$stmt = $pdo->prepare("SELECT is_featured FROM businesses WHERE id = ?");
$stmt->execute([$id]);
$business = $stmt->fetch();

if (!$business) {
    die('Business not found.');
}

$newValue = $business['is_featured'] ? 0 : 1;

// Update is_featured value
$update = $pdo->prepare("UPDATE businesses SET is_featured = ? WHERE id = ?");
$update->execute([$newValue, $id]);

// Redirect back to the referring page, or fallback to businesses.php
$redirect = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'businesses.php';
header('Location: ' . $redirect);
exit;
?>