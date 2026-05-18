<?php
// Koneksi ke database
include 'koneksi.php';
session_start();

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
    $user_record = ''; // Tangani jika tidak ada data pengguna
}

// Mengambil input pencarian dari user
$search_keyword = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

// Fungsi untuk menghasilkan no pesanan baru
function generate_pesanan_id($conn) {
    $current_year = date('y');
    $current_month = date('m');

    do {
        $sql = "SELECT IFNULL(MAX(CAST(SUBSTR(nopesanan, 7, 5) AS UNSIGNED)), 0) as last_number
                FROM eat_and_go_pesanan
                WHERE SUBSTR(nopesanan, 3, 4) = CONCAT('$current_year', '$current_month')";

        $result = mysqli_query($conn, $sql);
        if (!$result) {
            die("Query error: " . mysqli_error($conn));
        }

        $row = mysqli_fetch_assoc($result);
        $last_number = $row['last_number'];

        $new_sequence = str_pad($last_number + 1, 5, '0', STR_PAD_LEFT);
        $new_pesanan_id = "OR" . $current_year . $current_month . $new_sequence;

        $check_query = "SELECT COUNT(*) as count FROM eat_and_go_pesanan WHERE nopesanan = '$new_pesanan_id'";
        $check_result = mysqli_query($conn, $check_query);
        $check_row = mysqli_fetch_assoc($check_result);
    } while ($check_row['count'] > 0);

    return $new_pesanan_id;
}

$new_pesanan_id = generate_pesanan_id($conn);
$now = date('Y-m-d H:i:s');

// Query untuk mengambil data meja
$query_meja = "SELECT * FROM eat_and_go_meja";
$result_meja = mysqli_query($conn, $query_meja);

// Query untuk mengambil data barang
$query_barang = "SELECT * FROM eat_and_go_barang";
$result_barang = mysqli_query($conn, $query_barang);

// Hitung total item untuk kategori Makanan
$sql_count_makanan = "SELECT COUNT(*) AS total FROM eat_and_go_barang WHERE jenis = 'Makanan' AND (namabarang LIKE '%$search_keyword%' OR jenis LIKE '%$search_keyword%')";
$result_count_makanan = mysqli_query($conn, $sql_count_makanan);
if (!$result_count_makanan) {
    die("Query error: " . mysqli_error($conn));
}
$row_count_makanan = mysqli_fetch_assoc($result_count_makanan);
$total_items_makanan = $row_count_makanan['total'];

// Hitung total item untuk kategori Minuman
$sql_count_minuman = "SELECT COUNT(*) AS total FROM eat_and_go_barang WHERE jenis = 'Minuman' AND (namabarang LIKE '%$search_keyword%' OR jenis LIKE '%$search_keyword%')";
$result_count_minuman = mysqli_query($conn, $sql_count_minuman);
if (!$result_count_minuman) {
    die("Query error: " . mysqli_error($conn));
}
$row_count_minuman = mysqli_fetch_assoc($result_count_minuman);
$total_items_minuman = $row_count_minuman['total'];

// Proses simpan data penjualan
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nopesanan = $_POST['nopesanan'];
    $nomeja = $_POST['nomeja'];
    $tanggal = $_POST['tanggal'];
    $nama = $_POST['nama']; // Ambil nama dari modal
    $notelepon = $_POST['notelepon']; // Ambil no telepon dari modal
    $grandtotal = str_replace('.', '', $_POST['grandtotal']);
    $grandtotal = str_replace(',', '.', $grandtotal);
    $jenispembayaran = $_POST['jenispembayaran'];
    $bayar = str_replace('.', '', $_POST['bayar']);
    $bayar = str_replace(',', '.', $bayar);
    $kembali = str_replace('.', '', $_POST['kembali']);
    $kembali = str_replace(',', '.', $kembali);
    $terbayar = isset($_POST['terbayar']) ? 1 : 0;
    $bungkus = isset($_POST['bungkus']) ? 1 : 0; // Ambil nilai bungkus dari modal
    $keterangan = $_POST['keterangan']; // Ambil keterangan dari modal
    $idbarangs = $_POST['idbarang'];
    $hargas = array_map(function($value) {
        return (float) str_replace(',', '.', str_replace('.', '', $value));
    }, $_POST['harga']);
    
    $jumlahs = array_map(function($value) {
        return (int) str_replace(',', '.', str_replace('.', '', $value));
    }, $_POST['jumlah']);

    // Validasi jika bayar kurang dari grand total
    if ($bayar < $grandtotal) {
        header("Location: pesanan.php?error=1");
        exit();
    }
    
    // Query untuk menyimpan data ke eat_and_go_pesanan, termasuk nama, no telepon, keterangan, dan bungkus
    $query = "INSERT INTO eat_and_go_pesanan (nopesanan, nomeja, tanggal, nama, notelepon, grandtotal, jenispembayaran, bayar, kembali, terbayar, bungkus, keterangan) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ssssssdddiis", $nopesanan, $nomeja, $tanggal, $nama, $notelepon, $grandtotal, $jenispembayaran, $bayar, $kembali, $terbayar, $bungkus, $keterangan);
    mysqli_stmt_execute($stmt);

    // Simpan detail barang yang dipesan
    foreach ($idbarangs as $key => $idbarang) {
        $harga = $hargas[$key]; 
        $jumlah = $jumlahs[$key];
        $total = $harga * $jumlah;
        $query_detail = "INSERT INTO eat_and_go_detilpesanan (nopesanan, kodebarang, harga, jumlah, total) VALUES (?, ?, ?, ?, ?)";
        $stmt_detail = mysqli_prepare($conn, $query_detail);
        mysqli_stmt_bind_param($stmt_detail, "ssddd", $nopesanan, $idbarang, $harga, $jumlah, $total);
        mysqli_stmt_execute($stmt_detail);
    }

    echo "<script> window.location.href='print.php?id=$new_pesanan_id';</script>";
    exit();
}
?>




<!-- Tampilkan Total Item -->





<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Pesanan</title>
    <link rel="shortcut icon" href="edit pesanan.svg" type="image/svg+xml">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="w3.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
</head>

<body>
<div id="barangModal" class="w3-modal">
    <div class="w3-modal-content w3-card-4 custom-modal-size">
        <header class="w3-container w3-teal">
            <!-- <span onclick="closeBarangModal()" class="w3-button w3-display-topright">&times;</span> -->
            <h2><b>DAFTAR BARANG</b></h2>
            <div class="filter-buttons">
        <button type="button" class="w3-button w3-light-grey" style="font-weight: bold;" id="makananButton">
    Makanan <span class="w3-item"><?php echo $total_items_makanan; ?></span>
</button>

<button type="button" class="w3-button w3-light-grey" style="font-weight: bold;" id="minumanButton">
    Minuman <span class="w3-item"><?php echo $total_items_minuman; ?></span>
