<?php
require __DIR__ . '/../config.php';
$user = wajib_peran('Admin');

$dari = $_GET['dari'] ?? '';
$sampai = $_GET['sampai'] ?? '';
$status = $_GET['status'] ?? '';
$ok = fn($d) => (bool)preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) && strtotime($d) !== false;

$sql = 'SELECT l.*, f.nama_fasilitas, f.lokasi, p.nama AS pelapor, t.nama AS teknisi FROM laporan l
        JOIN fasilitas f ON f.id_fasilitas = l.id_fasilitas JOIN pengguna p ON p.id_pengguna = l.id_pelapor
        LEFT JOIN pengguna t ON t.id_pengguna = l.id_teknisi WHERE 1=1';
$par = [];
if ($ok($dari))   { $sql .= ' AND l.tanggal >= ?'; $par[] = $dari; }
if ($ok($sampai)) { $sql .= ' AND l.tanggal <= ?'; $par[] = $sampai; }
if (in_array($status, STATUS_LIST, true)) { $sql .= ' AND l.status = ?'; $par[] = $status; }
$sql .= ' ORDER BY l.tanggal, l.id_laporan';
$st = $pdo->prepare($sql); $st->execute($par);
$daftar = $st->fetchAll();

$judul = 'Cetak Laporan'; $menu_aktif = 'cetak';
include __DIR__ . '/../includes/header.php';
?>
<div class="judul no-print"><h1>Cetak Laporan</h1><p>Pilih periode dan status, lalu cetak rekap laporan.</p></div>
<form class="kartu no-print" method="get">
  <div class="filter">
    <div class="field"><label for="dari">Dari tanggal</label><input id="dari" type="date" name="dari" value="<?= e($dari) ?>"></div>
    <div class="field"><label for="sampai">Sampai tanggal</label><input id="sampai" type="date" name="sampai" value="<?= e($sampai) ?>"></div>
    <div class="field"><label for="st">Status</label><select id="st" name="status"><option value="">Semua status</option>
      <?php foreach (STATUS_LIST as $s): ?><option <?= $s === $status ? 'selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?></select></div>
    <button class="btn btn-garis" type="submit">Tampilkan</button>
    <button class="btn" type="button" onclick="window.print()">Cetak</button>
  </div>
</form>
<section class="kartu">
  <div>
    <h2 style="font-size:20px">Rekap Laporan Kerusakan Fasilitas Kampus</h2>
    <p class="sub" style="margin-top:4px">Periode: <?= $ok($dari) ? tgl($dari) : 'awal' ?> s.d. <?= $ok($sampai) ? tgl($sampai) : 'sekarang' ?> · Status: <?= e($status ?: 'Semua') ?> · Dicetak oleh <?= e($user['nama']) ?> pada <?= tgl(date('Y-m-d')) ?></p>
  </div>
  <div class="tabel-wrap"><table>
    <thead><tr><th>No</th><th>Kode</th><th>Tanggal</th><th>Pelapor</th><th>Fasilitas</th><th>Deskripsi</th><th>Teknisi</th><th>Status</th></tr></thead>
    <tbody>
    <?php foreach ($daftar as $i => $d): ?>
      <tr><td><?= $i + 1 ?></td><td><b><?= e($d['kode']) ?></b></td><td class="nowrap"><?= tgl($d['tanggal']) ?></td><td><?= e($d['pelapor']) ?></td>
        <td><?= e($d['nama_fasilitas'] . ', ' . $d['lokasi']) ?></td><td><?= e($d['deskripsi']) ?></td><td><?= e($d['teknisi'] ?? '-') ?></td><td><?= e($d['status']) ?></td></tr>
    <?php endforeach; if (!$daftar): ?><tr><td colspan="8" class="sub">Tidak ada data pada filter ini.</td></tr><?php endif; ?>
    </tbody></table></div>
  <p class="sub">Total: <?= count($daftar) ?> laporan</p>
</section>
<?php include __DIR__ . '/../includes/footer.php';
