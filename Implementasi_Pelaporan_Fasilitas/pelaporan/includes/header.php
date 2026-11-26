<?php
// Variabel yang diharapkan: $user (array pengguna), $judul, $menu_aktif
$menus = [
    'Pelapor' => [['laporan', 'pelapor/laporan.php', 'Laporan Saya', 'M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z M14 3v5h5 M9 13h6M9 17h6'],
                  ['buat', 'pelapor/buat.php', 'Buat Laporan', 'M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18z M12 8v8M8 12h8']],
    'Admin'   => [['monitoring', 'admin/monitoring.php', 'Monitoring Laporan', 'M3 3h7v9H3z M14 3h7v5h-7z M14 12h7v9h-7z M3 16h7v5H3z'],
                  ['verifikasi', 'admin/verifikasi.php', 'Verifikasi Laporan', 'M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z M8.5 12l2.5 2.5 4.5-5'],
                  ['fasilitas', 'admin/fasilitas.php', 'Data Fasilitas', 'M3 21h18M5 21V8l7-5 7 5v13 M9 21v-6h6v6'],
                  ['cetak', 'admin/cetak.php', 'Cetak Laporan', 'M7 9V3h10v6 M3 9h18v8H3z M7 14h10v7H7z']],
    'Teknisi' => [['tugas', 'teknisi/tugas.php', 'Tugas Saya', 'M9 4h6v3H9z M7 5H6a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1 M9 14l2 2 4-4']],
];
$base = base_url();
$inisial = strtoupper(implode('', array_map(fn($k) => mb_substr($k, 0, 1), array_slice(explode(' ', $user['nama']), 0, 2))));
$f = flash();
?><!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($judul) ?> - Pelaporan Fasilitas Kampus</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&amp;display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= $base ?>/assets/style.css">
</head>
<body>
<div class="app">
  <aside class="sidebar no-print">
    <div>
      <div class="brand">
        <div class="logo"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L3 18l3 3 6.3-6.3a4 4 0 0 0 5.4-5.4l-2.6 2.6-2.4-.6-.6-2.4z"/></svg></div>
        <div>Pelaporan Fasilitas<br>Kampus</div>
      </div>
      <nav>
        <?php foreach ($menus[$user['peran']] as [$kunci, $url, $label, $ikon]): ?>
          <a href="<?= $base . '/' . $url ?>" class="<?= $kunci === $menu_aktif ? 'aktif' : '' ?>">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="<?= $ikon ?>"/></svg>
            <?= e($label) ?>
          </a>
        <?php endforeach; ?>
      </nav>
    </div>
    <div class="profil">
      <div class="avatar"><?= e($inisial) ?></div>
      <div class="info"><strong><?= e($user['nama']) ?></strong><span><?= e($user['peran']) ?></span></div>
      <a href="<?= $base ?>/logout.php" title="Keluar" aria-label="Keluar">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4 M16 17l5-5-5-5M21 12H9"/></svg>
      </a>
    </div>
  </aside>
  <main class="main">
    <?php if ($f): ?><div class="alert <?= $f[1] === 'ok' ? 'alert-ok' : 'alert-err' ?> no-print"><?= e($f[0]) ?></div><?php endif; ?>
