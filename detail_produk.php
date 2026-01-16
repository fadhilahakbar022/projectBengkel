<?php
session_start();
// Sesuaikan path koneksi dengan struktur folder Anda
include 'db/koneksi.php'; 

// --- LOGIKA TAMBAH KE KERANJANG (AJAX KHUSUS DETAIL) ---
if (isset($_POST['action']) && $_POST['action'] == 'add_to_cart_detail') {
    header('Content-Type: application/json');
    
    $id_produk = (int)$_POST['id_produk'];
    $qty_diminta = (int)$_POST['quantity'];
    
    if ($qty_diminta < 1) $qty_diminta = 1;

    $stmt_stok = $conn->prepare("SELECT stok, nama FROM produk WHERE id = ?");
    $stmt_stok->bind_param("i", $id_produk);
    $stmt_stok->execute();
    $res_stok = $stmt_stok->get_result();
    
    if ($res_stok->num_rows > 0) {
        $prod_data = $res_stok->fetch_assoc();
        $stok_db = $prod_data['stok'];
        
        if ($qty_diminta > $stok_db) {
            echo json_encode(['sukses' => false, 'pesan' => 'Stok tidak mencukupi! Sisa: ' . $stok_db]);
            exit;
        }

        if (!isset($_SESSION['keranjang'])) {
            $_SESSION['keranjang'] = [];
        }

        if (isset($_SESSION['keranjang'][$id_produk])) {
            $_SESSION['keranjang'][$id_produk] += $qty_diminta;
        } else {
            $_SESSION['keranjang'][$id_produk] = $qty_diminta;
        }
        
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
            'pesan' => 'Berhasil menambahkan ' . $qty_diminta . ' item ke keranjang!',
            'total_keranjang' => array_sum($_SESSION['keranjang'])
        ]);
    } else {
        echo json_encode(['sukses' => false, 'pesan' => 'Produk tidak ditemukan.']);
    }
    exit;
}

// --- LOGIKA FETCH PRODUK ---
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id_produk = (int)$_GET['id'];

$stmt = $conn->prepare("SELECT * FROM produk WHERE id = ?");
$stmt->bind_param("i", $id_produk);
$stmt->execute();
$result = $stmt->get_result();

$produk_ditemukan = false;
$produk = [];
if ($result->num_rows > 0) {
    $produk_ditemukan = true;
    $produk = $result->fetch_assoc();
}
$stmt->close();

$total_item_keranjang = isset($_SESSION['keranjang']) ? array_sum($_SESSION['keranjang']) : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $produk_ditemukan ? htmlspecialchars($produk['nama']) : 'Produk Tidak Ditemukan' ?> - Bengkel Ida Jaya Oil</title>
    <!-- FAVICON -->
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%232563eb'><path d='M22.7 19l-9.1-9.1c.9-2.3.4-5-1.5-6.9-2-2-5-2.4-7.4-1.3L9 6 6 9 1.6 4.7C.4 7.1.9 10.1 2.9 12.1c1.9 1.9 4.6 2.4 6.9 1.5l9.1 9.1c.4.4 1 .4 1.4 0l2.3-2.3c.5-.4.5-1.1.1-1.4z'/></svg>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #f8fafc; }
        input[type=number]::-webkit-inner-spin-button, 
        input[type=number]::-webkit-outer-spin-button { 
            -webkit-appearance: none; margin: 0; 
        }
        .animate-fade-in { animation: fadeIn 0.5s ease-in-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        /* Lightbox Styles */
        .lightbox {
            display: none;
            position: fixed;
            z-index: 1000;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.9);
            justify-content: center;
            align-items: center;
            opacity: 0;
            transition: opacity 0.3s ease;
            backdrop-filter: blur(5px);
        }
        .lightbox.active {
            display: flex;
            opacity: 1;
        }
        .lightbox img {
            max-width: 90%;
            max-height: 85%;
            object-fit: contain;
            border-radius: 8px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            transform: scale(0.95);
            transition: transform 0.3s ease;
        }
        .lightbox.active img {
            transform: scale(1);
        }
        .lightbox-close {
            position: absolute;
            top: 20px;
            right: 30px;
            color: white;
            font-size: 40px;
            cursor: pointer;
            z-index: 1001;
            transition: transform 0.2s;
        }
        .lightbox-close:hover {
            transform: scale(1.1);
            color: #ef4444; /* red-500 */
        }
    </style>
