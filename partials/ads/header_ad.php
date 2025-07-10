<?php
/**
 * Header Ad Partial
 * Renders ads in the header zone
 */

require_once($_SERVER['DOCUMENT_ROOT'].'/includes/ad_renderer.php');

// Debug output if requested
/* if (isset($_GET['debug_ads'])) {
    echo "<!-- Header Ad Partial Debug: renderAd function exists: " . (function_exists('renderAd') ? 'Yes' : 'No') . " -->";
} */

echo renderAd('header');
?> 