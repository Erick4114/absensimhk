<?php
require __DIR__ . '/../config/app.php';
$adm = require_login('admin');
$pdo = db();

$dari = $_GET['dari'] ?? date('Y-m-01');
$sampai = $_GET['sampai'] ?? date('Y-m-d');
$uid = (int)($_GET['user'] ?? 0);
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dari)) $dari = date('Y-m-01');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $sampai)) $sampai = date('Y-m-d');

$where = 'a.tanggal BETWEEN ? AND ?';
$args = [$dari, $sampai];
if ($uid) { $where .= ' AND a.user_id = ?'; $args[] = $uid; }

// ---- Ekspor CSV ----
if (isset($_GET['export'])) {
    $st = $pdo->prepare("SELECT a.*, u.nip, u.nama FROM absensi a JOIN users u ON u.id=a.user_id WHERE $where ORDER BY a.tanggal, u.nama");
    $st->execute($args);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="absensi_' . $dari . '_' . $sampai . '.csv"');
    $o = fopen('php://output', 'w');
    fwrite($o, "\xEF\xBB\xBF");
    fputcsv($o, ['Tanggal', 'NIP', 'Nama', 'Jam masuk', 'Jam pulang', 'Status', 'Jarak masuk (m)', 'Lokasi masuk', 'Jarak pulang (m)', 'Lokasi pulang'], ';');
    foreach ($st as $r) {
        fputcsv($o, [
            $r['tanggal'], $r['nip'], $r['nama'], jam($r['jam_masuk']), jam($r['jam_pulang']), $r['status'],
            $r['jarak_masuk'], $r['lat_masuk'] ? $r['lat_masuk'] . ',' . $r['lng_masuk'] : '',
            $r['jarak_pulang'], $r['lat_pulang'] ? $r['lat_pulang'] . ',' . $r['lng_pulang'] : '',
        ], ';');
    }
    exit;
}

$per = 20;
$page = max(1, (int)($_GET['page'] ?? 1));
$cnt = $pdo->prepare("SELECT COUNT(*) FROM absensi a WHERE $where");
$cnt->execute($args);
$total = (int)$cnt->fetchColumn();
$pages = max(1, (int)ceil($total / $per));
$page = min($page, $pages);

$st = $pdo->prepare("SELECT a.*, u.nama, u.nip FROM absensi a JOIN users u ON u.id=a.user_id WHERE $where ORDER BY a.tanggal DESC, a.jam_masuk DESC LIMIT $per OFFSET " . (($page - 1) * $per));
$st->execute($args);
$rows = $st->fetchAll();
$users = $pdo->query("SELECT id, nama FROM users WHERE role='karyawan' ORDER BY nama")->fetchAll();

$title = 'Laporan'; $active = 'laporan';
require __DIR__ . '/../includes/admin_top.php';

$qs = fn(array $x = []) => http_build_query(array_merge(['dari' => $dari, 'sampai' => $sampai, 'user' => $uid ?: ''], $x));
$in = 'rounded-xl border border-river-200 bg-white px-3 py-2 text-sm outline-none focus:border-river-600 focus:ring-4 focus:ring-river-100';

function bukti(array $r, string $k): string
{
    if (!$r['jam_' . $k]) return '<span class="text-river-700">–</span>';
    $foto = url($r['foto_' . $k]);
    $map = 'https://www.google.com/maps?q=' . $r['lat_' . $k] . ',' . $r['lng_' . $k];
    return '<div class="flex items-center gap-3"><img src="' . e($foto) . '" data-full="' . e($foto) . '" alt="Foto ' . $k . '" loading="lazy" class="h-12 w-12 cursor-zoom-in rounded-lg object-cover">'
         . '<div><div class="font-semibold tabnum">' . jam($r['jam_' . $k]) . '</div>'
         . '<a href="' . e($map) . '" target="_blank" rel="noopener" class="text-xs font-semibold text-river-600 hover:underline">' . (int)$r['jarak_' . $k] . ' m · peta</a></div></div>';
}
?>
<div class="mb-6 flex flex-wrap items-end justify-between gap-3">
  <div>
    <h1 class="font-display text-3xl font-bold">Laporan absensi</h1>
    <p class="text-river-700"><?= $total ?> catatan · <?= e(tgl_id($dari, false, true)) ?> – <?= e(tgl_id($sampai, false, true)) ?></p>
  </div>
  <a href="?<?= e($qs(['export' => 1])) ?>" class="inline-flex items-center gap-2 rounded-xl border border-river-200 bg-white px-4 py-2.5 text-sm font-semibold text-river-800 hover:bg-river-50"><?= icon('download', 'w-4 h-4') ?>Unduh CSV</a>