</head>
<body class="flex flex-col min-h-screen">

    <!-- NAVBAR -->
    <nav class="bg-white/95 backdrop-blur-md shadow-md fixed w-full z-50 transition-all duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <div class="flex-shrink-0 flex items-center gap-2 group cursor-pointer" onclick="window.location.href='index.php'">
                    <i class="fas fa-wrench text-blue-600 text-2xl transform rotate-45 transition-transform group-hover:rotate-12"></i>
                    <span class="text-xl font-bold text-gray-800 tracking-wide group-hover:text-blue-600 transition-colors">
                        IDA JAYA <span class="text-blue-600 group-hover:text-gray-800">OIL</span>
                    </span>
                </div>
                <div class="hidden md:flex space-x-8 items-center">
                    <a href="index.php" class="text-gray-600 hover:text-blue-600 font-medium transition">Beranda</a>
                    <a href="index.php#produk" class="text-blue-600 font-medium transition font-bold">Produk</a>
                    <?php if (isset($_SESSION['user']) || isset($_SESSION['user_id'])): ?>
                        <a href="pesanan_saya.php" class="text-gray-600 hover:text-blue-600 font-medium transition">Pesanan Saya</a>
                        <a href="logout.php" class="text-red-500 border border-red-200 px-4 py-1 rounded-full hover:bg-red-50 font-medium transition">Logout</a>
                    <?php else: ?>
                        <a href="login.php" class="text-blue-600 font-medium hover:text-blue-800 transition">Masuk</a>
                        <a href="registrasi.php" class="bg-blue-600 text-white px-4 py-2 rounded-full font-medium hover:bg-blue-700 transition shadow-lg shadow-blue-500/30">Daftar</a>
                    <?php endif; ?>
                    <a href="keranjang.php" class="relative text-gray-600 hover:text-blue-600 transition">
                        <i class="fas fa-shopping-cart text-xl"></i>
                        <span id="cart-count" class="absolute -top-2 -right-2 bg-red-500 text-white text-xs font-bold w-5 h-5 flex items-center justify-center rounded-full border-2 border-white">
                            <?= $total_item_keranjang ?>
                        </span>
                    </a>
                </div>
                <div class="flex items-center md:hidden">
                    <a href="keranjang.php" class="relative text-gray-600 mr-4">
                        <i class="fas fa-shopping-cart text-xl"></i>
                        <span id="cart-count-mobile" class="absolute -top-2 -right-2 bg-red-500 text-white text-xs font-bold w-5 h-5 flex items-center justify-center rounded-full">
                            <?= $total_item_keranjang ?>
                        </span>
                    </a>
                    <button onclick="document.getElementById('mobile-menu').classList.toggle('hidden')" class="text-gray-600 hover:text-blue-600 focus:outline-none">
                        <i class="fas fa-bars text-2xl"></i>
                    </button>
                </div>
            </div>
        </div>
        <div id="mobile-menu" class="hidden md:hidden bg-white border-t">
            <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3">
                <a href="index.php" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-blue-600 hover:bg-gray-50">Beranda</a>
                <a href="index.php#produk" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-blue-600 hover:bg-gray-50">Produk</a>
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
                <p class="font-bold text-sm">Berhasil!</p>
                <p class="text-xs text-gray-500" id="notif-message">Item ditambahkan.</p>
            </div>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <main class="flex-grow container mx-auto px-4 py-24">
        
        <?php if ($produk_ditemukan): ?>
            
            <!-- Breadcrumbs -->
            <nav class="text-sm text-gray-500 mb-6 flex items-center gap-2 animate-fade-in">
                <a href="index.php" class="hover:text-blue-600 transition">Beranda</a>
                <i class="fas fa-chevron-right text-xs text-gray-300"></i>
                <a href="index.php#produk" class="hover:text-blue-600 transition">Produk</a>
                <i class="fas fa-chevron-right text-xs text-gray-300"></i>
                <?php if (!empty($produk['kategori'])): ?>
                    <span class="hover:text-blue-600 cursor-default"><?= htmlspecialchars($produk['kategori']) ?></span>
                    <i class="fas fa-chevron-right text-xs text-gray-300"></i>
                <?php endif; ?>
                <span class="text-gray-800 font-medium truncate max-w-[200px]"><?= htmlspecialchars($produk['nama']) ?></span>
            </nav>

            <!-- Product Card -->
            <div class="bg-white rounded-3xl shadow-xl overflow-hidden animate-fade-in">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-0">
                    
                    <!-- Kiri: Gambar Produk (Klik untuk Zoom) -->
                    <div class="bg-gray-100 flex items-center justify-center p-8 md:p-12 relative group cursor-zoom-in overflow-hidden" 
                         onclick="openLightbox('admin/uploads/<?= htmlspecialchars($produk['gambar']) ?>')">
                        
                        <img src="admin/uploads/<?= htmlspecialchars($produk['gambar']) ?>" 
                             alt="<?= htmlspecialchars($produk['nama']) ?>" 
                             class="max-h-[400px] w-auto object-contain drop-shadow-lg transition-transform duration-500 group-hover:scale-110"
                             onerror="this.src='https://placehold.co/600x600/e2e8f0/64748b?text=No+Image'">
                        
                        <!-- Zoom Icon Badge -->
                        <div class="absolute bottom-6 right-6 bg-white/90 p-3 rounded-full text-blue-600 shadow-lg opacity-0 translate-y-4 group-hover:opacity-100 group-hover:translate-y-0 transition-all duration-300">
                            <i class="fas fa-expand-alt text-xl"></i>
                        </div>
                    </div>

                    <!-- Kanan: Detail Produk -->
                    <div class="p-8 md:p-12 flex flex-col justify-center">
                        
                        <!-- Kategori Badge -->
                        <?php if (!empty($produk['kategori'])): ?>
                        <div class="mb-4">
                            <span class="bg-blue-100 text-blue-700 text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wide">
                                <?= htmlspecialchars($produk['kategori']) ?>
                            </span>
                        </div>
                        <?php endif; ?>

                        <h1 class="text-3xl md:text-4xl font-bold text-gray-800 mb-4 leading-tight">
                            <?= htmlspecialchars($produk['nama']) ?>
                        </h1>

                        <div class="text-3xl font-bold text-blue-600 mb-6">
                            Rp<?= number_format($produk['harga'], 0, ',', '.') ?>
                        </div>

                        <div class="flex items-center gap-4 mb-6 text-sm">
                            <div class="flex items-center gap-2 px-3 py-1 rounded bg-gray-100">
                                <i class="fas fa-box text-gray-500"></i>
                                <span class="text-gray-600">Stok: <strong class="text-gray-900"><?= $produk['stok'] ?></strong></span>
                            </div>
                            <?php if ($produk['stok'] > 0): ?>
                                <span class="text-green-600 font-medium flex items-center gap-1"><i class="fas fa-check-circle"></i> Tersedia</span>
                            <?php else: ?>
                                <span class="text-red-600 font-medium flex items-center gap-1"><i class="fas fa-times-circle"></i> Habis</span>
                            <?php endif; ?>
                        </div>

                        <div class="prose prose-sm text-gray-600 mb-8 border-t border-gray-100 pt-6">
                            <h3 class="text-gray-800 font-semibold mb-2">Deskripsi Produk</h3>
                            <p class="leading-relaxed">
                                <?= !empty($produk['deskripsi']) ? nl2br(htmlspecialchars($produk['deskripsi'])) : 'Tidak ada deskripsi detail untuk produk ini.' ?>
                            </p>
                        </div>

                        <?php if ($produk['stok'] > 0): ?>
                        <div class="flex flex-col sm:flex-row gap-4 mt-auto">
                            <div class="flex items-center border border-gray-300 rounded-lg w-max">
                                <button type="button" onclick="updateQty(-1)" class="w-10 h-10 flex items-center justify-center text-gray-500 hover:bg-gray-100 hover:text-blue-600 transition rounded-l-lg">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <input type="number" id="qty-input" value="1" min="1" max="<?= $produk['stok'] ?>" 
                                       class="w-12 h-10 text-center text-gray-700 font-medium border-x border-gray-300 focus:outline-none bg-white">
                                <button type="button" onclick="updateQty(1)" class="w-10 h-10 flex items-center justify-center text-gray-500 hover:bg-gray-100 hover:text-blue-600 transition rounded-r-lg">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>

                            <button onclick="addToCartDetail(<?= $produk['id'] ?>)" id="btn-add-cart"
                                class="flex-grow bg-gray-900 text-white font-bold py-3 px-6 rounded-lg shadow-lg hover:bg-blue-600 transition-all duration-300 flex items-center justify-center gap-2 transform active:scale-95">
                                <i class="fas fa-cart-plus"></i>
                                <span>Tambah ke Keranjang</span>
                            </button>
                        </div>
                        <?php else: ?>
                            <div class="bg-red-50 text-red-600 p-4 rounded-lg text-center font-medium border border-red-100">
                                Stok Habis
                            </div>
                        <?php endif; ?>

                    </div>
                </div>
            </div>

        <?php else: ?>
            
            <div class="flex flex-col items-center justify-center py-20 bg-white rounded-3xl shadow-sm text-center">
                <div class="bg-gray-100 p-6 rounded-full mb-6">
                    <i class="fas fa-box-open text-gray-400 text-5xl"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-800 mb-2">Produk Tidak Ditemukan</h2>
                <p class="text-gray-500 max-w-md mb-8">Maaf, produk yang Anda cari mungkin telah dihapus atau ID produk tidak valid.</p>
                <a href="index.php" class="bg-blue-600 text-white px-8 py-3 rounded-full font-medium hover:bg-blue-700 transition shadow-lg shadow-blue-500/30">
                    <i class="fas fa-arrow-left mr-2"></i> Kembali ke Katalog
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

    <!-- LIGHTBOX MODAL (POP UP GAMBAR) -->
    <div id="imageLightbox" class="lightbox" onclick="closeLightbox()">
        <span class="lightbox-close" onclick="closeLightbox()">&times;</span>
        <!-- Gambar yang muncul disini akan di-set lewat JS -->
        <img id="lightboxImg" src="" alt="Full Image" onclick="event.stopPropagation()">
    </div>

    <!-- SCRIPT LOGIKA JS -->
    <script>
        // Logika Lightbox Gambar
        function openLightbox(src) {
            const lightbox = document.getElementById('imageLightbox');
            const img = document.getElementById('lightboxImg');
            
            // Set sumber gambar
            img.src = src;
            
            // Tampilkan Modal
            lightbox.classList.add('active');
            
            // Matikan scroll body agar tidak geser
            document.body.style.overflow = 'hidden';
        }

        function closeLightbox() {
            const lightbox = document.getElementById('imageLightbox');
            
            // Sembunyikan Modal
            lightbox.classList.remove('active');
            
            // Hidupkan scroll body
            document.body.style.overflow = 'auto';
        }

        // Close on ESC key
        document.addEventListener('keydown', function(event) {
            if (event.key === "Escape") {
                closeLightbox();
            }
        });

        // Logika Input Plus/Minus
        function updateQty(change) {
            const input = document.getElementById('qty-input');
            const max = parseInt(input.getAttribute('max'));
            let val = parseInt(input.value);
            
            val += change;
            
            if (val < 1) val = 1;
            if (val > max) {
                val = max;
                alert('Stok maksimal hanya ' + max);
            }
            
            input.value = val;
        }

        // Logika AJAX Add to Cart
        function addToCartDetail(idProduk) {
            const btnElement = document.getElementById('btn-add-cart');
            const qty = document.getElementById('qty-input').value;
            const originalText = btnElement.innerHTML;

            btnElement.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>Memproses...</span>';
            btnElement.disabled = true;
            btnElement.classList.add('opacity-75', 'cursor-not-allowed');

            const formData = new FormData();
            formData.append('action', 'add_to_cart_detail');
            formData.append('id_produk', idProduk);
            formData.append('quantity', qty);

            fetch('detail_produk.php?id=' + idProduk, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.sukses) {
                    document.getElementById('cart-count').innerText = data.total_keranjang;
                    const msg = document.getElementById('notif-message');
                    msg.innerText = data.pesan;
                    const popup = document.getElementById('notification-popup');
                    popup.classList.remove('translate-x-full');
                    setTimeout(() => {
                        popup.classList.add('translate-x-full');
                    }, 3000);
                } else {
                    alert('Gagal: ' + data.pesan);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan sistem.');
            })
            .finally(() => {
                btnElement.innerHTML = originalText;
                btnElement.disabled = false;
                btnElement.classList.remove('opacity-75', 'cursor-not-allowed');
            });
        }
    </script>
</body>
</html>