<?php
require __DIR__ . '/../config/app.php';
$title = 'Ringkasan'; $active = 'index';
require __DIR__ . '/../includes/admin_top.php';

$pdo = db();
$p = pengaturan();
$hari = date('Y-m-d');

$total = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='karyawan' AND aktif=1")->fetchColumn();
$st = $pdo->prepare("SELECT COUNT(*) total, SUM(status='terlambat') telat, SUM(jam_pulang IS NOT NULL) pulang FROM absensi WHERE tanggal = ?");
$st->execute([$hari]);
$s = $st->fetch();
$hadir = (int)$s['total']; $telat = (int)$s['telat']; $pulang = (int)$s['pulang'];
$st = $pdo->prepare('SELECT COUNT(*) FROM ketidakhadiran WHERE tanggal=?');
$st->execute([$hari]);
$jumlahTidakHadir = (int)$st->fetchColumn();
$belum = max(0, $total - $hadir - $jumlahTidakHadir);

$st = $pdo->prepare("SELECT a.*, u.nama, u.jabatan FROM absensi a JOIN users u ON u.id = a.user_id WHERE a.tanggal = ? ORDER BY a.jam_masuk DESC");
$st->execute([$hari]);
$rows = $st->fetchAll();

$st = $pdo->prepare("SELECT k.*, u.nama, u.jabatan FROM ketidakhadiran k JOIN users u ON u.id=k.user_id WHERE k.tanggal=? ORDER BY k.created_at DESC");
$st->execute([$hari]);
$tidakHadir = $st->fetchAll();

$st = $pdo->prepare("SELECT nama, jabatan FROM users WHERE role='karyawan' AND aktif=1 AND id NOT IN (SELECT user_id FROM absensi WHERE tanggal = ?) AND id NOT IN (SELECT user_id FROM ketidakhadiran WHERE tanggal = ?) ORDER BY nama");
$st->execute([$hari, $hari]);
$absen = $st->fetchAll();

$cards = [
  ['Karyawan aktif', $total, 'text-river-900'],
  ['Sudah masuk', $hadir, 'text-river-600'],
  ['Terlambat', $telat, 'text-gold-600'],
  ['Izin/sakit', $jumlahTidakHadir, 'text-gold-600'],
  ['Belum absen', $belum, 'text-clay-600'],
];
?>
<div class="mb-6">
  <h1 class="font-display text-3xl font-bold">Ringkasan hari ini</h1>
  <p class="text-river-700"><?= e(tgl_id($hari)) ?> · jam masuk <?= jam($p['jam_masuk']) ?> (toleransi <?= (int)$p['toleransi_menit'] ?> menit)</p>
</div>

<section class="grid grid-cols-2 gap-3 lg:grid-cols-5">
  <?php foreach ($cards as [$lb, $n, $c]): ?>
    <div class="rounded-2xl bg-white p-5 shadow-sm">
      <div class="text-sm text-river-700"><?= $lb ?></div>
      <div class="font-display text-4xl font-extrabold tabnum <?= $c ?>"><?= $n ?></div>
    </div>
  <?php endforeach; ?>
</section>