</div>

<form method="get" class="mb-5 flex flex-wrap items-end gap-3 rounded-2xl bg-white p-4 shadow-sm">
  <div><label for="dari" class="mb-1 block text-xs font-semibold text-river-700">Dari</label><input id="dari" type="date" name="dari" value="<?= e($dari) ?>" class="<?= $in ?>"></div>
  <div><label for="sampai" class="mb-1 block text-xs font-semibold text-river-700">Sampai</label><input id="sampai" type="date" name="sampai" value="<?= e($sampai) ?>" class="<?= $in ?>"></div>
  <div><label for="user" class="mb-1 block text-xs font-semibold text-river-700">Karyawan</label>
    <select id="user" name="user" class="<?= $in ?>"><option value="">Semua</option>
      <?php foreach ($users as $u): ?><option value="<?= (int)$u['id'] ?>" <?= $uid === (int)$u['id'] ? 'selected' : '' ?>><?= e($u['nama']) ?></option><?php endforeach; ?>
    </select></div>
  <button class="rounded-xl bg-river-600 px-5 py-2 text-sm font-bold text-white hover:bg-river-700">Terapkan</button>
</form>

<section class="overflow-hidden rounded-2xl bg-white shadow-sm">
  <?php if (!$rows): ?>
    <p class="p-10 text-center text-river-700">Tidak ada data pada rentang ini.</p>
  <?php else: ?>
  <div class="overflow-x-auto">
    <table class="w-full text-left text-sm">
      <thead class="bg-river-50 text-river-700"><tr>
        <th class="px-5 py-2.5 font-semibold">Tanggal</th><th class="px-3 py-2.5 font-semibold">Karyawan</th>
        <th class="px-3 py-2.5 font-semibold">Masuk</th><th class="px-3 py-2.5 font-semibold">Pulang</th><th class="px-3 py-2.5 font-semibold">Status</th>
      </tr></thead>
      <tbody class="divide-y divide-river-50">
      <?php foreach ($rows as $r): ?>
        <tr>
          <td class="whitespace-nowrap px-5 py-3"><?= e(tgl_id($r['tanggal'], true, true)) ?></td>
          <td class="px-3 py-3"><div class="font-semibold"><?= e($r['nama']) ?></div><div class="text-xs text-river-700"><?= e($r['nip']) ?></div></td>
          <td class="px-3 py-3"><?= bukti($r, 'masuk') ?></td>
          <td class="px-3 py-3"><?= bukti($r, 'pulang') ?></td>
          <td class="px-3 py-3"><?= badge($r['status']) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if ($pages > 1): ?>
    <div class="flex items-center justify-between border-t border-river-50 px-5 py-3 text-sm">
      <span class="text-river-700">Halaman <?= $page ?> dari <?= $pages ?></span>
      <div class="flex gap-2">
        <?php if ($page > 1): ?><a class="rounded-lg border border-river-200 px-3 py-1.5 font-semibold hover:bg-river-50" href="?<?= e($qs(['page' => $page - 1])) ?>">Sebelumnya</a><?php endif; ?>
        <?php if ($page < $pages): ?><a class="rounded-lg border border-river-200 px-3 py-1.5 font-semibold hover:bg-river-50" href="?<?= e($qs(['page' => $page + 1])) ?>">Berikutnya</a><?php endif; ?>
      </div>
    </div>
  <?php endif; ?>
  <?php endif; ?>
</section>
<?php require __DIR__ . '/../includes/admin_bottom.php'; ?>
