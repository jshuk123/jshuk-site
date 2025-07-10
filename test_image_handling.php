<?php
/**
 * Test script for image handling logic
 * This helps verify that the getBusinessLogoUrl function works correctly
 */

/**
 * Helper function to safely get business logo URL with fallback
 */
function getBusinessLogoUrl($file_path, $business_name = '') {
    $default_logo = '/images/jshuk-logo.png';
    
    if (empty($file_path)) {
        return $default_logo;
    }
    
    // Check if it's already a full URL
    if (strpos($file_path, 'http') === 0) {
        return $file_path;
    }
    
    // Check if it's already a relative path starting with /
    if (strpos($file_path, '/') === 0) {
        return $file_path;
    }
    
    // It's a relative path, prepend uploads directory
    return '/uploads/' . $file_path;
}

// Test cases
$test_cases = [
    'Empty path' => ['', 'Test Business'],
    'Full URL' => ['https://example.com/logo.jpg', 'Test Business'],
    'Absolute path' => ['/images/custom-logo.png', 'Test Business'],
    'Relative path' => ['businesses/123/logo.jpg', 'Test Business'],
    'Null path' => [null, 'Test Business'],
];

echo "<h2>Image Handling Test Results</h2>\n";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
echo "<tr><th>Test Case</th><th>Input</th><th>Output</th><th>Status</th></tr>\n";

foreach ($test_cases as $test_name => $test_data) {
    $input = $test_data[0];
    $business_name = $test_data[1];
    $output = getBusinessLogoUrl($input, $business_name);
    
    $status = '✅ PASS';
    if (empty($output)) {
        $status = '❌ FAIL - Empty output';
    } elseif (strpos($output, 'uploads/') === false && strpos($output, 'http') === false && strpos($output, '/images/') === false) {
        $status = '❌ FAIL - Invalid path format';
    }
    
    echo "<tr>";
    echo "<td>" . htmlspecialchars($test_name) . "</td>";
    echo "<td>" . htmlspecialchars($input ?? 'NULL') . "</td>";
    echo "<td>" . htmlspecialchars($output) . "</td>";
    echo "<td>" . $status . "</td>";
    echo "</tr>\n";
}

echo "</table>\n";

// Test HTML output
echo "<h3>HTML Output Test</h3>\n";
$test_logo = getBusinessLogoUrl('businesses/123/logo.jpg', 'Test Business');
echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 10px 0;'>\n";
echo "<img src='" . htmlspecialchars($test_logo) . "' style='width:60px; height:60px; object-fit:cover; border-radius:8px;' alt='Test Business Logo' onerror=\"this.onerror=null; this.src='/images/jshuk-logo.png';\">\n";
echo "<p>Test Business</p>\n";
echo "</div>\n";

echo "<p><strong>Test completed!</strong> If you see a broken image above, it will automatically fall back to the default logo.</p>\n";
?> 