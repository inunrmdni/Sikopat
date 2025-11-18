-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 13 Nov 2025 pada 19.04
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
-- Database: `sikopat`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `chat`
--

CREATE TABLE `chat` (
  `id` int(11) NOT NULL,
  `pengirim_id` int(11) NOT NULL,
  `penerima_id` int(11) NOT NULL,
  `pesan` text DEFAULT NULL,
  `gambar` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `kamar`
--

CREATE TABLE `kamar` (
  `id` int(11) NOT NULL,
  `nomor_kamar` varchar(10) NOT NULL,
  `tipe_kamar` varchar(50) DEFAULT NULL,
  `harga` decimal(12,2) DEFAULT NULL,
  `status` enum('tersedia','terisi','rusak') DEFAULT 'tersedia',
  `keterangan` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `komentar_pengumuman`
--

CREATE TABLE `komentar_pengumuman` (
  `id` int(11) NOT NULL,
  `pengumuman_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `komentar` text NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `log_aktivitas`
--

CREATE TABLE `log_aktivitas` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `aksi` varchar(255) NOT NULL,
  `waktu` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `log_aktivitas`
--

INSERT INTO `log_aktivitas` (`id`, `username`, `aksi`, `waktu`) VALUES
(1, 'admin', 'Menghapus pengumuman ID 12 (isi: cekkkkk...)', '2025-11-09 09:03:25'),
(10, 'admin', 'Menghapus pengumuman ID 26 (isi: haiiiii...)', '2025-11-13 23:47:35'),
(11, 'admin', 'Menghapus pengumuman ID 24 (isi: uuuuu...)', '2025-11-14 00:06:32'),
(12, 'pemilik', 'Menghapus pengumuman ID 27 (isi: tolong yang bersih...)', '2025-11-14 00:22:49'),
(13, 'admin', 'Menghapus pengumuman ID 29 (isi: cinta damai...)', '2025-11-14 00:39:10');

-- --------------------------------------------------------

--
-- Struktur dari tabel `notifikasi`
--

CREATE TABLE `notifikasi` (
  `id` int(11) NOT NULL,
  `id_pengumuman` int(11) NOT NULL,
  `id_pengaduan` int(11) DEFAULT NULL,
  `pesan` text NOT NULL,
  `tanggal` datetime DEFAULT current_timestamp(),
  `status` enum('baru','dibaca') DEFAULT 'baru',
  `jenis` enum('pengumuman','pengaduan','lainnya') DEFAULT 'pengumuman'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `notifikasi`
--

INSERT INTO `notifikasi` (`id`, `id_pengumuman`, `id_pengaduan`, `pesan`, `tanggal`, `status`, `jenis`) VALUES
(17, 19, NULL, '📢 Pengumuman baru: HALOOOOO...', '2025-11-10 18:04:41', 'dibaca', 'pengumuman'),
(42, 23, NULL, 'Ada pengaduan baru dari penghuni ID 3.', '2025-11-11 16:38:41', 'baru', 'pengaduan'),
(43, 23, NULL, 'Status pengaduan Anda telah diubah menjadi <b>diproses</b>.', '2025-11-11 16:39:17', 'baru', 'pengaduan'),
(66, 28, NULL, 'Status pengaduan Anda telah diubah menjadi <b>diproses</b>.', '2025-11-13 21:36:51', 'baru', 'pengaduan'),
(67, 28, NULL, 'Status pengaduan Anda telah diubah menjadi <b>diproses</b>.', '2025-11-13 21:37:00', 'baru', 'pengaduan'),
(68, 28, NULL, 'Status pengaduan Anda telah diubah menjadi <b>diproses</b>.', '2025-11-13 21:37:10', 'baru', 'pengaduan'),
(69, 26, NULL, '💬 K111 memberikan komentar pada pengumuman.', '2025-11-13 21:42:33', 'baru', 'pengumuman'),
(70, 26, NULL, '💬 K111 memberikan komentar pada pengumuman.', '2025-11-13 21:43:04', 'baru', 'pengumuman'),
(71, 29, NULL, '💬 Pengaduan baru dari <b></b> telah diterima dan menunggu ditinjau.', '2025-11-13 21:44:42', 'baru', 'pengaduan'),
(72, 29, NULL, 'Status pengaduan Anda telah diubah menjadi <b>diproses</b>.', '2025-11-13 21:58:57', 'baru', 'pengaduan'),
(73, 27, NULL, '📢 Pengumuman baru: tolong yang bersih...', '2025-11-13 22:56:10', 'baru', 'pengumuman'),
(74, 29, NULL, 'Status pengaduan Anda telah diubah menjadi <b>baru</b>.', '2025-11-13 22:58:31', 'baru', 'pengaduan'),
(75, 29, NULL, 'Status pengaduan Anda telah diubah menjadi <b>diproses</b>.', '2025-11-13 22:58:35', 'baru', 'pengaduan'),
(76, 29, NULL, 'Status pengaduan Anda telah diubah menjadi <b>diproses</b>.', '2025-11-13 23:04:15', 'baru', 'pengaduan'),
(77, 29, NULL, 'Status pengaduan Anda telah diubah menjadi <b>diproses</b>.', '2025-11-13 23:04:20', 'baru', 'pengaduan'),
(78, 29, NULL, 'Status pengaduan Anda telah diubah menjadi <b>selesai</b>.', '2025-11-13 23:04:28', 'baru', 'pengaduan'),
(79, 28, NULL, 'Status pengaduan Anda telah diubah menjadi <b>diproses</b>.', '2025-11-13 23:04:33', 'baru', 'pengaduan'),
(80, 28, NULL, 'Status pengaduan Anda telah diubah menjadi <b>diproses</b>.', '2025-11-13 23:05:53', 'baru', 'pengaduan'),
(81, 29, NULL, 'Status pengaduan Anda telah diubah menjadi <b>diproses</b>.', '2025-11-13 23:05:59', 'baru', 'pengaduan'),
(82, 29, NULL, 'Status pengaduan Anda telah diubah menjadi <b>selesai</b>.', '2025-11-13 23:06:04', 'baru', 'pengaduan'),
(83, 28, NULL, 'Status pengaduan Anda telah diubah menjadi <b>selesai</b>.', '2025-11-13 23:06:08', 'baru', 'pengaduan'),
(84, 29, NULL, 'Status pengaduan Anda telah diubah menjadi <b>diproses</b>.', '2025-11-13 23:15:08', 'baru', 'pengaduan'),
(85, 29, NULL, 'Status pengaduan Anda telah diubah menjadi <b>diproses</b>.', '2025-11-13 23:15:53', 'baru', 'pengaduan'),
(86, 27, NULL, 'Status pengaduan Anda telah diubah menjadi <b>selesai</b>.', '2025-11-13 23:15:59', 'baru', 'pengaduan'),
(87, 25, NULL, 'Status pengaduan Anda telah diubah menjadi <b>diproses</b>.', '2025-11-13 23:16:03', 'baru', 'pengaduan'),
(88, 28, NULL, 'Status pengaduan Anda telah diubah menjadi <b>diproses</b>.', '2025-11-13 23:21:41', 'baru', 'pengaduan'),
(89, 24, NULL, 'Status pengaduan Anda telah diubah menjadi <b>selesai</b>.', '2025-11-13 23:21:46', 'baru', 'pengaduan'),
(90, 23, NULL, 'Status pengaduan Anda telah diubah menjadi <b>diproses</b>.', '2025-11-13 23:21:50', 'baru', 'pengaduan'),
(91, 27, NULL, 'Status pengaduan Anda telah diubah menjadi <b>diproses</b>.', '2025-11-13 23:23:40', 'baru', 'pengaduan'),
(92, 23, NULL, 'Status pengaduan Anda telah diubah menjadi <b>selesai</b>.', '2025-11-13 23:23:47', 'baru', 'pengaduan'),
(94, 0, 28, 'Status pengaduan Anda telah diubah menjadi <b>diproses</b>.', '2025-11-13 23:28:37', 'baru', 'pengaduan'),
(97, 0, 29, 'Status pengaduan Anda telah diubah menjadi <b>selesai</b>.', '2025-11-13 23:28:49', 'baru', 'pengaduan'),
(98, 0, 29, 'Status pengaduan Anda telah diubah menjadi <b>baru</b>.', '2025-11-13 23:29:17', 'baru', 'pengaduan'),
(99, 0, 29, 'Status pengaduan Anda telah diubah menjadi <b>selesai</b>.', '2025-11-13 23:29:44', 'baru', 'pengaduan'),
(100, 0, 29, 'Status pengaduan Anda telah diubah menjadi <b>diproses</b>.', '2025-11-13 23:29:58', 'baru', 'pengaduan'),
(101, 0, 29, 'Status pengaduan Anda telah diubah menjadi <b>diproses</b>.', '2025-11-13 23:32:29', 'baru', 'pengaduan'),
(103, 28, NULL, '📢 Pengumuman baru: ada maling...', '2025-11-14 00:11:31', 'baru', 'pengumuman'),
(104, 29, NULL, '📢 Pengumuman baru: cinta damai...', '2025-11-14 00:22:36', 'baru', 'pengumuman'),
(105, 30, NULL, '📢 Pengumuman baru: awas rampok...', '2025-11-14 00:23:52', 'baru', 'pengumuman'),
(106, 30, NULL, '💬 Pengaduan baru dari <b></b> telah diterima dan menunggu ditinjau.', '2025-11-14 00:31:11', 'baru', 'pengaduan'),
(109, 0, 28, 'Status pengaduan Anda telah diubah menjadi <b>selesai</b>.', '2025-11-14 00:33:25', 'baru', 'pengaduan'),
(110, 31, NULL, '📢 Pengumuman baru: cinta damai\r\n...', '2025-11-14 00:39:58', 'baru', 'pengumuman');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pembayaran`
--

CREATE TABLE `pembayaran` (
  `id` int(11) NOT NULL,
  `tagihan_id` int(11) NOT NULL,
  `bukti` varchar(255) DEFAULT NULL,
  `status` enum('pending','diverifikasi','ditolak') DEFAULT 'pending',
  `created_at` datetime DEFAULT current_timestamp(),
  `total_bayar` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `pengaduan`
--

CREATE TABLE `pengaduan` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `isi` text NOT NULL,
  `gambar` varchar(255) DEFAULT NULL,
  `anonim` tinyint(1) DEFAULT 0,
  `status` enum('baru','diproses','selesai') DEFAULT 'baru',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `pengaduan`
--

INSERT INTO `pengaduan` (`id`, `user_id`, `isi`, `gambar`, `anonim`, `status`, `created_at`) VALUES
(28, NULL, 'kamar banjirrrrrr', NULL, 0, 'selesai', '2025-11-13 21:35:14'),
(29, 22, 'DQSWGQGVYSGYXSY', NULL, 0, 'diproses', '2025-11-13 21:44:42');

-- --------------------------------------------------------

--
-- Struktur dari tabel `penghuni_kamar`
--

CREATE TABLE `penghuni_kamar` (
  `id` int(11) NOT NULL,
  `nama_penghuni` varchar(100) DEFAULT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `id_kamar` int(11) DEFAULT NULL,
  `penghuni_id` int(11) NOT NULL,
  `kamar_id` int(11) NOT NULL,
  `tgl_masuk` date DEFAULT NULL,
  `status` enum('aktif','nonaktif') NOT NULL DEFAULT 'aktif'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `pengumuman`
--

CREATE TABLE `pengumuman` (
  `id` int(11) NOT NULL,
  `judul` varchar(255) DEFAULT NULL,
  `isi` text NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `pengumuman`
--

INSERT INTO `pengumuman` (`id`, `judul`, `isi`, `created_by`, `created_at`) VALUES
(28, 'Keamanann', 'ada maling', 1, '2025-11-14 00:11:31'),
(30, 'Keamanan', 'awas rampok banyak', 1, '2025-11-14 00:23:52'),
(31, 'Kenyamanan', 'cinta damai\r\n', 1, '2025-11-14 00:39:58');

-- --------------------------------------------------------

--
-- Struktur dari tabel `riwayat_login`
--

CREATE TABLE `riwayat_login` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `login_time` datetime DEFAULT current_timestamp(),
  `logout_time` datetime DEFAULT NULL,
  `ip_address` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `tagihan`
--

CREATE TABLE `tagihan` (
  `id` int(11) NOT NULL,
  `penghuni_id` int(11) NOT NULL,
  `kamar_id` int(11) NOT NULL,
  `bulan` varchar(20) DEFAULT NULL,
  `jumlah` decimal(12,2) NOT NULL,
  `status` enum('belum_bayar','lunas') DEFAULT 'belum_bayar',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','pemilik','penghuni') NOT NULL,
  `status` enum('aktif','suspend') DEFAULT 'aktif',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `status`, `created_at`) VALUES
(1, 'admin', 'admin123', 'admin', 'aktif', '2025-10-30 15:33:29'),
(2, 'pemilik', 'pemilik123', 'pemilik', 'aktif', '2025-10-30 17:42:58'),
(22, 'K111', 'K121212', 'penghuni', 'aktif', '2025-11-13 12:52:29');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `chat`
--
ALTER TABLE `chat`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pengirim_id` (`pengirim_id`),
  ADD KEY `penerima_id` (`penerima_id`);

--
-- Indeks untuk tabel `kamar`
--
ALTER TABLE `kamar`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nomor_kamar` (`nomor_kamar`);

--
-- Indeks untuk tabel `komentar_pengumuman`
--
ALTER TABLE `komentar_pengumuman`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pengumuman_id` (`pengumuman_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `log_aktivitas`
--
ALTER TABLE `log_aktivitas`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `notifikasi`
--
ALTER TABLE `notifikasi`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `pembayaran`
--
ALTER TABLE `pembayaran`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tagihan_id` (`tagihan_id`);

--
-- Indeks untuk tabel `pengaduan`
--
ALTER TABLE `pengaduan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `penghuni_kamar`
--
ALTER TABLE `penghuni_kamar`
  ADD PRIMARY KEY (`id`),
  ADD KEY `penghuni_id` (`penghuni_id`),
  ADD KEY `kamar_id` (`kamar_id`);

--
-- Indeks untuk tabel `pengumuman`
--
ALTER TABLE `pengumuman`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indeks untuk tabel `riwayat_login`
--
ALTER TABLE `riwayat_login`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `tagihan`
--
ALTER TABLE `tagihan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `penghuni_id` (`penghuni_id`),
  ADD KEY `kamar_id` (`kamar_id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `chat`
--
ALTER TABLE `chat`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `kamar`
--
ALTER TABLE `kamar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `komentar_pengumuman`
--
ALTER TABLE `komentar_pengumuman`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT untuk tabel `log_aktivitas`
--
ALTER TABLE `log_aktivitas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT untuk tabel `notifikasi`
--
ALTER TABLE `notifikasi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=111;

--
-- AUTO_INCREMENT untuk tabel `pembayaran`
--
ALTER TABLE `pembayaran`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `pengaduan`
--
ALTER TABLE `pengaduan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT untuk tabel `penghuni_kamar`
--
ALTER TABLE `penghuni_kamar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `pengumuman`
--
ALTER TABLE `pengumuman`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT untuk tabel `riwayat_login`
--
ALTER TABLE `riwayat_login`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `tagihan`
--
ALTER TABLE `tagihan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `chat`
--
ALTER TABLE `chat`
  ADD CONSTRAINT `chat_ibfk_1` FOREIGN KEY (`pengirim_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `chat_ibfk_2` FOREIGN KEY (`penerima_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `komentar_pengumuman`
--
ALTER TABLE `komentar_pengumuman`
  ADD CONSTRAINT `komentar_pengumuman_ibfk_1` FOREIGN KEY (`pengumuman_id`) REFERENCES `pengumuman` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `komentar_pengumuman_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `pembayaran`
--
ALTER TABLE `pembayaran`
  ADD CONSTRAINT `pembayaran_ibfk_1` FOREIGN KEY (`tagihan_id`) REFERENCES `tagihan` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `pengaduan`
--
ALTER TABLE `pengaduan`
  ADD CONSTRAINT `pengaduan_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `penghuni_kamar`
--
ALTER TABLE `penghuni_kamar`
  ADD CONSTRAINT `penghuni_kamar_ibfk_1` FOREIGN KEY (`penghuni_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `penghuni_kamar_ibfk_2` FOREIGN KEY (`kamar_id`) REFERENCES `kamar` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `pengumuman`
--
ALTER TABLE `pengumuman`
  ADD CONSTRAINT `pengumuman_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `riwayat_login`
--
ALTER TABLE `riwayat_login`
  ADD CONSTRAINT `riwayat_login_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `tagihan`
--
ALTER TABLE `tagihan`
  ADD CONSTRAINT `tagihan_ibfk_1` FOREIGN KEY (`penghuni_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tagihan_ibfk_2` FOREIGN KEY (`kamar_id`) REFERENCES `kamar` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