</button>


                    <!-- <button type="button" class="w3-button w3-green" onclick="filterCategory('')">Tampilkan Semua</button> -->
                </div>
        </header>
        <div class="w3-container">
            <div class="table-container">
                <table class="w3-table-all">
                    <thead>
                        <tr>
                            <!-- <th>Gambar</th>
                            <th>Nama Barang</th> -->
                        </tr>
                    </thead>
                    <tbody id="barangTableBody">
                        <?php while ($row_barang = mysqli_fetch_assoc($result_barang)) { ?>
                            <tr>
                                <td style="text-align: left;">
                                    <?php if ($row_barang['gambar']): ?>
                                        <img src="data:image/jpeg;base64,<?php echo base64_encode($row_barang['gambar']); ?>" class="item-image" style="width: 50%; height: auto; max-width: 200px;"/>
                                    <?php else: ?>
                                        <img src="default_image.png" class="item-image" style="width: 50%; height: auto;"/>
                                    <?php endif; ?>
                                </td>
                                <td style="font-size: 14px; text-align: left; word-wrap: break-word; max-width: 120px; overflow: hidden; text-overflow: ellipsis;">
                                    <span style="color: ; font-weight: bold;"><?php echo $row_barang['namabarang']; ?></span>
                                    <span style="color: #009688; font-weight: bold;"><?php echo htmlspecialchars($row_barang['jenis']); ?></span><br>
                                    <span class="item-price" style="font-size: 16px; font-weight: bold; color: green;" data-price="<?php echo $row_barang['harga']; ?>">
                                        <?php echo number_format($row_barang['harga'], 2, ',', '.'); ?>
                                    </span>
                                </td>
                                <td>
                                    <button style="font-weight: bold;" type="button" class="w3-button w3-green" onclick="openProductModal('<?php echo $row_barang['namabarang']; ?>', '<?php echo htmlspecialchars($row_barang['namabarang']); ?>', '<?php echo number_format($row_barang['harga'], 2, ',', '.'); ?>', 'data:image/jpeg;base64,<?php echo base64_encode($row_barang['gambar']); ?>')">
                                    <i class="fa fa-shopping-cart"></i> Tambah <br><span>Pesanan</span>
                                    </button>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="checkout-container">
            <div class="left-options">
                <!-- Any additional options -->
            </div>
            <div class="right-options">
                <!-- Keranjang Button -->
                <button class="w3-button w3-light-grey" onclick="openCartModal()">
    <i class="fa fa-shopping-cart"></i> <span id="total-items">0</span>
</button>

                <div class="selected-items">
                    <span id="selected-items"></span>
                </div>
                <div class="total-section">
                    TOTAL : <span id="total-price">Rp0</span>
                </div>
                <a class="checkout-button w3-button w3-green" href="javascript:void(0);" onclick="closeBarangModal()">CHECK OUT</a>
            </div>
        </div>
    </div>
</div>





<!-- Modal Keranjang -->
<div id="cartModal" class="w3-modal">
    <div class="w3-modal-content w3-card-4 custom-cart-modal-size">
        <header class="w3-container w3-teal">
            <span onclick="closeCartModal()" class="w3-button w3-display-topright">&times;</span>
            <h2><b>KERANJANG</b></h2>
        </header>
        <div class="w3-container">
            <div class="cart-items">
                <!-- Daftar item keranjang akan diisi oleh JavaScript -->
                <div id="cartItemsContainer"></div>
            </div>
        </div>
        <div class="cart-checkout-container">
            <div class="cart-left-options">
                <!-- Any additional options -->
            </div>
            <div class="cart-right-options">
                <div class="cart-total-section">
                    <button class="w3-button w3-light-grey" onclick="openCartModal()">
                        <i class="fa fa-shopping-cart"></i> <span id="cart-total-items">0</span>
                    </button>
                    TOTAL: <span id="cart-total-price">Rp0</span>
                </div>
                <a class="cart-checkout-button w3-button w3-green" href="javascript:void(0);" onclick="closeAllModals();">
                    Bayar >
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.custom-cart-modal-size {
    width: 60%;  /* Lebar modal default 40% pada layar besar */
    max-height: 80vh; /* Batas maksimal tinggi 80% dari viewport */
    margin: auto;
    border-radius: 8px;
    position: fixed;
    top: 10%;  /* Jarak dari atas layar */
    left: 50%; /* Pusatkan secara horizontal */
    transform: translateX(-50%);
    overflow-y: auto; /* Scroll jika konten terlalu tinggi */
    background-color: white;
    box-shadow: 0px 4px 8px rgba(0,0,0,0.2);
}

.cart-checkout-container {
    width: 100%; /* Lebar checkout-container sesuai dengan lebar modal */
    background-color: white;
    padding: 10px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-top: 1px solid #ddd;
}

.cart-checkout-button {
    background-color: #ff5722;
    color: white;
    padding: 10px 20px;
    border: none;
    cursor: pointer;
    font-size: 16px;
    border-radius: 25px;
    display: flex;
    justify-content: center;
    align-items: center;
}

@media (max-width: 768px) {
    .custom-cart-modal-size {
        width: 60%;  /* Lebar modal 60% pada layar tablet */
        top: 5%;  
        left: 50%;
        transform: translateX(-50%);
        max-height: 85vh; 
    }

    .cart-checkout-container {
        flex-direction: column; /* Tampilkan dalam kolom pada layar tablet */
        align-items: stretch;
    }

    .cart-checkout-button {
        width: 100%; /* Tombol checkout lebar penuh */
        margin-top: 10px;
    }
}

@media (max-width: 600px) {
    .custom-cart-modal-size {
        width: 95%;  /* Lebar modal 95% pada layar HP */
        top: 5%;
        left: 50%;
        transform: translateX(-50%);
        max-height: 85vh;
    }

    .cart-checkout-button {
        width: 100%;  /* Tombol checkout lebar penuh */
    }
}

.cart-checkout-button .total-price {
    margin-left: 15px;
    font-size: 18px;
    font-weight: bold;
}

.cart-item-card {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px;
    border: 1px solid #ddd;
    margin-bottom: 10px;
    border-radius: 8px;
    background-color: white;
}

.cart-item-details {
    flex: 1;
    padding-left: 10px;
}

.cart-item-details h4 {
    margin: 0;
    font-weight: bold;
}

.cart-item-details .item-price {
    color: red;
    font-weight: bold;
}

.cart-item-controls {
    display: flex;
    align-items: center;
}

.cart-item-controls button {
    background-color: transparent;
    border: none;
    font-size: 20px;
    margin: 0 5px;
}

.cart-item-controls .quantity {
    font-weight: bold;
    margin: 0 10px;
}

.cart-item-controls .fa-trash {
    color: red;
    cursor: pointer;
}
</style>


<script>
    // Fungsi untuk menutup modal keranjang
function closeCartModal() {
    const modal = document.getElementById('cartModal');
    if (modal) {
        modal.style.display = 'none';
    } else {
        console.error('Modal with ID cartModal not found.');
    }
}

// Menutup modal ketika klik di luar area modal
window.onclick = function(event) {
    const modal = document.getElementById('cartModal');
    if (event.target === modal) {
        closeCartModal();
    }
}

    // Fungsi untuk membuka modal keranjang
    function openCartModal() {
        console.log('Mencoba membuka modal keranjang...');
        const modal = document.getElementById('cartModal');
        if (modal) {
            modal.style.display = 'block';
            updateCartModal();
            console.log('Modal keranjang seharusnya sudah terbuka.');
        } else {
            console.log('Elemen modal tidak ditemukan.');
        }
    }

    // Pastikan ini dipanggil saat tombol keranjang ditekan
    document.getElementById('openCartButton').addEventListener('click', openCartModal);


    function updateCartModal() {
    const cart = JSON.parse(localStorage.getItem('cart')) || [];
    const cartItemsContainer = document.getElementById('cartItemsContainer');
    const cartTotalItems = document.getElementById('cart-total-items');
    const cartTotalPrice = document.getElementById('cart-total-price');

    cartItemsContainer.innerHTML = ''; // Clear existing items
    let totalItems = cart.length; // Hitung total barang berdasarkan jumlah item unik
    let totalPrice = 0;

    cart.forEach(item => {
        const cartItemCard = document.createElement('div');
        cartItemCard.classList.add('cart-item-card');

        cartItemCard.innerHTML = `
            <div class="cart-item-image">
                <img src="${item.image ? item.image : 'default_image.png'}" 
                     style="width: 50px; height: auto;" 
                     alt="${item.title}" 
                     onerror="this.onerror=null; this.src='default_image.png';" />
            </div>
            <div class="cart-item-details">
                <h4>${item.title}</h4>
                <p class="item-price">Rp${item.price.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,')}</p>
            </div>
            <div class="cart-item-controls">
              <button onclick="decreaseQuantity('${item.title}')"><i class="fa fa-minus-circle"></i></button>
              <span class="quantity">${item.quantity}</span>
              <button onclick="increaseQuantity('${item.title}')"><i class="fa fa-plus-circle"></i></button>
              <button onclick="openEditItemModal('${item.title}')"><i class="fa fa-pencil"></i></button>
              <button onclick="removeFromCart('${item.title}')"><i class="fa fa-trash"></i></button>
            </div>
        `;
        cartItemsContainer.appendChild(cartItemCard);

        totalPrice += item.price * item.quantity;
    });

    cartTotalItems.textContent = totalItems; // Total jumlah barang unik
    cartTotalPrice.textContent = `Rp${totalPrice.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,')}`;

    // Panggil calculateTotal untuk memperbarui grand total setelah keranjang diperbarui
    calculateTotal();
}


function decreaseQuantity(itemTitle) {
    let cart = JSON.parse(localStorage.getItem('cart')) || [];
    const itemIndex = cart.findIndex(item => item.title === itemTitle);

    if (itemIndex > -1) {
        // Cek apakah kuantitas sudah 1, jika ya jangan kurangi lagi
        if (cart[itemIndex].quantity > 1) {
            cart[itemIndex].quantity -= 1;
        } else {
            // Tetapkan kuantitas tetap 1
            cart[itemIndex].quantity = 1;
        }
        
        localStorage.setItem('cart', JSON.stringify(cart));

        updateCartModal();
        updateTotal();
        updateOrderTable(itemTitle, cart[itemIndex].quantity);
        
        // Panggil calculateTotal untuk memperbarui grand total
        calculateTotal();
    }
}

function increaseQuantity(itemTitle) {
    let cart = JSON.parse(localStorage.getItem('cart')) || [];
    const itemIndex = cart.findIndex(item => item.title === itemTitle);
    
    if (itemIndex > -1) {
        cart[itemIndex].quantity += 1;
        localStorage.setItem('cart', JSON.stringify(cart));

        updateCartModal();
        updateTotal();
        updateOrderTable(itemTitle, cart[itemIndex].quantity);
        
        // Panggil calculateTotal untuk memperbarui grand total
        calculateTotal();
    }
}


function updateOrderTable(itemTitle, newQuantity) {
    const orderItems = document.getElementById('orderItems');
    const existingRow = Array.from(orderItems.rows).find(row => {
        return row.querySelector('.item-title') && row.querySelector('.item-title').textContent.includes(itemTitle);
    });

    // Ambil harga dari cart untuk perhitungan total
    const cart = JSON.parse(localStorage.getItem('cart')) || [];
    const itemInCart = cart.find(item => item.title === itemTitle);
    const price = itemInCart ? itemInCart.price : 0;
    const bungkus = itemInCart ? itemInCart.bungkus : false;
    const keterangan = itemInCart ? itemInCart.keterangan : '';

    if (existingRow) {
        if (newQuantity > 0) {
            // Perbarui kuantitas dan total
            const quantityInput = existingRow.querySelector('.quantity-input');
            const totalInput = existingRow.querySelector('.total-input');
            const bungkusCell = existingRow.querySelector('.bungkus-cell');
            const keteranganCell = existingRow.querySelector('.keterangan-cell');

            // Perbarui kuantitas
            quantityInput.value = newQuantity;

            // Perbarui total
            const newTotal = price * newQuantity;
            totalInput.value = newTotal.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

            // Update judul item dengan kuantitas baru
            const itemTitleElement = existingRow.querySelector('.item-title');
            itemTitleElement.textContent = `(${newQuantity}x) ${itemTitle}`;

            // Perbarui bungkus dan keterangan
            if (bungkus) {
                bungkusCell.textContent = '[BUNGKUS] ';
                keteranganCell.textContent = keterangan ? `Catatan: ${keterangan}` : '';
            } else {
                bungkusCell.textContent = '';
                keteranganCell.textContent = keterangan ? `Catatan: ${keterangan}` : '';
            }
        } else {
            // Jika kuantitas 0, hapus baris
            removeOrderRow(itemTitle);
        }
    } else if (newQuantity > 0) {
        // Jika baris tidak ditemukan, tambahkan baris baru
        const newRow = orderItems.insertRow();
        const total = price * newQuantity; // Total untuk baris baru
        newRow.innerHTML = `
            <td class="item-title">${itemTitle}</td>
            <td><input class="quantity-input" type="number" value="${newQuantity}" readonly /></td>
            <td class="price-input">${price.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
            <td><input class="total-input" value="${total.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}" readonly /></td>
            <td class="bungkus-cell">${bungkus ? '[BUNGKUS] ' : ''}</td>
            <td class="keterangan-cell">${keterangan ? `Catatan: ${keterangan}` : ''}</td>
        `;
    }
}



function removeOrderRow(itemTitle) {
    // closeCartModal();
    const orderItems = document.getElementById('orderItems');
    const existingRow = Array.from(orderItems.rows).find(row => {
        return row.querySelector('.item-title') && row.querySelector('.item-title').textContent === itemTitle;
    });

    if (existingRow) {
        existingRow.remove();
    }
    
    // Setelah penghapusan, perbarui total keseluruhan
    calculateTotal();
}



function removeFromCart(itemTitle) {
    let cart = JSON.parse(localStorage.getItem('cart')) || [];
    cart = cart.filter(item => item.title !== itemTitle);
    localStorage.setItem('cart', JSON.stringify(cart));

    // Perbarui modal keranjang
    updateCartModal();
    
    // Perbarui total di container
    updateTotal();
    
    // Hapus item dari tabel pesanan
    removeOrderRow(itemTitle);
}



    function closeAllModals() {
        var modals = document.getElementsByClassName('w3-modal');
        for (var i = 0; i < modals.length; i++) {
            modals[i].style.display = 'none';
        }
    }
</script>




<!-- Modal Edit Item -->
<!-- Modal Edit Item -->
<div id="editItemModal" class="w3-modal">
    <div class="w3-modal-content w3-card-4 customm-modal-size">
        <header class="w3-container w3-teal">
            <span onclick="closeEditItemModal()" class="w3-button w3-display-topright">&times;</span>
            <h2><b>Edit Item</b></h2>
        </header>
        <div class="w3-container">
            <div class="modal-item-image">
                <img id="modalItemImage" src="default_image.png" alt="Item Image" style="width: 100%; height: auto;">
            </div>
            <div class="modal-item-details">
                <h3 id="modalItemTitle">Item Title</h3>
                <p id="modalItemPrice" class="item-price">Rp 0</p>
                <div class="quantity-controls">
                    <button onclick="kurangiKuantitas()">
                        <i class="fa fa-minus-circle"></i>
                    </button>
                    <span id="itemQuantity">1</span>
                    <button onclick="tambahKuantitas()">
                        <i class="fa fa-plus-circle"></i>
                    </button>
                </div>
                <div class="bungkus-option">
                    Bungkus
                    <label class="switch">
                        <input type="checkbox" id="bungkusToggle">
                        <span class="slider round"></span>
                    </label>
                </div>
                <textarea id="itemKeterangan" placeholder="Tambahkan Catatan (Opsional)" class="w3-input"></textarea>
            </div>
        </div>
        <div class="modal-actions">
            <button class="w3-button w3-red" onclick="addItemToCart()">
                <i class="fa fa-shopping-cart"></i> MASUKKAN KERANJANG
            </button>
            <button class="w3-button w3-grey" onclick="closeEditItemModal()">BATALKAN</button>
        </div>
    </div>
</div>

<script>
function closeEditItemModal() {
    document.getElementById('editItemModal').style.display = 'none';
}

function addItemToCart() {
    const title = document.getElementById('modalItemTitle').textContent;
    const quantity = parseInt(document.getElementById('itemQuantity').textContent);
    const image = document.getElementById('modalItemImage').src;
    const price = parseFloat(document.getElementById('modalItemPrice').textContent
        .replace('Rp ', '')
        .replace(/\./g, '') // Remove dots for thousand separators
        .replace(',', '.')); // Replace comma with dot for decimal

    const bungkus = document.getElementById('bungkusToggle').checked;
    const keterangan = document.getElementById('itemKeterangan').value;

    let cart = JSON.parse(localStorage.getItem('cart')) || [];
    const existingItemIndex = cart.findIndex(item => item.title === title);

    if (existingItemIndex > -1) {
        // Update existing item
        cart[existingItemIndex] = {
            ...cart[existingItemIndex],
            quantity: quantity,
            image: image,
            price: price,
            bungkus: bungkus,
            keterangan: keterangan
        };
    } else {
        // Add new item
        cart.push({
            title: title,
            quantity: quantity,
            image: image,
            price: price,
            bungkus: bungkus,
            keterangan: keterangan
        });
    }

    localStorage.setItem('cart', JSON.stringify(cart));
    
    // Update semua bagian yang terpengaruh
    updateCartModal();        // Update modal keranjang
    updateOrderTable(title, quantity);  // Update tabel pesanan jika ada
    updateTotal();            // Update total harga dan item
    calculateTotal();         // Perbarui grand total
    
    closeEditItemModal();     // Tutup modal edit setelah selesai
}

function openEditItemModal(title) {
    closeCartModal();

    const modal = document.getElementById('editItemModal');
    if (modal) {
        const cart = JSON.parse(localStorage.getItem('cart')) || [];
        const item = cart.find(item => item.title === title);

        if (!item) {
            console.error('Item not found.');
            return;
        }

        // Set image, title, price, and quantity in the modal
        document.getElementById('modalItemImage').src = item.image || 'default_image.png';
        document.getElementById('modalItemTitle').textContent = item.title || 'Judul Item Tidak Tersedia';
        
        // Format price in Indonesian currency format (Rp)
        const formattedPrice = item.price 
            ? `Rp ${parseFloat(item.price).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`
            : 'Rp 0,00';
        
        document.getElementById('modalItemPrice').textContent = formattedPrice;
        document.getElementById('itemQuantity').textContent = item.quantity || '1';
        document.getElementById('bungkusToggle').checked = item.bungkus || false;
        document.getElementById('itemKeterangan').value = item.keterangan || '';

        modal.style.display = 'block';
    } else {
        console.error('Modal with ID editItemModal not found.');
    }
}

// Fungsi untuk mengurangi kuantitas
function kurangiKuantitas() {
    let quantityElement = document.getElementById('itemQuantity');
    let quantity = parseInt(quantityElement.textContent);

    if (quantity > 1) {
        quantity--;
        quantityElement.textContent = quantity;
    }
}

// Fungsi untuk menambah kuantitas
function tambahKuantitas() {
    let quantityElement = document.getElementById('itemQuantity');
    let quantity = parseInt(quantityElement.textContent);

    quantity++;
    quantityElement.textContent = quantity;
}
</script>


<style>
.customm-modal-size {
    width: 50%; /* Mengurangi lebar modal menjadi 60% dari viewport */
    max-width: 450px; /* Mengurangi lebar maksimum modal menjadi 500px */
}


.modal-item-image {
    display: flex;
    justify-content: center; /* Memusatkan gambar secara horizontal */
    align-items: center; /* Memusatkan gambar secara vertikal */
    margin-bottom: 15px; /* Tambahkan margin jika diperlukan */
}

.modal-item-image img {
    border-radius: 8px;
    max-width: 50%; /* Memastikan gambar tidak melebihi lebar kontainer */
    max-height: 400px; /* Atur tinggi maksimal gambar */
    object-fit: cover; /* Menjaga proporsi gambar agar tidak terdistorsi */
}

.modal-item-details {
    text-align: center;
}

.item-price {
    font-size: 18px;
    font-weight: bold;
    color: #333;
}

.quantity-controls {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 10px;
    margin: 10px 0;
}

.quantity-controls button {
    background-color: #f1f1f1;
    border: none;
    padding: 10px;
    font-size: 18px;
    cursor: pointer;
}

.bungkus-option {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 10px;
    margin-top: 15px;
}

textarea#w3-input {
    margin-top: 10px;
    resize: none;
}

