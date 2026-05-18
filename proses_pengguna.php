<?php
include 'koneksi.php';

session_start(); // Mulai sesi untuk mengakses informasi sesi pengguna

// Pastikan pengguna telah login sebelumnya
if (!isset($_SESSION['username'])) {
    header('Location: index.php'); // Redirect jika pengguna belum login
    exit;
}

// Ambil aksi dari URL atau form
$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'updateKonfirmasi') {
    // Mengambil data konfirmasi dari form
    $konfirmasiList = $_POST['konfirmasi'] ?? [];

    // Update status konfirmasi untuk setiap pengguna yang dipilih
    foreach ($konfirmasiList as $username) {
        $username = mysqli_real_escape_string($conn, $username);
        $query = "UPDATE eat_and_go_pengguna SET konfirmasi = 1 WHERE username = '$username'";
        if (!mysqli_query($conn, $query)) {
            die("Query error: " . mysqli_error($conn));
        }
    }

    // Update status konfirmasi untuk pengguna yang tidak dipilih (set menjadi 0)
    $query = "UPDATE eat_and_go_pengguna SET konfirmasi = 0 WHERE username != 'admin' AND username NOT IN ('" . implode("','", array_map('mysqli_real_escape_string', $konfirmasiList)) . "')";
    if (!mysqli_query($conn, $query)) {
        die("Query error: " . mysqli_error($conn));
    }

    header('Location: list_pengguna.php');
    exit;

} elseif ($action === 'delete' && isset($_GET['username'])) {
    $username = $_GET['username'];
    $username = mysqli_real_escape_string($conn, $username); // Sanitasi input

    $sql = "DELETE FROM eat_and_go_pengguna WHERE username='$username'";

    if (mysqli_query($conn, $sql)) {
        header('Location: list_pengguna.php');
        exit;
    } else {
        echo "Error deleting record: " . mysqli_error($conn);
    }

    mysqli_close($conn);
}
?>
