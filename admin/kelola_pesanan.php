<?php
include 'admin_header.php';

$pesan = "";

// --- LOGIKA UPDATE STATUS ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $id_pesanan = $_POST['id_pesanan'];
    $status_baru = $_POST['status_baru'];

    $sql_update = "UPDATE pesanan SET status_pesanan = ? WHERE id_pesanan = ?";
    $stmt = $conn->prepare($sql_update);
    $stmt->bind_param("si", $status_baru, $id_pesanan);
    
    if ($stmt->execute()) {
        $pesan = "<div class='bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm flex items-center'><i class='fas fa-check-circle mr-2'></i> Status pesanan #$id_pesanan berhasil diperbarui.</div>";
    } else {
        $pesan = "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm flex items-center'><i class='fas fa-exclamation-circle mr-2'></i> Gagal memperbarui status.</div>";
    }
}

// --- AMBIL DATA PESANAN (JOIN USERS) ---
$sql_pesanan = "
    SELECT p.*, u.nama_lengkap 
    FROM pesanan p 
    LEFT JOIN users u ON p.id_user = u.id_user
    ORDER BY p.tanggal_pesanan DESC
";
$semua_pesanan = $conn->query($sql_pesanan);
?>

<!-- JUDUL -->
<div class="mb-6 flex justify-between items-center">
    <div>
        <h1 class="text-2xl font-bold text-slate-800">Kelola Pesanan</h1>
        <p class="text-slate-500 text-sm">Pantau dan update status pesanan pelanggan.</p>
    </div>
    <!-- Tombol Refresh -->
    <a href="kelola_pesanan.php" class="bg-white border border-slate-300 text-slate-600 hover:text-blue-600 hover:border-blue-300 px-4 py-2 rounded-lg text-sm font-medium transition shadow-sm flex items-center gap-2">
        <i class="fas fa-sync-alt"></i> Refresh Data
    </a>
</div>

<!-- NOTIFIKASI -->
<?= $pesan ?>

