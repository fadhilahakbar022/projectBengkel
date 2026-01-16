<?php
/*
File: koneksi.php
Deskripsi: Membuat koneksi ke database MySQL.
*/

// --- Konfigurasi Database ---
// Ganti nilai-nilai ini dengan detail koneksi database Anda.
$host = "localhost";       // Biasanya "localhost" atau alamat IP server database Anda
$username_db = "root";     // Username untuk mengakses database
$password_db = "";         // Password untuk username tersebut
$nama_db = "bengkel_dbv2";   // Nama database yang akan digunakan

// --- Membuat Koneksi ---
// Membuat objek koneksi baru menggunakan MySQLi.
// @ die() akan menghentikan eksekusi skrip dan menampilkan pesan error jika koneksi gagal.
$conn = new mysqli($host, $username_db, $password_db, $nama_db);

// --- Memeriksa Koneksi ---
// Periksa apakah ada error saat mencoba terhubung.
if ($conn->connect_error) {
    // Jika ada error, hentikan skrip dan tampilkan pesan yang jelas.
    // Pesan ini sebaiknya tidak ditampilkan di lingkungan produksi untuk alasan keamanan.
    die("Koneksi ke database gagal: " . $conn->connect_error);
}

// --- Mengatur Karakter Set (Opsional tapi Direkomendasikan) ---
// Mengatur karakter set ke UTF-8 untuk mendukung berbagai macam karakter
// dan menghindari masalah encoding.
if (!$conn->set_charset("utf8")) {
    // Jika gagal, bisa dicatat sebagai log error.
    // Untuk saat ini, kita biarkan saja karena bukan error kritis.
    // printf("Error loading character set utf8: %s\n", $conn->error);
}

// --- Pesan Sukses (Hanya untuk Debugging) ---
// Baris ini bisa diaktifkan saat pengembangan untuk memastikan koneksi berhasil.
// echo "Koneksi ke database berhasil!";

// Koneksi siap digunakan di file lain yang menyertakan file ini.
// Tidak perlu menutup koneksi di sini jika akan digunakan di seluruh aplikasi.
// Koneksi akan otomatis ditutup saat skrip PHP selesai dieksekusi.
?>
