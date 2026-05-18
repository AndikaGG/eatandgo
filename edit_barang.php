<?php
include 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');
session_start();

// Pastikan pengguna sudah login
if (!isset($_SESSION['username'])) {
    header('Location: index.php');
    exit;
}

$username = $_SESSION['username'];
$action = isset($_POST['action']) ? $_POST['action'] : '';
$IDTOKO = null;
$nama_toko = ''; // Variabel untuk menyimpan nama toko

// Ambil IDTOKO pengguna dari database berdasarkan session username
$query = "SELECT IDTOKO FROM eat_and_go_pengguna WHERE username = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('s', $username);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $IDTOKO = $row['IDTOKO'];
} else {
    die("IDTOKO tidak ditemukan untuk pengguna ini.");
}

if ($action === 'edit') {
    // Handle form submission for editing
    $kodebarang = $_POST['kodebarang'];
    $namabarang = $_POST['namabarang'];
    $jenis = $_POST['jenis'];
    $stok = $_POST['stok']; // Menambahkan stok
    $harga = str_replace('.', '', $_POST['harga']); // Remove dots
    $harga = str_replace(',', '.', $harga); // Replace comma with dot for decimal
    $harga = floatval($harga); // Convert to float
    $user_modified = $username;
    $date_modified = date('Y-m-d H:i:s');

    // Handle image upload
    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == UPLOAD_ERR_OK) {
        // Check file size (1 MB limit)
        if ($_FILES['gambar']['size'] > 1 * 1024 * 1024) {
            die("File gambar lebih dari 1 MB.");
        }

        $gambar = file_get_contents($_FILES['gambar']['tmp_name']);
        $gambar = $conn->real_escape_string($gambar);
        $gambar_sql = ", gambar='$gambar'";
    } else {
        $gambar_sql = '';
    }

    // Admin bisa mengedit barang dari semua toko, non-admin hanya dapat mengedit barang dari toko mereka
    // Jika admin, kita harus ambil IDTOKO dari kodebarang yang ingin diedit
    if ($username == 'admin') { // Cek jika pengguna admin
        // Ambil IDTOKO berdasarkan kodebarang
        $query_get_idtoko = "SELECT IDTOKO FROM eat_and_go_barang WHERE kodebarang = ?";
        $stmt_get_idtoko = $conn->prepare($query_get_idtoko);
        $stmt_get_idtoko->bind_param('s', $kodebarang);
        $stmt_get_idtoko->execute();
        $result_get_idtoko = $stmt_get_idtoko->get_result();
        if ($result_get_idtoko->num_rows > 0) {
            $row_toko = $result_get_idtoko->fetch_assoc();
            $IDTOKO = $row_toko['IDTOKO'];
        } else {
            die("IDTOKO untuk kodebarang tidak ditemukan.");
        }
    }

    // Query update barang
    $query = "UPDATE eat_and_go_barang SET namabarang=?, jenis=?, stok=?, harga=?, usermodified=?, datemodified=? $gambar_sql WHERE kodebarang=?";
    if (!empty($IDTOKO)) {
        $query .= " AND IDTOKO=?";
    }
    $stmt = $conn->prepare($query);
    if (empty($IDTOKO)) {
        $stmt->bind_param('ssdsssi', $namabarang, $jenis, $stok, $harga, $user_modified, $date_modified, $kodebarang);
    } else {
        $stmt->bind_param('ssdssssi', $namabarang, $jenis, $stok, $harga, $user_modified, $date_modified, $kodebarang, $IDTOKO);
    }

    // Eksekusi query
    if ($stmt->execute()) {
        header('Location: list_barang.php');
        exit;
    } else {
        die("Update gagal: " . $stmt->error);
    }
}

// Ambil data untuk edit barang
$kodebarang = isset($_GET['kodebarang']) ? $_GET['kodebarang'] : null;

if (!$kodebarang) {
    die("Kode Barang tidak ditemukan.");
}

// Query untuk mengambil data barang yang sesuai dengan kodebarang dan IDTOKO
$sql_barang = "SELECT * FROM eat_and_go_barang WHERE kodebarang=?";
if (!empty($IDTOKO)) {
    $sql_barang .= " AND IDTOKO=?";
}
$stmt_barang = $conn->prepare($sql_barang);
if (empty($IDTOKO)) {
    $stmt_barang->bind_param('s', $kodebarang);
} else {
    $stmt_barang->bind_param('si', $kodebarang, $IDTOKO);
}
$stmt_barang->execute();
$result_barang = $stmt_barang->get_result();

