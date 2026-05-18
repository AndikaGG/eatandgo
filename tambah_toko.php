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

// Query to fetch user data based on the username
$query = "SELECT * FROM eat_and_go_pengguna WHERE username = '$username'";
$result = mysqli_query($conn, $query);

if (!$result) {
    die("Query error: " . mysqli_error($conn));
}

// Fetch user data
if (mysqli_num_rows($result) > 0) {
    $eat_and_go_pengguna = mysqli_fetch_assoc($result);
    $user_record = $eat_and_go_pengguna['username']; // Get the username value from the user
} else {
    $user_record = ''; // Or adjust with error handling logic
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'tambah') {
    $namatoko = mysqli_real_escape_string($conn, $_POST['namatoko']);
    $alamat = mysqli_real_escape_string($conn, $_POST['alamat']);
    $telp1 = mysqli_real_escape_string($conn, $_POST['telp1']);
    $telp2 = mysqli_real_escape_string($conn, $_POST['telp2']);
    $lokasi = mysqli_real_escape_string($conn, $_POST['lokasi']);
    $user_modified = mysqli_real_escape_string($conn, $_POST['usermodified']);

    // Check if the store name already exists
    $check_query = "SELECT COUNT(*) as count FROM eat_and_go_toko WHERE NAMATOKO = '$namatoko'";
    $check_result = mysqli_query($conn, $check_query);

    if ($check_result) {
        $row = mysqli_fetch_assoc($check_result);
        if ($row['count'] > 0) {
            // If store name already exists, set variable to show modal
            $showModal = true;
        } else {
            // If store name doesn't exist, insert data
            $sql_toko = "INSERT INTO eat_and_go_toko (NAMATOKO, ALAMAT, TELP1, TELP2, LOKASI) 
                         VALUES ('$namatoko', '$alamat', '$telp1', '$telp2', '$lokasi')";

            if (mysqli_query($conn, $sql_toko)) {
                header("Location: list_toko.php");
                exit; // Important to stop script execution after redirect
            } else {
                echo "Error: " . $sql_toko . "<br>" . mysqli_error($conn);
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
    <title>Tambah Toko</title>
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
            <h1 style="margin: 0; line-height: 3.5rem; margin-bottom:10px;"><b>Tambah Toko</b></h1>
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
                <p>Nama Toko sudah ada. Silakan gunakan Nama Toko yang lain.</p>
            </div>
        </div>
    </div>

    <!-- Form Tambah Toko -->
    <div id="mainContent" class="w3-padding-16">
        <form action="" method="post" class="w3-container w3-card-4 w3-light-grey w3-padding-16 w3-margin">

            <label for="namatoko">Nama Toko</label>
            <input type="text" id="namatoko" name="namatoko" class="w3-input w3-border w3-light-grey" required><br>

            <label for="alamat">Alamat</label>
            <input type="text" id="alamat" name="alamat" class="w3-input w3-border w3-light-grey" required><br>

            <label for="telp1">No. Telepon 1</label>
            <input type="number" id="telp1" name="telp1" class="w3-input w3-border w3-light-grey" required><br>

            <label for="telp2">No. Telepon 2 (Optional)</label>
            <input type="number" id="telp2" name="telp2" class="w3-input w3-border w3-light-grey"><br>

            <label for="lokasi">Lokasi</label>
            <input type="text" id="lokasi" name="lokasi" class="w3-input w3-border w3-light-grey" required><br>

            <input type="hidden" name="usermodified" value="<?php echo $username; ?>">

            <div class="w3-half">
                <a href="list_toko.php" class="w3-button w3-container w3-grey w3-padding-16"
                    style="width: 100%;">Kembali</a>
            </div>
            <div class="w3-half">
                <button type="submit" class="w3-button w3-green w3-container w3-padding-16" style="width: 100%;"
                    name="action" value="tambah">Tambah</button>
            </div>


        </form>
    </div>

    <script>
        // Sidebar functionality
        function w3_open() {
            document.getElementById("mySidebar").classList.add('show');
            document.getElementById("sidebarOverlay").classList.add('show');
        }

        function w3_close() {
            document.getElementById("mySidebar").classList.remove('show');
            document.getElementById("sidebarOverlay").classList.remove('show');
        }
    </script>
</body>

</html>