<?php
require __DIR__ . '/../config/app.php';
header('Content-Type: application/json; charset=utf-8');

function out(bool $ok, string $msg, array $extra = [], int $code = 200): never
{
    http_response_code($code);
    echo json_encode(['ok' => $ok, 'message' => $msg] + $extra, JSON_UNESCAPED_UNICODE);
    exit;
}

$user = current_user();
if (!$user || $user['role'] !== 'karyawan') out(false, 'Sesi berakhir. Silakan masuk kembali.', [], 401);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') out(false, 'Metode tidak valid.', [], 405);
if (!csrf_verify($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) out(false, 'Token keamanan tidak valid. Muat ulang halaman.', [], 419);

$in = json_decode((string)file_get_contents('php://input'), true);
if (!is_array($in)) out(false, 'Data tidak valid.', [], 400);

$type = $in['type'] ?? '';
if (!in_array($type, ['masuk', 'pulang'], true)) out(false, 'Jenis absen tidak valid.', [], 400);

// ---- Lokasi (wajib) ----
foreach (['lat', 'lng', 'akurasi'] as $k) {
    if (!isset($in[$k]) || !is_numeric($in[$k])) out(false, 'Lokasi wajib diaktifkan untuk absen.', [], 422);
}
$lat = (float)$in['lat'];
$lng = (float)$in['lng'];
$akurasi = (int)round((float)$in['akurasi']);
if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) out(false, 'Koordinat tidak valid.', [], 422);
if ($akurasi > MAX_AKURASI_GPS) out(false, "Akurasi GPS terlalu rendah (±{$akurasi} m). Pindah ke area terbuka lalu coba lagi.", [], 422);

$p = pengaturan();
$jarak = (int)round(jarak_meter($lat, $lng, (float)$p['lat'], (float)$p['lng']));
if ($jarak > (int)$p['radius_m']) {
    out(false, "Anda berada {$jarak} m dari kantor. Batas absen {$p['radius_m']} m.", ['jarak' => $jarak], 422);
}

// ---- Foto (wajib) ----
$foto = (string)($in['foto'] ?? '');
if (!preg_match('#^data:image/jpeg;base64,#', $foto)) out(false, 'Foto bukti wajib diambil dari kamera.', [], 422);
$bin = base64_decode(substr($foto, strpos($foto, ',') + 1), true);
if ($bin === false || strlen($bin) < 5000 || strlen($bin) > 3 * 1024 * 1024) out(false, 'Ukuran foto tidak valid.', [], 422);
$info = @getimagesizefromstring($bin);
if (!$info || $info['mime'] !== 'image/jpeg' || $info[0] < 240 || $info[1] < 240) out(false, 'Foto tidak valid.', [], 422);

$tanggal = date('Y-m-d');
$waktu = date('H:i:s');
$pdo = db();

$st = $pdo->prepare('SELECT * FROM absensi WHERE user_id = ? AND tanggal = ?');
$st->execute([$user['id'], $tanggal]);
$abs = $st->fetch();

if ($type === 'masuk' && $abs) out(false, 'Anda sudah absen masuk hari ini.', [], 409);
if ($type === 'pulang' && !$abs) out(false, 'Anda belum absen masuk hari ini.', [], 409);
if ($type === 'pulang' && $abs['jam_pulang']) out(false, 'Anda sudah absen pulang hari ini.', [], 409);

// ---- Simpan file ----
$dir = dirname(__DIR__) . '/uploads/absensi/' . date('Ym');
if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) out(false, 'Gagal menyiapkan folder penyimpanan.', [], 500);
$fname = $user['id'] . '_' . $tanggal . '_' . $type . '_' . bin2hex(random_bytes(6)) . '.jpg';
if (file_put_contents($dir . '/' . $fname, $bin) === false) out(false, 'Gagal menyimpan foto.', [], 500);
$rel = 'uploads/absensi/' . date('Ym') . '/' . $fname;

try {
    if ($type === 'masuk') {
        $batas = strtotime($tanggal . ' ' . $p['jam_masuk']) + ((int)$p['toleransi_menit'] * 60);
        $status = time() > $batas ? 'terlambat' : 'hadir';
        $pdo->prepare('INSERT INTO absensi (user_id, tanggal, jam_masuk, foto_masuk, lat_masuk, lng_masuk, akurasi_masuk, jarak_masuk, status)
                       VALUES (?,?,?,?,?,?,?,?,?)')
            ->execute([$user['id'], $tanggal, $waktu, $rel, $lat, $lng, $akurasi, $jarak, $status]);
        out(true, $status === 'terlambat' ? 'Absen masuk tercatat (terlambat).' : 'Absen masuk tercatat. Selamat bekerja!', ['jam' => jam($waktu), 'status' => $status]);
    }

    $upd = $pdo->prepare('UPDATE absensi SET jam_pulang=?, foto_pulang=?, lat_pulang=?, lng_pulang=?, akurasi_pulang=?, jarak_pulang=?
                          WHERE id=? AND jam_pulang IS NULL');
    $upd->execute([$waktu, $rel, $lat, $lng, $akurasi, $jarak, $abs['id']]);
    if ($upd->rowCount() === 0) { @unlink($dir . '/' . $fname); out(false, 'Anda sudah absen pulang hari ini.', [], 409); }
    out(true, 'Absen pulang tercatat. Hati-hati di jalan!', ['jam' => jam($waktu)]);
} catch (PDOException $ex) {
    @unlink($dir . '/' . $fname);
    if ($ex->getCode() === '23000') out(false, 'Anda sudah absen masuk hari ini.', [], 409);
    out(false, 'Terjadi kesalahan server.', [], 500);
}
