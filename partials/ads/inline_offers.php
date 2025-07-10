<?php
/**
 * Inline Offers Partial
 * Renders ads in the inline zone for business pages and content areas
 */

require_once($_SERVER['DOCUMENT_ROOT'].'/includes/ad_renderer.php');

// Get current category ID if on a category page
$categoryId = $_GET['cat_id'] ?? $_GET['category'] ?? null;

// Get user location from session if available
$userLocation = $_SESSION['user_location'] ?? null;

// Get business ID if on a business page
$businessId = $_GET['business_id'] ?? null;

// Render the inline ad
echo renderAd('inline', $categoryId, $userLocation, [
    'show_label' => true,
    'class' => 'inline-ad-container',
    'style' => 'margin: 2rem 0; text-align: center;'
]);
?> 