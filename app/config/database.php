<?php
// app/config/database.php - Koneksi Database MySQL & Inisialisasi Otomatis SPK PIP

$host = "localhost";
$user = "root";
$pass = "";
$db_name = "spk_pip";

// Nonaktifkan uncaught exception mode default di PHP 8.1+ agar ditangani manual
mysqli_report(MYSQLI_REPORT_OFF);

// 1. Coba hubungkan langsung ke database
$koneksi = @mysqli_connect($host, $user, $pass, $db_name);

// 2. Jika database belum dibuat di localhost MySQL, buat otomatis
if (!$koneksi) {
    $koneksi_server = @mysqli_connect($host, $user, $pass);
    if ($koneksi_server) {
        @mysqli_query($koneksi_server, "CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        @mysqli_close($koneksi_server);
        $koneksi = @mysqli_connect($host, $user, $pass, $db_name);
    }
}

if (!$koneksi) {
    die("Koneksi database gagal: " . mysqli_connect_error() . "<br><small>Silakan periksa pengaturan \$host, \$user, \$pass, dan \$db_name pada file app/config/database.php</small>");
}

// 3. Set charset utf8mb4
mysqli_set_charset($koneksi, "utf8mb4");
$GLOBALS['koneksi'] = $koneksi;

// 4. Inisialisasi tabel-tabel sesuai Class Diagram Skripsi jika belum ada
function initDatabase($conn) {
    // Tabel Admin
    $sql_admin = "CREATE TABLE IF NOT EXISTS `admin` (
        `id_admin` INT AUTO_INCREMENT PRIMARY KEY,
        `nama` VARCHAR(100) NOT NULL,
        `username` VARCHAR(50) NOT NULL UNIQUE,
        `password` VARCHAR(255) NOT NULL,
        `level` VARCHAR(20) DEFAULT 'admin',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    mysqli_query($conn, $sql_admin);

    // Tabel Kriteria
    $sql_kriteria = "CREATE TABLE IF NOT EXISTS `kriteria` (
        `id_kriteria` INT AUTO_INCREMENT PRIMARY KEY,
        `kode_kriteria` VARCHAR(10) NOT NULL UNIQUE,
        `nama_kriteria` VARCHAR(100) NOT NULL,
        `bobot` DECIMAL(8, 4) DEFAULT 0.0000,
        `atribut` ENUM('cost', 'benefit') DEFAULT 'benefit'
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    mysqli_query($conn, $sql_kriteria);

    // Tabel Matriks Perbandingan Kriteria (5x5)
    $sql_matriks = "CREATE TABLE IF NOT EXISTS `matriks_kriteria` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `kriteria_1` VARCHAR(10) NOT NULL,
        `kriteria_2` VARCHAR(10) NOT NULL,
        `nilai` DECIMAL(10, 4) NOT NULL,
        UNIQUE KEY `unique_pair` (`kriteria_1`, `kriteria_2`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    mysqli_query($conn, $sql_matriks);

    // Tabel Calon Penerima PIP (Alternatif)
    $sql_calon = "CREATE TABLE IF NOT EXISTS `calon_penerima` (
        `id_siswa` INT AUTO_INCREMENT PRIMARY KEY,
        `nisn` VARCHAR(20) NOT NULL UNIQUE,
        `nama` VARCHAR(100) NOT NULL,
        `nama_ortu` VARCHAR(100) NULL,
        `no_hp` VARCHAR(20) NULL,
        `foto` VARCHAR(255) NULL,
        `pin` VARCHAR(255) NULL,
        `jenis_kelamin` ENUM('Laki-laki', 'Perempuan') DEFAULT 'Laki-laki',
        `kelas` VARCHAR(20) DEFAULT 'Kelas VII',
        `sekolah_asal` VARCHAR(100) NULL,
        `alamat` TEXT NULL,
        `penghasilan` INT NOT NULL DEFAULT 1,
        `tanggungan` INT NOT NULL DEFAULT 1,
        `kondisi_rumah` INT NOT NULL DEFAULT 1,
        `prestasi` INT NOT NULL DEFAULT 1,
        `jarak` INT NOT NULL DEFAULT 1,
        `total_skor` DECIMAL(8, 4) DEFAULT 0.0000,
        `ranking` INT DEFAULT 0,
        `tahun` VARCHAR(10) DEFAULT '2025/2026',
        `status_verifikasi` ENUM('Menunggu Verifikasi', 'Terverifikasi', 'Ditolak') DEFAULT 'Menunggu Verifikasi',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    mysqli_query($conn, $sql_calon);

    // Tabel Pengaturan & Log Perhitungan AHP
    $sql_pengaturan = "CREATE TABLE IF NOT EXISTS `pengaturan` (
        `id` INT PRIMARY KEY,
        `nama_yayasan` VARCHAR(150) DEFAULT 'YAYASAN AL QODIRI LAMPUNG',
        `nama_sekolah` VARCHAR(100) DEFAULT 'SMP TUNAS BANGSA',
        `sub_instansi` VARCHAR(150) DEFAULT 'BANDAR MATARAM LAMPUNG TENGAH',
        `alamat_sekolah` TEXT NULL,
        `logo_kiri` VARCHAR(255) DEFAULT 'uploads/logo_lampung_tengah.png',
        `logo` VARCHAR(255) DEFAULT 'uploads/logo_default.png',
        `kepala_sekolah` VARCHAR(100) DEFAULT 'Fitri Wiyatni, S.Pd.I',
        `nip_kepala_sekolah` VARCHAR(50) DEFAULT '-',
        `kuota_pip` INT DEFAULT 27,
        `tahun_ajaran` VARCHAR(20) DEFAULT '2025/2026',
        `tgl_buka_pengajuan` DATE NULL DEFAULT '2026-09-01',
        `tgl_tutup_pengajuan` DATE NULL DEFAULT '2026-10-31',
        `lambda_max` DECIMAL(8, 4) DEFAULT 5.2300,
        `ci` DECIMAL(8, 4) DEFAULT 0.0570,
        `cr` DECIMAL(8, 4) DEFAULT 0.0510,
        `status_konsistensi` VARCHAR(30) DEFAULT 'Konsisten'
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    mysqli_query($conn, $sql_pengaturan);

    // Seeding Admin jika kosong
    $cek_admin = mysqli_query($conn, "SELECT COUNT(*) as jml FROM `admin`");
    $row_admin = mysqli_fetch_assoc($cek_admin);
    if ($row_admin['jml'] == 0) {
        $default_pass = password_hash('admin123', PASSWORD_DEFAULT);
        $sql_seed_admin = "INSERT INTO `admin` (`nama`, `username`, `password`, `level`) VALUES 
            ('Administrator Sekolah', 'admin', '$default_pass', 'admin')";
        mysqli_query($conn, $sql_seed_admin);
    }

    // Seeding Pengaturan jika kosong
    $cek_pengaturan = mysqli_query($conn, "SELECT COUNT(*) as jml FROM `pengaturan` WHERE id=1");
    $row_pengaturan = mysqli_fetch_assoc($cek_pengaturan);
    if ($row_pengaturan['jml'] == 0) {
        $sql_seed_pengaturan = "INSERT INTO `pengaturan` (`id`, `nama_yayasan`, `nama_sekolah`, `sub_instansi`, `alamat_sekolah`, `logo_kiri`, `logo`, `kepala_sekolah`, `nip_kepala_sekolah`, `kuota_pip`, `tahun_ajaran`) VALUES 
            (1, 'YAYASAN AL QODIRI LAMPUNG', 'SMP Tunas Bangsa', 'BANDAR MATARAM LAMPUNG TENGAH', 'Jln. Lapangan Merdeka Raman Agung Mataram Udik Kec. Bandar mataram Lampung Tengah', 'uploads/logo_lampung_tengah.png', 'uploads/logo_smp_tunas_bangsa.png', 'Fitri Wiyatni, S.Pd.I', '-', 27, '2025/2026')";
        mysqli_query($conn, $sql_seed_pengaturan);
    }
}

// Jalankan inisialisasi tabel
initDatabase($koneksi);

