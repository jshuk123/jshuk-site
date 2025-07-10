<?php

/**
 * Handles the upload of a single image file for a business.
 *
 * @param array $file The $_FILES['input_name'] array.
 * @param int $business_id The business ID for directory structure.
 * @param string $prefix A prefix for the new filename to avoid collisions (e.g., 'main' or 'gallery').
 * @return string The relative path to the uploaded file on success.
 * @throws Exception on failure.
 */
function handle_image_upload($file, $business_id, $prefix = '') {
    // Validate upload parameters
    if (!isset($file['error']) || is_array($file['error'])) {
        error_log('Invalid file upload parameters.');
        throw new Exception('Invalid file upload parameters.');
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        error_log('File upload error: ' . $file['error']);
        throw new Exception('File upload error: ' . $file['error']);
    }
    // Validate file type (allow only images)
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $valid_types = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $mime = $finfo->file($file['tmp_name']);
    if (!in_array($mime, $valid_types) || !array_key_exists($ext, $valid_types)) {
        error_log('Invalid image file type: ' . $mime . ' / ' . $ext);
        throw new Exception('Invalid image file type.');
    }
    // Build target directory
    $base_dir = dirname(__DIR__) . '/uploads/businesses/' . $business_id;
    if (!is_dir($base_dir)) {
        if (!mkdir($base_dir, 0777, true)) {
            error_log('Failed to create upload directory: ' . $base_dir);
            throw new Exception('Failed to create upload directory.');
        } else {
            error_log('Successfully created upload directory: ' . $base_dir);
        }
    }
    // Generate unique filename
    $filename = $prefix . '-' . uniqid() . '.' . $ext;
    $target_path = $base_dir . '/' . $filename;
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $target_path)) {
        error_log('Failed to move uploaded file: ' . $file['tmp_name'] . ' to ' . $target_path);
        throw new Exception('Failed to move uploaded file.');
    } else {
        error_log('Successfully moved uploaded file: ' . $file['tmp_name'] . ' to ' . $target_path);
    }
    // Return the relative path from the web root
    $relative_path = 'uploads/businesses/' . $business_id . '/' . $filename;
    return $relative_path;
}
