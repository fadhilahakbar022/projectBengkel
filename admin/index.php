<?php 
include 'admin_header.php'; 

// --- LOGIKA DATABASE TERBARU ---

// 1. Total Pendapatan (Status Selesai)
// Mengambil kolom total_harga dari tabel pesanan
$sql_pendapatan = "SELECT SUM(total_harga) AS total FROM pesanan WHERE status_pesanan = 'Selesai' OR status_pesanan = 'Sukses'";
$res_pendapatan = $conn->query($sql_pendapatan);
$pendapatan = $res_pendapatan->fetch_assoc()['total'] ?? 0;

// 2. Pesanan Baru (Status Diproses/Menunggu)
$sql_baru = "SELECT COUNT(id_pesanan) AS total FROM pesanan WHERE status_pesanan IN ('Diproses', 'Menunggu Pembayaran')";
$res_baru = $conn->query($sql_baru);
$pesanan_baru = $res_baru->fetch_assoc()['total'] ?? 0;

// 3. Jumlah Produk
$res_produk = $conn->query("SELECT COUNT(id) AS total FROM produk");
$jumlah_produk = $res_produk->fetch_assoc()['total'] ?? 0;

// 4. Jumlah Pelanggan (Dari tabel users, asumsikan role 'user' atau semua user)
// Cek dulu apakah tabel users punya kolom role, jika tidak hitung semua
$cek_kolom = $conn->query("SHOW COLUMNS FROM users LIKE 'role'");
if($cek_kolom->num_rows > 0) {
    $sql_pelanggan = "SELECT COUNT(id_user) AS total FROM users WHERE role = 'user'";
} else {
    $sql_pelanggan = "SELECT COUNT(id_user) AS total FROM users";
}
$res_pelanggan = $conn->query($sql_pelanggan);
$jumlah_pelanggan = $res_pelanggan->fetch_assoc()['total'] ?? 0;

// 5. Pesanan Terbaru (JOIN TABLE)
// Mengambil nama dari tabel users berdasarkan id_user di tabel pesanan
$pesanan_terbaru = [];
$sql_recent = "
    SELECT p.id_pesanan, u.nama_lengkap, p.tanggal_pesanan, p.total_harga, p.status_pesanan 
    FROM pesanan p
    LEFT JOIN users u ON p.id_user = u.id_user
    ORDER BY p.tanggal_pesanan DESC 
    LIMIT 5
";
$res_recent = $conn->query($sql_recent);
if ($res_recent && $res_recent->num_rows > 0) {
    while($row = $res_recent->fetch_assoc()) {
        $pesanan_terbaru[] = $row;
    }
}
?>

<!-- JUDUL HALAMAN -->
<div class="mb-8">
    <h1 class="text-2xl font-bold text-slate-800">Dashboard Overview</h1>
    <p class="text-slate-500 text-sm">Ringkasan aktivitas bengkel hari ini.</p>
</div>

<!-- KARTU STATISTIK (GRID) -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    
    <!-- Pendapatan -->
    <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-100 hover:shadow-md transition-shadow relative overflow-hidden">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Pendapatan</p>
                <h3 class="text-2xl font-bold text-slate-800">Rp<?= number_format($pendapatan, 0, ',', '.') ?></h3>
            </div>
            <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center text-green-600">
                <i class="fas fa-dollar-sign"></i>
            </div>
        </div>
        <div class="absolute bottom-0 left-0 w-full h-1 bg-green-500"></div>
    </div>

    <!-- Pesanan Baru -->
    <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-100 hover:shadow-md transition-shadow relative overflow-hidden">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Pesanan Baru</p>
                <h3 class="text-2xl font-bold text-slate-800"><?= $pesanan_baru ?></h3>
                <p class="text-xs text-blue-500 mt-1 font-medium">Perlu diproses</p>
            </div>
            <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600">
                <i class="fas fa-shopping-bag"></i>
            </div>
        </div>
        <div class="absolute bottom-0 left-0 w-full h-1 bg-blue-500"></div>
    </div>

    <!-- Produk -->
    <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-100 hover:shadow-md transition-shadow relative overflow-hidden">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Total Produk</p>
                <h3 class="text-2xl font-bold text-slate-800"><?= $jumlah_produk ?></h3>
            </div>
            <div class="w-10 h-10 rounded-full bg-orange-100 flex items-center justify-center text-orange-600">
                <i class="fas fa-box-open"></i>
            </div>
        </div>
        <div class="absolute bottom-0 left-0 w-full h-1 bg-orange-500"></div>
    </div>

    <!-- Pelanggan -->
    <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-100 hover:shadow-md transition-shadow relative overflow-hidden">
        <div class="flex justify-between items-start">
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Pelanggan</p>
                <h3 class="text-2xl font-bold text-slate-800"><?= $jumlah_pelanggan ?></h3>
            </div>
            <div class="w-10 h-10 rounded-full bg-purple-100 flex items-center justify-center text-purple-600">
                <i class="fas fa-users"></i>
            </div>
        </div>
        <div class="absolute bottom-0 left-0 w-full h-1 bg-purple-500"></div>
    </div>
</div>

<!-- TABEL PESANAN TERBARU -->
<div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
    <div class="px-6 py-4 border-b border-slate-200 flex justify-between items-center bg-slate-50">
        <h2 class="font-bold text-slate-700">Transaksi Terbaru</h2>
        <a href="kelola_pesanan.php" class="text-sm text-blue-600 hover:text-blue-800 font-medium">Lihat Semua</a>
    </div>
    
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider border-b border-slate-200">
                    <th class="px-6 py-3 font-semibold">ID Order</th>
                    <th class="px-6 py-3 font-semibold">Pelanggan</th>
                    <th class="px-6 py-3 font-semibold">Tanggal</th>
                    <th class="px-6 py-3 font-semibold">Total</th>
                    <th class="px-6 py-3 font-semibold">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-sm">
                <?php if (!empty($pesanan_terbaru)): ?>
                    <?php foreach ($pesanan_terbaru as $p): ?>
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4 font-medium text-slate-800">#<?= $p['id_pesanan'] ?></td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-full bg-slate-200 flex items-center justify-center text-xs font-bold text-slate-600">
                                        <?= substr($p['nama_lengkap'] ?? 'U', 0, 1) ?>
                                    </div>
                                    <span class="text-slate-600"><?= htmlspecialchars($p['nama_lengkap'] ?? 'User Terhapus') ?></span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-slate-500"><?= date('d M Y', strtotime($p['tanggal_pesanan'])) ?></td>
                            <td class="px-6 py-4 font-bold text-slate-700">Rp<?= number_format($p['total_harga'], 0, ',', '.') ?></td>
                            <td class="px-6 py-4">
                                <?php 
                                    $status = strtolower($p['status_pesanan']);
                                    $badgeColor = 'bg-gray-100 text-gray-600';
                                    if(strpos($status, 'proses') !== false) $badgeColor = 'bg-blue-100 text-blue-600';
                                    if(strpos($status, 'menunggu') !== false) $badgeColor = 'bg-yellow-100 text-yellow-600';
                                    if(strpos($status, 'selesai') !== false) $badgeColor = 'bg-green-100 text-green-600';
                                    if(strpos($status, 'batal') !== false) $badgeColor = 'bg-red-100 text-red-600';
                                ?>
                                <span class="px-2 py-1 rounded-full text-xs font-bold <?= $badgeColor ?>">
                                    <?= ucfirst($p['status_pesanan']) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-slate-400">Belum ada transaksi terbaru.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'admin_footer.php'; ?>