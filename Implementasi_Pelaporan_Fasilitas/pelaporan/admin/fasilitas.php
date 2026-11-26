<?php
require __DIR__ . '/../config.php';
$user = wajib_peran('Admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_cek();
    $aksi = $_POST['aksi'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    $nama = trim($_POST['nama'] ?? '');
    $lokasi = trim($_POST['lokasi'] ?? '');
    if ($aksi === 'hapus') {
        $n = $pdo->prepare('SELECT COUNT(*) FROM laporan WHERE id_fasilitas = ?'); $n->execute([$id]);
        if ($n->fetchColumn() > 0) flash('Fasilitas tidak dapat dihapus karena sudah memiliki laporan.', 'err');
        else { $pdo->prepare('DELETE FROM fasilitas WHERE id_fasilitas = ?')->execute([$id]); flash('Fasilitas dihapus.'); }
    } elseif (in_array($aksi, ['tambah', 'ubah'], true)) {
        if ($nama === '' || $lokasi === '' || mb_strlen($nama) > 100 || mb_strlen($lokasi) > 100) {
            flash('Nama dan lokasi wajib diisi (maksimal 100 karakter).', 'err');
        } elseif ($aksi === 'tambah') {
            $pdo->prepare('INSERT INTO fasilitas (nama_fasilitas,lokasi) VALUES (?,?)')->execute([$nama, $lokasi]);
            flash('Fasilitas ditambahkan.');
        } else {
            $pdo->prepare('UPDATE fasilitas SET nama_fasilitas = ?, lokasi = ? WHERE id_fasilitas = ?')->execute([$nama, $lokasi, $id]);
            flash('Fasilitas diperbarui.');
        }
    }
    redirect('admin/fasilitas.php');
}

$daftar = $pdo->query('SELECT f.*, (SELECT COUNT(*) FROM laporan l WHERE l.id_fasilitas = f.id_fasilitas) AS jml FROM fasilitas f ORDER BY nama_fasilitas, lokasi')->fetchAll();
$edit = null;
if (isset($_GET['ubah'])) foreach ($daftar as $d) if ((int)$d['id_fasilitas'] === (int)$_GET['ubah']) $edit = $d;

$judul = 'Data Fasilitas'; $menu_aktif = 'fasilitas';
include __DIR__ . '/../includes/header.php';
?>
<div class="judul"><h1>Data Fasilitas</h1><p>Kelola daftar fasilitas yang dapat dilaporkan.</p></div>
<div class="baris">
  <section class="kartu kartu-lebar">
    <div class="tabel-wrap"><table>
      <thead><tr><th>Fasilitas</th><th>Lokasi</th><th>Laporan</th><th>Aksi</th></tr></thead>
      <tbody>
      <?php foreach ($daftar as $d): ?>
        <tr>
          <td><b><?= e($d['nama_fasilitas']) ?></b></td><td><?= e($d['lokasi']) ?></td><td><?= (int)$d['jml'] ?></td>
          <td><div style="display:flex;gap:8px;flex-wrap:wrap">
            <a class="btn btn-kecil btn-garis" href="?ubah=<?= $d['id_fasilitas'] ?>">Ubah</a>
            <form method="post" onsubmit="return confirm('Hapus fasilitas ini?')"><?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= $d['id_fasilitas'] ?>">
              <button class="btn btn-kecil btn-bahaya" name="aksi" value="hapus" <?= $d['jml'] > 0 ? 'disabled title="Sudah memiliki laporan"' : '' ?>>Hapus</button></form>
          </div></td>
        </tr>
      <?php endforeach; if (!$daftar): ?><tr><td colspan="4" class="sub">Belum ada fasilitas.</td></tr><?php endif; ?>
      </tbody></table></div>
  </section>
  <form class="kartu kartu-sempit" method="post">
    <h2><?= $edit ? 'Ubah Fasilitas' : 'Tambah Fasilitas' ?></h2>
    <?= csrf_field() ?><input type="hidden" name="id" value="<?= $edit['id_fasilitas'] ?? 0 ?>">
    <div class="field"><label for="n">Nama fasilitas</label><input id="n" type="text" name="nama" required maxlength="100" value="<?= e($edit['nama_fasilitas'] ?? '') ?>"></div>
    <div class="field"><label for="lk">Lokasi</label><input id="lk" type="text" name="lokasi" required maxlength="100" value="<?= e($edit['lokasi'] ?? '') ?>"></div>
    <div style="display:flex;gap:12px;flex-wrap:wrap">
      <button class="btn" name="aksi" value="<?= $edit ? 'ubah' : 'tambah' ?>"><?= $edit ? 'Simpan' : 'Tambah' ?></button>
      <?php if ($edit): ?><a class="btn btn-garis" href="fasilitas.php">Batal</a><?php endif; ?>
    </div>
  </form>
</div>
<?php include __DIR__ . '/../includes/footer.php';
