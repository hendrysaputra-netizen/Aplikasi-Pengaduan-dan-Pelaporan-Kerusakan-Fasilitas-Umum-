<?php
require __DIR__ . '/config.php';
if (!empty($_SESSION['user'])) redirect(home_peran($_SESSION['user']['peran']));

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_cek();
    $email = trim($_POST['email'] ?? '');
    $st = $pdo->prepare('SELECT * FROM pengguna WHERE email = ?');
    $st->execute([$email]);
    $u = $st->fetch();
    if ($u && password_verify($_POST['password'] ?? '', $u['password'])) {
        session_regenerate_id(true);
        $_SESSION['user'] = ['id' => (int)$u['id_pengguna'], 'nama' => $u['nama'], 'peran' => $u['peran']];
        redirect(home_peran($u['peran']));
    }
    $error = 'Email atau password salah.';
}
?><!doctype html>
<html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Masuk - Pelaporan Fasilitas Kampus</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&amp;display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/style.css"></head><body>
<div class="auth">
  <section class="kiri">
    <div class="brand" style="padding:0"><div class="logo" style="width:44px;height:44px"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L3 18l3 3 6.3-6.3a4 4 0 0 0 5.4-5.4l-2.6 2.6-2.4-.6-.6-2.4z"/></svg></div><div style="font-size:15px">Pelaporan Fasilitas<br>Kampus</div></div>
    <div>
      <h1>Laporkan kerusakan fasilitas kampus, pantau sampai selesai.</h1>
      <ol>
        <li><span>1</span>Pelapor mengirim laporan beserta foto</li>
        <li><span>2</span>Admin memverifikasi dan menugaskan teknisi</li>
        <li><span>3</span>Teknisi memperbaiki dan mengunggah bukti</li>
      </ol>
    </div>
    <div style="font-size:13px;color:#A8C4C0">Aplikasi Pelaporan Kerusakan Fasilitas Kampus</div>
  </section>
  <section class="kanan">
    <form method="post" autocomplete="off">
      <div><h2>Masuk</h2><p style="margin-top:6px">Gunakan akun yang sudah terdaftar. Pelapor, Admin, dan Teknisi masuk dari halaman ini.</p></div>
      <?php if ($f = flash()): ?><div class="alert alert-ok"><?= e($f[0]) ?></div><?php endif; ?>
      <?php if ($error): ?><div class="alert alert-err"><?= e($error) ?></div><?php endif; ?>
      <?= csrf_field() ?>
      <div class="field"><label for="email">Email</label><input id="email" name="email" type="email" required value="<?= e($_POST['email'] ?? '') ?>"></div>
      <div class="field"><label for="password">Password</label><input id="password" name="password" type="password" required></div>
      <button class="btn" type="submit">Masuk</button>
      <p style="text-align:center">Belum punya akun? <a class="link" href="register.php">Daftar sebagai Pelapor</a></p>
    </form>
  </section>
</div></body></html>
