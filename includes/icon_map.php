<?php
function getCategoryIcon($categoryName) {
    $map = [
        'home services' => 'mdi:tools',
        'beauty personal care' => 'mdi:spa',
        'events simcha services' => 'mdi:party-popper',
        'transportation travel' => 'mdi:bus',
        'real estate property' => 'mdi:office-building',
        'cleaning services' => 'mdi:broom',
        'printing design' => 'mdi:printer',
        'legal financial services' => 'mdi:scale-balance',
        'education tutoring' => 'mdi:school',
        'marketing media' => 'mdi:bullhorn',
        'food beverages' => 'mdi:silverware-fork-knife',
        'retail' => 'mdi:shopping',
        'services' => 'mdi:briefcase',
        'crafts handmade' => 'mdi:palette',
        'technology' => 'mdi:laptop',
        'photography' => 'mdi:camera',
        'home garden' => 'mdi:home-outline',
        'childcare babysitting' => 'mdi:baby-face-outline',
        'clothing tailoring' => 'mdi:tshirt-crew',
        'health wellness' => 'mdi:heart-pulse',
        'other' => 'mdi:dots-horizontal'
    ];

    $key = strtolower(trim($categoryName));
    $key = str_replace(['&', '-', '/'], '', $key);
    $key = preg_replace('/\\s+/', '', $key);

    foreach ($map as $raw => $icon) {
        $normalized = strtolower(trim($raw));
        $normalized = str_replace(['&', '-', '/'], '', $normalized);
        $normalized = preg_replace('/\\s+/', '', $normalized);

        if (strpos($key, $normalized) !== false || strpos($normalized, $key) !== false) {
            return '<span class="iconify" data-icon="' . $icon . '" data-width="28" data-height="28"></span>';
        }
    }

    return '<span class="iconify" data-icon="mdi:folder" data-width="28" data-height="28"></span>';
}
?>
