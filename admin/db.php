<?php

$conn = new mysqli("localhost", "root", "", "flightdiary306");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>