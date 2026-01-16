<?php
session_start();
include 'db/koneksi.php'; 

// --- LOGIKA PENCARIAN & FILTER ---
$search_query = "";
$selected_kategori = "";
$sql_condition = "";
$params = [];
$types = "";

// 1. Cek Pencarian Teks
if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $search_query = trim($_GET['search']);
    $sql_condition .= " AND (nama LIKE ? OR deskripsi LIKE ?)";
    $term = "%" . $search_query . "%";
    $params[] = $term;
    $params[] = $term;
    $types .= "ss";
}

// 2. Cek Filter Kategori
if (isset($_GET['kategori']) && !empty($_GET['kategori'])) {
    $selected_kategori = $_GET['kategori'];
    $sql_condition .= " AND kategori = ?";
    $params[] = $selected_kategori;
    $types .= "s";
}

// --- LOGIKA TAMBAH KERANJANG (AJAX) ---
if (isset($_POST['action']) && $_POST['action'] == 'tambah_keranjang') {
    header('Content-Type: application/json');
    $id_produk = (int)$_POST['id_produk'];
    
    if (!isset($_SESSION['keranjang'])) {
        $_SESSION['keranjang'] = [];
    }
    
    if (isset($_SESSION['keranjang'][$id_produk])) {
        $_SESSION['keranjang'][$id_produk]++;
    } else {
        $_SESSION['keranjang'][$id_produk] = 1;
    }
    
    // Simpan ke database jika user login
    if (isset($_SESSION['user']) || isset($_SESSION['user_id'])) {
        $user_id = isset($_SESSION['user']['id']) ? $_SESSION['user']['id'] : $_SESSION['user_id']; 
        $kuantitas_baru = $_SESSION['keranjang'][$id_produk];
        
        $stmt_save = $conn->prepare("INSERT INTO keranjang_tersimpan (id_user, id_produk, kuantitas) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE kuantitas = ?");
        if($stmt_save){
            $stmt_save->bind_param("iiii", $user_id, $id_produk, $kuantitas_baru, $kuantitas_baru);
            $stmt_save->execute();
            $stmt_save->close();
        }
    }
    
    echo json_encode([
        'sukses' => true,
        'pesan' => 'Produk berhasil ditambahkan ke keranjang!',
        'total_keranjang' => array_sum($_SESSION['keranjang'])
    ]);
    exit;
}

// --- QUERY PRODUK UTAMA ---
$sql = "SELECT * FROM produk WHERE stok > 0" . $sql_condition . " ORDER BY nama ASC";
$stmt_produk = $conn->prepare($sql);

if (!empty($params)) {
    $bind_names[] = $types;
    for ($i=0; $i<count($params);$i++) {
        $bind_name = 'bind' . $i;
        $$bind_name = $params[$i];
        $bind_names[] = &$$bind_name;
    }
    call_user_func_array(array($stmt_produk, 'bind_param'), $bind_names);
}

$stmt_produk->execute();
$result = $stmt_produk->get_result();
$produk = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $produk[] = $row;
    }
}
$stmt_produk->close();

$total_item_keranjang = isset($_SESSION['keranjang']) ? array_sum($_SESSION['keranjang']) : 0;

