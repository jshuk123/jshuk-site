<?php
/**
 * Sidebar Ads Partial
 * Renders ads in the sidebar zone with category targeting
 */

require_once($_SERVER['DOCUMENT_ROOT'].'/includes/ad_renderer.php');

// Get current category ID if on a category page
$categoryId = $_GET['cat_id'] ?? $_GET['category'] ?? null;

// Get user location from session if available
$userLocation = $_SESSION['user_location'] ?? null;

// Render the sidebar ad
echo renderAd('sidebar', $categoryId, $userLocation, [
    'show_label' => true,
    'class' => 'sidebar-ad-container',
    'style' => 'margin-bottom: 1.5rem;'
]);
?> 