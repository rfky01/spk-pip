<?php
// portal_siswa.php - Dashboard & Portal Akun Pendaftar PIP Mandiri
require_once "koneksi.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek autentikasi pendaftar
if (!isset($_SESSION['siswa']) || empty($_SESSION['siswa']['id_siswa'])) {
    header("Location: login_siswa.php");
    exit;
}

// Handler unduh berkas bukti PDF resmi 2 lembar
if (isset($_GET['action']) && $_GET['action'] === 'unduh_pdf') {
    require_once "unduh_bukti_pdf.php";
    exit;
}

$id_siswa_sess = (int)$_SESSION['siswa']['id_siswa'];

// Ambil info sekolah & pengaturan
$res_pengaturan = mysqli_query($koneksi, "SELECT * FROM `pengaturan` WHERE id=1");
$pengaturan = mysqli_fetch_assoc($res_pengaturan) ?: [
    'nama_sekolah' => 'SMP Tunas Bangsa',
    'tahun_ajaran' => '2025/2026',
    'tgl_buka_pengajuan' => '2026-09-01',
    'tgl_tutup_pengajuan' => '2026-10-31',
    'logo' => 'uploads/logo_default.png',
    'kepala_sekolah' => 'Fitri Wiyatni, S.Pd.I',
    'nip_kepala_sekolah' => '-'
];

$today = date('Y-m-d');
$tgl_buka = $pengaturan['tgl_buka_pengajuan'] ?? '2026-09-01';
$tgl_tutup = $pengaturan['tgl_tutup_pengajuan'] ?? '2026-10-31';
$is_registration_open = ($today >= $tgl_buka && $today <= $tgl_tutup);

$msg = "";
$msg_type = "";

// 1. Ambil data terkini siswa
$stmt = mysqli_prepare($koneksi, "SELECT * FROM `calon_penerima` WHERE `id_siswa` = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $id_siswa_sess);
mysqli_stmt_execute($stmt);
$res_siswa = mysqli_stmt_get_result($stmt);
$siswa = mysqli_fetch_assoc($res_siswa);

if (!$siswa) {
    // Siswa tidak ditemukan (mungkin dihapus admin)
    session_destroy();
    header("Location: login_siswa.php?error=notfound");
    exit;
}

// 2. Handler POST: Update Data Pengajuan Mandiri
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_mandiri') {
    if (!$is_registration_open) {
        $msg = "Mohon maaf, perbaikan data tidak dapat disimpan karena periode pendaftaran telah ditutup.";
        $msg_type = "error";
    } elseif ($siswa['status_verifikasi'] === 'Terverifikasi') {
        $msg = "Pembaruan ditolak: Data Anda telah diverifikasi resmi oleh pihak sekolah dan dikunci demi menjaga objektivitas hasil seleksi AHP.";
        $msg_type = "error";
    } else {
        $nama          = trim($_POST['nama'] ?? '');
        $nama_ortu     = trim($_POST['nama_ortu'] ?? '');
        $no_hp         = trim($_POST['no_hp'] ?? '');
        $jenis_kelamin = $_POST['jenis_kelamin'] ?? 'Laki-laki';
        $sekolah_asal  = trim($_POST['sekolah_asal'] ?? '');
        $alamat        = trim($_POST['alamat'] ?? '');
        $c1            = (int)($_POST['penghasilan'] ?? 1);
        $c2            = (int)($_POST['tanggungan'] ?? 1);
        $c3            = (int)($_POST['kondisi_rumah'] ?? 1);
        $c4            = (int)($_POST['prestasi'] ?? 1);
        $c5            = (int)($_POST['jarak'] ?? 1);

        if (empty($nama) || empty($nama_ortu) || empty($no_hp)) {
            $msg = "Mohon lengkapi Nama Siswa, Nama Wali, dan Nomor HP!";
            $msg_type = "error";
        } else {
            // Cek jika ada unggahan foto baru
            $foto_path = $siswa['foto'] ?? null;
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                $file_tmp = $_FILES['foto']['tmp_name'];
                $file_name = $_FILES['foto']['name'];
                $file_size = $_FILES['foto']['size'];
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                $allowed_ext = ['jpg', 'jpeg', 'png', 'webp'];
                if (in_array($file_ext, $allowed_ext) && $file_size <= 3 * 1024 * 1024) {
                    $clean_nisn = preg_replace('/[^a-zA-Z0-9_-]/', '', $siswa['nisn']);
                    $new_foto_name = "uploads/foto_siswa/siswa_" . $clean_nisn . "_" . time() . "." . $file_ext;
                    if (!is_dir("uploads/foto_siswa")) {
                        @mkdir("uploads/foto_siswa", 0777, true);
                    }
                    if (move_uploaded_file($file_tmp, $new_foto_name)) {
                        if (!empty($siswa['foto']) && file_exists($siswa['foto']) && strpos($siswa['foto'], 'default') === false) {
                            @unlink($siswa['foto']);
                        }
                        $foto_path = $new_foto_name;
                    }
                }
            }

            // Jika sebelumnya Ditolak, kembalikan ke Menunggu Verifikasi agar diperiksa ulang
            $status_simpan = ($siswa['status_verifikasi'] === 'Ditolak') ? 'Menunggu Verifikasi' : $siswa['status_verifikasi'];

            $stmt_upd = mysqli_prepare($koneksi, "UPDATE `calon_penerima` SET 
                `nama`=?, `nama_ortu`=?, `no_hp`=?, `foto`=?, `jenis_kelamin`=?, `sekolah_asal`=?, `alamat`=?, 
                `penghasilan`=?, `tanggungan`=?, `kondisi_rumah`=?, `prestasi`=?, `jarak`=?, 
                `status_verifikasi`=? 
                WHERE `id_siswa`=?");
            mysqli_stmt_bind_param($stmt_upd, "sssssssiiiiisi", $nama, $nama_ortu, $no_hp, $foto_path, $jenis_kelamin, $sekolah_asal, $alamat, $c1, $c2, $c3, $c4, $c5, $status_simpan, $id_siswa_sess);

            if (mysqli_stmt_execute($stmt_upd)) {
                $msg = "pembaruan data pengajuan Anda berhasil disimpan!";
                $msg_type = "success";
                $_SESSION['siswa']['nama'] = $nama;

                // Refresh data
                mysqli_stmt_execute($stmt);
                $siswa = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
            } else {
                $msg = "Gagal memperbarui data: " . mysqli_error($koneksi);
                $msg_type = "error";
            }
        }
    }
}

