<?php
header('Content-Type: application/json');

// Define central coordinates for regions
$regions = [
    'london' => ['lat' => 51.509865, 'lon' => -0.118092],
    'manchester' => ['lat' => 53.483959, 'lon' => -2.244644],
    'gateshead' => ['lat' => 54.9500, 'lon' => -1.6000]
];

function haversine($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 6371; // in km
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat / 2) * sin($dLat / 2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon / 2) * sin($dLon / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return $earthRadius * $c;
}

$input = json_decode(file_get_contents('php://input'), true);
$lat = $input['latitude'] ?? null;
$lon = $input['longitude'] ?? null;

if (!$lat || !$lon) {
    echo json_encode(['error' => 'Invalid coordinates']);
    exit;
}

$closestRegion = null;
$shortestDistance = PHP_INT_MAX;

foreach ($regions as $region => $coords) {
    $distance = haversine($lat, $lon, $coords['lat'], $coords['lon']);
    if ($distance < $shortestDistance) {
        $shortestDistance = $distance;
        $closestRegion = $region;
    }
}

if ($closestRegion) {
    echo json_encode(['redirectUrl' => 'businesses.php?region=' . $closestRegion]);
} else {
    echo json_encode(['error' => 'Region not found']);
}
