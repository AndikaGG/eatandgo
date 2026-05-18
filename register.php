<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Register</title>
    <link rel="shortcut icon" href="register.svg" type="image/svg+xml">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
</head>

<style>
    /* Default logo size */
    img.responsive-logo {
        width: 20%;
    }

    /* Adjust logo size for screens smaller than 600px (typical mobile size) */
    @media screen and (max-width: 600px) {
        img.responsive-logo {
            width: 50%;
        }
    }
</style>

<body>
    <br>
    <div class="w3-center">
        <img src="eatngo.png" alt="Logo" class="responsive-logo" style="vertical-align: middle;">
    </div>

    <?php
    session_start();
    if (isset($_SESSION['username'])) {
        echo '<div id="idregistered" class="w3-modal" style="display:block;">
        <div class="w3-modal-content">
            <header class="w3-container w3-yellow">
                <span class="w3-button w3-hover-red w3-xlarge w3-display-topright" onclick="document.getElementById(\'idregistered\').style.display=\'none\'">&times;</span>
                <h2>Informasi</h2>
            </header>
            <div class="w3-container">
                <p>Anda sudah register</p>
            </div>
            <footer class="w3-container" style="padding-bottom:15px;">
                <a href="list_barang.php" class="w3-button w3-green w3-round">Kembali ke List Barang</a>
                <a href="logout.php" class="w3-button w3-red w3-round">Logout</a>
            </footer>
        </div>
    </div>';
        exit();
    }

    include 'koneksi.php';

    // Memeriksa koneksi
    if ($conn->connect_error) {
        die("Koneksi ke database gagal: " . $conn->connect_error);
    }

    // Fetching toko data
    $toko_query = "SELECT IDTOKO, NAMATOKO FROM eat_and_go_toko";
    $toko_result = $conn->query($toko_query);

    // Define variables and set them to empty values
    $username = $nama = $password = $password_confirm = $id_toko = "";
    $error_message = $success_message = "";

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = htmlspecialchars(trim($_POST['username']));
        $password = htmlspecialchars(trim($_POST['password']));
        $password_confirm = htmlspecialchars(trim($_POST['password_confirm']));
        $nama = htmlspecialchars(trim($_POST['nama']));
        $id_toko = $_POST['id_toko'];  // Get selected store ID
    
        // Memeriksa apakah username mengandung spasi di depan atau di belakang
        if ($username !== $_POST['username']) {
            $error_message = "Username tidak boleh mengandung spasi di depan atau di belakang.";
        } elseif ($password !== $password_confirm) {
            $error_message = "Password dan konfirmasi password tidak sesuai.";
        } else {
            // Memeriksa apakah username sudah ada
            $check_query = $conn->prepare("SELECT username FROM eat_and_go_pengguna WHERE username = ?");
            $check_query->bind_param("s", $username);
            $check_query->execute();
            $check_query->store_result();

            if ($check_query->num_rows > 0) {
                $error_message = "Username sudah ada. Silakan pilih username lain.";
            } else {
                // Query SQL untuk menyimpan data pengguna ke dalam tabel 'pengguna' dengan IDTOKO
                $query = $conn->prepare("INSERT INTO eat_and_go_pengguna (username, password, nama, IDTOKO) VALUES (?, ?, ?, ?)");
                $query->bind_param("sssi", $username, $password, $nama, $id_toko);

                if ($query->execute() === TRUE) {
                    $success_message = "Data Berhasil Disimpan.";
                } else {
                    $error_message = "Error: " . htmlspecialchars($query->error);
                }
                $query->close();
            }
            $check_query->close();
        }
    }

    $conn->close();
    ?>

    <?php if ($error_message): ?>
        <div id="idgagal" class="w3-modal" style="display:block;" onclick="this.style.display='none'">
            <div class="w3-modal-content w3-animate-top">
                <header class="w3-container w3-red">
                    <span class="w3-button w3-hover-red w3-xlarge w3-display-topright">&times;</span>
                    <h2>Informasi</h2>
                </header>
                <div class="w3-container">
                    <p><?php echo $error_message; ?></p>
                </div>
            </div>
        </div>
    <?php elseif ($success_message): ?>
        <div id="idberhasil" class="w3-modal" style="display:block;">
            <div class="w3-modal-content w3-animate-top">
                <header class="w3-container w3-green">
                    <span class="w3-button w3-hover-red w3-xlarge w3-display-topright"
                        onclick="document.getElementById('idberhasil').style.display='none'">&times;</span>
                    <h2>Konfirmasi</h2>
                </header>
                <div class="w3-container">
                    <p><?php echo $success_message; ?></p>
                </div>
                <footer class="w3-container w3-white" style="text-align: right;">
                    <button id="okButton" class="w3-button w3-green">OK</button>
                </footer>
            </div>
        </div>

        <script>
            document.addEventListener("DOMContentLoaded", function () {
                var modal = document.getElementById("idberhasil");
                var okButton = document.getElementById("okButton");

                okButton.addEventListener("click", function () {
                    modal.style.display = "none";
                    window.location.href = "index.php";
                });
            });
        </script>
    <?php endif; ?>

    <form class="w3-container w3-card-4 w3-light-grey w3-padding-16 w3-margin" action="" method="post">
        <h2 class="w3-container w3-center"><b>Register</b></h2>
        <label for="username">Username</label>
        <input type="text" id="username" class="w3-input w3-light-grey" name="username"
            value="<?php echo htmlspecialchars($username); ?>" required>

        <label for="nama">Nama</label>
        <input type="text" id="nama" class="w3-input w3-light-grey" name="nama"
            value="<?php echo htmlspecialchars($nama); ?>" required>

        <label for="toko">Pilih Toko</label>
        <select name="id_toko" class="w3-input w3-light-grey" required>
            <option value="" disabled selected>Pilih Toko</option>
            <?php while ($row = $toko_result->fetch_assoc()): ?>
                <option value="<?php echo $row['IDTOKO']; ?>"><?php echo $row['NAMATOKO']; ?></option>
            <?php endwhile; ?>
        </select>

        <label for="password">Password</label>
        <input type="password" id="password" class="w3-input w3-light-grey" name="password" required>

        <label for="password_confirm">Konfirmasi Password</label>
        <input type="password" id="password_confirm" class="w3-input w3-light-grey" name="password_confirm" required>

        <button type="submit" class="w3-input w3-button w3-round-large w3-teal w3-margin-top">Register</button>
        <a href="index.php" class="w3-input w3-button w3-round-large w3-green w3-margin-top">Kembali</a>
    </form>

</body>

</html>