<?php
$host = getenv('DB_HOST') ?: '127.0.0.1';
$username = getenv('DB_USERNAME') ?: 'testuser';
$password = getenv('DB_PASSWORD') ?: '';
$database = getenv('DB_NAME') ?: 'healthcare_db';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($host, $username, $password, $database);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    die("Database connection failed. Check DB_HOST, DB_USERNAME, DB_PASSWORD, and DB_NAME. Error: " . $e->getMessage());
}
?>