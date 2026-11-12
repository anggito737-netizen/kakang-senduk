CREATE DATABASE IF NOT EXISTS db_voting_kakang_senduk
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE db_voting_kakang_senduk;

CREATE TABLE IF NOT EXISTS users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  no_hp VARCHAR(20) NOT NULL,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin','pemilih') NOT NULL DEFAULT 'pemilih',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS kontestan (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nomor_peserta VARCHAR(30) NOT NULL UNIQUE,
  nama VARCHAR(120) NOT NULL,
  kategori ENUM('Kakang','Senduk') NOT NULL,
  foto TEXT,
  asal_instansi VARCHAR(160) NOT NULL,
  visi TEXT NOT NULL,
  misi TEXT NOT NULL,
  deskripsi TEXT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS periode_voting (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nama_periode VARCHAR(120) NOT NULL,
  tanggal_mulai DATETIME NOT NULL,
  tanggal_selesai DATETIME NOT NULL,
  status ENUM('Aktif','Nonaktif') NOT NULL DEFAULT 'Nonaktif',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_periode_status_waktu (status, tanggal_mulai, tanggal_selesai)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS voting (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  kontestan_id BIGINT UNSIGNED NOT NULL,
  kategori ENUM('Kakang','Senduk') NOT NULL,
  jumlah_suara INT UNSIGNED NOT NULL DEFAULT 1,
  jenis_vote ENUM('gratis','berbayar') NOT NULL,
  periode_id BIGINT UNSIGNED NOT NULL,
  pesan_dukungan VARCHAR(500) DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  gratis_user_id BIGINT UNSIGNED GENERATED ALWAYS AS
    (CASE WHEN jenis_vote = 'gratis' THEN user_id ELSE NULL END) STORED,
  UNIQUE KEY uq_voting_gratis_user_kategori_periode (gratis_user_id, kategori, periode_id),
  INDEX idx_voting_kontestan_periode (kontestan_id, periode_id),
  CONSTRAINT fk_voting_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_voting_kontestan FOREIGN KEY (kontestan_id) REFERENCES kontestan(id) ON DELETE CASCADE,
  CONSTRAINT fk_voting_periode FOREIGN KEY (periode_id) REFERENCES periode_voting(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS pembayaran_vote (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  kontestan_id BIGINT UNSIGNED NOT NULL,
  periode_id BIGINT UNSIGNED NOT NULL,
  kategori ENUM('Kakang','Senduk') NOT NULL,
  jumlah_suara INT UNSIGNED NOT NULL,
  total_harga BIGINT UNSIGNED NOT NULL,
  pesan_dukungan VARCHAR(500) DEFAULT NULL,
  status ENUM('Menunggu','Disetujui','Ditolak') NOT NULL DEFAULT 'Menunggu',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  diverifikasi_at DATETIME DEFAULT NULL,
  INDEX idx_pembayaran_status (status, created_at),
  CONSTRAINT fk_pembayaran_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_pembayaran_kontestan FOREIGN KEY (kontestan_id) REFERENCES kontestan(id) ON DELETE CASCADE,
  CONSTRAINT fk_pembayaran_periode FOREIGN KEY (periode_id) REFERENCES periode_voting(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Periode contoh untuk membantu instalasi; aktifkan dari panel admin setelah
-- tanggal disesuaikan dengan jadwal penyelenggaraan.
INSERT INTO periode_voting (nama_periode, tanggal_mulai, tanggal_selesai, status)
SELECT 'Pemilihan Kakang Senduk 2026', '2026-01-01 00:00:00', '2026-12-31 23:59:59', 'Nonaktif'
WHERE NOT EXISTS (SELECT 1 FROM periode_voting);
