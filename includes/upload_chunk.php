<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die(json_encode(['error' => 'Unauthorized']));
}

$chunk = $_FILES['chunk'] ?? null;
$chunk_index = $_POST['chunk_index'] ?? null;
$total_chunks = $_POST['total_chunks'] ?? null;
$filename = $_POST['filename'] ?? null;

if (!$chunk || !isset($chunk_index) || !isset($total_chunks) || !$filename) {
    http_response_code(400);
    die(json_encode(['error' => 'Missing required parameters']));
}

// Create temporary directory for chunks if it doesn't exist
$temp_dir = sys_get_temp_dir() . '/upload_chunks/' . session_id();
if (!file_exists($temp_dir)) {
    mkdir($temp_dir, 0777, true);
}

// Save the chunk
$chunk_path = $temp_dir . '/' . $filename . '.part' . $chunk_index;
move_uploaded_file($chunk['tmp_name'], $chunk_path);

// Check if all chunks are uploaded
if ($chunk_index == $total_chunks - 1) {
    // Combine chunks
    $final_path = 'uploads/' . uniqid() . '_' . $filename;
    $out = fopen($final_path, 'wb');

    for ($i = 0; $i < $total_chunks; $i++) {
        $chunk_path = $temp_dir . '/' . $filename . '.part' . $i;
        $in = fopen($chunk_path, 'rb');
        stream_copy_to_stream($in, $out);
        fclose($in);
        unlink($chunk_path); // Delete chunk file
    }
    fclose($out);

    // Clean up temp directory
    rmdir($temp_dir);

    echo json_encode([
        'success' => true,
        'path' => $final_path
    ]);
} else {
    echo json_encode([
        'success' => true,
        'chunk_received' => $chunk_index
    ]);
} 