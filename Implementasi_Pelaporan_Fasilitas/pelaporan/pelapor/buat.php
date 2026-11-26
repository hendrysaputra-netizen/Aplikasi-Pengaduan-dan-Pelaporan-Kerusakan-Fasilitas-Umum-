<?php
require __DIR__ . '/../config.php';
$user = wajib_peran('Pelapor');

$fasilitas = $pdo->query('SELECT * FROM fasilitas ORDER BY nama_fasilitas, lokasi')->fetchAll();
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_cek();
    $idf = (int)($_POST['id_fasilitas'] ?? 0);
    $desk = trim($_POST['deskripsi'] ?? '');
    try {
        $ada = $pdo->prepare('SELECT 1 FROM fasilitas WHERE id_fasilitas = ?'); $ada->execute([$idf]);
        if (!$ada->fetch()) throw new Exception('Pilih fasilitas yang valid.');
        if ($desk === '') throw new Exception('Deskripsi kerusakan wajib diisi.');
        if (mb_strlen($desk) > 2000) throw new Exception('Deskripsi terlalu panjang (maksimal 2000 karakter).');
        $foto = null;
        if (!empty($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) $foto = simpan_foto($_FILES['foto']);

        $pdo->beginTransaction();
        $pdo->prepare('INSERT INTO laporan (kode,id_pelapor,id_fasilitas,deskripsi,foto,status,tanggal) VALUES (?,?,?,?,?,?,CURDATE())')
            ->execute([bin2hex(random_bytes(4)), $user['id'], $idf, $desk, $foto, 'Menunggu Verifikasi']);
        $id = (int)$pdo->lastInsertId();
        $pdo->prepare('UPDATE laporan SET kode = ? WHERE id_laporan = ?')->execute([sprintf('LK-%03d', $id), $id]);
        $pdo->commit();
        flash('Laporan berhasil dikirim dan menunggu verifikasi Admin.');
        redirect('pelapor/laporan.php?id=' . $id);
    } catch (Exception $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $error = $ex->getMessage();
    }
}

$judul = 'Buat Laporan'; $menu_aktif = 'buat';
include __DIR__ . '/../includes/header.php';
?>
<div class="judul"><h1>Buat Laporan Kerusakan</h1><p>Lengkapi data berikut agar Admin dapat memeriksa laporanmu.</p></div>
<div class="baris">
  <form class="kartu kartu-lebar" method="post" enctype="multipart/form-data" style="padding:28px;gap:22px">
    <?php if ($error): ?><div class="alert alert-err"><?= e($error) ?></div><?php endif; ?>
    <?= csrf_field() ?>
    <div class="field"><label for="f">Fasilitas yang rusak</label>
      <select id="f" name="id_fasilitas" required>
        <?php foreach ($fasilitas as $f): ?><option value="<?= $f['id_fasilitas'] ?>" <?= (int)($_POST['id_fasilitas'] ?? 0) === (int)$f['id_fasilitas'] ? 'selected' : '' ?>><?= e($f['nama_fasilitas'] . ', ' . $f['lokasi']) ?></option><?php endforeach; ?>
      </select></div>
    <div class="field"><label for="d">Deskripsi kerusakan</label><textarea id="d" name="deskripsi" rows="4" required maxlength="2000"><?= e($_POST['deskripsi'] ?? '') ?></textarea></div>
    <div class="field"><label for="foto">Foto kerusakan (JPG/PNG, maks 2 MB)</label>
      <input id="foto" name="foto" type="file" accept="image/jpeg,image/png" style="padding:10px 14px;height:auto"></div>
    <div style="display:flex;gap:12px;flex-wrap:wrap">
      <button class="btn" type="submit" style="height:48px;padding:0 28px">Kirim Laporan</button>
      <a class="btn btn-garis" href="laporan.php" style="height:48px">Batal</a>
    </div>
  </form>
  <aside class="kartu kartu-sempit">
    <h2 style="font-size:16px">Setelah laporan dikirim</h2>
    <?= pill('Menunggu Verifikasi') ?>
    <ol style="margin:0;padding-left:20px;display:flex;flex-direction:column;gap:10px;font-size:14px;line-height:1.5;color:#3A4651">
      <li>Laporan masuk ke antrean verifikasi Admin.</li>
      <li>Jika valid, Admin menugaskan teknisi untuk memperbaikinya.</li>
      <li>Pantau perkembangannya kapan saja di menu Laporan Saya.</li>
    </ol>
  </aside>
</div>
<?php include __DIR__ . '/../includes/footer.php';
