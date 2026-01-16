<?php
session_start();
// Sesuaikan path ke file koneksi.php Anda (naik satu folder)
include '../db/koneksi.php';

// Keamanan: Cek sesi admin
if (!isset($_SESSION['admin'])) {
    header("Location: login.php"); 
    exit;
}

// Ambil data admin yang login
$admin_name = $_SESSION['admin']['username'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Bengkel Ida Jaya Oil</title>
    
    <!-- Favicon -->
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%232563eb'><path d='M22.7 19l-9.1-9.1c.9-2.3.4-5-1.5-6.9-2-2-5-2.4-7.4-1.3L9 6 6 9 1.6 4.7C.4 7.1.9 10.1 2.9 12.1c1.9 1.9 4.6 2.4 6.9 1.5l9.1 9.1c.4.4 1 .4 1.4 0l2.3-2.3c.5-.4.5-1.1.1-1.4z'/></svg>">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Poppins', sans-serif; }
        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #1e293b; }
        ::-webkit-scrollbar-thumb { background: #475569; border-radius: 3px; }
        ::-webkit-scrollbar-thumb:hover { background: #64748b; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800">

<div class="flex h-screen overflow-hidden">

    <!-- SIDEBAR (Navy Blue Theme) -->
    <aside class="w-64 bg-slate-900 text-white flex flex-col shadow-xl z-20 hidden md:flex transition-all duration-300">
        <!-- Logo Area -->
        <div class="h-16 flex items-center justify-center border-b border-slate-700 bg-slate-950">
            <div class="flex items-center gap-2 font-bold text-xl tracking-wider">
                <i class="fas fa-wrench text-blue-500"></i>
                <span>IDA JAYA <span class="text-blue-500">ADMIN</span></span>
            </div>
        </div>

        <!-- Menu -->
        <nav class="flex-grow py-6 px-3 space-y-2 overflow-y-auto">
            <p class="px-4 text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Utama</p>
            
            <a href="index.php" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-blue-600 hover:text-white transition-colors <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'bg-blue-600 text-white shadow-lg shadow-blue-900/50' : 'text-slate-300' ?>">
                <i class="fas fa-tachometer-alt w-5"></i> Dashboard
            </a>

            <p class="px-4 text-xs font-semibold text-slate-400 uppercase tracking-wider mt-6 mb-2">Manajemen</p>

            <a href="kelola_pesanan.php" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-blue-600 hover:text-white transition-colors <?= basename($_SERVER['PHP_SELF']) == 'kelola_pesanan.php' ? 'bg-blue-600 text-white' : 'text-slate-300' ?>">
                <i class="fas fa-shopping-cart w-5"></i> Pesanan
            </a>
            
            <a href="kelola_produk.php" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-blue-600 hover:text-white transition-colors <?= basename($_SERVER['PHP_SELF']) == 'kelola_produk.php' ? 'bg-blue-600 text-white' : 'text-slate-300' ?>">
                <i class="fas fa-box w-5"></i> Produk
            </a>
            
            <a href="kelola_pengguna.php" class="flex items-center gap-3 px-4 py-3 rounded-lg hover:bg-blue-600 hover:text-white transition-colors <?= basename($_SERVER['PHP_SELF']) == 'kelola_pengguna.php' ? 'bg-blue-600 text-white' : 'text-slate-300' ?>">
                <i class="fas fa-users w-5"></i> Pengguna
            </a>
        </nav>

        <!-- Logout Bottom -->
        <div class="p-4 border-t border-slate-700 bg-slate-950">
            <a href="logout.php" class="flex items-center gap-3 px-4 py-2 rounded-lg text-red-400 hover:bg-red-500/10 hover:text-red-300 transition-colors">
                <i class="fas fa-sign-out-alt w-5"></i> Logout
            </a>
        </div>
    </aside>

    <!-- CONTENT WRAPPER -->
    <div class="flex-grow flex flex-col h-screen overflow-hidden">
        
        <!-- TOP HEADER -->
        <header class="h-16 bg-white shadow-sm border-b border-slate-200 flex items-center justify-between px-6 z-10">
            <button class="md:hidden text-slate-600 hover:text-blue-600 text-xl">
                <i class="fas fa-bars"></i>
            </button>

            <div class="flex items-center gap-4 ml-auto">
                <div class="text-right hidden sm:block">
                    <p class="text-sm font-bold text-slate-700"><?= htmlspecialchars($admin_name) ?></p>
                    <p class="text-xs text-slate-500">Administrator</p>
                </div>
                <div class="w-10 h-10 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center font-bold text-lg border-2 border-blue-200">
                    <?= strtoupper(substr($admin_name, 0, 1)) ?>
                </div>
            </div>
        </header>

        <!-- MAIN CONTENT SCROLL AREA -->
        <main class="flex-grow overflow-y-auto p-6 bg-slate-50">