.modal-actions {
    display: flex;
    flex-direction: column; /* Susun tombol secara vertikal */
    align-items: center; /* Pusatkan tombol secara horizontal */
    padding: 10px;
}

.modal-actions button {
    width: 80%; /* Atur lebar tombol */
    padding: 10px;
    font-size: 16px;
    margin: 5px 0; /* Jarak antar tombol */
}



.switch {
    position: relative;
    display: inline-block;
    width: 34px;
    height: 20px;
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
    transition: 0.4s;
    border-radius: 20px;
}

.slider:before {
    position: absolute;
    content: "";
    height: 12px;
    width: 12px;
    left: 4px;
    bottom: 4px;
    background-color: white;
    transition: 0.4s;
    border-radius: 50%;
}

input:checked + .slider {
    background-color: #4CAF50;
}

input:checked + .slider:before {
    transform: translateX(14px);
}

</style>





<!-- Modal for adding to cart -->
<!-- Modal for adding to cart (half-screen size with image) -->
<div id="productModal" class="w3-modal">
    <div class="w3-modal-content w3-card-4" style="width: 50%; max-width: 600px; display: flex; flex-direction: row;">
        <!-- Image section -->
        <div style="flex: 1; background-color: #f1f1f1; padding: 20px; display: flex; justify-content: center; align-items: center;">
            <img id="productImage" src="default_image.png" class="item-image" style="width: 120%; height: auto; max-width: 300px;" />
        </div>
        <!-- Content section -->
        <div style="flex: 1; padding: 20px;">
            <header class="w3-container w3-white">
                <span onclick="closeProductModal()" class="w3-button w3-display-topright w3-red">&times;</span>
                <h2 style="font-weight: bold;" id="productTitle"></h2>
            </header>
            <div class="w3-container">
                <p style="font-weight: bold;" id="productDescription"></p>
                <div class="quantity-container">
                    <div class="quantity">
                        <button class="quantity-button w3-green" onclick="decrement(this)">-</button>
                        <input type="text" class="quantity-input w3-light-grey" value="1" readonly>
                        <button class="quantity-button w3-green" onclick="increment(this)">+</button>
                    </div>
                    <div class="bungkus-option">
                    Bungkus
                    <label class="switch">
                        <input type="checkbox" id="bungkusCheckbox">
                        <span class="slider round"></span>
                    </label>
                </div>
                    <!-- keterangan Input -->
                    <label for="keteranganInput"></label>
                    <textarea id="keteranganInput" placeholder="Tambahkan Catatan (Opsional)" class="w3-input"></textarea>
                    <br>
                    <button class="add-button w3-button w3-green" style="font-weight: bold;" onclick="addToCart()">
                        MASUKKAN KERANJANG <br>
                        Rp <span style="font-weight: bold;" id="productTotalPrice">0</span>
                    </button>
                    <button onclick="closeProductModal()" 
                        style="font-weight: bold; padding: 10px; width: 100%; background-color: white; color: red;" 
                        class="w3-button">Kembali
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>



