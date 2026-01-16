<?php
session_start();
include 'db/koneksi.php';

if (!isset($_SESSION['user']) && !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$id_user = isset($_SESSION['user']['id']) ? $_SESSION['user']['id'] : $_SESSION['user_id'];

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: pesanan_saya.php");
    exit;
}
$id_pesanan = (int)$_GET['id'];

// Ambil Data Pesanan
$stmt = $conn->prepare("SELECT * FROM pesanan WHERE id_pesanan = ? AND id_user = ?");
$stmt->bind_param("ii", $id_pesanan, $id_user);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: pesanan_saya.php");
    exit;
}
$pesanan = $result->fetch_assoc();

// Ambil Detail Barang
$stmt_detail = $conn->prepare("
    SELECT dp.*, p.nama AS nama_produk, p.gambar, p.harga AS harga_produk
    FROM detail_pesanan dp 
    JOIN produk p ON dp.id_produk = p.id 
    WHERE dp.id_pesanan = ?
");
$stmt_detail->bind_param("i", $id_pesanan);
$stmt_detail->execute();
$items_pesanan = $stmt_detail->get_result();

// --- HELPER STATUS ---
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
    <title>Detail Pesanan #<?= $pesanan['id_pesanan'] ?> - Bengkel Ida Jaya Oil</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%232563eb'><path d='M22.7 19l-9.1-9.1c.9-2.3.4-5-1.5-6.9-2-2-5-2.4-7.4-1.3L9 6 6 9 1.6 4.7C.4 7.1.9 10.1 2.9 12.1c1.9 1.9 4.6 2.4 6.9 1.5l9.1 9.1c.4.4 1 .4 1.4 0l2.3-2.3c.5-.4.5-1.1.1-1.4z'/></svg>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Poppins', sans-serif; background-color: #f8fafc; }</style>
</head>
<body class="flex flex-col min-h-screen">

    <!-- NAVBAR SEDERHANA -->
    <nav class="bg-white shadow-sm py-4">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center gap-2">
                    <i class="fas fa-wrench text-blue-600 text-2xl transform rotate-45"></i>
                    <span class="text-xl font-bold text-gray-800">IDA JAYA OIL</span>
                </div>
                <a href="pesanan_saya.php" class="text-gray-600 hover:text-blue-600 font-medium">Kembali</a>
            </div>
        </div>
    </nav>

    <main class="flex-grow container mx-auto px-4 py-8">
        
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Pesanan <span class="text-blue-600">#<?= $pesanan['id_pesanan'] ?></span></h1>
                <p class="text-sm text-gray-500 mt-1"><?= date('d F Y, H:i', strtotime($pesanan['tanggal_pesanan'])) ?> WIB</p>
            </div>
            <div class="px-4 py-2 rounded-lg border <?= getStatusBadge($pesanan['status_pesanan']) ?> flex items-center gap-2 shadow-sm">
                <i class="fas <?= getStatusIcon($pesanan['status_pesanan']) ?>"></i>
                <span class="font-bold uppercase tracking-wide text-sm"><?= htmlspecialchars($pesanan['status_pesanan']) ?></span>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- KOLOM KIRI: Daftar Item -->
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
                    <div class="p-4 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
                        <h2 class="font-bold text-gray-700">Daftar Produk</h2>
                    </div>
                    <div class="divide-y divide-gray-100">
                        <?php 
                        $items_pesanan->data_seek(0); 
                        while($item = $items_pesanan->fetch_assoc()): 
                            $harga_satuan = $item['harga_satuan'] ?? $item['harga_produk'] ?? 0;
                        ?>
                        <div class="p-4 flex gap-4">
                            <div class="w-16 h-16 bg-gray-100 rounded overflow-hidden flex-shrink-0">
                                <img src="admin/uploads/<?= htmlspecialchars($item['gambar']) ?>" class="w-full h-full object-cover" onerror="this.src='https://placehold.co/100'">
                            </div>
                            <div class="flex-grow">
                                <h3 class="font-semibold text-gray-800"><?= htmlspecialchars($item['nama_produk']) ?></h3>
                                <p class="text-sm text-gray-500"><?= $item['kuantitas'] ?> x Rp<?= number_format($harga_satuan, 0, ',', '.') ?></p>
                            </div>
                            <div class="text-right font-bold text-gray-800">Rp<?= number_format($item['subtotal'], 0, ',', '.') ?></div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>

            <!-- KOLOM KANAN: Info -->
            <div class="lg:col-span-1 space-y-6">
                
                <!-- Info Pengiriman -->
                <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-6">
                    <h3 class="font-bold text-gray-800 mb-4 pb-2 border-b"><i class="fas fa-truck text-blue-600 mr-2"></i> Pengiriman</h3>
                    <div class="text-sm text-gray-600 space-y-3">
                        <div class="bg-gray-50 p-3 rounded">
                            <span class="font-bold block text-gray-700 mb-1">Kurir:</span>
                            <?= !empty($pesanan['jasa_pengiriman']) ? htmlspecialchars($pesanan['jasa_pengiriman']) : 'Reguler' ?>
                        </div>
                        <div>
                            <span class="font-bold block text-gray-700 mb-1">Alamat:</span>
                            <?= nl2br(htmlspecialchars($pesanan['alamat_pengiriman'])); ?>
                        </div>
                    </div>
                </div>

                <!-- Rincian Pembayaran -->
                <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-6">
                    <h3 class="font-bold text-gray-800 mb-4 pb-2 border-b"><i class="fas fa-receipt text-blue-600 mr-2"></i> Rincian</h3>
                    <div class="space-y-3 mb-6 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-500">Metode</span>
                            <span class="font-medium"><?= htmlspecialchars($pesanan['metode_pembayaran']) ?></span>
                        </div>
                        
                        <!-- Hitung Subtotal Barang -->
                        <?php 
                            $ongkir = $pesanan['ongkos_kirim'] ?? 0;
                            $subtotal = $pesanan['total_harga'] - $ongkir;
                        ?>
                        
                        <div class="flex justify-between">
                            <span class="text-gray-500">Subtotal Barang</span>
                            <span class="font-medium">Rp<?= number_format($subtotal, 0, ',', '.') ?></span>
                        </div>
                        
                        <!-- Tampilkan Ongkir -->
                        <div class="flex justify-between text-green-600">
                            <span class="font-medium">Ongkos Kirim</span>
                            <span class="font-bold">+ Rp<?= number_format($ongkir, 0, ',', '.') ?></span>
                        </div>

                        <div class="flex justify-between items-end pt-2 border-t border-dashed border-gray-200">
                            <span class="text-gray-600 font-bold">Total Bayar</span>
                            <span class="text-xl font-bold text-blue-600">Rp<?= number_format($pesanan['total_harga'], 0, ',', '.') ?></span>
                        </div>
                    </div>

                    <a href="invoice.php?id=<?= $pesanan['id_pesanan'] ?>" target="_blank" class="block w-full bg-gray-900 text-white text-center py-3 rounded-lg font-bold shadow hover:bg-gray-700 transition">
                        <i class="fas fa-file-invoice mr-2"></i> Unduh Invoice
                    </a>
                </div>

            </div>
        </div>
    </main>

</body>
</html>