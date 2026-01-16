<?php
session_start();

// Ambil ID pesanan terakhir dari sesi
$last_order_id = $_SESSION['last_order_id'] ?? null;

// Hapus sesi agar tidak muncul lagi saat refresh, 
// tapi kita simpan dulu di variabel lokal $last_order_id untuk ditampilkan di HTML bawah
unset($_SESSION['last_order_id']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan Berhasil - Bengkel Ida Jaya Oil</title>
    
    <!-- FAVICON (Konsisten) -->
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%232563eb'><path d='M22.7 19l-9.1-9.1c.9-2.3.4-5-1.5-6.9-2-2-5-2.4-7.4-1.3L9 6 6 9 1.6 4.7C.4 7.1.9 10.1 2.9 12.1c1.9 1.9 4.6 2.4 6.9 1.5l9.1 9.1c.4.4 1 .4 1.4 0l2.3-2.3c.5-.4.5-1.1.1-1.4z'/></svg>">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #f8fafc; }
        
        /* Animasi Pop-up untuk Icon */
        @keyframes scaleUp {
            0% { transform: scale(0); opacity: 0; }
            60% { transform: scale(1.1); opacity: 1; }
            100% { transform: scale(1); }
        }
        .success-icon-anim {
            animation: scaleUp 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen bg-slate-50 p-4">

    <!-- Card Container -->
    <div class="bg-white rounded-3xl shadow-2xl p-8 md:p-12 max-w-md w-full text-center border border-gray-100 relative overflow-hidden">
        
        <!-- Background Decoration (Optional) -->
        <div class="absolute top-0 left-0 w-full h-2 bg-gradient-to-r from-blue-500 to-cyan-500"></div>

        <!-- Icon Sukses -->
        <div class="flex justify-center mb-6">
            <div class="w-24 h-24 bg-green-100 rounded-full flex items-center justify-center success-icon-anim shadow-sm">
                <i class="fas fa-check text-5xl text-green-600"></i>
            </div>
        </div>

        <!-- Heading -->
        <h1 class="text-2xl md:text-3xl font-bold text-gray-800 mb-2">Pesanan Berhasil!</h1>
        <p class="text-gray-500 mb-8 leading-relaxed">
            Terima kasih telah berbelanja di <span class="font-semibold text-blue-600">Ida Jaya Oil</span>.<br>
            Pesanan Anda sedang kami proses.
        </p>

        <!-- Order ID Info (Jika Ada) -->
        <?php if ($last_order_id): ?>
            <div class="bg-blue-50 border border-blue-100 rounded-lg p-4 mb-8">
                <p class="text-sm text-gray-500 mb-1">ID Pesanan Anda:</p>
                <p class="text-xl font-bold text-blue-700 tracking-wider">#<?= htmlspecialchars($last_order_id) ?></p>
            </div>

            <!-- Tombol Aksi -->
            <div class="space-y-3">
                <!-- Download Invoice -->
                <a href="invoice.php?id=<?= $last_order_id ?>" class="block w-full bg-blue-600 text-white font-bold py-3 px-4 rounded-xl shadow-lg hover:bg-blue-700 hover:shadow-xl transition-all transform hover:-translate-y-0.5 flex items-center justify-center gap-2">
                    <i class="fas fa-file-invoice"></i> Unduh Invoice
                </a>

                <!-- Cek Status Pesanan -->
                <a href="pesanan_saya.php" class="block w-full bg-white border border-gray-200 text-gray-700 font-semibold py-3 px-4 rounded-xl hover:bg-gray-50 hover:text-blue-600 transition-colors flex items-center justify-center gap-2">
                    <i class="fas fa-box-open"></i> Lihat Status Pesanan
                </a>
            </div>
        <?php else: ?>
            <!-- Jika halaman di-refresh dan ID hilang -->
            <div class="bg-yellow-50 text-yellow-800 p-3 rounded-lg text-sm mb-6">
                Informasi pesanan sudah tersimpan. Silakan cek riwayat pesanan Anda.
            </div>
            <a href="pesanan_saya.php" class="block w-full bg-blue-600 text-white font-bold py-3 px-4 rounded-xl shadow hover:bg-blue-700 transition">
                Lihat Riwayat Pesanan
            </a>
        <?php endif; ?>

        <!-- Kembali ke Beranda -->
        <div class="mt-8 pt-6 border-t border-gray-100">
            <a href="index.php" class="text-gray-400 hover:text-blue-600 text-sm font-medium transition-colors flex items-center justify-center gap-1 group">
                <i class="fas fa-arrow-left text-xs transition-transform group-hover:-translate-x-1"></i> Kembali ke Beranda
            </a>
        </div>

    </div>

    <!-- Efek Confetti Sederhana (Opsional) -->
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.5.1/dist/confetti.browser.min.js"></script>
    <script>
        // Jalankan efek confetti saat halaman dimuat
        window.onload = function() {
            confetti({
                particleCount: 100,
                spread: 70,
                origin: { y: 0.6 },
                colors: ['#2563eb', '#22c55e', '#facc15'] // Warna Biru, Hijau, Kuning
            });
        };
    </script>
</body>
</html>