<div id="id01" class="w3-modal">
  <div class="w3-modal-content">
    <header class="w3-container w3-teal"> 
      <span onclick="document.getElementById('id01').style.display='none'" 
      class="w3-button w3-display-topright">&times;</span>
      <h2>Isi Data</h2>
    </header>
    <div class="w3-container">
      <form id="modalForm" method="POST">
        <div class="form-group">
          <label for="modalNama">Nama:</label>
          <input type="text" class="form-control" id="modalNama" name="nama" required>
        </div>

        <div class="form-group">
          <label for="modalTelepon">No Telepon:</label>
          <input type="number" class="form-control" id="modalTelepon" name="notelepon" required>
        </div>
        <button type="button" class="w3-button w3-green" id="submitModalButton" onclick="submitModalData()">Mulai Pesan</button>

      </form>
    </div>
    
  </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    // Add event listeners to input fields to validate data
    document.getElementById('modalNama').addEventListener('input', validateModalFields);
    document.getElementById('modalTelepon').addEventListener('input', validateModalFields);
});

function openModal() {
    document.getElementById('id01').style.display = 'block';
    validateModalFields(); // Ensure button state is correct when opening the modal
}

function validateModalFields() {
    var nama = document.getElementById('modalNama').value.trim();
    var notelepon = document.getElementById('modalTelepon').value.trim();
    var submitButton = document.getElementById('submitModalButton');

    // Enable button only if both fields are filled
    submitButton.disabled = !(nama && notelepon);
}

function submitModalData() {
    var nama = document.getElementById('modalNama').value;
    var notelepon = document.getElementById('modalTelepon').value;

    if (nama && notelepon) {
        // Pass the modal values to the hidden form fields in the main form
        document.getElementById('hiddenNama').value = nama;
        document.getElementById('hiddenTelepon').value = notelepon;

        // Close the modal
        document.getElementById('id01').style.display = 'none';

        // Automatically submit the main form
        document.forms['mainForm'].submit();
    } else {
        // If validation fails, show an alert
        alert('Nama dan No Telepon harus diisi!');
    }
}
</script>




    <!-- Overlay Sidebar -->
    <div id="sidebarOverlay" class="w3-overlay" onclick="w3_close()"></div>
    <!-- Sidebar -->
    <div class="w3-sidebar w3-bar-block w3-border-right w3-light-grey" id="mySidebar">
        <button onclick="w3_close()" class="w3-bar-item w3-button w3-red w3-center close-button">
            <b>Close</b><i class="fa fa-close" style="font-size:20px"></i>
        </button>
        <a href="list_barang.php" class="w3-bar-item w3-button w3-border">List Barang</a>
        <a href="list_pengguna.php" class="w3-bar-item w3-button w3-border">List Pengguna</a>
        <a href="list_meja.php" class="w3-bar-item w3-button w3-border">List Meja</a>
        <?php if ($user_record === 'admin') { ?>
        <a href="list_pesanan.php" class="w3-bar-item w3-button w3-border">List Pesanan</a>
        <?php } ?>
        <a href="pesanan.php" class="w3-bar-item w3-button w3-border">Pesanan</a>
        <a href="logout.php" class="w3-bar-item w3-button w3-red w3-center"><b>Log Out </b><i class="fa fa-sign-out"
                style="font-size:20px"></i></a>
    </div>


    <!-- Header -->
    <div class="w3-teal fixed-header" style="display: flex; align-items: center;">
        <button class="w3-button w3-xlarge" onclick="w3_open()">☰</button>
        <div style="flex-grow: 1; display: flex; justify-content: center;">
            <h1 style="margin: 0; line-height: 3.5rem; margin-bottom:10px; font-size: 30px;"><b>Form Pesanan</b></h1>
        </div>
    </div>
