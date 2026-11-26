<?php
require __DIR__ . '/../config.php';
$user = wajib_peran('Pelapor');

$cari = trim($_GET['cari'] ?? '');
$status = $_GET['status'] ?? '';
$sql = 'SELECT l.*, f.nama_fasilitas, f.lokasi FROM laporan l JOIN fasilitas f ON f.id_fasilitas = l.id_fasilitas WHERE l.id_pelapor = ?';
$par = [$user['id']];
if ($cari !== '') { $sql .= ' AND (f.nama_fasilitas LIKE ? OR l.deskripsi LIKE ? OR l.kode LIKE ?)'; array_push($par, "%$cari%", "%$cari%", "%$cari%"); }
if (in_array($status, STATUS_LIST, true)) { $sql .= ' AND l.status = ?'; $par[] = $status; }
$sql .= ' ORDER BY l.id_laporan DESC';
$st = $pdo->prepare($sql); $st->execute($par);
$daftar = $st->fetchAll();

// Detail laporan terpilih (hanya milik pelapor ini)
$pilih = null;
$idp = (int)($_GET['id'] ?? ($daftar[0]['id_laporan'] ?? 0));
foreach ($daftar as $d) if ((int)$d['id_laporan'] === $idp) $pilih = $d;
$bukti = null; $teknisi = null;
if ($pilih) {
    $b = $pdo->prepare('SELECT * FROM bukti_perbaikan WHERE id_laporan = ? ORDER BY id_bukti DESC LIMIT 1');
    $b->execute([$pilih['id_laporan']]); $bukti = $b->fetch();
    if ($pilih['id_teknisi']) {
        $t = $pdo->prepare('SELECT nama FROM pengguna WHERE id_pengguna = ?');
        $t->execute([$pilih['id_teknisi']]); $teknisi = $t->fetchColumn();
    }
}

$judul = 'Laporan Saya'; $menu_aktif = 'laporan';
include __DIR__ . '/../includes/header.php';

// Riwayat status dibentuk dari urutan status laporan
$urut = ['Menunggu Verifikasi', 'Terverifikasi', 'Ditugaskan', 'Diproses', 'Selesai'];
?>
<div class="bar">
  <div class="judul"><h1>Laporan Saya</h1><p>Pantau status laporan kerusakan yang kamu kirim.</p></div>
  <a class="btn" href="buat.php">+ Buat Laporan</a>
</div>
<div class="baris">
  <section class="kartu kartu-lebar">
    <form class="filter" method="get">
      <div class="field"><label for="cari" class="kecil-label">Cari</label><input id="cari" type="search" name="cari" placeholder="Cari fasilitas atau deskripsi" value="<?= e($cari) ?>"></div>
      <div class="field" style="flex:0 1 220px"><label for="status" class="kecil-label">Status</label>
        <select id="status" name="status"><option value="">Semua status</option>
        <?php foreach (STATUS_LIST as $s): ?><option <?= $s === $status ? 'selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?></select></div>
      <button class="btn btn-garis" type="submit">Terapkan</button>
    </form>
    <div class="tabel-wrap"><table>
      <thead><tr><th>Laporan</th><th>Fasilitas</th><th>Tanggal</th><th>Status</th></tr></thead>
      <tbody>
      <?php foreach ($daftar as $d): ?>
        <tr class="<?= $pilih && $d['id_laporan'] === $pilih['id_laporan'] ? 'pilih' : '' ?>">
          <td><a href="?id=<?= $d['id_laporan'] ?>"><b><?= e($d['kode']) ?></b></a><div class="sub"><?= e($d['deskripsi']) ?></div></td>
          <td><b style="font-weight:600"><?= e($d['nama_fasilitas']) ?></b><div class="sub"><?= e($d['lokasi']) ?></div></td>
          <td class="nowrap"><?= tgl($d['tanggal']) ?></td>
          <td><?= pill($d['status']) ?></td>
        </tr>
      <?php endforeach; if (!$daftar): ?><tr><td colspan="4" class="sub">Belum ada laporan.</td></tr><?php endif; ?>
      </tbody></table></div>
  </section>

  <?php if ($pilih): ?>
  <section class="kartu kartu-sempit">
    <div class="bar"><h2>Detail <?= e($pilih['kode']) ?></h2><?= pill($pilih['status']) ?></div>
    <?php if ($pilih['foto']): ?><img class="foto" src="../uploads/<?= e($pilih['foto']) ?>" alt="Foto kerusakan"><?php endif; ?>
    <div>
      <div class="kecil-label" style="margin-bottom:14px">Riwayat status</div>
      <div class="tl">
      <?php if ($pilih['status'] === 'Ditolak'): ?>
        <div><div class="t"><span class="c">✓</span><span class="g"></span></div><div class="x"><b>Laporan dikirim</b><small><?= tgl($pilih['tanggal']) ?></small></div></div>
        <div><div class="t"><span class="c" style="background:#DC2626">✕</span></div><div class="x"><b>Ditolak oleh Admin</b></div></div>
      <?php else:
        $idx = array_search($pilih['status'], $urut);
        foreach ($urut as $i => $s):
          if ($i > $idx) break;
          $label = ['Laporan dikirim', 'Terverifikasi oleh Admin', 'Ditugaskan' . ($teknisi ? ' ke ' . $teknisi : ''), 'Perbaikan diproses', 'Perbaikan selesai'][$i];
          $tg = [$pilih['tanggal'], null, $pilih['tgl_tugas'], null, $pilih['tgl_selesai']][$i]; ?>
        <div><div class="t"><span class="c">✓</span><?php if ($i < $idx): ?><span class="g"></span><?php endif; ?></div>
          <div class="x"><b><?= e($label) ?></b><?php if ($tg): ?><small><?= tgl($tg) ?></small><?php endif; ?></div></div>
      <?php endforeach; endif; ?>
      </div>
    </div>
    <?php if ($bukti): ?>
    <div style="display:flex;flex-direction:column;gap:10px">
      <div class="kecil-label">Bukti perbaikan</div>
      <img class="foto" src="../uploads/<?= e($bukti['file']) ?>" alt="Bukti perbaikan" onerror="this.outerHTML='<div class=&quot;sub&quot;><?= e($bukti['file']) ?></div>'">
      <div class="sub"><?= e($bukti['keterangan']) ?></div>
    </div>
    <?php endif; ?>
  </section>
  <?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php';
