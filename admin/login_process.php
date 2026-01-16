<?php
session_start();
include '../db/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

$username = $_POST['username'];
$password = $_POST['password'];

if (empty($username) || empty($password)) {
    header('Location: login.php?error=gagal');
    exit;
}

$sql = "SELECT id_user, username, password, role FROM users WHERE username = ? AND role = 'admin'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();

    if (password_verify($password, $user['password'])) {
        // Buat sesi spesifik untuk 'admin'
        $_SESSION['admin'] = [
            'id' => $user['id_user'],
            'username' => $user['username']
        ];

        header("Location: index.php");
        exit;
    }
}

header("Location: login.php?error=gagal");
exit;
?>
