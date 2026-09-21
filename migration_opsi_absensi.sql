-- Jalankan sekali untuk database yang sudah ada.
USE absensimhk;

ALTER TABLE absensi
  ADD COLUMN jenis_kerja ENUM('kantor','luar_kantor') NOT NULL DEFAULT 'kantor' AFTER status,
  ADD COLUMN alasan_luar_kantor VARCHAR(255) NULL AFTER jenis_kerja,
  ADD COLUMN lembur TINYINT(1) NOT NULL DEFAULT 0 AFTER alasan_luar_kantor,
  ADD COLUMN keterangan VARCHAR(500) NULL AFTER lembur;

CREATE TABLE ketidakhadiran (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  tanggal DATE NOT NULL,
  jenis ENUM('izin','sakit') NOT NULL,
  keterangan VARCHAR(500) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_ketidakhadiran_user_tanggal (user_id, tanggal),
  KEY idx_ketidakhadiran_tanggal (tanggal),
  CONSTRAINT fk_ketidakhadiran_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
