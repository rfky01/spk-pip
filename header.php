<?php
// header.php - Template Bagian Atas & Proteksi Sesi Halaman SPK PIP
require_once "koneksi.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Proteksi akses: jika belum login, wajib dialihkan ke login.php
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

// Ambil data pengaturan sekolah
$res_pengaturan = mysqli_query($koneksi, "SELECT * FROM `pengaturan` WHERE id=1");
$pengaturan = mysqli_fetch_assoc($res_pengaturan) ?: [
    'nama_sekolah' => 'SMP Tunas Bangsa',
    'kuota_pip' => 27,
    'tahun_ajaran' => '2025/2026',
    'cr' => 0.0510,
    'status_konsistensi' => 'Konsisten'
];

// Handler Ubah Profil Admin & Pengaturan Sekolah (dari Modal & Halaman Profil)
$flash_message = "";
$flash_type = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_profile']) && in_array($_POST['action_profile'], ['update_profile', 'update_profil_sekolah'])) {
    $id_admin = $_SESSION['admin']['id_admin'] ?? 1;
    $nama_baru = trim($_POST['nama_admin'] ?? '');
    $password_baru = trim($_POST['password_baru'] ?? '');

    // 1. Update profil akun admin (jika field dikirim)
    if (!empty($nama_baru)) {
        if (!empty($password_baru)) {
            $hash = password_hash($password_baru, PASSWORD_DEFAULT);
            $stmt = mysqli_prepare($koneksi, "UPDATE `admin` SET `nama`=?, `password`=? WHERE `id_admin`=?");
            mysqli_stmt_bind_param($stmt, "ssi", $nama_baru, $hash, $id_admin);
        } else {
            $stmt = mysqli_prepare($koneksi, "UPDATE `admin` SET `nama`=? WHERE `id_admin`=?");
            mysqli_stmt_bind_param($stmt, "si", $nama_baru, $id_admin);
        }
        mysqli_stmt_execute($stmt);
        $_SESSION['admin']['nama'] = $nama_baru;
    }

    // 2. Update Pengaturan Sekolah: Yayasan, Nama Sekolah, Alamat, Kepala Sekolah, NIP, Tahun Ajaran, Jadwal
    $nama_yayasan_baru = trim($_POST['nama_yayasan'] ?? ($pengaturan['nama_yayasan'] ?? 'YAYASAN AL QODIRI LAMPUNG'));
    $nama_sekolah_baru = trim($_POST['nama_sekolah'] ?? $pengaturan['nama_sekolah']);
    $sub_instansi_baru = trim($_POST['sub_instansi'] ?? ($pengaturan['sub_instansi'] ?? 'BANDAR MATARAM LAMPUNG TENGAH'));
    $alamat_sekolah_baru = trim($_POST['alamat_sekolah'] ?? ($pengaturan['alamat_sekolah'] ?? 'Jln. Lapangan Merdeka Raman Agung Mataram Udik Kec. Bandar mataram Lampung Tengah'));
    $kepala_sekolah_baru = trim($_POST['kepala_sekolah'] ?? $pengaturan['kepala_sekolah']);
    $nip_kepala_sekolah_baru = trim($_POST['nip_kepala_sekolah'] ?? ($pengaturan['nip_kepala_sekolah'] ?? '-'));
    $tahun_ajaran_baru = trim($_POST['tahun_ajaran'] ?? $pengaturan['tahun_ajaran']);
    $tgl_buka_baru = trim($_POST['tgl_buka_pengajuan'] ?? $pengaturan['tgl_buka_pengajuan']);
    $tgl_tutup_baru = trim($_POST['tgl_tutup_pengajuan'] ?? $pengaturan['tgl_tutup_pengajuan']);
    $kuota_baru = isset($_POST['kuota_pip']) && (int)$_POST['kuota_pip'] > 0 ? (int)$_POST['kuota_pip'] : (int)$pengaturan['kuota_pip'];

    // Update database pengaturan
    $stmt_p = mysqli_prepare($koneksi, "UPDATE `pengaturan` SET `nama_yayasan`=?, `nama_sekolah`=?, `sub_instansi`=?, `alamat_sekolah`=?, `kepala_sekolah`=?, `nip_kepala_sekolah`=?, `tahun_ajaran`=?, `tgl_buka_pengajuan`=?, `tgl_tutup_pengajuan`=?, `kuota_pip`=? WHERE `id`=1");
    mysqli_stmt_bind_param($stmt_p, "sssssssssi", $nama_yayasan_baru, $nama_sekolah_baru, $sub_instansi_baru, $alamat_sekolah_baru, $kepala_sekolah_baru, $nip_kepala_sekolah_baru, $tahun_ajaran_baru, $tgl_buka_baru, $tgl_tutup_baru, $kuota_baru);
    mysqli_stmt_execute($stmt_p);

    $pengaturan['nama_yayasan'] = $nama_yayasan_baru;
    $pengaturan['nama_sekolah'] = $nama_sekolah_baru;
    $pengaturan['sub_instansi'] = $sub_instansi_baru;
    $pengaturan['alamat_sekolah'] = $alamat_sekolah_baru;
    $pengaturan['kepala_sekolah'] = $kepala_sekolah_baru;
    $pengaturan['nip_kepala_sekolah'] = $nip_kepala_sekolah_baru;
    $pengaturan['tahun_ajaran'] = $tahun_ajaran_baru;
    $pengaturan['tgl_buka_pengajuan'] = $tgl_buka_baru;
    $pengaturan['tgl_tutup_pengajuan'] = $tgl_tutup_baru;
    $pengaturan['kuota_pip'] = $kuota_baru;

    // 3. Handle Reset Logo (kembali ke default)
    if (isset($_POST['reset_logo']) && $_POST['reset_logo'] === '1') {
        if (!empty($pengaturan['logo']) && file_exists($pengaturan['logo']) && strpos($pengaturan['logo'], 'logo_default.png') === false) {
            @unlink($pengaturan['logo']);
        }
        mysqli_query($koneksi, "UPDATE `pengaturan` SET `logo` = 'uploads/logo_default.png' WHERE id=1");
        $pengaturan['logo'] = 'uploads/logo_default.png';
    }

    // 4. Handle Upload File Logo Baru
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $file_name = $_FILES['logo']['name'];
        $file_tmp  = $_FILES['logo']['tmp_name'];
        $file_size = $_FILES['logo']['size'];
        $ext       = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        if (in_array($ext, $allowed)) {
            if ($file_size <= 5 * 1024 * 1024) { // Maks 5 MB
                if (!is_dir('uploads')) {
                    @mkdir('uploads', 0777, true);
                }
                $new_logo_name = 'uploads/logo_sekolah_' . time() . '.' . $ext;
                if (move_uploaded_file($file_tmp, $new_logo_name)) {
                    // Hapus file logo lama jika bukan logo_default.png
                    if (!empty($pengaturan['logo']) && file_exists($pengaturan['logo']) && strpos($pengaturan['logo'], 'logo_default.png') === false) {
                        @unlink($pengaturan['logo']);
                    }
                    mysqli_query($koneksi, "UPDATE `pengaturan` SET `logo` = '" . mysqli_real_escape_string($koneksi, $new_logo_name) . "' WHERE id=1");
                    $pengaturan['logo'] = $new_logo_name;
                } else {
                    $flash_message = "Gagal memindahkan file logo yang diunggah ke folder server.";
                    $flash_type = "error";
                }
            } else {
                $flash_message = "Ukuran file logo terlalu besar! Maksimal 5 MB.";
                $flash_type = "error";
            }
        } else {
            $flash_message = "Format file logo tidak valid! Gunakan format gambar PNG, JPG, JPEG, atau WEBP.";
            $flash_type = "error";
        }
    }

    if (empty($flash_message)) {
        $flash_message = "Profil sekolah & pengaturan sistem berhasil diperbarui!";
        $flash_type = "success";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'SPK Penerima PIP (AHP)' ?> - <?= htmlspecialchars($pengaturan['nama_sekolah']) ?></title>
    <!-- Google Fonts: Open Sans -->
    <!-- Google Fonts: Roboto -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300..800;1,300..800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,300;0,400;0,500;0,700;1,300;1,400;1,500;1,700&display=swap" rel="stylesheet">
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Open Sans"', 'sans-serif'],
                        sans: ['"Roboto"', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <!-- FontAwesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body, input, button, select, textarea {
            font-family: 'Roboto', sans-serif !important;
        }
        .active-nav {
            background-color: #f1f5f9 !important;
            color: #0f172a !important;
            font-weight: 600 !important;
            border-left: 3px solid #1e293b !important;
        }
        #app-sidebar .active-nav i {
            color: #0f172a !important;
        }
        
        /* Styling Mode Navigasi Ciut / Mini Sidebar */
        #app-sidebar {
            transition: width 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }
        #app-sidebar.sidebar-collapsed {
            width: 5rem !important; /* 80px */
        }
        #app-sidebar.sidebar-collapsed .sidebar-text {
            display: none !important;
        }
        #app-sidebar.sidebar-collapsed .sidebar-body {
            padding-left: 0.625rem !important;
            padding-right: 0.625rem !important;
        }
        #app-sidebar.sidebar-collapsed .sidebar-header {
            flex-direction: column !important;
            align-items: center !important;
            justify-content: center !important;
            padding-left: 0 !important;
            padding-right: 0 !important;
            gap: 0.625rem !important;
        }
        #app-sidebar.sidebar-collapsed .sidebar-nav {
            padding-left: 0 !important;
            padding-right: 0 !important;
        }
        #app-sidebar.sidebar-collapsed .sidebar-nav-link {
            justify-content: center !important;
            padding-left: 0 !important;
            padding-right: 0 !important;
            gap: 0 !important;
        }
        #app-sidebar.sidebar-collapsed .sidebar-nav-link i {
            margin: 0 !important;
            font-size: 1.15rem !important;
        }
        #app-sidebar.sidebar-collapsed .sidebar-footer {
            padding-left: 0.5rem !important;
            padding-right: 0.5rem !important;
        }
        #app-sidebar.sidebar-collapsed .sidebar-profile-box {
            justify-content: center !important;
            margin: 0 !important;
            padding: 0.35rem 0 !important;
        }
        #app-sidebar.sidebar-collapsed .sidebar-logout-btn {
            padding-left: 0 !important;
            padding-right: 0 !important;
            justify-content: center !important;
        }
        /* Custom scrollbar modern dan halus */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: transparent;
        }
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 9999px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    </style>
</head>
<body class="bg-slate-100/80 font-sans antialiased text-slate-800 h-screen w-full overflow-hidden">

<div class="h-screen w-full flex overflow-hidden">

