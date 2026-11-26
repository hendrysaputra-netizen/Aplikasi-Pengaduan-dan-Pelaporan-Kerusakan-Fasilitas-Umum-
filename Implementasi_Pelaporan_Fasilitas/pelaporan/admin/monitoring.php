<?php
require __DIR__ . '/../config.php';
$user = wajib_peran('Admin');

$cari = trim($_GET['cari'] ?? '');
$status = $_GET['status'] ?? '';
$sql = 'SELECT l.*, f.nama_fasilitas, f.lokasi, p.nama AS pelapor FROM laporan l
        JOIN fasilitas f ON f.id_fasilitas = l.id_fasilitas JOIN pengguna p ON p.id_pengguna = l.id_pelapor WHERE 1=1';
$par = [];
if ($cari !== '') { $sql .= ' AND (p.nama LIKE ? OR f.nama_fasilitas LIKE ? OR l.kode LIKE ?)'; array_push($par, "%$cari%", "%$cari%", "%$cari%"); }
if (in_array($status, STATUS_LIST, true)) { $sql .= ' AND l.status = ?'; $par[] = $status; }
$sql .= ' ORDER BY l.id_laporan DESC';
$st = $pdo->prepare($sql); $st->execute($par);
$daftar = $st->fetchAll();

$h = array_fill_keys(STATUS_LIST, 0);
foreach ($pdo->query('SELECT status, COUNT(*) n FROM laporan GROUP BY status') as $r) $h[$r['status']] = (int)$r['n'];
$proses = $h['Terverifikasi'] + $h['Ditugaskan'] + $h['Diproses'];

$judul = 'Monitoring Laporan'; $menu_aktif = 'monitoring';
include __DIR__ . '/../includes/header.php';
?>
<div class="judul"><h1>Monitoring Laporan</h1><p>Ringkasan seluruh laporan kerusakan fasilitas kampus.</p></div>
<section class="ringkasan">
  <div><span>Menunggu Verifikasi</span><b style="color:#78350F"><?= $h['Menunggu Verifikasi'] ?></b></div>
  <div><span>Dalam Penanganan</span><b style="color:#1E3A8A"><?= $proses ?></b></div>
  <div><span>Selesai</span><b style="color:#14532D"><?= $h['Selesai'] ?></b></div>
  <div><span>Ditolak</span><b style="color:#7F1D1D"><?= $h['Ditolak'] ?></b></div>
</section>
<section class="kartu">
  <div class="bar"><h2>Daftar Laporan</h2>
    <form class="filter" method="get">
      <input type="search" name="cari" placeholder="Cari pelapor atau fasilitas" value="<?= e($cari) ?>" style="width:260px" aria-label="Cari laporan">
      <select name="status" style="width:auto" aria-label="Filter status"><option value="">Semua status</option>
        <?php foreach (STATUS_LIST as $s): ?><option <?= $s === $status ? 'selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?></select>
      <button class="btn btn-garis" type="submit">Terapkan</button>
    </form></div>
  <div class="tabel-wrap"><table style="min-width:720px">
    <thead><tr><th>Laporan</th><th>Pelapor</th><th>Fasilitas</th><th>Tanggal</th><th>Status</th><th>Aksi</th></tr></thead>
    <tbody>
    <?php foreach ($daftar as $d): ?>
      <tr>
        <td><b><?= e($d['kode']) ?></b><div class="sub"><?= e($d['deskripsi']) ?></div></td>
        <td><?= e($d['pelapor']) ?></td>
        <td><b style="font-weight:600"><?= e($d['nama_fasilitas']) ?></b><div class="sub"><?= e($d['lokasi']) ?></div></td>
        <td class="nowrap"><?= tgl($d['tanggal']) ?></td>
        <td><?= pill($d['status']) ?></td>
        <td><a class="btn btn-kecil <?= in_array($d['status'], ['Menunggu Verifikasi', 'Terverifikasi']) ? '' : 'btn-garis' ?>" href="verifikasi.php?id=<?= $d['id_laporan'] ?>">
          <?= $d['status'] === 'Menunggu Verifikasi' ? 'Verifikasi' : ($d['status'] === 'Terverifikasi' ? 'Tugaskan' : 'Lihat') ?></a></td>
      </tr>
    <?php endforeach; if (!$daftar): ?><tr><td colspan="6" class="sub">Tidak ada laporan.</td></tr><?php endif; ?>
    </tbody></table></div>
</section>
<?php include __DIR__ . '/../includes/footer.php';
