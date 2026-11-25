<?php
$host = getenv('BLAZE_DB_HOST') ?: 'localhost';
$username = getenv('BLAZE_DB_USER') ?: 'root';
$password = getenv('BLAZE_DB_PASSWORD') ?: '';
$database = getenv('BLAZE_DB_NAME') ?: 'blaze_bakery';
$port = (int) (getenv('BLAZE_DB_PORT') ?: 3306);

$conn = mysqli_connect($host, $username, $password, $database, $port);

if (!$conn) {
    error_log('Cr. Blaze Bakery database connection failed: ' . mysqli_connect_error());
    http_response_code(503);
    die('The bakery service is temporarily unavailable.');
}

mysqli_set_charset($conn, 'utf8mb4');
?>
