<?php
// header.php - Template Bagian Atas & Proteksi Sesi Halaman SPK PIP
require_once __DIR__ . '/../../koneksi.php';

global $koneksi;
if (!isset($koneksi) || !$koneksi) {
    $koneksi = $GLOBALS['koneksi'] ?? null;
}
if (!$koneksi) {
    require __DIR__ . '/../../app/config/database.php';
}

// Pastikan URL selalu menggunakan format hash tersembunyi
enforce_hashed_url();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Proteksi akses: jika belum login, wajib dialihkan ke login.php
if (!isset($_SESSION['admin'])) {
    header("Location: index.php?v=bG9naW4");
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
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['action_profile'])) {
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
           CLEAN BRIGHT WHITE THEME - SPK PIP SYSTEM
           Base Canvas: #F8FAFC | Cards/Surface: #FFFFFF | Borders: #E2E8F0
           Inner Panels: #F8FAFC / #F1F5F9 | Accent: #2563EB / #3B82F6
           Text: #0F172A (Headings) | #334155 (Body) | #64748B (Muted)
           ======================================================== */

        :root {
            color-scheme: light;
        }

        body {
            background-color: #F8FAFC !important;
            color: #0F172A !important;
        }

        main, main > div {
            background-color: #F8FAFC !important;
            color: #0F172A !important;
        }

        /* Card & Surface Elements */
        .bg-white, [class*="bg-white"],
        .bg-\[\#112240\], [class*="bg-[#112240]"] {
            background-color: #FFFFFF !important;
            color: #0F172A !important;
            border-color: #E2E8F0 !important;
        }

        /* Base & Inner Panels */
        .bg-slate-50, .bg-slate-100, .bg-slate-50\/80, .bg-slate-100\/80,
        .bg-gray-50, .bg-gray-100,
        [class*="bg-slate-50"], [class*="bg-slate-100"],
        .bg-\[\#0B192C\], [class*="bg-[#0B192C]"],
        .bg-\[\#07101E\], [class*="bg-[#07101E]"] {
            background-color: #F8FAFC !important;
            color: #334155 !important;
            border-color: #E2E8F0 !important;
        }

        /* Hover States for Lists and Rows */
        .hover\:bg-slate-50:hover, .hover\:bg-gray-50:hover, .hover\:bg-slate-100:hover,
        .hover\:bg-slate-50\/70:hover,
        .hover\:bg-\[\#162B4D\]:hover,
        .hover\:bg-\[\#112240\]:hover {
            background-color: #F1F5F9 !important;
        }

        /* Borders & Dividers */
        .border-slate-100, .border-slate-200, .border-slate-300,
        .border-gray-100, .border-gray-200, .border-gray-300,
        .border-\[\#1E3A5F\], [class*="border-[#1E3A5F]"],
        .border-\[\#2E5A8F\], [class*="border-[#2E5A8F]"],
        .border-\[\#274872\], [class*="border-[#274872]"],
        .divide-slate-100 > :not([hidden]) ~ :not([hidden]),
        .divide-slate-200 > :not([hidden]) ~ :not([hidden]),
        .divide-gray-100 > :not([hidden]) ~ :not([hidden]),
        .divide-gray-200 > :not([hidden]) ~ :not([hidden]) {
            border-color: #E2E8F0 !important;
        }

        /* Border top accent on cards */
        .border-t-slate-800, .border-t-\[\#1E3A5F\] {
            border-top-color: #2563EB !important;
        }

        /* Headings and Primary Text */
        h1, h2, h3, h4, h5, h6,
        .text-slate-900, .text-slate-800,
        .text-gray-900, .text-gray-800 {
            color: #0F172A !important;
        }

        /* Body and Secondary Text */
        .text-slate-700, .text-slate-600,
        .text-gray-700, .text-gray-600 {
            color: #334155 !important;
        }

        .text-slate-500, .text-slate-400,
        .text-gray-500, .text-gray-400 {
            color: #64748B !important;
        }

        /* Tables */
        table {
            border-color: #E2E8F0 !important;
            background-color: #FFFFFF !important;
        }
        table thead, table thead tr, table thead th {
            background-color: #F8FAFC !important;
            color: #475569 !important;
            border-color: #E2E8F0 !important;
            font-weight: 600 !important;
        }
        table tbody tr {
            background-color: #FFFFFF !important;
            color: #1E293B !important;
            border-color: #E2E8F0 !important;
        }
        table tbody tr:hover {
            background-color: #F8FAFC !important;
        }
        table td {
            border-color: #E2E8F0 !important;
        }
        table tfoot, table tfoot tr, table tfoot td {
            background-color: #F8FAFC !important;
            color: #0F172A !important;
            border-color: #E2E8F0 !important;
            font-weight: 600 !important;
        }

        /* Forms, Inputs, Selects & Textareas */
        input, select, textarea {
            color-scheme: light !important;
        }

        input:not([type="checkbox"]):not([type="radio"]):not([type="submit"]):not([type="button"]):not([type="file"]),
        select, textarea {
            background-color: #FFFFFF !important;
            color: #0F172A !important;
            border: 1px solid #CBD5E1 !important;
        }
        input:not([type="checkbox"]):not([type="radio"]):not([type="submit"]):not([type="button"]):not([type="file"]):focus,
        select:focus, textarea:focus {
            border-color: #2563EB !important;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15) !important;
            outline: none !important;
        }
        input:disabled, select:disabled, textarea:disabled {
            background-color: #F1F5F9 !important;
            color: #94A3B8 !important;
            border-color: #E2E8F0 !important;
            cursor: not-allowed !important;
        }
        select option {
            background-color: #FFFFFF !important;
            color: #0F172A !important;
        }

        /* Number Input Spinners */
        input[type="number"] {
            color-scheme: light !important;
            -moz-appearance: textfield !important;
        }
        input[type="number"]::-webkit-inner-spin-button,
        input[type="number"]::-webkit-outer-spin-button {
            -webkit-appearance: none !important;
            margin: 0 !important;
            display: none !important;
        }

        /* Buttons & Badges - Clean Modern Style */
        .bg-slate-900, .bg-slate-800,
        button.bg-blue-600, a.bg-blue-600:not([href*="status="]):not([href*="tab="]),
        button.bg-slate-900, a.bg-slate-900,
        button.bg-slate-800, a.bg-slate-800,
        button[type="submit"]:not(.bg-rose-600):not(.bg-red-600):not(.bg-rose-950) {
            background-color: #2563EB !important;
            color: #FFFFFF !important;
            border: 1px solid #1D4ED8 !important;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
        }
        .hover\:bg-slate-900:hover, .hover\:bg-slate-800:hover,
        .bg-slate-900:hover, .bg-slate-800:hover,
        button.bg-blue-600:hover, a.bg-blue-600:not([href*="status="]):not([href*="tab="]):hover,
        button.bg-slate-900:hover, a.bg-slate-900:hover,
        button.bg-slate-800:hover, a.bg-slate-800:hover,
        button[type="submit"]:not(.bg-rose-600):not(.bg-red-600):not(.bg-rose-950):hover {
            background-color: #1D4ED8 !important;
            border-color: #1E40AF !important;
            color: #FFFFFF !important;
            transform: scale(1.02) !important;
        }
        button.bg-white, a.bg-white {
            background-color: #FFFFFF !important;
            color: #334155 !important;
            border: 1px solid #E2E8F0 !important;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
        }
        button.bg-white:hover, a.bg-white:hover {
            background-color: #F8FAFC !important;
            color: #0F172A !important;
            border-color: #CBD5E1 !important;
            transform: scale(1.02) !important;
        }
        a.bg-slate-100, span.bg-slate-100, button.bg-slate-100 {
            background-color: #F1F5F9 !important;
            color: #1E293B !important;
            border-color: #E2E8F0 !important;
        }

        /* Action Buttons & Filter Tabs */
        button[onclick*="openTambahModal"] {
            background-color: #2563EB !important;
            color: #FFFFFF !important;
            border: 1px solid #1D4ED8 !important;
            box-shadow: 0 1px 3px 0 rgba(37, 99, 235, 0.2) !important;
            transition: all 0.2s ease !important;
        }
        button[onclick*="openTambahModal"]:hover {
            background-color: #1D4ED8 !important;
            border-color: #1E40AF !important;
            transform: scale(1.02) !important;
        }
        button[onclick*="openTambahModal"] i {
            color: #FFFFFF !important;
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
            background-color: #2563EB !important;
            color: #FFFFFF !important;
            border: 1px solid #1D4ED8 !important;
            box-shadow: 0 1px 3px 0 rgba(37, 99, 235, 0.2) !important;
        }
        a[href*="data_calon_penerima.php?status="].bg-blue-600 > span:last-child,
        a[href*="data_calon_penerima.php?status="].bg-\[\#162B4D\] > span:last-child,
        a[href*="ranking.php?tab="].bg-blue-600 > span:last-child,
        a[href*="ranking.php?tab="].bg-\[\#162B4D\] > span:last-child {
            background-color: #1D4ED8 !important;
            color: #FFFFFF !important;
            border: 1px solid #1E40AF !important;
        }
        a[href*="data_calon_penerima.php?status="]:not(.bg-blue-600):not(.bg-\[\#162B4D\]),
        a[href*="ranking.php?tab="]:not(.bg-blue-600):not(.bg-\[\#162B4D\]) {
            background-color: #FFFFFF !important;
            color: #475569 !important;
            border: 1px solid #E2E8F0 !important;
        }
        a[href*="data_calon_penerima.php?status="]:not(.bg-blue-600):not(.bg-\[\#162B4D\]):hover,
        a[href*="ranking.php?tab="]:not(.bg-blue-600):not(.bg-\[\#162B4D\]):hover {
            background-color: #F8FAFC !important;
            color: #0F172A !important;
        }
        a[href*="data_calon_penerima.php?status="]:not(.bg-blue-600):not(.bg-\[\#162B4D\]) > span:last-child,
        a[href*="ranking.php?tab="]:not(.bg-blue-600):not(.bg-\[\#162B4D\]) > span:last-child {
            background-color: #F1F5F9 !important;
            color: #64748B !important;
            border: 1px solid #E2E8F0 !important;
        }

        /* Text Accents in Bright White Theme */
        .text-indigo-600, .text-indigo-700, .text-indigo-800, .text-indigo-900 {
            color: #2563EB !important;
        }
        .text-blue-700, .text-blue-800, .text-blue-900, .text-blue-300, .text-blue-200 {
            color: #2563EB !important;
        }

        /* Sidebar Styling - Pure White Crisp Theme */
        #app-sidebar {
            background-color: #FFFFFF !important;
            border-color: #E2E8F0 !important;
            box-shadow: 1px 0 3px 0 rgba(0, 0, 0, 0.03) !important;
        }
        #app-sidebar .sidebar-header {
            border-color: #E2E8F0 !important;
        }
        #app-sidebar .sidebar-footer {
            background-color: #FFFFFF !important;
            border-color: #E2E8F0 !important;
        }
        #app-sidebar .active-nav, .active-nav {
            background-color: #EFF6FF !important;
            color: #2563EB !important;
            font-weight: 700 !important;
            border-left: 3px solid #2563EB !important;
        }
        #app-sidebar .active-nav i, .active-nav i {
            color: #2563EB !important;
        }
        #app-sidebar .sidebar-nav-link:not(.active-nav) {
            color: #475569 !important;
        }
        #app-sidebar .sidebar-nav-link:not(.active-nav):hover {
            background-color: #F8FAFC !important;
            color: #0F172A !important;
        }
        #app-sidebar .sidebar-nav-link:not(.active-nav):hover i {
            color: #2563EB !important;
        }
        #app-sidebar .sidebar-profile-box {
            background-color: #F8FAFC !important;
            border: 1px solid #E2E8F0 !important;
        }
        #app-sidebar .sidebar-profile-box:hover {
            background-color: #F1F5F9 !important;
            border-color: #CBD5E1 !important;
        }
        #app-sidebar .sidebar-logout-btn {
            background-color: #FFF1F2 !important;
            border-color: #FECDD3 !important;
            color: #E11D48 !important;
        }
        #app-sidebar .sidebar-logout-btn:hover {
            background-color: #FFE4E6 !important;
            border-color: #FDA4AF !important;
            color: #BE123C !important;
        }

        /* Pills & Badges */
        .bg-slate-200, .bg-slate-300, .bg-gray-200, .bg-gray-300,
        [class*="bg-slate-200"], [class*="bg-slate-300"] {
            background-color: #F1F5F9 !important;
            color: #334155 !important;
            border-color: #E2E8F0 !important;
        }

        /* Stat Cards Interactive Hover Animation */
        .stat-card-interactive {
            background-color: #FFFFFF !important;
            border-color: #E2E8F0 !important;
            transition: transform 0.25s cubic-bezier(0.34, 1.56, 0.64, 1), 
                        background-color 0.2s ease, 
                        border-color 0.2s ease, 
                        box-shadow 0.25s ease !important;
            will-change: transform, box-shadow;
            position: relative;
        }
        .stat-card-interactive:hover {
            transform: translateY(-4px) scale(1.015) !important;
            background-color: #FFFFFF !important;
            border-color: #2563EB !important;
            box-shadow: 0 12px 24px -6px rgba(0, 0, 0, 0.08), 0 4px 8px -2px rgba(0, 0, 0, 0.03) !important;
            z-index: 10;
        }
        .stat-card-interactive:hover .stat-card-title {
            color: #2563EB !important;
        }
        .stat-card-interactive:hover .stat-card-arrow {
            transform: translateX(4px);
            color: #2563EB !important;
        }

        /* Glowing Status Badges for Schedule Banner */
        .badge-glow-aktif {
            background-color: #ECFDF5 !important;
            color: #047857 !important;
            border: 1px solid #A7F3D0 !important;
            font-weight: 700 !important;
            box-shadow: 0 1px 2px rgba(16, 185, 129, 0.1) !important;
        }
        .badge-glow-menunggu {
            background-color: #FFFBEB !important;
            color: #B45309 !important;
            border: 1px solid #FDE68A !important;
            font-weight: 700 !important;
            box-shadow: 0 1px 2px rgba(245, 158, 11, 0.1) !important;
        }
        .badge-glow-tutup {
            background-color: #FFF1F2 !important;
            color: #BE123C !important;
            border: 1px solid #FECDD3 !important;
            font-weight: 700 !important;
            box-shadow: 0 1px 2px rgba(244, 63, 94, 0.1) !important;
        }

        /* Glowing Action Button (Atur Jadwal) */
        .btn-glow-schedule {
            background-color: #2563EB !important;
            color: #FFFFFF !important;
            border: 1px solid #1D4ED8 !important;
            box-shadow: 0 2px 4px rgba(37, 99, 235, 0.2) !important;
            font-weight: 600 !important;
            transition: all 0.2s cubic-bezier(0.34, 1.56, 0.64, 1) !important;
        }
        .btn-glow-schedule:hover {
            background-color: #1D4ED8 !important;
            border-color: #1E40AF !important;
            transform: translateY(-2px) scale(1.02) !important;
        }

        /* Status Badges */
        .bg-emerald-50, .bg-emerald-100 {
            background-color: #ECFDF5 !important;
            color: #047857 !important;
            border-color: #A7F3D0 !important;
        }
        .bg-emerald-50 i, .bg-emerald-100 i, .text-emerald-600, .text-emerald-700, .text-emerald-800 {
            color: #059669 !important;
        }

        .bg-rose-50, .bg-rose-100, .bg-red-50, .bg-red-100 {
            background-color: #FFF1F2 !important;
            color: #BE123C !important;
            border-color: #FECDD3 !important;
        }
        .bg-rose-50 i, .bg-rose-100 i, .bg-red-50 i, .bg-red-100 i, .text-rose-600, .text-rose-700, .text-rose-800, .text-red-600, .text-red-700 {
            color: #E11D48 !important;
        }

        .bg-amber-50, .bg-amber-100, .bg-yellow-50, .bg-yellow-100 {
            background-color: #FFFBEB !important;
            color: #B45309 !important;
            border-color: #FDE68A !important;
        }
        .bg-amber-50 i, .bg-amber-100 i, .text-amber-600, .text-amber-700, .text-amber-800, .text-amber-900 {
            color: #D97706 !important;
        }

        .bg-blue-50, .bg-blue-100 {
            background-color: #EFF6FF !important;
            color: #1D4ED8 !important;
            border-color: #BFDBFE !important;
        }

        /* Modals & Backdrop */
        .fixed.inset-0.bg-black\/50, .fixed.inset-0.bg-slate-900\/60, .fixed.inset-0.bg-black\/75 {
            background-color: rgba(15, 23, 42, 0.45) !important;
            backdrop-filter: blur(4px) !important;
        }

        
        /* ========================================================
           BULLETPROOF TEXT VISIBILITY FOR BRIGHT WHITE THEME
           Semua teks di dalam card/panel putih WAJIB berwarna gelap tajam
           ======================================================== */
        .stat-card-interactive span,
        .stat-card-interactive h3,
        .stat-card-interactive strong,
        .stat-card-interactive b {
            color: #0F172A !important;
        }

        .stat-card-interactive .text-3xl {
            color: #0F172A !important;
            font-weight: 800 !important;
        }

        /* Pastikan label nama siswa dan nilai di tabel/list kontras tajam */
        table td, table td span, table td strong, table td b,
        table th, table th span {
            color: #1E293B !important;
        }

        /* Text-white di dalam kontainer putih dipaksa menjadi gelap, KECUALI tombol / badge khusus */
        .bg-white .text-white:not(button *):not(a.bg-blue-600 *):not(button.bg-blue-600 *):not(a.bg-emerald-600 *):not(button.bg-emerald-600 *):not(.badge-glow-aktif *):not(.badge-glow-menunggu *):not(.badge-glow-tutup *):not(.btn-glow-schedule *):not(.bg-blue-600 *):not(.bg-slate-900 *):not(.bg-emerald-500 *):not(.bg-rose-500 *):not(.bg-red-500 *):not([class*="from-slate-900"] *),
        .bg-slate-50 .text-white:not(button *):not(a.bg-blue-600 *):not(button.bg-blue-600 *):not(a.bg-emerald-600 *):not(button.bg-emerald-600 *):not(.badge-glow-aktif *):not(.badge-glow-menunggu *):not(.badge-glow-tutup *):not(.btn-glow-schedule *):not(.bg-blue-600 *):not(.bg-slate-900 *):not(.bg-emerald-500 *):not(.bg-rose-500 *):not(.bg-red-500 *):not([class*="from-slate-900"] *) {
            color: #0F172A !important;
        }

        /* Input fields and selects text color */
        input, select, textarea {
            color: #0F172A !important;
            background-color: #FFFFFF !important;
        }

        
        /* Harmonious Light Status Badges & Alerts */
        [class*="bg-amber-950"], [class*="bg-amber-900"] {
            background-color: #FFFBEB !important;
            border-color: #FDE68A !important;
            color: #92400E !important;
        }
        [class*="bg-amber-950"] *, [class*="bg-amber-900"] * {
            color: #92400E !important;
        }

        [class*="bg-emerald-950"], [class*="bg-emerald-900"] {
            background-color: #ECFDF5 !important;
            border-color: #A7F3D0 !important;
            color: #047857 !important;
        }
        [class*="bg-emerald-950"] *, [class*="bg-emerald-900"] * {
            color: #047857 !important;
        }

        [class*="bg-rose-950"], [class*="bg-rose-900"] {
            background-color: #FFF1F2 !important;
            border-color: #FECDD3 !important;
            color: #BE123C !important;
        }
        [class*="bg-rose-950"] *, [class*="bg-rose-900"] * {
            color: #BE123C !important;
        }

        /* Scrollbars */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: #F1F5F9;
        }
        ::-webkit-scrollbar-thumb {
            background: #CBD5E1;
            border-radius: 9999px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #94A3B8;
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
<body class="bg-slate-50 font-sans antialiased text-slate-800 h-screen w-full overflow-hidden">

<?php 
// Memuat komponen universal popup notifikasi toast (Pojok Kanan Atas)
require_once __DIR__ . '/notifikasi.php'; 
?>

<div class="h-screen w-full flex overflow-hidden">

