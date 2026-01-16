<?php
include 'admin_header.php';

// Validasi ID
if (!isset($_GET['id'])) {
    echo "<script>window.location='kelola_pesanan.php';</script>";
    exit;
}
$id_pesanan = (int)$_GET['id'];

// --- AMBIL DATA UTAMA PESANAN ---
// Menggunakan LEFT JOIN agar jika user dihapus, data pesanan tetap muncul
$sql_pesanan = "
    SELECT p.*, u.nama_lengkap AS nama_akun, u.username 
    FROM pesanan p 
    LEFT JOIN users u ON p.id_user = u.id_user
    WHERE p.id_pesanan = ?
";
$stmt = $conn->prepare($sql_pesanan);
$stmt->bind_param("i", $id_pesanan);
$stmt->execute();
$result_pesanan = $stmt->get_result();

if ($result_pesanan->num_rows == 0) {
    echo "<div class='bg-red-100 text-red-700 p-4 rounded'>Pesanan tidak ditemukan.</div>";
    include 'admin_footer.php';
    exit;
}
$pesanan = $result_pesanan->fetch_assoc();

// --- AMBIL DETAIL BARANG ---
$sql_detail = "
    SELECT dp.*, p.nama AS nama_produk, p.gambar 
    FROM detail_pesanan dp 
    JOIN produk p ON dp.id_produk = p.id 
    WHERE dp.id_pesanan = ?
";
$stmt_detail = $conn->prepare($sql_detail);
$stmt_detail->bind_param("i", $id_pesanan);
$stmt_detail->execute();
$items_pesanan = $stmt_detail->get_result();

// --- HELPER STATUS ---
function getStatusColor($status) {
    $status = strtolower($status);
    if (strpos($status, 'menunggu') !== false) return 'bg-yellow-100 text-yellow-700 border-yellow-200';
    if (strpos($status, 'proses') !== false) return 'bg-blue-100 text-blue-700 border-blue-200';
    if (strpos($status, 'dikirim') !== false) return 'bg-purple-100 text-purple-700 border-purple-200';
    if (strpos($status, 'selesai') !== false) return 'bg-green-100 text-green-700 border-green-200';
    return 'bg-red-100 text-red-700 border-red-200';
}
?>

