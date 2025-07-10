<?php
// Define the directories we need
$directories = [
    'uploads',
    'uploads/businesses',
    'uploads/businesses/gallery',
    'uploads/businesses/products'
];

// Create directories if they don't exist
foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        if (mkdir($dir, 0777, true)) {
            echo "Created directory: {$dir}<br>";
        } else {
            echo "Failed to create directory: {$dir}<br>";
        }
    } else {
        // Make sure the directory is writable
        if (!is_writable($dir)) {
            chmod($dir, 0777);
            echo "Updated permissions for: {$dir}<br>";
        }
    }
}

echo "Setup complete!";
?> 