<?php
require('fpdf/fpdf.php');
include 'koneksi.php';


session_start();
// Ambil idpesanan dari metode GET menggunakan kunci 'id'
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("No Pesanan tidak ditemukan.");
}

$nopesanan = mysqli_real_escape_string($conn, $_GET['id']); // Untuk menghindari SQL injection

// Ambil IDTOKO dari session pengguna
$idTokoPengguna = mysqli_real_escape_string($conn, $_GET['idtoko']); // Hindari SQL Injection

// Untuk pengguna admin, ID toko biasanya diset di session

// Pastikan ID toko ada


// Buat instance PDF baru dengan ukuran khusus untuk printer POS 58
$pdf = new FPDF('P', 'mm', array(58, 210)); // Atur ukuran halaman 58mm x 210mm
$pdf->AddPage();
$pdf->SetMargins(2, 2, 2); // Atur margin lebih kecil untuk printer POS
$pdf->SetFont('Courier', '', 8); // Ukuran font lebih kecil tanpa bold

// Atur posisi awal Y
$pdf->SetY(2); // Atur posisi awal Y sangat dekat dengan bagian atas

// Query untuk mendapatkan detail toko
$query = "SELECT NAMATOKO, ALAMAT, TELP1, TELP2, LOKASI FROM eat_and_go_toko WHERE IDTOKO = '$idTokoPengguna' LIMIT 1";
$result = mysqli_query($conn, $query);

if (!$result) {
    die("Error saat mengambil data toko: " . mysqli_error($conn));
}

// Ambil detail toko
$toko = mysqli_fetch_assoc($result);
mysqli_free_result($result);

$namaToko = $toko['NAMATOKO'] ?? 'N/A';
$alamat = $toko['ALAMAT'] ?? 'N/A';
$noTelp = $toko['TELP1'] ?? 'N/A';
$lokasi = $toko['LOKASI'] ?? 'N/A';

// Cetak informasi toko
$pdf->SetFont('Courier', 'B', 8); // Bold untuk alamat toko di bagian atas
$pdf->Cell(0, 4, substr($namaToko, 0, 31), 0, 1, 'C'); // Nama toko tetap dicetak

// Pecah alamat berdasarkan koma
$alamatArray = explode(',', $alamat);
foreach ($alamatArray as $alamatBaris) {
    $pdf->Cell(0, 4, substr(trim($alamatBaris), 0, 31), 0, 1, 'C'); // Cetak setiap baris alamat dengan batas 31 karakter
}
$pdf->Cell(0, 4, substr($noTelp, 0, 31), 0, 1, 'C'); // telepon 1
$pdf->Cell(0, 4, substr($lokasi, 0, 31), 0, 1, 'C'); // lokasi
$pdf->Cell(0, 4, str_repeat("=", 31), 0, 1, 'C'); // Garis horizontal lebih pendek (31 karakter)
$pdf->Cell(0, 4, str_repeat("=", 31), 0, 1, 'C');

// Kembali ke font reguler untuk konten selanjutnya
$pdf->SetFont('Courier', '', 8);

// Query untuk mendapatkan detail transaksi dan item
$query = "
    SELECT p.tanggal, p.nomeja AS no_meja, p.nama AS nama_pelanggan, p.notelepon AS no_telepon, p.jenispembayaran, dp.namabarang, dp.harga, dp.jumlah, dp.bungkus, dp.keterangan, p.bayar
    FROM eat_and_go_pesanan p
    JOIN eat_and_go_detilpesanan dp ON p.nopesanan = dp.nopesanan
    WHERE p.nopesanan = '$nopesanan'
";

$result = mysqli_query($conn, $query);

if (!$result) {
    die("Error saat mengambil data pesanan dan item: " . mysqli_error($conn));
}

$total_amount = 0;
$tanggalTransaksi = '';
$noMeja = '';
$namaPelanggan = '';
$noTelepon = '';
$bayar = 0;
$jenisPembayaran = ''; // Inisialisasi variabel jenis pembayaran
$items = [];

