<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil data pesanan dari field tersembunyi
    $orderData = isset($_POST['orderData']) ? json_decode($_POST['orderData'], true) : [];

    // Proses data pesanan sesuai kebutuhan
    foreach ($orderData as $item) {
        // Akses $item['idbarang'], $item['harga'], $item['jumlah'], $item['total']
        // Contoh: Simpan setiap item ke database atau lakukan tindakan lain
    }

    // Redirect atau tampilkan pesan sukses
    header('Location: halaman_sukses.php');
}
?>