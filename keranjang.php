<?php
session_start();
include 'db/koneksi.php';

// --- [BARU] AJAX HANDLER: UPDATE KUANTITAS TANPA RELOAD ---
if (isset($_POST['action']) && $_POST['action'] == 'update_qty') {
    header('Content-Type: application/json');
    
    $id_produk = (int)$_POST['id_produk'];
    $qty = (int)$_POST['qty'];
    
    // Validasi minimal 1
    if ($qty < 1) $qty = 1;
    
    // Cek Stok & Harga di Database
    $stmt = $conn->prepare("SELECT stok, harga FROM produk WHERE id = ?");
    $stmt->bind_param("i", $id_produk);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($res->num_rows > 0) {
        $prod = $res->fetch_assoc();
        
        // Validasi Stok
        if ($qty > $prod['stok']) {
            echo json_encode([
                'status' => 'error', 
                'message' => 'Stok maksimal hanya ' . $prod['stok'],
                'reset_qty' => $prod['stok']
            ]);
            exit;
        }
        
        // Update Session
        $_SESSION['keranjang'][$id_produk] = $qty;
        
        // Update DB jika login (Sync Keranjang Tersimpan)
        if (isset($_SESSION['user']) || isset($_SESSION['user_id'])) {
            $id_user = isset($_SESSION['user']['id']) ? $_SESSION['user']['id'] : $_SESSION['user_id'];
            // Menggunakan ON DUPLICATE KEY UPDATE agar efisien
            $stmt_update = $conn->prepare("INSERT INTO keranjang_tersimpan (id_user, id_produk, kuantitas) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE kuantitas = VALUES(kuantitas)");
            $stmt_update->bind_param("iii", $id_user, $id_produk, $qty);
            $stmt_update->execute();
        }
        
        // Hitung data untuk dikirim balik ke JS
        $item_subtotal = $prod['harga'] * $qty;
        
        echo json_encode([
            'status' => 'success',
            'subtotal' => $item_subtotal, // Angka mentah untuk kalkulasi
            'subtotal_formatted' => 'Rp' . number_format($item_subtotal, 0, ',', '.'), // Teks untuk tampilan
            'total_cart_items' => array_sum($_SESSION['keranjang'])
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Produk tidak ditemukan']);
    }
    exit;
}

// --- LOGIKA MENGAMBIL DATA UNTUK TAMPILAN AWAL ---
$keranjang = $_SESSION['keranjang'] ?? [];
$items_in_cart = [];
$total_keseluruhan = 0; 
$total_item = 0;

if (!empty($keranjang)) {
    $product_ids = array_keys($keranjang);
    if (count($product_ids) > 0) {
        $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
        $sql = "SELECT id, nama, harga, gambar, stok FROM produk WHERE id IN ($placeholders)";
        $stmt = $conn->prepare($sql);
        $types = str_repeat('i', count($product_ids));
        $stmt->bind_param($types, ...$product_ids);
        $stmt->execute();
        $result = $stmt->get_result();

        $produk_ditemukan = [];
        while ($row = $result->fetch_assoc()) {
            $produk_ditemukan[$row['id']] = $row;
        }

        foreach ($keranjang as $id_produk => $kuantitas) {
            if (isset($produk_ditemukan[$id_produk])) {
                $produk = $produk_ditemukan[$id_produk];
                
                // Validasi Stok Awal
                if ($kuantitas > $produk['stok']) {
                    $kuantitas = $produk['stok'];
                    $_SESSION['keranjang'][$id_produk] = $kuantitas; 
                }
                if ($kuantitas < 1) {
                    $kuantitas = 1;
                    $_SESSION['keranjang'][$id_produk] = 1;
                }

                $subtotal = $produk['harga'] * $kuantitas;
                $total_keseluruhan += $subtotal;
                $total_item += $kuantitas;
                
                $items_in_cart[] = [
                    'id' => $id_produk,
                    'nama' => $produk['nama'],
                    'harga' => $produk['harga'],
                    'gambar' => $produk['gambar'],
                    'stok' => $produk['stok'],
                    'kuantitas' => $kuantitas,
                    'subtotal' => $subtotal
                ];
            } else {
                unset($_SESSION['keranjang'][$id_produk]);
            }
        }
        $stmt->close();
    }
}

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keranjang Belanja - Bengkel Ida Jaya Oil</title>
    
    <!-- FAVICON -->
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%232563eb'><path d='M22.7 19l-9.1-9.1c.9-2.3.4-5-1.5-6.9-2-2-5-2.4-7.4-1.3L9 6 6 9 1.6 4.7C.4 7.1.9 10.1 2.9 12.1c1.9 1.9 4.6 2.4 6.9 1.5l9.1 9.1c.4.4 1 .4 1.4 0l2.3-2.3c.5-.4.5-1.1.1-1.4z'/></svg>">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #f8fafc; }
        input[type=number]::-webkit-inner-spin-button, 
        input[type=number]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
        
        /* Checkbox Custom Style */
        .custom-checkbox {
            appearance: none;
            background-color: #fff;
            margin: 0;
            font: inherit;
            color: currentColor;
            width: 1.25em;
            height: 1.25em;
            border: 2px solid #cbd5e1;
            border-radius: 0.25em;
            display: grid;
            place-content: center;
            cursor: pointer;
            transition: all 0.2s ease-in-out;
        }
        .custom-checkbox::before {
            content: "";
            width: 0.65em;
            height: 0.65em;
            transform: scale(0);
            transition: 120ms transform ease-in-out;
            box-shadow: inset 1em 1em white;
            transform-origin: center;
            clip-path: polygon(14% 44%, 0 65%, 50% 100%, 100% 16%, 80% 0%, 43% 62%);
        }
        .custom-checkbox:checked {
            background-color: #2563eb;
            border-color: #2563eb;
        }
        .custom-checkbox:checked::before {
            transform: scale(1);
        }

        /* Navbar Links */
        .nav-link { position: relative; display: inline-block; }
        .nav-link::after {
            content: ''; position: absolute; width: 0; height: 1px; bottom: -1px; left: 0;
            background-color: #2563eb; transition: width 0.3s ease-in-out;
        }
        .nav-link:hover::after { width: 100%; }
        .nav-link.active::after { width: 100%; }
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
                    <a href="index.php" class="nav-link text-sm font-medium transition-colors duration-200 text-gray-600 hover:text-blue-600">Beranda</a>
                    <a href="index.php#produk" class="nav-link text-sm font-medium transition-colors duration-200 text-gray-600 hover:text-blue-600">Produk</a>
                    <?php if (isset($_SESSION['user']) || isset($_SESSION['user_id'])): ?>
                        <a href="pesanan_saya.php" class="nav-link text-sm font-medium transition-colors duration-200 text-gray-600 hover:text-blue-600">Pesanan Saya</a>
                        <a href="logout.php" class="text-red-500 border border-red-200 px-4 py-1.5 rounded-full text-sm font-medium hover:bg-red-50 hover:border-red-300 transition-all shadow-sm hover:shadow">Logout</a>
                    <?php else: ?>
                        <a href="login.php" class="nav-link text-sm font-medium transition-colors duration-200 text-blue-600 hover:text-blue-800">Masuk</a>
                        <a href="registrasi.php" class="bg-blue-600 text-white px-5 py-2 rounded-full text-sm font-medium hover:bg-blue-700 transition shadow-lg shadow-blue-500/30">Daftar</a>
                    <?php endif; ?>
                    <a href="keranjang.php" class="relative text-blue-600 transition transform scale-110">
                        <i class="fas fa-shopping-cart text-xl"></i>
                        <span id="cart-count" class="absolute -top-2 -right-2 bg-red-500 text-white text-[10px] font-bold w-5 h-5 flex items-center justify-center rounded-full border-2 border-white shadow-sm">
                            <?= $total_item ?>
                        </span>
                    </a>
                </div>
                <div class="flex items-center md:hidden">
                    <button onclick="document.getElementById('mobile-menu').classList.toggle('hidden')" class="text-gray-600 hover:text-blue-600 focus:outline-none p-2">
                        <i class="fas fa-bars text-2xl"></i>
                    </button>
                </div>
            </div>
        </div>
        <div id="mobile-menu" class="hidden md:hidden bg-white border-t pt-16">
            <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3">
                <a href="index.php" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-blue-600 hover:bg-blue-50">Beranda</a>
                <a href="index.php#produk" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-blue-600 hover:bg-blue-50">Produk</a>
                <a href="keranjang.php" class="block px-3 py-2 rounded-md text-base font-medium bg-blue-50 text-blue-600 border-l-4 border-blue-600">Keranjang</a>
            </div>
        </div>
    </nav>

    <!-- MAIN CONTENT -->
    <main class="flex-grow container mx-auto px-4 py-24">
        <h1 class="text-3xl font-bold text-gray-800 mb-8 flex items-center gap-3">
            <i class="fas fa-shopping-bag text-blue-600"></i> Keranjang Belanja
        </h1>

        <?php if (empty($items_in_cart)): ?>
            <div class="bg-white rounded-xl shadow-sm border border-dashed border-gray-300 py-16 text-center">
                <div class="inline-flex items-center justify-center w-24 h-24 bg-blue-50 rounded-full mb-6">
                    <i class="fas fa-shopping-cart text-blue-300 text-4xl"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-800 mb-2">Keranjang Kosong</h2>
                <p class="text-gray-500 max-w-md mx-auto mb-8">Wah, keranjang Anda masih kosong nih. Yuk cari suku cadang yang Anda butuhkan!</p>
                <a href="index.php" class="inline-flex items-center bg-blue-600 text-white px-8 py-3 rounded-full font-bold shadow-lg hover:bg-blue-700 transition transform hover:-translate-y-1">
                    <i class="fas fa-search mr-2"></i> Mulai Belanja
                </a>
            </div>
        <?php else: ?>

            <!-- FORM: Hanya digunakan untuk submit CHECKOUT, bukan update -->
            <form id="cartForm" method="POST">
                <div class="flex flex-col lg:flex-row gap-8">
                    
                    <!-- LEFT: List Items -->
                    <div class="flex-grow">
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                            <div class="hidden md:grid grid-cols-12 gap-4 p-4 bg-gray-50 border-b text-sm font-semibold text-gray-600 uppercase items-center">
                                <div class="col-span-1 text-center">
                                    <input type="checkbox" id="selectAll" class="custom-checkbox" onclick="toggleSelectAll()" checked>
                                </div>
                                <div class="col-span-5">Produk</div>
                                <div class="col-span-3 text-center">Jumlah</div>
                                <div class="col-span-2 text-right">Subtotal</div>
                                <div class="col-span-1 text-center">Aksi</div>
                            </div>

                            <div class="divide-y divide-gray-100">
                                <?php foreach ($items_in_cart as $item): ?>
                                <div class="p-4 md:p-6 grid grid-cols-1 md:grid-cols-12 gap-4 items-center group hover:bg-gray-50/50 transition">
                                    
                                    <!-- Checkbox Item -->
                                    <div class="col-span-1 flex justify-center md:justify-center items-center">
                                        <!-- ID ditambahkan agar mudah diakses JS -->
                                        <input type="checkbox" 
                                               id="cb-<?= $item['id'] ?>"
                                               name="selected_items[]" 
                                               value="<?= $item['id'] ?>" 
                                               class="custom-checkbox item-checkbox"
                                               data-price="<?= $item['subtotal'] ?>"
                                               onclick="calculateTotal()" 
                                               checked>
                                    </div>

                                    <!-- Product Info -->
                                    <div class="col-span-11 md:col-span-5 flex items-center gap-4">
                                        <div class="w-20 h-20 flex-shrink-0 bg-gray-100 rounded-lg overflow-hidden border border-gray-200">
                                            <img src="admin/uploads/<?= htmlspecialchars($item['gambar']) ?>" alt="" class="w-full h-full object-cover" onerror="this.src='https://placehold.co/100x100'">
                                        </div>
                                        <div>
                                            <h3 class="font-bold text-gray-800 text-lg line-clamp-2"><?= htmlspecialchars($item['nama']) ?></h3>
                                            <p class="text-sm text-gray-500">Harga: <span class="text-blue-600 font-medium">Rp<?= number_format($item['harga'], 0, ',', '.') ?></span></p>
                                        </div>
                                    </div>

                                    <!-- Quantity Input (AJAX) -->
                                    <div class="col-span-6 md:col-span-3 flex justify-between md:justify-center items-center pl-8 md:pl-0">
                                        <div class="flex items-center border border-gray-300 rounded-lg bg-white">
                                            <button type="button" onclick="ubahQty(<?= $item['id'] ?>, -1)" class="w-8 h-8 flex items-center justify-center text-gray-500 hover:bg-gray-100 hover:text-blue-600 transition rounded-l-lg"><i class="fas fa-minus text-xs"></i></button>
                                            
                                            <!-- onchange panggil updateManual, bukan submit form -->
                                            <input type="number" id="qty-<?= $item['id'] ?>" 
                                                   value="<?= $item['kuantitas'] ?>" 
                                                   min="1" max="<?= $item['stok'] ?>" 
                                                   onchange="updateManual(<?= $item['id'] ?>, this.value)"
                                                   class="w-12 h-8 text-center text-sm font-medium border-x border-gray-300 focus:outline-none text-gray-700">
                                            
                                            <button type="button" onclick="ubahQty(<?= $item['id'] ?>, 1)" class="w-8 h-8 flex items-center justify-center text-gray-500 hover:bg-gray-100 hover:text-blue-600 transition rounded-r-lg"><i class="fas fa-plus text-xs"></i></button>
                                        </div>
                                    </div>

                                    <!-- Subtotal (Dinamis ID) -->
                                    <div class="col-span-6 md:col-span-2 flex justify-between md:justify-end items-center">
                                        <span class="md:hidden text-sm font-medium text-gray-500">Subtotal:</span>
                                        <span class="font-bold text-gray-800" id="subtotal-<?= $item['id'] ?>">Rp<?= number_format($item['subtotal'], 0, ',', '.') ?></span>
                                    </div>

                                    <!-- Delete -->
                                    <div class="col-span-12 md:col-span-1 flex justify-end md:justify-center">
                                        <a href="hapus_keranjang.php?id=<?= $item['id'] ?>" class="text-red-400 hover:text-red-600 w-8 h-8 flex items-center justify-center rounded-full hover:bg-red-50 transition" onclick="return confirm('Hapus item ini?')">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <div class="mt-4 flex justify-between items-center">
                            <a href="index.php#produk" class="text-blue-600 hover:text-blue-800 font-medium text-sm flex items-center gap-1"><i class="fas fa-arrow-left"></i> Lanjut Belanja</a>
                        </div>
                    </div>

                    <!-- RIGHT: Summary (Sticky) -->
                    <div class="w-full lg:w-96 flex-shrink-0">
                        <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-6 sticky top-24">
                            <h3 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2">Ringkasan Belanja</h3>
                            
                            <div class="space-y-3 mb-6">
                                <div class="flex justify-between text-gray-600 text-sm">
                                    <span>Total Item Terpilih</span>
                                    <span id="selected-count"><?= count($items_in_cart) ?> barang</span>
                                </div>
                                <div class="flex justify-between text-xl font-bold text-blue-600 pt-3 border-t">
                                    <span>Total</span>
                                    <span id="display-total">Rp<?= number_format($total_keseluruhan, 0, ',', '.') ?></span>
                                </div>
                            </div>

                            <?php if (isset($_SESSION['user']) || isset($_SESSION['user_id'])): ?>
                                <button type="submit" 
                                        formaction="checkout.php" 
                                        id="checkout-btn"
                                        class="block w-full bg-gradient-to-r from-blue-600 to-cyan-600 text-white text-center font-bold py-3.5 rounded-xl shadow-lg hover:shadow-xl hover:from-blue-700 hover:to-cyan-700 transition transform hover:-translate-y-0.5 disabled:opacity-50 disabled:cursor-not-allowed">
                                    Checkout (<span id="btn-count"><?= count($items_in_cart) ?></span>)
                                </button>
                            <?php else: ?>
                                <a href="login.php" class="block w-full bg-gray-800 text-white text-center font-bold py-3.5 rounded-xl hover:bg-gray-900 transition mb-3">Login untuk Checkout</a>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>
            </form>
        <?php endif; ?>
    </main>

    <footer class="bg-slate-900 text-white pt-8 pb-6 mt-auto">
        <div class="container mx-auto px-4 text-center text-gray-400 text-sm">
            &copy; <?= date("Y") ?> Bengkel Ida Jaya Oil. All Rights Reserved.
        </div>
    </footer>

    <script>
        const formatter = new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0
        });

        // 1. UPDATE QTY VIA AJAX
        function ubahQty(id, change) {
            const input = document.getElementById('qty-' + id);
            const max = parseInt(input.getAttribute('max'));
            let val = parseInt(input.value);
            
            val += change;
            if (val < 1) val = 1;
            if (val > max) { val = max; alert('Stok maksimal hanya ' + max); }
            
            input.value = val;
            updateManual(id, val); // Panggil fungsi AJAX
        }

        // 2. FUNGSI AJAX KE SERVER
        function updateManual(id, qty) {
            // Gunakan FormData untuk mengirim POST request
            const formData = new FormData();
            formData.append('action', 'update_qty');
            formData.append('id_produk', id);
            formData.append('qty', qty);

            fetch('keranjang.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // Update tampilan subtotal di baris item
                    document.getElementById('subtotal-' + id).innerText = data.subtotal_formatted;
                    
                    // Update data-price pada checkbox agar kalkulasi total benar
                    const checkbox = document.getElementById('cb-' + id);
                    checkbox.setAttribute('data-price', data.subtotal);
                    
                    // Update Badge Cart di Navbar
                    document.getElementById('cart-count').innerText = data.total_cart_items;
                    
                    // Hitung ulang total belanja
                    calculateTotal();
                } else {
                    alert(data.message);
                    // Reset input jika stok tidak cukup
                    if(data.reset_qty) {
                        document.getElementById('qty-' + id).value = data.reset_qty;
                    }
                }
            })
            .catch(error => console.error('Error:', error));
        }

        // 3. KALKULASI TOTAL (Tanpa Reload)
        function calculateTotal() {
            const checkboxes = document.querySelectorAll('.item-checkbox');
            let total = 0;
            let count = 0;

            checkboxes.forEach(cb => {
                if (cb.checked) {
                    total += parseFloat(cb.getAttribute('data-price'));
                    count++;
                }
            });

            document.getElementById('display-total').innerText = formatter.format(total);
            document.getElementById('selected-count').innerText = count + " barang";
            
            const btn = document.getElementById('checkout-btn');
            if(btn) {
                document.getElementById('btn-count').innerText = count;
                btn.disabled = count === 0;
                if(count === 0) {
                    btn.classList.add('opacity-50', 'cursor-not-allowed');
                } else {
                    btn.classList.remove('opacity-50', 'cursor-not-allowed');
                }
            }
            
            const allChecked = Array.from(checkboxes).every(cb => cb.checked);
            const selectAll = document.getElementById('selectAll');
            if(selectAll) selectAll.checked = allChecked && checkboxes.length > 0;
        }

        function toggleSelectAll() {
            const masterCheckbox = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.item-checkbox');
            checkboxes.forEach(cb => cb.checked = masterCheckbox.checked);
            calculateTotal();
        }
    </script>
</body>
</html>