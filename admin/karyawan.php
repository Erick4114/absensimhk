<?php
require __DIR__ . '/../config/app.php';
$adm = require_login('admin');
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_or_die();
    $act = $_POST['act'] ?? '';
    $id = (int)($_POST['id'] ?? 0);

    if ($act === 'simpan') {
        $nip = trim($_POST['nip'] ?? '');
        $nama = trim($_POST['nama'] ?? '');
        $jab = trim($_POST['jabatan'] ?? '') ?: 'Staf';
        $pass = $_POST['password'] ?? '';
        if ($nip === '' || $nama === '') {
            flash('err', 'NIP dan nama wajib diisi.');
        } elseif (!$id && strlen($pass) < 6) {
            flash('err', 'Kata sandi minimal 6 karakter.');
        } elseif ($id && $pass !== '' && strlen($pass) < 6) {
            flash('err', 'Kata sandi baru minimal 6 karakter.');
        } else {
            try {
                if ($id) {
                    $pdo->prepare("UPDATE users SET nip=?, nama=?, jabatan=? WHERE id=? AND role='karyawan'")->execute([$nip, $nama, $jab, $id]);
                    if ($pass !== '') $pdo->prepare("UPDATE users SET password=? WHERE id=? AND role='karyawan'")->execute([password_hash($pass, PASSWORD_DEFAULT), $id]);
                    flash('ok', 'Data karyawan diperbarui.');
                } else {
                    $pdo->prepare("INSERT INTO users (nip, nama, jabatan, role, password) VALUES (?,?,?,'karyawan',?)")->execute([$nip, $nama, $jab, password_hash($pass, PASSWORD_DEFAULT)]);
                    flash('ok', 'Karyawan ditambahkan.');
                }
            } catch (PDOException $ex) {
                flash('err', $ex->getCode() === '23000' ? 'NIP sudah dipakai.' : 'Gagal menyimpan data.');
            }
        }
    } elseif ($act === 'toggle' && $id) {
        $pdo->prepare("UPDATE users SET aktif = 1 - aktif WHERE id=? AND role='karyawan'")->execute([$id]);
        flash('ok', 'Status akun diperbarui.');
    }
    redirect('admin/karyawan.php');
}

$title = 'Karyawan'; $active = 'karyawan';
require __DIR__ . '/../includes/admin_top.php';
$rows = $pdo->query("SELECT * FROM users WHERE role='karyawan' ORDER BY aktif DESC, nama")->fetchAll();
?>
<div class="mb-6 flex flex-wrap items-end justify-between gap-3">
  <div>
    <h1 class="font-display text-3xl font-bold">Karyawan</h1>
    <p class="text-river-700"><?= count($rows) ?> akun terdaftar</p>
  </div>
  <button type="button" onclick="openForm()" class="inline-flex items-center gap-2 rounded-xl bg-river-600 px-4 py-2.5 font-semibold text-white shadow-lg shadow-river-600/25 hover:bg-river-700"><?= icon('plus', 'w-4 h-4') ?>Tambah karyawan</button>
</div>

