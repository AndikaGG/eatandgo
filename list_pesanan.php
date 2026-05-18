<?php
// koneksi ke database
include 'koneksi.php';
session_start();

if (!isset($_SESSION["username"])) {
    header('Location: index.php');
    exit;
}

// Cek apakah pengguna adalah admin atau user biasa
$username = $_SESSION['username'];
$isAdmin = ($username === 'admin'); // True jika username adalah admin

// Ambil IDTOKO jika bukan admin
$idToko = $isAdmin ? null : (isset($_SESSION['IDTOKO']) ? $_SESSION['IDTOKO'] : null);

if (!$isAdmin && !$idToko) {
    die("Error: ID Toko tidak ditemukan. Pastikan Anda login dengan benar.");
}

// Update void status
if (isset($_GET['void']) && isset($_GET['nopesanan'])) {
    $nopesanan = $_GET['nopesanan'];
    $void = $_GET['void'];

    $update_query = "UPDATE eat_and_go_pesanan SET void = '$void' WHERE nopesanan = '$nopesanan'";
    if (!$isAdmin) {
        $update_query .= " AND IDTOKO = '$idToko'";
    }
    mysqli_query($conn, $update_query);
    header('Location: list_pesanan.php');
    exit;
}

// Mengambil input pencarian dari user
$search_keyword = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

// Pagination setup
$limit = 10; // Number of records per page
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$start = ($page > 1) ? ($page * $limit) - $limit : 0;

// Query untuk menghitung total data
$total_records_query = "SELECT COUNT(*) AS total FROM eat_and_go_pesanan WHERE nopesanan LIKE '%$search_keyword%'";
if (!$isAdmin) {
    $total_records_query .= " AND IDTOKO = '$idToko'";
}
$total_records_result = mysqli_query($conn, $total_records_query);
$total_records = mysqli_fetch_assoc($total_records_result)['total'];
$total_pages = ceil($total_records / $limit);

// Query untuk mendapatkan data pesanan
$query = "SELECT * FROM eat_and_go_pesanan WHERE nopesanan LIKE '%$search_keyword%'";
if (!$isAdmin) {
    $query .= " AND IDTOKO = '$idToko'";
}
$query .= " ORDER BY nopesanan DESC LIMIT $start, $limit";
$result = mysqli_query($conn, $query);

// Prepare an array to store the order details
$pesanan_details = [];

