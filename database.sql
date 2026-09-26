-- Skema Database SPK PIP (Analytical Hierarchy Process) SMP Tunas Bangsa
-- Penyusun: Annisa (NIM: 2255202002)


-- 1. Tabel Admin (Pengelolaan Akun & Hak Akses)
DROP TABLE IF EXISTS `admin`;
CREATE TABLE `admin` (
  `id_admin` int(11) NOT NULL AUTO_INCREMENT,
  `nama` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `level` varchar(20) DEFAULT 'admin',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_admin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default password: admin123 (bcrypt)
INSERT INTO `admin` (`id_admin`, `nama`, `username`, `password`, `level`) VALUES
(1, 'Administrator Sekolah', 'admin', '$2y$10$O01o04mDkF0Yp9K9E3PfZ.zG.Xm5bL43e493o27ZqGj8QcZb59x2u', 'admin');

-- 2. Tabel Kriteria
DROP TABLE IF EXISTS `kriteria`;
CREATE TABLE `kriteria` (
  `id_kriteria` int(11) NOT NULL AUTO_INCREMENT,
  `kode_kriteria` varchar(10) NOT NULL UNIQUE,
  `nama_kriteria` varchar(100) NOT NULL,
  `bobot` decimal(8,4) DEFAULT 0.0000,
  `atribut` enum('cost','benefit') DEFAULT 'benefit',
  PRIMARY KEY (`id_kriteria`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `kriteria` (`id_kriteria`, `kode_kriteria`, `nama_kriteria`, `bobot`, `atribut`) VALUES
(1, 'C1', 'Penghasilan Orang Tua', 0.4165, 'cost'),
(2, 'C2', 'Jumlah Tanggungan Keluarga', 0.2619, 'benefit'),
(3, 'C3', 'Kondisi Tempat Tinggal', 0.1608, 'benefit'),
(4, 'C4', 'Prestasi Akademik Siswa', 0.0985, 'benefit'),
(5, 'C5', 'Jarak Tempat Tinggal ke Sekolah', 0.0623, 'benefit');

-- 3. Tabel Matriks Perbandingan Kriteria (5x5)
DROP TABLE IF EXISTS `matriks_kriteria`;
CREATE TABLE `matriks_kriteria` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kriteria_1` varchar(10) NOT NULL,
  `kriteria_2` varchar(10) NOT NULL,
  `nilai` decimal(10,4) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_pair` (`kriteria_1`,`kriteria_2`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `matriks_kriteria` (`kriteria_1`, `kriteria_2`, `nilai`) VALUES
('C1', 'C1', 1.0000), ('C1', 'C2', 2.0000), ('C1', 'C3', 3.0000), ('C1', 'C4', 4.0000), ('C1', 'C5', 5.0000),
('C2', 'C1', 0.5000), ('C2', 'C2', 1.0000), ('C2', 'C3', 2.0000), ('C2', 'C4', 3.0000), ('C2', 'C5', 4.0000),
('C3', 'C1', 0.3300), ('C3', 'C2', 0.5000), ('C3', 'C3', 1.0000), ('C3', 'C4', 2.0000), ('C3', 'C5', 3.0000),
('C4', 'C1', 0.2500), ('C4', 'C2', 0.3300), ('C4', 'C3', 0.5000), ('C4', 'C4', 1.0000), ('C4', 'C5', 2.0000),
('C5', 'C1', 0.2000), ('C5', 'C2', 0.2500), ('C5', 'C3', 0.3300), ('C5', 'C4', 0.5000), ('C5', 'C5', 1.0000);

-- 4. Tabel Calon Penerima PIP (Alternatif Siswa)
DROP TABLE IF EXISTS `calon_penerima`;
CREATE TABLE `calon_penerima` (
  `id_siswa` int(11) NOT NULL AUTO_INCREMENT,
  `nisn` varchar(20) NOT NULL UNIQUE,
  `nama` varchar(100) NOT NULL,
  `nama_ortu` varchar(100) DEFAULT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `jenis_kelamin` enum('Laki-laki','Perempuan') DEFAULT 'Laki-laki',
  `kelas` varchar(20) DEFAULT 'Kelas VII',
  `sekolah_asal` varchar(100) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `penghasilan` int(11) NOT NULL DEFAULT 1,
  `tanggungan` int(11) NOT NULL DEFAULT 1,
  `kondisi_rumah` int(11) NOT NULL DEFAULT 1,
  `prestasi` int(11) NOT NULL DEFAULT 1,
  `jarak` int(11) NOT NULL DEFAULT 1,
  `total_skor` decimal(8,4) DEFAULT 0.0000,
  `ranking` int(11) DEFAULT 0,
  `tahun` varchar(10) DEFAULT '2025/2026',
  `status_verifikasi` enum('Menunggu Verifikasi','Terverifikasi','Ditolak') DEFAULT 'Menunggu Verifikasi',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_siswa`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Tabel Pengaturan Sistem
DROP TABLE IF EXISTS `pengaturan`;
CREATE TABLE `pengaturan` (
  `id` int(11) NOT NULL,
  `nama_yayasan` varchar(150) DEFAULT 'YAYASAN AL QODIRI LAMPUNG',
  `nama_sekolah` varchar(100) DEFAULT 'SMP TUNAS BANGSA',
  `sub_instansi` varchar(150) DEFAULT 'BANDAR MATARAM LAMPUNG TENGAH',
  `alamat_sekolah` text DEFAULT 'Jln. Lapangan Merdeka Raman Agung Mataram Udik Kec. Bandar mataram Lampung Tengah',
  `logo_kiri` varchar(255) DEFAULT 'uploads/logo_lampung_tengah.png',
  `logo` varchar(255) DEFAULT 'uploads/logo_default.png',
  `kepala_sekolah` varchar(100) DEFAULT 'Fitri Wiyatni, S.Pd.I',
  `nip_kepala_sekolah` varchar(50) DEFAULT '-',
  `kuota_pip` int(11) DEFAULT 27,
  `tahun_ajaran` varchar(20) DEFAULT '2025/2026',
  `tgl_buka_pengajuan` date DEFAULT '2026-09-01',
  `tgl_tutup_pengajuan` date DEFAULT '2026-10-31',
  `lambda_max` decimal(8,4) DEFAULT 5.2300,
  `ci` decimal(8,4) DEFAULT 0.0570,
  `cr` decimal(8,4) DEFAULT 0.0510,
  `status_konsistensi` varchar(30) DEFAULT 'Konsisten',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `pengaturan` (`id`, `nama_yayasan`, `nama_sekolah`, `sub_instansi`, `alamat_sekolah`, `logo_kiri`, `logo`, `kepala_sekolah`, `nip_kepala_sekolah`, `kuota_pip`, `tahun_ajaran`, `tgl_buka_pengajuan`, `tgl_tutup_pengajuan`, `lambda_max`, `ci`, `cr`, `status_konsistensi`) VALUES
(1, 'YAYASAN AL QODIRI LAMPUNG', 'SMP TUNAS BANGSA', 'BANDAR MATARAM LAMPUNG TENGAH', 'Jln. Lapangan Merdeka Raman Agung Mataram Udik Kec. Bandar mataram Lampung Tengah', 'uploads/logo_lampung_tengah.png', 'uploads/logo_default.png', 'Fitri Wiyatni, S.Pd.I', '-', 27, '2025/2026', '2026-09-01', '2026-10-31', 5.2300, 0.0570, 0.0510, 'Konsisten');

