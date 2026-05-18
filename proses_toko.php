<?php
include 'koneksi.php'; // Menghubungkan ke database
session_start(); // Memulai session

// Pastikan pengguna telah login
if (!isset($_SESSION['username'])) {
    header('Location: index.php');
    exit;
}

// Periksa apakah data yang diperlukan tersedia di form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['namatoko']) && $_POST['action'] === 'edit') {
    $namatoko = $_POST['namatoko'];
    $alamat = $_POST['alamat'];
    $telp1 = $_POST['telp1'];
    $telp2 = $_POST['telp2'];
    $lokasi = $_POST['lokasi'];

    // Query untuk mendapatkan data toko yang asli
    $sql_toko = "SELECT * FROM eat_and_go_toko WHERE NAMATOKO=?";
    $stmt_toko = $conn->prepare($sql_toko);
    $stmt_toko->bind_param('s', $namatoko);
    $stmt_toko->execute();
    $result_toko = $stmt_toko->get_result();
    $row_toko = $result_toko->fetch_assoc();

    if ($row_toko) {
        // Update hanya pada field yang diubah
        $sql_update = "UPDATE eat_and_go_toko SET ";
        $fields = [];
        $params = [];
        $types = '';

        if ($alamat !== $row_toko['ALAMAT']) {
            $fields[] = "ALAMAT=?";
            $params[] = $alamat;
            $types .= 's';
        }
        if ($telp1 !== $row_toko['TELP1']) {
            $fields[] = "TELP1=?";
            $params[] = $telp1;
            $types .= 's';
        }
        if ($telp2 !== $row_toko['TELP2']) {
            $fields[] = "TELP2=?";
            $params[] = $telp2;
            $types .= 's';
        }
        if ($lokasi !== $row_toko['LOKASI']) {
            $fields[] = "LOKASI=?";
            $params[] = $lokasi;
            $types .= 's';
        }

        // Jika ada perubahan pada field
        if (count($fields) > 0) {
            $sql_update .= implode(", ", $fields) . " WHERE NAMATOKO=?";
            $params[] = $namatoko;
            $types .= 's';

            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->bind_param($types, ...$params);
            if ($stmt_update->execute()) {
                echo "Data toko berhasil diperbarui.";
                header("Location: list_toko.php"); // Redirect ke list toko setelah update
                exit;
            } else {
                echo "Terjadi kesalahan saat mengupdate data: " . $conn->error;
            }
        } else {
            echo "Tidak ada perubahan yang terdeteksi.";
            header("Location: list_toko.php"); // Redirect ke list toko jika tidak ada perubahan
            exit;
        }
    } else {
        echo "Data toko tidak ditemukan.";
    }

    $stmt_toko->close();
} else {
    echo "Data tidak valid.";
}

$conn->close();
?>