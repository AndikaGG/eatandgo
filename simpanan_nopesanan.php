<?php
// Fungsi untuk menghasilkan no pesanan baru dengan idtoko
function generate_pesanan_id($conn, $idtoko)
{
    $current_year = date('y');
    $current_month = date('m');

    do {
        $sql = "SELECT IFNULL(MAX(CAST(SUBSTR(nopesanan, 11, 5) AS UNSIGNED)), 0) as last_number
                FROM eat_and_go_pesanan
                WHERE SUBSTR(nopesanan, 3, 6) = CONCAT('$current_year', '$current_month', '$idtoko')";

        $result = mysqli_query($conn, $sql);
        if (!$result) {
            die("Query error: " . mysqli_error($conn));
        }

        $row = mysqli_fetch_assoc($result);
        $last_number = $row['last_number'];

        $new_sequence = str_pad($last_number + 1, 5, '0', STR_PAD_LEFT);
        $new_pesanan_id = "OR" . $idtoko . $current_year . $current_month . $new_sequence;

        $check_query = "SELECT COUNT(*) as count FROM eat_and_go_pesanan WHERE nopesanan = '$new_pesanan_id'";
        $check_result = mysqli_query($conn, $check_query);
        $check_row = mysqli_fetch_assoc($check_result);
    } while ($check_row['count'] > 0);

    return $new_pesanan_id;
}

// Mendapatkan idtoko berdasarkan login atau URL
function get_idtoko($conn)
{

    // Jika user login
    if (isset($_SESSION['username'])) {
        $username = $_SESSION['username'];
        $query = "SELECT IDTOKO FROM eat_and_go_pengguna WHERE username = '$username' LIMIT 1";
        $result = mysqli_query($conn, $query);

        if ($result && mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            return $row['IDTOKO'];
        }
    }

    // Jika user tidak login, ambil IDTOKO dari URL
    if (isset($_GET['IDTOKO'])) {
        return mysqli_real_escape_string($conn, $_GET['IDTOKO']);
    }

    die("ID toko tidak ditemukan.");
}

// Menggunakan fungsi
$idtoko = get_idtoko($conn);
$new_pesanan_id = generate_pesanan_id($conn, $idtoko);
$now = date('Y-m-d H:i:s');
?>