// 3. Handler POST: Ganti PIN Pendaftar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'ganti_pin') {
    $pin_lama = trim($_POST['pin_lama'] ?? '');
    $pin_baru = trim($_POST['pin_baru'] ?? '');
    $pin_baru_konf = trim($_POST['pin_baru_konf'] ?? '');

    $pin_match = false;
    if (empty($siswa['pin'])) {
        $last_4 = substr($siswa['nisn'], -4);
        if ($pin_lama === $last_4 || $pin_lama === '123456') {
            $pin_match = true;
        }
    } else {
        if (password_verify($pin_lama, $siswa['pin']) || $pin_lama === $siswa['pin']) {
            $pin_match = true;
        }
    }

    if (!$pin_match) {
        $msg = "PIN Lama yang Anda masukkan salah!";
        $msg_type = "error";
    } elseif (strlen($pin_baru) < 4) {
        $msg = "PIN Baru minimal 4 - 6 digit angka!";
        $msg_type = "error";
    } elseif ($pin_baru !== $pin_baru_konf) {
        $msg = "Konfirmasi PIN Baru tidak cocok!";
        $msg_type = "error";
    } else {
        $hash_baru = password_hash($pin_baru, PASSWORD_DEFAULT);
        $stmt_pin = mysqli_prepare($koneksi, "UPDATE `calon_penerima` SET `pin` = ? WHERE `id_siswa` = ?");
        mysqli_stmt_bind_param($stmt_pin, "si", $hash_baru, $id_siswa_sess);
        if (mysqli_stmt_execute($stmt_pin)) {
            $msg = "PIN Keamanan Akun Anda berhasil diperbarui. Jangan lupa mengingat PIN baru Anda!";
            $msg_type = "success";
            // Refresh data
            mysqli_stmt_execute($stmt);
            $siswa = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        } else {
            $msg = "Gagal mengganti PIN: " . mysqli_error($koneksi);
            $msg_type = "error";
        }
    }
}

// Penamaan Kriteria untuk Preview
$label_penghasilan = [
    5 => '< Rp 500.000 (Sangat Rendah)',
    4 => 'Rp 600.000 - Rp 1.000.000 (Rendah)',
    3 => 'Rp 1.000.000 - Rp 2.000.000 (Sedang)',
    2 => 'Rp 2.000.000 - Rp 3.000.000 (Cukup)',
    1 => '> Rp 4.000.000 (Mampu)'
];
$label_tanggungan = [
    5 => '> 5 Orang',
    4 => '4 Orang',
    3 => '3 Orang',
    2 => '2 Orang',
    1 => '1 Orang'
];
$label_rumah = [
    5 => 'Tidak Layak Huni',
    4 => 'Dinding Kayu',
    3 => 'Dinding Batu Atap Seng',
    2 => 'Dinding Batu Atap Genteng',
    1 => 'Tembok Keramik (Layak)'
];
$label_prestasi = [
    5 => 'Juara 1 - 3 Tingkat Kabupaten',
    4 => 'Juara Harapan',
    3 => 'Juara Kelas 1 - 3',
    2 => 'Peringkat 10 Besar',
    1 => 'Peringkat 20 Besar'
];
$label_jarak = [
    5 => '> 5 km',
    4 => '3 – 5 km',
    3 => '2 km',
    2 => '1 km',
    1 => '< 1 km'
];

$is_locked = ($siswa['status_verifikasi'] === 'Terverifikasi');
$nama_sekolah = $pengaturan['nama_sekolah'] ?? 'SMP Tunas Bangsa';
$logo_sekolah = !empty($pengaturan['logo']) && file_exists($pengaturan['logo']) ? $pengaturan['logo'] : 'uploads/logo_default.png';

