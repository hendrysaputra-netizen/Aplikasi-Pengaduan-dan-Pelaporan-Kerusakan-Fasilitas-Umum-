# Implementasi Aplikasi Pelaporan Kerusakan Fasilitas Kampus

Aplikasi web PHP native (PDO) + MySQL, sesuai diagram UML dan desain UI/UX.

## Cara menjalankan (XAMPP)
1. Salin folder `pelaporan` ke `C:\xampp\htdocs\`.
2. Jalankan Apache dan MySQL di XAMPP Control Panel.
3. Buka `http://localhost/phpmyadmin`, klik **Import**, pilih `database.sql`, lalu **Go**.
4. Buka `http://localhost/pelaporan/`.
5. Jika password MySQL bukan kosong, ubah `DB_PASS` di `config.php`.

## Akun contoh (password semua: `password123`)
| Peran | Email |
|---|---|
| Pelapor | budi@mail.com |
| Admin | siti@mail.com |
| Teknisi | agus@mail.com |

## Alur status
Menunggu Verifikasi -> Terverifikasi -> Ditugaskan -> Diproses -> Selesai (atau Ditolak).
Perubahan status divalidasi di sisi server, jadi urutan tidak bisa dilompati.

## Fitur per use case
- Registrasi Akun, Login: `register.php`, `index.php`, `logout.php`
- Membuat Laporan Kerusakan, Monitoring Laporan (Pelapor): `pelapor/buat.php`, `pelapor/laporan.php`
- Monitoring, Verifikasi, Penugasan Teknisi (Admin): `admin/monitoring.php`, `admin/verifikasi.php`
- Kelola Data Fasilitas: `admin/fasilitas.php` (fasilitas yang sudah punya laporan tidak bisa dihapus)
- Cetak Laporan: `admin/cetak.php` (filter tanggal dan status, tombol Cetak)
- Update Status Perbaikan, Upload Bukti Perbaikan (Teknisi): `teknisi/tugas.php`

## Keamanan
Prepared statement (anti SQL injection), `htmlspecialchars` (anti XSS), token CSRF, password di-hash (`password_hash`), validasi peran di tiap halaman, unggahan foto dicek tipe aslinya (JPG/PNG, maks 2 MB) dan diberi nama acak.

## Struktur
config.php, database.sql, index.php, register.php, logout.php, includes/ (header, footer), assets/style.css, pelapor/, admin/, teknisi/, uploads/ (foto unggahan).
