<?php
include 'koneksi.php'; // Include the database connection file
date_default_timezone_set('Asia/Jakarta'); // Set the timezone

session_start(); // Start the session to access session information

// Ensure the user is logged in
if (!isset($_SESSION['username'])) {
    header('Location: index.php'); // Redirect if the user is not logged in
    exit;
}

$username = $_SESSION['username']; // Get the username from the session

// Cek apakah pengguna adalah admin
$is_admin = ($username === 'admin'); // Jika username adalah 'admin', maka pengguna dianggap admin

// Query to fetch user data based on the username
$query = "SELECT p.*, t.NAMATOKO FROM eat_and_go_pengguna p 
          LEFT JOIN eat_and_go_toko t ON p.IDTOKO = t.IDTOKO 
          WHERE p.username = '$username'";
$result = mysqli_query($conn, $query);

if (!$result) {
    die("Query error: " . mysqli_error($conn));
}

// Fetch user data
if (mysqli_num_rows($result) > 0) {
    $eat_and_go_pengguna = mysqli_fetch_assoc($result);
    $user_record = $eat_and_go_pengguna['username']; // Get the username value from the user
    $IDTOKO = $eat_and_go_pengguna['IDTOKO']; // Get the IDTOKO of the user
    $NAMATOKO = $eat_and_go_pengguna['NAMATOKO']; // Get the NAMATOKO
} else {
    $user_record = ''; // Or adjust with error handling logic
    $IDTOKO = null;
    $NAMATOKO = "Tidak Diketahui"; // Default value if the name is not found
}

// Handle form submission
// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'tambah') {
    $nomeja = mysqli_real_escape_string($conn, $_POST['nomeja']);
    $jumlahkursi = mysqli_real_escape_string($conn, $_POST['jumlahkursi']);
    $keterangan = mysqli_real_escape_string($conn, $_POST['keterangan']);
    $now = date('Y-m-d H:i:s');

    // Jika pengguna adalah admin, ambil IDTOKO dari input form
    if ($is_admin) {
        $IDTOKO = isset($_POST['id_toko']) ? mysqli_real_escape_string($conn, $_POST['id_toko']) : null;

        if (empty($IDTOKO)) {
            echo "Admin harus memilih toko.";
            exit;
        }
    }

    // Tentukan nilai idmeja baru (optional jika tidak auto-increment)
    $next_id_query = "SELECT MAX(idmeja) + 1 AS next_id FROM eat_and_go_meja";
    $next_id_result = mysqli_query($conn, $next_id_query);
    $next_id = 1; // Default jika tabel kosong
    if ($next_id_result) {
        $row = mysqli_fetch_assoc($next_id_result);
        $next_id = $row['next_id'] ?? 1;
    }

    // Check if the table number already exists for the specific IDTOKO
    $check_query = "SELECT COUNT(*) as count FROM eat_and_go_meja WHERE nomeja = '$nomeja' AND IDTOKO = '$IDTOKO'";
    $check_result = mysqli_query($conn, $check_query);

    if ($check_result) {
        $row = mysqli_fetch_assoc($check_result);
        if ($row['count'] > 0) {
            // If table number already exists, set variable to show modal
            $showModal = true;
        } else {
            // Insert data with idmeja and IDTOKO
            $sql_meja = "INSERT INTO eat_and_go_meja (idmeja, nomeja, jumlahkursi, keterangan, IDTOKO) 
                         VALUES ('$next_id', '$nomeja', '$jumlahkursi', '$keterangan', '$IDTOKO')";

            if (mysqli_query($conn, $sql_meja)) {
                header("Location: list_meja.php");
                exit; // Important to stop script execution after redirect
            } else {
                echo "Error: " . $sql_meja . "<br>" . mysqli_error($conn);
            }
        }
    } else {
        echo "Error: " . $check_query . "<br>" . mysqli_error($conn);
    }
}


mysqli_close($conn); // Close the database connection
?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Meja</title>
    <link rel="shortcut icon" href="kursi.svg" type="image/svg+xml">
    <link rel="stylesheet" href="w3.css"> <!-- Adjust with your CSS location -->
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
    <div class="w3-teal" style="display: flex; align-items: center;">
        <button class="w3-button w3-xlarge" onclick="w3_open()">☰</button>
        <div style="flex-grow: 1; display: flex; justify-content: center;">
            <h1 style="margin: 0; line-height: 3.5rem; margin-bottom:10px;"><b>Tambah Meja</b></h1>
        </div>
    </div>

    <!-- Modal to display error message -->
    <div id="myModal" class="w3-modal"
        style="display:<?php echo isset($showModal) && $showModal ? 'block' : 'none'; ?>">
        <div class="w3-modal-content w3-animate-top w3-card-4">
            <header class="w3-container w3-red">
                <span onclick="document.getElementById('myModal').style.display='none'"
                    class="w3-button w3-display-topright">&times;</span>
                <h2>Informasi</h2>
            </header>
            <div class="w3-container">
                <p>Nomor Meja sudah ada. Silakan gunakan Nomor Meja yang lain.</p>
            </div>
        </div>
    </div>

    <!-- Form Tambah Meja -->
    <div id="mainContent" class="w3-padding-16">
        <form action="" method="post" class="w3-container w3-card-4 w3-light-grey w3-padding-16 w3-margin">
            <input type="hidden" name="action" value="tambah">
            <label>No Meja</label>
            <input type="text" class="w3-input w3-border w3-light-grey" name="nomeja" required><br>
            <label>Jumlah Kursi</label>
            <input type="number" class="w3-input w3-border w3-light-grey" name="jumlahkursi" required><br>
            <label>Keterangan</label>
            <input type="text" class="w3-input w3-border w3-light-grey" name="keterangan"><br>

            <?php if ($is_admin) { ?>
                <!-- Dropdown hanya untuk admin -->
                <label>Nama Toko</label>
                <select class="w3-select w3-border w3-light-grey" name="id_toko" required>
                    <option value="" disabled selected>Pilih Toko</option>
                    <?php
                    // Menghubungkan ke database untuk menampilkan daftar toko
                    include 'koneksi.php';
                    $query = "SELECT IDTOKO, NAMATOKO FROM eat_and_go_toko";  // Query untuk memilih semua toko
                    $result = mysqli_query($conn, $query);

                    while ($row = mysqli_fetch_assoc($result)) {
                        // Menampilkan toko dalam opsi dropdown
                        $selected = ($row['IDTOKO'] == $IDTOKO) ? 'selected' : ''; // Menandai toko yang sudah dipilih
                        echo "<option value='" . htmlspecialchars($row['IDTOKO']) . "' $selected>" . htmlspecialchars($row['NAMATOKO']) . "</option>";
                    }
                    ?>
                </select><br><br>
            <?php } else { ?>
                <!-- Tampilkan nama toko untuk pengguna biasa -->
                <input type="text" class="w3-input w3-border w3-light-grey"
                    value="<?php echo htmlspecialchars($NAMATOKO); ?>" readonly><br>
            <?php } ?>

            <input type="hidden" name="usermodified" value="<?php echo htmlspecialchars($username); ?>">
            <div class="w3-half">
                <a href="list_meja.php" class="w3-button w3-container w3-grey w3-padding-16"
                    style="width: 100%;">Kembali</a>
            </div>
            <div class="w3-half">
                <input type="submit" class="w3-button w3-green w3-container w3-padding-16" style="width: 100%;"
                    value="Tambah">
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

        document.getElementById('sidebarOverlay').addEventListener('click', w3_close);
    </script>
</body>

</html>