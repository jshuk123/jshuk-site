<?php

// Function to create business directories
function createBusinessDirectories($email, $business_name) {
    // Base upload directory
    $base_upload_dir = __DIR__ . '/../uploads';
    
    // Create businesses directory if it doesn't exist
    $businesses_dir = $base_upload_dir . '/businesses/';
    if (!is_dir($businesses_dir)) {
        mkdir($businesses_dir, 0777, true);
    }
    
    // Create business directory using sanitized business name
    $business_dir = $businesses_dir . sanitizeDirectoryName($business_name) . '_' . md5($email) . '/';
    if (!is_dir($business_dir)) {
        mkdir($business_dir, 0777, true);
    }
    
    // Create subdirectories
    $dirs = [
        'images' => $business_dir . 'images/',
        'products' => $business_dir . 'products/',
        'gallery' => $business_dir . 'gallery/'
    ];
    
    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
    }
    
    return [
        'business_path' => $business_dir,
        'images_path' => $dirs['images'],
        'products_path' => $dirs['products'],
        'gallery_path' => $dirs['gallery']
    ];
}

// Function to sanitize directory names
function sanitizeDirectoryName($name) {
    // Remove special characters and convert spaces to underscores
    $sanitized = preg_replace('/[^a-zA-Z0-9-_]/', '_', $name);
    return strtolower($sanitized);
}

// Function to upload business images
function uploadBusinessImage($file, $directory, $prefix = '') {
    // Check if file was uploaded successfully
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = $prefix . uniqid() . '.' . $extension;
    $filepath = $directory . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        // Return the relative path from web root
        return '/uploads/businesses/' . basename(dirname(dirname($filepath))) . '/' . basename(dirname($filepath)) . '/' . $filename;
    }
    
    return false;
}

function uploadMultipleImages($files, $path, $prefix = '') {
    $uploaded_files = [];
    
    if (is_array($files['name'])) {
        for ($i = 0; $i < count($files['name']); $i++) {
            if ($files['error'][$i] === UPLOAD_ERR_OK) {
                $file = [
                    'name' => $files['name'][$i],
                    'type' => $files['type'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'error' => $files['error'][$i],
                    'size' => $files['size'][$i]
                ];
                
                $result = uploadBusinessImage($file, $path, $prefix);
                if ($result) {
                    $uploaded_files[] = $result;
                }
            }
        }
    }
    
    return $uploaded_files;
}

function deleteBusinessDirectories($user_email, $business_name) {
    $business_folder = preg_replace('/[^a-z0-9]+/', '-', strtolower($business_name));
    $base_path = 'uploads/' . $user_email . '/' . $business_folder;
    
    if (file_exists($base_path)) {
        // Delete all files in subdirectories
        $directories = ['business', 'gallery', 'products'];
        foreach ($directories as $dir) {
            $dir_path = $base_path . '/' . $dir;
            if (file_exists($dir_path)) {
                array_map('unlink', glob("$dir_path/*.*"));
                rmdir($dir_path);
            }
        }
        // Remove the business directory
        rmdir($base_path);
        
        // If user's directory is empty, remove it too
        $user_dir = 'uploads/' . $user_email;
        if (file_exists($user_dir) && count(scandir($user_dir)) <= 2) {
            rmdir($user_dir);
        }
        return true;
    }
    return false;
} 