<section class="overflow-hidden rounded-2xl bg-white shadow-sm">
  <?php if (!$rows): ?>
    <p class="p-8 text-center text-river-700">Belum ada karyawan. Tambahkan yang pertama.</p>
  <?php else: ?>
  <div class="overflow-x-auto">
    <table class="w-full text-left text-sm">
      <thead class="bg-river-50 text-river-700"><tr>
        <th class="px-5 py-2.5 font-semibold">Nama</th><th class="px-3 py-2.5 font-semibold">NIP</th><th class="px-3 py-2.5 font-semibold">Status</th><th class="px-5 py-2.5"></th>
      </tr></thead>
      <tbody class="divide-y divide-river-50">
      <?php foreach ($rows as $r): ?>
        <tr class="<?= $r['aktif'] ? '' : 'opacity-60' ?>">
          <td class="px-5 py-3">
            <div class="flex items-center gap-3">
              <span class="flex h-10 w-10 items-center justify-center rounded-full bg-river-100 font-display font-bold text-river-800"><?= e(inisial($r['nama'])) ?></span>
              <div><div class="font-semibold"><?= e($r['nama']) ?></div><div class="text-xs text-river-700"><?= e($r['jabatan']) ?></div></div>
            </div>
          </td>
          <td class="px-3 py-3 tabnum"><?= e($r['nip']) ?></td>
          <td class="px-3 py-3"><?= $r['aktif'] ? '<span class="rounded-full bg-river-100 px-2.5 py-1 text-xs font-semibold text-river-800">Aktif</span>' : '<span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600">Nonaktif</span>' ?></td>
          <td class="px-5 py-3">
            <div class="flex justify-end gap-2">
              <button type="button" class="rounded-lg border border-river-200 px-3 py-1.5 text-sm font-semibold text-river-800 hover:bg-river-50"
                      onclick='openForm(<?= json_encode(["id" => (int)$r["id"], "nip" => $r["nip"], "nama" => $r["nama"], "jabatan" => $r["jabatan"]], JSON_HEX_APOS | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_QUOT) ?>)'>Ubah</button>
              <form method="post" onsubmit="return confirm('<?= $r['aktif'] ? 'Nonaktifkan' : 'Aktifkan' ?> akun ini?')">
                <?= csrf_field() ?><input type="hidden" name="act" value="toggle"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <button class="rounded-lg px-3 py-1.5 text-sm font-semibold <?= $r['aktif'] ? 'text-clay-700 hover:bg-clay-50' : 'text-river-700 hover:bg-river-50' ?>"><?= $r['aktif'] ? 'Nonaktifkan' : 'Aktifkan' ?></button>
              </form>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</section>

<dialog id="form-dlg" class="m-auto w-[92vw] max-w-md rounded-3xl p-0 shadow-2xl">
  <form method="post" class="p-6">
    <?= csrf_field() ?>
    <input type="hidden" name="act" value="simpan">
    <input type="hidden" name="id" id="f-id" value="0">
    <h2 id="f-title" class="font-display text-2xl font-bold">Tambah karyawan</h2>
    <div class="mt-5 space-y-4">
      <?php
      $f = [['f-nip', 'nip', 'NIP', 'text', true], ['f-nama', 'nama', 'Nama lengkap', 'text', true], ['f-jab', 'jabatan', 'Jabatan', 'text', false]];
      foreach ($f as [$i, $n, $l, $t, $req]): ?>
        <div>
          <label for="<?= $i ?>" class="mb-1.5 block text-sm font-semibold"><?= $l ?></label>
          <input id="<?= $i ?>" name="<?= $n ?>" type="<?= $t ?>" <?= $req ? 'required' : '' ?> class="w-full rounded-xl border border-river-200 px-4 py-2.5 outline-none focus:border-river-600 focus:ring-4 focus:ring-river-100">
        </div>
      <?php endforeach; ?>
      <div>
        <label for="f-pass" class="mb-1.5 block text-sm font-semibold">Kata sandi <span id="f-pass-hint" class="font-normal text-river-700"></span></label>
        <input id="f-pass" name="password" type="text" minlength="6" autocomplete="new-password" class="w-full rounded-xl border border-river-200 px-4 py-2.5 outline-none focus:border-river-600 focus:ring-4 focus:ring-river-100">
      </div>
    </div>
    <div class="mt-6 flex justify-end gap-2">
      <button type="button" onclick="document.getElementById('form-dlg').close()" class="rounded-xl px-4 py-2.5 font-semibold text-river-800 hover:bg-river-50">Batal</button>
      <button class="rounded-xl bg-river-600 px-5 py-2.5 font-bold text-white hover:bg-river-700">Simpan</button>
    </div>
  </form>
</dialog>
<script>
  window.openForm = function (d) {
    var g = function (i) { return document.getElementById(i); };
    g('f-id').value = d ? d.id : 0;
    g('f-nip').value = d ? d.nip : '';
    g('f-nama').value = d ? d.nama : '';
    g('f-jab').value = d ? d.jabatan : '';
    g('f-pass').value = '';
    g('f-pass').required = !d;
    g('f-pass-hint').textContent = d ? '(kosongkan jika tidak diubah)' : '(min. 6 karakter)';
    g('f-title').textContent = d ? 'Ubah karyawan' : 'Tambah karyawan';
    var dialog = g('form-dlg');
    if (typeof dialog.showModal === 'function') dialog.showModal();
    else dialog.setAttribute('open', 'open');
  };
</script>
<?php require __DIR__ . '/../includes/admin_bottom.php'; ?>
