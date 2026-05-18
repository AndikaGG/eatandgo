<?php
include 'koneksi.php';
session_start();

// Ambil parameter 'nomeja' dan 'IDTOKO' dari URL
$nomejanya = isset($_GET["nomeja"]) ? $_GET["nomeja"] : 0;
$id_toko = isset($_GET["IDTOKO"]) ? $_GET["IDTOKO"] : 0;

$current_year = date('y');
$current_month = date('m');

//coba test hanif
$prefix = "OR".$id_toko.$current_year.$current_month;
//echo $prefix."<br>";

$sql = "select ifnull(RIGHT(max(nopesanan),5),0)+1 as hasil from eat_and_go_pesanan where nopesanan like '".$prefix."%'";
//echo $sql;
//echo "<br>";

$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($result); // data row ini berisi angka terakhir dari row

//echo $row['hasil'];
//echo "<br>";

$hasilakhir = $prefix.substr("00000".$row['hasil'],-5);

echo $hasilakhir;

?>