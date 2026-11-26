<?php
require __DIR__ . '/../config.php';
$user = wajib_peran('Teknisi');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_cek();
    $id = (int)($_POST['id'] ?? 0);
    $baru = $_POST['status'] ?? '';
    $st = $pdo->prepare('SELECT * FROM laporan WHERE id_laporan = ? AND id_teknisi = ?');
    $st->execute([$id, $user['id']]);
    $lap = $st->fetch();
    try {
        if (!$lap) throw new Exception('Tugas tidak ditemukan.');
        // Transisi valid: Ditugaskan -> Diproses -> Selesai (sesuai state machine)
        $valid = ['Ditugaskan' => ['Diproses'], 'Diproses' => ['Selesai']];
        if (!in_array($baru, $valid[$lap['status']] ?? [], true)) throw new Exception('Perubahan status tidak valid dari status "' . $lap['status'] . '".');

        if ($baru === 'Selesai') {
            if (empty($_FILES['bukti']) || $_FILES['bukti']['error'] === UPLOAD_ERR_NO_FILE) throw new Exception('Bukti perbaikan wajib diunggah untuk menyelesaikan tugas.');
            $file = simpan_foto($_FILES['bukti']);
            $pdo->beginTransaction();
            $pdo->prepare("UPDATE laporan SET status = 'Selesai', tgl_selesai = CURDATE() WHERE id_laporan = ?")->execute([$id]);
            $pdo->prepare('INSERT INTO bukti_perbaikan (id_laporan,file,keterangan,tanggal) VALUES (?,?,?,CURDATE())')
                ->execute([$id, $file, mb_substr(trim($_POST['keterangan'] ?? ''), 0, 255) ?: null]);
            $pdo->commit();
        } else {
            $pdo->prepare("UPDATE laporan SET status = 'Diproses' WHERE id_laporan = ?")->execute([$id]);
        }
        flash('Status tugas diperbarui menjadi ' . $baru . '.');
    } catch (Exception $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        flash($ex->getMessage(), 'err');
    }
    redirect('teknisi/tugas.php?id=' . $id);
}

$st = $pdo->prepare('SELECT l.*, f.nama_fasilitas, f.lokasi, p.nama AS pelapor FROM laporan l
    JOIN fasilitas f ON f.id_fasilitas = l.id_fasilitas JOIN pengguna p ON p.id_pengguna = l.id_pelapor
    WHERE l.id_teknisi = ? ORDER BY FIELD(l.status,"Selesai") ASC, l.id_laporan DESC');
$st->execute([$user['id']]);
$daftar = $st->fetchAll();
$tugas = null;
$id = (int)($_GET['id'] ?? ($daftar[0]['id_laporan'] ?? 0));
foreach ($daftar as $d) if ((int)$d['id_laporan'] === $id) $tugas = $d;
$bukti = null;
if ($tugas) { $b = $pdo->prepare('SELECT * FROM bukti_perbaikan WHERE id_laporan = ? ORDER BY id_bukti DESC LIMIT 1'); $b->execute([$tugas['id_laporan']]); $bukti = $b->fetch(); }

$judul = 'Tugas Saya'; $menu_aktif = 'tugas';
include __DIR__ . '/../includes/header.php';
?>
<div class="judul"><h1>Tugas Saya</h1><p>Perbarui status perbaikan dan unggah bukti pengerjaan.</p></div>
<div class="baris">
  <section class="kartu" style="flex:1 1 320px">
    <h2>Daftar Tugas</h2>
    <?php foreach ($daftar as $d): ?>
      <a class="tugas <?= $tugas && $d['id_laporan'] === $tugas['id_laporan'] ? 'pilih' : '' ?>" href="?id=<?= $d['id_laporan'] ?>">
        <div class="atas"><span><?= e($d['kode']) ?></span><?= pill($d['status']) ?></div>
        <div style="font-weight:600;font-size:14px"><?= e($d['nama_fasilitas'] . ', ' . $d['lokasi']) ?></div>
        <div class="sub"><?= e($d['deskripsi']) ?></div>
        <div class="sub" style="margin-top:6px">Ditugaskan <?= tgl($d['tgl_tugas']) ?></div>
      </a>
    <?php endforeach; ?>
    <p class="sub">Tugas baru muncul di sini setelah Admin menugaskan laporan kepadamu.</p>
  </section>

  <?php if ($tugas): $s = $tugas['status']; ?>
  <section class="kartu" style="flex:1.6 1 440px;padding:24px">
    <h2>Detail Tugas <?= e($tugas['kode']) ?></h2>
    <dl class="dl">
      <dt>Fasilitas</dt><dd><?= e($tugas['nama_fasilitas'] . ', ' . $tugas['lokasi']) ?></dd>
      <dt>Keluhan</dt><dd><?= e($tugas['deskripsi']) ?></dd>
      <dt>Pelapor</dt><dd><?= e($tugas['pelapor']) ?></dd>
      <dt>Catatan Admin</dt><dd><?= e($tugas['catatan_admin'] ?: '-') ?></dd>
    </dl>
    <?php if ($tugas['foto']): ?><img class="foto" src="../uploads/<?= e($tugas['foto']) ?>" alt="Foto kerusakan"><?php endif; ?>

    <?php if ($s === 'Selesai'): ?>
      <div class="alert alert-ok">Tugas ini sudah selesai.</div>
      <?php if ($bukti): ?>
        <div class="kecil-label">Bukti perbaikan</div>
        <img class="foto" src="../uploads/<?= e($bukti['file']) ?>" alt="Bukti perbaikan" onerror="this.outerHTML='<div class=&quot;sub&quot;><?= e($bukti['file']) ?></div>'">
        <div class="sub"><?= e($bukti['keterangan']) ?> · Diunggah <?= tgl($bukti['tanggal']) ?></div>
      <?php endif; ?>
    <?php else: $lanjut = $s === 'Ditugaskan' ? 'Diproses' : 'Selesai'; ?>
      <form method="post" enctype="multipart/form-data" style="display:flex;flex-direction:column;gap:18px">
        <?= csrf_field() ?><input type="hidden" name="id" value="<?= $tugas['id_laporan'] ?>"><input type="hidden" name="status" value="<?= $lanjut ?>">
        <div class="kecil-label">Status saat ini: <?= e($s) ?> → <?= $lanjut ?></div>
        <?php if ($lanjut === 'Selesai'): ?>
          <div class="field"><label for="bk">Bukti perbaikan (JPG/PNG, maks 2 MB)</label><input id="bk" type="file" name="bukti" accept="image/jpeg,image/png" required style="padding:10px 14px;height:auto"></div>
          <div class="field"><label for="ket">Keterangan</label><input id="ket" type="text" name="keterangan" maxlength="255" placeholder="Contoh: Lampu diganti"></div>
        <?php endif; ?>
        <div><button class="btn" type="submit" style="height:48px"><?= $lanjut === 'Diproses' ? 'Mulai Proses Perbaikan' : 'Simpan dan Selesaikan' ?></button></div>
      </form>
    <?php endif; ?>
  </section>
  <?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php';
