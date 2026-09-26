<?php
// daftar_siswa.php - Formulir Pendaftaran Akun & Pengajuan PIP Mandiri oleh Orang Tua / Siswa
require_once "koneksi.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Pastikan kolom pin ada di tabel calon_penerima
$cek_pin = mysqli_query($koneksi, "SHOW COLUMNS FROM `calon_penerima` LIKE 'pin'");
if (mysqli_num_rows($cek_pin) == 0) {
    @mysqli_query($koneksi, "ALTER TABLE `calon_penerima` ADD COLUMN `pin` VARCHAR(255) NULL AFTER `no_hp`");
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
            // Hash PIN pendaftar
            $hashed_pin = password_hash($pin, PASSWORD_DEFAULT);
            $tahun_pengajuan = $pengaturan['tahun_ajaran'] ?? '2025/2026';
            $kelas = 'Kelas VII';

            $stmt_ins = mysqli_prepare($koneksi, "INSERT INTO `calon_penerima` 
                (`nisn`, `nama`, `nama_ortu`, `no_hp`, `pin`, `jenis_kelamin`, `kelas`, `sekolah_asal`, `alamat`, `penghasilan`, `tanggungan`, `kondisi_rumah`, `prestasi`, `jarak`, `status_verifikasi`, `tahun`) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Menunggu Verifikasi', ?)");

            mysqli_stmt_bind_param(
                $stmt_ins, 
                "sssssssssiiiiis", 
                $formData['nisn'], 
                $formData['nama'], 
                $formData['nama_ortu'], 
                $formData['no_hp'], 
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
<body class="bg-slate-100 min-h-screen text-slate-800 font-sans flex flex-col justify-between">

    <!-- HEADER / NAVBAR -->
    <header class="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-xs">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 py-3.5 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-slate-50 border border-slate-200 p-1 flex items-center justify-center shrink-0">
                    <img src="<?= htmlspecialchars($logo_sekolah) ?>" alt="Logo Sekolah" class="max-h-full max-w-full object-contain">
                </div>
                <div>
                    <h1 class="text-sm font-extrabold text-slate-900 leading-tight">Pendaftaran Pengajuan PIP</h1>
                    <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider"><?= htmlspecialchars($pengaturan['nama_sekolah']) ?></p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="login_siswa.php" class="px-3.5 py-1.5 bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 rounded-xl text-xs font-bold transition-colors inline-flex items-center gap-1.5">
                    <i class="fa-solid fa-right-to-bracket text-xs"></i>
                    <span>Login Pendaftar</span>
                </a>
                <a href="login.php" class="hidden sm:inline-flex px-3 py-1.5 text-slate-500 hover:text-slate-800 text-xs font-semibold transition-colors items-center gap-1">
                    <i class="fa-solid fa-user-shield text-[11px]"></i> Login Admin
                </a>
            </div>
        </div>
    </header>

    <!-- KONTEN UTAMA -->
    <main class="max-w-4xl w-full mx-auto px-4 sm:px-6 py-8 flex-1">

        <!-- KARTU INFORMASI JADWAL -->
        <div class="mb-6 p-4 rounded-2xl border text-xs flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-xs
            <?= $is_registration_open ? 'bg-emerald-50 border-emerald-200 text-emerald-900' : 'bg-rose-50 border-rose-200 text-rose-900' ?>">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl flex items-center justify-center text-base shrink-0
                    <?= $is_registration_open ? 'bg-emerald-500 text-white' : 'bg-rose-500 text-white' ?>">
                    <i class="fa-solid <?= $is_registration_open ? 'fa-calendar-check' : 'fa-calendar-xmark' ?>"></i>
                </div>
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
            <div>
                <a href="login_siswa.php" class="px-3.5 py-1.5 bg-blue-50 hover:bg-blue-100 text-blue-700 border border-blue-200 rounded-xl text-xs font-bold transition-colors inline-flex items-center gap-1.5 shadow-xs">
                    <i class="fa-solid fa-right-to-bracket text-xs"></i>
                    <span>Sudah punya akun? Login di sini</span>
                </a>
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
        <div class="bg-white rounded-2xl border border-slate-200 shadow-xl overflow-hidden">
            
            <div class="p-6 bg-gradient-to-r from-slate-900 to-blue-900 text-white">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-2xl bg-white/10 backdrop-blur-xs flex items-center justify-center text-xl text-blue-300">
                        <i class="fa-solid fa-user-plus"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold">Formulir Pendaftaran Akun Pendaftar PIP</h2>
                        <p class="text-xs text-blue-200 mt-0.5">
                            Isi biodata anak, buat PIN keamanan akun Anda, dan lengkapi kriteria sosial ekonomi.
                        </p>
                    </div>
                </div>
            </div>

            <form action="daftar_siswa.php" method="POST" class="p-6 sm:p-8 space-y-6">

                <!-- BAGIAN 1: KEAMANAN AKUN (NISN & PIN) -->
                <div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                        <div class="sm:col-span-2">
                            <label class="block font-bold text-slate-700 mb-1">
                                Nomor Induk Siswa Nasional (NISN) <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" name="nisn" id="input-nisn" required maxlength="20"
                                value="<?= htmlspecialchars($formData['nisn']) ?>"
                                placeholder="Contoh: 0081234567"
                                class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-600 focus:outline-none text-xs font-bold text-slate-900 bg-white">
                            <span class="text-[10px] text-slate-400 mt-1 block">NISN dapat dilihat pada Kartu Pelajar, Rapor SD/MI, atau Ijazah.</span>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">
                                Buat PIN Keamanan Akun <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative flex items-center">
                                <input type="password" name="pin" id="input-pin" required maxlength="10" placeholder="Minimal 4 - 6 digit angka (misal: 123456)"
                                    class="w-full pl-3.5 pr-11 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-600 focus:outline-none text-xs font-semibold text-slate-900 bg-white">
                                <button type="button" onclick="togglePin('input-pin', 'icon-eye-1')" class="absolute right-0 inset-y-0 px-3.5 flex items-center text-slate-400 hover:text-slate-700 cursor-pointer select-none" title="Lihat/Sembunyikan PIN">
                                    <i class="fa-solid fa-eye text-sm" id="icon-eye-1"></i>
                                </button>
                            </div>
                            <span class="text-[10px] text-slate-400 mt-1 block">Buat PIN yang mudah Anda ingat, contoh: 6 digit tanggal lahir.</span>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">
                                Ulangi Konfirmasi PIN <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative flex items-center">
                                <input type="password" name="pin_konfirmasi" id="input-pin-confirm" required maxlength="10" placeholder="Ketik ulang PIN yang sama"
                                    class="w-full pl-3.5 pr-11 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-600 focus:outline-none text-xs font-semibold text-slate-900 bg-white">
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
                    <div class="flex items-center gap-2 pb-2.5 border-b border-slate-100 mb-4">
                        <div class="w-6 h-6 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-xs font-bold">2</div>
                        <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-800">
                            Identitas Siswa &amp; Kontak Wali Murid
                        </h3>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Nama Lengkap Siswa <span class="text-rose-500">*</span></label>
                            <input type="text" name="nama" required value="<?= htmlspecialchars($formData['nama']) ?>" placeholder="Nama sesuai rapor / ijazah"
                                class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-600 focus:outline-none text-xs font-semibold text-slate-900 bg-white">
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Sekolah Asal (SD / MI) <span class="text-rose-500">*</span></label>
                            <input type="text" name="sekolah_asal" required value="<?= htmlspecialchars($formData['sekolah_asal']) ?>" placeholder="Contoh: SDN 1 Bandar Mataram"
                                class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-600 focus:outline-none text-xs font-semibold text-slate-900 bg-white">
                            <span class="text-[10px] text-slate-400">Pendaftar adalah calon siswa baru (Kelas VII).</span>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Jenis Kelamin <span class="text-rose-500">*</span></label>
                            <select name="jenis_kelamin" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-600 focus:outline-none text-xs font-semibold text-slate-900 bg-white">
                                <option value="Laki-laki" <?= $formData['jenis_kelamin'] === 'Laki-laki' ? 'selected' : '' ?>>Laki-laki</option>
                                <option value="Perempuan" <?= $formData['jenis_kelamin'] === 'Perempuan' ? 'selected' : '' ?>>Perempuan</option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Nama Orang Tua / Wali <span class="text-rose-500">*</span></label>
                            <input type="text" name="nama_ortu" required value="<?= htmlspecialchars($formData['nama_ortu']) ?>" placeholder="Nama Ayah / Ibu / Wali"
                                class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-600 focus:outline-none text-xs font-semibold text-slate-900 bg-white">
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block font-bold text-slate-700 mb-1">No. WhatsApp / HP Aktif <span class="text-rose-500">*</span></label>
                            <input type="tel" name="no_hp" required value="<?= htmlspecialchars($formData['no_hp']) ?>" placeholder="Contoh: 081234567890"
                                class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-600 focus:outline-none text-xs font-semibold text-slate-900 bg-white">
                            <span class="text-[10px] text-slate-400">Nomor aktif untuk pengumuman verifikasi berkas dari pihak sekolah.</span>
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block font-bold text-slate-700 mb-1">Alamat Tempat Tinggal</label>
                            <textarea name="alamat" rows="2" placeholder="Contoh: Dusun I RT 02 / RW 01, Desa Bandar Mataram"
                                class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-600 focus:outline-none text-xs font-semibold text-slate-900 bg-white"><?= htmlspecialchars($formData['alamat']) ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- BAGIAN 3: KRITERIA SOSIAL EKONOMI (SPK AHP) -->
                <div>
                    <div class="flex items-center gap-2 pb-2.5 border-b border-slate-100 mb-4">
                        <div class="w-6 h-6 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-xs font-bold">3</div>
                        <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-800">
                            Kondisi Sosial Ekonomi &amp; Kriteria Penilaian AHP
                        </h3>
                    </div>

                    <div class="space-y-4 text-xs">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">1. Penghasilan Rata-rata Orang Tua per Bulan <span class="text-rose-500">*</span></label>
                            <select name="penghasilan" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl bg-white focus:ring-2 focus:ring-blue-600 focus:outline-none text-xs font-semibold text-slate-900">
                                <option value="5" <?= $formData['penghasilan'] === 5 ? 'selected' : '' ?>>&lt; Rp 500.000</option>
                                <option value="4" <?= $formData['penghasilan'] === 4 ? 'selected' : '' ?>>Rp 600.000 - Rp 1.000.000</option>
                                <option value="3" <?= $formData['penghasilan'] === 3 ? 'selected' : '' ?>>Rp 1.000.000 - Rp 2.000.000</option>
                                <option value="2" <?= $formData['penghasilan'] === 2 ? 'selected' : '' ?>>Rp 2.000.000 - Rp 3.000.000</option>
                                <option value="1" <?= $formData['penghasilan'] === 1 ? 'selected' : '' ?>>&gt; Rp 4.000.000</option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">2. Jumlah Anggota Keluarga yang Ditanggung <span class="text-rose-500">*</span></label>
                            <select name="tanggungan" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl bg-white focus:ring-2 focus:ring-blue-600 focus:outline-none text-xs font-semibold text-slate-900">
                                <option value="5" <?= $formData['tanggungan'] === 5 ? 'selected' : '' ?>>&gt; 5 Orang</option>
                                <option value="4" <?= $formData['tanggungan'] === 4 ? 'selected' : '' ?>>4 Orang</option>
                                <option value="3" <?= $formData['tanggungan'] === 3 ? 'selected' : '' ?>>3 Orang</option>
                                <option value="2" <?= $formData['tanggungan'] === 2 ? 'selected' : '' ?>>2 Orang</option>
                                <option value="1" <?= $formData['tanggungan'] === 1 ? 'selected' : '' ?>>1 Orang</option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">3. Kondisi Fisik Tempat Tinggal / Rumah <span class="text-rose-500">*</span></label>
                            <select name="kondisi_rumah" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl bg-white focus:ring-2 focus:ring-blue-600 focus:outline-none text-xs font-semibold text-slate-900">
                                <option value="5" <?= $formData['kondisi_rumah'] === 5 ? 'selected' : '' ?>>Tidak Layak</option>
                                <option value="4" <?= $formData['kondisi_rumah'] === 4 ? 'selected' : '' ?>>Dinding Kayu</option>
                                <option value="3" <?= $formData['kondisi_rumah'] === 3 ? 'selected' : '' ?>>Dinding Batu Atap Seng</option>
                                <option value="2" <?= $formData['kondisi_rumah'] === 2 ? 'selected' : '' ?>>Dinding Batu Atap Genteng</option>
                                <option value="1" <?= $formData['kondisi_rumah'] === 1 ? 'selected' : '' ?>>Tembok Keramik</option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">4. Prestasi Akademik Tertinggi Siswa <span class="text-rose-500">*</span></label>
                            <select name="prestasi" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl bg-white focus:ring-2 focus:ring-blue-600 focus:outline-none text-xs font-semibold text-slate-900">
                                <option value="5" <?= $formData['prestasi'] === 5 ? 'selected' : '' ?>>Juara 1 - 3 Tingkat Kabupaten</option>
                                <option value="4" <?= $formData['prestasi'] === 4 ? 'selected' : '' ?>>Juara Harapan</option>
                                <option value="3" <?= $formData['prestasi'] === 3 ? 'selected' : '' ?>>Juara Kelas 1 - 3</option>
                                <option value="2" <?= $formData['prestasi'] === 2 ? 'selected' : '' ?>>Peringkat 10 Besar</option>
                                <option value="1" <?= $formData['prestasi'] === 1 ? 'selected' : '' ?>>Peringkat 20 Besar</option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">5. Jarak Rumah Siswa ke Sekolah <span class="text-rose-500">*</span></label>
                            <select name="jarak" class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl bg-white focus:ring-2 focus:ring-blue-600 focus:outline-none text-xs font-semibold text-slate-900">
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
                    <button type="submit" class="w-full sm:w-auto px-8 py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl text-xs shadow-lg hover:shadow-xl transition-all cursor-pointer flex items-center justify-center gap-2 order-1 sm:order-2">
                        <i class="fa-solid fa-paper-plane text-xs"></i> Kirim Pendaftaran &amp; Buat Akun
                    </button>
                </div>

            </form>
        </div>

    </main>

    <!-- FOOTER -->
    <footer class="bg-white border-t border-slate-200 py-5 text-center text-xs text-slate-400">
        <div class="max-w-4xl mx-auto px-4 flex flex-col sm:flex-row items-center justify-between gap-2">
            <span>&copy; <?= date('Y') ?> <?= htmlspecialchars($pengaturan['nama_sekolah']) ?> &bull; Sistem SPK PIP (AHP)</span>
            <div class="flex items-center gap-3">
                <a href="login_siswa.php" class="text-blue-600 hover:underline font-semibold">Login Siswa</a>
                <span class="text-slate-300">|</span>
                <a href="login.php" class="text-slate-500 hover:text-slate-700">Login Guru / Admin</a>
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
    </script>
</body>
</html>

