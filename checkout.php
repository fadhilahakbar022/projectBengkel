<?php
session_start();
include 'db/koneksi.php';

// 1. Cek Login
if (!isset($_SESSION['user']) && !isset($_SESSION['user_id'])) {
    $_SESSION['error_message'] = "Silakan login untuk melanjutkan checkout.";
    header("Location: login.php");
    exit;
}

// Ambil Data User untuk Auto-Fill
$id_user = isset($_SESSION['user']['id']) ? $_SESSION['user']['id'] : $_SESSION['user_id'];
$user_info = [];
// Coba ambil data user (sesuaikan nama kolom id di tabel users Anda)
$sql_user = "SELECT nama_lengkap, no_telp FROM users WHERE id_user = ? LIMIT 1"; 
$stmt_user = $conn->prepare($sql_user);

// Fallback jika pakai id_user
if (!$stmt_user) {
    $sql_user = "SELECT nama_lengkap, no_telp FROM users WHERE id_user = ? LIMIT 1";
    $stmt_user = $conn->prepare($sql_user);
}

if ($stmt_user) {
    $stmt_user->bind_param("i", $id_user);
    $stmt_user->execute();
    $result_user = $stmt_user->get_result();
    if ($result_user->num_rows > 0) {
        $user_info = $result_user->fetch_assoc();
    }
}

// 2. Filter Item Checkout
$items_to_checkout = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['selected_items'])) {
    foreach ($_POST['selected_items'] as $id) {
        if (isset($_SESSION['keranjang'][$id])) {
            $items_to_checkout[$id] = $_SESSION['keranjang'][$id];
        }
    }
} else {
    if (!empty($_SESSION['keranjang'])) {
        $items_to_checkout = $_SESSION['keranjang'];
    }
}

if (empty($items_to_checkout)) {
    header("Location: keranjang.php");
    exit;
}

// 3. Ambil Data Produk
$product_ids = array_keys($items_to_checkout);
$placeholders = implode(',', array_fill(0, count($product_ids), '?'));
$sql = "SELECT id, nama, harga, gambar FROM produk WHERE id IN ($placeholders)";
$stmt = $conn->prepare($sql);
$types = str_repeat('i', count($product_ids));
$stmt->bind_param($types, ...$product_ids);
$stmt->execute();
$result = $stmt->get_result();

$items_checkout = [];
$total_belanja = 0;

while ($row = $result->fetch_assoc()) {
    $qty = $items_to_checkout[$row['id']];
    $subtotal = $row['harga'] * $qty;
    $total_belanja += $subtotal;
    
    $items_checkout[] = [
        'nama' => $row['nama'],
        'harga' => $row['harga'],
        'qty' => $qty,
        'subtotal' => $subtotal,
        'gambar' => $row['gambar']
    ];
}

