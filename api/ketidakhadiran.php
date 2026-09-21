<?php
require __DIR__ . '/../config/app.php';
header('Content-Type: application/json; charset=utf-8');

function out(bool $ok, string $msg, int $code = 200): never
{
    http_response_code($code);
    echo json_encode(['ok' => $ok, 'message' => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}

$user = current_user();
if (!$user || $user['role'] !== 'karyawan') out(false, 'Sesi berakhir. Silakan masuk kembali.', 401);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') out(false, 'Metode tidak valid.', 405);
if (!csrf_verify($_POST['csrf'] ?? null)) out(false, 'Token keamanan tidak valid. Muat ulang halaman.', 419);

$jenis = (string)($_POST['jenis'] ?? '');
$keterangan = trim((string)($_POST['keterangan'] ?? ''));
if (!in_array($jenis, ['izin', 'sakit'], true)) out(false, 'Jenis keterangan tidak valid.', 422);
if (mb_strlen($keterangan) < 5 || mb_strlen($keterangan) > 500) out(false, 'Keterangan wajib diisi 5–500 karakter.', 422);

$pdo = db();
$tanggal = date('Y-m-d');
$st = $pdo->prepare('SELECT id FROM absensi WHERE user_id=? AND tanggal=?');
$st->execute([$user['id'], $tanggal]);
if ($st->fetch()) out(false, 'Anda sudah melakukan absensi hari ini.', 409);

try {
    $pdo->prepare('INSERT INTO ketidakhadiran (user_id, tanggal, jenis, keterangan) VALUES (?,?,?,?)')
        ->execute([$user['id'], $tanggal, $jenis, $keterangan]);
    out(true, ucfirst($jenis) . ' hari ini berhasil dicatat.');
} catch (PDOException $ex) {
    if ($ex->getCode() === '23000') out(false, 'Izin atau sakit hari ini sudah dicatat.', 409);
    out(false, 'Terjadi kesalahan server.', 500);
}
