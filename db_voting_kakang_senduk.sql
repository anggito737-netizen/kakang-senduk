-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 12 Nov 2026 pada 08.01
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
-- Database: `db_voting_kakang_senduk`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `kontestan`
--

CREATE TABLE `kontestan` (
  `id` int(11) NOT NULL,
  `nomor_peserta` varchar(30) NOT NULL,
  `nama` varchar(120) NOT NULL,
  `kategori` enum('Kakang','Senduk') NOT NULL,
  `foto` text DEFAULT NULL,
  `asal_instansi` varchar(160) DEFAULT NULL,
  `visi` text DEFAULT NULL,
  `misi` text DEFAULT NULL,
  `deskripsi` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `kontestan`
--

INSERT INTO `kontestan` (`id`, `nomor_peserta`, `nama`, `kategori`, `foto`, `asal_instansi`, `visi`, `misi`, `deskripsi`) VALUES
(1, '1', 'moreno', 'Kakang', 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSoc4naXbbD0JU9U5wR9VE9jXpWX5va9zWSlb0TPSfgTg&s', 'SMAN 2Ponorogo', '0', '0', '0');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pembayaran_vote`
--

CREATE TABLE `pembayaran_vote` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `kontestan_id` int(11) NOT NULL,
  `periode_id` int(11) NOT NULL,
  `kategori` enum('Kakang','Senduk') NOT NULL,
  `jumlah_suara` int(10) UNSIGNED NOT NULL,
  `total_harga` bigint(20) UNSIGNED NOT NULL,
  `pesan_dukungan` varchar(500) DEFAULT NULL,
  `status` enum('Menunggu','Disetujui','Ditolak') NOT NULL DEFAULT 'Menunggu',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `diverifikasi_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `periode_voting`
--

CREATE TABLE `periode_voting` (
  `id` int(11) NOT NULL,
  `nama_periode` varchar(120) NOT NULL,
  `tanggal_mulai` datetime NOT NULL,
  `tanggal_selesai` datetime NOT NULL,
  `status` enum('Aktif','Nonaktif') NOT NULL DEFAULT 'Nonaktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nama` varchar(120) NOT NULL,
  `email` varchar(190) NOT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','pemilih') NOT NULL DEFAULT 'pemilih'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `nama`, `email`, `no_hp`, `password`, `role`) VALUES
(1, 'Administrator', 'admin@kakangsenduk.test', '08123456789', '$2y$10$pCMaAPNvydCkRK5x6vdnL.dpHg1ZPEpIDQOudOennIJIQFZSgjl1m', 'admin'),
(2, 'moza', 'moza@gmail.com', '0897777666', '$2y$10$JlNanTWseptzDZOBvFMv4eLW4AOu4vXvN1P9KcFr2wRJaNEkgrHaa', 'pemilih'),
(3, 'anggito abimanyu', 'anggito737@gmail.com', '089677358024', '$2y$10$f22F4cyOO8pmmQXMUh4WUOcDk0Zvc27j/K12XSumiXCBkB.ckyuq.', 'pemilih'),
(4, 'Admin Kakang Senduk', 'admin@kakangsenduk.local', '0000000000', '$2y$10$0csxFmvZ5rKv1x.k9eN4KuTmMohO8OfhxaIVt6qEEuj8ZFhq.3jzm', 'admin');

-- --------------------------------------------------------

--
-- Struktur dari tabel `voting`
--

CREATE TABLE `voting` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `kontestan_id` int(11) NOT NULL,
  `kategori` enum('Kakang','Senduk') NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `jumlah_suara` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `jenis_vote` enum('gratis','berbayar') NOT NULL DEFAULT 'gratis',
  `periode_id` int(11) NOT NULL,
  `pesan_dukungan` varchar(500) DEFAULT NULL,
  `gratis_user_id` int(11) GENERATED ALWAYS AS (case when `jenis_vote` = 'gratis' then `user_id` else NULL end) STORED
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `kontestan`
--
ALTER TABLE `kontestan`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_kontestan_nomor` (`nomor_peserta`);

--
-- Indeks untuk tabel `pembayaran_vote`
--
ALTER TABLE `pembayaran_vote`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pembayaran_status` (`status`,`created_at`),
  ADD KEY `fk_pembayaran_user` (`user_id`),
  ADD KEY `fk_pembayaran_kontestan` (`kontestan_id`),
  ADD KEY `fk_pembayaran_periode` (`periode_id`);

--
-- Indeks untuk tabel `periode_voting`
--
ALTER TABLE `periode_voting`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_periode_status_waktu` (`status`,`tanggal_mulai`,`tanggal_selesai`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indeks untuk tabel `voting`
--
ALTER TABLE `voting`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_voting_gratis_user_kategori_periode` (`gratis_user_id`,`kategori`,`periode_id`),
  ADD KEY `idx_voting_kontestan_periode` (`kontestan_id`,`periode_id`),
  ADD KEY `fk_voting_user` (`user_id`),
  ADD KEY `fk_voting_periode` (`periode_id`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `kontestan`
--
ALTER TABLE `kontestan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `pembayaran_vote`
--
ALTER TABLE `pembayaran_vote`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `periode_voting`
--
ALTER TABLE `periode_voting`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `voting`
--
ALTER TABLE `voting`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `pembayaran_vote`
--
ALTER TABLE `pembayaran_vote`
  ADD CONSTRAINT `fk_pembayaran_kontestan` FOREIGN KEY (`kontestan_id`) REFERENCES `kontestan` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pembayaran_periode` FOREIGN KEY (`periode_id`) REFERENCES `periode_voting` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pembayaran_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `voting`
--
ALTER TABLE `voting`
  ADD CONSTRAINT `fk_voting_kontestan` FOREIGN KEY (`kontestan_id`) REFERENCES `kontestan` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_voting_periode` FOREIGN KEY (`periode_id`) REFERENCES `periode_voting` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_voting_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