<br>

    <div class="w3-container content-container">
    <form id="mainForm" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
        <div class="form-group">
            <strong>No Pesanan:</strong> <?php echo $new_pesanan_id; ?>
            <input type="hidden" name="nopesanan" value="<?php echo $new_pesanan_id; ?>">
        </div>

 
        <div class="form-group">
            <strong>No. Meja:</strong> <?php echo $_GET["nomeja"]; ?>
            <input type="hidden" name="nomeja" value="<?php echo $_GET["nomeja"]; ?>">
        </div>

        <div class="w3-section">
    <strong>Tanggal:</strong>
    <input type="datetime-local"  id="tanggal" name="tanggal" value="<?php echo $today; ?>" readonly>
</div>


            
        <input type="hidden" id="hiddenNama" name="nama">
        <input type="hidden" id="hiddenTelepon" name="notelepon">

            <!-- <div class="form-group">
    <label for="nama">Nama:</label>
    <input type="text" class="form-control" id="nama" name="nama" required>
</div>

<div class="form-group">
    <label for="notelepon">No Telepon:</label>
    <input type="number" class="form-control" id="notelepon" name="notelepon" required>
</div> -->

            <div class="table-responsive">
            <div class="my-2">
</div>


<table class="table">
                    <thead>
                        <tr>
                            <th>
                        <div style="display: flex; font-size: 14px;">
                    <div style="flex: 1; text-align: left;">
                        <span style="font-size: 16px; color: black; font-weight: bold;">Nama Barang</span><br><span
                            style="font-size: 16px; color: green; font-weight: bold;">Harga</span>
                        <span style="font-size: 16px; color: #009688; font-weight: bold;">Jumlah</span> <br>
                    </div>
                    </th>
                            <!-- <th>Jumlah</th> -->
                            <th style="text-align: right; color: black;"><b>Ak</b><span style="color: black;"><b>si</b></span><br>
                            <span style="color: green;"><b>Total</b></span></th>
                        </tr>
                    </thead>
                    <tbody id="orderItems">
                        <tr>

                        </tr>
                    </tbody>
                </table>
                    
                <div class="my-2">
                  
                </div>
            </div>


            <div>
            <button type="button" class="w3-button w3-green" onclick="openBarangModal()"><i class="fa fa-shopping-cart"></i> TAMBAH PESANAN LAINNYA</button>
            </div>

            <div class="form-group">
    <label for="grandtotal">Grand Total:</label>
    <input type="text" class="form-control" id="grandtotal" name="grandtotal" readonly>
</div>


            <div class="form-group">
                <label for="jenispembayaran">Jenis Pembayaran:</label>
                <select class="form-control" id="jenispembayaran" name="jenispembayaran" required>
                <option value="QRIS">QRIS</option>
                </select>
            </div>


            <script>
document.getElementById('jenispembayaran').addEventListener('change', function() {
    var selectedValue = this.value;
    if (selectedValue === 'QRIS') {
        // Ganti dengan URL QRIS yang sesuai
        window.location.href = 'url_pembayaran_qris';
    }
});
</script>


            <div class="form-group">
                <label for="bayar">Bayar:</label>
                <input type="text" class="form-control" id="bayar" name="bayar" required oninput="formatNumberInput(this)"
                    onchange="calculateTotal()">
            </div>

            <div class="form-group">
    <label for="kembali">Kembali:</label>
    <input type="text" class="form-control" id="kembali" name="kembali" readonly>
    <small id="bayarError" class="text-danger" style="display: none;">Jumlah bayar kurang</small>
</div>

<?php if ($user_record === 'admin') { ?>
            <div class="checkbox-container">
                <label for="terbayar">Terbayar </label>
                <input type="checkbox" id="terbayar" name="terbayar" class="custom-checkbox">
            </div>
            <?php } ?>

            <button type="button" class="w3-button w3-green w3-margin-top" id="payButton" onclick="openModal()">BAYAR</button>

    

                
        </form>
        <br>

        <!-- modal untuk notifikasi/pesan -->
        <div class="w3-container">
            <?php if (isset($_GET['success'])): ?>
                <div id="successModal" class="w3-modal" style="display: block;">
                    <div class="w3-modal-content w3-card-4 modal-content-custom">
                        <header class="w3-container w3-green">
                            <span onclick="closeSuccessModal()" class="w3-button w3-display-topright">&times;</span>
                            <h2>Informasi</h2>
                        </header>
                        <div class="w3-container">
                            <p>Data pesanan berhasil disimpan.</p>
                        </div>
                    </div>
                </div>
                <script>
                    // Open the success modal and set a timeout to close it automatically
                    document.addEventListener('DOMContentLoaded', function () {
                        setTimeout(closeSuccessModal, 2000);
                    });
                </script>
            <?php elseif (isset($_GET['error'])): ?>
                <div id="errorModal" class="w3-modal" style="display: block;">
                    <div class="w3-modal-content w3-card-4 modal-content-custom">
                        <header class="w3-container w3-red">
                            <span onclick="closeErrorModal()" class="w3-button w3-display-topright">&times;</span>
                            <h2>Informasi</h2>
                        </header>
                        <div class="w3-container">
                            <p>Jumlah bayar tidak mencukupi. Silakan periksa kembali.</p>
                        </div>
                    </div>
                </div>
                <script>
                    // Open the error modal and set a timeout to close it automatically
                    document.addEventListener('DOMContentLoaded', function () {
                        setTimeout(closeErrorModal, 2000);
                    });
                </script>
            <?php endif; ?>
        </div>
    </div>




    <style>
  /* Gaya yang dimodifikasi untuk sidebar */
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
        /* Gaya untuk overlay sidebar */
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
        @media (max-width: 300px) {
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
                width: 40%;
            }
        .w3-table-all th:nth-of-type(2),
        .w3-table-all td:nth-of-type(2) {
                width: 30%;
            }
        .w3-table-all th:nth-of-type(3),
        .w3-table-all td:nth-of-type(3) {
                width: 30%;
            }
        }
        .w3-overlay.show {
            display: block;
        }

/* css untuk tombol makanan dan minuman */
.filter-buttons {
    display: flex;
    justify-content: flex-end;
    margin-bottom: 20px;
}
.filter-buttons button {
    margin-left: 10px; /* Space between buttons */
    background-color: #007bff; /* Blue background color */
    color: #fff; /* White text color */
    border: none; /* Remove border */
    border-radius: 4px; /* Rounded corners */
    padding: 10px 20px; /* Padding inside the button */
    font-size: 16px; /* Font size */
    cursor: pointer; /* Pointer cursor on hover */
}
.filter-buttons button:hover {
    background-color: #0056b3; /* Darker blue on hover */
}

/* css untuk order */
.order-row {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  padding: 1px 0;
}
.item-details {
  flex-grow: 1;
}
.top-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 5px;
}
.action-buttons {
  display: flex;
  flex-direction: row; /* Align buttons side by side */
  gap: 10px; /* Space between the buttons */
}
.bottom-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.left-section {
  display: flex;
  align-items: center;
  gap: 3px; /* Keep price and quantity close */
}
.price-input {
  margin-right: 3px;
}
.quantity-x {
  margin-left: 2px; /* Reduced space around the 'x' */
}
.quantity-input {
  width: 40px;
  text-align: center;
}
.total-input {
  min-width: 2px;
  text-align: right;
  font-weight: bold; /* Highlight the total */
}
.w3-button {
  padding: 5px 10px;
}


/* css untuk quantity */

.quantity {
    display: flex;
    align-items: center;
}
.quantity-button {
    background-color: 	#228B22;
    color: white;
    border: none;
    padding: 7px 14px;
    font-size: 16px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}
