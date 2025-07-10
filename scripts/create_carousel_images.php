<?php
/**
 * Create Sample Carousel Images
 * This script generates placeholder images for the carousel
 */

// Create uploads/carousel directory if it doesn't exist
$upload_dir = '../uploads/carousel/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
    echo "📁 Created uploads/carousel/ directory\n";
}

// Sample carousel data
$carousel_data = [
    [
        'filename' => 'sample_ad1.jpg',
        'title' => 'Welcome to JShuk',
        'subtitle' => 'Your Jewish Community Hub',
        'gradient' => ['#667eea', '#764ba2']
    ],
    [
        'filename' => 'sample_ad2.jpg',
        'title' => 'Kosher Restaurants',
        'subtitle' => 'Find the best kosher dining',
        'gradient' => ['#f093fb', '#f5576c']
    ],
    [
        'filename' => 'sample_ad3.jpg',
        'title' => 'Community Events',
        'subtitle' => 'Stay connected with your community',
        'gradient' => ['#4facfe', '#00f2fe']
    ]
];

// Check if GD extension is available
if (!extension_loaded('gd')) {
    echo "❌ GD extension not available. Creating placeholder files instead.\n";
    
    foreach ($carousel_data as $data) {
        $placeholder_content = "Sample carousel image: {$data['title']}\nSubtitle: {$data['subtitle']}\nGenerated on: " . date('Y-m-d H:i:s');
        file_put_contents($upload_dir . $data['filename'] . '.txt', $placeholder_content);
        echo "📝 Created placeholder: {$data['filename']}.txt\n";
    }
    exit;
}

echo "✅ GD extension available. Creating carousel images...\n";

foreach ($carousel_data as $data) {
    $width = 1920;
    $height = 600;
    $image = imagecreatetruecolor($width, $height);
    
    // Create gradient background
    $color1 = hex2rgb($data['gradient'][0]);
    $color2 = hex2rgb($data['gradient'][1]);
    
    for ($i = 0; $i < $height; $i++) {
        $ratio = $i / $height;
        $red = $color1[0] + ($ratio * ($color2[0] - $color1[0]));
        $green = $color1[1] + ($ratio * ($color2[1] - $color1[1]));
        $blue = $color1[2] + ($ratio * ($color2[2] - $color1[2]));
        $color = imagecolorallocate($image, $red, $green, $blue);
        imageline($image, 0, $i, $width, $i, $color);
    }
    
    // Add overlay for better text readability
    $overlay = imagecolorallocatealpha($image, 0, 0, 0, 80);
    imagefilledrectangle($image, 0, 0, $width, $height, $overlay);
    
    // Add text
    $text_color = imagecolorallocate($image, 255, 255, 255);
    
    // Main title
    $title_size = 48;
    $title = $data['title'];
    $title_bbox = imagettfbbox($title_size, 0, 'arial.ttf', $title);
    $title_width = $title_bbox[4] - $title_bbox[0];
    $title_x = ($width - $title_width) / 2;
    $title_y = $height / 2 - 30;
    
    // Use imagestring if imagettfbbox fails
    if ($title_bbox[4] == 0) {
        imagestring($image, 5, $title_x, $title_y, $title, $text_color);
    } else {
        imagestring($image, 5, $title_x, $title_y, $title, $text_color);
    }
    
    // Subtitle
    $subtitle_size = 24;
    $subtitle = $data['subtitle'];
    $subtitle_bbox = imagettfbbox($subtitle_size, 0, 'arial.ttf', $subtitle);
    $subtitle_width = $subtitle_bbox[4] - $subtitle_bbox[0];
    $subtitle_x = ($width - $subtitle_width) / 2;
    $subtitle_y = $title_y + 60;
    
    if ($subtitle_bbox[4] == 0) {
        imagestring($image, 3, $subtitle_x, $subtitle_y, $subtitle, $text_color);
    } else {
        imagestring($image, 3, $subtitle_x, $subtitle_y, $subtitle, $text_color);
    }
    
    // Save image
    $filepath = $upload_dir . $data['filename'];
    imagejpeg($image, $filepath, 90);
    imagedestroy($image);
    
    echo "🖼️ Created: {$data['filename']}\n";
}

echo "✅ All carousel images created successfully!\n";

// Helper function to convert hex to RGB
function hex2rgb($hex) {
    $hex = str_replace('#', '', $hex);
    return [
        hexdec(substr($hex, 0, 2)),
        hexdec(substr($hex, 2, 2)),
        hexdec(substr($hex, 4, 2))
    ];
}
?> 