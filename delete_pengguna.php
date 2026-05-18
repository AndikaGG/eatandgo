<?php
include 'koneksi.php';

session_start();

// Pastikan pengguna telah login
if (!isset($_SESSION['username'])) {
    header('Location: index.php');
    exit;
}

// Ambil username pengguna dari sesi
$username = $_SESSION['username'];

// Validasi username admin
if ($username !== 'admin') {
    // Jika bukan admin, beri pesan akses ditolak atau redirect ke halaman lain
    echo "Anda tidak memiliki izin untuk menghapus pengguna.";
    exit;
}

// Proses penghapusan pengguna jika ada parameter GET 'id'
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $sql = "DELETE FROM eat_and_go_pengguna WHERE id='$id'";

    if (mysqli_query($conn, $sql)) {
        header("Location: list_pengguna.php");
        exit;
    } else {
        echo "Error deleting record: " . mysqli_error($conn);
    }
}

mysqli_close($conn);
?>