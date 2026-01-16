-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 16 Jan 2026 pada 15.46
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `bengkel_dbv2`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `detail_pesanan`
--

CREATE TABLE `detail_pesanan` (
  `id` int(11) NOT NULL,
  `id_pesanan` int(11) NOT NULL,
  `id_produk` int(11) NOT NULL,
  `harga_satuan` decimal(15,2) NOT NULL,
  `kuantitas` int(11) NOT NULL,
  `subtotal` decimal(15,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `detail_pesanan`
--

INSERT INTO `detail_pesanan` (`id`, `id_pesanan`, `id_produk`, `harga_satuan`, `kuantitas`, `subtotal`) VALUES
(7, 5, 11, 40000.00, 1, 40000.00),
(8, 6, 8, 55000.00, 1, 55000.00),
(9, 7, 3, 45000.00, 1, 45000.00),
(10, 7, 6, 53000.00, 1, 53000.00),
(11, 7, 14, 75000.00, 1, 75000.00),
(12, 8, 8, 55000.00, 1, 55000.00),
(13, 8, 11, 40000.00, 1, 40000.00),
(14, 9, 3, 45000.00, 1, 45000.00),
(15, 9, 12, 40000.00, 1, 40000.00),
(16, 10, 8, 55000.00, 1, 55000.00),
(17, 10, 11, 40000.00, 1, 40000.00);

-- --------------------------------------------------------

--
-- Struktur dari tabel `keranjang_tersimpan`
--

CREATE TABLE `keranjang_tersimpan` (
  `id_keranjang` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `id_produk` int(11) NOT NULL,
  `kuantitas` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `keranjang_tersimpan`
--

INSERT INTO `keranjang_tersimpan` (`id_keranjang`, `id_user`, `id_produk`, `kuantitas`) VALUES
(77, 2, 12, 2),
(78, 2, 14, 1),
(333, 8, 14, 1);

-- --------------------------------------------------------

--
-- Struktur dari tabel `pesanan`
--

CREATE TABLE `pesanan` (
  `id_pesanan` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `tanggal_pesanan` datetime DEFAULT current_timestamp(),
  `total_harga` decimal(15,2) NOT NULL,
  `ongkos_kirim` decimal(15,2) DEFAULT NULL,
  `alamat_pengiriman` text NOT NULL,
  `jasa_pengiriman` varchar(50) DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  `metode_pembayaran` varchar(50) NOT NULL,
  `status_pesanan` varchar(50) DEFAULT 'Menunggu Pembayaran',
  `bukti_pembayaran` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `pesanan`
--

INSERT INTO `pesanan` (`id_pesanan`, `id_user`, `tanggal_pesanan`, `total_harga`, `ongkos_kirim`, `alamat_pengiriman`, `jasa_pengiriman`, `catatan`, `metode_pembayaran`, `status_pesanan`, `bukti_pembayaran`) VALUES
(5, 8, '2026-01-16 17:42:45', 57000.00, NULL, 'Penerima: fadhilahakbar (086509998822)\nAlamat: jl.jbhbj', NULL, 'wkwowkok', 'Transfer Bank - BCA', 'Menunggu Pembayaran', NULL),
(6, 8, '2026-01-16 17:44:49', 72000.00, NULL, 'Penerima: fadhilahakbar (086509998822)\nAlamat: jl.nhbbhb', NULL, 'wkwowkok', 'QRIS (Gopay/OVO/Dana)', 'Diproses', NULL),
(7, 8, '2026-01-16 17:51:54', 115000.00, NULL, 'Penerima: fadhilahakbar (086509998822)\nAlamat: jl.njh', NULL, 'wkwowkok', 'QRIS (Gopay/OVO/Dana)', 'Diproses', NULL),
(8, 8, '2026-01-16 21:31:34', 109000.00, 12000.00, 'Penerima: fadhilahakbar (086509998822)\nAlamat: jl.jhghj', 'JNE Reguler', '', 'Transfer Bank - BCA', 'Diproses', NULL),
(9, 8, '2026-01-16 21:33:03', 102000.00, 15000.00, 'Penerima: fadhilahakbar (086509998822)\nAlamat: jl.nkhghk', 'J&T Express', '', 'Transfer Bank - BCA', 'Diproses', NULL),
(10, 8, '2026-01-16 21:37:09', 54000.00, 12000.00, 'Penerima: fadhilahakbar (086509998822)\nAlamat: jl.khghg', 'JNE Reguler', 'wkwowkok', 'QRIS (E-Wallet)', 'Diproses', NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `produk`
--

CREATE TABLE `produk` (
  `id` int(11) NOT NULL,
  `nama` varchar(200) NOT NULL,
  `kategori` varchar(50) DEFAULT NULL,
  `deskripsi` text DEFAULT NULL,
  `harga` decimal(10,2) NOT NULL,
  `stok` int(11) NOT NULL DEFAULT 0,
  `gambar` varchar(255) DEFAULT 'default.jpg',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `produk`
--

INSERT INTO `produk` (`id`, `nama`, `kategori`, `deskripsi`, `harga`, `stok`, `gambar`, `created_at`) VALUES
(3, 'Ahm Oil Mpx 2 800 Ml 10w30', 'Oli', 'ini produk ok banget', 45000.00, 98, '686cdcb5df4f4.jpeg', '2025-07-08 08:54:13'),
(4, 'Yamalube Matic 20W-40 Oli Motor [0.8 L]', 'Oli', '', 46000.00, 70, '686cf564b72eb.jpeg', '2025-07-08 10:39:32'),
(5, 'Federal Oil Ultratec 20W-50 [0.8L]', NULL, '', 49900.00, 98, '686e70c63ee52.jpeg', '2025-07-09 13:38:14'),
(6, 'Pertamina Oli Enduro 4T 20W-50', NULL, '', 53000.00, 87, '686e720f0735e.jpeg', '2025-07-09 13:43:43'),
(7, 'Oli Yamalube Sport 10W-40 4T JASO MA2 1L', NULL, '', 65000.00, 99, '686e741ed57d3.jpeg', '2025-07-09 13:52:30'),
(8, 'Ban Dalam 90/90-14 Atau 100/80-14 Irc Motor Matic Vario Beat Belakang', NULL, '', 55000.00, 90, '686e74ac8bb41.jpeg', '2025-07-09 13:54:52'),
(9, 'Oli Repsol Mxr Matic 10W-30 Api Sl / Jaso - Mb Motor Matik 1L', NULL, '', 65000.00, 78, '686e751190aa9.jpeg', '2025-07-09 13:56:33'),
(10, 'Ban Matic IRC 90/90-14 Tubeless', NULL, '', 280000.00, 67, '686e77281ceec.jpg', '2025-07-09 14:05:28'),
(11, 'Ban Dalam Motor Swallow 250/275-17 70/90-17 80/90-17', NULL, '', 40000.00, 73, '686e7821a52e0.jpeg', '2025-07-09 14:09:37'),
(12, 'Ban Dalam Motor SWALLOW 275/300/10', NULL, '', 40000.00, 82, '686e796234415.jpeg', '2025-07-09 14:14:58'),
(13, 'Karet Ban Harga Per Ikat Panjang +/- 15 Meter', 'Ban', '', 15000.00, 100, '686e7a74aca5b.jpg', '2025-07-09 14:19:32'),
(14, 'Lampu Motor Led Hs1, 12v7/8w White (Motor Sport)', NULL, '', 75000.00, 54, '686e7b26412a9.jpg', '2025-07-09 14:22:30');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id_user` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `no_telp` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(150) DEFAULT NULL,
  `role` enum('user','admin') NOT NULL DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id_user`, `username`, `no_telp`, `password`, `nama_lengkap`, `role`, `created_at`) VALUES
(1, 'admin', NULL, '$2y$10$Sf9puuAsS8v0cTDpx/TU6OmYRKtVmWpRwFdL09Xuqc.h0J6xg/tTm', 'Admin Utama', 'admin', '2025-07-08 06:52:36'),
(2, 'gabriel', NULL, '$2y$10$.C97MqnwQQoUf/YiJvN6bOd.FwgzF7Q5JNlCT1ADnstqhzW9hSThW', 'gabriel', 'user', '2025-07-08 07:04:37'),
(3, 'udin', NULL, '$2y$10$RE11LOSwP3s.dNF5.egV4.F1GifJymVbZDisdCBQTq7WV0oVs8enO', 'awa', 'user', '2025-07-09 15:36:38'),
(4, 'fadilbau', NULL, '$2y$10$qEn91HTzt3NF06g70D1f0ur6JMy5paGcf0CuA0drLRSpSHwCsVhB2', 'fadil', 'user', '2025-07-14 07:39:40'),
(5, 'afgan', NULL, '$2y$10$sNm7lNRm5q1cm4/H8IAOFewwPEfwgZcca8kM.iCDtMHq9jLgWZ2bi', 'afgan bintang', 'user', '2025-07-17 07:33:50'),
(8, 'barr', '086509998822', '$2y$10$JxqbgNypy15bRKjuz9klReVK1MCitVYhWVNdfy7FgVbtXDqpEQUni', 'fadhilahakbar', 'user', '2026-01-14 18:27:39');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `detail_pesanan`
--
ALTER TABLE `detail_pesanan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_detail_pesanan` (`id_pesanan`),
  ADD KEY `fk_detail_produk` (`id_produk`);

--
-- Indeks untuk tabel `keranjang_tersimpan`
--
ALTER TABLE `keranjang_tersimpan`
  ADD PRIMARY KEY (`id_keranjang`),
  ADD UNIQUE KEY `user_produk_unik` (`id_user`,`id_produk`),
  ADD KEY `id_user_keranjang` (`id_user`),
  ADD KEY `id_produk_keranjang` (`id_produk`);

--
-- Indeks untuk tabel `pesanan`
--
ALTER TABLE `pesanan`
  ADD PRIMARY KEY (`id_pesanan`),
  ADD KEY `fk_pesanan_users` (`id_user`);

--
-- Indeks untuk tabel `produk`
--
ALTER TABLE `produk`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `detail_pesanan`
--
ALTER TABLE `detail_pesanan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT untuk tabel `keranjang_tersimpan`
--
ALTER TABLE `keranjang_tersimpan`
  MODIFY `id_keranjang` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=334;

--
-- AUTO_INCREMENT untuk tabel `pesanan`
--
ALTER TABLE `pesanan`
  MODIFY `id_pesanan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT untuk tabel `produk`
--
ALTER TABLE `produk`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `detail_pesanan`
--
ALTER TABLE `detail_pesanan`
  ADD CONSTRAINT `fk_detail_pesanan` FOREIGN KEY (`id_pesanan`) REFERENCES `pesanan` (`id_pesanan`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_detail_produk` FOREIGN KEY (`id_produk`) REFERENCES `produk` (`id`) ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `keranjang_tersimpan`
--
ALTER TABLE `keranjang_tersimpan`
  ADD CONSTRAINT `keranjang_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `keranjang_ibfk_2` FOREIGN KEY (`id_produk`) REFERENCES `produk` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `pesanan`
--
ALTER TABLE `pesanan`
  ADD CONSTRAINT `fk_pesanan_users` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