// Simpan data item untuk dicetak setelah informasi pesanan
while ($row = mysqli_fetch_assoc($result)) {
    // Ambil informasi transaksi
    if (empty($tanggalTransaksi)) {
        $tanggalTransaksi = $row['tanggal'];
        $noMeja = $row['no_meja'];
        $namaPelanggan = $row['nama_pelanggan'];
        $noTelepon = $row['no_telepon'];
        $jenisPembayaran = $row['jenispembayaran']; // Simpan jenis pembayaran
        $bayar = (float)($row['bayar'] ?? 0);
    }

    $itemNamaBarang = $row['namabarang'] ?? '';
    $itemHarga = (float)($row['harga'] ?? 0);
    $itemJumlah = (int)($row['jumlah'] ?? 0);
    $total = $itemHarga * $itemJumlah;
    $total_amount += $total;

    // Cek apakah bungkus dan keterangan ada
    $bungkus = ($row['bungkus'] == 1) ? '[BUNGKUS]' : '';
    $keterangan = $row['keterangan'] ? $row['keterangan'] : '';

    // Tambahkan ke item
    $items[] = [
        'nama' => $itemNamaBarang,
        'bungkus' => $bungkus,
        'keterangan' => $keterangan,
        'harga' => $itemHarga,
        'jumlah' => $itemJumlah,
        'total' => $total
    ];
}

// Cetak informasi pesanan di bawah alamat toko
$pdf->SetFont('Courier', '', 8); // Ukuran font lebih kecil tanpa bold
$pdf->Cell(0, 4, 'No Pesanan: ' . $nopesanan, 0, 1, 'L'); // Nomor pesanan
$pdf->Cell(0, 4, 'Tanggal   : ' . $tanggalTransaksi, 0, 1, 'L'); // Tanggal
$pdf->Cell(0, 4, 'No Meja   : ' . $noMeja, 0, 1, 'L'); // Nomor meja
$pdf->Cell(0, 4, 'Nama      : ' . $namaPelanggan, 0, 1, 'L'); // Nama pelanggan
$pdf->Cell(0, 4, 'No Telepon: ' . $noTelepon, 0, 1, 'L'); // Nomor telepon
$pdf->Cell(0, 4, str_repeat("=", 31), 0, 1, 'C'); // Garis horizontal lebih pendek (31 karakter)

// Cetak daftar item
$pdf->SetFont('Courier', '', 8); // Ukuran font lebih kecil tanpa bold
foreach ($items as $item) {
    // Cetak nama barang dan bungkus (jika ada)
    $pdf->Cell(0, 4, substr($item['nama'] . ' ' . $item['bungkus'], 0, 31), 0, 1, 'L'); // Nama barang + [BUNGKUS]

    // Cetak keterangan di baris baru jika ada, tetapi tidak menampilkan jumlah
    if ($item['keterangan']) {
        $pdf->Cell(0, 4, $item['keterangan'], 0, 1, 'L'); // Cetak keterangan tanpa jumlah
    }

    // Membentuk satu baris dengan format sesuai (HARGA x JUMLAH     TOTAL)
    $line = str_pad(number_format($item['harga'], 2, ',', '.') . " x " . $item['jumlah'], 15, ' ', STR_PAD_RIGHT) . // 2 angka desimal untuk harga
            str_pad('Rp ' . number_format($item['total'], 2, ',', '.'), 16, ' ', STR_PAD_LEFT); // 2 angka desimal untuk total

    // Cetak baris
    $pdf->Cell(0, 4, substr($line, 0, 31), 0, 1, 'L'); // Batas karakter 31
}

// Cetak garis horizontal sebelum total
$pdf->Cell(0, 4, str_repeat("=", 31), 0, 1, 'C'); // Garis horizontal lebih pendek (31 karakter)

// Grand Total
$pdf->SetFont('Courier', '', 8); // Ukuran font lebih kecil tanpa bold
$grandTotalLabel = 'GRANDTOTAL:';
$grandTotalValue = 'Rp ' . number_format($total_amount, 2, ',', '.'); // 2 angka desimal untuk grand total

// Gabungkan grand total dalam satu baris
$combinedLines = [
    str_pad($grandTotalLabel, 15, ' ', STR_PAD_RIGHT) . str_pad($grandTotalValue, 16, ' ', STR_PAD_LEFT),
];

foreach ($combinedLines as $line) {
    $pdf->Cell(0, 4, substr($line, 0, 31), 0, 1, 'L'); // Batas karakter 31
}

// Cetak jenis pembayaran setelah kembalian
$pdf->SetFont('Courier', 'B', 8); // Atur font menjadi bold untuk nama toko di bagian bawah
$pdf->Cell(0, 15, substr($namaToko, 0, 31), 0, 1, 'L'); // Batas karakter 31
$pdf->SetFont('Courier', '', 8); // Kembalikan font ke normal untuk teks lainnya
$pdf->Cell(0, 30, "(_____________)", 0, 1, 'L'); // Spasi untuk menyesuaikan tanda tangan

// Output PDF ke browser dengan nama file dinamis
$filename = $nopesanan . '.pdf';
$pdf->Output('I', $filename); // 'I' untuk mengirim ke browser, 'F' untuk menyimpan di server$pdf->Output();
?>