// Fetch all the orders
while ($row = mysqli_fetch_assoc($result)) {
    $nopesanan = $row['nopesanan'];

    // Get detailed order information from eat_and_go_detilpesanan for each order
    $details_query = "SELECT kodebarang, namabarang, harga, jumlah, total, bungkus, keterangan 
                      FROM eat_and_go_detilpesanan 
                      WHERE nopesanan = '$nopesanan'";
    $details_result = mysqli_query($conn, $details_query);

    $details = [];
    while ($detail_row = mysqli_fetch_assoc($details_result)) {
        $details[] = $detail_row; // Store each detail in an array
    }

    // Store the order along with its details
    $pesanan_details[] = [
        'pesanan' => $row,
        'details' => $details
    ];
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>List Pesanan</title>
    <link rel="shortcut icon" href="listbarang.svg" type="image/svg+xml">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="w3.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        /* Modified styles for sidebar */
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

        /* Styling for sidebar overlay */
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

        @media (max-width: 200px) {
            .w3-table-all {
                table-layout: fixed;
                width: 100%;
            }

            .w3-table-all th,
            .w3-table-all td {
                word-wrap: break-word;
            }

            .w3-table-all th:nth-of-type(1),
            .w3-table-all td:nth-of-type(1) {
                width: 10%;
            }

            .w3-table-all th:nth-of-type(2),
            .w3-table-all td:nth-of-type(2) {
                width: 10%;
            }

            .w3-table-all th:nth-of-type(3),
            .w3-table-all td:nth-of-type(3) {
                width: 10%;
            }
        }

        .w3-overlay.show {
            display: block;
        }

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

        /* Styling for disabled button */
        .w3-button.disabled {
            background-color: gray;
            color: white;
            cursor: not-allowed;
        }

        /* style untuk modal view */

        /* Modal Container */
        .modal,
        #viewModal {
            display: none;
            /* Hidden by default */
            position: fixed;
            /* Stay in place */
            z-index: 1000;
            /* Sit on top */
            left: 0;
            top: 0;
            width: 100%;
            /* Full width */
            height: 100%;
            /* Full height */
            background-color: rgba(0, 0, 0, 0.5);
            /* Black background with transparency */
            justify-content: center;
            align-items: center;
            overflow: auto;
            /* Allows scrolling if content overflows */
        }

        /* Modal Content */
        .modal-content,
        #modalContent {
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            width: 100vw;
            /* Full viewport width with some margin */
            height: 100vh;
            /* Full viewport height with some margin */
            max-width: 100%;
            /* Ensure full width usage */
            max-height: 100vh;
            /* Ensure full height usage */
            overflow-y: auto;
            /* Vertical scrollbar if content exceeds max height */
            position: relative;
            /* Position for close button */
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);
        }

        /* Close Button */
        .close-btn,
        #closeModalBtn {
            background-color: #ff4d4d;
            color: #fff;
            border: none;
            padding: 10px;
            /* border-radius: 50%; */
            cursor: pointer;
            font-size: 20px;
            line-height: 1;
            transition: background-color 0.3s ease;
            position: absolute;
            top: 10px;
            right: 10px;
        }

        .close-btn:hover,
        #closeModalBtn:hover {
            background-color: #ff0000;
        }

        /* Modal Header */
        .modal-content h2,
        #modalContent h2 {
            margin: 0;
            font-size: 24px;
            color: #333;
            border-bottom: 2px solid #ddd;
            padding-bottom: 10px;
        }

        /* Table Container */
        .table-container {
            max-height: calc(100vh - 100px);
            /* Adjust height based on modal content and header */
            overflow-y: auto;
            /* Vertical scrollbar if needed */
            padding: 10px;
        }

        /* Table Styling */
        .w3-table-all {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .w3-table-all th,
        .w3-table-all td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
            word-wrap: break-word;
            /* Ensure text wraps in cells */
        }

        /* .w3-table-all th {
    background-color: blue; 
    color: #fff; 
    font-weight: bold;
} */

        .w3-table-all td {
            background-color: #fff;
            /* Change cell background color */
            color: #333;
            /* Change cell text color */
        }

        .w3-table-all tr:nth-child(even) {
            background-color: #f4f4f4;
            /* Change alternating row background color */
        }

        /* .w3-table-all tr:hover {
    background-color: blue; 
} */

        /* General Text Styling */
        p {
            font-size: 16px;
            margin: 10px 0;
            color: #333;
        }

        h3 {
            font-size: 22px;
            margin-top: 20px;
            color: #444;
            border-bottom: 2px solid #ddd;
            padding-bottom: 10px;
        }

        /* Responsive Adjustments */
        @media (max-width: 600px) {

            .modal-content,
            #modalContent {
                width: 100vw;
                /* Adjust width for mobile */
                height: 100vh;
                /* Adjust height for mobile */
            }

            .table-container {
                max-height: calc(100vh - 100px);
                /* Adjust height for mobile */
            }
        }

        /* Make sure you include W3.CSS in your project */
        .w3-teal {
            background-color: #009688 !important;
            color: white;
        }

        /* Optional: Style for table headers with W3.CSS */
        .w3-table-all th {
            background-color: #009688;
            color: white;
        }

        /* Optional: Style for table rows with W3.CSS */
        .w3-table-all tr:nth-child(even) {
            background-color: #f2f2f2;
            /* Light grey for alternating rows */
        }
    </style>
</head>

