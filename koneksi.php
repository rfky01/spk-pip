<?php
// koneksi.php - Koneksi Database MySQL dan Otomasi Migrasi Tabel SPK PIP

$host = "localhost";
$user = "root";
$pass = "";
$db_name = "spk_pip";

// 1. Coba hubungkan langsung ke database
$koneksi = @mysqli_connect($host, $user, $pass, $db_name);

// 2. Jika koneksi langsung gagal (misal database belum dibuat di localhost Laragon), coba inisialisasi
if (!$koneksi) {
    $koneksi_server = @mysqli_connect($host, $user, $pass);
    if ($koneksi_server) {
        @mysqli_query($koneksi_server, "CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        @mysqli_close($koneksi_server);
        $koneksi = @mysqli_connect($host, $user, $pass, $db_name);
    }
}

if (!$koneksi) {
    die("Koneksi database gagal: " . mysqli_connect_error() . "<br><small>Silakan periksa pengaturan \$host, \$user, \$pass, dan \$db_name pada file koneksi.php</small>");
}

// 4. Inisialisasi tabel-tabel sesuai Class Diagram Skripsi
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

    // Auto-migrate kolom sekolah_asal jika belum ada
    $cek_col = mysqli_query($conn, "SHOW COLUMNS FROM `calon_penerima` LIKE 'sekolah_asal'");
    if (mysqli_num_rows($cek_col) == 0) {
        mysqli_query($conn, "ALTER TABLE `calon_penerima` ADD COLUMN `sekolah_asal` VARCHAR(100) NULL AFTER `kelas`");
    }

    // Auto-migrate kolom pin jika belum ada (untuk keamanan login siswa/wali)
    $cek_col_pin = mysqli_query($conn, "SHOW COLUMNS FROM `calon_penerima` LIKE 'pin'");
    if (mysqli_num_rows($cek_col_pin) == 0) {
        mysqli_query($conn, "ALTER TABLE `calon_penerima` ADD COLUMN `pin` VARCHAR(255) NULL AFTER `no_hp`");
    }

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

    // SEEDING DATA AWAL JIKA KOSONG

    // 1. Akun Admin Default: admin / admin123
    $cek_admin = mysqli_query($conn, "SELECT id_admin FROM `admin` LIMIT 1");
    if (mysqli_num_rows($cek_admin) == 0) {
        $hash_pass = password_hash('admin123', PASSWORD_DEFAULT);
        mysqli_query($conn, "INSERT INTO `admin` (`nama`, `username`, `password`, `level`) VALUES 
            ('Administrator Sekolah', 'admin', '$hash_pass', 'admin')");
    }

    // 2. Kriteria Default Sesuai Bab 3 Skripsi (Tabel 3.2 & 3.5)
    $cek_kriteria = mysqli_query($conn, "SELECT id_kriteria FROM `kriteria` LIMIT 1");
    if (mysqli_num_rows($cek_kriteria) == 0) {
        mysqli_query($conn, "INSERT INTO `kriteria` (`kode_kriteria`, `nama_kriteria`, `bobot`, `atribut`) VALUES
            ('C1', 'Penghasilan Orang Tua', 0.4165, 'cost'),
            ('C2', 'Jumlah Tanggungan Keluarga', 0.2619, 'benefit'),
            ('C3', 'Kondisi Tempat Tinggal', 0.1608, 'benefit'),
            ('C4', 'Prestasi Akademik Siswa', 0.0985, 'benefit'),
            ('C5', 'Jarak Tempat Tinggal ke Sekolah', 0.0623, 'benefit')");
    }

    // 3. Matriks Perbandingan Berpasangan Default Sesuai Tabel 3.3 Skripsi
    $cek_matriks = mysqli_query($conn, "SELECT id FROM `matriks_kriteria` LIMIT 1");
    if (mysqli_num_rows($cek_matriks) == 0) {
        $default_matrix = [
            ['C1', 'C1', 1.00], ['C1', 'C2', 2.00], ['C1', 'C3', 3.00], ['C1', 'C4', 4.00], ['C1', 'C5', 5.00],
            ['C2', 'C1', 0.50], ['C2', 'C2', 1.00], ['C2', 'C3', 2.00], ['C2', 'C4', 3.00], ['C2', 'C5', 4.00],
            ['C3', 'C1', 0.33], ['C3', 'C2', 0.50], ['C3', 'C3', 1.00], ['C3', 'C4', 2.00], ['C3', 'C5', 3.00],
            ['C4', 'C1', 0.25], ['C4', 'C2', 0.33], ['C4', 'C3', 0.50], ['C4', 'C4', 1.00], ['C4', 'C5', 2.00],
            ['C5', 'C1', 0.20], ['C5', 'C2', 0.25], ['C5', 'C3', 0.33], ['C5', 'C4', 0.50], ['C5', 'C5', 1.00]
        ];
        foreach ($default_matrix as $m) {
            mysqli_query($conn, "INSERT INTO `matriks_kriteria` (`kriteria_1`, `kriteria_2`, `nilai`) VALUES ('$m[0]', '$m[1]', $m[2])");
        }
    }

    // 4. Data Pengaturan Default
    $cek_pengaturan = mysqli_query($conn, "SELECT id FROM `pengaturan` WHERE id=1");
    if (mysqli_num_rows($cek_pengaturan) == 0) {
        mysqli_query($conn, "INSERT INTO `pengaturan` (`id`, `nama_sekolah`, `kepala_sekolah`, `kuota_pip`, `tahun_ajaran`, `tgl_buka_pengajuan`, `tgl_tutup_pengajuan`, `lambda_max`, `ci`, `cr`, `status_konsistensi`) VALUES
            (1, 'SMP Tunas Bangsa', 'Fitri Wiyatni, S.Pd.I', 27, '2025/2026', '2026-09-01', '2026-10-31', 5.2300, 0.0570, 0.0510, 'Konsisten')");
        mysqli_query($conn, "INSERT INTO `pengaturan` (`id`, `nama_sekolah`, `logo`, `kepala_sekolah`, `kuota_pip`, `tahun_ajaran`, `tgl_buka_pengajuan`, `tgl_tutup_pengajuan`, `lambda_max`, `ci`, `cr`, `status_konsistensi`) VALUES
            (1, 'SMP Tunas Bangsa', 'uploads/logo_default.png', 'Fitri Wiyatni, S.Pd.I', 27, '2025/2026', '2026-09-01', '2026-10-31', 5.2300, 0.0570, 0.0510, 'Konsisten')");
    } else {
        // Auto-migrate kolom logo jika belum ada
        $cek_col_logo = mysqli_query($conn, "SHOW COLUMNS FROM `pengaturan` LIKE 'logo'");
        if (mysqli_num_rows($cek_col_logo) == 0) {
            mysqli_query($conn, "ALTER TABLE `pengaturan` ADD COLUMN `logo` VARCHAR(255) NULL DEFAULT 'uploads/logo_default.png' AFTER `nama_sekolah`");
            mysqli_query($conn, "UPDATE `pengaturan` SET `logo` = 'uploads/logo_default.png' WHERE id=1");
        }

        // Auto-migrate kolom nip_kepala_sekolah jika belum ada
        $cek_col_nip = mysqli_query($conn, "SHOW COLUMNS FROM `pengaturan` LIKE 'nip_kepala_sekolah'");
        if (mysqli_num_rows($cek_col_nip) == 0) {
            mysqli_query($conn, "ALTER TABLE `pengaturan` ADD COLUMN `nip_kepala_sekolah` VARCHAR(50) NULL DEFAULT '-' AFTER `kepala_sekolah`");
            mysqli_query($conn, "UPDATE `pengaturan` SET `nip_kepala_sekolah` = '-' WHERE id=1");
        }

        // Auto-migrate kolom KOP Surat resmi (nama_yayasan, sub_instansi, alamat_sekolah, logo_kiri)
        $cek_col_yayasan = mysqli_query($conn, "SHOW COLUMNS FROM `pengaturan` LIKE 'nama_yayasan'");
        if (mysqli_num_rows($cek_col_yayasan) == 0) {
            mysqli_query($conn, "ALTER TABLE `pengaturan` ADD COLUMN `nama_yayasan` VARCHAR(150) NULL DEFAULT 'YAYASAN AL QODIRI LAMPUNG' AFTER `id`");
            mysqli_query($conn, "UPDATE `pengaturan` SET `nama_yayasan` = 'YAYASAN AL QODIRI LAMPUNG' WHERE id=1");
        }

        $cek_col_sub = mysqli_query($conn, "SHOW COLUMNS FROM `pengaturan` LIKE 'sub_instansi'");
        if (mysqli_num_rows($cek_col_sub) == 0) {
            mysqli_query($conn, "ALTER TABLE `pengaturan` ADD COLUMN `sub_instansi` VARCHAR(150) NULL DEFAULT 'BANDAR MATARAM LAMPUNG TENGAH' AFTER `nama_sekolah`");
            mysqli_query($conn, "UPDATE `pengaturan` SET `sub_instansi` = 'BANDAR MATARAM LAMPUNG TENGAH' WHERE id=1");
        }

        $cek_col_alamat = mysqli_query($conn, "SHOW COLUMNS FROM `pengaturan` LIKE 'alamat_sekolah'");
        if (mysqli_num_rows($cek_col_alamat) == 0) {
            mysqli_query($conn, "ALTER TABLE `pengaturan` ADD COLUMN `alamat_sekolah` TEXT NULL AFTER `sub_instansi`");
            mysqli_query($conn, "UPDATE `pengaturan` SET `alamat_sekolah` = 'Jln. Lapangan Merdeka Raman Agung Mataram Udik Kec. Bandar mataram Lampung Tengah' WHERE id=1");
        }

        $cek_col_logokiri = mysqli_query($conn, "SHOW COLUMNS FROM `pengaturan` LIKE 'logo_kiri'");
        if (mysqli_num_rows($cek_col_logokiri) == 0) {
            mysqli_query($conn, "ALTER TABLE `pengaturan` ADD COLUMN `logo_kiri` VARCHAR(255) NULL DEFAULT 'uploads/logo_lampung_tengah.png' AFTER `alamat_sekolah`");
            mysqli_query($conn, "UPDATE `pengaturan` SET `logo_kiri` = 'uploads/logo_lampung_tengah.png' WHERE id=1");
        }

        // Auto-migrate kolom tgl_buka_pengajuan & tgl_tutup_pengajuan jika belum ada
        $cek_col_buka = mysqli_query($conn, "SHOW COLUMNS FROM `pengaturan` LIKE 'tgl_buka_pengajuan'");
        if (mysqli_num_rows($cek_col_buka) == 0) {
            mysqli_query($conn, "ALTER TABLE `pengaturan` ADD COLUMN `tgl_buka_pengajuan` DATE NULL DEFAULT '2026-09-01' AFTER `tahun_ajaran`");
        }
        $cek_col_tutup = mysqli_query($conn, "SHOW COLUMNS FROM `pengaturan` LIKE 'tgl_tutup_pengajuan'");
        if (mysqli_num_rows($cek_col_tutup) == 0) {
            mysqli_query($conn, "ALTER TABLE `pengaturan` ADD COLUMN `tgl_tutup_pengajuan` DATE NULL DEFAULT '2026-10-31' AFTER `tgl_buka_pengajuan`");
        }

        // Pastikan logo terisi jika kosong
        $cek_logo_val = mysqli_query($conn, "SELECT logo FROM `pengaturan` WHERE id=1");
        if ($r_logo = mysqli_fetch_assoc($cek_logo_val)) {
            if (empty($r_logo['logo'])) {
                mysqli_query($conn, "UPDATE `pengaturan` SET `logo` = 'uploads/logo_default.png' WHERE id=1");
            }
        }

        // Isi default jika masih kosong / NULL
        $cek_val = mysqli_query($conn, "SELECT tgl_buka_pengajuan, tgl_tutup_pengajuan FROM `pengaturan` WHERE id=1");
        if ($r_val = mysqli_fetch_assoc($cek_val)) {
            if (empty($r_val['tgl_buka_pengajuan']) || empty($r_val['tgl_tutup_pengajuan']) || $r_val['tgl_buka_pengajuan'] === '0000-00-00') {
                mysqli_query($conn, "UPDATE `pengaturan` SET `tgl_buka_pengajuan` = '2026-09-01', `tgl_tutup_pengajuan` = '2026-10-31' WHERE id=1");
            }
        }
    }

}

// Fungsi pembantu format tanggal Bahasa Indonesia (contoh: 20 September 2026)
if (!function_exists('format_tgl_indo')) {
    function format_tgl_indo($tanggal) {
        if (empty($tanggal) || $tanggal === '0000-00-00') return '-';
        $bulan = [
            1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
        ];
        $time = strtotime($tanggal);
        if (!$time) return $tanggal;
        $d = (int)date('d', $time);
        $m = (int)date('m', $time);
        $y = date('Y', $time);
        return sprintf('%02d', $d) . ' ' . ($bulan[$m] ?? '') . ' ' . $y;
    }
}

// Jalankan inisialisasi tabel
initDatabase($koneksi);
?>

