<?php
include 'koneksi.php'; // Include the database connection file
session_start(); // Start the session to access user information

// Ensure the user is logged in
if (!isset($_SESSION['username'])) {
    header('Location: index.php'); // Redirect if the user is not logged in
    exit;
}

$username = $_SESSION['username'];
$action = isset($_POST['action']) ? $_POST['action'] : '';
$IDTOKO = null;
$nama_toko = '';

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
    // Handle edit operation
    $idmeja = mysqli_real_escape_string($conn, $_POST['idmeja']);
    $nomeja = mysqli_real_escape_string($conn, $_POST['nomeja']);
    $jumlahkursi = (int)$_POST['jumlahkursi'];
    $keterangan = mysqli_real_escape_string($conn, $_POST['keterangan']);

    // Jika admin, ambil IDTOKO berdasarkan idmeja
    if ($username === 'admin') {
        $query_get_idtoko = "SELECT IDTOKO FROM eat_and_go_meja WHERE idmeja = ?";
        $stmt_get_idtoko = $conn->prepare($query_get_idtoko);
        $stmt_get_idtoko->bind_param('s', $idmeja);
        $stmt_get_idtoko->execute();
        $result_get_idtoko = $stmt_get_idtoko->get_result();
        if ($result_get_idtoko->num_rows > 0) {
            $row_toko = $result_get_idtoko->fetch_assoc();
            $IDTOKO = $row_toko['IDTOKO'];
        } else {
            die("IDTOKO untuk idmeja tidak ditemukan.");
        }
    }

    // Query update meja
    $query = "UPDATE eat_and_go_meja SET nomeja=?, jumlahkursi=?, keterangan=? WHERE idmeja=?";
    if (!empty($IDTOKO)) {
        $query .= " AND IDTOKO=?";
    }
    $stmt = $conn->prepare($query);
    if (empty($IDTOKO)) {
        $stmt->bind_param('siss', $nomeja, $jumlahkursi, $keterangan, $idmeja);
    } else {
        $stmt->bind_param('sissi', $nomeja, $jumlahkursi, $keterangan, $idmeja, $IDTOKO);
    }

    // Eksekusi query
    if ($stmt->execute()) {
        header('Location: list_meja.php');
        exit;
    } else {
        die("Update gagal: " . $stmt->error);
    }
}

// Ambil data untuk edit meja
$idmeja = isset($_GET['idmeja']) ? $_GET['idmeja'] : null;

if (!$idmeja) {
    die("ID meja tidak ditemukan.");
}

// Query untuk mengambil data meja
$sql_meja = "SELECT * FROM eat_and_go_meja WHERE idmeja=?";
if (!empty($IDTOKO)) {
    $sql_meja .= " AND IDTOKO=?";
}
$stmt_meja = $conn->prepare($sql_meja);
if (empty($IDTOKO)) {
    $stmt_meja->bind_param('s', $idmeja);
} else {
    $stmt_meja->bind_param('si', $idmeja, $IDTOKO);
}
$stmt_meja->execute();
$result_meja = $stmt_meja->get_result();

if ($result_meja->num_rows > 0) {
    $row_meja = $result_meja->fetch_assoc();
  
    $IDTOKO = $row_meja['IDTOKO'];

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
    die("Data meja dengan ID $idmeja tidak ditemukan.");
}

$conn->close();
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Meja</title>
    <link rel="shortcut icon" href="editbarang.svg" type="image/svg+xml">
    <link rel="stylesheet" href="w3.css"> <!-- Adjust to your CSS location -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        /* Sidebar styling */
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
            <h1 style="margin: 0; line-height: 3.5rem; margin-bottom:10px; font-size: 30px;"><b>Edit Meja</b></h1>
        </div>
    </div>

    <!-- Edit Meja Form -->
    <div id="mainContent">
        <form action="" method="post"
            class="w3-container w3-card-4 w3-light-grey w3-padding-16 w3-margin">

            <input type="hidden" name="idmeja" value="<?php echo htmlspecialchars($idmeja); ?>">
            <label for="nomeja">No Meja</label>
            <input type="text" id="nomeja" name="nomeja" class="w3-input w3-border w3-light-grey"
                value="<?php echo htmlspecialchars($row_meja['nomeja']); ?>" readonly><br>
            <input type="hidden" name="nomeja" value="<?php echo htmlspecialchars($row_meja['nomeja']); ?>">
            <label for="jumlahkursi">Jumlah Kursi</label>
            <input type="number" id="jumlahkursi" name="jumlahkursi" class="w3-input w3-border w3-light-grey"
                value="<?php echo htmlspecialchars($row_meja['jumlahkursi']); ?>" oninput="checkChanges()" required><br>
            <label for="keterangan">Keterangan</label>
            <input type="text" id="keterangan" name="keterangan" class="w3-input w3-border w3-light-grey"
                value="<?php echo htmlspecialchars($row_meja['keterangan']); ?>" oninput="checkChanges()" required><br>

                <label>Nama Toko</label>
            <input type="hidden" name="id_toko" value="<?php echo htmlspecialchars($IDTOKO); ?>">
            <input type="text" class="w3-input w3-border w3-light-grey"
                value="<?php echo htmlspecialchars($nama_toko); ?>" readonly><br>


            <div class="w3-half">
                <a href="list_meja.php" class="w3-button w3-grey w3-padding-16" style="width: 100%;">Kembali</a>
            </div>
            <div class="w3-half">
                <input type="hidden" name="action" value="edit">
                <input type="submit" id="updateButton" class="w3-button w3-green w3-padding-16" style="width: 100%;"
                    value="Update" disabled>
            </div>
        </form>
    </div>

    <script>
        // Function to open the sidebar
        function w3_open() {
            document.getElementById("mySidebar").classList.add("show");
            document.getElementById("mySidebar").classList.remove("hide");
            document.getElementById("sidebarOverlay").classList.add("show");
        }

        // Function to close the sidebar
        function w3_close() {
            document.getElementById("mySidebar").classList.remove("show");
            document.getElementById("mySidebar").classList.add("hide");
            document.getElementById("sidebarOverlay").classList.remove("show");
        }

    function checkChanges() {
    const originalValues = {
        nomeja: "<?php echo htmlspecialchars($row_meja['nomeja']); ?>",
        jumlahkursi: "<?php echo htmlspecialchars($row_meja['jumlahkursi']); ?>",
        keterangan: "<?php echo htmlspecialchars($row_meja['keterangan']); ?>"
    };

    const currentValues = {
        nomeja: document.getElementById("nomeja").value,
        jumlahkursi: document.getElementById("jumlahkursi").value,
        keterangan: document.getElementById("keterangan").value
    };

    const hasChanges = Object.keys(originalValues).some(key => originalValues[key] != currentValues[key]);
    document.getElementById("updateButton").disabled = !hasChanges;
}

    </script>
</body>

</html>

