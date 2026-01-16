<?php
/*
File: proses_registrasi.php
Deskripsi: Memproses data dari formulir registrasi, memvalidasi,
           dan menyimpan pengguna baru ke database.
*/

session_start();
include 'db/koneksi.php'; // Hubungkan ke database

// 1. Pastikan permintaan adalah POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: registrasi.php');
    exit;
}

// 2. Ambil data dari formulir
$nama_lengkap = trim($_POST['nama_lengkap']);
$username = trim($_POST['username']);
$no_telp= trim($_POST['no_telp']);
$password = $_POST['password'];
$konfirmasi_password = $_POST['konfirmasi_password'];

// 3. Validasi dasar
if (empty($nama_lengkap) || empty($username) || empty($password) || empty($no_telp)){
    $_SESSION['error_message'] = "Semua kolom wajib diisi!";
    header('Location: registrasi.php');
    exit;
}

// 4. Periksa apakah password dan konfirmasi password cocok
if ($password !== $konfirmasi_password) {
    $_SESSION['error_message'] = "Password dan konfirmasi password tidak cocok!";
    header('Location: registrasi.php');
    exit;
}

// 5. Periksa apakah username sudah ada di database
// Menggunakan prepared statement untuk mencegah SQL Injection
$sql_check = "SELECT id_user FROM users WHERE username = ?";
$stmt_check = $conn->prepare($sql_check);
if ($stmt_check === false) {
    die("Error preparing statement: " . $conn->error);
}

$stmt_check->bind_param("s", $username);
$stmt_check->execute();
$stmt_check->store_result();

if ($stmt_check->num_rows > 0) {
    $_SESSION['error_message'] = "Username sudah digunakan. Silakan pilih username lain.";
    $stmt_check->close();
    header('Location: registrasi.php');
    exit;
}
$stmt_check->close();

// 6. Hash password sebelum disimpan
// Ini adalah langkah keamanan yang sangat penting
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// 7. Simpan pengguna baru ke database
$sql_insert = "INSERT INTO users (nama_lengkap, username, no_telp, password, role) VALUES (?, ?, ?, ?, 'user')";
$stmt_insert = $conn->prepare($sql_insert);
if ($stmt_insert === false) {
    die("Error preparing statement: " . $conn->error);
}

$stmt_insert->bind_param("ssss", $nama_lengkap, $username, $no_telp, $hashed_password);

if ($stmt_insert->execute()) {
    // Jika registrasi berhasil, arahkan ke halaman login dengan pesan sukses
    // Anda bisa menggunakan session untuk membawa pesan ini jika mau
    header('Location: login.php?status=registrasi_sukses');
    exit;
} else {
    // Jika terjadi error saat menyimpan
    $_SESSION['error_message'] = "Terjadi kesalahan pada server. Silakan coba lagi nanti.";
    header('Location: registrasi.php');
    exit;
}

$stmt_insert->close();
$conn->close();
?>