// Menentukan halaman aktif untuk Navbar
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth"> <!-- Smooth Scroll Aktif -->
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ida Jaya Oil - Pusat Suku Cadang & Oli</title>
    
    <!-- FAVICON -->
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%232563eb'><path d='M22.7 19l-9.1-9.1c.9-2.3.4-5-1.5-6.9-2-2-5-2.4-7.4-1.3L9 6 6 9 1.6 4.7C.4 7.1.9 10.1 2.9 12.1c1.9 1.9 4.6 2.4 6.9 1.5l9.1 9.1c.4.4 1 .4 1.4 0l2.3-2.3c.5-.4.5-1.1.1-1.4z'/></svg>">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #f3f4f6; }
        .hero-bg {
            background: linear-gradient(rgba(15, 23, 42, 0.8), rgba(15, 23, 42, 0.8)), url('https://images.unsplash.com/photo-1486262715619-67b85e0b08d3?ixlib=rb-1.2.1&auto=format&fit=crop&w=1950&q=80');
            background-size: cover;
            background-position: center;
        }
        
        /* Animasi garis bawah navbar */
        .nav-link { 
            position: relative; 
            display: inline-block;
        }
        .nav-link::after {
            content: ''; 
            position: absolute; 
            width: 0; 
            height: 1px; 
            bottom: -1px; 
            left: 0;
            background-color: #2563eb; 
            transition: width 0.3s ease-in-out;
        }
        .nav-link:hover::after { width: 100%; }
        
        /* Class active khusus JS */
        .nav-link.active-js::after { width: 100%; }
        .nav-link.active-js { color: #2563eb; font-weight: 700; }
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
                    <!-- Update href ke #beranda agar smooth scroll berjalan -->
                    <a href="#beranda" id="nav-beranda" onclick="setActiveNav('beranda')"
                       class="nav-link text-sm font-medium transition-colors duration-200 text-blue-600 font-bold active-js">
                       Beranda
                    </a>
                    <a href="#produk" id="nav-produk" onclick="setActiveNav('produk')"
                       class="nav-link text-sm font-medium transition-colors duration-200 text-gray-600 hover:text-blue-600">
                       Produk
                    </a>
                    
                    <?php if (isset($_SESSION['user']) || isset($_SESSION['user_id'])): ?>
                        <a href="pesanan_saya.php" 
                           class="nav-link text-sm font-medium transition-colors duration-200 text-gray-600 hover:text-blue-600">
                           Pesanan Saya
                        </a>
                        <a href="logout.php" class="text-red-500 border border-red-200 px-4 py-1.5 rounded-full text-sm font-medium hover:bg-red-50 hover:border-red-300 transition-all shadow-sm hover:shadow">
                            Logout
                        </a>
                    <?php else: ?>
                        <a href="login.php" class="nav-link text-sm font-medium transition-colors duration-200 text-blue-600 hover:text-blue-800">Masuk</a>
                        <a href="registrasi.php" class="bg-blue-600 text-white px-5 py-2 rounded-full text-sm font-medium hover:bg-blue-700 transition shadow-lg shadow-blue-500/30">Daftar</a>
                    <?php endif; ?>

                    <!-- Cart Icon -->
                    <a href="keranjang.php" class="relative text-gray-600 hover:text-blue-600 transition transform hover:scale-110">
                        <i class="fas fa-shopping-cart text-xl"></i>
                        <span id="cart-count" class="absolute -top-2 -right-2 bg-red-500 text-white text-[10px] font-bold w-5 h-5 flex items-center justify-center rounded-full border-2 border-white shadow-sm <?= $total_item_keranjang > 0 ? 'animate-bounce' : '' ?>">
                            <?= $total_item_keranjang ?>
                        </span>
                    </a>
                </div>

                <!-- Mobile Menu Button -->
                <div class="flex items-center md:hidden">
                    <a href="keranjang.php" class="relative text-gray-600 mr-4">
                        <i class="fas fa-shopping-cart text-xl"></i>
                        <span id="cart-count-mobile" class="absolute -top-2 -right-2 bg-red-500 text-white text-xs font-bold w-5 h-5 flex items-center justify-center rounded-full">
                            <?= $total_item_keranjang ?>
                        </span>
                    </a>
                    <button onclick="document.getElementById('mobile-menu').classList.toggle('hidden')" class="text-gray-600 hover:text-blue-600 focus:outline-none p-2">
                        <i class="fas fa-bars text-2xl"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Menu Dropdown -->
        <div id="mobile-menu" class="hidden md:hidden bg-white border-t pt-16">
            <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3">
                <a href="#beranda" onclick="setActiveNav('beranda'); document.getElementById('mobile-menu').classList.add('hidden')" class="block px-3 py-2 rounded-md text-base font-medium bg-blue-50 text-blue-600 border-l-4 border-blue-600">Beranda</a>
                <a href="#produk" onclick="setActiveNav('produk'); document.getElementById('mobile-menu').classList.add('hidden')" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-blue-600 hover:bg-blue-50">Produk</a>
                <?php if (isset($_SESSION['user']) || isset($_SESSION['user_id'])): ?>
                    <a href="pesanan_saya.php" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-blue-600 hover:bg-gray-50">Pesanan Saya</a>
                    <a href="logout.php" class="block px-3 py-2 rounded-md text-base font-medium text-red-600 hover:bg-red-50">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="block px-3 py-2 rounded-md text-base font-medium text-blue-600 hover:bg-blue-50">Masuk</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- NOTIFIKASI POPUP -->
    <div id="notification-popup" class="fixed top-24 right-5 z-50 transform translate-x-full transition-transform duration-300">
        <div class="bg-white border-l-4 border-green-500 text-gray-800 p-4 rounded shadow-xl flex items-center gap-3">
            <div class="bg-green-100 p-2 rounded-full text-green-600">
                <i class="fas fa-check"></i>
            </div>
            <div>
                <p class="font-bold text-sm">Sukses!</p>
                <p class="text-xs text-gray-500" id="notif-message">Item ditambahkan ke keranjang.</p>
            </div>
        </div>
    </div>

    <!-- HERO SECTION (ID BERANDA DITAMBAHKAN) -->
    <header class="hero-bg h-[550px] flex items-center justify-center text-center px-4 pt-16" id="beranda">
        <div class="max-w-4xl text-white w-full">
            <h1 class="text-4xl md:text-6xl font-bold mb-4 drop-shadow-lg leading-tight">
                Solusi Terbaik untuk <span class="text-blue-400">Kendaraan Anda</span>
            </h1>
            <p class="text-lg md:text-xl text-gray-200 mb-8 font-light">
                Temukan Oli, Ban, dan Spare Part berkualitas dengan mudah.
            </p>
            
            <!-- FORM PENCARIAN -->
            <div class="bg-white p-2 rounded-full shadow-2xl w-full max-w-3xl mx-auto transform transition-all hover:scale-[1.02]">
                <form action="index.php" method="GET" class="flex w-full items-center">
                    <?php if(!empty($selected_kategori)): ?>
                        <input type="hidden" name="kategori" value="<?= htmlspecialchars($selected_kategori) ?>">
                    <?php endif; ?>

                    <input type="text" name="search" 
                        class="flex-grow w-full px-6 py-3 text-gray-700 focus:outline-none placeholder-gray-400 rounded-l-full" 
                        placeholder="Cari nama barang di sini..." 
                        value="<?= htmlspecialchars($search_query) ?>">
                    
                    <button type="submit" class="bg-gradient-to-r from-blue-600 to-cyan-600 text-white px-8 py-3 rounded-full font-bold hover:shadow-lg transition flex items-center justify-center gap-2 transform hover:scale-105 active:scale-95 m-1">
                        <i class="fas fa-search"></i> <span class="hidden sm:inline">Cari</span>
                    </button>
                </form>
            </div>

            <!-- CHIPS KATEGORI -->
            <div class="mt-6 flex flex-wrap justify-center gap-3">
                <a href="index.php" 
                   class="px-5 py-2 rounded-full border border-white/30 backdrop-blur-sm transition-all duration-300 <?= empty($selected_kategori) ? 'bg-blue-600 text-white border-blue-600 shadow-lg scale-105' : 'bg-white/10 text-white hover:bg-white/20' ?>">
                   <i class="fas fa-th-large mr-2"></i>Semua
                </a>
                <a href="index.php?kategori=Oli" 
                   class="px-5 py-2 rounded-full border border-white/30 backdrop-blur-sm transition-all duration-300 <?= $selected_kategori == 'Oli' ? 'bg-blue-600 text-white border-blue-600 shadow-lg scale-105' : 'bg-white/10 text-white hover:bg-white/20' ?>">
                   <i class="fas fa-oil-can mr-2"></i>Oli
                </a>
                <a href="index.php?kategori=Ban" 
                   class="px-5 py-2 rounded-full border border-white/30 backdrop-blur-sm transition-all duration-300 <?= $selected_kategori == 'Ban' ? 'bg-blue-600 text-white border-blue-600 shadow-lg scale-105' : 'bg-white/10 text-white hover:bg-white/20' ?>">
                   <i class="fas fa-ring mr-2"></i>Ban
                </a>
                <a href="index.php?kategori=Spare Part" 
                   class="px-5 py-2 rounded-full border border-white/30 backdrop-blur-sm transition-all duration-300 <?= $selected_kategori == 'Spare Part' ? 'bg-blue-600 text-white border-blue-600 shadow-lg scale-105' : 'bg-white/10 text-white hover:bg-white/20' ?>">
                   <i class="fas fa-cogs mr-2"></i>Spare Part
                </a>
            </div>
        </div>
    </header>

    <!-- INFO BAR -->
    <div class="bg-white shadow-sm py-6">
        <div class="max-w-7xl mx-auto px-4 grid grid-cols-1 md:grid-cols-3 gap-6 text-center">
            <div class="flex items-center justify-center gap-3">
                <i class="fas fa-check-shield text-blue-600 text-3xl"></i>
                <div><h4 class="font-bold text-gray-800">Produk Asli</h4><p class="text-xs text-gray-500">Jaminan kualitas original</p></div>
            </div>
            <div class="flex items-center justify-center gap-3">
                <i class="fas fa-shipping-fast text-blue-600 text-3xl"></i>
                <div><h4 class="font-bold text-gray-800">Pengiriman Cepat</h4><p class="text-xs text-gray-500">Siap antar ke lokasi</p></div>
            </div>
            <div class="flex items-center justify-center gap-3">
                <i class="fas fa-headset text-blue-600 text-3xl"></i>
                <div><h4 class="font-bold text-gray-800">Layanan Ramah</h4><p class="text-xs text-gray-500">Bantuan 24/7</p></div>
            </div>
        </div>
    </div>

    <!-- MAIN CONTENT (Produk) -->
    <main class="flex-grow container mx-auto px-4 py-12" id="produk">
        <div class="flex flex-col md:flex-row items-center justify-between mb-8 border-b pb-4 gap-4">
            <h2 class="text-3xl font-bold text-gray-800">
                <?php 
                if (!empty($search_query) || !empty($selected_kategori)) {
                    echo 'Hasil Pencarian';
                    if(!empty($selected_kategori)) echo ' <span class="text-gray-500 text-lg mx-1">|</span> Kategori: <span class="text-blue-600">' . htmlspecialchars($selected_kategori) . '</span>';
                    if(!empty($search_query)) echo ' <span class="text-gray-500 text-lg mx-1">|</span> Keyword: "<span class="text-blue-600">' . htmlspecialchars($search_query) . '</span>"';
                } else {
                    echo 'Katalog <span class="text-blue-600">Produk</span>';
                }
                ?>
            </h2>
            <?php if (!empty($search_query) || !empty($selected_kategori)): ?>
                <a href="index.php" class="text-sm text-red-500 hover:bg-red-50 px-4 py-2 rounded-full border border-red-200 transition font-medium">
                    <i class="fas fa-times mr-1"></i> Reset Filter
                </a>
            <?php endif; ?>
        </div>
        
        <!-- Grid Produk -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-8">
            <?php if (!empty($produk)): ?>
                <?php foreach ($produk as $p): ?>
                <div class="bg-white rounded-2xl shadow-lg overflow-hidden group hover:shadow-2xl transition-all duration-300 border border-gray-100 flex flex-col h-full">
                    
                    <!-- Gambar Produk -->
                    <div class="relative h-48 overflow-hidden bg-gray-100">
                        <a href="detail_produk.php?id=<?= $p['id'] ?>">
                            <img src="admin/uploads/<?= htmlspecialchars($p['gambar'] ?? '') ?>" 
                                 alt="<?= htmlspecialchars($p['nama']) ?>" 
                                 class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110"
                                 onerror="this.src='https://placehold.co/400x300/e2e8f0/64748b?text=No+Image'">
                        </a>
                        <div class="absolute top-2 right-2 bg-blue-600 text-white text-xs px-2 py-1 rounded-md shadow">
                            Stok: <?= $p['stok'] ?>
                        </div>
                        <?php if(isset($p['kategori']) && !empty($p['kategori'])): ?>
                            <div class="absolute top-2 left-2 bg-gray-800/80 backdrop-blur-sm text-white text-xs px-2 py-1 rounded-md">
                                <?= htmlspecialchars($p['kategori']) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Info Produk -->
                    <div class="p-5 flex flex-col flex-grow">
                        <div class="flex-grow">
                            <a href="detail_produk.php?id=<?= $p['id'] ?>" class="hover:text-blue-600 transition">
                                <h3 class="font-bold text-lg text-gray-800 mb-1 line-clamp-2"><?= htmlspecialchars($p['nama']) ?></h3>
                            </a>
                            <p class="text-gray-500 text-sm mb-3 line-clamp-2"><?= htmlspecialchars($p['deskripsi']) ?></p>
                        </div>
                        
                        <div class="mt-4 pt-4 border-t border-gray-100">
                            <div class="flex items-center justify-between mb-4">
                                <span class="text-gray-400 text-xs">Harga Satuan</span>
                                <span class="text-xl font-bold text-blue-700">Rp<?= number_format($p['harga'], 0, ',', '.') ?></span>
                            </div>
                            
                            <button onclick="tambahKeKeranjang(<?= $p['id'] ?>, this)" 
                                class="w-full bg-gray-800 hover:bg-blue-600 text-white font-medium py-2.5 rounded-lg transition-colors duration-300 flex items-center justify-center gap-2 group-hover:bg-gradient-to-r group-hover:from-blue-600 group-hover:to-cyan-600">
                                <i class="fas fa-cart-plus"></i> Tambah ke Keranjang
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-span-full py-16 text-center bg-white rounded-xl shadow-sm border border-dashed border-gray-300">
                    <div class="inline-flex items-center justify-center w-20 h-20 bg-gray-100 rounded-full mb-4">
                        <i class="fas fa-search text-gray-400 text-3xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-2">Produk Tidak Ditemukan</h3>
                    <p class="text-gray-500 mb-6">Maaf, kami tidak menemukan produk untuk filter:<br>
                        <?php if(!empty($selected_kategori)) echo "Kategori: <strong class='text-blue-600'>$selected_kategori</strong> "; ?>
                        <?php if(!empty($search_query)) echo "Kata Kunci: <strong class='text-blue-600'>$search_query</strong>"; ?>
                    </p>
                    <a href="index.php" class="bg-blue-600 text-white px-6 py-2 rounded-full font-medium hover:bg-blue-700 transition">Lihat Semua Produk</a>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- FOOTER LENGKAP -->
    <footer class="bg-slate-900 text-white pt-12 pb-6">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-8 border-b border-gray-700 pb-8">
                <!-- Tentang -->
                <div>
                    <h3 class="text-2xl font-bold mb-4">Ida Jaya <span class="text-blue-500">Oil</span></h3>
                    <p class="text-gray-400 leading-relaxed text-sm">
                        Mitra terpercaya untuk kebutuhan bengkel Anda. Menyediakan produk berkualitas tinggi dengan harga yang bersahabat.
                    </p>
                </div>
                <!-- Navigasi -->
                <div>
                    <h4 class="text-lg font-bold mb-4 text-gray-200">Navigasi Cepat</h4>
                    <ul class="space-y-2 text-sm text-gray-400">
                        <li><a href="#beranda" onclick="setActiveNav('beranda')" class="hover:text-blue-400 transition">Beranda</a></li>
                        <li><a href="#produk" onclick="setActiveNav('produk')" class="hover:text-blue-400 transition">Katalog Produk</a></li>
                        <li><a href="keranjang.php" class="hover:text-blue-400 transition">Keranjang Belanja</a></li>
                    </ul>
                </div>
                <!-- Kontak -->
                <div>
                    <h4 class="text-lg font-bold mb-4 text-gray-200">Hubungi Kami</h4>
                    <ul class="space-y-2 text-sm text-gray-400">
                        <li class="flex items-center gap-2"><i class="fas fa-map-marker-alt text-blue-500"></i> Jl. Bengkel No. 123, Kota</li>
                        <li class="flex items-center gap-2"><i class="fas fa-phone text-blue-500"></i> +62 812-3456-7890</li>
                        <li class="flex items-center gap-2"><i class="fas fa-envelope text-blue-500"></i> info@idajayaoil.com</li>
                    </ul>
                </div>
            </div>
            <div class="text-center text-gray-500 text-sm">
                &copy; <?= date("Y") ?> Bengkel Ida Jaya Oil. All Rights Reserved.
            </div>
        </div>
    </footer>

    <script>
        function tambahKeKeranjang(idProduk, btnElement) {
            const originalText = btnElement.innerHTML;
            btnElement.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menambahkan...';
            btnElement.disabled = true;

            const formData = new FormData();
            formData.append('action', 'tambah_keranjang');
            formData.append('id_produk', idProduk);

            fetch('index.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.sukses) {
                    document.getElementById('cart-count').innerText = data.total_keranjang;
                    const mobileBadge = document.getElementById('cart-count-mobile');
                    if(mobileBadge) mobileBadge.innerText = data.total_keranjang;
                    
                    const popup = document.getElementById('notification-popup');
                    document.getElementById('notif-message').innerText = data.pesan;
                    
                    popup.classList.remove('translate-x-full'); 
                    
                    setTimeout(() => {
                        popup.classList.add('translate-x-full');
                    }, 3000);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Gagal menambahkan ke keranjang');
            })
            .finally(() => {
                btnElement.innerHTML = originalText;
                btnElement.disabled = false;
            });
        }

        // Script untuk mengatur Navigasi Aktif (Beranda vs Produk)
        function setActiveNav(type) {
            const berandaBtn = document.getElementById('nav-beranda');
            const produkBtn = document.getElementById('nav-produk');

            if (type === 'produk') {
                berandaBtn.classList.remove('active-js', 'text-blue-600', 'font-bold');
                berandaBtn.classList.add('text-gray-600');
                
                produkBtn.classList.add('active-js', 'text-blue-600', 'font-bold');
                produkBtn.classList.remove('text-gray-600');
            } else {
                produkBtn.classList.remove('active-js', 'text-blue-600', 'font-bold');
                produkBtn.classList.add('text-gray-600');
                
                berandaBtn.classList.add('active-js', 'text-blue-600', 'font-bold');
                berandaBtn.classList.remove('text-gray-600');
            }
        }

        // Scroll Spy (Agar Navbar berubah saat discroll)
        window.addEventListener('scroll', () => {
            const produkSection = document.getElementById('produk');
            // Offset agar switch sedikit sebelum mencapai elemen
            const scrollPosition = window.scrollY + 100;

            if (produkSection && scrollPosition >= produkSection.offsetTop) {
                setActiveNav('produk');
            } else {
                setActiveNav('beranda');
            }
        });

        // Otomatis cek hash saat load
        window.addEventListener('load', () => {
            if(window.location.hash === '#produk') {
                setActiveNav('produk');
            } else {
                setActiveNav('beranda');
            }
        });

        // Cek jika hash berubah manual
        window.addEventListener('hashchange', () => {
            if(window.location.hash === '#produk') {
                setActiveNav('produk');
            } else {
                setActiveNav('beranda');
            }
        });
    </script>
</body>
</html>