-- Jalankan sekali setelah migration_opsi_absensi.sql.
USE absensimhk;

ALTER TABLE absensi
  ADD COLUMN jenis_pulang ENUM('kantor','luar_kantor') NULL AFTER alasan_luar_kantor,
  ADD COLUMN alasan_luar_pulang VARCHAR(255) NULL AFTER jenis_pulang,
  ADD COLUMN keterangan_lembur VARCHAR(500) NULL AFTER lembur,
  ADD COLUMN foto_lembur VARCHAR(255) NULL AFTER keterangan_lembur;