<!-- HEADER PAGE -->
<div class="mb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
    <div>
        <div class="flex items-center gap-2 text-sm text-slate-500 mb-1">
            <a href="kelola_pesanan.php" class="hover:text-blue-600 transition">Kelola Pesanan</a>
            <i class="fas fa-chevron-right text-xs"></i>
            <span class="text-slate-800 font-medium">Detail</span>
        </div>
        <h1 class="text-2xl font-bold text-slate-800">
            Pesanan <span class="text-blue-600">#<?= $pesanan['id_pesanan'] ?></span>
        </h1>
    </div>
    
    <!-- Status Badge Besar -->
    <div class="px-4 py-2 rounded-lg border <?= getStatusColor($pesanan['status_pesanan']) ?> flex items-center gap-2 shadow-sm">
        <span class="font-bold uppercase tracking-wide text-sm"><?= htmlspecialchars($pesanan['status_pesanan']) ?></span>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    
    <!-- KOLOM KIRI: Daftar Item -->
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                <h2 class="font-bold text-slate-700 flex items-center gap-2">
                    <i class="fas fa-box text-blue-500"></i> Item Pesanan
                </h2>
                <span class="text-xs font-medium bg-white px-2 py-1 rounded border border-slate-200 text-slate-500"><?= $items_pesanan->num_rows ?> Barang</span>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-white text-slate-500 text-xs uppercase tracking-wider border-b border-slate-100">
                            <th class="px-6 py-3 font-semibold">Produk</th>
                            <th class="px-6 py-3 font-semibold text-center">Qty</th>
                            <th class="px-6 py-3 font-semibold text-right">Harga</th>
                            <th class="px-6 py-3 font-semibold text-right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 text-sm">
                        <?php while($item = $items_pesanan->fetch_assoc()): ?>
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 bg-slate-100 rounded-lg overflow-hidden border border-slate-200 flex-shrink-0">
                                        <img src="uploads/<?= htmlspecialchars($item['gambar']) ?>" alt="" class="w-full h-full object-cover">
                                    </div>
                                    <span class="font-medium text-slate-700 line-clamp-2"><?= htmlspecialchars($item['nama_produk']) ?></span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center text-slate-600">
                                <?= $item['kuantitas'] ?>
                            </td>
                            <td class="px-6 py-4 text-right text-slate-600">
                                Rp<?= number_format($item['harga_satuan'], 0, ',', '.') ?>
                            </td>
                            <td class="px-6 py-4 text-right font-bold text-slate-700">
                                Rp<?= number_format($item['subtotal'], 0, ',', '.') ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- KOLOM KANAN: Informasi Pelanggan & Pembayaran -->
    <div class="lg:col-span-1 space-y-6">
        
        <!-- Info Pelanggan & Pengiriman -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
            <h3 class="font-bold text-slate-800 mb-4 pb-2 border-b border-slate-100 flex items-center gap-2">
                <i class="fas fa-user-circle text-blue-600"></i> Informasi Pelanggan
            </h3>
            
            <div class="space-y-4 text-sm">
                <div>
                    <span class="text-xs text-slate-400 uppercase font-bold">Akun Pengguna</span>
                    <p class="font-medium text-slate-700"><?= htmlspecialchars($pesanan['nama_akun'] ?? 'Guest/Terhapus') ?></p>
                </div>
                
                <div>
                    <span class="text-xs text-slate-400 uppercase font-bold">Detail Pengiriman</span>
                    <div class="mt-1 p-3 bg-slate-50 rounded-lg border border-slate-100 text-slate-600 leading-relaxed">
                        <?= nl2br(htmlspecialchars($pesanan['alamat_pengiriman'])) ?>
                    </div>
                </div>

                <?php if(!empty($pesanan['catatan'])): ?>
                <div>
                    <span class="text-xs text-slate-400 uppercase font-bold">Catatan</span>
                    <p class="italic text-slate-500">"<?= htmlspecialchars($pesanan['catatan']) ?>"</p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Rincian Keuangan -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
            <h3 class="font-bold text-slate-800 mb-4 pb-2 border-b border-slate-100 flex items-center gap-2">
                <i class="fas fa-receipt text-green-600"></i> Pembayaran
            </h3>
            
            <div class="space-y-3 mb-6 text-sm">
                <div class="flex justify-between">
                    <span class="text-slate-500">Metode</span>
                    <span class="font-medium text-slate-700"><?= htmlspecialchars($pesanan['metode_pembayaran']) ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Kurir</span>
                    <span class="font-medium text-slate-700"><?= htmlspecialchars($pesanan['jasa_pengiriman'] ?? '-') ?></span>
                </div>
                
                <div class="pt-3 border-t border-dashed border-slate-200">
                    <div class="flex justify-between text-slate-500 mb-1">
                        <span>Ongkir</span>
                        <span>Rp<?= number_format($pesanan['ongkos_kirim'] ?? 0, 0, ',', '.') ?></span>
                    </div>
                    <div class="flex justify-between items-center mt-2">
                        <span class="font-bold text-slate-700">Total Bayar</span>
                        <span class="text-xl font-bold text-blue-600">Rp<?= number_format($pesanan['total_harga'], 0, ',', '.') ?></span>
                    </div>
                </div>
            </div>

            <!-- Bukti Pembayaran -->
            <div class="mb-4">
                <span class="text-xs text-slate-400 uppercase font-bold block mb-2">Bukti Pembayaran</span>
                <?php if (!empty($pesanan['bukti_pembayaran'])): ?>
                    <a href="../uploads/bukti_pembayaran/<?= htmlspecialchars($pesanan['bukti_pembayaran']) ?>" target="_blank" 
                       class="flex items-center justify-center gap-2 w-full py-2 bg-purple-50 text-purple-600 rounded-lg hover:bg-purple-100 transition border border-purple-200 font-medium text-sm">
                        <i class="fas fa-image"></i> Lihat Bukti
                    </a>
                <?php else: ?>
                    <div class="text-center py-2 bg-slate-50 text-slate-400 rounded-lg text-sm border border-slate-100">
                        Belum ada bukti
                    </div>
                <?php endif; ?>
            </div>

            <!-- Tombol Kembali -->
            <a href="kelola_pesanan.php" class="block w-full text-center py-2 text-slate-500 hover:text-slate-700 text-sm font-medium transition">
                <i class="fas fa-arrow-left mr-1"></i> Kembali
            </a>
        </div>

    </div>
</div>

<?php include 'admin_footer.php'; ?>