<?php
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

session_start();

if (!isset($_SESSION['username'])) {
    header('Location: index.php');
    exit;
}

$username = $_SESSION['username'];
$is_admin = ($username === 'admin'); // Cek apakah pengguna adalah admin

// Query untuk mengambil data pengguna dan IDTOKO
$query = "SELECT pengguna.*, toko.NAMATOKO 
          FROM eat_and_go_pengguna AS pengguna
          LEFT JOIN eat_and_go_toko AS toko ON pengguna.IDTOKO = toko.IDTOKO
          WHERE pengguna.username = '$username'";
$result = mysqli_query($conn, $query);

if (!$result) {
    die("Query error: " . mysqli_error($conn));
}

if (mysqli_num_rows($result) > 0) {
    $user_data = mysqli_fetch_assoc($result);
    $user_record = $user_data['username'];
    $IDTOKO = $is_admin ? null : $user_data['IDTOKO'];
    $nama_toko = $is_admin ? null : ($user_data['NAMATOKO'] ?? null); // Ambil nama toko
} else {
    $user_record = '';
    $IDTOKO = null;
    $nama_toko = null;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'tambah') {
    $kode_barang = $_POST['kodebarang'];
    $nama_barang = $_POST['namabarang'];
    $jenis_barang = $_POST['jenisbarang'];
    $stok_baru = $_POST['stok']; // Stok baru yang ditambahkan
    $harga = str_replace('.', '', $_POST['harga']); // Hapus titik dari harga
    $now = date('Y-m-d H:i:s');
    $user_modified = $_POST['usermodified'];

    // Jika admin, ambil IDTOKO dari input form
    if ($is_admin) {
        $IDTOKO = $_POST['id_toko'];
    }

    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == UPLOAD_ERR_OK) {
        $fileSize = $_FILES['gambar']['size'];
        $maxSize = 1 * 1024 * 1024; // 1 MB

        if ($fileSize > $maxSize) {
            echo '<script>
                    document.addEventListener("DOMContentLoaded", function() {
                        var modal = document.getElementById("fileSizeModal");
                        modal.style.display = "block";
                    });
                  </script>';
            $gambarData = null;
        } else {
            $gambar = $_FILES['gambar']['tmp_name'];
            $gambarData = file_get_contents($gambar);
            if ($gambarData === false) {
                echo "Error reading file data.";
            }
        }
    } else {
        $gambarData = null;
    }

    if ($IDTOKO !== null) { // Pastikan IDTOKO tersedia
        // Cek apakah kode barang sudah ada untuk IDTOKO yang sama
        $sql_barang = "INSERT INTO eat_and_go_barang (kodebarang, namabarang, jenis, harga, gambar, stok, IDTOKO, userrecord, daterecord, datemodified, usermodified)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt_insert = mysqli_prepare($conn, $sql_barang);
        mysqli_stmt_bind_param($stmt_insert, 'sssssssssss', $kode_barang, $nama_barang, $jenis_barang, $harga, $gambarData, $stok_baru, $IDTOKO, $user_record, $now, $now, $user_modified);

        try {
            if (!mysqli_stmt_execute($stmt_insert)) {
                throw new Exception("Duplicate entry detected for primary key: " . $kode_barang);
            }
            header("Location: list_barang.php");
            exit;
        } catch (Exception $e) {
            // Tangkap error dan tampilkan modal W3CSS
            echo '<script>
                    document.addEventListener("DOMContentLoaded", function() {
                        var modal = document.getElementById("duplicateEntryModal");
                        modal.style.display = "block";
                    });
                  </script>';
        }

        mysqli_stmt_close($stmt_insert);
    } else {
        echo "IDTOKO tidak ditemukan.";
    }
}

mysqli_close($conn);
?>

<!-- Modal W3CSS: Duplikasi Entri -->
<div id="duplicateEntryModal" class="w3-modal" style="display:none; z-index: 9999;">
    <div class="w3-modal-content w3-animate-top">
        <header class="w3-container w3-teal">
            <span onclick="document.getElementById('duplicateEntryModal').style.display='none'"
                class="w3-button w3-display-topright">&times;</span>
            <h2>Informasi</h2>
        </header>
        <div class="w3-container">
            <p>kode barang tersebut sudah ada/terpakai. Silakan coba kode barang lain.</p>
        </div>
        <footer class="w3-container w3-white" style="text-align: right;">
            <button onclick="document.getElementById('duplicateEntryModal').style.display='none'"
                class="w3-button w3-teal">OK</button>
        </footer>
    </div>