.quantity-button:disabled {
    background-color: white;
    cursor: not-allowed;
}
.quantity-button:hover {
    background-color: grey;
}
.quantity-input {
    width: 50px;
    text-align: center;
    border: 1px solid #ced4da;
    border-radius: 4px;
    margin: 0 5px;
    font-size: 16px;
    padding: 5px;
    background-color: #ffffff;
    color: #495057;
    cursor: default;
}
.quantity-input:read-only {
    background-color: #f8f9fa;
}

/* css untuk sselect item */
.selected-items {
        margin-right: 10px;
        max-height: 100px;
        overflow-y: auto;
        font-size: 12px;
    }
.left-options, .right-options {
        display: flex;
        align-items: center;
    }
.left-options {
        gap: 100px;
    }
.left-options input[type="checkbox"] {
        margin-right: 100px;
    }
.left-options a {
        color: #ff5722;
        text-decoration: none;
    }

/* css untuk total dekat checkout */
.total-section {
        margin-right: 10px;
        font-weight: bold;
    }
.total-section span {
        color: green;
        align-items: right;
    }

/* css untuk tombol check out */
.checkout-button {
        background-color: #ff5722;
        color: white;
        padding: 10px 20px;
        border: none;
        cursor: pointer;
        font-size: 16px;
    }
.checkout-container {
        position: fixed;
        bottom: 0;
        width: 100%;
        background-color: white;
        padding: 10px;
        box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
        display: flex;
        justify-content: right;
        align-items: right;
        z-index: 1000;
    }
.checkout-button {
        background-color: green;
        color: white;
        border: none;
        padding: 15px 30px;
        border-radius: 25px;
        font-size: 16px;
        font-weight: bold;
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: right;
    }
.checkout-button .total-price {
        margin-left: 15px;
        font-size: 18px;
        font-weight: bold;
    }
.custom-modal-size {
        width: 100%;
        height: 100vh;
        max-width: 100%;
        max-height: 100%;
        margin: 0;
        border-radius: 0;
        position: fixed;
        top: 0;
        left: 0;
        overflow: hidden;
    }
.table-container {
        max-height: calc(79vh - 60px);
        overflow-y: auto;
        padding: 10px;
    }

.w3-modal {
        z-index: 9999;
    }

/* css untuk header tetap */
.fixed-header {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        height: 3.5rem;
        z-index: 1000;
        }
        /* Menyesuaikan padding container agar tidak tertutup header */
.content-container {
        padding-top: 3.5rem;
        }

 /* css untuk checkbox */
.checkbox-container {
    display: flex;
    align-items: center;
    }
.custom-checkbox {
    width: 30px;
    height: 30px;
    margin-left: 10px;
/* Sesuaikan jarak antara label dan checkbox */
    }
    </style>
   

<!-- CSS
    SCRIPT -->


   <script>

/*script untuk modal barang*/
function openBarangModal() {
    document.getElementById('barangModal').style.display = 'block';
    localStorage.setItem('modalState', 'open'); // Save modal state as 'open' in localStorage
}
// Function to close the barang modal
function closeBarangModal() {
    document.getElementById('barangModal').style.display = 'none';
    localStorage.setItem('modalState', 'closed'); // Save modal state as 'closed' in localStorage
}
// Check the modal state on page load and open the modal if needed
function checkModalState() {
    const modalState = localStorage.getItem('modalState');
    if (modalState === 'open') {
        openBarangModal();
    }
}
// Call the function to check the modal state when the page loads
window.addEventListener('load', checkModalState);


// script untuk refresh
window.onload = function() {
        var modal = document.getElementById("barangModal");
        var makananButton = document.getElementById("makananButton");
        var span = document.getElementsByClassName("close")[0];

        // Tampilkan modal ketika halaman dimuat
        modal.style.display = "block";

        // Tutup modal ketika pengguna menekan tombol close (x)
        if (span) {
            span.onclick = function() {
                modal.style.display = "none";
            }
        }
        // Tutup modal jika pengguna mengklik di luar modal
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
        // Klik otomatis pada tombol Makanan setelah modal sepenuhnya dimuat
        setTimeout(function() {
            if (makananButton) {
                makananButton.click();
            }
        }, 100); // Delay in milliseconds
    }

    function filterCategory(category) {
        var table, tr, td, i, itemCategory;

        table = document.querySelector('.w3-table-all');
        tr = table.getElementsByTagName('tr');

        for (i = 0; i < tr.length; i++) {
            td = tr[i].getElementsByTagName('td');
            if (td.length > 0) {
                itemCategory = td[1] ? td[1].textContent.toLowerCase() : ''; // Asumsi kategori ada di kolom kedua

                if (category === '' || itemCategory.includes(category.toLowerCase())) {
                    tr[i].style.display = "";
                } else {
                    tr[i].style.display = "none";
                }
            }
        }
    }

    document.getElementById('makananButton').addEventListener('click', function() {
        filterCategory('makanan');
    });

    document.getElementById('minumanButton').addEventListener('click', function() {
        filterCategory('minuman');
    }); 

// script untuk modal 1 dan 2

// Fungsi untuk menghapus cart
function clearCart() {
    // Hapus HTML item yang dipilih
    document.getElementById('selected-items').innerHTML = '';
    // Reset jumlah item dan harga
    document.getElementById('total-items').textContent = '0';
    document.getElementById('total-price').textContent = 'Rp0';
    
    // Hapus cart dari localStorage
    localStorage.removeItem('cart');
}

// Fungsi yang dijalankan saat halaman dimuat
function onPageLoad() {
    clearCart(); // Hapus cart saat halaman dimuat
}

// Panggil fungsi onPageLoad ketika halaman siap
window.addEventListener('load', onPageLoad);

function handleAddButtonClick() {
    addToCart(); // Add the item to localStorage cart
    addSelectedItemToOrder(); // Add the item to the order form
}

function addSelectedItemToOrder() {
    // Get item details from the modal
    var title = document.getElementById('productTitle').textContent;
    var price = parseFloat(document.getElementById('productTotalPrice').getAttribute('data-price').replace('.', '').replace(',', '.'));
    var quantity = parseInt(document.querySelector('.quantity-input').value);
    var total = price * quantity;

    // Get bungkus and keterangan from the modal
    var bungkus = document.getElementById('bungkusCheckbox').checked; // Checkbox di modal
    var keterangan = document.getElementById('keteranganInput').value.trim(); // Textarea di modal

    // Reference to the orderItems table body
    var orderItems = document.getElementById('orderItems');

    // Check if the orderItems element exists
    if (!orderItems) {
        console.error('Order items table body not found.');
        return;
    }

    // Check if the item already exists in the table
    var existingRow = Array.from(orderItems.rows).find(row => {
        return row.querySelector('.item-title') && row.querySelector('.item-title').textContent.includes(title);
    });

    if (existingRow) {
        // Update quantity and total for existing item
        var quantityInput = existingRow.querySelector('.quantity-input');
        var totalInput = existingRow.querySelector('.total-input');
        var bungkusCell = existingRow.querySelector('.bungkus-cell');
        var keteranganCell = existingRow.querySelector('.keterangan-cell');
        
        // Update quantity
        quantityInput.value = parseInt(quantityInput.value) + quantity;
        
        // Update total
        var newQuantity = parseInt(quantityInput.value);
        var newTotal = price * newQuantity;
        totalInput.value = newTotal.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        // Update the item title with new quantity
        var itemTitle = existingRow.querySelector('.item-title');
        itemTitle.textContent = `(${newQuantity}x) ${title}`;

        // Update bungkus and keterangan
        bungkusCell.textContent = bungkus ? '[BUNGKUS]' : '';
        keteranganCell.textContent = keterangan ? `Catatan: ${keterangan}` : ''; // Only show if keterangan is not empty

        // Recalculate the total for the row
        calculateRowTotal(quantityInput);
    } else {
        // Create a new row in the order form
        var newRow = orderItems.insertRow();
        
        // Prepare the keterangan display
        var keteranganDisplay = keterangan ? `Catatan: ${keterangan}` : '';

        // Add row with hidden inputs for 'idbarang', 'harga', 'jumlah', 'bungkus', and 'keterangan'
        newRow.innerHTML = `
            <td colspan="4">
                <div class="order-row">
                    <div class="item-details">
                        <div class="top-row">
                            <span class="item-title" style="font-weight: bold;">
                                (${quantity}x) ${title}
                            </span>
                            <span class="bungkus-cell" style="margin-left: 10px;">${bungkus ? '[BUNGKUS]' : ''}</span>
                            <span class="keterangan-cell" style="margin-left: 10px;">${keteranganDisplay}</span>
<div class="action-buttons" style="margin-left: auto;">
<button type="button" class="" onclick="openEditItemModal('${title}', ${price}, ${quantity}, ${bungkus ? 'true' : 'false'}, '${keterangan}')"><i class="fa fa-pencil"></i></button>
    <button type="button" class="w3-button w3-red" onclick="handleRemoveItem('${title}', this)">Hapus</button>
</div>

                        </div>
                        <div class="bottom-row">
                            <div class="left-section">
                                <span class="price-input" style="color: green; font-weight: bold;">${price.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span>
                                <span class="quantity-x"> x </span>
                                
                                <button type="button" class="w3-button w3-blue" onclick="decrementQuantity(this)">-</button>
                                <input type="number" class="form-control quantity-input" style="color: #009688; font-weight: bold;" name="jumlah[]" value="${quantity}" oninput="calculateRowTotal(this)" readonly>
                                <button type="button" class="w3-button w3-blue" onclick="incrementQuantity(this)">+</button>

                                <input type="hidden" name="idbarang[]" value="${title}">
                                <input type="hidden" name="harga[]" value="${price.toFixed(2).replace('.', ',')}">
                            </div>
                            <input type="text" class="form-control total-input" name="total[]" style="color: green;" value="${total.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}" readonly>
                        </div>
                    </div>
                </div>
            </td>
        `;
    }

    // Update the grand total
    calculateTotal();

    // Close the modal after adding the item to the form
    closeProductModal();
}