<div class="mt-6 grid gap-6 lg:grid-cols-[1fr_280px]">
  <section class="overflow-hidden rounded-2xl bg-white shadow-sm">
    <div class="flex items-center justify-between px-5 py-4">
      <h2 class="font-display text-lg font-bold">Kehadiran hari ini</h2>
      <a href="<?= url('admin/laporan.php') ?>" class="text-sm font-semibold text-river-600 hover:underline">Lihat laporan</a>
    </div>
    <?php if (!$rows): ?>
      <p class="px-5 pb-8 pt-2 text-river-700">Belum ada yang absen hari ini.</p>
    <?php else: ?>
    <div class="overflow-x-auto">
      <table class="w-full text-left text-sm">
        <thead class="bg-river-50 text-river-700"><tr>
          <th class="px-5 py-2.5 font-semibold">Karyawan</th><th class="px-3 py-2.5 font-semibold">Masuk</th><th class="px-3 py-2.5 font-semibold">Pulang</th><th class="px-3 py-2.5 font-semibold">Status</th>
        </tr></thead>
        <tbody class="divide-y divide-river-50">
        <?php foreach ($rows as $r): ?>
          <tr>
            <td class="px-5 py-3">
              <div class="flex items-center gap-3">
                <img src="<?= e(url($r['foto_masuk'])) ?>" data-full="<?= e(url($r['foto_masuk'])) ?>" alt="Foto masuk <?= e($r['nama']) ?>" class="h-10 w-10 cursor-zoom-in rounded-full object-cover" loading="lazy">
                <div><div class="font-semibold"><?= e($r['nama']) ?></div><div class="text-xs text-river-700"><?= e($r['jabatan']) ?></div></div>
              </div>
            </td>
            <td class="px-3 py-3 font-semibold tabnum"><?= jam($r['jam_masuk']) ?><?php if ($r['lat_masuk']): ?><a href="<?= e('https://www.google.com/maps?q=' . $r['lat_masuk'] . ',' . $r['lng_masuk']) ?>" target="_blank" rel="noopener" class="mt-1 block text-xs text-river-600 hover:underline">📍 Buka peta masuk</a><?php endif; ?></td>
            <td class="px-3 py-3 tabnum"><?= jam($r['jam_pulang']) ?><?php if ($r['lat_pulang']): ?><a href="<?= e('https://www.google.com/maps?q=' . $r['lat_pulang'] . ',' . $r['lng_pulang']) ?>" target="_blank" rel="noopener" class="mt-1 block text-xs font-semibold text-river-600 hover:underline">📍 Buka peta pulang</a><?php endif; ?></td>
            <td class="px-3 py-3"><div class="flex flex-wrap gap-1"><?= badge($r['status']) ?><?php if ($r['jenis_kerja'] === 'luar_kantor'): ?><?= badge('luar_kantor') ?><?php endif; ?><?php if ($r['lembur']): ?><?= badge('lembur') ?><?php endif; ?></div></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </section>

  <section class="rounded-2xl bg-white p-5 shadow-sm lg:col-span-2">
    <h2 class="font-display text-lg font-bold">Izin dan sakit hari ini</h2>
    <?php if (!$tidakHadir): ?><p class="mt-2 text-sm text-river-700">Tidak ada catatan izin atau sakit.</p><?php else: ?>
      <div class="mt-3 grid gap-3 md:grid-cols-2"><?php foreach ($tidakHadir as $r): ?>
        <div class="rounded-xl bg-river-50 p-3"><div class="flex justify-between gap-2"><b><?= e($r['nama']) ?></b><?= badge($r['jenis']) ?></div><p class="mt-1 text-sm text-river-700"><?= e($r['keterangan']) ?></p></div>
      <?php endforeach; ?></div>
    <?php endif; ?>
  </section>

  <aside class="rounded-2xl bg-white p-5 shadow-sm">
    <h2 class="font-display text-lg font-bold">Belum absen</h2>
    <?php if (!$absen): ?>
      <p class="mt-2 text-sm text-river-700">Semua karyawan sudah absen.</p>
    <?php else: ?>
      <ul class="mt-3 space-y-3">
        <?php foreach ($absen as $a): ?>
          <li class="flex items-center gap-3">
            <span class="flex h-9 w-9 items-center justify-center rounded-full bg-clay-100 text-sm font-bold text-clay-700"><?= e(inisial($a['nama'])) ?></span>
            <div class="min-w-0"><div class="truncate text-sm font-semibold"><?= e($a['nama']) ?></div><div class="truncate text-xs text-river-700"><?= e($a['jabatan']) ?></div></div>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </aside>
</div>
<?php require __DIR__ . '/../includes/admin_bottom.php'; ?>
