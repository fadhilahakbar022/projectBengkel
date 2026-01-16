<?php
// Memanggil header admin
include 'admin_header.php';

// --- LOGIKA UNTUK PROSES FORM (TAMBAH/EDIT/HAPUS) ---

$pesan = ""; // Variabel untuk menyimpan pesan notifikasi

// PROSES TAMBAH ATAU EDIT PRODUK
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Ambil data dari form
    $nama = $_POST['nama'];
    $deskripsi = $_POST['deskripsi'];
    $harga = $_POST['harga'];
    $stok = $_POST['stok'];
    $id_produk = $_POST['id_produk']; // Untuk mode edit
    $kategori = $_POST['kategori'] ?? 'Spare Part'; 

    // --- Proses Upload Gambar ---
    $nama_gambar = $_POST['gambar_lama']; // Default ke gambar lama jika ada
    
    if (isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
        $target_dir = "uploads/";
        
        // Pastikan folder uploads ada
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $nama_file_asli = basename($_FILES["gambar"]["name"]);
        $tipe_file = strtolower(pathinfo($nama_file_asli, PATHINFO_EXTENSION));
        
        // Buat nama file unik
        $nama_gambar_unik = uniqid() . '.' . $tipe_file;
        $target_file = $target_dir . $nama_gambar_unik;

        // Validasi file
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (in_array($tipe_file, $allowed_types) && $_FILES["gambar"]["size"] < 2000000) { 
            if (move_uploaded_file($_FILES["gambar"]["tmp_name"], $target_file)) {
                $nama_gambar = $nama_gambar_unik;
                // Hapus gambar lama jika mode edit
                if (!empty($id_produk) && !empty($_POST['gambar_lama'])) {
                    if(file_exists($target_dir . $_POST['gambar_lama'])){
                        unlink($target_dir . $_POST['gambar_lama']);
                    }
                }
            } else {
                $pesan = "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded shadow-sm'>Gagal mengupload gambar.</div>";
            }
        } else {
            $pesan = "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded shadow-sm'>File tidak valid (Format: JPG/PNG/WEBP, Max 2MB).</div>";
        }
    }

    if (empty($pesan)) { 
        if (empty($id_produk)) {
            // Mode TAMBAH
            $sql = "INSERT INTO produk (nama, deskripsi, harga, stok, gambar, kategori) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            // d = double (harga), i = integer (stok), s = string
            $stmt->bind_param("ssdiss", $nama, $deskripsi, $harga, $stok, $nama_gambar, $kategori);
            
            if ($stmt->execute()) {
                $pesan = "<div class='bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded shadow-sm'><i class='fas fa-check-circle mr-2'></i> Produk berhasil ditambahkan.</div>";
            } else {
                $pesan = "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded shadow-sm'>Gagal menambahkan produk: " . $conn->error . "</div>";
            }
        } else {
            // Mode EDIT
            $sql = "UPDATE produk SET nama=?, deskripsi=?, harga=?, stok=?, gambar=?, kategori=? WHERE id=?";
            $stmt = $conn->prepare($sql);
            // Perhatikan urutan dan tipe datanya: s-s-d-i-s-s-i
            $stmt->bind_param("ssdissi", $nama, $deskripsi, $harga, $stok, $nama_gambar, $kategori, $id_produk);
            
            if ($stmt->execute()) {
                $pesan = "<div class='bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded shadow-sm'><i class='fas fa-check-circle mr-2'></i> Produk berhasil diperbarui.</div>";
            } else {
                $pesan = "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded shadow-sm'>Gagal memperbarui produk.</div>";
            }
        }
    }
}

// PROSES HAPUS PRODUK
if (isset($_GET['action']) && $_GET['action'] == 'hapus' && isset($_GET['id'])) {
    $id_hapus = $_GET['id'];
    
    // Ambil info gambar dulu
    $stmt_get_img = $conn->prepare("SELECT gambar FROM produk WHERE id=?");
    $stmt_get_img->bind_param("i", $id_hapus);
    $stmt_get_img->execute();
    $result_img = $stmt_get_img->get_result();
    if($row_img = $result_img->fetch_assoc()){
        $gambar_hapus = $row_img['gambar'];
        if(!empty($gambar_hapus) && file_exists("uploads/" . $gambar_hapus)){
            unlink("uploads/" . $gambar_hapus);
        }
    }

    $stmt_hapus = $conn->prepare("DELETE FROM produk WHERE id=?");
    $stmt_hapus->bind_param("i", $id_hapus);
    if ($stmt_hapus->execute()) {
        $pesan = "<div class='bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded shadow-sm'><i class='fas fa-trash-alt mr-2'></i> Produk berhasil dihapus.</div>";
    } else {
        $pesan = "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded shadow-sm'>Gagal menghapus produk.</div>";
    }
}

// Ambil semua produk
$semua_produk = $conn->query("SELECT * FROM produk ORDER BY id DESC");
?>

