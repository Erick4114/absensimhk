-- Absensi Koperasi Mahakam Jaya
-- Import lewat phpMyAdmin / HeidiSQL (Laragon) atau: mysql -u root < database.sql
CREATE DATABASE IF NOT EXISTS absensi_mahakam CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE absensi_mahakam;

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nip VARCHAR(30) NOT NULL UNIQUE,
  nama VARCHAR(100) NOT NULL,
  jabatan VARCHAR(80) NOT NULL DEFAULT 'Staf',
  role ENUM('admin','karyawan') NOT NULL DEFAULT 'karyawan',
  password VARCHAR(255) NOT NULL,
  aktif TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS absensi (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  tanggal DATE NOT NULL,
  jam_masuk TIME NULL,
  foto_masuk VARCHAR(255) NULL,
  lat_masuk DECIMAL(10,7) NULL,
  lng_masuk DECIMAL(10,7) NULL,
  akurasi_masuk SMALLINT UNSIGNED NULL,
  jarak_masuk INT UNSIGNED NULL,
  jam_pulang TIME NULL,
  foto_pulang VARCHAR(255) NULL,
  lat_pulang DECIMAL(10,7) NULL,
  lng_pulang DECIMAL(10,7) NULL,
  akurasi_pulang SMALLINT UNSIGNED NULL,
  jarak_pulang INT UNSIGNED NULL,
  status ENUM('hadir','terlambat') NOT NULL DEFAULT 'hadir',
  UNIQUE KEY uq_user_tanggal (user_id, tanggal),
  KEY idx_tanggal (tanggal),
  CONSTRAINT fk_absensi_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS pengaturan (
  id TINYINT UNSIGNED PRIMARY KEY DEFAULT 1,
  nama_kantor VARCHAR(120) NOT NULL,
  lat DECIMAL(10,7) NOT NULL,
  lng DECIMAL(10,7) NOT NULL,
  radius_m SMALLINT UNSIGNED NOT NULL DEFAULT 100,
  jam_masuk TIME NOT NULL DEFAULT '08:00:00',
  jam_pulang TIME NOT NULL DEFAULT '16:00:00',
  toleransi_menit SMALLINT UNSIGNED NOT NULL DEFAULT 10
) ENGINE=InnoDB;

-- Koordinat awal = pusat Samarinda. GANTI lewat menu "Lokasi & Jam" setelah login admin.
INSERT INTO pengaturan (id, nama_kantor, lat, lng, radius_m, jam_masuk, jam_pulang, toleransi_menit)
VALUES (1, 'Kantor Koperasi Mahakam Jaya', -0.4948320, 117.1436150, 100, '08:00:00', '16:00:00', 10)
ON DUPLICATE KEY UPDATE id = id;

-- Akun awal. Kata sandi keduanya: password  (WAJIB diganti setelah login pertama)
INSERT INTO users (nip, nama, jabatan, role, password) VALUES
('admin',   'Administrator',  'Admin Koperasi', 'admin',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('K001',    'Budi Santoso',   'Staf Simpan Pinjam', 'karyawan', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('K002',    'Siti Rahmawati', 'Kasir',          'karyawan', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi')
ON DUPLICATE KEY UPDATE nip = nip;