$biaya_layanan = 1000;
// Ongkir awal 0
$grand_total_awal = $total_belanja + $biaya_layanan;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Bengkel Ida Jaya Oil</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%232563eb'><path d='M22.7 19l-9.1-9.1c.9-2.3.4-5-1.5-6.9-2-2-5-2.4-7.4-1.3L9 6 6 9 1.6 4.7C.4 7.1.9 10.1 2.9 12.1c1.9 1.9 4.6 2.4 6.9 1.5l9.1 9.1c.4.4 1 .4 1.4 0l2.3-2.3c.5-.4.5-1.1.1-1.4z'/></svg>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body { font-family: 'Poppins', sans-serif; background-color: #f3f4f6; }
        
        /* Modal Style */
        .modal {
            transition: opacity 0.2s ease-in-out, visibility 0.2s;
            opacity: 0;
            visibility: hidden;
        }
        .modal.active {
            opacity: 1;
            visibility: visible;
        }
        .modal-content {
            transform: scale(0.95);
            transition: transform 0.2s ease-in-out;
        }
        .modal.active .modal-content {
            transform: scale(1);
        }
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #f1f1f1; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        
        /* Selection Style */
        .option-label.selected {
            border-color: #2563eb;
            background-color: #eff6ff;
        }
        .option-label .check-indicator {
            opacity: 0;
            transform: scale(0.5);
            transition: all 0.2s;
        }
        .option-label.selected .check-indicator {
            opacity: 1;
            transform: scale(1);
        }
        
        /* Error Input Style */
        .input-error {
            border-color: #ef4444 !important; /* Red-500 */
            background-color: #fef2f2 !important; /* Red-50 */
        }
        .input-error:focus {
            ring-color: #ef4444 !important;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col">

    <nav class="bg-white shadow-sm py-4 sticky top-0 z-40">
        <div class="container mx-auto px-4 flex items-center justify-between">
            <a href="keranjang.php" class="text-gray-600 hover:text-blue-600 flex items-center gap-2 text-sm font-medium">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
            <div class="flex items-center gap-2">
                <i class="fas fa-shield-alt text-green-600"></i>
                <span class="font-bold text-gray-800">Checkout Aman</span>
            </div>
        </div>
    </nav>

    <main class="flex-grow container mx-auto px-4 py-8">
        <form action="proses_checkout.php" method="POST" id="checkoutForm" novalidate>
            
            <div class="flex flex-col lg:flex-row gap-8">
                
                <!-- KOLOM KIRI -->
                <div class="flex-grow space-y-6">
                    
                    <!-- 1. Alamat Pengiriman -->
                    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                        <h2 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                            <span class="bg-blue-600 text-white w-6 h-6 rounded-full flex items-center justify-center text-xs">1</span>
                            Alamat Pengiriman
                        </h2>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Nama Penerima</label>
                                <input type="text" id="input_nama" name="nama_penerima" class="w-full bg-gray-50 border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:outline-none transition" placeholder="Nama Lengkap" value="<?= htmlspecialchars($user_info['nama_lengkap'] ?? '') ?>">
                                <!-- Pesan Error -->
                                <p id="error_nama" class="text-red-500 text-xs mt-1 hidden"><i class="fas fa-exclamation-circle mr-1"></i> Nama penerima wajib diisi.</p>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">No. WhatsApp</label>
                                <input type="tel" id="input_hp" name="no_hp" class="w-full bg-gray-50 border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:outline-none transition" placeholder="08xxxxxxxxxx" value="<?= htmlspecialchars($user_info['no_telp'] ?? '') ?>">
                                <!-- Pesan Error -->
                                <p id="error_hp" class="text-red-500 text-xs mt-1 hidden"><i class="fas fa-exclamation-circle mr-1"></i> Nomor WhatsApp wajib diisi.</p>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Alamat Lengkap</label>
                                <textarea id="input_alamat" name="alamat" rows="3" class="w-full bg-gray-50 border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:outline-none transition" placeholder="Jalan, RT/RW, Kelurahan, Kecamatan..."></textarea>
                                <!-- Pesan Error -->
                                <p id="error_alamat" class="text-red-500 text-xs mt-1 hidden"><i class="fas fa-exclamation-circle mr-1"></i> Alamat lengkap wajib diisi.</p>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Catatan (Opsional)</label>
                                <input type="text" name="catatan" class="w-full bg-gray-50 border border-gray-300 rounded-lg px-4 py-2.5 focus:ring-2 focus:ring-blue-500 focus:outline-none transition" placeholder="Pesan untuk penjual/kurir">
                            </div>
                        </div>
                    </div>

                    <!-- 2. Metode Pembayaran -->
                    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                        <h2 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                            <span class="bg-blue-600 text-white w-6 h-6 rounded-full flex items-center justify-center text-xs">2</span>
                            Metode Pembayaran
                        </h2>

                        <input type="hidden" name="metode_pembayaran" id="paymentInput" required>

                        <!-- Tombol Buka Modal Pembayaran -->
                        <div id="paymentDisplay" class="bg-white border border-gray-300 rounded-xl p-4 flex justify-between items-center cursor-pointer hover:border-blue-500 hover:shadow-md transition-all group" onclick="openModal('paymentModal')">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center text-gray-500 group-hover:bg-blue-100 group-hover:text-blue-600 transition">
                                    <i class="fas fa-wallet text-xl"></i>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-700 group-hover:text-gray-900 transition">Pilih Metode Pembayaran</p>
                                    <p class="text-xs text-gray-500">Transfer Bank, E-Wallet, atau COD</p>
                                </div>
                            </div>
                            <div class="text-gray-400 group-hover:text-blue-600 transition flex items-center gap-2">
                                <span class="text-sm font-medium">Lihat Semua</span>
                                <i class="fas fa-chevron-right text-xs"></i>
                            </div>
                        </div>

                        <!-- Tampilan Setelah Dipilih -->
                        <div id="paymentSelected" class="hidden bg-blue-50 border border-blue-500 rounded-xl p-4 flex justify-between items-center cursor-pointer hover:bg-blue-100 transition shadow-sm" onclick="openModal('paymentModal')">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-white rounded-full flex items-center justify-center shadow-sm text-blue-600 text-lg">
                                    <i id="selectedPaymentIcon" class="fas fa-university"></i>
                                </div>
                                <div>
                                    <p class="text-xs text-blue-600 font-bold uppercase tracking-wider mb-0.5">Metode Terpilih</p>
                                    <p class="font-bold text-gray-800" id="selectedPaymentLabel">Transfer Bank</p>
                                </div>
                            </div>
                            <span class="text-blue-600 text-sm font-medium flex items-center gap-1">
                                Ubah <i class="fas fa-chevron-down"></i>
                            </span>
                        </div>
                    </div>

                    <!-- 3. Jasa Pengiriman -->
                    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                        <h2 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                            <span class="bg-blue-600 text-white w-6 h-6 rounded-full flex items-center justify-center text-xs">3</span>
                            Jasa Pengiriman
                        </h2>

                        <input type="hidden" name="jasa_pengiriman" id="shippingInput" required>
                        <input type="hidden" name="ongkos_kirim" id="ongkirInput" value="0">

                        <!-- Tombol Buka Modal Pengiriman -->
                        <div id="shippingDisplay" class="bg-white border border-gray-300 rounded-xl p-4 flex justify-between items-center cursor-pointer hover:border-blue-500 hover:shadow-md transition-all group" onclick="openModal('shippingModal')">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center text-gray-500 group-hover:bg-blue-100 group-hover:text-blue-600 transition">
                                    <i class="fas fa-truck text-xl"></i>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-700 group-hover:text-gray-900 transition">Pilih Jasa Pengiriman</p>
                                    <p class="text-xs text-gray-500">JNE, J&T, GoSend, SiCepat</p>
                                </div>
                            </div>
                            <div class="text-gray-400 group-hover:text-blue-600 transition flex items-center gap-2">
                                <span class="text-sm font-medium">Lihat Semua</span>
                                <i class="fas fa-chevron-right text-xs"></i>
                            </div>
                        </div>

                        <!-- Tampilan Setelah Pengiriman Dipilih -->
                        <div id="shippingSelected" class="hidden bg-blue-50 border border-blue-500 rounded-xl p-4 flex justify-between items-center cursor-pointer hover:bg-blue-100 transition shadow-sm" onclick="openModal('shippingModal')">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 bg-white rounded-full flex items-center justify-center shadow-sm text-blue-600 text-lg">
                                    <i id="selectedShippingIcon" class="fas fa-truck"></i>
                                </div>
                                <div>
                                    <p class="text-xs text-blue-600 font-bold uppercase tracking-wider mb-0.5">Kurir Terpilih</p>
                                    <p class="font-bold text-gray-800"><span id="selectedShippingLabel">JNE</span> - <span id="selectedShippingPrice">Rp12.000</span></p>
                                </div>
                            </div>
                            <span class="text-blue-600 text-sm font-medium flex items-center gap-1">
                                Ubah <i class="fas fa-chevron-down"></i>
                            </span>
                        </div>
                    </div>

                </div>

                <!-- KOLOM KANAN: Ringkasan -->
                <div class="w-full lg:w-96 flex-shrink-0">
                    <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-6 sticky top-24">
                        <h3 class="text-lg font-bold text-gray-800 mb-4 pb-2 border-b">Ringkasan Pesanan</h3>
                        
                        <div class="space-y-3 mb-4 max-h-60 overflow-y-auto pr-2 custom-scrollbar">
                            <?php foreach($items_checkout as $item): ?>
                            <div class="flex gap-3">
                                <img src="admin/uploads/<?= htmlspecialchars($item['gambar']) ?>" class="w-12 h-12 object-cover rounded bg-gray-100 border border-gray-200" onerror="this.src='https://placehold.co/50'">
                                <div class="flex-grow">
                                    <p class="text-sm font-medium text-gray-800 line-clamp-1"><?= htmlspecialchars($item['nama']) ?></p>
                                    <p class="text-xs text-gray-500"><?= $item['qty'] ?> x Rp<?= number_format($item['harga'],0,',','.') ?></p>
                                </div>
                                <div class="text-sm font-semibold text-gray-700">Rp<?= number_format($item['subtotal'],0,',','.') ?></div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="space-y-2 border-t border-gray-100 pt-4 text-sm text-gray-600">
                            <div class="flex justify-between">
                                <span>Total Harga Barang</span>
                                <span id="subtotal_barang" data-val="<?= $total_belanja ?>">Rp<?= number_format($total_belanja, 0, ',', '.') ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span>Biaya Layanan</span>
                                <span id="biaya_layanan" data-val="<?= $biaya_layanan ?>">Rp<?= number_format($biaya_layanan, 0, ',', '.') ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span>Ongkos Kirim</span>
                                <span id="display_ongkir" class="font-medium text-gray-800">-</span>
                            </div>
                        </div>

                        <div class="flex justify-between items-center mt-4 pt-4 border-t border-gray-200">
                            <span class="font-bold text-gray-800">Total Tagihan</span>
                            <span class="text-xl font-bold text-blue-600" id="display_grand_total">Rp<?= number_format($grand_total_awal, 0, ',', '.') ?></span>
                        </div>

                        <input type="hidden" name="total_harga" id="input_grand_total" value="<?= $grand_total_awal ?>">

                        <button type="submit" id="submitBtn" class="w-full bg-gray-400 text-white font-bold py-3.5 rounded-lg mt-6 cursor-not-allowed transition-all flex justify-center items-center gap-2" disabled>
                            <i class="fas fa-lock"></i> Buat Pesanan
                        </button>
                        <p class="text-xs text-center text-gray-400 mt-2" id="paymentWarning">Lengkapi pembayaran & pengiriman</p>
                    </div>
                </div>

            </div>
        </form>
    </main>

    <!-- MODAL 1: METODE PEMBAYARAN -->
    <div id="paymentModal" class="modal fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
        <div class="modal-content bg-white w-full max-w-lg rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                <h3 class="text-lg font-bold text-gray-800">Pilih Metode Pembayaran</h3>
                <button onclick="closeModal('paymentModal')" class="text-gray-400 hover:text-red-500 transition w-8 h-8 flex items-center justify-center rounded-full hover:bg-red-50"><i class="fas fa-times text-xl"></i></button>
            </div>
            <div class="p-6 overflow-y-auto custom-scrollbar space-y-6">
                <!-- Group Transfer -->
                <div>
                    <h4 class="text-xs font-bold text-gray-500 uppercase mb-3 ml-1">Transfer Virtual Account</h4>
                    <div class="space-y-3">
                        <label class="option-label relative flex items-center p-4 border border-gray-200 rounded-xl cursor-pointer hover:border-blue-400 transition" onclick="highlightOption(this)">
                            <input type="radio" name="temp_payment" value="Transfer Bank - BCA" class="sr-only" onchange="selectPayment('Transfer Bank - BCA', 'fa-university')">
                            <div class="w-10 h-10 bg-white rounded-full flex items-center justify-center shadow-sm text-blue-600 mr-4 border border-gray-100"><i class="fas fa-university"></i></div>
                            <div class="flex-grow"><span class="font-bold text-gray-800 block">Bank BCA</span><span class="text-xs text-gray-500">Cek Otomatis</span></div>
                            <div class="check-indicator text-blue-600"><i class="fas fa-check-circle text-xl"></i></div>
                        </label>
                        <label class="option-label relative flex items-center p-4 border border-gray-200 rounded-xl cursor-pointer hover:border-blue-400 transition" onclick="highlightOption(this)">
                            <input type="radio" name="temp_payment" value="Transfer Bank - BRI" class="sr-only" onchange="selectPayment('Transfer Bank - BRI', 'fa-university')">
                            <div class="w-10 h-10 bg-white rounded-full flex items-center justify-center shadow-sm text-blue-800 mr-4 border border-gray-100"><i class="fas fa-university"></i></div>
                            <div class="flex-grow"><span class="font-bold text-gray-800 block">Bank BRI</span><span class="text-xs text-gray-500">Cek Otomatis</span></div>
                            <div class="check-indicator text-blue-600"><i class="fas fa-check-circle text-xl"></i></div>
                        </label>
                    </div>
                </div>
                <!-- Group E-Wallet & COD -->
                <div>
                    <h4 class="text-xs font-bold text-gray-500 uppercase mb-3 ml-1">Lainnya</h4>
                    <div class="space-y-3">
                        <label class="option-label relative flex items-center p-4 border border-gray-200 rounded-xl cursor-pointer hover:border-blue-400 transition" onclick="highlightOption(this)">
                            <input type="radio" name="temp_payment" value="QRIS" class="sr-only" onchange="selectPayment('QRIS (E-Wallet)', 'fa-qrcode')">
                            <div class="w-10 h-10 bg-white rounded-full flex items-center justify-center shadow-sm text-gray-800 mr-4 border border-gray-100"><i class="fas fa-qrcode"></i></div>
                            <div class="flex-grow"><span class="font-bold text-gray-800 block">QRIS</span><span class="text-xs text-gray-500">Gopay, OVO, Dana, ShopeePay</span></div>
                            <div class="check-indicator text-blue-600"><i class="fas fa-check-circle text-xl"></i></div>
                        </label>
                        <label class="option-label relative flex items-center p-4 border border-gray-200 rounded-xl cursor-pointer hover:border-blue-400 transition" onclick="highlightOption(this)">
                            <input type="radio" name="temp_payment" value="COD" class="sr-only" onchange="selectPayment('Bayar di Tempat (COD)', 'fa-hand-holding-usd')">
                            <div class="w-10 h-10 bg-white rounded-full flex items-center justify-center shadow-sm text-green-600 mr-4 border border-gray-100"><i class="fas fa-hand-holding-usd"></i></div>
                            <div class="flex-grow"><span class="font-bold text-gray-800 block">COD</span><span class="text-xs text-gray-500">Bayar tunai saat sampai</span></div>
                            <div class="check-indicator text-blue-600"><i class="fas fa-check-circle text-xl"></i></div>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL 2: JASA PENGIRIMAN -->
    <div id="shippingModal" class="modal fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4">
        <div class="modal-content bg-white w-full max-w-lg rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
            <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                <h3 class="text-lg font-bold text-gray-800">Pilih Jasa Pengiriman</h3>
                <button onclick="closeModal('shippingModal')" class="text-gray-400 hover:text-red-500 transition w-8 h-8 flex items-center justify-center rounded-full"><i class="fas fa-times text-xl"></i></button>
            </div>
            <div class="p-6 overflow-y-auto custom-scrollbar space-y-4">
                
                <!-- JNE -->
                <label class="option-label relative flex items-center p-4 border border-gray-200 rounded-xl cursor-pointer hover:border-blue-400 transition" onclick="highlightOption(this)">
                    <input type="radio" name="temp_shipping" value="JNE Reguler" class="sr-only" onchange="selectShipping('JNE Reguler', 12000, 'fa-truck')">
                    <div class="w-10 h-10 bg-white rounded-full flex items-center justify-center shadow-sm text-blue-600 mr-4 border border-gray-100"><i class="fas fa-truck"></i></div>
                    <div class="flex-grow">
                        <span class="font-bold text-gray-800 block">JNE Reguler</span>
                        <span class="text-xs text-gray-500">Estimasi 2-3 Hari</span>
                    </div>
                    <div class="text-right mr-3">
                        <span class="font-bold text-gray-700 block">Rp12.000</span>
                    </div>
                    <div class="check-indicator text-blue-600"><i class="fas fa-check-circle text-xl"></i></div>
                </label>

                <!-- J&T -->
                <label class="option-label relative flex items-center p-4 border border-gray-200 rounded-xl cursor-pointer hover:border-blue-400 transition" onclick="highlightOption(this)">
                    <input type="radio" name="temp_shipping" value="J&T Express" class="sr-only" onchange="selectShipping('J&T Express', 15000, 'fa-shipping-fast')">
                    <div class="w-10 h-10 bg-white rounded-full flex items-center justify-center shadow-sm text-red-600 mr-4 border border-gray-100"><i class="fas fa-shipping-fast"></i></div>
                    <div class="flex-grow">
                        <span class="font-bold text-gray-800 block">J&T Express</span>
                        <span class="text-xs text-gray-500">Estimasi 1-2 Hari</span>
                    </div>
                    <div class="text-right mr-3">
                        <span class="font-bold text-gray-700 block">Rp15.000</span>
                    </div>
                    <div class="check-indicator text-blue-600"><i class="fas fa-check-circle text-xl"></i></div>
                </label>

                <!-- GoSend -->
                <label class="option-label relative flex items-center p-4 border border-gray-200 rounded-xl cursor-pointer hover:border-blue-400 transition" onclick="highlightOption(this)">
                    <input type="radio" name="temp_shipping" value="GoSend Instant" class="sr-only" onchange="selectShipping('GoSend Instant', 25000, 'fa-motorcycle')">
                    <div class="w-10 h-10 bg-white rounded-full flex items-center justify-center shadow-sm text-green-600 mr-4 border border-gray-100"><i class="fas fa-motorcycle"></i></div>
                    <div class="flex-grow">
                        <span class="font-bold text-gray-800 block">GoSend Instant</span>
                        <span class="text-xs text-gray-500">Tiba hari ini</span>
                    </div>
                    <div class="text-right mr-3">
                        <span class="font-bold text-gray-700 block">Rp25.000</span>
                    </div>
                    <div class="check-indicator text-blue-600"><i class="fas fa-check-circle text-xl"></i></div>
                </label>

                <!-- SiCepat -->
                <label class="option-label relative flex items-center p-4 border border-gray-200 rounded-xl cursor-pointer hover:border-blue-400 transition" onclick="highlightOption(this)">
                    <input type="radio" name="temp_shipping" value="SiCepat Halu" class="sr-only" onchange="selectShipping('SiCepat Halu', 10000, 'fa-bolt')">
                    <div class="w-10 h-10 bg-white rounded-full flex items-center justify-center shadow-sm text-red-500 mr-4 border border-gray-100"><i class="fas fa-bolt"></i></div>
                    <div class="flex-grow">
                        <span class="font-bold text-gray-800 block">SiCepat Halu</span>
                        <span class="text-xs text-gray-500">Ekonomis 3-4 Hari</span>
                    </div>
                    <div class="text-right mr-3">
                        <span class="font-bold text-gray-700 block">Rp10.000</span>
                    </div>
                    <div class="check-indicator text-blue-600"><i class="fas fa-check-circle text-xl"></i></div>
                </label>

            </div>
        </div>
    </div>

    <script>
        const formatter = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 });
        const subtotal = parseFloat(document.getElementById('subtotal_barang').getAttribute('data-val'));
        const layanan = parseFloat(document.getElementById('biaya_layanan').getAttribute('data-val'));
        
        let selectedOngkir = 0;
        let isPaymentSelected = false;
        let isShippingSelected = false;

        // Modal Controls
        function openModal(id) { document.getElementById(id).classList.add('active'); }
        function closeModal(id) { document.getElementById(id).classList.remove('active'); }
        
        function highlightOption(el) {
            const container = el.closest('.space-y-3') || el.closest('.p-6');
            container.querySelectorAll('.option-label').forEach(opt => opt.classList.remove('selected'));
            el.classList.add('selected');
        }

        // Logic Pilih Pembayaran
        function selectPayment(name, iconClass) {
            document.getElementById('paymentInput').value = name;
            isPaymentSelected = true;

            // Update UI
            document.getElementById('paymentDisplay').classList.add('hidden');
            document.getElementById('paymentSelected').classList.remove('hidden');
            document.getElementById('paymentSelected').classList.add('flex');
            
            document.getElementById('selectedPaymentLabel').innerText = name;
            document.getElementById('selectedPaymentIcon').className = `fas ${iconClass}`;

            checkSubmitButton();
            setTimeout(() => closeModal('paymentModal'), 300);
        }

        // Logic Pilih Pengiriman
        function selectShipping(name, price, iconClass) {
            document.getElementById('shippingInput').value = name;
            document.getElementById('ongkirInput').value = price;
            selectedOngkir = price;
            isShippingSelected = true;

            // Update UI Pengiriman
            document.getElementById('shippingDisplay').classList.add('hidden');
            document.getElementById('shippingSelected').classList.remove('hidden');
            document.getElementById('shippingSelected').classList.add('flex');
            
            document.getElementById('selectedShippingLabel').innerText = name;
            document.getElementById('selectedShippingPrice').innerText = formatter.format(price);
            document.getElementById('selectedShippingIcon').className = `fas ${iconClass}`;

            // Update Ringkasan Harga
            document.getElementById('display_ongkir').innerText = formatter.format(price);
            
            // Recalculate Grand Total
            const grandTotal = subtotal + layanan + selectedOngkir;
            document.getElementById('display_grand_total').innerText = formatter.format(grandTotal);
            document.getElementById('input_grand_total').value = grandTotal;

            checkSubmitButton();
            setTimeout(() => closeModal('shippingModal'), 300);
        }

        // Validasi Tombol Submit
        function checkSubmitButton() {
            const btn = document.getElementById('submitBtn');
            const warning = document.getElementById('paymentWarning');

            if (isPaymentSelected && isShippingSelected) {
                btn.disabled = false;
                btn.classList.remove('bg-gray-400', 'cursor-not-allowed');
                btn.classList.add('bg-blue-600', 'hover:bg-blue-700', 'hover:shadow-lg');
                btn.innerHTML = '<i class="fas fa-check-circle"></i> Buat Pesanan';
                warning.classList.add('hidden');
            } else {
                btn.disabled = true;
                btn.classList.add('bg-gray-400', 'cursor-not-allowed');
                btn.classList.remove('bg-blue-600', 'hover:bg-blue-700', 'hover:shadow-lg');
                btn.innerHTML = '<i class="fas fa-lock"></i> Buat Pesanan';
                
                if (!isShippingSelected) warning.innerText = "Pilih jasa pengiriman dulu";
                else if (!isPaymentSelected) warning.innerText = "Pilih metode pembayaran dulu";
                warning.classList.remove('hidden');
            }
        }

        // Close on outside click
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('active');
            }
        }

        // --- VALIDASI MANUAL (TANPA ALERT) ---
        document.getElementById('checkoutForm').addEventListener('submit', function(e) {
            const nama = document.getElementById('input_nama');
            const hp = document.getElementById('input_hp');
            const alamat = document.getElementById('input_alamat');
            
            const errNama = document.getElementById('error_nama');
            const errHp = document.getElementById('error_hp');
            const errAlamat = document.getElementById('error_alamat');
            
            let isValid = true;

            // Reset Status
            [nama, hp, alamat].forEach(el => el.classList.remove('input-error'));
            [errNama, errHp, errAlamat].forEach(el => el.classList.add('hidden'));

            // Cek Nama
            if (!nama.value.trim()) {
                nama.classList.add('input-error');
                errNama.classList.remove('hidden');
                isValid = false;
            }

            // Cek HP
            if (!hp.value.trim()) {
                hp.classList.add('input-error');
                errHp.classList.remove('hidden');
                isValid = false;
            }

            // Cek Alamat
            if (!alamat.value.trim()) {
                alamat.classList.add('input-error');
                errAlamat.classList.remove('hidden');
                isValid = false;
            }

            if (!isValid) {
                e.preventDefault();
                // Scroll ke error pertama
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        });
    </script>

</body>
</html>