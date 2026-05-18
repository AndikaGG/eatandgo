<?php
include 'koneksi.php'; // Include the database connection file
session_start(); // Start the session to access user information

// Redirect jika user belum login
if (isset($_GET['idmeja']) && isset($_GET['action']) && $_GET['action'] === 'delete') {
    session_start();


  // Cek apakah username tersedia dalam session
  if (isset($_SESSION['username'])) {
    $idmeja = $_GET['idmeja'];
    $username = $_SESSION['username'];

    // Jika username adalah admin, hapus tanpa memeriksa IDTOKO
    if ($username === 'admin') {
        $sql = "DELETE FROM eat_and_go_meja WHERE idmeja = ?";
        $stmt = $conn->prepare($sql);

        if ($stmt === false) {
            die("Error in prepare statement: " . $conn->error);
        }

        $stmt->bind_param("s", $idmeja);
    } else if (isset($_SESSION['IDTOKO'])) {
        // Jika bukan admin, periksa IDTOKO
        $idtoko = $_SESSION['IDTOKO'];
        $sql = "DELETE FROM eat_and_go_meja WHERE idmeja = ? AND IDTOKO = ?";
        $stmt = $conn->prepare($sql);

        if ($stmt === false) {
            die("Error in prepare statement: " . $conn->error);
        }

        $stmt->bind_param("si", $idmeja, $idtoko);
    } else {
        // Redirect jika IDTOKO tidak ditemukan
        header("Location: index.php?message=session_expired");
        exit;
    }

    if ($stmt->execute()) {
        // Redirect jika penghapusan berhasil
        header("Location: list_meja.php?message=delete_success");
    } else {
        // Redirect jika penghapusan gagal
        header("Location: list_meja.php?message=delete_error");
    }

    $stmt->close();
} else {
    // Redirect ke login jika session tidak ditemukan
    header("Location: index.php?message=session_expired");
    exit;
}
} else {
// Redirect jika parameter tidak valid
header("Location: list_meja.php?message=invalid_request");
exit;
}

$conn->close();
?>