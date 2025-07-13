<?php
// Script to fix legacy/incorrect business image paths in the database and filesystem
require_once __DIR__ . '/../config/config.php';

// $pdo is already available from config.php

function is_numeric_folder($path_part) {
    return preg_match('/^\d+$/', $path_part);
}

function log_msg($msg) {
    echo $msg . PHP_EOL;
}

try {
    $stmt = $pdo->query("SELECT id, business_id, file_path, sort_order FROM business_images WHERE sort_order = 0");
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($images as $img) {
        $id = $img['id'];
        $business_id = $img['business_id'];
        $file_path = $img['file_path'];

        // Parse the path to check if it uses a slug or the correct business_id
        $parts = explode('/', $file_path);
        // Expect: uploads/businesses/{something}/images/main-...
        if (count($parts) < 5) {
            log_msg("[SKIP] Unexpected path format: $file_path");
            continue;
        }
        $folder = $parts[2];
        if (is_numeric_folder($folder)) {
            // Already correct
            log_msg("[OK] $file_path");
            continue;
        }
        // It's a slug, needs to be fixed
        $filename = $parts[count($parts)-1];
        $type_dir = $parts[count($parts)-2]; // images or gallery
        $old_dir = __DIR__ . "/../" . implode('/', array_slice($parts, 0, count($parts)-1));
        $old_file = $old_dir . '/' . $filename;
        $new_dir = __DIR__ . "/../uploads/businesses/$business_id/$type_dir";
        if (!is_dir($new_dir)) {
            mkdir($new_dir, 0777, true);
        }
        $new_file = $new_dir . '/' . $filename;
        if (file_exists($old_file)) {
            if (rename($old_file, $new_file)) {
                $new_rel_path = "uploads/businesses/$business_id/$type_dir/$filename";
                $update = $pdo->prepare("UPDATE business_images SET file_path = ? WHERE id = ?");
                $update->execute([$new_rel_path, $id]);
                log_msg("[FIXED] $file_path => $new_rel_path");
            } else {
                log_msg("[ERROR] Failed to move $old_file to $new_file");
            }
        } else {
            log_msg("[MISSING] File does not exist: $old_file");
        }
    }
    log_msg("Done.");
} catch (Exception $e) {
    log_msg("[FATAL] " . $e->getMessage());
} 