if ($result_barang->num_rows > 0) {
    $row_barang = $result_barang->fetch_assoc();
    $user_modified = $row_barang['usermodified'];
    $IDTOKO = $row_barang['IDTOKO']; // Ambil IDTOKO terkait barang

    // Ambil nama toko berdasarkan IDTOKO
    $query_toko = "SELECT NAMATOKO FROM eat_and_go_toko WHERE IDTOKO = ?";
    $stmt_toko = $conn->prepare($query_toko);
    $stmt_toko->bind_param('i', $IDTOKO);
    $stmt_toko->execute();
    $result_toko = $stmt_toko->get_result();
    if ($result_toko->num_rows > 0) {
        $row_toko = $result_toko->fetch_assoc();
        $nama_toko = $row_toko['NAMATOKO'];
    } else {
        die("Nama toko tidak ditemukan untuk IDTOKO: $IDTOKO");
    }
} else {
    die("Data barang dengan kode $kodebarang tidak ditemukan.");
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Barang</title>
    <link rel="shortcut icon" href="editbarang.svg" type="image/svg+xml">
    <link rel="stylesheet" href="w3.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        /* Sidebar styling */
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
        }

        .w3-sidebar.show {
            left: 0;
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

        /* Sidebar overlay styling */
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

        /* Image styling */
        .w3-image {
            max-width: 100px;
            height: auto;
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

    <!-- Header -->
    <div class="w3-teal fixed-header" style="display: flex; align-items: center;">
        <button class="w3-button w3-xlarge" onclick="w3_open()">☰</button>
        <div style="flex-grow: 1; display: flex; justify-content: center;">
            <h1 style="margin: 0; line-height: 3.5rem; margin-bottom:10px; font-size: 30px;"><b>Edit Barang</b></h1>
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

    <!-- Edit Barang Form -->
    <div id="mainContent">
        <form action="" method="post" enctype="multipart/form-data"
            class="w3-container w3-card-4 w3-light-grey w3-padding-16 w3-margin">
            <label for="kodebarang">Kode Barang</label>
            <input type="text" id="kodebarang" name="kodebarang" class="w3-input w3-border w3-light-grey"
                value="<?php echo htmlspecialchars($row_barang['kodebarang']); ?>" readonly><br>

            <input type="hidden" name="kodebarang" value="<?php echo htmlspecialchars($row_barang['kodebarang']); ?>">
            <label for="namabarang">Nama Barang</label>
            <input type="text" id="namabarang" name="namabarang" class="w3-input w3-border w3-light-grey"
                value="<?php echo htmlspecialchars($row_barang['namabarang']); ?>" oninput="checkChanges()"
                required><br>

            <label for="jenis">Jenis Barang</label>
            <select id="jenis" name="jenis" class="w3-select w3-border w3-light-grey" onchange="checkChanges()"
                required>
                <option value="" disabled <?php echo empty($row_barang['jenis']) ? 'selected' : ''; ?>>Pilih Jenis
                    Barang</option>
                <option value="MAKANAN" <?php echo $row_barang['jenis'] == 'MAKANAN' ? 'selected' : ''; ?>>MAKANAN
                </option>
                <option value="MINUMAN" <?php echo $row_barang['jenis'] == 'MINUMAN' ? 'selected' : ''; ?>>MINUMAN
                </option>
            </select>
            <br><br>

            <label for="harga">Harga</label>
            <input type="text" id="harga" name="harga" class="w3-input w3-border w3-light-grey"
                value="<?php echo isset($row_barang['harga']) ? htmlspecialchars(number_format($row_barang['harga'], 0, ',', '.')) : ''; ?>"
                oninput="checkChanges(); formatCurrency(this);" required><br>

            <label for="stok">Stok Barang</label> <!-- Menambahkan stok -->
            <input type="number" id="stok" name="stok" class="w3-input w3-border w3-light-grey"
                value="<?php echo htmlspecialchars($row_barang['stok']); ?>" oninput="checkChanges()" required><br>

            <label>Nama Toko</label>
            <input type="hidden" name="id_toko" value="<?php echo htmlspecialchars($IDTOKO); ?>">
            <input type="text" class="w3-input w3-border w3-light-grey"
                value="<?php echo htmlspecialchars($nama_toko); ?>" readonly><br>


            <label for="gambar">Gambar Barang</label>
            <input type="file" id="gambar" name="gambar" class="w3-input w3-border" onchange="checkChanges();"><br>

            <?php if (!empty($row_barang['gambar'])): ?>
                <img src="data:image/jpeg;base64,<?php echo base64_encode($row_barang['gambar']); ?>" alt="Gambar Barang"
                    class="w3-image"><br>
            <?php endif; ?>

            <label for="usermodified">User Modified</label>
            <input type="text" id="usermodified" name="usermodified" class="w3-input w3-border w3-light-grey"
                value="<?php echo htmlspecialchars($user_modified); ?>" readonly><br>

            <div class="w3-half">
                <a href="list_barang.php" class="w3-button w3-grey w3-padding-16" style="width: 100%;">Kembali</a>
            </div>
            <div class="w3-half">
                <input type="hidden" name="action" value="edit">
                <input type="submit" id="updateButton" class="w3-button w3-green w3-padding-16" style="width: 100%;"
                    value="Update" disabled>
            </div>
        </form>
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

        // function checkChanges() {
        //     const updateButton = document.getElementById('updateButton');
        //     const formElements = document.querySelectorAll('input[type="text"], input[type="file"]');
        //     let isModified = false;

        //     formElements.forEach(element => {
        //         if (element.value !== element.defaultValue) {
        //             isModified = true;
        //         }
        //     });

        //     // Enable the update button if there are changes
        //     updateButton.disabled = !isModified;
        // }

        function checkChanges() {
            const updateButton = document.getElementById('updateButton');
            const formElements = document.querySelectorAll('input[type="text"], input[type="number"], input[type="file"], select');
            let isModified = false;

            formElements.forEach(element => {
                if (element.type === 'number') {
                    // Untuk input type="number", bandingkan dengan nilai default
                    if (element.value !== element.defaultValue.toString()) {
                        isModified = true;
                    }
                } else {
                    if (element.value !== element.defaultValue) {
                        isModified = true;
                    }
                }
            });

            // Enable the update button if there are changes
            updateButton.disabled = !isModified;
        }

        function checkFileSize(input) {
            const file = input.files[0];
            if (file && file.size > 1 * 1024 * 1024) { // 1 mb
                alert("ukuran file lebih dari 1 mb limit.");
                input.value = ''; // Clear the file input
            }
        }

        document.getElementById('gambar').addEventListener('change', function () {
            checkFileSize(this);
        });

        function formatCurrency(input) {
            let value = input.value.replace(/[^0-9]/g, ''); // Remove non-numeric characters
            if (value.length > 0) {
                value = parseFloat(value).toLocaleString('id-ID', { style: 'decimal' });
                input.value = value;
            }
        }
    </script>
</body>

</html>