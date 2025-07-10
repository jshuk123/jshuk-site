<?php
/**
 * Home Carousel Ads Partial
 * Renders multiple ads for the carousel zone on the homepage
 */

require_once($_SERVER['DOCUMENT_ROOT'].'/includes/ad_renderer.php');

// Get user location from session if available
$userLocation = $_SESSION['user_location'] ?? null;

// Render multiple carousel ads (up to 5)
$carouselAds = renderMultipleAds('carousel', 5, null, $userLocation);

if (!empty($carouselAds)) {
    echo '<div class="carousel-ads-container">';
    foreach ($carouselAds as $adHtml) {
        echo $adHtml;
    }
    echo '</div>';
}
?> 