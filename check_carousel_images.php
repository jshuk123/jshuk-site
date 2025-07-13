<?php
require_once __DIR__ . '/config/config.php';

echo "<h2>Carousel Image File Check</h2>";

try {
    $stmt = $pdo->query("SELECT id, title, image_url FROM carousel_slides");
    $slides = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $missing = [];
    $found = [];

    foreach ($slides as $slide) {
        $path = __DIR__ . '/' . $slide['image_url'];
        if (!empty($slide['image_url']) && file_exists($path)) {
            $found[] = [
                'id' => $slide['id'],
                'title' => $slide['title'],
                'image_url' => $slide['image_url']
            ];
        } else {
            $missing[] = [
                'id' => $slide['id'],
                'title' => $slide['title'],
                'image_url' => $slide['image_url']
            ];
        }
    }

    echo "<h3>✅ Images Found:</h3>";
    if ($found) {
        echo "<ul>";
        foreach ($found as $f) {
            echo "<li><strong>{$f['title']}</strong> ({$f['image_url']})</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>None found.</p>";
    }

    echo "<h3>❌ Missing Images:</h3>";
    if ($missing) {
        echo "<ul style='color:red'>";
        foreach ($missing as $m) {
            echo "<li><strong>{$m['title']}</strong> ({$m['image_url']})</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>All images found!</p>";
    }

} catch (PDOException $e) {
    echo "<p style='color:red'>Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?> 