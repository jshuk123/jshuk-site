<?php
/**
 * Ad Click Tracking Script
 * Logs ad clicks and redirects to target URL
 */

session_start();
require_once 'config/config.php';

// Get parameters
$adId = (int)($_GET['id'] ?? 0);
$targetUrl = $_GET['url'] ?? '';

// Validate parameters
if (!$adId || !$targetUrl) {
    header('Location: /');
    exit;
}

try {
    // $pdo is already available from config.php
    
    // Verify the ad exists and is active
    $stmt = $pdo->prepare("
        SELECT id, title, link_url, status, start_date, end_date 
        FROM ads 
        WHERE id = ? AND status = 'active' 
        AND start_date <= CURDATE() AND end_date >= CURDATE()
    ");
    $stmt->execute([$adId]);
    $ad = $stmt->fetch();

    if (!$ad) {
        // Ad not found or not active, redirect to target URL anyway
        header('Location: ' . $targetUrl);
        exit;
    }

    // Log the click
    $today = date('Y-m-d');
    
    // Try to update existing record
    $stmt = $pdo->prepare("
        INSERT INTO ad_stats (ad_id, date, clicks) 
        VALUES (:ad_id, :date, 1)
        ON DUPLICATE KEY UPDATE clicks = clicks + 1
    ");
    
    $stmt->execute([
        ':ad_id' => $adId,
        ':date' => $today
    ]);
    
    // Also update the main ads table clicks count
    $stmt = $pdo->prepare("UPDATE ads SET clicks = clicks + 1 WHERE id = ?");
    $stmt->execute([$adId]);

    // Log admin action for tracking
    if (isset($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("INSERT INTO admin_logs (admin_id, action, table_name, record_id, details, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_SESSION['user_id'],
            'CLICK',
            'ads',
            $adId,
            "Ad clicked: {$ad['title']}",
            $_SERVER['REMOTE_ADDR']
        ]);
    }

    // Redirect to target URL
    header('Location: ' . $targetUrl);
    exit;

} catch (PDOException $e) {
    // Silently fail for analytics - don't break the redirect
    error_log("Failed to log ad click: " . $e->getMessage());
    header('Location: ' . $targetUrl);
    exit;
}
?> 