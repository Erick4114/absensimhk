<?php
require __DIR__ . '/../config/app.php';
$adm = require_login('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_or_die();
    $nama = trim($_POST['nama_kantor'] ?? '');
    $lat = (float)($_POST['lat'] ?? 0);
    $lng = (float)($_POST['lng'] ?? 0);
    $radius = (int)($_POST['radius_m'] ?? 0);
    $jm = $_POST['jam_masuk'] ?? '';
    $jp = $_POST['jam_pulang'] ?? '';
    $tol = (int)($_POST['toleransi_menit'] ?? 0);
    $okJam = preg_match('/^\d{2}:\d{2}$/', $jm) && preg_match('/^\d{2}:\d{2}$/', $jp);

    if ($nama === '' || $lat < -90 || $lat > 90 || $lng < -180 || $lng > 180 || ($lat == 0 && $lng == 0)) {
        flash('err', 'Nama kantor dan koordinat wajib diisi dengan benar.');
    } elseif ($radius < 20 || $radius > 2000) {
        flash('err', 'Radius harus antara 20 dan 2000 meter.');
    } elseif (!$okJam || $tol < 0 || $tol > 120) {
        flash('err', 'Jam kerja atau toleransi tidak valid.');
    } else {
        db()->prepare('UPDATE pengaturan SET nama_kantor=?, lat=?, lng=?, radius_m=?, jam_masuk=?, jam_pulang=?, toleransi_menit=? WHERE id=1')
            ->execute([$nama, $lat, $lng, $radius, $jm . ':00', $jp . ':00', $tol]);
        flash('ok', 'Pengaturan disimpan.');
    }
    redirect('admin/pengaturan.php');
}

$title = 'Lokasi & jam'; $active = 'pengaturan';
require __DIR__ . '/../includes/admin_top.php';
$p = pengaturan();
$in = 'w-full rounded-xl border border-river-200 bg-white px-4 py-2.5 outline-none focus:border-river-600 focus:ring-4 focus:ring-river-100';
?>
<div class="mb-6">
  <h1 class="font-display text-3xl font-bold">Lokasi &amp; jam kerja</h1>
  <p class="text-river-700">Karyawan hanya bisa absen bila berada dalam radius dari titik kantor.</p>
</div>

<form method="post" class="grid gap-6 lg:grid-cols-2">
  <?= csrf_field() ?>
  <section class="rounded-2xl bg-white p-6 shadow-sm">
    <h2 class="font-display text-lg font-bold">Titik kantor</h2>
    <div class="mt-4 space-y-4">
      <div><label for="nama_kantor" class="mb-1.5 block text-sm font-semibold">Nama kantor</label>
        <input id="nama_kantor" name="nama_kantor" required value="<?= e($p['nama_kantor']) ?>" class="<?= $in ?>"></div>
      <div class="grid grid-cols-2 gap-3">
        <div><label for="lat" class="mb-1.5 block text-sm font-semibold">Latitude</label>
          <input id="lat" name="lat" required inputmode="decimal" value="<?= e((string)$p['lat']) ?>" class="<?= $in ?> tabnum"></div>
        <div><label for="lng" class="mb-1.5 block text-sm font-semibold">Longitude</label>
          <input id="lng" name="lng" required inputmode="decimal" value="<?= e((string)$p['lng']) ?>" class="<?= $in ?> tabnum"></div>
      </div>
      <div class="flex flex-wrap items-center gap-3">
        <button type="button" id="btn-here" class="inline-flex items-center gap-2 rounded-xl border border-river-200 px-4 py-2.5 text-sm font-semibold text-river-800 hover:bg-river-50"><?= icon('pin', 'w-4 h-4') ?>Pakai lokasi saya sekarang</button>
        <a id="map-link" target="_blank" rel="noopener" class="text-sm font-semibold text-river-600 hover:underline" href="https://www.google.com/maps?q=<?= e($p['lat'] . ',' . $p['lng']) ?>">Cek di Google Maps</a>
      </div>
      <p id="here-msg" class="text-sm text-river-700">Buka halaman ini dari perangkat yang berada di kantor agar titik tepat.</p>
      <div><label for="radius_m" class="mb-1.5 block text-sm font-semibold">Radius absen (meter)</label>
        <input id="radius_m" name="radius_m" type="number" min="20" max="2000" required value="<?= (int)$p['radius_m'] ?>" class="<?= $in ?>">
        <p class="mt-1.5 text-xs text-river-700">Disarankan 80–150 m agar GPS dalam ruangan tetap terbaca.</p></div>
    </div>
  </section>

  <section class="rounded-2xl bg-white p-6 shadow-sm lg:self-start">
    <h2 class="font-display text-lg font-bold">Jam kerja</h2>
    <div class="mt-4 grid grid-cols-2 gap-3">
      <div><label for="jam_masuk" class="mb-1.5 block text-sm font-semibold">Jam masuk</label>
        <input id="jam_masuk" name="jam_masuk" type="time" required value="<?= jam($p['jam_masuk']) ?>" class="<?= $in ?>"></div>
      <div><label for="jam_pulang" class="mb-1.5 block text-sm font-semibold">Jam pulang</label>
        <input id="jam_pulang" name="jam_pulang" type="time" required value="<?= jam($p['jam_pulang']) ?>" class="<?= $in ?>"></div>
    </div>
    <div class="mt-4"><label for="toleransi_menit" class="mb-1.5 block text-sm font-semibold">Toleransi keterlambatan (menit)</label>
      <input id="toleransi_menit" name="toleransi_menit" type="number" min="0" max="120" required value="<?= (int)$p['toleransi_menit'] ?>" class="<?= $in ?>"></div>
    <button class="mt-6 w-full rounded-xl bg-river-600 px-5 py-3 font-bold text-white shadow-lg shadow-river-600/25 hover:bg-river-700">Simpan pengaturan</button>
  </section>
</form>

<script>
  const $ = (i) => document.getElementById(i);
  function syncLink() { $('map-link').href = 'https://www.google.com/maps?q=' + encodeURIComponent($('lat').value + ',' + $('lng').value); }
  $('lat').addEventListener('input', syncLink); $('lng').addEventListener('input', syncLink);
  $('btn-here').addEventListener('click', () => {
    const msg = $('here-msg');
    if (!navigator.geolocation) { msg.textContent = 'Browser tidak mendukung lokasi.'; return; }
    msg.textContent = 'Mengambil lokasi…';
    navigator.geolocation.getCurrentPosition((p) => {
      $('lat').value = p.coords.latitude.toFixed(7);
      $('lng').value = p.coords.longitude.toFixed(7);
      syncLink();
      msg.textContent = `Lokasi terisi (akurasi ±${Math.round(p.coords.accuracy)} m). Klik Simpan pengaturan.`;
    }, () => { msg.textContent = 'Gagal mengambil lokasi. Izinkan akses lokasi lalu coba lagi.'; }, { enableHighAccuracy: true, timeout: 20000 });
  });
</script>
<?php require __DIR__ . '/../includes/admin_bottom.php'; ?>
