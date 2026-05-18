<?php
include 'koneksi.php'; // Include the database connection file
session_start(); // Start the session to access user information

// Ensure the user is logged in
if (!isset($_SESSION['username'])) {
    header('Location: index.php'); // Redirect if the user is not logged in
    exit;
}

$username = $_SESSION['username'];

// Get the ID of the store to be edited from the GET parameter
$namatoko = isset($_GET['namatoko']) ? $_GET['namatoko'] : null;

if (!$namatoko) {
    die("Nama Toko tidak ditemukan.");
}

// Query to get store data
$sql_toko = "SELECT * FROM eat_and_go_toko WHERE NAMATOKO=?";
$stmt_toko = $conn->prepare($sql_toko);
$stmt_toko->bind_param('s', $namatoko);
$stmt_toko->execute();
$result_toko = $stmt_toko->get_result();

if (!$result_toko) {
    die("Query error: " . $conn->error);
}

// Check if store data is found
if ($result_toko->num_rows > 0) {
    $row_toko = $result_toko->fetch_assoc();
} else {
    die("Data toko dengan nama $namatoko tidak ditemukan."); // Error if store is not found
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Toko</title>
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
            <h1 style="margin: 0; line-height: 3.5rem; margin-bottom:10px; font-size: 30px;"><b>Edit Toko</b></h1>
        </div>
    </div>

    <!-- Edit Toko Form -->
    <div id="mainContent">
        <form action="proses_toko.php" method="post"
            class="w3-container w3-card-4 w3-light-grey w3-padding-16 w3-margin">
            <label for="namatoko">Nama Toko</label>
            <input type="text" id="namatoko" name="namatoko" class="w3-input w3-border w3-light-grey"
                value="<?php echo htmlspecialchars($row_toko['NAMATOKO']); ?>" readonly><br>
            <input type="hidden" name="namatoko" value="<?php echo htmlspecialchars($row_toko['NAMATOKO']); ?>">
            <label for="alamat">Alamat</label>
            <input type="text" id="alamat" name="alamat" class="w3-input w3-border w3-light-grey"
                value="<?php echo htmlspecialchars($row_toko['ALAMAT']); ?>" oninput="checkChanges()" required><br>
            <label for="telp1">No. Telepon 1</label>
            <input type="text" id="telp1" name="telp1" class="w3-input w3-border w3-light-grey"
                value="<?php echo htmlspecialchars($row_toko['TELP1']); ?>" oninput="checkChanges()" required><br>
            <label for="telp2">No. Telepon 2 (Optional)</label>
            <input type="text" id="telp2" name="telp2" class="w3-input w3-border w3-light-grey"
                value="<?php echo htmlspecialchars($row_toko['TELP2']); ?>" oninput="checkChanges()"><br>
            <label for="lokasi">Lokasi</label>
            <input type="text" id="lokasi" name="lokasi" class="w3-input w3-border w3-light-grey"
                value="<?php echo htmlspecialchars($row_toko['LOKASI']); ?>" oninput="checkChanges()" required><br>
            <div class="w3-half">
                <a href="list_toko.php" class="w3-button w3-grey w3-padding-16" style="width: 100%;">Kembali</a>
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

        // Function to enable the update button when form values change
        function checkChanges() {
            const originalValues = {
                alamat: "<?php echo htmlspecialchars($row_toko['ALAMAT']); ?>",
                telp1: "<?php echo htmlspecialchars($row_toko['TELP1']); ?>",
                telp2: "<?php echo htmlspecialchars($row_toko['TELP2']); ?>",
                lokasi: "<?php echo htmlspecialchars($row_toko['LOKASI']); ?>"
            };
            let formChanged = false;

            if (document.getElementById("alamat").value !== originalValues.alamat ||
                document.getElementById("telp1").value !== originalValues.telp1 ||
                document.getElementById("telp2").value !== originalValues.telp2 ||
                document.getElementById("lokasi").value !== originalValues.lokasi) {
                formChanged = true;
            }

            document.getElementById("updateButton").disabled = !formChanged;
        }
    </script>
</body>

</html>