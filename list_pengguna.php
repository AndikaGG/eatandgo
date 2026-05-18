<?php
include 'koneksi.php'; // Sertakan file koneksi ke database

session_start(); // Mulai sesi untuk mengakses informasi sesi pengguna

// Pastikan pengguna telah login sebelumnya
if (!isset($_SESSION['username'])) {
    header('Location: index.php'); // Redirect jika pengguna belum login
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

// Query untuk menghitung total jumlah pengguna dengan pencarian
$sql_count = "SELECT COUNT(username) AS total 
              FROM eat_and_go_pengguna p 
              LEFT JOIN eat_and_go_toko t ON p.IDTOKO = t.IDTOKO
              WHERE p.username != 'admin' 
              AND (p.username LIKE '%$search_keyword%' 
                   OR p.nama LIKE '%$search_keyword%' 
                   OR t.NAMATOKO LIKE '%$search_keyword%')";
$result_count = mysqli_query($conn, $sql_count);
$row_count = mysqli_fetch_assoc($result_count);
$total_records = $row_count['total'];

// Jumlah pengguna per halaman
$records_per_page = 10;

// Menghitung jumlah halaman
$total_pages = ceil($total_records / $records_per_page);

// Mendapatkan halaman saat ini
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Query untuk mengambil data pengguna dengan pencarian dan pagination
$sql = "SELECT p.*, t.NAMATOKO, t.ALAMAT, t.TELP1, t.TELP2
        FROM eat_and_go_pengguna p 
        LEFT JOIN eat_and_go_toko t ON p.IDTOKO = t.IDTOKO
        WHERE p.username != 'admin' 
        AND (p.username LIKE '%$search_keyword%' 
             OR p.nama LIKE '%$search_keyword%' 
             OR t.NAMATOKO LIKE '%$search_keyword%') 
        ORDER BY p.username DESC 
        LIMIT $offset, $records_per_page";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>List Pengguna</title>
    <link rel="shortcut icon" href="listbarang.svg" type="image/svg+xml">
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
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
    <div id="mainContent" style="margin-left: 0; transition: margin-left 0.5s;">
        <div class="w3-teal sticky-header" style="display: flex; align-items: center; padding: 10px;">
            <button class="w3-button w3-xlarge" onclick="w3_open()">☰</button>
            <div style="flex-grow: 1; display: flex; flex-direction: column; justify-content: center;">
                <h1
                    style="margin: 0; line-height: 1.5rem; text-align: center; font-size: 30px; margin-top:5px; margin-bottom: 10px;">
                    <b>List Pengguna</b>
                </h1>
                <!-- <div style="display: flex; font-size: 14px;">
                    <div style="flex: 1; text-align: center;">
                        <span style="font-size: 15px; font-weight: bold;">Username</span> <br>
                    </div>
                    <div style="flex: 1; text-align: center;">
                        <span style="font-size: 15px; font-weight: bold;">Nama</span>
                    </div>
                    <?php if ($user_record === 'admin') { ?>
                        <div style="flex: 1; text-align: center;">
                            <span style="font-size: 16px; font-weight: bold;">Aksi</span>
                        </div>
                    <?php } ?>
                </div> -->
            </div>
        </div>

        <!-- Modal untuk konfirmasi penghapusan -->
        <div id="deleteModal" class="w3-modal" onclick="closeModal(event)"
            style="align-items:center; padding-top: 10%;">
            <div class="w3-modal-content w3-animate-top w3-card-4">
                <header class="w3-container w3-red">
                    <span onclick="document.getElementById('deleteModal').style.display='none'"
                        class="w3-button w3-display-topright">&times;</span>
                    <h2>Konfirmasi</h2>
                </header>
                <div class="w3-container">
                    <p>Apakah Anda yakin ingin menghapus pengguna ini?</p>
                    <div class="w3-right">
                        <button class="w3-button w3-grey"
                            onclick="document.getElementById('deleteModal').style.display='none'">Batal</button>
                        <button class="w3-button w3-red" onclick="confirmDelete()">Hapus</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- CSS untuk modal -->
        <style>
            .w3-modal {
                display: none;
                /* Sembunyikan modal secara default */
                position: fixed;
                z-index: 9999;
                /* Pastikan modal berada di atas semua elemen lain */
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
                overflow: auto;
                background-color: rgba(0, 0, 0, 0.4);
                /* Latar belakang gelap semi-transparan */
            }
        </style>

        <!-- Search Bar -->
        <!-- Search Bar -->
        <div style="display: flex; justify-content: center; margin: 20px;">
            <form method="GET" action="" style="width: 100%; max-width: 600px; display: flex; position: relative;">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search_keyword); ?>"
                    class="w3-input w3-border" placeholder="Cari pengguna..."
                    style="border-radius:20px; padding-right: 40px; width: 100%;">
                <button type="submit" class="w3-button w3-green"
                    style="border-radius:20px; position: absolute; right: 10px; top: 50%; transform: translateY(-50%); border: none; background: transparent; cursor: pointer;">
                    <i class="fa fa-search" style="font-size: 16px; color: white;"></i> <!-- Ikon pencarian -->
                </button>
            </form>

        </div>

        <div style="font-size: 15px; text-align: right; padding-right: 30px;">
            <span class="w3-bar-item">Total: <?php echo $total_records; ?> pengguna</span>
        </div>

        <!-- Table of Users -->
        <!-- Table of Users -->
        <div class="w3-responsive">
            <table class="w3-table-all w3-centered" border="1" style="border-collapse: collapse; width: 100%;">
                <thead>
                    <tr class="w3-teal">
                        <th style="font-size: 20px;">Username</th> <!-- Set width for Username -->
                        <th style="font-size: 20px;">Nama</th> <!-- Set width for Nama -->
                        <th style="font-size: 20px;">Toko</th>
                        <?php if ($user_record === 'admin') { ?>
                            <th style="font-size: 20px;">Aksi</th> <!-- Set width for Aksi -->
                        <?php } ?>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                        <tr class="username-row">
                            <td><?php echo htmlspecialchars($row['username']); ?>
                            </td>
                            <td><?php echo htmlspecialchars($row['nama']); ?></td>
                            <td><?php echo ($row['NAMATOKO'] ? $row['NAMATOKO'] : 'Belum ada toko'); ?></td>
                            <?php if ($user_record === 'admin') { ?>
                                <td style="font-size: 14px; text-align: center;">
                                    <a href="edit_pengguna.php?username=<?php echo $row['username']; ?>"
                                        class="material-icons w3-yellow w3-btn w3-button w3-round"
                                        style="font-size: 15px;">&#xe22b;</a>
                                    <a href="#"
                                        onclick="deleteUser('<?php echo htmlspecialchars($row['username']); ?>', '<?php echo htmlspecialchars($row['nama']); ?>')"
                                        class="fa fa-trash w3-btn w3-button w3-round w3-red" style="font-size: 15px;"></a>
                                </td>
                            <?php } ?>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>


        <!-- Pagination -->
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


        <!-- Add New User Button -->
        <?php if ($user_record === 'admin') { ?>
            <a href="tambah_pengguna.php" class="w3-btn w3-round-xlarge w3-green bottom-right">
                <i class="fa fa-plus" style="font-size:30px;"></i>
            </a>
        <?php } ?>
        <!-- JavaScript -->
        <script>
            function w3_open() {
                document.getElementById("mySidebar").classList.add('show');
                document.getElementById("sidebarOverlay").classList.add('show');
            }

            function w3_close() {
                document.getElementById("mySidebar").classList.remove('show');
                document.getElementById("sidebarOverlay").classList.remove('show');
            }

            // function searchItems() {
            //     var input, filter, table, rows, cells, i, j, shouldShow;
            //     input = document.getElementById("searchInput");
            //     filter = input.value.toUpperCase();
            //     table = document.querySelector(".w3-table-all");
            //     rows = table.getElementsByTagName("tr");

            //     for (i = 1; i < rows.length; i++) {
            //         cells = rows[i].getElementsByTagName("td");
            //         shouldShow = false;

            //         for (j = 0; j < cells.length; j++) {
            //             if (cells[j] && cells[j].innerHTML.toUpperCase().indexOf(filter) > -1) {
            //                 shouldShow = true;
            //             }
            //         }
            //         rows[i].style.display = shouldShow ? "" : "none";
            //     }
            // }

            function deleteUser(username, nama) {
                var modal = document.getElementById('deleteModal');
                modal.style.display = 'block'; // Display the delete confirmation modal
                var modalMessage = modal.querySelector('p');
                modalMessage.textContent = "Apakah Anda yakin ingin menghapus pengguna '" + nama + "'?";
                var confirmButton = modal.querySelector('.w3-button.w3-red');
                confirmButton.onclick = function () {
                    window.location.href = "proses_pengguna.php?action=delete&username=" + encodeURIComponent(username);
                };
            }

            function searchItems() {
                var input, filter, table, tr, td, i, txtValue;
                input = document.getElementById("searchInput");
                filter = input.value.toUpperCase();
                table = document.querySelector("table");
                tr = table.getElementsByClassName("username-row");

                for (i = 0; i < tr.length; i++) {
                    td = tr[i].getElementsByTagName("td")[0];
                    if (td) {
                        txtValue = td.textContent || td.innerText;
                        if (txtValue.toUpperCase().indexOf(filter) > -1) {
                            tr[i].style.display = "";
                        } else {
                            tr[i].style.display = "none";
                        }
                    }
                }
            }
        </script>
    </div>
</body>

</html>

<?php mysqli_close($conn); ?>