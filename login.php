<?php
require __DIR__ . '/config/app.php';

if (current_user()) redirect('index.php');

$error = '';
$nip = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nip = trim($_POST['nip'] ?? '');
    $pass = $_POST['password'] ?? '';
    $locked = ($_SESSION['login_lock'] ?? 0) > time();

    if (!csrf_verify($_POST['csrf'] ?? null)) {
        $error = 'Sesi habis. Muat ulang halaman lalu coba lagi.';
    } elseif ($locked) {
        $error = 'Terlalu banyak percobaan. Tunggu ' . ($_SESSION['login_lock'] - time()) . ' detik.';
    } else {
        $st = db()->prepare('SELECT id, password, role FROM users WHERE nip = ? AND aktif = 1');
        $st->execute([$nip]);
        $row = $st->fetch();
        if ($row && password_verify($pass, $row['password'])) {
            session_regenerate_id(true);
            $_SESSION['uid'] = (int)$row['id'];
            unset($_SESSION['login_fail'], $_SESSION['login_lock']);
            redirect($row['role'] === 'admin' ? 'admin/index.php' : 'dashboard.php');
        }
        $_SESSION['login_fail'] = ($_SESSION['login_fail'] ?? 0) + 1;
        if ($_SESSION['login_fail'] >= 5) {
            $_SESSION['login_lock'] = time() + 60;
            $_SESSION['login_fail'] = 0;
        }
        $error = 'NIP atau kata sandi salah, atau akun tidak aktif.';
    }
}

$title = 'Masuk';
require __DIR__ . '/includes/head.php';
?>
<body class="min-h-full bg-river-950">
<div class="min-h-screen lg:grid lg:grid-cols-[1.1fr_1fr]">

  <!-- Panel kiri: identitas -->
  <section class="relative hidden lg:flex flex-col justify-between overflow-hidden bg-river-900 p-12 text-white">
    <svg class="absolute inset-0 h-full w-full opacity-[.16]" viewBox="0 0 600 800" preserveAspectRatio="xMidYMid slice" aria-hidden="true">
      <?php for ($i = 0; $i < 16; $i++): $y = 40 + $i * 52; ?>
        <path d="M-20 <?= $y ?> C 80 <?= $y - 34 ?>, 160 <?= $y + 34 ?>, 260 <?= $y ?> S 440 <?= $y - 34 ?>, 620 <?= $y ?>" fill="none" stroke="<?= $i % 5 === 0 ? '#E0A526' : '#A9D8CF' ?>" stroke-width="<?= $i % 5 === 0 ? 2.5 : 1.5 ?>"/>
      <?php endfor; ?>
    </svg>
    <div class="relative flex items-center gap-3">
      <?= logo_mark('w-12 h-12') ?>
      <div class="font-display text-xl font-bold leading-tight">Koperasi<br>Mahakam Jaya</div>
    </div>
    <div class="relative max-w-md">
      <h1 class="font-display text-5xl font-extrabold leading-[1.05]">Hadir tercatat, lengkap dengan lokasi dan foto.</h1>
      <p class="mt-5 text-lg text-river-200">Absen langsung dari ponsel begitu Anda tiba di kantor. Tanpa kertas, tanpa titip absen.</p>
    </div>
    <p class="relative text-sm text-river-200">Waktu Indonesia Tengah (WITA)</p>
  </section>

  <!-- Panel kanan: form -->
  <section class="flex min-h-screen items-center justify-center bg-river-50 px-5 py-10 lg:min-h-0">
    <div class="w-full max-w-sm">
      <div class="mb-8 flex items-center gap-3 lg:hidden">
        <?= logo_mark('w-11 h-11') ?>
        <div class="font-display text-lg font-bold leading-tight text-river-900">Koperasi<br>Mahakam Jaya</div>
      </div>
      <h2 class="font-display text-3xl font-bold text-river-900">Masuk</h2>
      <p class="mt-1.5 text-river-700">Gunakan NIP dan kata sandi dari admin koperasi.</p>

      <?php if ($error): ?>
        <div class="mt-5 rounded-xl bg-clay-100 px-4 py-3 text-sm font-medium text-clay-700" role="alert"><?= e($error) ?></div>
      <?php endif; ?>

      <form method="post" class="mt-6 space-y-4" autocomplete="on">
        <?= csrf_field() ?>
        <div>
          <label for="nip" class="mb-1.5 block text-sm font-semibold text-river-900">NIP</label>
          <input id="nip" name="nip" value="<?= e($nip) ?>" required autofocus autocomplete="username"
                 class="w-full rounded-xl border border-river-200 bg-white px-4 py-3 text-base outline-none transition focus:border-river-600 focus:ring-4 focus:ring-river-100">
        </div>
        <div>
          <label for="password" class="mb-1.5 block text-sm font-semibold text-river-900">Kata sandi</label>
          <div class="relative">
            <input id="password" name="password" type="password" required autocomplete="current-password"
                   class="w-full rounded-xl border border-river-200 bg-white px-4 py-3 pr-20 text-base outline-none transition focus:border-river-600 focus:ring-4 focus:ring-river-100">
            <button type="button" id="toggle-pass" class="absolute inset-y-0 right-3 px-2 text-sm font-semibold text-river-600">Lihat</button>
          </div>
        </div>
        <button class="w-full rounded-xl bg-river-600 px-4 py-3.5 text-base font-bold text-white shadow-lg shadow-river-600/25 transition hover:bg-river-700 active:scale-[.99]">Masuk</button>
      </form>

      <p class="mt-8 text-sm text-river-700">Absen memerlukan izin <b>lokasi</b> dan <b>kamera</b> pada browser Anda.</p>
    </div>
  </section>
</div>
<script>
  const p = document.getElementById('password'), t = document.getElementById('toggle-pass');
  t.addEventListener('click', () => { const s = p.type === 'password'; p.type = s ? 'text' : 'password'; t.textContent = s ? 'Sembunyikan' : 'Lihat'; });
</script>
</body>
</html>
