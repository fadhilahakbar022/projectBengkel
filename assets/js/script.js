/*
File: script.js
Deskripsi: Berisi semua fungsi JavaScript untuk interaktivitas di sisi klien.
*/

/**
 * Menampilkan notifikasi popup sementara.
 * @param {string} pesan - Pesan yang akan ditampilkan.
 * @param {boolean} isError - Jika true, notifikasi akan berwarna merah.
 */
function tampilkanNotifikasi(pesan, isError = false) {
    const popup = document.getElementById('notification-popup');
    if (!popup) return;

    popup.textContent = pesan;
    popup.className = 'notification'; // Reset class
    if (isError) {
        popup.classList.add('error');
    }
    popup.classList.add('show');
    
    setTimeout(() => {
        popup.classList.remove('show');
    }, 3000); // Notifikasi hilang setelah 3 detik
}

/**
 * Mengirim permintaan untuk menambah produk ke keranjang via AJAX.
 * @param {number} idProduk - ID produk yang akan ditambahkan.
 * @param {HTMLElement} tombol - Tombol yang diklik.
 */
function tambahKeKeranjang(idProduk, tombol) {
    const teksAsliTombol = tombol.innerHTML;
    tombol.innerHTML = '<i class="fas fa-spinner fa-spin"></i>'; // Tampilkan ikon loading
    tombol.disabled = true;

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
            const cartCount = document.getElementById('cart-count');
            if(cartCount) cartCount.textContent = data.total_keranjang;
            tampilkanNotifikasi(data.pesan, false);
        } else {
            tampilkanNotifikasi(data.pesan, true);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        tampilkanNotifikasi('Terjadi kesalahan. Silakan coba lagi.', true);
    })
    .finally(() => {
        // Kembalikan tombol ke keadaan semula
        tombol.innerHTML = teksAsliTombol;
        tombol.disabled = false;
    });
}

/**
 * Mengisi form edit produk dengan data yang ada.
 * @param {number} id 
 * @param {string} nama 
 * @param {string} deskripsi 
 * @param {number} harga 
 * @param {number} stok 
 * @param {string} gambar 
 */
function editProduk(id, nama, deskripsi, harga, stok, gambar) {
    document.getElementById('id_produk').value = id;
    document.getElementById('nama').value = nama;
    document.getElementById('deskripsi').value = deskripsi;
    document.getElementById('harga').value = harga;
    document.getElementById('stok').value = stok;
    document.getElementById('gambar_lama').value = gambar;

    document.getElementById('form-title').innerText = 'Edit Produk';
    document.getElementById('btn-submit').innerText = 'Simpan Perubahan';
    document.getElementById('btn-cancel').style.display = 'inline-block';

    document.getElementById('form-produk').scrollIntoView({ behavior: 'smooth' });
}

/**
 * Mengisi form edit pengguna dengan data yang ada.
 * @param {number} id 
 * @param {string} nama_lengkap 
 * @param {string} username 
 * @param {string} role 
 */
function editPengguna(id, nama_lengkap, username, role) {
    document.getElementById('id_user').value = id;
    document.getElementById('nama_lengkap').value = nama_lengkap;
    document.getElementById('username').value = username;
    document.getElementById('role').value = role;

    document.getElementById('form-title').innerText = 'Edit Pengguna';
    document.getElementById('btn-submit').innerText = 'Simpan Perubahan';
    document.getElementById('btn-cancel').style.display = 'inline-block';

    document.getElementById('form-pengguna').scrollIntoView({ behavior: 'smooth' });
}
