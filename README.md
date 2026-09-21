# Absensi Koperasi Mahakam Jaya

PHP native + Tailwind (CDN) + MySQL. Absen wajib **lokasi (geofence)** dan **foto kamera**.

## Pasang di Laragon
1. Salin folder `absensi-mahakam` ke `C:\laragon\www\`.
2. Start Laragon (Apache + MySQL), lalu import `database.sql` lewat HeidiSQL / phpMyAdmin.
3. Buka `http://localhost/absensi-mahakam` (kamera & GPS hanya jalan di `localhost` atau HTTPS).
4. Login admin: NIP `admin`, sandi `password`. Karyawan contoh: `K001` / `password`. **Ganti sandi segera.**
5. Menu **Lokasi & jam** > "Pakai lokasi saya sekarang" (dari perangkat di kantor) > Simpan.

Jika DB Anda bukan default (root tanpa sandi), ubah `config/app.php`.

## Uji dari HP
Kamera/GPS butuh HTTPS. Di Laragon: klik kanan > Apache > SSL > Enabled, pakai `https://absensi-mahakam.test`
(atau pakai tunnel seperti ngrok / Cloudflare Tunnel). Untuk produksi, wajib pasang SSL.
