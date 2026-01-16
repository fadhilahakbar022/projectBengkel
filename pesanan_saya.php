<?php
session_start();
include 'db/koneksi.php'; 

// --- CEK LOGIN ---
if (!isset($_SESSION['user']) && !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$id_user = isset($_SESSION['user']['id']) ? $_SESSION['user']['id'] : $_SESSION['user_id'];

// Ambil Data Pesanan
$stmt = $conn->prepare("SELECT * FROM pesanan WHERE id_user = ? ORDER BY tanggal_pesanan DESC");
$stmt->bind_param("i", $id_user);
$stmt->execute();
$hasil_pesanan = $stmt->get_result();

// --- LOGIKA NAVBAR AKTIF ---
// Mendapatkan nama file script saat ini (misal: pesanan_saya.php)
$current_page = basename($_SERVER['PHP_SELF']);

function getStatusBadge($status) {
    $status = strtolower($status);
    switch ($status) {
        case 'menunggu pembayaran': case 'pending': return 'bg-yellow-100 text-yellow-800 border-yellow-200';
        case 'diproses': case 'dikemas': return 'bg-blue-100 text-blue-800 border-blue-200';
        case 'dikirim': return 'bg-purple-100 text-purple-800 border-purple-200';
        case 'selesai': case 'sukses': return 'bg-green-100 text-green-800 border-green-200';
        case 'batal': case 'dibatalkan': return 'bg-red-100 text-red-800 border-red-200';
        default: return 'bg-gray-100 text-gray-800 border-gray-200';
    }
}

function getStatusIcon($status) {
    $status = strtolower($status);
    if (strpos($status, 'menunggu') !== false) return 'fa-clock';
    if (strpos($status, 'proses') !== false) return 'fa-cog fa-spin';
    if (strpos($status, 'dikirim') !== false) return 'fa-truck';
    if (strpos($status, 'selesai') !== false) return 'fa-check-circle';
    if (strpos($status, 'batal') !== false) return 'fa-times-circle';
    return 'fa-info-circle';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Pesanan Saya - Bengkel Ida Jaya Oil</title>
    
    <!-- FAVICON -->
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%232563eb'><path d='M22.7 19l-9.1-9.1c.9-2.3.4-5-1.5-6.9-2-2-5-2.4-7.4-1.3L9 6 6 9 1.6 4.7C.4 7.1.9 10.1 2.9 12.1c1.9 1.9 4.6 2.4 6.9 1.5l9.1 9.1c.4.4 1 .4 1.4 0l2.3-2.3c.5-.4.5-1.1.1-1.4z'/></svg>">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #f8fafc; }
        
        /* Animasi garis bawah navbar yang menyesuaikan huruf */
        .nav-link { 
            position: relative; 
            display: inline-block; /* Penting: agar lebar elemen mengikuti teks */
        }
        .nav-link::after {
            content: ''; 
            position: absolute; 
            width: 0; 
            height: 2px; 
            bottom: -2px; /* Jarak garis ke teks lebih presisi */
            left: 0;
            background-color: #2563eb; 
            transition: width 0.3s ease-in-out;
        }
        .nav-link:hover::after { width: 100%; }
        .nav-link.active::after { width: 100%; }
    </style>
</head>
<body class="flex flex-col min-h-screen">

    <!-- NAVBAR INTERAKTIF -->
    <nav class="bg-white/95 backdrop-blur-md shadow-md fixed w-full z-50 transition-all duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <!-- Logo -->
                <div class="flex-shrink-0 flex items-center gap-2 group cursor-pointer" onclick="window.location.href='index.php'">
                    <i class="fas fa-wrench text-blue-600 text-2xl transform rotate-45 transition-transform group-hover:rotate-12"></i>
                    <span class="text-xl font-bold text-gray-800 tracking-wide group-hover:text-blue-600 transition-colors">
                        IDA JAYA <span class="text-blue-600 group-hover:text-gray-800">OIL</span>
                    </span>
                </div>

                <!-- Desktop Menu -->
                <div class="hidden md:flex space-x-8 items-center">
                    
                    <!-- Link Beranda -->
                    <a href="index.php" 
                       class="nav-link text-sm font-medium transition-colors duration-200 <?= ($current_page == 'index.php') ? 'text-blue-600 active font-bold' : 'text-gray-600 hover:text-blue-600' ?>">
                       Beranda
                    </a>

                    <!-- Link Produk -->
                    <a href="index.php#produk" 
                       class="nav-link text-sm font-medium transition-colors duration-200 text-gray-600 hover:text-blue-600">
                       Produk
                    </a>

                    <!-- Link Pesanan Saya (Aktif di halaman ini) -->
                    <a href="pesanan_saya.php" 
                       class="nav-link text-sm font-medium transition-colors duration-200 <?= ($current_page == 'pesanan_saya.php') ? 'text-blue-600 active font-bold' : 'text-gray-600 hover:text-blue-600' ?>">
                       Pesanan Saya
                    </a>
                    
                    <!-- Tombol Logout -->
                    <a href="logout.php" class="text-red-500 border border-red-200 px-4 py-1.5 rounded-full text-sm font-medium hover:bg-red-50 hover:border-red-300 transition-all shadow-sm hover:shadow">
                        Logout
                    </a>

                    <!-- Cart Icon -->
                    <a href="keranjang.php" class="relative text-gray-600 hover:text-blue-600 transition transform hover:scale-110">
                        <i class="fas fa-shopping-cart text-xl"></i>
                        <?php if(isset($_SESSION['keranjang']) && count($_SESSION['keranjang']) > 0): ?>
                            <span class="absolute -top-2 -right-2 bg-red-500 text-white text-[10px] font-bold w-5 h-5 flex items-center justify-center rounded-full border-2 border-white shadow-sm animate-bounce">
                                <?= array_sum($_SESSION['keranjang']) ?>
                            </span>
                        <?php endif; ?>
                    </a>
                </div>

                <!-- Mobile Menu Button -->
                <div class="flex items-center md:hidden">
                    <button onclick="document.getElementById('mobile-menu').classList.toggle('hidden')" class="text-gray-600 hover:text-blue-600 focus:outline-none p-2">
                        <i class="fas fa-bars text-2xl"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Menu Dropdown -->
        <div id="mobile-menu" class="hidden md:hidden bg-white border-t">
            <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3">
                <a href="index.php" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-blue-600 hover:bg-blue-50">Beranda</a>
                <a href="index.php#produk" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-blue-600 hover:bg-blue-50">Produk</a>
                <!-- Link Mobile Aktif -->
                <a href="pesanan_saya.php" class="block px-3 py-2 rounded-md text-base font-medium bg-blue-50 text-blue-600 border-l-4 border-blue-600">Pesanan Saya</a>
                <a href="logout.php" class="block px-3 py-2 rounded-md text-base font-medium text-red-600 hover:bg-red-50">Logout</a>
            </div>
        </div>
    </nav>

    <!-- MAIN CONTENT -->
    <main class="flex-grow container mx-auto px-4 py-24">
        
        <div class="flex flex-col md:flex-row justify-between items-end mb-8 border-b pb-4 gap-4 animate-fade-in-down">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">Riwayat Pesanan</h1>
                <p class="text-gray-500 text-sm mt-1">Pantau status pembelian suku cadang & oli Anda di sini.</p>
            </div>
            <a href="index.php#produk" class="bg-gray-800 text-white px-5 py-2 rounded-full text-sm hover:bg-blue-600 hover:shadow-lg transition-all transform hover:-translate-y-0.5">
                <i class="fas fa-shopping-bag mr-2"></i> Belanja Lagi
            </a>
        </div>

        <?php if ($hasil_pesanan->num_rows > 0): ?>
            <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-100 transition-all hover:shadow-xl">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 text-gray-600 text-sm uppercase tracking-wider">
                                <th class="p-4 font-semibold border-b">ID Pesanan</th>
                                <th class="p-4 font-semibold border-b">Tanggal</th>
                                <th class="p-4 font-semibold border-b">Total</th>
                                <th class="p-4 font-semibold border-b">Status</th>
                                <th class="p-4 font-semibold border-b text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php while($pesanan = $hasil_pesanan->fetch_assoc()): ?>
                                <tr class="hover:bg-blue-50/50 transition-colors duration-200 group">
                                    <td class="p-4 font-medium text-gray-900 group-hover:text-blue-600 transition-colors">
                                        #<?= $pesanan['id_pesanan'] ?>
                                    </td>
                                    <td class="p-4 text-gray-600 text-sm">
                                        <div class="flex items-center gap-2">
                                            <i class="far fa-calendar-alt text-gray-400"></i>
                                            <?= date('d M Y', strtotime($pesanan['tanggal_pesanan'])) ?>
                                        </div>
                                    </td>
                                    <td class="p-4 font-bold text-gray-800">
                                        Rp<?= number_format($pesanan['total_harga'], 0, ',', '.') ?>
                                    </td>
                                    <td class="p-4">
                                        <?php $statusClass = getStatusBadge($pesanan['status_pesanan']); $iconClass = getStatusIcon($pesanan['status_pesanan']); ?>
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold border <?= $statusClass ?>">
                                            <i class="fas <?= $iconClass ?>"></i>
                                            <?= ucfirst($pesanan['status_pesanan']) ?>
                                        </span>
                                    </td>
                                    <td class="p-4 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <a href="detail_pesanan_user.php?id=<?= $pesanan['id_pesanan'] ?>" class="bg-white border border-blue-200 text-blue-600 w-8 h-8 rounded-full flex items-center justify-center hover:bg-blue-600 hover:text-white transition shadow-sm" title="Lihat Detail">
                                                <i class="fas fa-eye text-xs"></i>
                                            </a>
                                            <a href="invoice.php?id=<?= $pesanan['id_pesanan'] ?>" target="_blank" class="bg-white border border-green-200 text-green-600 w-8 h-8 rounded-full flex items-center justify-center hover:bg-green-600 hover:text-white transition shadow-sm" title="Unduh Invoice">
                                                <i class="fas fa-file-invoice text-xs"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-xl shadow-sm border border-dashed border-gray-300 py-16 text-center animate-fade-in-up">
                <div class="inline-flex items-center justify-center w-24 h-24 bg-blue-50 rounded-full mb-6">
                    <i class="fas fa-receipt text-blue-300 text-4xl"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-800 mb-2">Belum Ada Pesanan</h2>
                <p class="text-gray-500 max-w-md mx-auto mb-8">
                    Sepertinya Anda belum pernah melakukan transaksi.
                </p>
                <a href="index.php" class="inline-flex items-center bg-blue-600 text-white px-8 py-3 rounded-full font-bold shadow-lg hover:shadow-xl hover:bg-blue-700 transition">
                    <i class="fas fa-search mr-2"></i> Cari Produk
                </a>
            </div>
        <?php endif; ?>

    </main>

    <!-- FOOTER -->
    <footer class="bg-slate-900 text-white pt-12 pb-6 mt-auto">
        <div class="container mx-auto px-4 text-center text-gray-400 text-sm">
            &copy; <?= date("Y") ?> Bengkel Ida Jaya Oil. All Rights Reserved.
        </div>
    </footer>

</body>
</html>