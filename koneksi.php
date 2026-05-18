<?php
$host = "localhost";
$username = "root";
$password = "";
$database = "eat_and_go"; // Ensure this is the correct database name

// Create connection
$conn = mysqli_connect($host, $username, $password, $database);

// Check connection
if (!$conn) {
    die("koneksi gagal: " . mysqli_connect_error);
}
?>