<style>
    /* CSS Khusus untuk menghilangkan panah up/down (spinner) pada input number */
    input[type=number]::-webkit-inner-spin-button, 
    input[type=number]::-webkit-outer-spin-button { 
        -webkit-appearance: none; 
        margin: 0; 
    }
    input[type=number] {
        -moz-appearance: textfield; /* Firefox */
    }
</style>

<!-- HEADER PAGE -->
<div class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-800">Manajemen Produk</h1>
        <p class="text-slate-500 text-sm">Kelola katalog, harga, dan stok barang.</p>
    </div>
    
    <button onclick="resetForm(); document.getElementById('form-produk').scrollIntoView({behavior: 'smooth'})" 
            class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-lg shadow-lg shadow-blue-500/30 transition flex items-center gap-2 text-sm font-bold">
        <i class="fas fa-plus"></i> Tambah Produk Baru
    </button>
</div>

<?= $pesan ?>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-8 items-start">
    
    <!-- KOLOM KIRI: FORM INPUT -->
    <div class="xl:col-span-1 xl:sticky xl:top-6">
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6" id="form-produk">
            <h2 class="text-lg font-bold text-slate-700 mb-4 pb-2 border-b border-slate-100 flex justify-between items-center">
                <span id="form-title">Tambah Produk</span>
                <span id="mode-badge" class="text-xs bg-orange-100 text-orange-600 px-2 py-1 rounded hidden font-bold">MODE EDIT</span>
            </h2>
            
            <form action="kelola_produk.php" method="POST" enctype="multipart/form-data" class="space-y-4">
                <input type="hidden" name="id_produk" id="id_produk">
                <input type="hidden" name="gambar_lama" id="gambar_lama">
                
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Nama Produk</label>
                    <input type="text" id="nama" name="nama" required 
                           class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none transition">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Kategori</label>
                    <select id="kategori" name="kategori" class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none transition cursor-pointer">
                        <option value="Oli">Oli Mesin & Gear</option>
                        <option value="Ban">Ban Motor</option>
                        <option value="Spare Part">Spare Part & Alat</option>
                        <option value="Aksesoris">Aksesoris</option>
                        <option value="Lainnya">Lainnya</option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Harga (Rp)</label>
                        <input type="number" id="harga" name="harga" required min="0" step="0.01"
                               class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none transition">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Stok</label>
                        <!-- Input Stok dengan Tombol +/- -->
                        <div class="flex items-center border border-slate-300 rounded-lg bg-slate-50 overflow-hidden">
                            <button type="button" onclick="updateStok(-1)" class="w-10 h-10 flex-shrink-0 flex items-center justify-center text-slate-500 hover:bg-slate-200 hover:text-slate-700 transition border-r border-slate-300 focus:outline-none">
                                <i class="fas fa-minus text-xs"></i>
                            </button>
                            <input type="number" id="stok" name="stok" required min="0" value="0"
                                   class="w-full bg-transparent border-none text-center text-sm font-medium focus:ring-0 focus:outline-none py-2 text-slate-700">
                            <button type="button" onclick="updateStok(1)" class="w-10 h-10 flex-shrink-0 flex items-center justify-center text-slate-500 hover:bg-slate-200 hover:text-slate-700 transition border-l border-slate-300 focus:outline-none">
                                <i class="fas fa-plus text-xs"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Deskripsi</label>
                    <textarea id="deskripsi" name="deskripsi" rows="3" 
                              class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none transition"></textarea>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Gambar</label>
                    <div class="flex items-center gap-4">
                        <div id="preview-container" class="hidden w-16 h-16 bg-slate-100 rounded-lg overflow-hidden border border-slate-200">
                            <img id="img-preview" src="" class="w-full h-full object-cover">
                        </div>
                        <input type="file" id="gambar" name="gambar" accept="image/*" class="text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer">
                    </div>
                </div>

                <div class="pt-2 flex gap-2">
                    <button type="submit" id="btn-submit" class="flex-grow bg-slate-800 hover:bg-slate-900 text-white font-bold py-2.5 rounded-lg transition shadow-lg text-sm">
                        <i class="fas fa-save mr-2"></i> Simpan Produk
                    </button>
                    <button type="button" id="btn-cancel" onclick="resetForm()" class="hidden px-4 py-2.5 bg-gray-200 text-gray-600 font-bold rounded-lg hover:bg-gray-300 transition text-sm">
                        Batal
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- KOLOM KANAN: TABEL DAFTAR PRODUK -->
    <div class="xl:col-span-2">
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                <h3 class="font-bold text-slate-700">Daftar Produk Tersedia</h3>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-white text-slate-500 text-xs uppercase tracking-wider border-b border-slate-100">
                            <th class="px-6 py-3 font-semibold">Produk</th>
                            <th class="px-6 py-3 font-semibold">Kategori</th>
                            <th class="px-6 py-3 font-semibold text-right">Harga</th>
                            <th class="px-6 py-3 font-semibold text-center">Stok</th>
                            <th class="px-6 py-3 font-semibold text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm">
                        <?php if ($semua_produk->num_rows > 0): ?>
                            <?php while($produk = $semua_produk->fetch_assoc()): 
                                // Bersihkan deskripsi dari karakter aneh/enter agar tidak merusak JS
                                $deskripsi_js = htmlspecialchars(str_replace(array("\r", "\n"), " ", addslashes($produk['deskripsi'])));
                            ?>
                            <tr class="hover:bg-slate-50 transition">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-12 h-12 bg-slate-100 rounded-lg overflow-hidden border border-slate-200 flex-shrink-0">
                                            <img src="uploads/<?= htmlspecialchars($produk['gambar']) ?>" alt="" class="w-full h-full object-cover" onerror="this.src='https://placehold.co/100?text=IMG'">
                                        </div>
                                        <span class="font-medium text-slate-700 line-clamp-2"><?= htmlspecialchars($produk['nama']) ?></span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-block bg-slate-100 text-slate-600 px-2 py-1 rounded text-xs border border-slate-200">
                                        <?= htmlspecialchars($produk['kategori'] ?? '-') ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right font-semibold text-slate-700">
                                    Rp<?= number_format($produk['harga'], 0, ',', '.') ?>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <?php if($produk['stok'] > 5): ?>
                                        <span class="text-green-600 font-bold"><?= $produk['stok'] ?></span>
                                    <?php elseif($produk['stok'] > 0): ?>
                                        <span class="text-orange-500 font-bold"><?= $produk['stok'] ?></span>
                                    <?php else: ?>
                                        <span class="bg-red-100 text-red-600 px-2 py-1 rounded text-xs font-bold">Habis</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex justify-center gap-2">
                                        <!-- Tombol Edit yang memanggil JS -->
                                        <button onclick="editProduk(
                                            '<?= $produk['id'] ?>',
                                            '<?= htmlspecialchars(addslashes($produk['nama'])) ?>',
                                            '<?= $deskripsi_js ?>',
                                            '<?= $produk['harga'] ?>',
                                            '<?= $produk['stok'] ?>',
                                            '<?= htmlspecialchars(addslashes($produk['gambar'])) ?>',
                                            '<?= htmlspecialchars($produk['kategori'] ?? 'Spare Part') ?>'
                                        )" class="w-8 h-8 rounded-full bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white transition flex items-center justify-center shadow-sm" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        
                                        <a href="kelola_produk.php?action=hapus&id=<?= $produk['id'] ?>" 
                                           class="w-8 h-8 rounded-full bg-red-50 text-red-600 hover:bg-red-600 hover:text-white transition flex items-center justify-center shadow-sm" 
                                           title="Hapus"
                                           onclick="return confirm('Yakin ingin menghapus produk ini?')">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-slate-400">Belum ada produk.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    // Fungsi untuk menambah/mengurangi stok dengan tombol
    function updateStok(change) {
        const input = document.getElementById('stok');
        let val = parseInt(input.value) || 0;
        val += change;
        if (val < 0) val = 0;
        input.value = val;
    }

    function editProduk(id, nama, deskripsi, harga, stok, gambar, kategori) {
        // Reset dulu biar bersih
        resetForm();

        // Isi Form dengan Data
        document.getElementById('id_produk').value = id;
        document.getElementById('nama').value = nama;
        document.getElementById('deskripsi').value = deskripsi;
        document.getElementById('harga').value = harga;
        document.getElementById('stok').value = stok;
        document.getElementById('gambar_lama').value = gambar;
        document.getElementById('kategori').value = kategori;

        // Tampilkan Preview Gambar Lama
        if (gambar) {
            document.getElementById('preview-container').classList.remove('hidden');
            document.getElementById('img-preview').src = 'uploads/' + gambar;
        }

        // Ubah Tampilan UI Form ke Mode Edit
        document.getElementById('form-title').innerText = 'Edit Produk #' + id;
        document.getElementById('btn-submit').innerHTML = '<i class="fas fa-save mr-2"></i> Simpan Perubahan';
        document.getElementById('btn-submit').className = "flex-grow bg-blue-600 hover:bg-blue-700 text-white font-bold py-2.5 rounded-lg transition shadow-lg text-sm";
        
        document.getElementById('btn-cancel').classList.remove('hidden');
        document.getElementById('mode-badge').classList.remove('hidden');

        // Scroll ke form
        document.getElementById('form-produk').scrollIntoView({ behavior: 'smooth' });
    }

    function resetForm() {
        // Reset Form Values
        document.querySelector('form').reset();
        document.getElementById('id_produk').value = '';
        document.getElementById('gambar_lama').value = '';
        document.getElementById('stok').value = '0'; // Default stok
        document.getElementById('preview-container').classList.add('hidden');
        
        // Reset UI Form ke Mode Tambah
        document.getElementById('form-title').innerText = 'Tambah Produk';
        document.getElementById('btn-submit').innerHTML = '<i class="fas fa-plus mr-2"></i> Tambah Produk';
        document.getElementById('btn-submit').className = "flex-grow bg-slate-800 hover:bg-slate-900 text-white font-bold py-2.5 rounded-lg transition shadow-lg text-sm";
        
        document.getElementById('btn-cancel').classList.add('hidden');
        document.getElementById('mode-badge').classList.add('hidden');
    }
</script>

<?php include 'admin_footer.php'; ?>