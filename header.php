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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_profile'])) {
    if ($_POST['action_profile'] === 'update_jadwal') {
        $tgl_buka_baru = trim($_POST['tgl_buka_pengajuan'] ?? $pengaturan['tgl_buka_pengajuan']);
        $tgl_tutup_baru = trim($_POST['tgl_tutup_pengajuan'] ?? $pengaturan['tgl_tutup_pengajuan']);

        $stmt_j = mysqli_prepare($koneksi, "UPDATE `pengaturan` SET `tgl_buka_pengajuan`=?, `tgl_tutup_pengajuan`=? WHERE `id`=1");
        mysqli_stmt_bind_param($stmt_j, "ss", $tgl_buka_baru, $tgl_tutup_baru);
        if (mysqli_stmt_execute($stmt_j)) {
            $pengaturan['tgl_buka_pengajuan'] = $tgl_buka_baru;
            $pengaturan['tgl_tutup_pengajuan'] = $tgl_tutup_baru;
            $flash_message = "Jadwal pendaftaran PIP berhasil diperbarui!";
            $flash_type = "success";
        } else {
            $flash_message = "Gagal memperbarui jadwal pendaftaran PIP.";
            $flash_type = "error";
        }
    } elseif (in_array($_POST['action_profile'], ['update_profile', 'update_akun'])) {
        $id_admin = $_SESSION['admin']['id_admin'] ?? 1;
        $nama_baru = trim($_POST['nama_admin'] ?? '');
        $password_baru = trim($_POST['password_baru'] ?? '');

        if (!empty($nama_baru)) {
            if (!empty($password_baru)) {
                if (strlen($password_baru) < 6) {
                    $flash_message = "Password baru minimal harus 6 karakter!";
                    $flash_type = "error";
                } else {
                    $hash = password_hash($password_baru, PASSWORD_DEFAULT);
                    $stmt = mysqli_prepare($koneksi, "UPDATE `admin` SET `nama`=?, `password`=? WHERE `id_admin`=?");
                    mysqli_stmt_bind_param($stmt, "ssi", $nama_baru, $hash, $id_admin);
                    mysqli_stmt_execute($stmt);
                    $_SESSION['admin']['nama'] = $nama_baru;
                    if ($password_baru === 'admin123') {
                        $flash_message = "Password akun admin berhasil di-reset kembali ke bawaan (admin123)!";
                    } else {
                        $flash_message = "Akun login admin & kata sandi berhasil diperbarui!";
                    }
                    $flash_type = "success";
                }
            } else {
                $stmt = mysqli_prepare($koneksi, "UPDATE `admin` SET `nama`=? WHERE `id_admin`=?");
                mysqli_stmt_bind_param($stmt, "si", $nama_baru, $id_admin);
                mysqli_stmt_execute($stmt);
                $_SESSION['admin']['nama'] = $nama_baru;
                $flash_message = "Nama akun admin berhasil diperbarui!";
                $flash_type = "success";
            }
        } else {
            $flash_message = "Nama lengkap admin tidak boleh kosong!";
            $flash_type = "error";
        }
    } elseif ($_POST['action_profile'] === 'update_profil_sekolah') {
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

    // 3. Handle Reset Logo (kembali ke standar sistem: Tut Wuri Handayani)
    if (isset($_POST['reset_logo']) && $_POST['reset_logo'] === '1') {
        if (!empty($pengaturan['logo']) && file_exists($pengaturan['logo']) 
            && strpos($pengaturan['logo'], 'logo_default.png') === false
            && strpos($pengaturan['logo'], 'logo_tut_wuri_handayani.png') === false
            && strpos($pengaturan['logo'], 'logo_smp_tunas_bangsa.png') === false
            && strpos($pengaturan['logo'], 'logo_lampung_tengah.png') === false) {
            @unlink($pengaturan['logo']);
        }
        mysqli_query($koneksi, "UPDATE `pengaturan` SET `logo` = 'uploads/logo_tut_wuri_handayani.png' WHERE id=1");
        $pengaturan['logo'] = 'uploads/logo_tut_wuri_handayani.png';
    }

    // 4. Handle Upload File Logo Baru
    elseif (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
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
                    // Hapus file logo kustom lama jika ada
                    if (!empty($pengaturan['logo']) && file_exists($pengaturan['logo']) 
                        && strpos($pengaturan['logo'], 'logo_default.png') === false
                        && strpos($pengaturan['logo'], 'logo_tut_wuri_handayani.png') === false
                        && strpos($pengaturan['logo'], 'logo_smp_tunas_bangsa.png') === false
                        && strpos($pengaturan['logo'], 'logo_lampung_tengah.png') === false) {
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

    // Jika request dikirim melalui AJAX (XMLHttpRequest / Fetch)
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status'  => $flash_type ?: 'success',
            'message' => $flash_message,
            'data'    => [
                'nama_yayasan'        => $pengaturan['nama_yayasan'] ?? '',
                'nama_sekolah'        => $pengaturan['nama_sekolah'] ?? '',
                'sub_instansi'        => $pengaturan['sub_instansi'] ?? '',
                'alamat_sekolah'      => $pengaturan['alamat_sekolah'] ?? '',
                'kepala_sekolah'      => $pengaturan['kepala_sekolah'] ?? '',
                'nip_kepala_sekolah'  => $pengaturan['nip_kepala_sekolah'] ?? '-',
                'tahun_ajaran'        => $pengaturan['tahun_ajaran'] ?? '',
                'kuota_pip'           => (int)($pengaturan['kuota_pip'] ?? 0),
                'logo'                => $pengaturan['logo'] ?? '',
                'tgl_buka_pengajuan'  => $pengaturan['tgl_buka_pengajuan'] ?? '',
                'tgl_tutup_pengajuan' => $pengaturan['tgl_tutup_pengajuan'] ?? '',
            ]
        ]);
        exit;
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
        /* ========================================================
           FULL DARK NAVY MODE - SPK PIP SYSTEM THEME
           Base Canvas: #0B192C | Cards/Surface: #112240 | Borders: #1E3A5F
           Inner Panels: #07101E | Accent: #2563EB / #3B82F6
           ======================================================== */

        body {
            background-color: #0B192C !important;
            color: #F8FAFC !important;
        }

        main, main > div {
            background-color: #0B192C !important;
            color: #F8FAFC !important;
        }

        /* Card & Surface Elements */
        .bg-white, [class*="bg-white"] {
            background-color: #112240 !important;
            color: #F8FAFC !important;
        }

        /* Inner Panels / Secondary Backgrounds */
        .bg-slate-50, .bg-slate-100, .bg-slate-50\/80, .bg-slate-100\/80,
        .bg-gray-50, .bg-gray-100,
        [class*="bg-slate-50"], [class*="bg-slate-100"] {
            background-color: #0B192C !important;
            color: #E2E8F0 !important;
        }

        /* Hover States for Lists and Rows */
        .hover\:bg-slate-50:hover, .hover\:bg-gray-50:hover, .hover\:bg-slate-100:hover,
        .hover\:bg-slate-50\/70:hover {
            background-color: #162B4D !important;
        }

        /* Borders & Dividers */
        .border-slate-100, .border-slate-200, .border-slate-300,
        .border-gray-100, .border-gray-200, .border-gray-300,
        .divide-slate-100 > :not([hidden]) ~ :not([hidden]),
        .divide-slate-200 > :not([hidden]) ~ :not([hidden]),
        .divide-gray-100 > :not([hidden]) ~ :not([hidden]),
        .divide-gray-200 > :not([hidden]) ~ :not([hidden]) {
            border-color: #1E3A5F !important;
        }

        /* Border top accent on cards (e.g. dashboard cards) */
        .border-t-slate-800 {
            border-top-color: #3B82F6 !important;
        }

        /* Headings and Primary Text */
        h1, h2, h3, h4, h5, h6,
        .text-slate-900, .text-slate-800,
        .text-gray-900, .text-gray-800 {
            color: #FFFFFF !important;
        }

        /* Body and Secondary Text */
        .text-slate-700, .text-slate-600,
        .text-gray-700, .text-gray-600 {
            color: #CBD5E1 !important;
        }

        .text-slate-500, .text-slate-400,
        .text-gray-500, .text-gray-400 {
            color: #94A3B8 !important;
        }

        /* Tables */
        table {
            border-color: #1E3A5F !important;
        }
        table thead, table thead tr, table thead th {
            background-color: #07101E !important;
            color: #94A3B8 !important;
            border-color: #1E3A5F !important;
        }
        table tbody tr {
            background-color: #112240 !important;
            color: #F1F5F9 !important;
            border-color: #1E3A5F !important;
        }
        table tbody tr:hover {
            background-color: #162B4D !important;
        }
        table td {
            border-color: #1E3A5F !important;
        }
        table tfoot, table tfoot tr, table tfoot td {
            background-color: #07101E !important;
            color: #FFFFFF !important;
            border-color: #1E3A5F !important;
        }

        /* Forms, Inputs, Selects & Textareas - Full Dark Scheme Integration */
        :root {
            color-scheme: dark;
        }

        input, select, textarea {
            color-scheme: dark !important;
        }

        input:not([type="checkbox"]):not([type="radio"]):not([type="submit"]):not([type="button"]):not([type="file"]),
        select, textarea {
            background-color: #07101E !important;
            color: #FFFFFF !important;
            border: 1px solid #1E3A5F !important;
        }
        input:not([type="checkbox"]):not([type="radio"]):not([type="submit"]):not([type="button"]):not([type="file"]):focus,
        select:focus, textarea:focus {
            border-color: #3B82F6 !important;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.3) !important;
            outline: none !important;
        }
        input:disabled, select:disabled, textarea:disabled {
            background-color: #0B192C !important;
            color: #64748B !important;
            border-color: #1E3A5F !important;
            cursor: not-allowed !important;
        }
        select option {
            background-color: #112240 !important;
            color: #FFFFFF !important;
        }

        /* Number Input Spinners - Sembunyikan default spinner bawaan browser */
        input[type="number"] {
            color-scheme: dark !important;
            -moz-appearance: textfield !important;
        }
        input[type="number"]::-webkit-inner-spin-button,
        input[type="number"]::-webkit-outer-spin-button {
            -webkit-appearance: none !important;
            margin: 0 !important;
            display: none !important;
        }

        /* Buttons & Badges - Harmonious Dark Navy Theme */
        .bg-slate-900, .bg-slate-800,
        button.bg-blue-600, a.bg-blue-600:not([href*="status="]):not([href*="tab="]),
        button.bg-slate-900, a.bg-slate-900,
        button.bg-slate-800, a.bg-slate-800 {
            background-color: #162B4D !important;
            color: #FFFFFF !important;
            border: 1px solid #2E5A8F !important;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
        }
        .hover\:bg-slate-900:hover, .hover\:bg-slate-800:hover,
        .bg-slate-900:hover, .bg-slate-800:hover,
        button.bg-blue-600:hover, a.bg-blue-600:not([href*="status="]):not([href*="tab="]):hover,
        button.bg-slate-900:hover, a.bg-slate-900:hover,
        button.bg-slate-800:hover, a.bg-slate-800:hover {
            background-color: #1E3A5F !important;
            border-color: #3B82F6 !important;
            color: #FFFFFF !important;
            transform: scale(1.02) !important;
        }
        button.bg-white, a.bg-white {
            background-color: #112240 !important;
            color: #CBD5E1 !important;
            border: 1px solid #1E3A5F !important;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
        }
        button.bg-white:hover, a.bg-white:hover {
            background-color: #162B4D !important;
            color: #FFFFFF !important;
            border-color: #2E5A8F !important;
            transform: scale(1.02) !important;
        }
        a.bg-slate-100, span.bg-slate-100, button.bg-slate-100 {
            background-color: #162B4D !important;
            color: #93C5FD !important;
            border-color: #1E3A5F !important;
        }

        /* Harmonious Dark Navy Buttons & Filter Tabs */
        button[onclick*="openTambahModal"] {
            background-color: #162B4D !important;
            color: #FFFFFF !important;
            border: 1px solid #2E5A8F !important;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important;
            transition: all 0.2s ease !important;
        }
        button[onclick*="openTambahModal"]:hover {
            background-color: #1E3A5F !important;
            border-color: #3B82F6 !important;
            transform: scale(1.02) !important;
        }
        button[onclick*="openTambahModal"] i {
            color: #60A5FA !important;
        }

        /* Filter Tabs on data_calon_penerima.php & ranking.php */
        a[href*="data_calon_penerima.php?status="],
        a[href*="ranking.php?tab="] {
            transition: all 0.15s ease !important;
        }
        a[href*="data_calon_penerima.php?status="].bg-blue-600,
        a[href*="data_calon_penerima.php?status="].bg-\[\#162B4D\],
        a[href*="ranking.php?tab="].bg-blue-600,
        a[href*="ranking.php?tab="].bg-\[\#162B4D\] {
            background-color: #162B4D !important;
            color: #FFFFFF !important;
            border: 1px solid #3B82F6 !important;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1) !important;
        }
        a[href*="data_calon_penerima.php?status="].bg-blue-600 > span:last-child,
        a[href*="data_calon_penerima.php?status="].bg-\[\#162B4D\] > span:last-child,
        a[href*="ranking.php?tab="].bg-blue-600 > span:last-child,
        a[href*="ranking.php?tab="].bg-\[\#162B4D\] > span:last-child {
            background-color: #1E3A5F !important;
            color: #93C5FD !important;
            border: 1px solid #2E5A8F !important;
        }
        a[href*="data_calon_penerima.php?status="]:not(.bg-blue-600):not(.bg-\[\#162B4D\]),
        a[href*="ranking.php?tab="]:not(.bg-blue-600):not(.bg-\[\#162B4D\]) {
            background-color: #112240 !important;
            color: #CBD5E1 !important;
            border: 1px solid #1E3A5F !important;
        }
        a[href*="data_calon_penerima.php?status="]:not(.bg-blue-600):not(.bg-\[\#162B4D\]):hover,
        a[href*="ranking.php?tab="]:not(.bg-blue-600):not(.bg-\[\#162B4D\]):hover {
            background-color: #162B4D !important;
            color: #FFFFFF !important;
        }
        a[href*="data_calon_penerima.php?status="]:not(.bg-blue-600):not(.bg-\[\#162B4D\]) > span:last-child,
        a[href*="ranking.php?tab="]:not(.bg-blue-600):not(.bg-\[\#162B4D\]) > span:last-child {
            background-color: #07101E !important;
            color: #94A3B8 !important;
            border: 1px solid #1E3A5F !important;
        }


        /* Text Accents in Dark Navy */
        .text-indigo-600, .text-indigo-700, .text-indigo-800, .text-indigo-900 {
            color: #93C5FD !important;
        }
        .text-blue-700, .text-blue-800, .text-blue-900 {
            color: #60A5FA !important;
        }

        /* Sidebar Styling */
        #app-sidebar {
            background-color: #0B192C !important;
            border-color: #1E3A5F !important;
        }
        #app-sidebar .sidebar-header {
            border-color: #1E3A5F !important;
        }
        #app-sidebar .sidebar-footer {
            background-color: #0B192C !important;
            border-color: #1E3A5F !important;
        }
        #app-sidebar .active-nav {
            background-color: #162B4D !important;
            color: #60A5FA !important;
            font-weight: 600 !important;
            border-left: 3px solid #3B82F6 !important;
        }
        #app-sidebar .active-nav i {
            color: #60A5FA !important;
        }
        #app-sidebar .sidebar-nav-link:not(.active-nav) {
            color: #94A3B8 !important;
        }
        #app-sidebar .sidebar-nav-link:not(.active-nav):hover {
            background-color: #112240 !important;
            color: #FFFFFF !important;
        }
        #app-sidebar .sidebar-nav-link:not(.active-nav):hover i {
            color: #60A5FA !important;
        }
        #app-sidebar .sidebar-profile-box:hover {
            background-color: #112240 !important;
            border-color: #1E3A5F !important;
        }
        #app-sidebar .sidebar-logout-btn {
            background-color: #112240 !important;
            border-color: #1E3A5F !important;
            color: #FDA4AF !important;
        }
        #app-sidebar .sidebar-logout-btn:hover {
            background-color: rgba(159, 18, 57, 0.4) !important;
            border-color: #9F1239 !important;
            color: #F43F5E !important;
        }


        /* Pills, Badges & Numbers with High Contrast in Dark Navy */
        .bg-slate-200, .bg-slate-300, .bg-gray-200, .bg-gray-300,
        [class*="bg-slate-200"], [class*="bg-slate-300"],
        .bg-slate-200\/90, .bg-slate-200\/70 {
            background-color: #1E3A5F !important;
            color: #93C5FD !important;
            border-color: #2E5A8F !important;
        }
        .bg-slate-200 *, .bg-slate-300 *, [class*="bg-slate-200"] *, .bg-slate-200\/90 * {
            color: #93C5FD !important;
        }

        /* Amber & Warning Alerts in Dark Navy */
        .bg-amber-50, .bg-amber-100, .bg-amber-50\/80, .bg-amber-100\/80,
        [class*="bg-amber-50"], [class*="bg-amber-100"],
        .border-amber-200, .border-amber-300, .border-amber-200\/80,
        [class*="border-amber-200"], [class*="border-amber-300"] {
            background-color: rgba(69, 26, 3, 0.75) !important;
            border-color: #B45309 !important;
            color: #FDE68A !important;
        }
        [class*="bg-amber-50"] *, [class*="bg-amber-100"] * {
            color: #FDE68A !important;
        }
        [class*="bg-amber-50"] b, [class*="bg-amber-100"] b,
        [class*="bg-amber-50"] strong, [class*="bg-amber-100"] strong {
            color: #FFFFFF !important;
        }
        [class*="bg-amber-50"] a, [class*="bg-amber-100"] a {
            color: #93C5FD !important;
        }
        [class*="bg-amber-50"] a:hover, [class*="bg-amber-100"] a:hover {
            color: #BFDBFE !important;
        }


        /* Stat Cards Interactive Hover Animation (Mengembang & Berubah Warna) */
        .stat-card-interactive {
            transition: transform 0.28s cubic-bezier(0.34, 1.56, 0.64, 1), 
                        background-color 0.25s ease, 
                        border-color 0.25s ease, 
                        box-shadow 0.28s ease !important;
            will-change: transform, box-shadow;
            position: relative;
        }

        .stat-card-interactive:hover {
            transform: translateY(-5px) scale(1.025) !important;
            background-color: #172E54 !important;
            border-color: #3B82F6 !important;
            box-shadow: 0 16px 32px -8px rgba(11, 25, 44, 0.75), 0 0 20px 3px rgba(59, 130, 246, 0.28) !important;
            z-index: 10;
        }

        .stat-card-interactive:hover .stat-card-title {
            color: #93C5FD !important;
        }

        .stat-card-interactive:hover .stat-card-arrow {
            transform: translateX(4px);
            color: #60A5FA !important;
        }

        /* Glowing Status Badges for Schedule Banner */
        .badge-glow-aktif {
            background: linear-gradient(135deg, #059669 0%, #10B981 100%) !important;
            color: #FFFFFF !important;
            border: 1px solid #34D399 !important;
            box-shadow: 0 0 16px rgba(16, 185, 129, 0.65), 0 2px 4px rgba(0, 0, 0, 0.25) !important;
            font-weight: 800 !important;
            letter-spacing: 0.02em !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 6px !important;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.35) !important;
        }
        .badge-glow-aktif i {
            color: #FFFFFF !important;
        }

        .badge-glow-menunggu {
            background: linear-gradient(135deg, #D97706 0%, #F59E0B 100%) !important;
            color: #FFFFFF !important;
            border: 1px solid #FCD34D !important;
            box-shadow: 0 0 16px rgba(245, 158, 11, 0.65), 0 2px 4px rgba(0, 0, 0, 0.25) !important;
            font-weight: 800 !important;
            letter-spacing: 0.02em !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 6px !important;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.35) !important;
        }
        .badge-glow-menunggu i {
            color: #FFFFFF !important;
        }

        .badge-glow-tutup {
            background: linear-gradient(135deg, #E11D48 0%, #F43F5E 100%) !important;
            color: #FFFFFF !important;
            border: 1px solid #FDA4AF !important;
            box-shadow: 0 0 16px rgba(244, 63, 94, 0.65), 0 2px 4px rgba(0, 0, 0, 0.25) !important;
            font-weight: 800 !important;
            letter-spacing: 0.02em !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 6px !important;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.35) !important;
        }
        .badge-glow-tutup i {
            color: #FFFFFF !important;
        }

        /* Glowing Action Button (Atur Jadwal) */
        .btn-glow-schedule {
            background: linear-gradient(135deg, #2563EB 0%, #3B82F6 100%) !important;
            color: #FFFFFF !important;
            border: 1.5px solid #60A5FA !important;
            box-shadow: 0 0 18px rgba(59, 130, 246, 0.6), 0 4px 10px rgba(11, 25, 44, 0.5) !important;
            font-weight: 700 !important;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3) !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 8px !important;
            transition: all 0.25s cubic-bezier(0.34, 1.56, 0.64, 1) !important;
            cursor: pointer !important;
        }
        .btn-glow-schedule:hover {
            background: linear-gradient(135deg, #1D4ED8 0%, #2563EB 100%) !important;
            border-color: #93C5FD !important;
            box-shadow: 0 0 26px rgba(59, 130, 246, 0.85), 0 6px 14px rgba(11, 25, 44, 0.6) !important;
            transform: translateY(-2px) scale(1.03) !important;
        }
        .btn-glow-schedule i {
            color: #FFFFFF !important;
            font-size: 13px !important;
        }

        /* Status Badges - Dark Mode Polished */
        .bg-emerald-50, .bg-emerald-100 {
            background-color: rgba(6, 78, 59, 0.45) !important;
            color: #6EE7B7 !important;
            border-color: #047857 !important;
        }
        .bg-emerald-50 i, .bg-emerald-100 i, .text-emerald-600, .text-emerald-700, .text-emerald-800 {
            color: #6EE7B7 !important;
        }

        .bg-rose-50, .bg-rose-100, .bg-red-50, .bg-red-100 {
            background-color: rgba(136, 19, 55, 0.45) !important;
            color: #FDA4AF !important;
            border-color: #BE123C !important;
        }
        .bg-rose-50 i, .bg-rose-100 i, .bg-red-50 i, .bg-red-100 i, .text-rose-600, .text-rose-700, .text-rose-800, .text-red-600, .text-red-700 {
            color: #FDA4AF !important;
        }

        .bg-amber-50, .bg-amber-100, .bg-yellow-50, .bg-yellow-100 {
            background-color: rgba(120, 53, 15, 0.45) !important;
            color: #FCD34D !important;
            border-color: #B45309 !important;
        }
        .bg-amber-50 i, .bg-amber-100 i, .text-amber-600, .text-amber-700, .text-amber-800, .text-amber-900 {
            color: #FCD34D !important;
        }

        .bg-blue-50, .bg-blue-100 {
            background-color: rgba(30, 58, 138, 0.45) !important;
            color: #93C5FD !important;
            border-color: #1D4ED8 !important;
        }

        /* Bright badges stay dark text */
        .bg-amber-400, .bg-yellow-400, .bg-amber-300, .bg-yellow-300 {
            color: #0F172A !important;
        }
        .bg-amber-400 *, .bg-yellow-400 * {
            color: #0F172A !important;
        }

        /* Modals & Backdrop */
        .fixed.inset-0.bg-black\/50, .fixed.inset-0.bg-slate-900\/60 {
            background-color: rgba(3, 7, 18, 0.75) !important;
        }

        /* Scrollbars */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: #0B192C;
        }
        ::-webkit-scrollbar-thumb {
            background: #1E3A5F;
            border-radius: 9999px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #2E5A8F;
        }

        @media print {
            *, *::before, *::after, html, body, main, table, tr, td, th, div, p, span, h1, h2, h3, h4, h5, h6 {
                font-family: 'Times New Roman', Times, serif !important;
            }
            body, main { background: white !important; color: black !important; }
            .no-print { display: none !important; }
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
<body class="bg-[#0B192C] font-sans antialiased text-slate-100 h-screen w-full overflow-hidden">

<?php 
// Memuat komponen universal popup notifikasi toast (Pojok Kanan Atas)
require_once "notifikasi.php"; 
?>

<div class="h-screen w-full flex overflow-hidden">

