-- Database Aplikasi Pelaporan Kerusakan Fasilitas Kampus (MySQL / MariaDB, XAMPP)
CREATE DATABASE IF NOT EXISTS db_pelaporan CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE db_pelaporan;

DROP TABLE IF EXISTS bukti_perbaikan;
DROP TABLE IF EXISTS laporan;
DROP TABLE IF EXISTS fasilitas;
DROP TABLE IF EXISTS pengguna;

CREATE TABLE pengguna (
  id_pengguna INT AUTO_INCREMENT PRIMARY KEY,
  nama        VARCHAR(100) NOT NULL,
  email       VARCHAR(100) NOT NULL UNIQUE,
  password    VARCHAR(255) NOT NULL,
  peran       ENUM('Pelapor','Admin','Teknisi') NOT NULL
) ENGINE=InnoDB;

CREATE TABLE fasilitas (
  id_fasilitas   INT AUTO_INCREMENT PRIMARY KEY,
  nama_fasilitas VARCHAR(100) NOT NULL,
  lokasi         VARCHAR(100) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE laporan (
  id_laporan    INT AUTO_INCREMENT PRIMARY KEY,
  kode          VARCHAR(10) NOT NULL UNIQUE,
  id_pelapor    INT NOT NULL,
  id_fasilitas  INT NOT NULL,
  deskripsi     TEXT NOT NULL,
  foto          VARCHAR(255) NULL,
  status        ENUM('Menunggu Verifikasi','Terverifikasi','Ditugaskan','Diproses','Selesai','Ditolak') NOT NULL DEFAULT 'Menunggu Verifikasi',
  tanggal       DATE NOT NULL,
  id_teknisi    INT NULL,
  catatan_admin VARCHAR(255) NULL,
  tgl_tugas     DATE NULL,
  tgl_selesai   DATE NULL,
  FOREIGN KEY (id_pelapor)   REFERENCES pengguna(id_pengguna),
  FOREIGN KEY (id_fasilitas) REFERENCES fasilitas(id_fasilitas),
  FOREIGN KEY (id_teknisi)   REFERENCES pengguna(id_pengguna)
) ENGINE=InnoDB;

CREATE TABLE bukti_perbaikan (
  id_bukti   INT AUTO_INCREMENT PRIMARY KEY,
  id_laporan INT NOT NULL,
  file       VARCHAR(255) NOT NULL,
  keterangan VARCHAR(255) NULL,
  tanggal    DATE NOT NULL,
  FOREIGN KEY (id_laporan) REFERENCES laporan(id_laporan) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Data contoh (password semua akun: password123)
INSERT INTO pengguna (nama,email,password,peran) VALUES
('Budi Santoso','budi@mail.com','$2y$10$rs4dVhNPXj4zGzQ5sqqUB.Nrn8UP5p39VEicsAWJh5vCTbxJB4ENK','Pelapor'),
('Siti Aminah','siti@mail.com','$2y$10$rs4dVhNPXj4zGzQ5sqqUB.Nrn8UP5p39VEicsAWJh5vCTbxJB4ENK','Admin'),
('Agus Pratama','agus@mail.com','$2y$10$rs4dVhNPXj4zGzQ5sqqUB.Nrn8UP5p39VEicsAWJh5vCTbxJB4ENK','Teknisi');

INSERT INTO fasilitas (nama_fasilitas,lokasi) VALUES
('Proyektor','Lab Komputer 3'),
('AC','Ruang 201');

INSERT INTO laporan (kode,id_pelapor,id_fasilitas,deskripsi,foto,status,tanggal,id_teknisi,catatan_admin,tgl_tugas,tgl_selesai) VALUES
('LK-001',1,1,'Proyektor tidak menyala',NULL,'Selesai','2026-10-12',3,'Periksa lampu proyektor','2026-10-13','2026-10-14'),
('LK-002',1,2,'AC tidak dingin',NULL,'Menunggu Verifikasi','2026-10-14',NULL,NULL,NULL,NULL);

INSERT INTO bukti_perbaikan (id_laporan,file,keterangan,tanggal) VALUES (1,'bukti_001.jpg','Lampu diganti','2026-10-14');
