<?php
include 'koneksi.php';
session_start();

if (!isset($_SESSION['username'])) {
    header('Location: index.php');
    exit;
}

$username = $_SESSION['username']; // Ambil username pengguna dari sesi

// Query untuk mengambil data pengguna berdasarkan username
$query = "SELECT * FROM eat_and_go_pengguna WHERE username = '$username'";
$result = mysqli_query($conn, $query);

if (!$result) {
    die("Query error: " . mysqli_error($conn));
}

// Ambil data pengguna
if (mysqli_num_rows($result) > 0) {
    $eat_and_go_pengguna = mysqli_fetch_assoc($result);
    $user_record = $eat_and_go_pengguna['username']; // Ambil nilai username dari pengguna
} else {
    // Handle jika tidak ada data pengguna yang ditemukan
    $user_record = ''; // Atau sesuaikan dengan logika penanganan kesalahan
}

// Mengambil input pencarian dari user
$search_keyword = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

// Count total items dengan pencarian
$sql_count = "SELECT COUNT(*) AS total FROM eat_and_go_toko WHERE NAMATOKO LIKE '%$search_keyword%' OR ALAMAT LIKE '%$search_keyword%' OR TELP1 LIKE '%$search_keyword%' OR TELP2 LIKE '%$search_keyword%' OR LOKASI LIKE '%$search_keyword%'";
$result_count = mysqli_query($conn, $sql_count);
$row_count = mysqli_fetch_assoc($result_count);
$total_records = $row_count['total'];

// Pagination
$records_per_page = 10;
$total_pages = ceil($total_records / $records_per_page);
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Query untuk mengambil data toko dengan pencarian dan pagination
$sql = "SELECT IDTOKO, NAMATOKO, ALAMAT, TELP1, TELP2, LOKASI
        FROM eat_and_go_toko
        WHERE NAMATOKO LIKE '%$search_keyword%'
        ORDER BY NAMATOKO DESC
        LIMIT $offset, $records_per_page";

