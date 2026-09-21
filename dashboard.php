<?php
require __DIR__ . '/config/app.php';
$user = require_login('karyawan');
$p = pengaturan();
$hari = date('Y-m-d');

$st = db()->prepare('SELECT * FROM absensi WHERE user_id = ? AND tanggal = ?');
$st->execute([$user['id'], $hari]);
$abs = $st->fetch() ?: null;
$mode = !$abs ? 'masuk' : (!$abs['jam_pulang'] ? 'pulang' : 'selesai');

$h = (int)date('G');
$salam = $h < 11 ? 'Selamat pagi' : ($h < 15 ? 'Selamat siang' : ($h < 18 ? 'Selamat sore' : 'Selamat malam'));

$cfg = [
    'mode' => $mode,
    'nama' => $user['nama'],
    'lat' => (float)$p['lat'],
    'lng' => (float)$p['lng'],
    'radius' => (int)$p['radius_m'],
    'maxAkurasi' => MAX_AKURASI_GPS,
    'csrf' => csrf_token(),
    'api' => url('api/absen.php'),
    'serverNow' => (int)(microtime(true) * 1000),
];

$title = 'Beranda';
require __DIR__ . '/includes/head.php';
?>
<body class="min-h-full bg-river-50 pb-28 text-river-950">

<header class="bg-river-900 text-white rounded-b-[2rem] px-5 pt-6 pb-24">
  <div class="mx-auto max-w-md">
    <div class="flex items-center gap-3">
      <div class="flex h-11 w-11 items-center justify-center rounded-full bg-gold-500 font-display text-lg font-bold text-river-950"><?= e(inisial($user['nama'])) ?></div>
      <div class="min-w-0 flex-1">
        <div class="text-sm text-river-200"><?= $salam ?>,</div>
        <div class="truncate font-display text-lg font-bold leading-tight"><?= e($user['nama']) ?></div>
      </div>
      <a href="<?= url('logout.php') ?>" class="rounded-full p-2.5 text-river-200 hover:bg-river-800" aria-label="Keluar"><?= icon('logout') ?></a>
    </div>
    <div class="mt-7">
      <div class="font-display text-6xl font-extrabold tabnum leading-none" id="clock" aria-live="off">--:--<span class="ml-1 text-2xl font-bold text-river-200">--</span></div>
      <div class="mt-2 text-river-200"><?= e(tgl_id($hari)) ?> · WITA</div>
    </div>
  </div>
</header>

<main class="-mt-16 mx-auto max-w-md space-y-4 px-4">

  <!-- Radar lokasi -->
  <section class="rounded-3xl bg-white p-5 shadow-xl shadow-river-900/10">
    <div class="flex items-start justify-between gap-3">
      <div>
        <div class="text-xs font-semibold text-river-600">Area absen</div>
        <div class="font-display text-lg font-bold leading-tight"><?= e($p['nama_kantor']) ?></div>
      </div>
      <div class="text-right text-sm text-river-700">Radius<br><b class="font-display text-base text-river-900"><?= (int)$p['radius_m'] ?> m</b></div>
    </div>

    <div class="mx-auto mt-3 w-56">
      <svg viewBox="0 0 220 220" class="block w-full" role="img" aria-label="Posisi Anda terhadap area kantor">
        <circle cx="110" cy="110" r="106" fill="#EEF6F4" stroke="#D5ECE7"/>
        <circle cx="110" cy="110" r="53" fill="none" stroke="#D5ECE7" stroke-dasharray="2 4"/>
        <line x1="110" y1="6" x2="110" y2="214" stroke="#D5ECE7"/><line x1="6" y1="110" x2="214" y2="110" stroke="#D5ECE7"/>
        <path class="radar-sweep" d="M110 110 L110 4 A106 106 0 0 1 172 18 Z" fill="#14776A" fill-opacity=".14"/>
        <circle id="ring" cx="110" cy="110" r="80" fill="#14776A" fill-opacity=".10" stroke="#14776A" stroke-width="2.5"/>
        <rect x="103" y="103" width="14" height="14" rx="4" fill="#E0A526"/>
        <text x="110" y="134" text-anchor="middle" font-size="10" font-weight="700" fill="#0F4841">Kantor</text>
        <g id="dot-user" style="transform:translate(110px,110px);opacity:0">
          <circle r="16" fill="#14776A" fill-opacity=".18"/>
          <circle id="dot-core" r="8" fill="#14776A" stroke="#fff" stroke-width="3"/>
        </g>
      </svg>
    </div>

    <div id="loc-chip" class="mx-auto mt-2 flex w-fit max-w-full items-center gap-2 rounded-full bg-river-50 px-4 py-2 text-sm font-semibold text-river-800" role="status" aria-live="polite">
      <span id="loc-text">Mencari lokasi Anda…</span>
    </div>
    <p id="loc-sub" class="mt-2 text-center text-xs text-river-700">Izinkan akses lokasi pada browser.</p>
  </section>

  <!-- Status hari ini -->
  <section class="grid grid-cols-2 gap-3">
    <div class="rounded-2xl bg-white p-4 shadow-sm">
      <div class="text-sm text-river-700">Masuk</div>
      <div class="font-display text-3xl font-bold tabnum"><?= $abs ? jam($abs['jam_masuk']) : '–' ?></div>
      <div class="mt-1.5 min-h-[1.75rem]"><?= $abs ? badge($abs['status']) : '<span class="text-xs text-river-700">Belum absen</span>' ?></div>
    </div>
    <div class="rounded-2xl bg-white p-4 shadow-sm">
      <div class="text-sm text-river-700">Pulang</div>
      <div class="font-display text-3xl font-bold tabnum"><?= $abs ? jam($abs['jam_pulang']) : '–' ?></div>
      <div class="mt-1.5 min-h-[1.75rem] text-xs text-river-700">Jadwal <?= jam($p['jam_pulang']) ?></div>
    </div>
  </section>

  <!-- Aksi -->
  <section>
    <?php if ($mode === 'selesai'): ?>
      <div class="flex items-center gap-3 rounded-2xl bg-river-600 px-5 py-4 text-white">
        <span class="flex h-10 w-10 items-center justify-center rounded-full bg-white/20"><?= icon('check', 'w-6 h-6') ?></span>
        <div><div class="font-display text-lg font-bold">Absensi hari ini selesai</div><div class="text-sm text-river-100">Sampai jumpa besok.</div></div>
      </div>
    <?php else: ?>
      <button id="btn-absen" type="button" disabled
        class="relative isolate flex w-full items-center justify-center gap-3 rounded-2xl bg-gold-500 px-5 py-5 font-display text-xl font-bold text-river-950 shadow-lg shadow-gold-500/30 transition active:scale-[.99] disabled:cursor-not-allowed disabled:bg-river-100 disabled:text-river-700/60 disabled:shadow-none">
        <?= icon('camera', 'w-6 h-6') ?><?= $mode === 'masuk' ? 'Absen masuk' : 'Absen pulang' ?>
      </button>
      <ul class="mt-3 space-y-1.5 px-1 text-sm">
        <li id="chk-loc" class="flex items-center gap-2 text-river-700"><span class="chk h-5 w-5 rounded-full border-2 border-river-200"></span>Lokasi berada di area kantor</li>
        <li class="flex items-center gap-2 text-river-700"><span class="h-5 w-5 rounded-full border-2 border-river-200"></span>Foto selfie sebagai bukti</li>
      </ul>
    <?php endif; ?>
  </section>
