<?php
include 'koneksi.php';

// Memeriksa apakah ID toko ada di parameter URL
if (isset($_GET['id'])) {
    $idToko = $_GET['id'];

    // Query untuk menghapus toko berdasarkan ID
    $query = "DELETE FROM eat_and_go_toko WHERE IDTOKO = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $idToko);

    if ($stmt->execute()) {
        // Jika berhasil, kembali ke halaman list_toko.php dengan pesan sukses
        header("Location: list_toko.php?message=deleted");
    } else {
        // Jika gagal, kembali ke halaman list_toko.php dengan pesan error
        header("Location: list_toko.php?message=error");
    }

    $stmt->close();
} else {
    // Jika ID toko tidak ditemukan, kembali ke halaman list_toko.php
    header("Location: list_toko.php");
}
$conn->close();
?>