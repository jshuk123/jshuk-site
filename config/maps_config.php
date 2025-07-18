<?php
/**
 * JShuk Maps Configuration
 * 
 * This file manages map-related configuration including API keys and settings.
 * 
 * To use Stadia Maps (recommended for better performance):
 * 1. Sign up at https://stadiamaps.com
 * 2. Get your free API key from the dashboard
 * 3. Replace 'YOUR_STADIA_API_KEY_HERE' below with your actual key
 * 
 * If no API key is provided, the system will automatically fall back to free OpenStreetMap tiles.
 */

// Stadia Maps API Key (optional - for better performance)
// Get your free API key from: https://stadiamaps.com
define('STADIA_API_KEY', 'YOUR_STADIA_API_KEY_HERE'); // Replace with your actual API key

// Map default settings
define('DEFAULT_MAP_LAT', 51.5074);  // London latitude
define('DEFAULT_MAP_LNG', -0.1278);  // London longitude
define('DEFAULT_MAP_ZOOM', 10);      // Default zoom level

// Map tile provider settings
define('USE_STADIA_MAPS', !empty(STADIA_API_KEY) && STADIA_API_KEY !== 'YOUR_STADIA_API_KEY_HERE');

// Map styling options
define('MAP_TILE_STYLE', USE_STADIA_MAPS ? 'alidade_smooth' : 'osm'); // alidade_smooth, osm, osm_bright, etc.

/**
 * Get map configuration for JavaScript
 */
function getMapConfig() {
    return [
        'stadiaApiKey' => USE_STADIA_MAPS ? STADIA_API_KEY : null,
        'defaultLat' => DEFAULT_MAP_LAT,
        'defaultLng' => DEFAULT_MAP_LNG,
        'defaultZoom' => DEFAULT_MAP_ZOOM,
        'useStadiaMaps' => USE_STADIA_MAPS,
        'tileStyle' => MAP_TILE_STYLE
    ];
}

/**
 * Output map configuration as JavaScript
 */
function outputMapConfig() {
    $config = getMapConfig();
    echo '<script>window.JSHUK_CONFIG = ' . json_encode($config) . ';</script>';
}
?> 