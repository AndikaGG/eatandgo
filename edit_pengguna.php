<?php
include 'koneksi.php'; // Sertakan file koneksi ke database
session_start(); // Mulai sesi untuk mengakses informasi sesi pengguna

// Pastikan pengguna telah login sebelumnya
if (!isset($_SESSION['username'])) {
    header('Location: index.php'); // Redirect jika pengguna belum login
    exit;
}

$username = $_GET['username']; // Ambil username pengguna dari parameter GET

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $original_username = $_POST['original_username'];
    $new_username = $_POST['username'];
    $nama = $_POST['nama'];
    $password = !empty($_POST['password']) ? $_POST['password'] : null;
    $konfirmasi = isset($_POST['konfirmasi']) ? 1 : 0;
    $IDTOKO = $_POST['IDTOKO']; // Ambil IDTOKO yang dipilih

    // Update query dengan mempertimbangkan password dan IDTOKO
    if ($password) {
        $sql = "UPDATE eat_and_go_pengguna 
                SET username='$new_username', nama='$nama', password='$password', konfirmasi='$konfirmasi', IDTOKO='$IDTOKO' 
                WHERE username='$original_username'";
    } else {
        $sql = "UPDATE eat_and_go_pengguna 
                SET username='$new_username', nama='$nama', konfirmasi='$konfirmasi', IDTOKO='$IDTOKO' 
                WHERE username='$original_username'";
    }

    // Eksekusi query update
    if (mysqli_query($conn, $sql)) {
        header('Location: list_pengguna.php'); // Redirect ke halaman list_pengguna.php setelah berhasil update
    } else {
        echo "Error: " . $sql . "<br>" . mysqli_error($conn); // Tampilkan pesan error jika query gagal
    }

    mysqli_close($conn); // Tutup koneksi database
} else {
    // Query untuk mengambil data pengguna berdasarkan username
    $sql = "SELECT * FROM eat_and_go_pengguna WHERE username='$username'";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);

    // Query untuk mengambil daftar toko dari tabel eat_and_go_toko
    $toko_query = "SELECT * FROM eat_and_go_toko";
    $toko_result = mysqli_query($conn, $toko_query);
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Edit Pengguna</title>
    <link rel="shortcut icon" href="edit pengguna.svg" type="image/svg+xml">
    <link rel="stylesheet" href="w3.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
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

        /* Custom Toggle Switch */
        .switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            border-radius: 50%;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
        }

        input:checked+.slider {
            background-color: #2196F3;
        }

        input:checked+.slider:before {
            transform: translateX(26px);
        }

        .w3-input-container {
            position: relative;
        }

        .w3-input-container input[type="password"] {
            padding-right: 40px;
            /* space for the eye icon */
        }

        .w3-input-container i {
            position: absolute;
            right: 10px;
            top: 10px;
            font-size: 18px;
            color: #555;
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
        <?php if ($username === 'admin') { ?>
            <a href="list_pesanan.php" class="w3-bar-item w3-button w3-border">List Pesanan</a>
        <?php } ?>
        <?php if ($username === 'admin') { ?>
            <a href="list_toko.php" class="w3-bar-item w3-button w3-border">List Toko</a>
        <?php } ?>
        <a href="pesanan.php" class="w3-bar-item w3-button w3-border">Pesanan</a>
        <a href="logout.php" class="w3-bar-item w3-button w3-red w3-center"><b>Log Out </b><i class="fa fa-sign-out"
                style="font-size:20px"></i></a>
    </div>

    <!-- Page Content -->
    <div class="w3-teal" style="display: flex; align-items: center;">
        <button class="w3-button w3-xlarge" onclick="w3_open()">☰</button>
        <div style="flex-grow: 1; display: flex; justify-content: center;">
            <h1 style="margin: 0; line-height: 3.5rem; margin-bottom:10px; font-size: 30px;"><b>Edit Pengguna</b></h1>
        </div>
    </div>

    <!-- Form Edit Pengguna -->
    <form method="post" action="" class="w3-container w3-card-4 w3-light-grey w3-padding-16 w3-margin">
        <input type="hidden" name="original_username" value="<?php echo htmlspecialchars($row['username']); ?>">
        <label>Username</label>
        <input type="text" class="w3-input w3-border w3-light-grey" id="username" name="username"
            value="<?php echo htmlspecialchars($row['username']); ?>" required><br>

        <label>Nama</label>
        <input type="text" class="w3-input w3-border w3-light-grey" id="nama" name="nama"
            value="<?php echo htmlspecialchars($row['nama']); ?>" required><br>

        <label>Password</label>
        <div class="w3-input-container">
            <input type="password" class="w3-input w3-border w3-light-grey" id="password" name="password"
                value="<?php echo htmlspecialchars($row['password']); ?>">
            <i id="togglePassword" class="fa fa-eye"
                style="position: absolute; right: 10px; top: 10px; cursor: pointer;"></i>
        </div>
        <br>

        <label>Konfirmasi</label><br>
        <label class="switch">
            <input type="checkbox" id="konfirmasi" name="konfirmasi" <?php echo $row['konfirmasi'] ? 'checked' : ''; ?>>
            <span class="slider"></span>
        </label><br><br>

        <label for="IDTOKO">Pilih Toko</label>
        <select id="IDTOKO" name="IDTOKO" class="w3-input w3-border w3-light-grey" required>
            <option value="">Pilih Toko</option>
            <?php while ($toko = mysqli_fetch_assoc($toko_result)): ?>
                <option value="<?php echo $toko['IDTOKO']; ?>" <?php echo $row['IDTOKO'] == $toko['IDTOKO'] ? 'selected' : ''; ?>>
                    <?php echo $toko['NAMATOKO']; ?>
                </option>
            <?php endwhile; ?>
        </select>
        <br>

        <div class="w3-half">
            <a href="list_pengguna.php" class="w3-button w3-container w3-grey w3-padding-16"
                style="width: 100%;">Kembali</a>
        </div>
        <div class="w3-half">
            <button type="submit" id="updateButton" class="w3-button w3-green w3-container w3-padding-16"
                style="width: 100%;" disabled>Update</button>
        </div>
    </form>

    <!-- JavaScript -->
    <script>
        function w3_open() {
            document.getElementById("mySidebar").classList.add('show');
            document.getElementById("sidebarOverlay").classList.add('show');
            document.body.addEventListener('click', closeSidebarOutside);
        }

        function w3_close() {
            document.getElementById("mySidebar").classList.remove('show');
            document.getElementById("sidebarOverlay").classList.remove('show');
            document.body.removeEventListener('click', closeSidebarOutside);
        }

        function closeSidebarOutside(event) {
            if (!event.target.closest('#mySidebar') && !event.target.closest('.w3-xlarge')) {
                w3_close();
            }
        }

        // Function to enable/disable update button based on input changes
        function checkChanges() {
            var originalValues = {
                username: "<?php echo $row['username']; ?>",
                nama: "<?php echo $row['nama']; ?>",
                password: "<?php echo $row['password']; ?>",
                konfirmasi: "<?php echo $row['konfirmasi']; ?>",
                IDTOKO: "<?php echo $row['IDTOKO']; ?>" // ID toko yang dipilih sebelumnya
            };

            var currentValues = {
                username: document.getElementById('username').value,
                nama: document.getElementById('nama').value,
                password: document.getElementById('password').value,
                konfirmasi: document.getElementById('konfirmasi').checked ? 1 : 0,
                IDTOKO: document.getElementById('IDTOKO').value // ID toko yang dipilih sekarang
            };

            var updateButton = document.getElementById('updateButton');

            // Check if any value has changed
            if (currentValues.username !== originalValues.username ||
                currentValues.nama !== originalValues.nama ||
                currentValues.password !== originalValues.password ||
                currentValues.konfirmasi !== originalValues.konfirmasi ||
                currentValues.IDTOKO !== originalValues.IDTOKO) {
                updateButton.disabled = false; // Enable update button
            } else {
                updateButton.disabled = true; // Disable update button
            }
        }

        // Add event listener to ensure the function runs after the DOM has loaded
        document.addEventListener('DOMContentLoaded', function () {
            document.getElementById('username').addEventListener('input', checkChanges);
            document.getElementById('nama').addEventListener('input', checkChanges);
            document.getElementById('password').addEventListener('input', checkChanges);
            document.getElementById('konfirmasi').addEventListener('change', checkChanges);
            document.getElementById('IDTOKO').addEventListener('change', checkChanges); // Add event listener for IDTOKO

            var togglePassword = document.getElementById('togglePassword');
            var passwordField = document.getElementById('password');

            togglePassword.addEventListener('click', function () {
                // Toggle the type attribute
                var type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordField.setAttribute('type', type);

                // Toggle the eye icon
                this.classList.toggle('fa-eye');
                this.classList.toggle('fa-eye-slash');
            });
        });


        document.addEventListener("DOMContentLoaded", function () {
            var inputs = document.querySelectorAll('input[required]');
            inputs.forEach(input => {
                input.addEventListener('invalid', function (event) {
                    event.preventDefault();
                    // Custom validation message
                    let message = "Mohon diisi, tidak boleh kosong";
                    input.setCustomValidity(message);
                    // Display the message
                    if (input.validity.valueMissing) {
                        input.reportValidity();
                    }
                });

                input.addEventListener('input', function () {
                    input.setCustomValidity(""); // Reset custom message on input
                });
            });
        });
    </script>
</body>

</html>