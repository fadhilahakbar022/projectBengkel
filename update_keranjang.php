<?php
session_start();
include 'db/koneksi.php'; // Pastikan path benar

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['kuantitas'])) {

    // Kumpulkan ID produk untuk query stok sekali jalan (efisiensi)
    $product_ids = array_keys($_POST['kuantitas']);
    
    if (!empty($product_ids)) {
        $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
        $sql_stok = "SELECT id, stok FROM produk WHERE id IN ($placeholders)";
        $stmt_stok = $conn->prepare($sql_stok);
        
        // Bind parameter dinamis
        $types = str_repeat('i', count($product_ids));
        $stmt_stok->bind_param($types, ...$product_ids);
        $stmt_stok->execute();
        $result_stok = $stmt_stok->get_result();
        
        $stok_db = [];
        while ($row = $result_stok->fetch_assoc()) {
            $stok_db[$row['id']] = $row['stok'];
        }
        $stmt_stok->close();

        // Proses Update
        foreach ($_POST['kuantitas'] as $id_produk => $kuantitas) {
            $id_produk = (int)$id_produk;
            $kuantitas = (int)$kuantitas;

            if ($id_produk > 0) {
                if ($kuantitas <= 0) {
                    // Hapus jika 0 atau minus
                    unset($_SESSION['keranjang'][$id_produk]);
                    // Hapus dari DB juga jika login
                    if (isset($_SESSION['user']['id']) || isset($_SESSION['user_id'])) {
                        $uid = $_SESSION['user']['id'] ?? $_SESSION['user_id'];
                        $conn->query("DELETE FROM keranjang_tersimpan WHERE id_user = $uid AND id_produk = $id_produk");
                    }
                } else {
                    // Cek Stok DB
                    $max_stok = $stok_db[$id_produk] ?? 0;
                    if ($kuantitas > $max_stok) {
                        $kuantitas = $max_stok; // Batasi sesuai stok
                    }

                    // Update Session
                    $_SESSION['keranjang'][$id_produk] = $kuantitas;

                    // Update DB jika login
                    if (isset($_SESSION['user']) || isset($_SESSION['user_id'])) {
                        $id_user = $_SESSION['user']['id'] ?? $_SESSION['user_id'];
                        
                        // Upsert Logic (Insert or Update)
                        $stmt = $conn->prepare("INSERT INTO keranjang_tersimpan (id_user, id_produk, kuantitas) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE kuantitas = VALUES(kuantitas)");
                        $stmt->bind_param("iii", $id_user, $id_produk, $kuantitas);
                        $stmt->execute();
                        $stmt->close();
                    }
                }
            }
        }
    }
}

// Redirect kembali ke keranjang
header('Location: keranjang.php');
exit;
?>