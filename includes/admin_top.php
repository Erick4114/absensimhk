<?php
/** @var string $title */
/** @var string $active */
$adm = require_login('admin');
require __DIR__ . '/head.php';
$nav = [
    'index'      => ['Ringkasan', 'home', 'admin/index.php'],
    'karyawan'   => ['Karyawan', 'users', 'admin/karyawan.php'],
    'laporan'    => ['Laporan', 'file', 'admin/laporan.php'],
    'pengaturan' => ['Lokasi & jam', 'pin', 'admin/pengaturan.php'],
];
$fl = flash_get();
?>
<body class="min-h-full bg-river-50 text-river-950">
<div class="lg:flex min-h-screen">
  <aside class="hidden lg:flex lg:w-64 shrink-0 flex-col bg-river-950 text-river-100 p-5 sticky top-0 h-screen">
    <a href="<?= url('admin/index.php') ?>" class="flex items-center gap-3 mb-8">
      <?= logo_mark('w-10 h-10') ?>
      <span class="font-display text-lg font-bold leading-tight text-white">Mahakam Jaya<br><span class="text-sm font-medium text-river-200">Absensi karyawan</span></span>
    </a>
    <nav class="space-y-1 flex-1">
      <?php foreach ($nav as $k => [$label, $ic, $href]): ?>
        <a href="<?= url($href) ?>" class="flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-sm font-medium transition <?= $active === $k ? 'bg-river-600 text-white' : 'text-river-200 hover:bg-river-900 hover:text-white' ?>">
          <?= icon($ic) ?><?= e($label) ?>
        </a>
      <?php endforeach; ?>
    </nav>
    <div class="border-t border-river-800 pt-4">
      <div class="text-sm font-semibold text-white"><?= e($adm['nama']) ?></div>
      <div class="text-xs text-river-200 mb-3"><?= e($adm['jabatan']) ?></div>
      <a href="<?= url('logout.php') ?>" class="flex items-center gap-2 text-sm text-river-200 hover:text-white"><?= icon('logout', 'w-4 h-4') ?>Keluar</a>
    </div>
  </aside>

  <div class="flex-1 min-w-0">
    <header class="lg:hidden bg-river-950 text-white px-4 pt-4 pb-3 sticky top-0 z-30">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-2.5"><?= logo_mark('w-8 h-8') ?><span class="font-display font-bold">Mahakam Jaya</span></div>
        <a href="<?= url('logout.php') ?>" class="text-river-200 p-2" aria-label="Keluar"><?= icon('logout') ?></a>
      </div>
      <nav class="flex gap-1 mt-3 overflow-x-auto -mx-1 px-1">
        <?php foreach ($nav as $k => [$label, $ic, $href]): ?>
          <a href="<?= url($href) ?>" class="whitespace-nowrap rounded-full px-3.5 py-1.5 text-sm font-medium <?= $active === $k ? 'bg-river-600 text-white' : 'text-river-200 bg-river-900' ?>"><?= e($label) ?></a>
        <?php endforeach; ?>
      </nav>
    </header>

    <main class="p-4 sm:p-6 lg:p-10 max-w-6xl">
      <?php if ($fl): ?>
        <div class="mb-5 rounded-xl px-4 py-3 text-sm font-medium <?= $fl[0] === 'ok' ? 'bg-river-100 text-river-800' : 'bg-clay-100 text-clay-700' ?>" role="status"><?= e($fl[1]) ?></div>
      <?php endif; ?>