</main>

<!-- Navigasi bawah -->
<nav class="fixed inset-x-0 bottom-0 z-20 border-t border-river-100 bg-white/95 backdrop-blur" style="padding-bottom:env(safe-area-inset-bottom)">
  <div class="mx-auto grid max-w-md grid-cols-2">
    <a href="<?= url('dashboard.php') ?>" class="flex flex-col items-center gap-0.5 py-3 text-sm font-bold text-river-600" aria-current="page"><?= icon('home') ?>Beranda</a>
    <a href="<?= url('riwayat.php') ?>" class="flex flex-col items-center gap-0.5 py-3 text-sm font-medium text-river-700"><?= icon('calendar') ?>Riwayat</a>
  </div>
</nav>

<!-- Kamera -->
<div id="cam-modal" class="fixed inset-0 z-50 hidden flex-col bg-black" role="dialog" aria-modal="true" aria-label="Ambil foto absen">
  <div class="flex items-center justify-between px-4 py-3 text-white">
    <div class="font-display font-bold">Foto bukti <?= $mode === 'masuk' ? 'masuk' : 'pulang' ?></div>
    <button id="cam-close" type="button" class="rounded-full bg-white/15 p-2.5" aria-label="Tutup kamera"><?= icon('x') ?></button>
  </div>

  <div class="relative flex-1 overflow-hidden">
    <video id="cam-video" class="absolute inset-0 h-full w-full -scale-x-100 object-cover" playsinline muted autoplay></video>
    <img id="cam-preview" class="absolute inset-0 hidden h-full w-full object-contain bg-black" alt="Pratinjau foto">
    <div id="cam-guide" class="pointer-events-none absolute inset-0 flex items-center justify-center">
      <div class="h-[52%] aspect-[3/4] rounded-[50%] border-2 border-dashed border-white/60"></div>
    </div>
    <div id="cam-error" class="absolute inset-x-4 top-4 hidden rounded-xl bg-clay-100 px-4 py-3 text-sm font-medium text-clay-700" role="alert"></div>

    <div id="cam-done" class="absolute inset-0 hidden flex-col items-center justify-center gap-3 bg-river-900 p-8 text-center text-white">
      <span class="flex h-20 w-20 items-center justify-center rounded-full bg-gold-500 text-river-950"><?= icon('check', 'w-10 h-10') ?></span>
      <div id="done-title" class="font-display text-2xl font-bold"></div>
      <div id="done-sub" class="text-river-200"></div>
    </div>
  </div>

  <div class="px-6 pb-8 pt-5" style="padding-bottom:max(2rem,env(safe-area-inset-bottom))">
    <div id="ctl-shoot" class="flex justify-center">
      <button id="btn-shoot" type="button" class="h-20 w-20 rounded-full border-4 border-white p-1.5" aria-label="Ambil foto"><span class="block h-full w-full rounded-full bg-white"></span></button>
    </div>
    <div id="ctl-review" class="hidden grid-cols-2 gap-3">
      <button id="btn-retake" type="button" class="rounded-2xl bg-white/15 py-4 font-bold text-white">Foto ulang</button>
      <button id="btn-send" type="button" class="rounded-2xl bg-gold-500 py-4 font-display text-lg font-bold text-river-950 disabled:opacity-60">Kirim absen</button>
    </div>
  </div>
</div>
<canvas id="cam-canvas" class="hidden"></canvas>

<script>window.ABSEN = <?= json_encode($cfg, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;</script>
<script src="<?= url('assets/js/absen.js') ?>?v=1"></script>
</body>
</html>
