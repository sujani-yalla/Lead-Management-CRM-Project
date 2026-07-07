<?php
$conn = new mysqli("localhost", "root", "", "overseas");

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
?>
