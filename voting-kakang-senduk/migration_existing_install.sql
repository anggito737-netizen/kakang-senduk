-- Migration for the earlier schema used by this project.
-- Back up db_voting_kakang_senduk before running this file.
USE db_voting_kakang_senduk;

ALTER TABLE users
  MODIFY nama VARCHAR(120) NOT NULL,
  MODIFY email VARCHAR(190) NOT NULL;

ALTER TABLE kontestan
  MODIFY nomor_peserta VARCHAR(30) NOT NULL,
  MODIFY nama VARCHAR(120) NOT NULL,
  MODIFY foto TEXT NULL,
  MODIFY asal_instansi VARCHAR(160) NULL,
  ADD UNIQUE KEY uq_kontestan_nomor (nomor_peserta);

ALTER TABLE periode_voting
  MODIFY nama_periode VARCHAR(120) NOT NULL,
  ADD COLUMN created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ADD INDEX idx_periode_status_waktu (status, tanggal_mulai, tanggal_selesai);

-- Keep historic ballots visible in their own non-active period.
INSERT INTO periode_voting (nama_periode, tanggal_mulai, tanggal_selesai, status)
SELECT 'Data voting sebelum pembaruan', '2000-01-01 00:00:00', '2099-12-31 23:59:59', 'Nonaktif'
WHERE EXISTS (SELECT 1 FROM voting)
  AND NOT EXISTS (
    SELECT 1 FROM periode_voting
    WHERE nama_periode = 'Data voting sebelum pembaruan'
  );

SET @legacy_period_id = (
  SELECT id FROM periode_voting
  WHERE nama_periode = 'Data voting sebelum pembaruan'
  ORDER BY id LIMIT 1
);

ALTER TABLE voting
  DROP FOREIGN KEY voting_ibfk_1,
  DROP FOREIGN KEY voting_ibfk_2,
  DROP INDEX uniq_user_kategori,
  ADD COLUMN jumlah_suara INT UNSIGNED NOT NULL DEFAULT 1,
  ADD COLUMN jenis_vote ENUM('gratis','berbayar') NOT NULL DEFAULT 'gratis',
  ADD COLUMN periode_id INT NULL,
  ADD COLUMN pesan_dukungan VARCHAR(500) DEFAULT NULL,
  ADD COLUMN gratis_user_id INT GENERATED ALWAYS AS
    (CASE WHEN jenis_vote = 'gratis' THEN user_id ELSE NULL END) STORED;

UPDATE voting
SET periode_id = @legacy_period_id
WHERE periode_id IS NULL;

ALTER TABLE voting
  MODIFY periode_id INT NOT NULL,
  ADD UNIQUE KEY uq_voting_gratis_user_kategori_periode (gratis_user_id, kategori, periode_id),
  ADD KEY idx_voting_kontestan_periode (kontestan_id, periode_id),
  ADD CONSTRAINT fk_voting_user
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  ADD CONSTRAINT fk_voting_kontestan
    FOREIGN KEY (kontestan_id) REFERENCES kontestan(id) ON DELETE CASCADE,
  ADD CONSTRAINT fk_voting_periode
    FOREIGN KEY (periode_id) REFERENCES periode_voting(id) ON DELETE CASCADE;

CREATE TABLE IF NOT EXISTS pembayaran_vote (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  kontestan_id INT NOT NULL,
  periode_id INT NOT NULL,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
