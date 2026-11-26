<?php
// Konfigurasi koneksi database (XAMPP default)
const DB_HOST = 'localhost';
const DB_NAME = 'db_pelaporan';
const DB_USER = 'root';
const DB_PASS = '';

session_start();

try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    die('Koneksi database gagal. Pastikan MySQL di XAMPP aktif dan database db_pelaporan sudah diimpor.');
}

const STATUS_WARNA = [
    'Menunggu Verifikasi' => ['#FEF3C7', '#78350F', '#D97706'],
    'Terverifikasi'       => ['#E0E7FF', '#312E81', '#6366F1'],
    'Ditugaskan'          => ['#DBEAFE', '#1E3A8A', '#3B82F6'],
    'Diproses'            => ['#CFFAFE', '#164E63', '#06B6D4'],
    'Selesai'             => ['#DCFCE7', '#14532D', '#16A34A'],
    'Ditolak'             => ['#FEE2E2', '#7F1D1D', '#DC2626'],
];
const STATUS_LIST = ['Menunggu Verifikasi', 'Terverifikasi', 'Ditugaskan', 'Diproses', 'Selesai', 'Ditolak'];

function e($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function tgl($d): string {
    if (!$d) return '-';
    $b = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    $t = strtotime($d);
    return date('j', $t) . ' ' . $b[(int)date('n', $t)] . ' ' . date('Y', $t);
}

function pill(string $status): string {
    [$bg, $fg, $dot] = STATUS_WARNA[$status] ?? ['#eee', '#333', '#999'];
    return '<span class="pill" style="background:' . $bg . ';color:' . $fg . '"><span class="dot" style="background:' . $dot . '"></span>' . e($status) . '</span>';
}

function base_url(): string {
    // folder aplikasi relatif terhadap document root, tanpa subfolder peran
    $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    $dir = preg_replace('#/(pelapor|admin|teknisi)$#', '', $dir);
    return rtrim($dir, '/');
}

function redirect(string $path): never {
    header('Location: ' . base_url() . '/' . ltrim($path, '/'));
    exit;
}

function home_peran(string $peran): string {
    return ['Pelapor' => 'pelapor/laporan.php', 'Admin' => 'admin/monitoring.php', 'Teknisi' => 'teknisi/tugas.php'][$peran];
}

function wajib_peran(string $peran): array {
    if (empty($_SESSION['user'])) redirect('index.php');
    if ($_SESSION['user']['peran'] !== $peran) redirect(home_peran($_SESSION['user']['peran']));
    return $_SESSION['user'];
}

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . csrf_token() . '">'; }
function csrf_cek(): void {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(400);
        die('Permintaan tidak valid. Muat ulang halaman.');
    }
}

function flash(?string $pesan = null, string $tipe = 'ok'): ?array {
    if ($pesan !== null) { $_SESSION['flash'] = [$pesan, $tipe]; return null; }
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

// Simpan foto unggahan dengan validasi (JPG/PNG, maks 2 MB). Mengembalikan nama file atau melempar Exception.
function simpan_foto(array $f): string {
    if ($f['error'] !== UPLOAD_ERR_OK) throw new Exception('Unggah foto gagal.');
    if ($f['size'] > 2 * 1024 * 1024) throw new Exception('Ukuran foto maksimal 2 MB.');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png'][$mime] ?? null;
    if (!$ext) throw new Exception('Format foto harus JPG atau PNG.');
    $nama = bin2hex(random_bytes(12)) . '.' . $ext;
    if (!move_uploaded_file($f['tmp_name'], __DIR__ . '/uploads/' . $nama)) throw new Exception('Foto tidak dapat disimpan.');
    return $nama;
}
