<?php
$host = 'localhost';
$db   = 'u544457429_jshuk_db';
$user = 'u544457429_jshuk01';
$pass = 'jshuk01'; // ← original password, no exclamation
$charset = 'utf8mb4';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=$charset", $user, $pass);
    echo "✅ SUCCESS: Connected to the database.";
} catch (PDOException $e) {
    echo "❌ ERROR: " . $e->getMessage();
}
?>
