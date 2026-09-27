<?php
// daftar_siswa.php - Formulir Pendaftaran Akun & Pengajuan PIP Mandiri oleh Orang Tua / Siswa
require_once "koneksi.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Pastikan kolom pin & foto ada di tabel calon_penerima
$cek_pin = mysqli_query($koneksi, "SHOW COLUMNS FROM `calon_penerima` LIKE 'pin'");
if (mysqli_num_rows($cek_pin) == 0) {
    @mysqli_query($koneksi, "ALTER TABLE `calon_penerima` ADD COLUMN `pin` VARCHAR(255) NULL AFTER `no_hp`");
}
$cek_foto = mysqli_query($koneksi, "SHOW COLUMNS FROM `calon_penerima` LIKE 'foto'");
if (mysqli_num_rows($cek_foto) == 0) {
    @mysqli_query($koneksi, "ALTER TABLE `calon_penerima` ADD COLUMN `foto` VARCHAR(255) NULL AFTER `no_hp`");
}

// Jika siswa sudah login, langsung alihkan ke portal_siswa.php
if (isset($_SESSION['siswa'])) {
    header("Location: portal_siswa.php");
    exit;
}

// Ambil info sekolah & jadwal pendaftaran
$res_pengaturan = mysqli_query($koneksi, "SELECT * FROM `pengaturan` WHERE id=1");
$pengaturan = mysqli_fetch_assoc($res_pengaturan) ?: [
    'nama_sekolah' => 'SMP Tunas Bangsa',
    'tahun_ajaran' => '2025/2026',
    'tgl_buka_pengajuan' => '2026-09-01',
    'tgl_tutup_pengajuan' => '2026-10-31',
    'logo' => 'uploads/logo_default.png'
];

$today = date('Y-m-d');
$tgl_buka = $pengaturan['tgl_buka_pengajuan'] ?? '2026-09-01';
$tgl_tutup = $pengaturan['tgl_tutup_pengajuan'] ?? '2026-10-31';
$is_registration_open = ($today >= $tgl_buka && $today <= $tgl_tutup);

$error = "";
$success = "";

// Form Data Preservation
$formData = [
    'nisn' => '',
    'nama' => '',
    'nama_ortu' => '',
    'no_hp' => '',
    'jenis_kelamin' => 'Laki-laki',
    'sekolah_asal' => '',
    'alamat' => '',
    'penghasilan' => 5,
    'tanggungan' => 5,
    'kondisi_rumah' => 5,
    'prestasi' => 3,
    'jarak' => 4
];

