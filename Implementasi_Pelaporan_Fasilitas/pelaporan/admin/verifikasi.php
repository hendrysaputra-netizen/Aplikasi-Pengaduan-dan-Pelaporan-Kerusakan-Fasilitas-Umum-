<?php
require __DIR__ . '/../config.php';
$user = wajib_peran('Admin');

$teknisi = $pdo->query("SELECT id_pengguna, nama FROM pengguna WHERE peran = 'Teknisi' ORDER BY nama")->fetchAll();

function ambil_laporan(PDO $pdo, int $id): ?array {
    $st = $pdo->prepare('SELECT l.*, f.nama_fasilitas, f.lokasi, p.nama AS pelapor FROM laporan l
        JOIN fasilitas f ON f.id_fasilitas = l.id_fasilitas JOIN pengguna p ON p.id_pengguna = l.id_pelapor WHERE l.id_laporan = ?');
    $st->execute([$id]);
    return $st->fetch() ?: null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_cek();
    $id = (int)($_POST['id'] ?? 0);
    $aksi = $_POST['aksi'] ?? '';
    $lap = ambil_laporan($pdo, $id);
    if (!$lap) { flash('Laporan tidak ditemukan.', 'err'); redirect('admin/verifikasi.php'); }

    // Transisi status mengikuti state machine: Menunggu Verifikasi -> Terverifikasi/Ditolak -> Ditugaskan
    if ($aksi === 'setujui' && $lap['status'] === 'Menunggu Verifikasi') {
        $pdo->prepare("UPDATE laporan SET status = 'Terverifikasi' WHERE id_laporan = ?")->execute([$id]);
        flash('Laporan diverifikasi. Lanjutkan dengan menugaskan teknisi.');
    } elseif ($aksi === 'tolak' && $lap['status'] === 'Menunggu Verifikasi') {
        $pdo->prepare("UPDATE laporan SET status = 'Ditolak', catatan_admin = ? WHERE id_laporan = ?")
            ->execute([mb_substr(trim($_POST['catatan'] ?? ''), 0, 255) ?: null, $id]);
        flash('Laporan ditolak.');
    } elseif ($aksi === 'tugaskan' && $lap['status'] === 'Terverifikasi') {
        $idt = (int)($_POST['id_teknisi'] ?? 0);
        $cek = $pdo->prepare("SELECT 1 FROM pengguna WHERE id_pengguna = ? AND peran = 'Teknisi'"); $cek->execute([$idt]);
        if (!$cek->fetch()) { flash('Pilih teknisi yang valid.', 'err'); redirect('admin/verifikasi.php?id=' . $id); }
        $pdo->prepare("UPDATE laporan SET status = 'Ditugaskan', id_teknisi = ?, catatan_admin = ?, tgl_tugas = CURDATE() WHERE id_laporan = ?")
            ->execute([$idt, mb_substr(trim($_POST['catatan'] ?? ''), 0, 255) ?: null, $id]);
        flash('Teknisi berhasil ditugaskan.');
    } else {
        flash('Aksi tidak dapat dilakukan pada status laporan saat ini.', 'err');
    }
    redirect('admin/verifikasi.php?id=' . $id);
}

$daftar = $pdo->query("SELECT l.id_laporan, l.kode, l.deskripsi, l.status, f.nama_fasilitas, f.lokasi FROM laporan l
    JOIN fasilitas f ON f.id_fasilitas = l.id_fasilitas ORDER BY FIELD(l.status,'Menunggu Verifikasi','Terverifikasi') DESC, l.id_laporan DESC")->fetchAll();
// Urutkan: yang perlu tindakan Admin di atas
usort($daftar, fn($a, $b) => (in_array($b['status'], ['Menunggu Verifikasi', 'Terverifikasi']) <=> in_array($a['status'], ['Menunggu Verifikasi', 'Terverifikasi'])) ?: $b['id_laporan'] <=> $a['id_laporan']);

$id = (int)($_GET['id'] ?? ($daftar[0]['id_laporan'] ?? 0));
$lap = $id ? ambil_laporan($pdo, $id) : null;
$namaTeknisi = '';
if ($lap && $lap['id_teknisi']) foreach ($teknisi as $t) if ((int)$t['id_pengguna'] === (int)$lap['id_teknisi']) $namaTeknisi = $t['nama'];

$judul = 'Verifikasi dan Penugasan'; $menu_aktif = 'verifikasi';
include __DIR__ . '/../includes/header.php';
?>
<div class="judul"><h1>Verifikasi dan Penugasan</h1><p>Periksa laporan masuk, lalu tugaskan teknisi.</p></div>
<div class="baris">
  <section class="kartu" style="flex:1 1 300px">
    <h2>Daftar Laporan</h2>
    <?php foreach ($daftar as $d): ?>
      <a class="tugas <?= $lap && $d['id_laporan'] === $lap['id_laporan'] ? 'pilih' : '' ?>" href="?id=<?= $d['id_laporan'] ?>">
        <div class="atas"><span><?= e($d['kode']) ?></span><?= pill($d['status']) ?></div>
        <div style="font-weight:600;font-size:14px"><?= e($d['nama_fasilitas'] . ', ' . $d['lokasi']) ?></div>
        <div class="sub"><?= e($d['deskripsi']) ?></div>
      </a>
    <?php endforeach; if (!$daftar): ?><p class="sub">Belum ada laporan.</p><?php endif; ?>
  </section>

  <?php if ($lap): $s = $lap['status']; ?>
  <section class="kartu" style="flex:1.6 1 440px;padding:24px">
    <div class="bar"><h2>Detail <?= e($lap['kode']) ?></h2><?= pill($s) ?></div>
    <dl class="dl">
      <dt>Pelapor</dt><dd><?= e($lap['pelapor']) ?></dd>
      <dt>Fasilitas</dt><dd><?= e($lap['nama_fasilitas'] . ', ' . $lap['lokasi']) ?></dd>
      <dt>Tanggal</dt><dd><?= tgl($lap['tanggal']) ?></dd>
      <dt>Deskripsi</dt><dd><?= e($lap['deskripsi']) ?></dd>
      <?php if ($namaTeknisi): ?><dt>Teknisi</dt><dd><?= e($namaTeknisi) ?></dd><?php endif; ?>
      <?php if ($lap['catatan_admin']): ?><dt>Catatan Admin</dt><dd><?= e($lap['catatan_admin']) ?></dd><?php endif; ?>
    </dl>
    <?php if ($lap['foto']): ?><img class="foto" src="../uploads/<?= e($lap['foto']) ?>" alt="Foto kerusakan"><?php endif; ?>

    <form method="post" class="langkah <?= $s === 'Menunggu Verifikasi' ? '' : 'mati' ?>">
      <?= csrf_field() ?><input type="hidden" name="id" value="<?= $lap['id_laporan'] ?>">
      <div class="kecil-label">Langkah 1: Verifikasi</div>
      <div class="field"><label for="catatan1">Alasan penolakan (opsional)</label><input id="catatan1" type="text" name="catatan" maxlength="255" <?= $s === 'Menunggu Verifikasi' ? '' : 'disabled' ?>></div>
      <div style="display:flex;gap:12px;flex-wrap:wrap">
        <button class="btn" name="aksi" value="setujui" <?= $s === 'Menunggu Verifikasi' ? '' : 'disabled' ?>>Verifikasi</button>
        <button class="btn btn-bahaya" name="aksi" value="tolak" onclick="return confirm('Tolak laporan ini?')" <?= $s === 'Menunggu Verifikasi' ? '' : 'disabled' ?>>Tolak</button>
      </div>
    </form>

    <form method="post" class="langkah <?= $s === 'Terverifikasi' ? '' : 'mati' ?>">
      <?= csrf_field() ?><input type="hidden" name="id" value="<?= $lap['id_laporan'] ?>">
      <div class="kecil-label">Langkah 2: Penugasan teknisi<?= $s === 'Menunggu Verifikasi' ? ' (aktif setelah Terverifikasi)' : '' ?></div>
      <div class="field"><label for="tek">Teknisi</label>
        <select id="tek" name="id_teknisi" <?= $s === 'Terverifikasi' ? '' : 'disabled' ?>>
          <?php foreach ($teknisi as $t): ?><option value="<?= $t['id_pengguna'] ?>"><?= e($t['nama']) ?></option><?php endforeach; ?></select></div>
      <div class="field"><label for="catatan2">Catatan untuk teknisi</label><input id="catatan2" type="text" name="catatan" maxlength="255" <?= $s === 'Terverifikasi' ? '' : 'disabled' ?>></div>
      <div><button class="btn" name="aksi" value="tugaskan" <?= $s === 'Terverifikasi' ? '' : 'disabled' ?>>Tugaskan</button></div>
    </form>
  </section>
  <?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php';
