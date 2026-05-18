<?php
$host = "sql201.infinityfree.com";
$user = "if0_41951238";
$pass = "PASSWORD vPanel kamu";
$db   = "if0_41951238_eat_and_go";

$conn = mysqli_connect($host, $user, $pass, $db);

// Check connection
if (!$conn) {
    die("koneksi gagal: " . mysqli_connect_error);
}
?>