</div>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Barang</title>
    <link rel="shortcut icon" href="tambahbarang.svg" type="image/svg+xml">
    <link rel="stylesheet" href="w3.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        .action-icons {
            display: flex;
            justify-content: space-around;
            margin-top: 10px;
        }

        .sticky-header {
            position: sticky;
            top: 0;
            z-index: 1000;
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
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.3);
        }

        .bottom-right i {
            margin: 0;
        }

        .w3-sidebar {
            z-index: 1100;
            position: fixed;
            left: -250px;
            top: 0;
            width: 250px;
            height: 100%;
            overflow-x: hidden;
            transition: 0.5s;
            padding-top: 0;
            background-color: #f4f4f4;
            border-right: 1px solid #ccc;
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

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
    </style>
</head>

<body>
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

    <script>
        function w3_open() {
            document.getElementById("mySidebar").classList.add("show");
            document.getElementById("sidebarOverlay").classList.add("show");
        }

        function w3_close() {
            document.getElementById("mySidebar").classList.remove("show");
            document.getElementById("sidebarOverlay").classList.remove("show");
        }

        document.getElementById('sidebarOverlay').addEventListener('click', w3_close);

        function formatNumberInput(input) {
            let value = input.value.replace(/\./g, ''); // Hapus titik yang ada
            if (!isNaN(value) && value.trim() !== '') {
                input.value = new Intl.NumberFormat('id-ID').format(value); // Format dengan titik sebagai pemisah ribuan
            }
        }
    </script>

    <!-- Header -->
    <div class="w3-teal fixed-header" style="display: flex; align-items: center;">
        <button class="w3-button w3-xlarge" onclick="w3_open()">☰</button>
        <div style="flex-grow: 1; display: flex; justify-content: center;">
            <h1 style="margin: 0; line-height: 3.5rem; margin-bottom:10px;"><b>Tambah Barang</b></h1>
        </div>
    </div>

    <style>
        /* Fixed header */
        .fixed-header {
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
        }

        /* Add padding to body to prevent content from being hidden under the fixed header */
        body {
            padding-top: 70px;
            /* Adjust based on the height of the header */
        }
    </style>

    <!-- Form -->
    <form action="tambah_barang.php" method="post" enctype="multipart/form-data"
        class="w3-container w3-card-4 w3-light-grey w3-padding-16 w3-margin">
        <label>Kode Barang</label>
        <input type="text" class="w3-input w3-border w3-light-grey" name="kodebarang" required><br>
        <label>Nama Barang</label>
        <input type="text" class="w3-input w3-border w3-light-grey" name="namabarang" required><br>
        <label>Jenis Barang</label>
        <select class="w3-select w3-border w3-light-grey" name="jenisbarang" required>
            <option value="" disabled selected>Pilih Jenis Barang</option>
            <option value="MAKANAN">MAKANAN</option>
            <option value="MINUMAN">MINUMAN</option>
        </select>
        <br><br>
        <label>Harga</label>
        <input type="number" class="w3-input w3-border w3-light-grey" name="harga" required
            oninput="formatNumberInput(this)"><br>
        <label>Stok</label>
        <input type="number" class="w3-input w3-border w3-light-grey" name="stok" required min="1"><br>

        <label>Nama Toko</label>
        <?php if ($is_admin) { ?>
            <!-- Dropdown untuk admin -->
            <select class="w3-select w3-border w3-light-grey" name="id_toko" required>
                <option value="" disabled selected>Pilih Toko</option>
                <?php
                include 'koneksi.php';
                $query = "SELECT IDTOKO, NAMATOKO FROM eat_and_go_toko";
                $result = mysqli_query($conn, $query);

                while ($row = mysqli_fetch_assoc($result)) {
                    echo "<option value='" . htmlspecialchars($row['IDTOKO']) . "'>" . htmlspecialchars($row['NAMATOKO']) . "</option>";
                }
                ?>
            </select><br><br>
        <?php } else { ?>
            <!-- Tampilkan nama toko tanpa input untuk pengguna biasa -->
            <input type="hidden" name="id_toko" value="<?php echo htmlspecialchars($IDTOKO); ?>">
            <input type="text" class="w3-input w3-border w3-light-grey" value="<?php echo htmlspecialchars($nama_toko); ?>"
                readonly><br>
        <?php } ?>

        <label>Gambar</label>
        <input type="file" class="w3-input w3-border w3-light-grey" name="gambar" accept="image/*" required><br>
        <input type="hidden" name="usermodified" value="<?php echo htmlspecialchars($username); ?>">
        <div class="w3-half">
            <a href="list_barang.php" class="w3-button w3-container w3-grey w3-padding-16"
                style="width: 100%;">Kembali</a>
        </div>
        <div class="w3-half">
            <input type="hidden" name="action" value="tambah">
            <input type="submit" class="w3-button w3-green w3-container w3-padding-16" style="width: 100%;"
                value="Tambah">
        </div>
        <div id="fileSizeModal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <p> Maaf ukuran file terlalu besar! Maksimum 1 MB.</p>
            </div>
        </div>

        <script>
            // Modal JavaScript
            var modal = document.getElementById("fileSizeModal");
            var span = document.getElementsByClassName("close")[0];

            span.onclick = function () {
                modal.style.display = "none";
            }

            window.onclick = function (event) {
                if (event.target == modal) {
                    modal.style.display = "none";
                }
            }
        </script>
    </form>
</body>

</html>