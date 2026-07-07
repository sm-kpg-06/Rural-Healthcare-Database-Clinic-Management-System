<?php
$host = getenv('DB_HOST') ?: '127.0.0.1';
$username = getenv('DB_USERNAME') ?: 'testuser';
$password = getenv('DB_PASSWORD') ?: '';
$database = getenv('DB_NAME') ?: 'healthcare_db';

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>