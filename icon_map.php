<?php
function getCategoryIcon($categoryName) {
    $map = [
        'home services' => 'fas fa-tools',
        'beauty personal care' => 'fas fa-spa',
        'events simcha services' => 'fas fa-glass-cheers',
        'transportation travel' => 'fas fa-bus',
        'real estate property' => 'fas fa-building',
        'cleaning services' => 'fas fa-broom',
        'printing design' => 'fas fa-print',
        'legal financial services' => 'fas fa-balance-scale',
        'education tutoring' => 'fas fa-graduation-cap',
        'marketing media' => 'fas fa-bullhorn',
        'food beverages' => 'fas fa-utensils',
        'retail' => 'fas fa-shopping-bag',
        'services' => 'fas fa-concierge-bell',
        'crafts handmade' => 'fas fa-paint-brush',
        'technology' => 'fas fa-laptop-code',
        'photography' => 'fas fa-camera-retro',
        'home garden' => 'fas fa-home',
        'childcare babysitting' => 'fas fa-baby',
        'clothing tailoring' => 'fas fa-tshirt',
        'health wellness' => 'fas fa-heartbeat',
        'other' => 'fas fa-ellipsis-h'
    ];

    $key = strtolower(trim($categoryName));
    $key = str_replace(['&', '-', '/'], '', $key);       // Remove special chars
    $key = preg_replace('/\s+/', '', $key);             // Normalize + collapse whitespace

    // Fuzzy match attempt
    foreach ($map as $raw => $icon) {
        $normalized = strtolower(trim($raw));
        $normalized = str_replace(['&', '-', '/'], '', $normalized);
        $normalized = preg_replace('/\s+/', '', $normalized);

        if (strpos($key, $normalized) !== false || strpos($normalized, $key) !== false) {
            echo "<!-- ICON MATCH: $categoryName => $icon -->\n";
            return $icon;
        }
    }

    echo "<!-- ICON MISSING: $categoryName => default -->\n";
    error_log('Missing icon for: ' . $categoryName);
    return 'fas fa-folder';
}
