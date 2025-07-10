<?php
/**
 * Footer Ad Partial
 * Renders ads in the footer zone
 */

require_once($_SERVER['DOCUMENT_ROOT'].'/includes/ad_renderer.php');

// Get user location from session if available
$userLocation = $_SESSION['user_location'] ?? null;

// Render the footer ad
echo renderAd('footer', null, $userLocation, [
    'show_label' => true,
    'class' => 'footer-ad-container',
    'style' => 'margin-top: 2rem; text-align: center;'
]);
?> 