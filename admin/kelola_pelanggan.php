<?php
// Memanggil header admin
include 'admin_header.php';

$pesan = ""; // Variabel untuk notifikasi

// --- LOGIKA UNTUK PROSES FORM (TAMBAH/EDIT) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_pengguna'])) {
    $id_user = $_POST['id_user'];
    $nama_lengkap = trim($_POST['nama_lengkap']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $role = $_POST['role'];

    // Validasi dasar
    if (empty($nama_lengkap) || empty($username) || empty($role)) {
        $pesan = "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded shadow-sm'>Nama lengkap, username, dan peran tidak boleh kosong.</div>";
    } else {
        if (empty($id_user)) {
            // Mode TAMBAH PENGGUNA BARU
            if (empty($password)) {
                $pesan = "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded shadow-sm'>Password wajib diisi untuk pengguna baru.</div>";
            } else {
                // Cek apakah username sudah ada
                $stmt_check = $conn->prepare("SELECT id FROM users WHERE username = ?");
                $stmt_check->bind_param("s", $username);
                $stmt_check->execute();
                $stmt_check->store_result();
                if ($stmt_check->num_rows > 0) {
                    $pesan = "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded shadow-sm'>Username sudah digunakan.</div>";
                } else {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $sql = "INSERT INTO users (nama_lengkap, username, password, role) VALUES (?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ssss", $nama_lengkap, $username, $hashed_password, $role);
                    if ($stmt->execute()) {
                        $pesan = "<div class='bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded shadow-sm'><i class='fas fa-check-circle mr-2'></i> Pengguna baru berhasil ditambahkan.</div>";
                    } else {
                        $pesan = "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded shadow-sm'>Gagal menambahkan pengguna.</div>";
                    }
                }
            }
        } else {
            // Mode EDIT PENGGUNA
            // Keamanan: Admin tidak bisa mengubah role dirinya sendiri menjadi user (harus tetap admin)
            if ($id_user == $_SESSION['user_id'] && $role != $_SESSION['role']) {
                 $pesan = "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded shadow-sm'>Anda tidak dapat mengubah peran (role) Anda sendiri.</div>";
            } else {
                if (!empty($password)) {
                    // Jika password diisi, update password
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $sql = "UPDATE users SET nama_lengkap=?, username=?, password=?, role=? WHERE id=?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ssssi", $nama_lengkap, $username, $hashed_password, $role, $id_user);
                } else {
                    // Jika password kosong, jangan update password
                    $sql = "UPDATE users SET nama_lengkap=?, username=?, role=? WHERE id=?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("sssi", $nama_lengkap, $username, $role, $id_user);
                }

                if ($stmt->execute()) {
                    $pesan = "<div class='bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded shadow-sm'><i class='fas fa-check-circle mr-2'></i> Data pengguna berhasil diperbarui.</div>";
                } else {
                    $pesan = "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded shadow-sm'>Gagal memperbarui data pengguna. Username mungkin sudah ada.</div>";
                }
            }
        }
    }
}


// --- LOGIKA UNTUK HAPUS PENGGUNA ---
if (isset($_GET['action']) && $_GET['action'] == 'hapus' && isset($_GET['id'])) {
    $id_hapus = (int)$_GET['id'];
    $id_admin_sekarang = isset($_SESSION['user']['id']) ? $_SESSION['user']['id'] : $_SESSION['user_id'];

    // Keamanan: Admin tidak bisa menghapus akunnya sendiri
    if ($id_hapus === $id_admin_sekarang) {
        $pesan = "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded shadow-sm'>Anda tidak dapat menghapus akun Anda sendiri.</div>";
    } else {
        // Hapus pengguna dari database
        $stmt_hapus = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt_hapus->bind_param("i", $id_hapus);
        
        if ($stmt_hapus->execute()) {
            $pesan = "<div class='bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded shadow-sm'><i class='fas fa-trash-alt mr-2'></i> Pengguna berhasil dihapus.</div>";
        } else {
            // Error ini bisa terjadi jika pengguna memiliki data terkait di tabel lain (misal: pesanan)
            $pesan = "<div class='bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded shadow-sm'>Gagal menghapus pengguna. Mungkin pengguna ini memiliki riwayat pesanan.</div>";
        }
        $stmt_hapus->close();
    }
}