// Proses Pendaftaran Akun & Pengajuan
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData['nisn']          = trim($_POST['nisn'] ?? '');
    $formData['nama']          = trim($_POST['nama'] ?? '');
    $formData['nama_ortu']     = trim($_POST['nama_ortu'] ?? '');
    $formData['no_hp']         = trim($_POST['no_hp'] ?? '');
    $formData['jenis_kelamin'] = $_POST['jenis_kelamin'] ?? 'Laki-laki';
    $formData['sekolah_asal']  = trim($_POST['sekolah_asal'] ?? '');
    $formData['alamat']        = trim($_POST['alamat'] ?? '');
    $formData['penghasilan']   = (int)($_POST['penghasilan'] ?? 5);
    $formData['tanggungan']    = (int)($_POST['tanggungan'] ?? 5);
    $formData['kondisi_rumah'] = (int)($_POST['kondisi_rumah'] ?? 5);
    $formData['prestasi']      = (int)($_POST['prestasi'] ?? 3);
    $formData['jarak']         = (int)($_POST['jarak'] ?? 4);

    $pin                       = trim($_POST['pin'] ?? '');
    $pin_konfirmasi            = trim($_POST['pin_konfirmasi'] ?? '');

    if (!$is_registration_open) {
        $error = "Mohon maaf, pendaftaran bantuan PIP saat ini sedang ditutup (Periode: " . format_tgl_indo($tgl_buka) . " s.d. " . format_tgl_indo($tgl_tutup) . ").";
    } elseif (empty($formData['nisn']) || empty($formData['nama'])) {
        $error = "Nomor NISN dan Nama Lengkap Siswa wajib diisi!";
    } elseif (strlen($formData['nisn']) < 6) {
        $error = "Nomor NISN tidak valid (minimal 6 - 10 digit angka)!";
    } elseif (empty($pin)) {
        $error = "Silakan buat PIN Keamanan (minimal 4 - 6 digit angka) untuk melindungi akun pengajuan Anda!";
    } elseif (strlen($pin) < 4) {
        $error = "PIN Keamanan terlalu pendek, minimal 4 digit angka demi keamanan!";
    } elseif ($pin !== $pin_konfirmasi) {
        $error = "Konfirmasi PIN tidak cocok dengan PIN yang dibuat. Silakan ketik ulang dengan teliti!";
    } else {
        // Cek apakah NISN sudah pernah terdaftar
        $stmt_cek = mysqli_prepare($koneksi, "SELECT id_siswa, nisn, nama, pin FROM `calon_penerima` WHERE `nisn` = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt_cek, "s", $formData['nisn']);
        mysqli_stmt_execute($stmt_cek);
        $res_cek = mysqli_stmt_get_result($stmt_cek);

        if ($siswa_ada = mysqli_fetch_assoc($res_cek)) {
            $error = "NISN <b>" . htmlspecialchars($formData['nisn']) . "</b> sudah terdaftar atas nama <b>" . htmlspecialchars($siswa_ada['nama']) . "</b>.<br>Silakan <a href='login_siswa.php?nisn=" . urlencode($formData['nisn']) . "' class='underline font-bold text-blue-700 hover:text-blue-900'>Login ke Akun Pendaftar</a> menggunakan PIN Anda.";
        } else {
            // Validasi dan Upload Pas Foto Siswa (3x4)
            $foto_path = null;
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                $file_tmp = $_FILES['foto']['tmp_name'];
                $file_name = $_FILES['foto']['name'];
                $file_size = $_FILES['foto']['size'];
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                $allowed_ext = ['jpg', 'jpeg', 'png', 'webp'];

                if (!in_array($file_ext, $allowed_ext)) {
                    $error = "Format file foto tidak didukung! Harap unggah foto dengan format JPG, JPEG, PNG, atau WEBP.";
                } elseif ($file_size > 3 * 1024 * 1024) {
                    $error = "Ukuran file foto terlalu besar! Maksimal ukuran file adalah 3MB.";
                } else {
                    $clean_nisn = preg_replace('/[^a-zA-Z0-9_-]/', '', $formData['nisn']);
                    $new_foto_name = "uploads/foto_siswa/siswa_" . $clean_nisn . "_" . time() . "." . $file_ext;
                    if (!is_dir("uploads/foto_siswa")) {
                        @mkdir("uploads/foto_siswa", 0777, true);
                    }
                    if (move_uploaded_file($file_tmp, $new_foto_name)) {
                        $foto_path = $new_foto_name;
                    } else {
                        $error = "Gagal mengunggah file foto siswa. Silakan coba kembali.";
                    }
                }
            } else {
                $error = "Pas foto siswa (3x4) wajib diunggah!";
            }

            if (empty($error)) {
                // Hash PIN pendaftar
                $hashed_pin = password_hash($pin, PASSWORD_DEFAULT);
                $tahun_pengajuan = $pengaturan['tahun_ajaran'] ?? '2025/2026';
                $kelas = 'Kelas VII';

                $stmt_ins = mysqli_prepare($koneksi, "INSERT INTO `calon_penerima` 
                    (`nisn`, `nama`, `nama_ortu`, `no_hp`, `foto`, `pin`, `jenis_kelamin`, `kelas`, `sekolah_asal`, `alamat`, `penghasilan`, `tanggungan`, `kondisi_rumah`, `prestasi`, `jarak`, `status_verifikasi`, `tahun`) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Menunggu Verifikasi', ?)");

                mysqli_stmt_bind_param(
                    $stmt_ins, 
                    "ssssssssssiiiiis", 
                    $formData['nisn'], 
                    $formData['nama'], 
                    $formData['nama_ortu'], 
                    $formData['no_hp'], 
                    $foto_path,
                    $hashed_pin, 
                    $formData['jenis_kelamin'], 
                    $kelas, 
                    $formData['sekolah_asal'], 
                    $formData['alamat'], 
                    $formData['penghasilan'], 
                    $formData['tanggungan'], 
                    $formData['kondisi_rumah'], 
                    $formData['prestasi'], 
                    $formData['jarak'], 
                    $tahun_pengajuan
                );

                if (mysqli_stmt_execute($stmt_ins)) {
                    $new_id = mysqli_insert_id($koneksi);
                    // Langsung login-kan siswa
                    $_SESSION['siswa'] = [
                        'id_siswa' => $new_id,
                        'nisn'     => $formData['nisn'],
                        'nama'     => $formData['nama']
                    ];
                    header("Location: portal_siswa.php?status=sukses_daftar");
                    exit;
                } else {
                    $error = "Terjadi kendala saat menyimpan pendaftaran: " . mysqli_error($koneksi);
                }
            }
        }
    }
}

