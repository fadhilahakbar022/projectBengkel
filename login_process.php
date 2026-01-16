<?php
session_start();
include 'db/koneksi.php';

// Hanya proses jika metode adalah POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

$username = $_POST['username'];
$password = $_POST['password'];

if (empty($username) || empty($password)) {
    header('Location: login.php?error=Username atau password salah');
    exit;
}

// Cari pengguna berdasarkan username
$sql = "SELECT id_user, username, password, role FROM users WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();

    // Tolak jika yang mencoba login adalah admin
    if ($user['role'] === 'admin') {
        header('Location: login.php?error=Login admin hanya melalui halaman admin.');
        exit;
    }

    // Verifikasi password untuk pengguna
    if (password_verify($password, $user['password']) && $user['role'] === 'user') {
        // Login berhasil, buat sesi SPESIFIK untuk 'user'
        $_SESSION['user'] = [
            'id' => $user['id_user'],
            'username' => $user['username']
        ];

        // Logika penggabungan keranjang
        $user_id = $_SESSION['user']['id'];
        $session_cart = $_SESSION['keranjang'] ?? [];

        $db_cart = [];
        $stmt_get = $conn->prepare("SELECT id_produk, kuantitas FROM keranjang_tersimpan WHERE id_user = ?");
        $stmt_get->bind_param("i", $user_id);
        $stmt_get->execute();
        $result_get = $stmt_get->get_result();
        while ($row = $result_get->fetch_assoc()) {
            $db_cart[$row['id_produk']] = (int)$row['kuantitas'];
        }
        $stmt_get->close();

        $merged_cart = $db_cart;
        foreach ($session_cart as $id_produk => $kuantitas) {
            $merged_cart[$id_produk] = ($merged_cart[$id_produk] ?? 0) + $kuantitas;
        }

        if (!empty($merged_cart)) {
            $stmt_del = $conn->prepare("DELETE FROM keranjang_tersimpan WHERE id_user = ?");
            $stmt_del->bind_param("i", $user_id);
            $stmt_del->execute();
            $stmt_del->close();
            
            $stmt_ins = $conn->prepare("INSERT INTO keranjang_tersimpan (id_user, id_produk, kuantitas) VALUES (?, ?, ?)");
            foreach ($merged_cart as $id_produk => $kuantitas) {
                $stmt_ins->bind_param("iii", $user_id, $id_produk, $kuantitas);
                $stmt_ins->execute();
            }
            $stmt_ins->close();
        }
        
        $_SESSION['keranjang'] = $merged_cart;

        header("Location: index.php");
        exit;
    }
}

// Jika username tidak ditemukan atau password salah
header("Location: login.php?error=Username atau password salah");
exit;
?>
