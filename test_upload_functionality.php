<?php
require_once 'config/config.php';

echo "<h2>Upload Directory Test</h2>";

$upload_dir = 'uploads/carousel/';
$full_path = __DIR__ . '/' . $upload_dir;

echo "<h3>Directory Check:</h3>";
echo "<ul>";
echo "<li><strong>Directory path:</strong> " . htmlspecialchars($full_path) . "</li>";
echo "<li><strong>Directory exists:</strong> " . (is_dir($full_path) ? '✅ Yes' : '❌ No') . "</li>";

if (is_dir($full_path)) {
    echo "<li><strong>Directory readable:</strong> " . (is_readable($full_path) ? '✅ Yes' : '❌ No') . "</li>";
    echo "<li><strong>Directory writable:</strong> " . (is_writable($full_path) ? '✅ Yes' : '❌ No') . "</li>";
    echo "<li><strong>Directory permissions:</strong> " . substr(sprintf('%o', fileperms($full_path)), -4) . "</li>";
} else {
    echo "<li><strong>Attempting to create directory...</strong></li>";
    if (mkdir($full_path, 0755, true)) {
        echo "<li>✅ Directory created successfully!</li>";
        echo "<li><strong>Directory writable:</strong> " . (is_writable($full_path) ? '✅ Yes' : '❌ No') . "</li>";
    } else {
        echo "<li>❌ Failed to create directory</li>";
    }
}

echo "</ul>";

echo "<h3>Current Files in Directory:</h3>";
if (is_dir($full_path)) {
    $files = scandir($full_path);
    if ($files) {
        echo "<ul>";
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..') {
                $file_path = $full_path . $file;
                $size = filesize($file_path);
                $modified = date('Y-m-d H:i:s', filemtime($file_path));
                echo "<li><strong>" . htmlspecialchars($file) . "</strong> - Size: " . number_format($size) . " bytes - Modified: " . $modified . "</li>";
            }
        }
        echo "</ul>";
    } else {
        echo "<p>No files found in directory.</p>";
    }
}

echo "<h3>PHP Upload Settings:</h3>";
echo "<ul>";
echo "<li><strong>upload_max_filesize:</strong> " . ini_get('upload_max_filesize') . "</li>";
echo "<li><strong>post_max_size:</strong> " . ini_get('post_max_size') . "</li>";
echo "<li><strong>max_file_uploads:</strong> " . ini_get('max_file_uploads') . "</li>";
echo "<li><strong>file_uploads:</strong> " . (ini_get('file_uploads') ? '✅ Enabled' : '❌ Disabled') . "</li>";
echo "</ul>";

echo "<h3>Test Upload Form:</h3>";
?>
<form method="post" enctype="multipart/form-data">
    <input type="file" name="test_image" accept="image/*" required>
    <button type="submit">Test Upload</button>
</form>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['test_image'])) {
    echo "<h3>Upload Test Results:</h3>";
    $file = $_FILES['test_image'];
    
    echo "<ul>";
    echo "<li><strong>File name:</strong> " . htmlspecialchars($file['name']) . "</li>";
    echo "<li><strong>File size:</strong> " . number_format($file['size']) . " bytes</li>";
    echo "<li><strong>File type:</strong> " . htmlspecialchars($file['type']) . "</li>";
    echo "<li><strong>Upload error:</strong> " . $file['error'] . " (" . ($file['error'] === 0 ? 'No error' : 'Error occurred') . ")</li>";
    echo "<li><strong>Temporary file:</strong> " . htmlspecialchars($file['tmp_name']) . "</li>";
    echo "</ul>";
    
    if ($file['error'] === 0 && is_uploaded_file($file['tmp_name'])) {
        $filename = 'test_' . time() . '_' . uniqid() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
        $target_path = $full_path . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $target_path)) {
            echo "<p style='color: green;'>✅ Upload successful! File saved as: " . htmlspecialchars($filename) . "</p>";
        } else {
            echo "<p style='color: red;'>❌ Failed to move uploaded file to: " . htmlspecialchars($target_path) . "</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ File upload failed or was not uploaded via HTTP POST</p>";
    }
}
?> 