function incrementQuantity(button) {
    // Mendapatkan input quantity yang terkait
    const quantityInput = button.parentNode.querySelector('.quantity-input');
    const itemId = button.dataset.itemId; // Anggap setiap tombol memiliki data atribut untuk item id

    // Menambahkan 1 pada nilai quantity
    quantityInput.value = parseInt(quantityInput.value) + 1;

    // Perbarui item di localStorage
    let cart = JSON.parse(localStorage.getItem('cart')) || [];
    let item = cart.find(i => i.id === itemId);
    if (item) {
        item.quantity += 1;
        localStorage.setItem('cart', JSON.stringify(cart));
    }

    // Update jumlah di sebelah nama item
    const itemTitle = button.closest('.order-row').querySelector('.item-title');
    itemTitle.textContent = `(${quantityInput.value}x) ${itemTitle.textContent.split(') ')[1]}`;

    // Panggil fungsi untuk menghitung ulang total baris
    calculateRowTotal(quantityInput);

    // Panggil fungsi untuk memperbarui total
    updateTotal();
}

function decrementQuantity(button) {
    // Mendapatkan input quantity yang terkait
    const quantityInput = button.parentNode.querySelector('.quantity-input');
    const itemId = button.dataset.itemId; // Anggap setiap tombol memiliki data atribut untuk item id

    // Mengurangi 1 pada nilai quantity, tetapi tidak boleh kurang dari 1
    if (quantityInput.value > 1) {
        quantityInput.value = parseInt(quantityInput.value) - 1;

        // Perbarui item di localStorage
        let cart = JSON.parse(localStorage.getItem('cart')) || [];
        let item = cart.find(i => i.id === itemId);
        if (item) {
            item.quantity -= 1;
            localStorage.setItem('cart', JSON.stringify(cart));
        }

        // Update jumlah di sebelah nama item
        const itemTitle = button.closest('.order-row').querySelector('.item-title');
        itemTitle.textContent = `(${quantityInput.value}x) ${itemTitle.textContent.split(') ')[1]}`;

        // Panggil fungsi untuk menghitung ulang total baris
        calculateRowTotal(quantityInput);

        // Panggil fungsi untuk memperbarui total
        updateTotal();
    }
}


function handleRemoveItem(title, button) {
    removeItemFromCart(title);
    deleteRow(button);
}

function deleteRow(button) {
    // Find the row to delete
    let row = button.closest('tr');
    
    // Get the item title from the row
    let itemTitle = row.querySelector('.item-title').textContent.trim();

    // Remove the row from the table
    row.parentNode.removeChild(row);

    // Also remove the corresponding item from the cart in localStorage
    removeItemFromCart(itemTitle);

    // Recalculate and update the grand total
    updateGrandTotal();
}

function removeItemFromCart(title) {
    // Retrieve existing cart from localStorage
    let cart = JSON.parse(localStorage.getItem('cart')) || [];

    // Filter out the item with the specified title
    cart = cart.filter(item => item.title !== title);

    // Save updated cart to localStorage
    localStorage.setItem('cart', JSON.stringify(cart));

    // Update the total items and price
    updateTotal();
}
// Initial update for the checkout container
updateTotal();


// Attach the addSelectedItemToOrder function to the modal "Add" button
document.querySelector('.add-button').addEventListener('click', addSelectedItemToOrder);

function checkoutAndRedirect() {
    // Tutup modal
    document.getElementById('barangModal').style.display = 'none';
    
    // Redirect ke halaman pesanan.php
    window.location.href = 'pesanan.php';
}

function openProductModal(title, description, price, imageUrl) {
    // Set product image, title, description, and price in the modal
    document.getElementById('productImage').src = imageUrl; // Set the actual path to your product image
    document.getElementById('productTitle').textContent = title;
    document.getElementById('productDescription').textContent = description;
    document.getElementById('productTotalPrice').setAttribute('data-price', price);
    
    // Initialize quantity and total price in the modal
    document.querySelector('.quantity-input').value = 1; // Set initial quantity to 1
    updateProductPrice(); // Update the total price based on initial quantity

    // Kosongkan checkbox bungkus dan textarea keterangan
    document.getElementById('bungkusCheckbox').checked = false; // Reset checkbox
    document.getElementById('keteranganInput').value = ''; // Reset textarea

    // Show the modal
    document.getElementById('productModal').style.display = 'block';
}


    function closeProductModal() {
        document.getElementById('productModal').style.display = 'none';
    }

    function increment(button) {
        var input = button.parentElement.querySelector('.quantity-input');
        var currentValue = parseInt(input.value);
        input.value = currentValue + 1;
        updateProductPrice();
    }

    function decrement(button) {
        var input = button.parentElement.querySelector('.quantity-input');
        var currentValue = parseInt(input.value);
        if (currentValue > 1) { // Ensure quantity doesn't go below 1
            input.value = currentValue - 1;
            updateProductPrice();
        }
    }

    function updateProductPrice() {
        var pricePerItem = parseFloat(document.getElementById('productTotalPrice').getAttribute('data-price').replace('.', '').replace(',', '.'));
        var quantity = parseInt(document.querySelector('.quantity-input').value);
        var totalPrice = pricePerItem * quantity;

        document.getElementById('productTotalPrice').textContent = totalPrice.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }


    function addToCart() {
    // Ambil detail item
    const title = document.getElementById('productTitle').textContent.trim();
    const price = parseFloat(document.getElementById('productTotalPrice').getAttribute('data-price').replace('.', '').replace(',', '.'));
    const quantity = parseInt(document.querySelector('.quantity-input').value, 10);
    const imageUrl = document.getElementById('productImage').src; // Ambil URL gambar produk
    const bungkus = document.getElementById('bungkusCheckbox').checked; // Status bungkus
    const keterangan = document.getElementById('keteranganInput').value.trim(); // Keterangan pengguna

    if (isNaN(price) || isNaN(quantity) || quantity <= 0) {
        console.error('Harga atau jumlah tidak valid.');
        return;
    }

    // Ambil keranjang yang ada dari localStorage atau inisialisasi
    let cart = JSON.parse(localStorage.getItem('cart')) || [];

    // Cek jika item sudah ada di keranjang
    const itemIndex = cart.findIndex(item => item.title === title);
    if (itemIndex > -1) {
        // Perbarui kuantitas dan informasi tambahan jika item sudah ada di keranjang
        cart[itemIndex].quantity += quantity;
        cart[itemIndex].bungkus = bungkus; // Update status bungkus
        cart[itemIndex].keterangan = keterangan; // Update keterangan
    } else {
        // Tambahkan item baru ke keranjang dengan bungkus dan keterangan
        cart.push({ title, price, quantity, image: imageUrl, bungkus, keterangan });
    }

    // Simpan keranjang yang diperbarui ke localStorage
    localStorage.setItem('cart', JSON.stringify(cart));

    // Perbarui total item dan harga di modal
    updateTotal();

    // Perbarui tampilan keranjang langsung setelah menambahkan item
    updateCartDisplay(cart);

    // Kosongkan keterangan untuk penggunaan selanjutnya
    document.getElementById('keteranganInput').value = '';

    // Tutup modal produk
    closeProductModal();
}


