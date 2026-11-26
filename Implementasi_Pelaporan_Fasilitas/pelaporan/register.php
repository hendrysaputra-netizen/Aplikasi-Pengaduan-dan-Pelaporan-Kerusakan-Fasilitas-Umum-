<?php
require __DIR__ . '/config.php';
if (!empty($_SESSION['user'])) redirect(home_peran($_SESSION['user']['peran']));

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_cek();
    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pw = $_POST['password'] ?? '';
    if ($nama === '' || mb_strlen($nama) > 100) $error = 'Nama wajib diisi (maksimal 100 karakter).';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $error = 'Format email tidak valid.';
    elseif (strlen($pw) < 8) $error = 'Password minimal 8 karakter.';
    elseif ($pw !== ($_POST['konfirmasi'] ?? '')) $error = 'Konfirmasi password tidak sama.';
    else {
        $cek = $pdo->prepare('SELECT 1 FROM pengguna WHERE email = ?');
        $cek->execute([$email]);
        if ($cek->fetch()) $error = 'Email sudah terdaftar.';
        else {
            // Peran selalu Pelapor: Admin dan Teknisi tidak bisa mendaftar sendiri
            $pdo->prepare('INSERT INTO pengguna (nama,email,password,peran) VALUES (?,?,?,?)')
                ->execute([$nama, $email, password_hash($pw, PASSWORD_DEFAULT), 'Pelapor']);
            flash('Registrasi berhasil. Silakan masuk.');
            redirect('index.php');
        }
    }
}
?><!doctype html>
<html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Registrasi - Pelaporan Fasilitas Kampus</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&amp;display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/style.css"></head><body>
<div class="auth">
  <section class="kiri">
    <div class="brand" style="padding:0"><div class="logo" style="width:44px;height:44px"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L3 18l3 3 6.3-6.3a4 4 0 0 0 5.4-5.4l-2.6 2.6-2.4-.6-.6-2.4z"/></svg></div><div style="font-size:15px">Pelaporan Fasilitas<br>Kampus</div></div>
    <div><h1>Buat akun Pelapor untuk mulai melapor.</h1>
      <p style="margin-top:16px;font-size:16px;line-height:1.6;color:#C8DAD7;max-width:460px">Akun Admin dan Teknisi dikelola oleh pengelola sistem, sehingga pendaftaran mandiri hanya tersedia untuk Pelapor.</p></div>
    <div style="font-size:13px;color:#A8C4C0">Aplikasi Pelaporan Kerusakan Fasilitas Kampus</div>
  </section>
  <section class="kanan">
    <form method="post" autocomplete="off">
      <div><h2>Registrasi Akun</h2><p style="margin-top:6px">Isi data berikut untuk mendaftar sebagai Pelapor.</p></div>
      <?php if ($error): ?><div class="alert alert-err"><?= e($error) ?></div><?php endif; ?>
      <?= csrf_field() ?>
      <div class="field"><label for="nama">Nama lengkap</label><input id="nama" name="nama" type="text" required maxlength="100" value="<?= e($_POST['nama'] ?? '') ?>"></div>
      <div class="field"><label for="email">Email</label><input id="email" name="email" type="email" required value="<?= e($_POST['email'] ?? '') ?>"></div>
      <div class="field"><label for="password">Password</label><input id="password" name="password" type="password" required minlength="8" placeholder="Minimal 8 karakter"></div>
      <div class="field"><label for="konfirmasi">Konfirmasi password</label><input id="konfirmasi" name="konfirmasi" type="password" required minlength="8"></div>
      <button class="btn" type="submit">Daftar</button>
      <p style="text-align:center">Sudah punya akun? <a class="link" href="index.php">Masuk</a></p>
    </form>
  </section>
</div></body></html>
