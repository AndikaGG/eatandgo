<?php
// Koneksi ke database
include 'koneksi.php'; // Sesuaikan dengan nama file koneksi Anda

// Validasi parameter GET
if (isset($_GET['kodebarang']) && isset($_GET['action']) && $_GET['action'] === 'delete') {
    session_start();

    // Cek apakah username tersedia dalam session
    if (isset($_SESSION['username'])) {
        $kodebarang = $_GET['kodebarang'];
        $username = $_SESSION['username'];

        // Jika username adalah admin, hapus tanpa memeriksa IDTOKO
        if ($username === 'admin') {
            $sql = "DELETE FROM eat_and_go_barang WHERE kodebarang = ?";
            $stmt = $conn->prepare($sql);

            if ($stmt === false) {
                die("Error in prepare statement: " . $conn->error);
            }

            $stmt->bind_param("s", $kodebarang);
        } else if (isset($_SESSION['IDTOKO'])) {
            // Jika bukan admin, periksa IDTOKO
            $idtoko = $_SESSION['IDTOKO'];
            $sql = "DELETE FROM eat_and_go_barang WHERE kodebarang = ? AND IDTOKO = ?";
            $stmt = $conn->prepare($sql);

            if ($stmt === false) {
                die("Error in prepare statement: " . $conn->error);
            }

            $stmt->bind_param("si", $kodebarang, $idtoko);
        } else {
            // Redirect jika IDTOKO tidak ditemukan
            header("Location: index.php?message=session_expired");
            exit;
        }

        if ($stmt->execute()) {
            // Redirect jika penghapusan berhasil
            header("Location: list_barang.php?message=delete_success");
        } else {
            // Redirect jika penghapusan gagal
            header("Location: list_barang.php?message=delete_error");
        }

        $stmt->close();
    } else {
        // Redirect ke login jika session tidak ditemukan
        header("Location: index.php?message=session_expired");
        exit;
    }
} else {
    // Redirect jika parameter tidak valid
    header("Location: list_barang.php?message=invalid_request");
    exit;
}

$conn->close();
?>