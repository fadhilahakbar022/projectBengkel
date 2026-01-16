<?php
session_start();
include 'db/koneksi.php';

// 1. Validasi Akses
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: keranjang.php");
    exit;
}

// Cek Login
if (!isset($_SESSION['user']) && !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Cek Keranjang
if (empty($_SESSION['keranjang'])) {
    header("Location: index.php");
    exit;
}

// 2. Ambil ID User
$id_user = null;
if (isset($_SESSION['user'])) {
    if (is_array($_SESSION['user'])) {
        $id_user = $_SESSION['user']['id'] ?? $_SESSION['user']['id_user'] ?? null;
    } else {
        $id_user = $_SESSION['user'];
    }
}
if (!$id_user && isset($_SESSION['user_id'])) {
    $id_user = $_SESSION['user_id'];
}

if (!$id_user) {
    die("Error: ID User tidak valid.");
}

// 3. Ambil Data Form (TERMASUK JASA PENGIRIMAN & ONGKIR)
$nama_penerima = trim($_POST['nama_penerima']);
$no_hp = trim($_POST['no_hp']);
$alamat_input = trim($_POST['alamat']);
$catatan = trim($_POST['catatan']);
$metode_pembayaran = $_POST['metode_pembayaran'];
$jasa_pengiriman = $_POST['jasa_pengiriman']; // <--- DATA BARU
$ongkos_kirim = $_POST['ongkos_kirim'];       // <--- DATA BARU
$grand_total = $_POST['total_harga'];

$alamat_lengkap = "Penerima: $nama_penerima ($no_hp)\nAlamat: $alamat_input";

// Status langsung 'Diproses' (Simulasi)
$status_awal = 'Diproses';

// 4. Mulai Transaksi Database
$conn->begin_transaction();

try {
    // A. Simpan ke Tabel PESANAN (Update Query untuk Jasa Pengiriman)
    // Pastikan kolom 'jasa_pengiriman' dan 'ongkos_kirim' sudah ada di database!
    $stmt = $conn->prepare("INSERT INTO pesanan (id_user, total_harga, alamat_pengiriman, catatan, metode_pembayaran, jasa_pengiriman, ongkos_kirim, status_pesanan) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    
    // idssssds -> int, decimal, string, string, string, string, decimal, string
    $stmt->bind_param("idssssds", $id_user, $grand_total, $alamat_lengkap, $catatan, $metode_pembayaran, $jasa_pengiriman, $ongkos_kirim, $status_awal);
    
    if (!$stmt->execute()) {
        throw new Exception("Gagal menyimpan pesanan: " . $stmt->error);
    }
    
    $id_pesanan = $conn->insert_id;
    $stmt->close();

    // B. Simpan Detail & Kurangi Stok
    $keranjang = $_SESSION['keranjang'];
    $ids = array_keys($keranjang);
    
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $sql_prod = "SELECT id, harga, stok FROM produk WHERE id IN ($placeholders)";
    $stmt_prod = $conn->prepare($sql_prod);
    $types = str_repeat('i', count($ids));
    $stmt_prod->bind_param($types, ...$ids);
    $stmt_prod->execute();
    $res_prod = $stmt_prod->get_result();
    
    while ($row = $res_prod->fetch_assoc()) {
        $id_prod = $row['id'];
        if (!isset($keranjang[$id_prod])) continue; 

        $qty_beli = $keranjang[$id_prod];
        $harga_satuan = $row['harga'];
        $subtotal = $harga_satuan * $qty_beli;
        
        if ($qty_beli > $row['stok']) {
            throw new Exception("Stok habis untuk ID $id_prod");
        }

        $stmt_detail = $conn->prepare("INSERT INTO detail_pesanan (id_pesanan, id_produk, harga_satuan, kuantitas, subtotal) VALUES (?, ?, ?, ?, ?)");
        $stmt_detail->bind_param("iidid", $id_pesanan, $id_prod, $harga_satuan, $qty_beli, $subtotal);
        $stmt_detail->execute();
        $stmt_detail->close();

        $stmt_update = $conn->prepare("UPDATE produk SET stok = stok - ? WHERE id = ?");
        $stmt_update->bind_param("ii", $qty_beli, $id_prod);
        $stmt_update->execute();
        $stmt_update->close();
    }

    // C. Bersihkan Keranjang
    unset($_SESSION['keranjang']);
    $conn->query("DELETE FROM keranjang_tersimpan WHERE id_user = $id_user");

    // D. Commit
    $conn->commit();

    $_SESSION['last_order_id'] = $id_pesanan;
    header("Location: berhasil.php");
    exit;

} catch (Exception $e) {
    $conn->rollback();
    echo "Error: " . $e->getMessage();
    echo "<br><a href='keranjang.php'>Kembali</a>";
}
?>