$logo_sekolah = !empty($pengaturan['logo']) && file_exists($pengaturan['logo']) ? $pengaturan['logo'] : 'uploads/logo_default.png';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendaftaran Akun Pengajuan PIP - <?= htmlspecialchars($pengaturan['nama_sekolah']) ?></title>
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
                    fontFamily: { sans: ['"Roboto"', 'sans-serif'] },
                    colors: {
                        brand: { 50: '#eff6ff', 100: '#dbeafe', 500: '#3b82f6', 600: '#2563eb', 700: '#1d4ed8', 800: '#1e40af', 900: '#1e3a8a' }
                    }
                }
            }
        }
    </script>
    <style>
        body, input, button, select, textarea { font-family: 'Roboto', sans-serif !important; }
    </style>
</head>
<body class="bg-[#0B192C] min-h-screen text-slate-100 font-sans flex flex-col justify-between">

    <!-- HEADER / NAVBAR -->
    <header class="bg-[#112240] border-b border-[#1E3A5F] sticky top-0 z-30 shadow-md">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 py-3.5 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-[#0B192C] border border-[#1E3A5F] p-1 flex items-center justify-center shrink-0">
                    <img src="<?= htmlspecialchars($logo_sekolah) ?>" alt="Logo Sekolah" class="max-h-full max-w-full object-contain">
                </div>
                <div>
                    <h1 class="text-sm font-extrabold text-white leading-tight">Pendaftaran Pengajuan PIP</h1>
                    <p class="text-[11px] font-semibold text-blue-300 uppercase tracking-wider"><?= htmlspecialchars($pengaturan['nama_sekolah']) ?></p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="login_siswa.php" class="px-3.5 py-1.5 bg-[#162B4D] hover:bg-[#1E3A5F] text-white border border-[#2E5A8F] hover:border-blue-400 rounded-xl text-xs font-semibold transition-all duration-200 hover:scale-[1.02] active:scale-[0.98] inline-flex items-center gap-1.5">
                    <i class="fa-solid fa-right-to-bracket text-xs text-blue-400"></i>
                    <span>Login Pendaftar</span>
                </a>
            </div>
        </div>
    </header>

    <!-- KONTEN UTAMA -->
    <main class="max-w-4xl w-full mx-auto px-4 sm:px-6 py-8 flex-1">

        <!-- KARTU INFORMASI JADWAL -->
        <div class="mb-6 p-4 rounded-2xl border text-xs shadow-xs
            <?= $is_registration_open ? 'bg-emerald-50 border-emerald-200 text-emerald-900' : 'bg-rose-50 border-rose-200 text-rose-900' ?>">
            <div>
                <div class="font-bold text-xs uppercase tracking-wider">
                    <?= $is_registration_open ? 'Pendaftaran Bantuan PIP Sedang Dibuka' : 'Periode Pendaftaran Telah Ditutup' ?>
                </div>
                <div class="text-[11px] text-slate-600 mt-1 flex flex-wrap items-center gap-1.5">
                    <span>Tahun Ajaran: <b><?= htmlspecialchars($pengaturan['tahun_ajaran']) ?></b></span>
                    <span class="text-slate-300">&bull;</span>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-lg <?= $is_registration_open ? 'bg-white/90 hover:bg-white text-emerald-800 border border-emerald-200/90 hover:border-emerald-300' : 'bg-white/90 hover:bg-white text-rose-800 border border-rose-200/90 hover:border-rose-300' ?> font-semibold shadow-2xs hover:shadow-xs transition-all cursor-default" title="Rentang Waktu Pendaftaran">
                        <i class="fa-regular fa-clock text-[10px] <?= $is_registration_open ? 'text-emerald-600' : 'text-rose-600' ?>"></i>
                        <span>Jadwal: <b><?= format_tgl_indo($tgl_buka) ?> s.d. <?= format_tgl_indo($tgl_tutup) ?></b></span>
                    </span>
                </div>
            </div>
        </div>

        <?php if (!empty($error)): ?>
            <div class="mb-6 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold shadow-xs flex items-start justify-between gap-3">
                <div class="flex items-start gap-2.5">
                    <i class="fa-solid fa-triangle-exclamation text-rose-500 text-sm mt-0.5 shrink-0"></i>
                    <div class="leading-relaxed"><?= $error ?></div>
                </div>
                <button type="button" onclick="this.parentElement.remove()" class="text-rose-400 hover:text-rose-600 font-bold text-base leading-none">&times;</button>
            </div>
        <?php endif; ?>

        <!-- FORM PENDAFTARAN -->
        <div class="bg-[#112240] rounded-2xl border border-[#1E3A5F] shadow-2xl overflow-hidden">
            
            <div class="p-6 bg-gradient-to-r from-[#07101E] to-[#1E3A5F] text-white border-b border-[#1E3A5F]">
                <h2 class="text-lg font-bold">Formulir Pendaftaran Akun Pendaftar PIP</h2>
                <p class="text-xs text-blue-200 mt-1">
                    Isi biodata anak, buat PIN keamanan akun Anda, dan lengkapi kriteria sosial ekonomi.
                </p>
            </div>

            <form action="daftar_siswa.php" method="POST" enctype="multipart/form-data" class="p-6 sm:p-8 space-y-6">

                <!-- BAGIAN 1: KEAMANAN AKUN (NISN & PIN) -->
                <div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                        <div class="sm:col-span-2">
                            <label class="block font-bold text-slate-300 mb-1">
                                Nomor Induk Siswa Nasional (NISN) <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" name="nisn" id="input-nisn" required maxlength="20"
                                value="<?= htmlspecialchars($formData['nisn']) ?>"
                                placeholder="Contoh: 0081234567"
                                class="w-full px-3.5 py-2.5 border border-[#1E3A5F] rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none text-xs font-bold text-white bg-[#0B192C]">
                            <span class="text-[10px] text-slate-400 mt-1 block">NISN dapat dilihat pada Kartu Pelajar, Rapor SD/MI, atau Ijazah.</span>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-300 mb-1">
                                Buat PIN Keamanan Akun <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative flex items-center">
                                <input type="password" name="pin" id="input-pin" required maxlength="10" placeholder="Minimal 4 - 6 digit angka (misal: 123456)"
                                    class="w-full pl-3.5 pr-11 py-2.5 border border-[#1E3A5F] rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none text-xs font-semibold text-white bg-[#0B192C]">
                                <button type="button" onclick="togglePin('input-pin', 'icon-eye-1')" class="absolute right-0 inset-y-0 px-3.5 flex items-center text-slate-400 hover:text-slate-700 cursor-pointer select-none" title="Lihat/Sembunyikan PIN">
                                    <i class="fa-solid fa-eye text-sm" id="icon-eye-1"></i>
                                </button>
                            </div>
                            <span class="text-[10px] text-slate-400 mt-1 block">Buat PIN yang mudah Anda ingat, contoh: 6 digit tanggal lahir.</span>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-300 mb-1">
                                Ulangi Konfirmasi PIN <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative flex items-center">
                                <input type="password" name="pin_konfirmasi" id="input-pin-confirm" required maxlength="10" placeholder="Ketik ulang PIN yang sama"
                                    class="w-full pl-3.5 pr-11 py-2.5 border border-[#1E3A5F] rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none text-xs font-semibold text-white bg-[#0B192C]">
                                <button type="button" onclick="togglePin('input-pin-confirm', 'icon-eye-2')" class="absolute right-0 inset-y-0 px-3.5 flex items-center text-slate-400 hover:text-slate-700 cursor-pointer select-none" title="Lihat/Sembunyikan PIN">
                                    <i class="fa-solid fa-eye text-sm" id="icon-eye-2"></i>
                                </button>
                            </div>
                            <span class="text-[10px] text-slate-400 mt-1 block">Pastikan PIN konfirmasi sama persis.</span>
                        </div>
                    </div>
                </div>

                <!-- BAGIAN 2: IDENTITAS SISWA & WALI -->
                <div>
                    <div class="pb-2.5 border-b border-[#1E3A5F] mb-4">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-300">
                            Identitas Siswa &amp; Kontak Wali Murid
                        </h3>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                        <div>
                            <label class="block font-bold text-slate-300 mb-1">Nama Lengkap Siswa <span class="text-rose-500">*</span></label>
                            <input type="text" name="nama" required value="<?= htmlspecialchars($formData['nama']) ?>" placeholder="Nama sesuai rapor / ijazah"
                                class="w-full px-3.5 py-2.5 border border-[#1E3A5F] rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none text-xs font-semibold text-white bg-[#0B192C]">
                        </div>

                        <div>
                            <label class="block font-bold text-slate-300 mb-1">Sekolah Asal (SD / MI) <span class="text-rose-500">*</span></label>
                            <input type="text" name="sekolah_asal" required value="<?= htmlspecialchars($formData['sekolah_asal']) ?>" placeholder="Contoh: SDN 1 Bandar Mataram"
                                class="w-full px-3.5 py-2.5 border border-[#1E3A5F] rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none text-xs font-semibold text-white bg-[#0B192C]">
                            <span class="text-[10px] text-slate-400">Pendaftar adalah calon siswa baru (Kelas VII).</span>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-300 mb-1">Jenis Kelamin <span class="text-rose-500">*</span></label>
                            <select name="jenis_kelamin" class="w-full px-3.5 py-2.5 border border-[#1E3A5F] rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none text-xs font-semibold text-white bg-[#0B192C]">
                                <option value="Laki-laki" <?= $formData['jenis_kelamin'] === 'Laki-laki' ? 'selected' : '' ?>>Laki-laki</option>
                                <option value="Perempuan" <?= $formData['jenis_kelamin'] === 'Perempuan' ? 'selected' : '' ?>>Perempuan</option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-300 mb-1">Nama Orang Tua / Wali <span class="text-rose-500">*</span></label>
                            <input type="text" name="nama_ortu" required value="<?= htmlspecialchars($formData['nama_ortu']) ?>" placeholder="Nama Ayah / Ibu / Wali"
                                class="w-full px-3.5 py-2.5 border border-[#1E3A5F] rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none text-xs font-semibold text-white bg-[#0B192C]">
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block font-bold text-slate-300 mb-1">No. WhatsApp / HP Aktif <span class="text-rose-500">*</span></label>
                            <input type="tel" name="no_hp" required value="<?= htmlspecialchars($formData['no_hp']) ?>" placeholder="Contoh: 081234567890"
                                class="w-full px-3.5 py-2.5 border border-[#1E3A5F] rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none text-xs font-semibold text-white bg-[#0B192C]">
                            <span class="text-[10px] text-slate-400">Nomor aktif untuk pengumuman verifikasi berkas dari pihak sekolah.</span>
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block font-bold text-slate-300 mb-1">Alamat Tempat Tinggal</label>
                            <textarea name="alamat" rows="2" placeholder="Contoh: Dusun I RT 02 / RW 01, Desa Bandar Mataram"
                                class="w-full px-3.5 py-2.5 border border-[#1E3A5F] rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none text-xs font-semibold text-white bg-[#0B192C]"><?= htmlspecialchars($formData['alamat']) ?></textarea>
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block font-bold text-slate-300 mb-1">
                                Pas Foto Siswa (3x4) Resmi <span class="text-rose-500">*</span>
                            </label>
                            <div class="flex flex-col sm:flex-row items-center gap-4 p-4 rounded-xl border border-[#1E3A5F] bg-[#07101E]">
                                <div class="w-20 h-24 rounded-lg border-2 border-dashed border-[#1E3A5F] flex items-center justify-center overflow-hidden shrink-0 bg-[#0B192C]" id="preview-foto-container">
                                    <img id="preview-foto-siswa" src="" alt="Pratinjau Foto" class="w-full h-full object-cover hidden">
                                    <div id="placeholder-foto" class="text-center p-1 text-slate-500">
                                        <i class="fa-solid fa-camera text-xl block mb-1"></i>
                                        <span class="text-[9px] block">3 x 4</span>
                                    </div>
                                </div>
                                <div class="flex-1 w-full">
                                    <input type="file" name="foto" id="input-foto" required accept="image/jpeg,image/png,image/webp,image/jpg" onchange="previewFotoSiswa(event)"
                                        class="w-full text-xs text-slate-300 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border border-[#2E5A8F] file:text-xs file:font-semibold file:bg-[#162B4D] file:text-blue-300 hover:file:bg-[#1E3A5F] hover:file:border-blue-400 file:cursor-pointer cursor-pointer">
                                    <p class="text-[10px] text-slate-400 mt-1.5 leading-relaxed">
                                        Unggah pas foto resmi siswa (latar merah/biru atau seragam sekolah). Format: <b>JPG, JPEG, PNG, WEBP</b> (Maksimal 3MB).
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- BAGIAN 3: KRITERIA SOSIAL EKONOMI (SPK AHP) -->
                <div>
                    <div class="pb-2.5 border-b border-[#1E3A5F] mb-4">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-300">
                            Kondisi Sosial Ekonomi &amp; Kriteria Penilaian AHP
                        </h3>
                    </div>

                    <div class="space-y-4 text-xs">
                        <div>
                            <label class="block font-bold text-slate-300 mb-1">Penghasilan Rata-rata Orang Tua per Bulan <span class="text-rose-500">*</span></label>
                            <select name="penghasilan" class="w-full px-3.5 py-2.5 border border-[#1E3A5F] rounded-xl bg-[#0B192C] focus:ring-2 focus:ring-blue-500 focus:outline-none text-xs font-semibold text-white">
                                <option value="5" <?= $formData['penghasilan'] === 5 ? 'selected' : '' ?>>&lt; Rp 500.000</option>
                                <option value="4" <?= $formData['penghasilan'] === 4 ? 'selected' : '' ?>>Rp 600.000 - Rp 1.000.000</option>
                                <option value="3" <?= $formData['penghasilan'] === 3 ? 'selected' : '' ?>>Rp 1.000.000 - Rp 2.000.000</option>
                                <option value="2" <?= $formData['penghasilan'] === 2 ? 'selected' : '' ?>>Rp 2.000.000 - Rp 3.000.000</option>
                                <option value="1" <?= $formData['penghasilan'] === 1 ? 'selected' : '' ?>>&gt; Rp 4.000.000</option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-300 mb-1">Jumlah Anggota Keluarga yang Ditanggung <span class="text-rose-500">*</span></label>
                            <select name="tanggungan" class="w-full px-3.5 py-2.5 border border-[#1E3A5F] rounded-xl bg-[#0B192C] focus:ring-2 focus:ring-blue-500 focus:outline-none text-xs font-semibold text-white">
                                <option value="5" <?= $formData['tanggungan'] === 5 ? 'selected' : '' ?>>&gt; 5 Orang</option>
                                <option value="4" <?= $formData['tanggungan'] === 4 ? 'selected' : '' ?>>4 Orang</option>
                                <option value="3" <?= $formData['tanggungan'] === 3 ? 'selected' : '' ?>>3 Orang</option>
                                <option value="2" <?= $formData['tanggungan'] === 2 ? 'selected' : '' ?>>2 Orang</option>
                                <option value="1" <?= $formData['tanggungan'] === 1 ? 'selected' : '' ?>>1 Orang</option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-300 mb-1">Kondisi Fisik Tempat Tinggal / Rumah <span class="text-rose-500">*</span></label>
                            <select name="kondisi_rumah" class="w-full px-3.5 py-2.5 border border-[#1E3A5F] rounded-xl bg-[#0B192C] focus:ring-2 focus:ring-blue-500 focus:outline-none text-xs font-semibold text-white">
                                <option value="5" <?= $formData['kondisi_rumah'] === 5 ? 'selected' : '' ?>>Tidak Layak</option>
                                <option value="4" <?= $formData['kondisi_rumah'] === 4 ? 'selected' : '' ?>>Dinding Kayu</option>
                                <option value="3" <?= $formData['kondisi_rumah'] === 3 ? 'selected' : '' ?>>Dinding Batu Atap Seng</option>
                                <option value="2" <?= $formData['kondisi_rumah'] === 2 ? 'selected' : '' ?>>Dinding Batu Atap Genteng</option>
                                <option value="1" <?= $formData['kondisi_rumah'] === 1 ? 'selected' : '' ?>>Tembok Keramik</option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-300 mb-1">Prestasi Akademik Tertinggi Siswa <span class="text-rose-500">*</span></label>
                            <select name="prestasi" class="w-full px-3.5 py-2.5 border border-[#1E3A5F] rounded-xl bg-[#0B192C] focus:ring-2 focus:ring-blue-500 focus:outline-none text-xs font-semibold text-white">
                                <option value="5" <?= $formData['prestasi'] === 5 ? 'selected' : '' ?>>Juara 1 - 3 Tingkat Kabupaten</option>
                                <option value="4" <?= $formData['prestasi'] === 4 ? 'selected' : '' ?>>Juara Harapan</option>
                                <option value="3" <?= $formData['prestasi'] === 3 ? 'selected' : '' ?>>Juara Kelas 1 - 3</option>
                                <option value="2" <?= $formData['prestasi'] === 2 ? 'selected' : '' ?>>Peringkat 10 Besar</option>
                                <option value="1" <?= $formData['prestasi'] === 1 ? 'selected' : '' ?>>Peringkat 20 Besar</option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-300 mb-1">Jarak Rumah Siswa ke Sekolah <span class="text-rose-500">*</span></label>
                            <select name="jarak" class="w-full px-3.5 py-2.5 border border-[#1E3A5F] rounded-xl bg-[#0B192C] focus:ring-2 focus:ring-blue-500 focus:outline-none text-xs font-semibold text-white">
                                <option value="5" <?= $formData['jarak'] === 5 ? 'selected' : '' ?>>&gt; 5 km</option>
                                <option value="4" <?= $formData['jarak'] === 4 ? 'selected' : '' ?>>3 – 5 km</option>
                                <option value="3" <?= $formData['jarak'] === 3 ? 'selected' : '' ?>>2 km</option>
                                <option value="2" <?= $formData['jarak'] === 2 ? 'selected' : '' ?>>1 km</option>
                                <option value="1" <?= $formData['jarak'] === 1 ? 'selected' : '' ?>>&lt; 1 km</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- SUBMIT BUTTON -->
                <div class="pt-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3">
                    <a href="login_siswa.php" class="text-xs text-slate-500 hover:text-slate-800 font-semibold order-2 sm:order-1">
                        &larr; Sudah pernah mendaftar? Login di sini
                    </a>
                    <button type="submit" class="w-full sm:w-auto px-8 py-3 bg-[#162B4D] hover:bg-[#1E3A5F] text-white border border-[#2E5A8F] hover:border-blue-400 font-bold rounded-xl text-xs shadow-md transition-all duration-200 hover:scale-[1.02] active:scale-[0.98] cursor-pointer flex items-center justify-center gap-2 order-1 sm:order-2">
                        <i class="fa-solid fa-paper-plane text-xs text-blue-400"></i> Kirim Pendaftaran &amp; Buat Akun
                    </button>
                </div>

            </form>
        </div>

    </main>

    <!-- FOOTER -->
    <footer class="bg-[#112240] border-t border-[#1E3A5F] py-5 text-center text-xs text-slate-400">
        <div class="max-w-4xl mx-auto px-4 flex flex-col sm:flex-row items-center justify-between gap-2">
            <span>&copy; <?= date('Y') ?> <?= htmlspecialchars($pengaturan['nama_sekolah']) ?> &bull; Sistem SPK PIP (AHP)</span>
            <div class="flex items-center gap-3">
                <a href="login_siswa.php" class="text-blue-400 hover:underline font-semibold">Login Siswa</a>
                <span class="text-[#1E3A5F]">|</span>
                <a href="login.php" class="text-slate-400 hover:text-white">Login Guru / Admin</a>
            </div>
        </div>
    </footer>

    <script>
        function togglePin(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        function previewFotoSiswa(event) {
            const file = event.target.files[0];
            const img = document.getElementById('preview-foto-siswa');
            const placeholder = document.getElementById('placeholder-foto');
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    img.src = e.target.result;
                    img.classList.remove('hidden');
                    placeholder.classList.add('hidden');
                };
                reader.readAsDataURL(file);
            } else {
                img.classList.add('hidden');
                placeholder.classList.remove('hidden');
            }
        }
    </script>
</body>
</html>
