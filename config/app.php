<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}
date_default_timezone_set('Asia/Makassar'); // WITA (Kaltim)

// ---- Konfigurasi database (default Laragon) ----
const DB_HOST = '127.0.0.1';
const DB_NAME = 'absensimhk';
const DB_USER = 'root';
const DB_PASS = '';

const APP_NAME = 'Absensi Mahakam Jaya';
const MAX_AKURASI_GPS = 150; // meter. Akurasi GPS lebih buruk dari ini ditolak.

// BASE_URL otomatis: jalan di http://localhost/absensi-mahakam maupun http://absensi-mahakam.test
$docRoot = rtrim(str_replace('\\', '/', (string)(realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: '')), '/');
$appRoot = str_replace('\\', '/', dirname(__DIR__));
define('BASE_URL', rtrim(substr($appRoot, strlen($docRoot)), '/'));

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
    }
    return $pdo;
}

function e(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function url(string $path = ''): string { return BASE_URL . '/' . ltrim($path, '/'); }
function redirect(string $path): never { header('Location: ' . url($path)); exit; }

// ---- CSRF ----
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">'; }
function csrf_verify(?string $t): bool { return is_string($t) && hash_equals($_SESSION['csrf'] ?? '', $t); }
function csrf_or_die(): void
{
    if (!csrf_verify($_POST['csrf'] ?? null)) { http_response_code(419); exit('Token keamanan tidak valid. Muat ulang halaman.'); }
}

// ---- Auth ----
function current_user(): ?array
{
    static $cache = false;
    if ($cache !== false) return $cache;
    if (empty($_SESSION['uid'])) return $cache = null;
    $st = db()->prepare('SELECT id, nip, nama, jabatan, role FROM users WHERE id = ? AND aktif = 1');
    $st->execute([$_SESSION['uid']]);
    return $cache = ($st->fetch() ?: null);
}

function require_login(?string $role = null): array
{
    $u = current_user();
    if (!$u) redirect('login.php');
    if ($role && $u['role'] !== $role) redirect($u['role'] === 'admin' ? 'admin/index.php' : 'dashboard.php');
    return $u;
}

// ---- Flash ----
function flash(string $type, string $msg): void { $_SESSION['flash'] = [$type, $msg]; }
function flash_get(): ?array { $f = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $f; }

// ---- Pengaturan & util ----
function pengaturan(): array
{
    static $p = null;
    return $p ??= db()->query('SELECT * FROM pengaturan WHERE id = 1')->fetch();
}

function jarak_meter(float $lat1, float $lng1, float $lat2, float $lng2): float
{
    $r = 6371000.0;
    $a = deg2rad($lat2 - $lat1);
    $b = deg2rad($lng2 - $lng1);
    $h = sin($a / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($b / 2) ** 2;
    return 2 * $r * asin(min(1, sqrt($h)));
}

function tgl_id(string $date, bool $hari = true, bool $singkat = false): string
{
    $H = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    $B = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $t = strtotime($date);
    $bln = $singkat ? substr($B[(int)date('n', $t)], 0, 3) : $B[(int)date('n', $t)];
    return ($hari ? $H[(int)date('w', $t)] . ', ' : '') . date('j', $t) . ' ' . $bln . ' ' . date('Y', $t);
}

function jam(?string $t): string { return $t ? substr($t, 0, 5) : '–'; }
function inisial(string $nama): string
{
    $w = preg_split('/\s+/', trim($nama));
    return strtoupper(mb_substr($w[0], 0, 1) . (isset($w[1]) ? mb_substr($w[1], 0, 1) : ''));
}

function badge(string $status): string
{
    $map = [
        'terlambat' => ['bg-gold-100 text-gold-800', 'Terlambat'],
        'izin' => ['bg-gold-100 text-gold-800', 'Izin'],
        'sakit' => ['bg-clay-100 text-clay-700', 'Sakit'],
        'luar_kantor' => ['bg-river-100 text-river-800', 'Luar kantor'],
        'lembur' => ['bg-river-900 text-white', 'Lembur'],
    ];
    [$class, $label] = $map[$status] ?? ['bg-river-100 text-river-800', 'Tepat waktu'];
    return '<span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold ' . $class . '">' . $label . '</span>';
}

function icon(string $name, string $cls = 'w-5 h-5'): string
{
    $p = [
        'home'    => '<path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>',
        'users'   => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
        'file'    => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>',
        'pin'     => '<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>',
        'clock'   => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
        'logout'  => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>',
        'camera'  => '<path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3z"/><circle cx="12" cy="13" r="3"/>',
        'calendar'=> '<rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>',
        'check'   => '<polyline points="20 6 9 17 4 12"/>',
        'download'=> '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>',
        'x'       => '<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>',
        'plus'    => '<line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>',
    ];
    return '<svg class="' . $cls . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($p[$name] ?? '') . '</svg>';
}

function logo_mark(string $cls = 'w-10 h-10'): string
{
    return '<svg class="' . $cls . '" viewBox="0 0 48 48" aria-hidden="true"><rect width="48" height="48" rx="13" fill="#14776A"/>'
        . '<path d="M8 19c4-4 8-4 12 0s8 4 12 0 6-3 8-2" fill="none" stroke="#E0A526" stroke-width="3" stroke-linecap="round"/>'
        . '<path d="M8 27c4-4 8-4 12 0s8 4 12 0 6-3 8-2" fill="none" stroke="#fff" stroke-width="3" stroke-linecap="round" opacity=".9"/>'
        . '<path d="M8 35c4-4 8-4 12 0s8 4 12 0 6-3 8-2" fill="none" stroke="#fff" stroke-width="3" stroke-linecap="round" opacity=".45"/></svg>';
}
