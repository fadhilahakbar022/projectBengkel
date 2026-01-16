<?php
session_start();
include 'db/koneksi.php'; // Pastikan path benar

// Pastikan ID produk ada di URL
if (!isset($_GET['id'])) {
    header('Location: keranjang.php');
    exit;
}

$id_produk = (int)$_GET['id'];

// 1. Hapus dari Session
if (isset($_SESSION['keranjang'][$id_produk])) {
    unset($_SESSION['keranjang'][$id_produk]);
}

// 2. Hapus dari Database (Jika User Login)
// Cek kedua kemungkinan format session ID
if (isset($_SESSION['user']) || isset($_SESSION['user_id'])) {
    // Prioritaskan user['id'] jika ada, kalau tidak pakai user_id langsung
    $user_id = isset($_SESSION['user']['id']) ? $_SESSION['user']['id'] : $_SESSION['user_id'];
    
    $stmt = $conn->prepare("DELETE FROM keranjang_tersimpan WHERE id_user = ? AND id_produk = ?");
    $stmt->bind_param("ii", $user_id, $id_produk);
    $stmt->execute();
    $stmt->close();
}

// Kembali ke halaman keranjang
header('Location: keranjang.php');
exit;
?>