// Siapkan Pas Foto Siswa Base64 untuk Export PDF (menghindari CORS / delay canvas)
$foto_siswa_base64 = '';
if (!empty($siswa['foto']) && file_exists($siswa['foto'])) {
    $img_data = @file_get_contents($siswa['foto']);
    if ($img_data !== false) {
        $ext = strtolower(pathinfo($siswa['foto'], PATHINFO_EXTENSION));
        $mime = ($ext === 'png') ? 'image/png' : (($ext === 'webp') ? 'image/webp' : 'image/jpeg');
        $foto_siswa_base64 = 'data:' . $mime . ';base64,' . base64_encode($img_data);
    }
}
$foto_siswa_src = !empty($foto_siswa_base64) ? $foto_siswa_base64 : (!empty($siswa['foto']) ? $siswa['foto'] : '');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Akun Pendaftar PIP - <?= htmlspecialchars($siswa['nama']) ?></title>
    <!-- Google Fonts: Roboto -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['"Roboto"', 'sans-serif'] }
                }
            }
        }
    </script>
    <style>
        body, input, button, select, textarea { font-family: 'Roboto', sans-serif !important; }
        .print-only { display: none; }
        @media print {
            @page {
                size: A4 portrait;
                margin: 8mm 12mm 8mm 12mm;
            }
            *, *::before, *::after, html, body, main, input, button, select, textarea, p, span, h1, h2, h3, h4, h5, h6, table, tr, td, th, div, label { 
                font-family: 'Times New Roman', Times, serif !important;
            }
            html, body { 
                background: white !important; 
                color: #0f172a !important; 
                margin: 0 !important;
                padding: 0 !important;
                display: block !important;
                width: 100% !important;
                height: auto !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            main {
                margin: 0 !important;
                padding: 0 !important;
                max-width: 100% !important;
                width: 100% !important;
                display: block !important;
            }
            .no-print { 
                display: none !important; 
            }
            .print-only { 
                display: block !important; 
            }
            
            /* LEMBAR 1: FORMULIR BIODATA & KRITERIA PENDAFTARAN */
            .print-sheet-1 {
                display: flex !important;
                flex-direction: column !important;
                justify-content: space-between !important;
                height: 260mm !important;
                min-height: 260mm !important;
                box-sizing: border-box !important;
                page-break-after: always !important;
                break-after: page !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
                background: white !important;
                color: #0f172a !important;
                border: 2px solid #0f172a !important;
                border-radius: 12px !important;
                padding: 18px 22px !important;
                margin: 0 !important;
                box-shadow: none !important;
            }
            .print-sheet-1 form {
                display: flex !important;
                flex-direction: column !important;
                justify-content: flex-start !important;
                flex: 1 !important;
                height: 100% !important;
                margin: 0 !important;
            }
            .print-sheet-1 * {
                color: #0f172a !important;
                font-family: 'Times New Roman', Times, serif !important;
            }
            .print-sheet-1 input, 
            .print-sheet-1 select, 
            .print-sheet-1 textarea {
                background: #f8fafc !important;
                border: 1.5px solid #475569 !important;
                color: #0f172a !important;
                padding: 8px 12px !important;
                font-size: 12.5px !important;
                line-height: 1.4 !important;
                border-radius: 8px !important;
                box-shadow: none !important;
            }
            .print-sheet-1 textarea {
                height: 92px !important;
                resize: none !important;
            }
            .print-sheet-1 label {
                color: #0f172a !important;
                font-size: 12px !important;
                font-weight: 700 !important;
                margin-bottom: 5px !important;
                display: block !important;
            }
            .print-sheet-1 .print-section-title {
                color: #0f172a !important;
                border-bottom: 2px solid #1e293b !important;
                padding-bottom: 5px !important;
                margin-bottom: 12px !important;
                margin-top: 14px !important;
                font-size: 13.5px !important;
                font-weight: 800 !important;
                letter-spacing: 0.5px !important;
            }
            .print-sheet-1 .print-photo-box {
                border: 1.5px solid #475569 !important;
                background: #f8fafc !important;
                padding: 6px 12px !important;
                border-radius: 8px !important;
                height: 92px !important;
                box-sizing: border-box !important;
                display: flex !important;
                align-items: center !important;
            }

            /* LEMBAR 2: TANDA TERIMA & BUKTI PENDAFTARAN */
            .print-sheet-2 {
                display: flex !important;
                flex-direction: column !important;
                justify-content: space-between !important;
                min-height: 254mm !important;
                box-sizing: border-box !important;
                page-break-before: always !important;
                break-before: page !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
                background: white !important;
                color: black !important;
                padding: 10px 14px !important;
                margin: 0 auto !important;
                max-width: 100% !important;
            }
            .print-sheet-2 * {
                color: black !important;
                font-family: 'Times New Roman', Times, serif !important;
            }
        }
    </style>
</head>
<body class="bg-[#0B192C] min-h-screen text-slate-100 font-sans flex flex-col justify-between">

    <!-- NAVBAR PENDAFTAR -->
    <header class="bg-[#112240] border-b border-[#1E3A5F] sticky top-0 z-30 shadow-md no-print">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 py-3 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-[#0B192C] border border-[#1E3A5F] p-1 flex items-center justify-center shrink-0">
                    <img src="<?= htmlspecialchars($logo_sekolah) ?>" alt="Logo Sekolah" class="max-h-full max-w-full object-contain">
                </div>
                <div>
                    <h1 class="text-sm font-extrabold text-white leading-tight">Portal Akun Pendaftar PIP</h1>
                    <p class="text-[11px] font-semibold text-blue-300 uppercase tracking-wider"><?= htmlspecialchars($nama_sekolah) ?></p>
                </div>
            </div>

            <!-- USER INFO & LOGOUT -->
            <div class="flex items-center gap-2 sm:gap-3">
                <div class="hidden md:flex items-center gap-2.5 text-right">
                    <div>
                        <div class="text-xs font-bold text-white leading-tight"><?= htmlspecialchars($siswa['nama']) ?></div>
                        <div class="text-[11px] text-slate-400 font-mono">NISN: <?= htmlspecialchars($siswa['nisn']) ?></div>
                    </div>
                    <div class="w-8 h-9 rounded-lg overflow-hidden border border-[#2E5A8F] bg-[#07101E] shrink-0 flex items-center justify-center shadow-xs">
                        <?php if (!empty($siswa['foto']) && file_exists($siswa['foto'])): ?>
                            <img src="<?= htmlspecialchars($siswa['foto']) ?>" alt="Foto" class="w-full h-full object-cover">
                        <?php else: ?>
                            <i class="fa-solid fa-user text-slate-400 text-xs"></i>
                        <?php endif; ?>
                    </div>
                </div>

                <button type="button" onclick="bukaModalGantiPin()" class="px-3 py-1.5 bg-[#1E3A5F] hover:bg-[#274872] text-white rounded-xl text-xs font-semibold transition-colors flex items-center gap-1.5 cursor-pointer" title="Ganti PIN Keamanan">
                    <i class="fa-solid fa-key text-xs"></i>
                    <span class="hidden sm:inline">Ganti PIN</span>
                </button>

                <a href="logout_siswa.php" onclick="return confirm('Apakah Anda yakin ingin keluar dari Akun Pendaftar?')" class="px-3.5 py-1.5 bg-rose-950/40 hover:bg-rose-900/60 text-rose-300 border border-rose-800/60 rounded-xl text-xs font-bold transition-colors flex items-center gap-1.5">
                    <i class="fa-solid fa-arrow-right-from-bracket text-xs"></i>
                    <span class="hidden sm:inline">Logout</span>
                </a>
            </div>
        </div>
    </header>

    <!-- KONTEN UTAMA -->
    <main class="max-w-5xl w-full mx-auto px-4 sm:px-6 py-8 flex-1 space-y-6">

        <!-- NOTIFIKASI SUKSES / ERROR -->
        <?php if (isset($_GET['status']) && $_GET['status'] === 'sukses_daftar'): ?>
            <div class="p-4 rounded-2xl bg-emerald-950/40 border border-emerald-700/60 text-emerald-200 text-xs font-semibold shadow-xs flex items-start justify-between gap-3 no-print">
                <div class="flex items-start gap-2.5">
                    <i class="fa-solid fa-circle-check text-emerald-600 text-base mt-0.5 shrink-0"></i>
                    <div>
                        <b class="text-sm block mb-0.5">Pendaftaran Akun Berhasil!</b>
                        <span>Data pengajuan calon penerima PIP atas nama <b><?= htmlspecialchars($siswa['nama']) ?></b> telah tercatat dalam sistem. Simpan NISN dan PIN Anda untuk mengakses portal ini sewaktu-waktu.</span>
                    </div>
                </div>
                <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700 font-bold text-base leading-none">&times;</button>
            </div>
        <?php endif; ?>

        <?php if (!empty($msg)): ?>
            <div class="p-4 rounded-2xl text-xs font-semibold shadow-xs flex items-start justify-between gap-3 no-print
                <?= $msg_type === 'success' ? 'bg-emerald-950/50 border border-emerald-700/60 text-emerald-200' : 'bg-rose-950/50 border border-rose-700/60 text-rose-200' ?>">
                <div class="flex items-start gap-2.5">
                    <i class="fa-solid <?= $msg_type === 'success' ? 'fa-circle-check text-emerald-600' : 'fa-triangle-exclamation text-rose-500' ?> text-base mt-0.5 shrink-0"></i>
                    <div class="leading-relaxed"><?= $msg ?></div>
                </div>
                <button type="button" onclick="this.parentElement.remove()" class="text-slate-400 hover:text-slate-600 font-bold text-base leading-none">&times;</button>
            </div>
        <?php endif; ?>

        <!-- KARTU STATUS PENGAJUAN (HERO BANNER) -->
        <div class="bg-[#112240] rounded-2xl border border-[#1E3A5F] shadow-lg p-6 overflow-hidden no-print">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-5 pb-5 border-b border-[#1E3A5F]">
                <div class="flex items-center gap-4 sm:gap-5">
                    <!-- FOTO SISWA RESMI (3x4) -->
                    <div class="w-16 h-20 sm:w-20 sm:h-24 rounded-xl overflow-hidden border-2 border-[#2E5A8F] bg-[#07101E] shrink-0 shadow-md flex items-center justify-center relative">
                        <?php if (!empty($siswa['foto']) && file_exists($siswa['foto'])): ?>
                            <img src="<?= htmlspecialchars($siswa['foto']) ?>" alt="Foto <?= htmlspecialchars($siswa['nama']) ?>" class="w-full h-full object-cover">
                        <?php else: ?>
                            <div class="text-center p-2 text-slate-500">
                                <i class="fa-solid fa-user text-2xl text-slate-600 block mb-1"></i>
                                <span class="text-[9px] font-semibold text-slate-500 block">3 x 4</span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div>
                        <span class="text-xs font-bold text-blue-400 uppercase tracking-wider block">Status Pengajuan PIP Siswa:</span>
                        <h2 class="text-xl sm:text-2xl font-extrabold text-white mt-0.5"><?= htmlspecialchars($siswa['nama']) ?></h2>
                        <p class="text-xs text-slate-300 mt-1 flex flex-wrap items-center gap-1.5">
                            <span>NISN: <b class="font-mono font-bold text-blue-300"><?= htmlspecialchars($siswa['nisn']) ?></b></span>
                            <span class="text-slate-500">&bull;</span>
                            <span>Asal: <b class="text-white"><?= htmlspecialchars($siswa['sekolah_asal'] ?: 'SD/MI') ?></b></span>
                            <span class="text-slate-500">&bull;</span>
                            <span>Tingkat: <b class="text-white"><?= htmlspecialchars($siswa['kelas']) ?></b></span>
                        </p>
                    </div>
                </div>

                <!-- BADGE STATUS -->
                <div class="shrink-0">
                    <?php if ($siswa['status_verifikasi'] === 'Terverifikasi'): ?>
                        <div class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-950/60 text-emerald-300 rounded-xl text-xs font-extrabold border border-emerald-700 shadow-xs">
                            <i class="fa-solid fa-circle-check text-emerald-600 text-sm"></i>
                            <span>Terverifikasi Resmi</span>
                        </div>
                    <?php elseif ($siswa['status_verifikasi'] === 'Ditolak'): ?>
                        <div class="inline-flex items-center gap-2 px-4 py-2 bg-rose-950/60 text-rose-300 rounded-xl text-xs font-extrabold border border-rose-700 shadow-xs">
                            <i class="fa-solid fa-circle-xmark text-rose-600 text-sm"></i>
                            <span>Perlu Perbaikan Berkas</span>
                        </div>
                    <?php else: ?>
                        <div class="inline-flex items-center gap-2 px-4 py-2 bg-amber-950/60 text-amber-300 rounded-xl text-xs font-extrabold border border-amber-700 shadow-xs">
                            <i class="fa-solid fa-clock-rotate-left text-amber-600 text-sm"></i>
                            <span>Menunggu Verifikasi Berkas</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ACTION BAR: CETAK BUKTI & UNDUH PDF -->
            <div class="mt-5 pt-4 border-t border-[#1E3A5F] flex flex-wrap items-center justify-between gap-3 no-print">
                <div class="flex flex-wrap items-center gap-2.5">
                    <button type="button" onclick="window.print()" class="px-4 py-2 bg-[#162B4D] hover:bg-[#1E3A5F] text-white border border-[#2E5A8F] hover:border-blue-400 rounded-xl text-xs font-semibold shadow-sm transition-all duration-200 hover:scale-[1.02] active:scale-[0.98] inline-flex items-center gap-2 cursor-pointer">
                        <i class="fa-solid fa-print text-xs text-blue-400"></i>
                        <span>Cetak Tanda Terima Pengajuan</span>
                    </button>
                    <a href="unduh_bukti_pdf.php" id="btn-unduh-pdf" onclick="animasiUnduhPDF(this)" class="px-4 py-2 bg-emerald-950/70 hover:bg-emerald-900/90 text-emerald-200 border border-emerald-700/80 hover:border-emerald-400 rounded-xl text-xs font-semibold shadow-sm transition-all duration-200 hover:scale-[1.02] active:scale-[0.98] inline-flex items-center gap-2 cursor-pointer" title="Unduh langsung dokumen bukti pendaftaran 2 lembar resmi dalam format PDF">
                        <i class="fa-solid fa-file-pdf text-xs text-emerald-400"></i>
                        <span>Unduh PDF</span>
                    </a>
                </div>

                <div class="flex items-center gap-2 text-xs text-slate-500">
                    <?php if ($is_locked): ?>
                        <span class="inline-flex items-center gap-1.5 text-emerald-300 font-bold bg-emerald-950/60 px-3 py-1.5 rounded-lg border border-emerald-800">
                            <i class="fa-solid fa-lock text-xs"></i> Data Terkunci (Terverifikasi)
                        </span>
                    <?php elseif (!$is_registration_open): ?>
                        <span class="inline-flex items-center gap-1.5 text-slate-300 font-bold bg-[#0B192C] px-3 py-1.5 rounded-lg border border-[#1E3A5F]">
                            <i class="fa-solid fa-lock text-xs"></i> Pendaftaran Telah Ditutup
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- FORM DETAIL DATA PENGAJUAN (LEMBAR 1 SAAT DICETAK) -->
        <div class="bg-[#112240] rounded-2xl border border-[#1E3A5F] shadow-lg p-6 print-sheet-1">
            <div class="flex items-center justify-between pb-4 border-b border-[#1E3A5F] mb-5 print:pb-2.5 print:mb-3">
                <div>
                    <h3 class="text-sm font-bold text-white print:text-base print:font-black print:text-slate-900">Rincian &amp; Formulir Pembaruan Data</h3>
                    <p class="text-[11px] text-slate-400 print:text-[11px] print:font-semibold print:text-slate-600">SMP TUNAS BANGSA &bull; Tahun Ajaran <?= htmlspecialchars($pengaturan['tahun_ajaran']) ?></p>
                </div>

                <span class="text-[11px] text-slate-400 italic font-semibold print:text-xs print:font-bold print:text-slate-800 print:bg-slate-100 print:px-2.5 print:py-1 print:rounded-md print:border print:border-slate-300">Lembar 1: Formulir Pendaftaran</span>
            </div>

            <form action="portal_siswa.php" method="POST" enctype="multipart/form-data" class="space-y-5 print:space-y-0">
                <input type="hidden" name="action" value="update_mandiri">

                <!-- BAGIAN 1: BIODATA SISWA -->
                <div>
                    <h4 class="text-xs font-extrabold uppercase tracking-wider text-blue-300 mb-3 print:mb-2 print-section-title">
                        Biodata Siswa &amp; Wali Murid
                    </h4>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs print:grid-cols-2 print:gap-3">
                        <div>
                            <label class="block font-bold text-slate-300 mb-1">Nomor Induk Siswa Nasional (NISN)</label>
                            <input type="text" value="<?= htmlspecialchars($siswa['nisn']) ?>" disabled 
                                class="w-full px-3.5 py-2.5 border border-[#1E3A5F] rounded-xl bg-[#0B192C] text-slate-400 font-mono font-bold cursor-not-allowed">
                            <span class="text-[10px] text-slate-400 mt-1 block print:hidden">NISN merupakan nomor identitas unik dan tidak dapat diubah.</span>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-300 mb-1">Nama Lengkap Siswa *</label>
                            <input type="text" name="nama" required value="<?= htmlspecialchars($siswa['nama']) ?>" <?= $is_locked ? 'disabled' : '' ?>
                                class="w-full px-3.5 py-2.5 border border-[#1E3A5F] rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none text-xs font-semibold text-white <?= $is_locked ? 'bg-[#0B192C] text-slate-400 cursor-not-allowed' : 'bg-[#0B192C]' ?>">
                        </div>

                        <div>
                            <label class="block font-bold text-slate-300 mb-1">Sekolah Asal (SD / MI) *</label>
                            <input type="text" name="sekolah_asal" required value="<?= htmlspecialchars($siswa['sekolah_asal'] ?: 'SD/MI') ?>" <?= $is_locked ? 'disabled' : '' ?>
                                class="w-full px-3.5 py-2.5 border border-[#1E3A5F] rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none text-xs font-semibold text-white <?= $is_locked ? 'bg-[#0B192C] text-slate-400 cursor-not-allowed' : 'bg-[#0B192C]' ?>">
                        </div>

                        <div>
                            <label class="block font-bold text-slate-300 mb-1">Jenis Kelamin</label>
                            <select name="jenis_kelamin" <?= $is_locked ? 'disabled' : '' ?>
                                class="w-full px-3.5 py-2.5 border border-[#1E3A5F] rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none text-xs font-semibold text-white <?= $is_locked ? 'bg-[#0B192C] text-slate-400 cursor-not-allowed' : 'bg-[#0B192C]' ?>">
                                <option value="Laki-laki" <?= ($siswa['jenis_kelamin'] ?? '') === 'Laki-laki' ? 'selected' : '' ?>>Laki-laki</option>
                                <option value="Perempuan" <?= ($siswa['jenis_kelamin'] ?? '') === 'Perempuan' ? 'selected' : '' ?>>Perempuan</option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-300 mb-1">Nama Orang Tua / Wali *</label>
                            <input type="text" name="nama_ortu" required value="<?= htmlspecialchars($siswa['nama_ortu'] ?? '') ?>" <?= $is_locked ? 'disabled' : '' ?>
                                class="w-full px-3.5 py-2.5 border border-[#1E3A5F] rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none text-xs font-semibold text-white <?= $is_locked ? 'bg-[#0B192C] text-slate-400 cursor-not-allowed' : 'bg-[#0B192C]' ?>">
                        </div>

                        <div>
                            <label class="block font-bold text-slate-300 mb-1">No. WhatsApp / HP Wali Murid *</label>
                            <input type="tel" name="no_hp" required value="<?= htmlspecialchars($siswa['no_hp'] ?? '') ?>" <?= $is_locked ? 'disabled' : '' ?>
                                class="w-full px-3.5 py-2.5 border border-[#1E3A5F] rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none text-xs font-semibold text-white <?= $is_locked ? 'bg-[#0B192C] text-slate-400 cursor-not-allowed' : 'bg-[#0B192C]' ?>">
                        </div>

                        <div class="sm:col-span-2 print:col-span-1">
                            <label class="block font-bold text-slate-300 mb-1">Alamat Tempat Tinggal</label>
                            <textarea name="alamat" rows="2" <?= $is_locked ? 'disabled' : '' ?>
                                class="w-full px-3.5 py-2 border border-[#1E3A5F] rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none text-xs font-semibold text-white <?= $is_locked ? 'bg-[#0B192C] text-slate-400 cursor-not-allowed' : 'bg-[#0B192C]' ?>"><?= htmlspecialchars($siswa['alamat'] ?? '') ?></textarea>
                        </div>

                        <div class="sm:col-span-2 print:col-span-1">
                            <label class="block font-bold text-slate-300 mb-1">
                                Pas Foto Siswa (3x4) Resmi
                            </label>
                            <div class="flex items-center gap-3 p-2.5 rounded-xl border border-[#1E3A5F] bg-[#07101E] print-photo-box">
                                <div class="w-14 h-18 sm:w-16 sm:h-20 rounded-lg border-2 border-dashed border-[#1E3A5F] flex items-center justify-center overflow-hidden shrink-0 bg-[#0B192C]" id="preview-foto-container">
                                    <?php if (!empty($siswa['foto']) && file_exists($siswa['foto'])): ?>
                                        <img id="preview-foto-siswa" src="<?= htmlspecialchars($siswa['foto']) ?>" alt="Foto Siswa" class="w-full h-full object-cover">
                                        <div id="placeholder-foto" class="text-center p-1 text-slate-500 hidden">
                                            <i class="fa-solid fa-camera text-base block mb-0.5"></i>
                                            <span class="text-[8px] block">3 x 4</span>
                                        </div>
                                    <?php else: ?>
                                        <img id="preview-foto-siswa" src="" alt="Pratinjau Foto" class="w-full h-full object-cover hidden">
                                        <div id="placeholder-foto" class="text-center p-1 text-slate-500">
                                            <i class="fa-solid fa-camera text-base block mb-0.5"></i>
                                            <span class="text-[8px] block">3 x 4</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="flex-1 w-full min-w-0">
                                    <?php if (!$is_locked): ?>
                                        <input type="file" name="foto" id="input-foto" accept="image/jpeg,image/png,image/webp,image/jpg" onchange="previewFotoSiswa(event)"
                                            class="w-full text-xs text-slate-300 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border border-[#2E5A8F] file:text-[11px] file:font-semibold file:bg-[#162B4D] file:text-blue-300 hover:file:bg-[#1E3A5F] hover:file:border-blue-400 file:cursor-pointer cursor-pointer print:hidden">
                                        <p class="text-[10px] text-slate-400 mt-1 leading-relaxed print:hidden">
                                            Format: JPG, PNG, WEBP (Maks. 3MB).
                                        </p>
                                        <span class="hidden print:block text-[11px] text-slate-700 font-medium">Pas foto pendaftaran siswa resmi.</span>
                                    <?php else: ?>
                                        <span class="text-[11px] text-slate-400 print:text-slate-700 italic">Pas foto resmi telah terverifikasi dalam sistem.</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- BAGIAN 2: KRITERIA SOSIAL EKONOMI -->
                <div class="pt-3 border-t border-[#1E3A5F] print:pt-2">
                    <h4 class="text-xs font-extrabold uppercase tracking-wider text-blue-300 mb-2.5 print:mb-2 print-section-title">
                        Kriteria Penilaian AHP
                    </h4>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 text-xs print:grid-cols-2 print:gap-3">
                        <div>
                            <label class="block font-bold text-slate-300 mb-1">Penghasilan Rata-rata Orang Tua per Bulan *</label>
                            <?php $c1 = (int)($siswa['penghasilan'] ?? 5); ?>
                            <select name="penghasilan" <?= $is_locked ? 'disabled' : '' ?>
                                class="w-full px-3.5 py-2.5 border border-[#1E3A5F] rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none text-xs font-semibold text-white <?= $is_locked ? 'bg-[#0B192C] text-slate-400 cursor-not-allowed' : 'bg-[#0B192C]' ?>">
                                <option value="5" <?= $c1 === 5 ? 'selected' : '' ?>>&lt; Rp 500.000</option>
                                <option value="4" <?= $c1 === 4 ? 'selected' : '' ?>>Rp 600.000 - Rp 1.000.000</option>
                                <option value="3" <?= $c1 === 3 ? 'selected' : '' ?>>Rp 1.000.000 - Rp 2.000.000</option>
                                <option value="2" <?= $c1 === 2 ? 'selected' : '' ?>>Rp 2.000.000 - Rp 3.000.000</option>
                                <option value="1" <?= $c1 === 1 ? 'selected' : '' ?>>&gt; Rp 4.000.000</option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-300 mb-1">Jumlah Anggota Keluarga yang Ditanggung *</label>
                            <?php $c2 = (int)($siswa['tanggungan'] ?? 5); ?>
                            <select name="tanggungan" <?= $is_locked ? 'disabled' : '' ?>
                                class="w-full px-3.5 py-2.5 border border-[#1E3A5F] rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none text-xs font-semibold text-white <?= $is_locked ? 'bg-[#0B192C] text-slate-400 cursor-not-allowed' : 'bg-[#0B192C]' ?>">
                                <option value="5" <?= $c2 === 5 ? 'selected' : '' ?>>&gt; 5 Orang</option>
                                <option value="4" <?= $c2 === 4 ? 'selected' : '' ?>>4 Orang</option>
                                <option value="3" <?= $c2 === 3 ? 'selected' : '' ?>>3 Orang</option>
                                <option value="2" <?= $c2 === 2 ? 'selected' : '' ?>>2 Orang</option>
                                <option value="1" <?= $c2 === 1 ? 'selected' : '' ?>>1 Orang</option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-300 mb-1">Kondisi Fisik Tempat Tinggal / Rumah *</label>
                            <?php $c3 = (int)($siswa['kondisi_rumah'] ?? 5); ?>
                            <select name="kondisi_rumah" <?= $is_locked ? 'disabled' : '' ?>
                                class="w-full px-3.5 py-2.5 border border-[#1E3A5F] rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none text-xs font-semibold text-white <?= $is_locked ? 'bg-[#0B192C] text-slate-400 cursor-not-allowed' : 'bg-[#0B192C]' ?>">
                                <option value="5" <?= $c3 === 5 ? 'selected' : '' ?>>Tidak Layak</option>
                                <option value="4" <?= $c3 === 4 ? 'selected' : '' ?>>Dinding Kayu</option>
                                <option value="3" <?= $c3 === 3 ? 'selected' : '' ?>>Dinding Batu Atap Seng</option>
                                <option value="2" <?= $c3 === 2 ? 'selected' : '' ?>>Dinding Batu Atap Genteng</option>
                                <option value="1" <?= $c3 === 1 ? 'selected' : '' ?>>Tembok Keramik</option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-300 mb-1">Prestasi Akademik Tertinggi Siswa *</label>
                            <?php $c4 = (int)($siswa['prestasi'] ?? 3); ?>
                            <select name="prestasi" <?= $is_locked ? 'disabled' : '' ?>
                                class="w-full px-3.5 py-2.5 border border-[#1E3A5F] rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none text-xs font-semibold text-white <?= $is_locked ? 'bg-[#0B192C] text-slate-400 cursor-not-allowed' : 'bg-[#0B192C]' ?>">
                                <option value="5" <?= $c4 === 5 ? 'selected' : '' ?>>Juara 1 - 3 Tingkat Kabupaten</option>
                                <option value="4" <?= $c4 === 4 ? 'selected' : '' ?>>Juara Harapan</option>
                                <option value="3" <?= $c4 === 3 ? 'selected' : '' ?>>Juara Kelas 1 - 3</option>
                                <option value="2" <?= $c4 === 2 ? 'selected' : '' ?>>Peringkat 10 Besar</option>
                                <option value="1" <?= $c4 === 1 ? 'selected' : '' ?>>Peringkat 20 Besar</option>
                            </select>
                        </div>

                        <div class="sm:col-span-2 print:col-span-1">
                            <label class="block font-bold text-slate-300 mb-1">Jarak Rumah Siswa ke Sekolah *</label>
                            <?php $c5 = (int)($siswa['jarak'] ?? 4); ?>
                            <select name="jarak" <?= $is_locked ? 'disabled' : '' ?>
                                class="w-full px-3.5 py-2.5 border border-[#1E3A5F] rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none text-xs font-semibold text-white <?= $is_locked ? 'bg-[#0B192C] text-slate-400 cursor-not-allowed' : 'bg-[#0B192C]' ?>">
                                <option value="5" <?= $c5 === 5 ? 'selected' : '' ?>>&gt; 5 km</option>
                                <option value="4" <?= $c5 === 4 ? 'selected' : '' ?>>3 – 5 km</option>
                                <option value="3" <?= $c5 === 3 ? 'selected' : '' ?>>2 km</option>
                                <option value="2" <?= $c5 === 2 ? 'selected' : '' ?>>1 km</option>
                                <option value="1" <?= $c5 === 1 ? 'selected' : '' ?>>&lt; 1 km</option>
                            </select>
                        </div>

                        <div class="hidden print:block col-span-1">
                            <label class="block font-bold text-slate-300 mb-1">Status Verifikasi Berkas Pendaftaran</label>
                            <input type="text" value="<?= htmlspecialchars($siswa['status_verifikasi']) ?>" disabled
                                class="w-full px-3.5 py-2.5 border border-[#1E3A5F] rounded-xl font-bold <?= $siswa['status_verifikasi'] === 'Terverifikasi' ? 'text-emerald-700' : 'text-amber-700' ?>">
                        </div>
                    </div>
                </div>

                <!-- BAGIAN 3: PERNYATAAN & TANDA TANGAN PENDAFTAR (PRINT ONLY) -->
                <div class="hidden print:block pt-3 border-t border-slate-300 mt-2">
                    <div class="p-2.5 rounded-lg border border-slate-300 bg-slate-50 text-[10.5px] leading-relaxed text-slate-700 mb-3">
                        <b>Pernyataan Kebenaran Data:</b> Saya menyatakan dengan sesungguhnya bahwa seluruh data yang tercantum dalam formulir ini adalah benar, sah, dan dapat dipertanggungjawabkan sesuai dokumen pendukung fisik calon penerima bantuan Program Indonesia Pintar (PIP).
                    </div>
                    <div class="flex items-end justify-between text-xs text-slate-800 px-2">
                        <div>
                            <p class="text-[11px] text-slate-600">Tanggal Cetak: <b class="text-slate-800"><?= date('d F Y') ?></b></p>
                            <p class="text-[11px] font-semibold text-slate-700">Status Akun: Terdaftar Resmi &bull; NISN: <?= htmlspecialchars($siswa['nisn']) ?></p>
                        </div>
                        <div class="text-center w-52">
                            <p class="text-[11px]">Calon Penerima / Wali Murid,</p>
                            <div class="h-10 border-b border-dotted border-slate-600 mx-auto w-40 mt-1"></div>
                            <p class="text-[11px] font-bold mt-1">( <?= htmlspecialchars($siswa['nama_ortu'] ?: $siswa['nama']) ?> )</p>
                        </div>
                    </div>
                </div>

                <!-- LEMBAR 1 FOOTER: CATATAN & NOMOR HALAMAN (PRINT ONLY) -->
                <div class="hidden print:flex items-center justify-between pt-2.5 border-t-2 border-slate-800 text-[10.5px] text-slate-600 font-medium">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-file-lines text-slate-700"></i>
                        <span>Salinan Resmi Formulir Pendaftaran PIP &bull; Dicetak Mandiri melalui Portal Siswa</span>
                    </div>
                    <span class="font-bold text-slate-800 bg-slate-100 px-2 py-0.5 rounded border border-slate-300">
                        Halaman 1 dari 2
                    </span>
                </div>

                <!-- SUBMIT PERUBAHAN -->
                <?php if (!$is_locked): ?>
                    <div class="pt-4 border-t border-[#1E3A5F] flex items-center justify-between no-print">
                        <?php if (!$is_registration_open): ?>
                            <div class="w-full p-3 bg-[#0B192C] border border-[#1E3A5F] text-slate-300 rounded-xl text-xs flex items-center gap-2">
                                <i class="fa-solid fa-calendar-xmark text-slate-500"></i>
                                <span>Periode pendaftaran telah ditutup, pembaruan data telah berakhir.</span>
                            </div>
                        <?php else: ?>
                            <span class="text-xs text-slate-500">Pastikan data yang diperbarui sudah sesuai dengan dokumen fisik.</span>
                            <button type="submit" class="px-6 py-2.5 bg-[#162B4D] hover:bg-[#1E3A5F] text-white border border-[#2E5A8F] hover:border-blue-400 font-bold rounded-xl text-xs shadow-md transition-all duration-200 hover:scale-[1.02] active:scale-[0.98] cursor-pointer flex items-center gap-2">
                                <i class="fa-solid fa-floppy-disk text-xs text-blue-400"></i>
                                <span>Simpan Pembaruan Data</span>
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            </form>
        </div>

    </main>

    <!-- MODAL GANTI PIN -->
    <div id="modal-ganti-pin" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 hidden no-print">
        <div class="bg-[#112240] w-full max-w-md rounded-2xl shadow-2xl border border-[#1E3A5F] p-6 space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-[#1E3A5F]">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-blue-950/60 text-blue-400 border border-blue-800/60 flex items-center justify-center text-sm">
                        <i class="fa-solid fa-key"></i>
                    </div>
                    <h3 class="font-bold text-white text-sm">Ganti PIN Keamanan Akun</h3>
                </div>
                <button type="button" onclick="tutupModalGantiPin()" class="text-slate-400 hover:text-slate-600 font-bold text-lg leading-none cursor-pointer">&times;</button>
            </div>

            <p class="text-xs text-slate-300 leading-relaxed">
                PIN ini digunakan untuk login ke portal akun pendaftar agar data anak Anda terlindungi dari pihak lain.
            </p>

            <form action="portal_siswa.php" method="POST" class="space-y-3.5 text-xs">
                <input type="hidden" name="action" value="ganti_pin">

                <div>
                    <label class="block font-bold text-slate-300 mb-1">PIN Lama <span class="text-rose-500">*</span></label>
                    <input type="password" name="pin_lama" required placeholder="Masukkan PIN saat ini"
                        class="w-full px-3.5 py-2.5 border border-[#1E3A5F] rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none font-semibold text-white bg-[#0B192C]">
                </div>

                <div>
                    <label class="block font-bold text-slate-300 mb-1">PIN Baru (4 - 6 digit) <span class="text-rose-500">*</span></label>
                    <input type="password" name="pin_baru" required maxlength="10" placeholder="Ketik PIN baru"
                        class="w-full px-3.5 py-2.5 border border-[#1E3A5F] rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none font-semibold text-white bg-[#0B192C]">
                </div>

                <div>
                    <label class="block font-bold text-slate-300 mb-1">Konfirmasi PIN Baru <span class="text-rose-500">*</span></label>
                    <input type="password" name="pin_baru_konf" required maxlength="10" placeholder="Ulangi PIN baru"
                        class="w-full px-3.5 py-2.5 border border-[#1E3A5F] rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none font-semibold text-white bg-[#0B192C]">
                </div>

                <div class="pt-2 flex items-center justify-end gap-2.5">
                    <button type="button" onclick="tutupModalGantiPin()" class="px-4 py-2 bg-[#112240] hover:bg-[#162B4D] text-slate-300 border border-[#1E3A5F] hover:border-[#2E5A8F] rounded-xl font-semibold transition-all duration-200 hover:scale-[1.02] active:scale-[0.98] cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 bg-[#162B4D] hover:bg-[#1E3A5F] text-white border border-[#2E5A8F] hover:border-blue-400 rounded-xl font-bold transition-all duration-200 hover:scale-[1.02] active:scale-[0.98] cursor-pointer">
                        Simpan PIN Baru
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- TAMPILAN CETAK BUKTI PENDAFTARAN RESMI (PRINT ONLY - LEMBAR 2) -->
    <div class="print-only print-sheet-2 p-6 text-black text-xs leading-relaxed max-w-2xl mx-auto">
        <div class="flex items-center justify-between text-[10px] text-gray-500 mb-2 border-b border-gray-200 pb-1 font-semibold italic">
            <span>Lembar 2: Tanda Terima &amp; Bukti Pendaftaran Resmi</span>
            <span>No. NISN: <?= htmlspecialchars($siswa['nisn']) ?> &bull; Tanggal Cetak: <?= date('d/m/Y') ?></span>
        </div>

        <div class="text-center pb-3 border-b-2 border-black mb-5">
            <h2 class="text-base font-extrabold uppercase"><?= htmlspecialchars($pengaturan['nama_yayasan'] ?? 'YAYASAN AL QODIRI LAMPUNG') ?></h2>
            <h1 class="text-lg font-black uppercase"><?= htmlspecialchars($nama_sekolah) ?></h1>
            <p class="text-[11px]"><?= htmlspecialchars($pengaturan['sub_instansi'] ?? 'KABUPATEN LAMPUNG TENGAH') ?></p>
            <p class="text-[10px] mt-0.5">Tanda Terima &amp; Bukti Pendaftaran Calon Penerima Bantuan PIP (Metode AHP) &bull; T.A. <?= htmlspecialchars($pengaturan['tahun_ajaran']) ?></p>
        </div>

        <div class="mb-4 flex items-start justify-between gap-4">
            <div class="flex-1">
                <h3 class="font-bold text-sm uppercase mb-2 border-b border-gray-300 pb-1">I. Data Calon Penerima</h3>
                <table class="w-full text-xs">
                    <tr><td class="w-40 py-1 font-semibold">Nomor NISN</td><td class="w-4">:</td><td class="font-mono font-bold"><?= htmlspecialchars($siswa['nisn']) ?></td></tr>
                    <tr><td class="py-1 font-semibold">Nama Siswa</td><td>:</td><td class="font-bold"><?= htmlspecialchars($siswa['nama']) ?></td></tr>
                    <tr><td class="py-1 font-semibold">Jenis Kelamin</td><td>:</td><td><?= htmlspecialchars($siswa['jenis_kelamin'] ?? 'Laki-laki') ?></td></tr>
                    <tr><td class="py-1 font-semibold">Sekolah Asal</td><td>:</td><td><?= htmlspecialchars($siswa['sekolah_asal'] ?: 'SD/MI') ?></td></tr>
                    <tr><td class="py-1 font-semibold">Tingkat Kelas</td><td>:</td><td><?= htmlspecialchars($siswa['kelas']) ?></td></tr>
                    <tr><td class="py-1 font-semibold">Nama Wali Murid</td><td>:</td><td><?= htmlspecialchars($siswa['nama_ortu'] ?? '-') ?></td></tr>
                    <tr><td class="py-1 font-semibold">Nomor Kontak / HP</td><td>:</td><td><?= htmlspecialchars($siswa['no_hp'] ?? '-') ?></td></tr>
                    <tr><td class="py-1 font-semibold">Alamat</td><td>:</td><td><?= htmlspecialchars($siswa['alamat'] ?? '-') ?></td></tr>
                </table>
            </div>
            <!-- FOTO RESMI SISWA 3x4 (PRINT) -->
            <div class="w-24 h-32 border-2 border-gray-400 flex items-center justify-center shrink-0 overflow-hidden bg-gray-50">
                <?php if (!empty($siswa['foto']) && file_exists($siswa['foto'])): ?>
                    <img src="<?= htmlspecialchars($siswa['foto']) ?>" alt="Foto <?= htmlspecialchars($siswa['nama']) ?>" class="w-full h-full object-cover">
                <?php else: ?>
                    <span class="text-gray-400 text-[10px] font-bold">FOTO 3x4</span>
                <?php endif; ?>
            </div>
        </div>

        <div class="mb-6">
            <h3 class="font-bold text-sm uppercase mb-2 border-b border-gray-300 pb-1">II. Rincian Kriteria Sosial Ekonomi</h3>
            <table class="w-full text-xs">
                <tr><td class="w-40 py-1 font-semibold">Penghasilan Orang Tua</td><td class="w-4">:</td><td><?= $label_penghasilan[$siswa['penghasilan']] ?? '-' ?></td></tr>
                <tr><td class="py-1 font-semibold">Tanggungan Keluarga</td><td>:</td><td><?= $label_tanggungan[$siswa['tanggungan']] ?? '-' ?></td></tr>
                <tr><td class="py-1 font-semibold">Kondisi Rumah</td><td>:</td><td><?= $label_rumah[$siswa['kondisi_rumah']] ?? '-' ?></td></tr>
                <tr><td class="py-1 font-semibold">Prestasi Siswa</td><td>:</td><td><?= $label_prestasi[$siswa['prestasi']] ?? '-' ?></td></tr>
                <tr><td class="py-1 font-semibold">Jarak ke Sekolah</td><td>:</td><td><?= $label_jarak[$siswa['jarak']] ?? '-' ?></td></tr>
                <tr><td class="py-1 font-semibold">Status Berkas</td><td>:</td><td class="font-bold"><?= htmlspecialchars($siswa['status_verifikasi']) ?></td></tr>
            </table>
        </div>

        <div class="pt-8 grid grid-cols-2 text-center text-xs">
            <div>
                <p>Orang Tua / Wali Murid,</p>
                <div class="h-16"></div>
                <p class="font-bold underline"><?= htmlspecialchars($siswa['nama_ortu'] ?: $siswa['nama']) ?></p>
            </div>
            <div>
                <p>Panitia Seleksi PIP Sekolah,</p>
                <div class="h-16"></div>
                <p class="font-bold underline"><?= htmlspecialchars($pengaturan['kepala_sekolah'] ?? 'Panitia TU') ?></p>
            </div>
        </div>
    </div>

    <!-- FOOTER -->
    <footer class="bg-[#112240] border-t border-[#1E3A5F] py-5 text-center text-xs text-slate-400 no-print">
        <div class="max-w-5xl mx-auto px-4 flex items-center justify-center">
            <span>&copy; <?= date('Y') ?> <?= htmlspecialchars($nama_sekolah) ?> &bull; Sistem SPK PIP (Metode AHP)</span>
        </div>
    </footer>

    <script>
        function bukaModalGantiPin() {
            document.getElementById('modal-ganti-pin').classList.remove('hidden');
        }
        function tutupModalGantiPin() {
            document.getElementById('modal-ganti-pin').classList.add('hidden');
        }
        function previewFotoSiswa(event) {
            const file = event.target.files[0];
            const img = document.getElementById('preview-foto-siswa');
            const placeholder = document.getElementById('placeholder-foto');
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    if (img) {
                        img.src = e.target.result;
                        img.classList.remove('hidden');
                    }
                    if (placeholder) placeholder.classList.add('hidden');
                }
                reader.readAsDataURL(file);
            }
        }

        function animasiUnduhPDF(btn) {
            const originalContent = btn.innerHTML;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin text-xs text-emerald-400"></i> <span>Menyiapkan PDF...</span>';
            btn.classList.add('opacity-75', 'pointer-events-none');
            setTimeout(function() {
                btn.innerHTML = originalContent;
                btn.classList.remove('opacity-75', 'pointer-events-none');
            }, 4000);
        }
    </script>
</body>
</html>