// Ambil semua data pengguna untuk ditampilkan di tabel
// Asumsi tabel users memiliki kolom 'created_at'. Jika tidak, hapus ORDER BY created_at
$semua_pengguna = $conn->query("SELECT id, username, nama_lengkap, role FROM users ORDER BY role, username ASC");
?>

<!-- HEADER PAGE -->
<div class="mb-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
    <div>
        <h1 class="text-2xl font-bold text-slate-800">Kelola Pengguna</h1>
        <p class="text-slate-500 text-sm">Manajemen akun admin dan pelanggan.</p>
    </div>
    
    <!-- Tombol Scroll ke Form -->
    <button onclick="document.getElementById('form-pengguna').scrollIntoView({behavior: 'smooth'})" 
            class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-lg shadow-lg shadow-blue-500/30 transition flex items-center gap-2 text-sm font-bold">
        <i class="fas fa-user-plus"></i> Tambah Pengguna
    </button>
</div>

<!-- NOTIFIKASI -->
<?= $pesan ?>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-8 items-start">
    
    <!-- KOLOM KIRI: FORM INPUT (Sticky di Desktop) -->
    <div class="xl:col-span-1 xl:sticky xl:top-6">
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6" id="form-pengguna">
            <h2 class="text-lg font-bold text-slate-700 mb-4 pb-2 border-b border-slate-100 flex justify-between items-center">
                <span id="form-title">Tambah Pengguna</span>
                <span id="mode-badge" class="text-xs bg-blue-100 text-blue-600 px-2 py-1 rounded hidden">Mode Edit</span>
            </h2>
            
            <form action="kelola_pengguna.php" method="POST" class="space-y-4">
                <input type="hidden" name="id_user" id="id_user">
                
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Nama Lengkap</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-id-card text-slate-400"></i>
                        </div>
                        <input type="text" id="nama_lengkap" name="nama_lengkap" required 
                               class="w-full pl-10 bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none transition" placeholder="Nama Lengkap">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Username</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-user text-slate-400"></i>
                        </div>
                        <input type="text" id="username" name="username" required 
                               class="w-full pl-10 bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none transition" placeholder="Username unik">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-lock text-slate-400"></i>
                        </div>
                        <input type="password" id="password" name="password" 
                               class="w-full pl-10 bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none transition" placeholder="Password (Min. 6 karakter)">
                    </div>
                    <p class="text-[10px] text-slate-400 mt-1 ml-1">*Kosongkan jika tidak ingin mengubah password saat edit.</p>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Peran (Role)</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-user-tag text-slate-400"></i>
                        </div>
                        <select name="role" id="role" required class="w-full pl-10 bg-slate-50 border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none transition cursor-pointer">
                            <option value="user">User (Pelanggan)</option>
                            <option value="admin">Admin (Pengelola)</option>
                        </select>
                    </div>
                </div>

                <div class="pt-2 flex gap-2">
                    <button type="submit" name="submit_pengguna" id="btn-submit" class="flex-grow bg-slate-800 hover:bg-slate-900 text-white font-bold py-2.5 rounded-lg transition shadow-lg text-sm">
                        <i class="fas fa-save mr-2"></i> Simpan Pengguna
                    </button>
                    <button type="button" id="btn-cancel" onclick="resetForm()" class="hidden px-4 py-2.5 bg-gray-200 text-gray-600 font-bold rounded-lg hover:bg-gray-300 transition text-sm">
                        Batal
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- KOLOM KANAN: TABEL DAFTAR PENGGUNA -->
    <div class="xl:col-span-2">
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                <h3 class="font-bold text-slate-700">Daftar Pengguna Terdaftar</h3>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-white text-slate-500 text-xs uppercase tracking-wider border-b border-slate-100">
                            <th class="px-6 py-3 font-semibold">User Info</th>
                            <th class="px-6 py-3 font-semibold">Role</th>
                            <th class="px-6 py-3 font-semibold text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm">
                        <?php if ($semua_pengguna->num_rows > 0): ?>
                            <?php while($pengguna = $semua_pengguna->fetch_assoc()): ?>
                            <tr class="hover:bg-slate-50 transition">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-500 font-bold border border-slate-200">
                                            <?= strtoupper(substr($pengguna['nama_lengkap'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <p class="font-bold text-slate-700"><?= htmlspecialchars($pengguna['nama_lengkap']) ?></p>
                                            <p class="text-xs text-slate-500">@<?= htmlspecialchars($pengguna['username']) ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if ($pengguna['role'] === 'admin'): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                            <i class="fas fa-shield-alt mr-1"></i> Admin
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            <i class="fas fa-user mr-1"></i> User
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex justify-center gap-2">
                                        <button onclick="editPengguna(
                                            '<?= $pengguna['id'] ?>',
                                            '<?= htmlspecialchars(addslashes($pengguna['nama_lengkap'])) ?>',
                                            '<?= htmlspecialchars(addslashes($pengguna['username'])) ?>',
                                            '<?= $pengguna['role'] ?>'
                                        )" class="w-8 h-8 rounded-full bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white transition flex items-center justify-center shadow-sm" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        
                                        <?php 
                                        $current_admin_id = isset($_SESSION['user']['id']) ? $_SESSION['user']['id'] : $_SESSION['user_id'];
                                        if ((int)$pengguna['id'] !== (int)$current_admin_id): 
                                        ?>
                                            <a href="kelola_pengguna.php?action=hapus&id=<?= $pengguna['id'] ?>" 
                                               class="w-8 h-8 rounded-full bg-red-50 text-red-600 hover:bg-red-600 hover:text-white transition flex items-center justify-center shadow-sm" 
                                               title="Hapus"
                                               onclick="return confirm('Yakin ingin menghapus pengguna ini?')">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                        <?php else: ?>
                                            <!-- Disabled Delete Button for Self -->
                                            <span class="w-8 h-8 rounded-full bg-gray-100 text-gray-300 flex items-center justify-center cursor-not-allowed" title="Tidak bisa hapus diri sendiri">
                                                <i class="fas fa-trash-alt"></i>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="px-6 py-12 text-center text-slate-400">
                                    <div class="flex flex-col items-center">
                                        <i class="fas fa-users-slash text-4xl mb-3 text-slate-300"></i>
                                        <p>Belum ada pengguna terdaftar.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    function editPengguna(id, nama_lengkap, username, role) {
        // Isi Form
        document.getElementById('id_user').value = id;
        document.getElementById('nama_lengkap').value = nama_lengkap;
        document.getElementById('username').value = username;
        document.getElementById('role').value = role;

        // Ubah Tampilan UI Form
        document.getElementById('form-title').innerText = 'Edit Pengguna';
        document.getElementById('btn-submit').innerHTML = '<i class="fas fa-save mr-2"></i> Simpan Perubahan';
        document.getElementById('btn-submit').classList.remove('bg-slate-800');
        document.getElementById('btn-submit').classList.add('bg-blue-600', 'hover:bg-blue-700');
        
        document.getElementById('btn-cancel').classList.remove('hidden');
        document.getElementById('mode-badge').classList.remove('hidden');

        // Scroll ke form (untuk mobile)
        document.getElementById('form-pengguna').scrollIntoView({ behavior: 'smooth' });
    }

    function resetForm() {
        // Reset Form
        document.querySelector('form').reset();
        document.getElementById('id_user').value = '';
        
        // Reset UI Form
        document.getElementById('form-title').innerText = 'Tambah Pengguna';
        document.getElementById('btn-submit').innerHTML = '<i class="fas fa-save mr-2"></i> Simpan Pengguna';
        document.getElementById('btn-submit').classList.add('bg-slate-800');
        document.getElementById('btn-submit').classList.remove('bg-blue-600', 'hover:bg-blue-700');
        
        document.getElementById('btn-cancel').classList.add('hidden');
        document.getElementById('mode-badge').classList.add('hidden');
    }
</script>

<?php 
// Memanggil footer admin
include 'admin_footer.php'; 
?>