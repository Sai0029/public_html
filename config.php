<?php
$servername = "localhost"; // Replace with your server name
    $database = "id22126747_myproject"; // Replace with your database name
    $username = "root"; // Default MySQL username for XAMPP
    $password = ""; // Default MySQL password for XAMPP is empty
    $port = 3307; // Your MySQL port number

$conn = new mysqli($servername, $username, $password, $database,$port);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
