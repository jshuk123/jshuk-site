<?php
// echo "SECURITY FILE LOADED";
// Define constant to prevent direct access
define('JSHUK_LOADED', true);

// Function to check if script is being accessed directly
function preventDirectAccess() {
    if (!defined('JSHUK_LOADED')) {
        http_response_code(403);
        die('Direct access not allowed');
    }
}

// Call the function
preventDirectAccess();
?> 