<?php
/*
File: logout.php
Deskripsi: Menghancurkan sesi pengguna dan mengalihkannya ke halaman login.
*/

session_start();

// Hapus semua variabel sesi.
$_SESSION = array();

// Hancurkan sesi. Ini akan menghapus semua data sesi dari server.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

// Alihkan pengguna kembali ke halaman login dengan pesan sukses.
header("Location: login.php?status=logout_success");
exit;
?>