<body>
    <!-- Sidebar Overlay -->
    <div id="sidebarOverlay" class="w3-overlay" onclick="w3_close()"></div>

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
    <div id="confirmationModal" class="w3-modal" style="display: none; z-index: 9999;">
        <div class="w3-modal-content w3-animate-top w3-card-4">
            <header class="w3-container w3-teal">
                <h2>Konfirmasi</h2>
            </header>
            <div class="w3-container">
                <p>Apakah Anda yakin ingin menyelesaikan pesanan ini?</p>
            </div>
            <footer class="w3-container w3-white w3-padding" style="text-align: right;">
                <button class="w3-button w3-gray" onclick="closeModal()">Batal</button>
                <a id="confirmButton" class="w3-button w3-green">Selesai</a>
            </footer>
        </div>
    </div>

    <script>
        function searchItems() {
            var input, filter, table, tr, td, i, txtValue;
            input = document.getElementById("searchInput");
            filter = input.value.toLowerCase();
            table = document.getElementById("pesananTable");
            tr = table.getElementsByTagName("tr");

            for (i = 1; i < tr.length; i++) {
                td = tr[i].getElementsByTagName("td");
                if (td) {
                    txtValue = td[0].textContent || td[0].innerText;
                    if (txtValue.toLowerCase().indexOf(filter) > -1) {
                        tr[i].style.display = "";
                    } else {
                        tr[i].style.display = "none";
                    }
                }
            }
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

    <!-- Header -->
    <div class="w3-teal fixed-header" style="display: flex; align-items: center;">
        <button class="w3-button w3-xlarge" onclick="w3_open()">☰</button>
        <div style="flex-grow: 1; display: flex; justify-content: center;">
            <h1 style="margin: 0; line-height: 3.5rem; margin-bottom:10px; font-size: 30px;"><b>List Pesanan</b></h1>
        </div>
    </div>
    <br>
    <div style="display: flex; justify-content: center; margin: 20px;">
        <form method="GET" action="" style="width: 100%; max-width: 600px; display: flex; position: relative;">
            <input type="text" id="searchInput" name="search" class="w3-input w3-border"
                placeholder="Cari no pesanan..." value="<?php echo htmlspecialchars($search_keyword); ?>"
                onkeyup="searchItems()" style="border-radius:20px; padding-right: 40px; width: 100%;">
            <button type="submit" class="w3-button w3-green"
                style="border-radius:20px; position: absolute; right: 10px; top: 50%; transform: translateY(-50%); border: none; background: transparent; cursor: pointer;">
                <i class="fa fa-search" style="font-size: 16px; color: white;"></i> <!-- Ikon pencarian -->
            </button>
        </form>

    </div>

    <div style="font-size: 15px; text-align: right; padding-right: 30px;">
        <span class="w3-bar-item">Total: <?php echo $total_records; ?> Pesanan</span>
    </div>

    <!-- Existing table structure -->
    <style>
        .w3-responsive table {
            border-collapse: collapse;
            width: 100%;
        }

        .w3-responsive th,
        .w3-responsive td {
            border: none;
            /* Menghilangkan garis tabel */
            padding: 15px;
            /* Menambah jarak agar tampilan lebih rapi */
        }

        .w3-responsive thead th {
            background-color: #008080;
            /* Warna header */
            color: white;
            font-size: 15px;
        }

        .w3-responsive tbody tr:nth-child(even) {
            background-color: #f9f9f9;
            /* Warna untuk baris genap */
        }

        .w3-responsive tbody tr:hover {
            background-color: #e0f7fa;
            /* Efek hover */
        }

        .action-buttons button {
            margin: 0 5px;
            /* Jarak antar tombol aksi */
        }
    </style>

    <div class="w3-responsive">
        <table class="w3-table-all w3-centered" id="pesananTable">
            <thead>
                <tr class="w3-teal">
                    <th>No Pesanan</th>
                    <th>No Meja</th>
                    <th>Grand Total</th>
                    <th>Jenis Pembayaran</th>
                    <th>Terbayar</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pesanan_details as $order_data): ?>
                    <?php $row = $order_data['pesanan']; ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['nopesanan']); ?></td>
                        <td><?php echo htmlspecialchars($row['nomeja']); ?></td>
                        <td>Rp. <?php echo number_format($row['grandtotal'], 2, ',', '.'); ?></td>
                        <td><?php echo htmlspecialchars($row['jenispembayaran']); ?></td>
                        <td><?php echo $row['terbayar'] ? 'Ya' : 'Tidak'; ?></td>
                        <td class="action-buttons">
                            <?php if ($row['void'] == 1): ?>
                                <button class="w3-button disabled">Selesai</button>
                            <?php else: ?>
                                <button class="w3-button w3-green"
                                    onclick="showConfirmationModal('<?php echo urlencode($row['nopesanan']); ?>', '<?php echo urlencode($search_keyword); ?>')">Selesai</button>
                            <?php endif; ?>
                            <button class="w3-button w3-teal"
                                onclick="openViewModal(<?php echo htmlspecialchars(json_encode($order_data)); ?>)">Lihat
                                pesanan</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Existing content -->

    <!-- View Details Modal -->
    <div id="viewModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeViewModal()">&times;</span>
            <h2>DETAIL PESANAN</h2>
            <div id="modalContent">
                <!-- The order details will be inserted here -->
                <div class="table-container">
                    <table class="w3-table-all">
                        <!-- Table content will be inserted here -->
                    </table>
                </div>
            </div>
        </div>
    </div>


    <script>
        function showConfirmationModal(nopesanan, search) {
            // Show the modal
            document.getElementById('confirmationModal').style.display = 'block';

            // Set the href for the confirm button
            const confirmButton = document.getElementById('confirmButton');
            confirmButton.href = 'list_pesanan.php?void=1&nopesanan=' + nopesanan + '&search=' + search;
        }

        function closeModal() {
            // Hide the modal
            document.getElementById('confirmationModal').style.display = 'none';
        }
    </script>
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

    <script>
        function openViewModal(orderData) {
            // Get the modal element
            var modal = document.getElementById("viewModal");
            var modalContent = document.getElementById("modalContent");

            // Clear previous modal content
            modalContent.innerHTML = '';

            // Populate the modal content with the order details
            var htmlContent = '<p><span style="font-weight: bold;">NO PESANAN:</span> ' + orderData.pesanan.nopesanan + '</p>';
            htmlContent += '<p><span style="font-weight: bold;">NO MEJA:</span> ' + orderData.pesanan.nomeja + '</p>';
            htmlContent += '<p><span style="font-weight: bold;">TANGGAL:</span> ' + orderData.pesanan.tanggal + '</p>';
            htmlContent += '<p><span style="font-weight: bold;">NAMA:</span> ' + orderData.pesanan.nama + '</p>';
            htmlContent += '<p><span style="font-weight: bold;">NO TELEPON:</span> ' + orderData.pesanan.notelepon + '</p>';
            htmlContent += '<p><span style="font-weight: bold;">GRAND TOTAL:</span> Rp. ' + parseFloat(orderData.pesanan.grandtotal).toLocaleString() + '</p>';
            htmlContent += '<p><span style="font-weight: bold;">JENIS PEMBAYARAN:</span> ' + orderData.pesanan.jenispembayaran + '</p>';
            // htmlContent += '<p><span style="font-weight: bold;">BAYAR:</span> Rp. ' + parseFloat(orderData.pesanan.bayar).toLocaleString() + '</p>';
            // htmlContent += '<p><span style="font-weight: bold;">KEMBALI:</span> Rp. ' + parseFloat(orderData.pesanan.kembali).toLocaleString() + '</p>';
            htmlContent += '<p><span style="font-weight: bold;">TERBAYAR:</span> ' + (orderData.pesanan.terbayar == '1' ? 'Ya' : 'Tidak') + '</p>';

            // Details of each item in the order
            htmlContent += '<h3 style="font-weight: bold;">DETAIL PESANAN:</h3>';
            htmlContent += '<table class="w3-table-all">';
            htmlContent += '<tr style="font-weight: bold;"><th>KODE BARANG</th><th>NAMA BARANG</th><th>HARGA</th><th>JUMLAH</th><th>TOTAL</th><th>BUNGKUS</th><th>CATATAN</th></tr>';

            // Loop through each detail item in the order
            orderData.details.forEach(function (detail) {
                var bungkus = detail.bungkus == '1' ? 'Ya' : 'Tidak'; // Ubah 1/0 menjadi Ya/Tidak
                htmlContent += '<tr class="w3-blue">';
                htmlContent += '<td style="font-weight: bold;">' + detail.kodebarang + '</td>';
                htmlContent += '<td style="font-weight: bold;">' + detail.namabarang + '</td>';
                htmlContent += '<td style="font-weight: bold;">Rp. ' + parseFloat(detail.harga).toLocaleString() + '</td>';
                htmlContent += '<td style="font-weight: bold;">' + detail.jumlah + '</td>';
                htmlContent += '<td style="font-weight: bold;">Rp. ' + parseFloat(detail.total).toLocaleString() + '</td>';

                // Add bungkus (takeaway) and keterangan (notes)
                htmlContent += '<td style="font-weight: bold;">' + bungkus + '</td>';
                htmlContent += '<td style="font-weight: bold;">' + (detail.keterangan ? detail.keterangan : 'Tidak ada catatan') + '</td>';

                htmlContent += '</tr>';
            });

            htmlContent += '</table>';

            modalContent.innerHTML = htmlContent;

            // Display the modal
            modal.style.display = "flex";
        }



        function closeViewModal() {
            // Close the modal
            document.getElementById("viewModal").style.display = "none";
        }
    </script>

</body>

</html>