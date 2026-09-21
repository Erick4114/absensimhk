<?php
require __DIR__ . '/config/app.php';
$user = require_login('karyawan');

$bulan = $_GET['bulan'] ?? date('Y-m');
if (!preg_match('/^\d{4}-\d{2}$/', $bulan)) $bulan = date('Y-m');
$awal = $bulan . '-01';
$akhir = date('Y-m-t', strtotime($awal));

$st = db()->prepare('SELECT * FROM absensi WHERE user_id = ? AND tanggal BETWEEN ? AND ? ORDER BY tanggal DESC');
$st->execute([$user['id'], $awal, $akhir]);
$rows = $st->fetchAll();
$st = db()->prepare('SELECT *, jenis AS status FROM ketidakhadiran WHERE user_id = ? AND tanggal BETWEEN ? AND ?');
$st->execute([$user['id'], $awal, $akhir]);
$tidakHadir = $st->fetchAll();
foreach ($tidakHadir as &$r) { $r['_tidak_hadir'] = true; }
unset($r);
$rows = array_merge($rows, $tidakHadir);
usort($rows, fn($a, $b) => strcmp($b['tanggal'], $a['tanggal']));
$hadir = count($rows) - count($tidakHadir);
$telat = count(array_filter($rows, fn($r) => $r['status'] === 'terlambat'));

$title = 'Riwayat';
require __DIR__ . '/includes/head.php';
?>
<body class="min-h-full bg-river-50 pb-28 text-river-950">
<header class="bg-river-900 px-5 pb-16 pt-6 text-white rounded-b-[2rem]">
  <div class="mx-auto max-w-md">
    <h1 class="font-display text-2xl font-bold">Riwayat absensi</h1>
    <form method="get" class="mt-4">
      <label for="bulan" class="sr-only">Bulan</label>
      <input id="bulan" type="month" name="bulan" value="<?= e($bulan) ?>" max="<?= date('Y-m') ?>" onchange="this.form.submit()"
             class="w-full rounded-xl border-0 bg-river-800 px-4 py-3 text-white [color-scheme:dark]">
    </form>
  </div>
</header>

<main class="-mt-8 mx-auto max-w-md space-y-3 px-4">
  <section class="grid grid-cols-2 gap-3">
    <div class="rounded-2xl bg-white p-4 shadow-sm"><div class="text-sm text-river-700">Hari hadir</div><div class="font-display text-3xl font-bold"><?= $hadir ?></div></div>
    <div class="rounded-2xl bg-white p-4 shadow-sm"><div class="text-sm text-river-700">Terlambat</div><div class="font-display text-3xl font-bold"><?= $telat ?></div></div>
  </section>

  <?php if (!$rows): ?>
    <div class="rounded-2xl bg-white p-8 text-center text-river-700">Belum ada absensi pada bulan ini.</div>
  <?php endif; ?>

  <?php foreach ($rows as $r): ?>
    <article class="rounded-2xl bg-white p-4 shadow-sm">
      <div class="flex items-center justify-between gap-2">
        <div class="font-display font-bold"><?= e(tgl_id($r['tanggal'], true, true)) ?></div>
        <?= badge($r['status']) ?>
      </div>
      <?php if (!empty($r['_tidak_hadir'])): ?>
        <p class="mt-3 text-sm text-river-700"><?= e($r['keterangan']) ?></p>
      <?php else: ?>
      <div class="mt-2 flex flex-wrap gap-2">
        <?php if (($r['jenis_kerja'] ?? 'kantor') === 'luar_kantor' || ($r['jenis_pulang'] ?? 'kantor') === 'luar_kantor'): ?><?= badge('luar_kantor') ?><?php endif; ?>
        <?php if (!empty($r['lembur'])): ?><?= badge('lembur') ?><?php endif; ?>
      </div>
      <?php if (!empty($r['alasan_luar_kantor'])): ?><p class="mt-2 text-sm text-river-700"><?= e($r['alasan_luar_kantor']) ?></p><?php endif; ?>
      <?php if (!empty($r['alasan_luar_pulang'])): ?><p class="mt-2 text-sm text-river-700">Pulang di luar kantor: <?= e($r['alasan_luar_pulang']) ?></p><?php endif; ?>
      <?php if (!empty($r['keterangan_lembur'])): ?><p class="mt-2 text-sm text-river-700">Lembur: <?= e($r['keterangan_lembur']) ?></p><?php endif; ?>
      <?php if (!empty($r['foto_lembur'])): ?><a href="<?= e(url($r['foto_lembur'])) ?>" target="_blank" class="mt-2 inline-block text-sm font-semibold text-river-600 hover:underline">Lihat lampiran lembur</a><?php endif; ?>
      <div class="mt-3 grid grid-cols-2 gap-3">
        <?php foreach (['masuk' => 'Masuk', 'pulang' => 'Pulang'] as $k => $lb): ?>
          <div class="flex items-center gap-3">
            <?php if ($r['foto_' . $k]): ?>
              <img src="<?= e(url($r['foto_' . $k])) ?>" data-full="<?= e(url($r['foto_' . $k])) ?>" alt="Foto <?= $lb ?>" loading="lazy" class="h-14 w-14 cursor-zoom-in rounded-xl object-cover">
            <?php else: ?>
              <div class="h-14 w-14 rounded-xl bg-river-50"></div>
            <?php endif; ?>
            <div>
              <div class="text-xs text-river-700"><?= $lb ?></div>
              <div class="font-display text-xl font-bold tabnum"><?= jam($r['jam_' . $k]) ?></div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </article>
  <?php endforeach; ?>
</main>

<nav class="fixed inset-x-0 bottom-0 z-20 border-t border-river-100 bg-white/95 backdrop-blur" style="padding-bottom:env(safe-area-inset-bottom)">
  <div class="mx-auto grid max-w-md grid-cols-2">
    <a href="<?= url('dashboard.php') ?>" class="flex flex-col items-center gap-0.5 py-3 text-sm font-medium text-river-700"><?= icon('home') ?>Beranda</a>
    <a href="<?= url('riwayat.php') ?>" class="flex flex-col items-center gap-0.5 py-3 text-sm font-bold text-river-600" aria-current="page"><?= icon('calendar') ?>Riwayat</a>
  </div>
</nav>
<?php require __DIR__ . '/includes/lightbox.php'; ?>
</body>
</html>