function updateTotal() {
    // Retrieve cart from localStorage
    let cart = JSON.parse(localStorage.getItem('cart')) || [];
    let totalItems = cart.length; // Hitung total barang berdasarkan jumlah item unik
    let totalPrice = 0;
    let selectedItemsHtml = '';

    cart.forEach(item => {
        totalPrice += item.price * item.quantity;

        // Build HTML for selected items
        selectedItemsHtml += `
            <div class="selected-item">
                <span class="item-title">${item.title}</span> 
                <span class="item-quantity">(${item.quantity})</span> 
                <span class="item-price">Rp ${item.price.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span>
                <span class="item-total">Rp ${(item.price * item.quantity).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span>
            </div>`;
    });

    // Update the selected items list and total section
    // document.getElementById('selected-items').innerHTML = selectedItemsHtml;
    document.getElementById('total-items').textContent = totalItems; // Total jumlah barang unik
    document.getElementById('total-price').textContent = 'Rp ' + totalPrice.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    // Show or hide the checkout container based on the number of items
    const checkoutContainer = document.querySelector('.checkout-container');
    if (totalItems > 0) {
        checkoutContainer.style.display = 'flex'; // Show the checkout container
    } else {
        checkoutContainer.style.display = 'none'; // Hide the checkout container
    }
}

// Initial update for the checkout container
updateTotal();


function calculateRowTotal(input) {
    var row = input.closest('tr'); // Mendapatkan baris terkait
    var priceElement = row.querySelector('.price-input'); // Elemen harga satuan
    var totalInput = row.querySelector('.total-input'); // Input untuk total per item

    // Ambil harga satuan dan jumlah item
    var price = parseFloat(priceElement.textContent.replace(/\./g, '').replace(',', '.')); // Ubah format ID ke angka
    var quantity = parseInt(input.value);

    // Hitung total untuk baris ini
    var rowTotal = price * quantity;

    // Tampilkan hasilnya ke input total
    totalInput.value = rowTotal.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    // Hitung ulang grand total
    calculateTotal();
}


function updateGrandTotal() {
    let total = 0;

    // Select all input elements with the name 'total[]'
    const totalElements = document.querySelectorAll('input[name="total[]"]');

    // Check if there are any 'total' elements left
    if (totalElements.length > 0) {
        // Loop through each element and add up the total
        totalElements.forEach(function (element) {
            // Convert the value from text format to a number
            let value = parseFloat(element.value.replace(/\./g, '').replace(',', '.'));
            if (!isNaN(value)) {
                total += value;
            }
        });

        // Format the total into Indonesian currency format
        let formattedTotal = total.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

        // Display the grand total in the input field
        document.getElementById('grandtotal').value = formattedTotal;
    } else {
        // If no 'total' elements remain, set the grand total to 0
        document.getElementById('grandtotal').value = '0,00';
    }
}
// script untuk close
  // Function to close the success modal
function closeSuccessModal() {
            document.getElementById('successModal').style.display = 'none';
            }
            // Function to close the error modal
function closeErrorModal() {
             document.getElementById('errorModal').style.display = 'none';
            }
// script untuk membuka menu
function w3_open() {
            document.getElementById("mySidebar").classList.add("show");
            document.getElementById("sidebarOverlay").classList.add("show");
        }

function w3_close() {
            document.getElementById("mySidebar").classList.remove("show");
            document.getElementById("sidebarOverlay").classList.remove("show");
        }

        function calculateTotal() {
    var orderItems = document.getElementById('orderItems');
    var totalInputs = orderItems.querySelectorAll('.total-input');
    var grandTotal = 0;

    totalInputs.forEach(function(totalInput) {
        // Pastikan untuk membersihkan format angka sebelum melakukan perhitungan
        var rowTotal = parseFloat(totalInput.value.replace(/\./g, '').replace(',', '.')) || 0;
        grandTotal += rowTotal;
    });

    // Tampilkan nilai grand total
    document.getElementById('grandtotal').value = grandTotal.toLocaleString('id-ID', { 
        minimumFractionDigits: 2, 
        maximumFractionDigits: 2 
    });

    // Panggil calculateKembali untuk menghitung kembalian setelah grand total di-update
    calculateKembali();
}


   function formatNumberInput(input) {
    let value = input.value.replace(/\./g, '').replace(/[^,\d]/g, '');
    let parts = value.split(',');
    let intPart = parts[0];
    let formatted = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, '.');

    if (parts.length > 1) {
        formatted += ',' + parts[1].slice(0, 2); // Maksimal 2 angka desimal
    }

    input.value = formatted;
}

    function setHarga(select) {
        var harga = select.options[select.selectedIndex].getAttribute('data-harga');
        var row = select.closest('tr');
        var hargaFormatted = new Intl.NumberFormat('de-DE').format(harga);
        row.querySelector('input[name="harga[]"]').value = hargaFormatted;
        calculateRowTotal(row.querySelector('input[name="jumlah[]"]'));
    }

    document.addEventListener("DOMContentLoaded", function () {
    // Event listener for input change in the 'bayar' field
    document.getElementById('bayar').addEventListener('input', calculateKembali);
});

function calculateKembali() {
    var grandTotal = parseFloat(document.getElementById('grandtotal').value.replace(/\./g, '').replace(',', '.')) || 0;
    var bayarField = document.getElementById('bayar');
    var kembaliField = document.getElementById('kembali');
    var bayarError = document.getElementById('bayarError');
    var payButton = document.getElementById('payButton'); // Button element

    // Ambil nilai bayar
    var bayar = parseFloat(bayarField.value.replace(/\./g, '').replace(/,/g, '.')) || 0;

    if (bayar > 0) {
        var kembali = bayar - grandTotal;

        if (kembali < 0) {
            // Jika nilai kembali negatif, kosongkan kolom dan tampilkan error
            kembaliField.value = "";
            bayarError.style.display = 'block';  // Tampilkan pesan error
            payButton.disabled = true; // Nonaktifkan tombol BAYAR
        } else {
            // Jika cukup, tampilkan nilai kembali
            kembaliField.value = kembali.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            bayarError.style.display = 'none';  // Sembunyikan pesan error
            payButton.disabled = false; // Aktifkan tombol BAYAR
        }
    } else {
        // Jika bayar tidak valid, kosongkan kolom kembali dan sembunyikan error
        kembaliField.value = "";
        bayarError.style.display = 'none';
        payButton.disabled = true; // Nonaktifkan tombol BAYAR
    }
}


document.addEventListener("DOMContentLoaded", function () {
    var inputs = document.querySelectorAll('input[required], select[required]');
    inputs.forEach(input => {   
        input.addEventListener('invalid', function (event) {
            event.preventDefault();
            let message = "Mohon diisi, tidak boleh kosong"; // Default message for empty fields
            if (input.id === 'nomeja') {
                message = "Tolong isi meja"; // Custom message for 'nomeja'
            }
            input.setCustomValidity(message);
            input.reportValidity();
        });

        input.addEventListener('input', function () {
            input.setCustomValidity(""); // Reset custom message on input
        });
    });
});

function validateForm() {
    var form = document.getElementById('mainForm');
    if (form.checkValidity()) {
        openModal(); // Call your function to open the modal
    } else {
        form.reportValidity(); // Trigger the validation messages
    }
}

    document.addEventListener('DOMContentLoaded', function () {
            const datetimeInput = document.getElementById('tanggal');

            function formatDateTime() {
                const now = new Date();
                const year = now.getFullYear();
                const month = String(now.getMonth() + 1).padStart(2, '0'); // Bulan adalah basis nol
                const day = String(now.getDate()).padStart(2, '0');
                const hours = String(now.getHours()).padStart(2, '0');
                const minutes = String(now.getMinutes()).padStart(2, '0');

                return `${year}-${month}-${day}T${hours}:${minutes}`;
            }

            // Set nilai dan atribut min dari input ke tanggal dan waktu saat ini
            const now = formatDateTime();
            datetimeInput.value = now; // Set nilai input ke tanggal dan waktu saat ini
            datetimeInput.min = now; // Set nilai minimum yang diizinkan ke tanggal dan waktu saat ini
        });
</script>


</body>
</html>