$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>List Toko</title>
    <link rel="shortcut icon" href="listbarang.svg" type="image/svg+xml">
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        body {
            overflow-x: hidden;
        }

        .action-icons {
            display: flex;
            justify-content: space-around;
            margin-top: 10px;
        }

        .bottom-right {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            text-align: center;
        }

        .bottom-right i {
            margin: 0;
        }

        .sticky-header {
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .w3-sidebar {
            z-index: 1100;
            position: fixed;
            left: -250px;
            width: 250px;
            height: 100%;
            overflow-x: hidden;
            transition: 0.5s;
            padding-top: 0;
        }

        .w3-sidebar.show {
            left: 0;
        }

        .w3-sidebar.hide {
            left: -250px;
        }

        .w3-sidebar a {
            padding: 10px;
            text-decoration: none;
            font-size: 18px;
            color: black;
            display: block;
        }

        .w3-sidebar a:hover {
            background-color: #ddd;
        }

        .w3-sidebar .close-button {
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
            padding: 15px;
            background-color: #f44336;
            color: white;
            font-size: 20px;
            text-align: center;
            border: none;
            cursor: pointer;
        }

        .w3-sidebar-overlay {
            display: none;
            position: fixed;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }

        .w3-sidebar-overlay.show {
            display: block;
        }
    </style>
</head>
<!-- 
<?php if (isset($_GET['message']) && $_GET['message'] == 'deleted') { ?>
    <p style="color: green;">Data toko berhasil dihapus.</p>
<?php } elseif (isset($_GET['message']) && $_GET['message'] == 'error') { ?>
    <p style="color: red;">Terjadi kesalahan saat menghapus data toko.</p>
<?php } ?>
 -->

<body class="w3-light-grey">
    <!-- Sidebar Overlay -->
    <div id="sidebarOverlay" class="w3-sidebar-overlay" onclick="w3_close()"></div>

    <!-- Sidebar -->
    <div class="w3-sidebar w3-bar-block w3-border-right w3-light-grey" id="mySidebar">
        <button onclick="w3_close()" class="w3-bar-item w3-button w3-red w3-center close-button">
            <b>Close</b><i class="fa fa-close" style="font-size:20px"></i>
        </button>
        <a href="list_barang.php" class="w3-bar-item w3-button w3-border">List Barang</a>
        <?php if ($username === 'admin') { ?>
            <a href="list_pengguna.php" class="w3-bar-item w3-button w3-border">List Pengguna</a>
        <?php } ?>
        <a href="list_meja.php" class="w3-bar-item w3-button w3-border">List Meja</a>
        <a href="list_pesanan.php" class="w3-bar-item w3-button w3-border">List Pesanan</a>
        <?php if ($username === 'admin') { ?>
            <a href="list_toko.php" class="w3-bar-item w3-button w3-border">List Toko</a>
        <?php } ?>
        <a href="pesanan.php" class="w3-bar-item w3-button w3-border">Pesanan</a>
        <a href="logout.php" class="w3-bar-item w3-button w3-red w3-center"><b>Log Out </b><i class="fa fa-sign-out"
                style="font-size:20px"></i></a>
    </div>

    <!-- Page Content -->
    <div id="mainContent" style="margin-left: 0; transition: margin-left 0.5s;">
        <div class="w3-teal sticky-header" style="display: flex; align-items: center; padding: 10px;">
            <button class="w3-button w3-xlarge" onclick="w3_open()">☰</button>
            <div style="flex-grow: 1; display: flex; flex-direction: column; justify-content: center;">
                <h1
                    style="margin: 0; line-height: 1.5rem; text-align: center; font-size: 30px; margin-top:5px; margin-bottom: 10px;">
                    <b>List Toko</b>
                </h1>
            </div>
        </div>
        <div style="display: flex; justify-content: center; margin: 20px;">
            <form method="GET" action="" style="width: 100%; max-width: 600px; display: flex; position: relative;">
                <input type="text" name="search" id="searchInput" class="w3-input w3-border" placeholder="Cari Toko..."
                    value="<?php echo htmlspecialchars($search_keyword); ?>"
                    style="border-radius:20px; flex: 1; padding-right: 40px;">
                <button type="submit" class="w3-button w3-green"
                    style="border-radius:20px; position: absolute; right: 10px; top: 50%; transform: translateY(-50%); border: none; background: transparent; cursor: pointer;">
                    <i class="fa fa-search" style="font-size: 16px; color: white;"></i>
                    <!-- Ganti dengan ikon pencarian -->
                </button>
            </form>
        </div>
        <div style="font-size: 15px; text-align: right; padding-right: 30px;">
            <span class="w3-bar-item">Total: <?php echo $total_records; ?> Toko</span>
        </div>

        <!-- Main Content -->

        <div class="w3-responsive">
            <table class="w3-table-all w3-centered" id="barangTable">
                <thead>
                    <tr class="w3-teal">
                        <th style="text-align: center;">
                            <span style="font-size: 16px; font-weight: bold;">Nama toko</span>
                        </th>
                        <th style="text-align: center;">
                            <span style="font-size: 16px; font-weight: bold;">Alamat</span>
                        </th>
                        <th style="text-align: center;">
                            <span style="font-size: 16px; font-weight: bold;">Aksi</span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (mysqli_num_rows($result) > 0) {
                        while ($row = mysqli_fetch_assoc($result)) {
                            echo "<tr>";
                            echo "<td>" . $row['NAMATOKO'] . "</td>";
                            echo "<td>" . $row['ALAMAT'] . "</td>";
                            echo "<td class='action-icons'>";

                            // Tombol "Lihat Lainnya" selalu muncul
                            echo "<button onclick='showModal(" . json_encode($row) . ")' class='w3-button w3-teal w3-round' style='margin-right: 10px; margin-left: 5px; font-size: 12px;'>Lihat Lainnya</button>";

                            // Tombol edit dan delete hanya muncul untuk admin
                            if ($username === 'admin') {
                                echo "<a href='edit_toko.php?namatoko=" . urlencode($row['NAMATOKO']) . "' class='material-icons w3-yellow w3-btn w3-button w3-round' style='font-size: 15px; margin-right: 10px;'>&#xe22b;</a>";
                                echo "<button onclick=\"showDeleteModal('" . $row['IDTOKO'] . "')\" class='fa fa-trash w3-btn w3-button w3-round w3-red' style='font-size: 15px; margin-right: 10px;'></button>";
                            }

                            echo "</td>";
                            echo "</tr>";
                        }
                    }
                    ?>
                </tbody>
            </table>

            <div class="w3-bar">
                <a href="?page=<?php echo max(1, $page - 1); ?>&search=<?php echo urlencode($search_keyword); ?>"
                    class="w3-button w3-teal">&laquo;</a>
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search_keyword); ?>"
                        class="w3-button <?php echo ($i == $page) ? 'w3-grey' : ''; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>
                <a href="?page=<?php echo min($total_pages, $page + 1); ?>&search=<?php echo urlencode($search_keyword); ?>"
                    class="w3-button w3-teal">&raquo;</a>
            </div>
        </div>
        <?php if ($username === 'admin') { ?>
            <div class="bottom-right" style="box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);">
                <a href="tambah_toko.php" class="w3-btn w3-round-xlarge w3-green bottom-right">
                    <i class="fa fa-plus" style="font-size: 30px;"></i>
                </a>
            </div>
        <?php } ?>
    </div>

    <!-- Modal untuk Menampilkan Informasi Lengkap -->
    <div id="detailsModal" class="w3-modal" style="display: none; z-index: 9999;">
        <div class="w3-modal-content w3-animate-top w3-card-4" style="width: 50%;">
            <header class="w3-container w3-teal w3-center">
                <span onclick="closeModal()" class="w3-button w3-display-topright">&times;</span>
                <h2>Detail Toko</h2>
            </header>
            <div class="w3-container">
                <p><b>ID Toko:</b> <span id="modalID"></span></p>
                <p><b>Nama Toko:</b> <span id="modalNama"></span></p>
                <p><b>Alamat:</b> <span id="modalAlamat"></span></p>
                <p><b>Telp 1:</b> <span id="modalTelp1"></span></p>
                <p><b>Telp 2:</b> <span id="modalTelp2"></span></p>
                <p><b>Lokasi:</b> <span id="modalLokasi"></span></p>
            </div>
            <footer class="w3-container" style="position: relative; height: 50px;">
                <button onclick="closeModal()" class="w3-button w3-round w3-red"
                    style="position: absolute; bottom: 10px; right: 10px;">Tutup</button>
            </footer>
        </div>
    </div>

    <!-- Modal Konfirmasi Penghapusan -->
    <div id="deleteModal" class="w3-modal" style="display: none; z-index: 9999;">
        <div class="w3-modal-content w3-animate-top w3-card-4">
            <header class="w3-container w3-red">
                <span onclick="closeDeleteModal()" class="w3-button w3-display-topright">&times;</span>
                <h2>Konfirmasi</h2>
            </header>
            <div class="w3-container">
                <p>Apakah Anda yakin ingin menghapus toko ini?</p>
                <div class="w3-right">
                    <button onclick="closeDeleteModal()" class="w3-button w3-grey">Batal</button>
                    <a id="confirmDeleteBtn" href="#" class="w3-button w3-red">Hapus</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Script JavaScript -->
    <script>
        function showModal(rowData) {
            // Isi data ke dalam modal
            document.getElementById('modalID').innerText = rowData.IDTOKO;
            document.getElementById('modalNama').innerText = rowData.NAMATOKO;
            document.getElementById('modalAlamat').innerText = rowData.ALAMAT;
            document.getElementById('modalTelp1').innerText = rowData.TELP1 || '-';
            document.getElementById('modalTelp2').innerText = rowData.TELP2 || '-';
            document.getElementById('modalLokasi').innerText = rowData.LOKASI || '-';

            // Tampilkan modal
            document.getElementById('detailsModal').style.display = 'block';
        }

        function closeModal() {
            // Sembunyikan modal
            document.getElementById('detailsModal').style.display = 'none';
        }

        function showDeleteModal(idToko) {
            // Tampilkan modal
            document.getElementById('deleteModal').style.display = 'block';
            // Set tautan penghapusan dengan ID toko yang dipilih
            document.getElementById('confirmDeleteBtn').href = 'delete_toko.php?id=' + idToko;
        }

        function closeDeleteModal() {
            // Sembunyikan modal
            document.getElementById('deleteModal').style.display = 'none';
        }


        function w3_open() {
            document.getElementById("mySidebar").classList.add("show");
            document.getElementById("sidebarOverlay").classList.add("show");
        }

        function w3_close() {
            document.getElementById("mySidebar").classList.remove("show");
            document.getElementById("sidebarOverlay").classList.remove("show");
        }
    </script>
</body>

</html>