<!-- TABEL DATA -->
<div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider border-b border-slate-200">
                    <th class="px-6 py-4 font-semibold">ID</th>
                    <th class="px-6 py-4 font-semibold">Pelanggan</th>
                    <th class="px-6 py-4 font-semibold">Tanggal</th>
                    <th class="px-6 py-4 font-semibold">Total</th>
                    <th class="px-6 py-4 font-semibold">Pembayaran</th>
                    <th class="px-6 py-4 font-semibold">Pengiriman</th> <!-- KOLOM BARU -->
                    <th class="px-6 py-4 font-semibold">Status</th>
                    <th class="px-6 py-4 font-semibold text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-sm">
                <?php if ($semua_pesanan->num_rows > 0): ?>
                    <?php while($pesanan = $semua_pesanan->fetch_assoc()): ?>
                        <tr class="hover:bg-slate-50 transition-colors">
                            
                            <!-- ID -->
                            <td class="px-6 py-4 font-medium text-slate-800">
                                #<?= $pesanan['id_pesanan'] ?>
                            </td>
                            
                            <!-- Pelanggan -->
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-bold text-xs">
                                        <?= strtoupper(substr($pesanan['nama_lengkap'] ?? 'U', 0, 1)) ?>
                                    </div>
                                    <span class="text-slate-700 font-medium"><?= htmlspecialchars($pesanan['nama_lengkap'] ?? 'User Dihapus') ?></span>
                                </div>
                            </td>

                            <!-- Tanggal -->
                            <td class="px-6 py-4 text-slate-500">
                                <?= date('d M Y', strtotime($pesanan['tanggal_pesanan'])) ?>
                                <br><span class="text-xs text-slate-400"><?= date('H:i', strtotime($pesanan['tanggal_pesanan'])) ?></span>
                            </td>

                            <!-- Total -->
                            <td class="px-6 py-4 font-bold text-slate-700">
                                Rp<?= number_format($pesanan['total_harga'], 0, ',', '.') ?>
                            </td>

                            <!-- Metode Pembayaran -->
                            <td class="px-6 py-4">
                                <span class="bg-slate-100 text-slate-600 px-2 py-1 rounded text-xs font-medium border border-slate-200 block w-max">
                                    <?= htmlspecialchars($pesanan['metode_pembayaran']) ?>
                                </span>
                            </td>

                            <!-- Jasa Pengiriman (KOLOM BARU) -->
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2 text-slate-700 font-medium text-xs">
                                    <i class="fas fa-truck text-blue-400"></i>
                                    <?= htmlspecialchars($pesanan['jasa_pengiriman'] ?? 'Reguler') ?>
                                </div>
                            </td>

                            <!-- Status & Form Update -->
                            <td class="px-6 py-4">
                                <form action="kelola_pesanan.php" method="POST" class="flex items-center gap-2">
                                    <input type="hidden" name="id_pesanan" value="<?= $pesanan['id_pesanan'] ?>">
                                    <div class="relative">
                                        <select name="status_baru" onchange="this.form.submit()" 
                                            class="appearance-none bg-white border border-slate-300 text-slate-700 py-1 pl-3 pr-8 rounded focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-xs font-medium cursor-pointer hover:bg-slate-50 transition shadow-sm">
                                            <option value="Menunggu Pembayaran" <?= $pesanan['status_pesanan'] == 'Menunggu Pembayaran' ? 'selected' : '' ?>>Menunggu</option>
                                            <option value="Diproses" <?= $pesanan['status_pesanan'] == 'Diproses' ? 'selected' : '' ?>>Diproses</option>
                                            <option value="Dikirim" <?= $pesanan['status_pesanan'] == 'Dikirim' ? 'selected' : '' ?>>Dikirim</option>
                                            <option value="Selesai" <?= $pesanan['status_pesanan'] == 'Selesai' ? 'selected' : '' ?>>Selesai</option>
                                            <option value="Dibatalkan" <?= $pesanan['status_pesanan'] == 'Dibatalkan' ? 'selected' : '' ?>>Batal</option>
                                        </select>
                                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-slate-500">
                                            <i class="fas fa-chevron-down text-[10px]"></i>
                                        </div>
                                    </div>
                                    <input type="hidden" name="update_status" value="1">
                                </form>
                            </td>

                            <!-- Aksi -->
                            <td class="px-6 py-4 text-center">
                                <div class="flex justify-center gap-2">
                                    <!-- Tombol Detail -->
                                    <a href="detail_pesanan.php?id=<?= $pesanan['id_pesanan'] ?>" class="text-blue-500 hover:text-blue-700 hover:bg-blue-50 p-2 rounded-full transition" title="Lihat Detail">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    
                                    <!-- Tombol Bukti Bayar (Jika Ada) -->
                                    <?php if (!empty($pesanan['bukti_pembayaran'])): ?>
                                        <a href="../uploads/bukti_pembayaran/<?= htmlspecialchars($pesanan['bukti_pembayaran']) ?>" target="_blank" class="text-purple-500 hover:text-purple-700 hover:bg-purple-50 p-2 rounded-full transition" title="Lihat Bukti Bayar">
                                            <i class="fas fa-receipt"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>

                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center text-slate-400">
                            <div class="flex flex-col items-center justify-center">
                                <i class="fas fa-inbox text-4xl mb-3 text-slate-300"></i>
                                <p>Belum ada pesanan masuk.</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Footer Tabel -->
    <div class="bg-slate-50 px-6 py-4 border-t border-slate-200 text-xs text-slate-500 flex justify-between items-center">
        <span>Menampilkan <?= $semua_pesanan->num_rows ?> data pesanan.</span>
        <!-- Pagination (Opsional, placeholder) -->
        <div class="flex gap-1">
            <button class="px-2 py-1 bg-white border border-slate-300 rounded hover:bg-slate-100 disabled:opacity-50" disabled>&lt;</button>
            <button class="px-2 py-1 bg-white border border-slate-300 rounded hover:bg-slate-100 disabled:opacity-50" disabled>&gt;</button>
        </div>
    </div>
</div>

<?php include 'admin_footer.php'; ?>