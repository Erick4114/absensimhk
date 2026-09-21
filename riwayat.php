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
$hadir = count($rows);
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
