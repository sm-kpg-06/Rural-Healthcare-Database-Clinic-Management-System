<?php
$conn = new mysqli("127.0.0.1", "testuser", "", "healthcare_db");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>