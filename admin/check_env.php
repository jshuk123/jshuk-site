<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<pre>";
echo "PHP Version: " . phpversion() . "\n";
echo "PHP SAPI: " . php_sapi_name() . "\n\n";

echo "Session Configuration:\n";
echo "session.save_handler: " . ini_get('session.save_handler') . "\n";
echo "session.save_path: " . ini_get('session.save_path') . "\n";
echo "session.gc_maxlifetime: " . ini_get('session.gc_maxlifetime') . "\n\n";

echo "Error Reporting Configuration:\n";
echo "error_reporting: " . ini_get('error_reporting') . "\n";
echo "display_errors: " . ini_get('display_errors') . "\n";
echo "log_errors: " . ini_get('log_errors') . "\n";
echo "error_log: " . ini_get('error_log') . "\n\n";

echo "Output Buffering Configuration:\n";
echo "output_buffering: " . ini_get('output_buffering') . "\n";
echo "implicit_flush: " . ini_get('implicit_flush') . "\n\n";

echo "Environment Variables:\n";
$required_vars = [
    'DB_HOST',
    'DB_NAME',
    'DB_USER',
    'DB_PASS',
    'APP_ENV',
    'SITE_URL'
];

foreach ($required_vars as $var) {
    echo "$var: " . (getenv($var) ? '[SET]' : '[NOT SET]') . "\n";
}

echo "\nPHP Extensions:\n";
$required_extensions = [
    'pdo',
    'pdo_mysql',
    'session',
    'json',
    'mbstring'
];

foreach ($required_extensions as $ext) {
    echo "$ext: " . (extension_loaded($ext) ? '[LOADED]' : '[NOT LOADED]') . "\n";
}

echo "\nDirectory Permissions:\n";
$paths_to_check = [
    '../logs',
    '../uploads',
    '../config'
];

foreach ($paths_to_check as $path) {
    $realpath = realpath(__DIR__ . '/' . $path);
    echo "$path: " . ($realpath ? '[EXISTS]' : '[NOT FOUND]') . "\n";
    if ($realpath) {
        echo "  Readable: " . (is_readable($realpath) ? 'Yes' : 'No') . "\n";
        echo "  Writable: " . (is_writable($realpath) ? 'Yes' : 'No') . "\n";
    }
}

echo "</pre>"; 