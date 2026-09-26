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
            // Jika sebelumnya Ditolak, kembalikan ke Menunggu Verifikasi agar diperiksa ulang
            $status_simpan = ($siswa['status_verifikasi'] === 'Ditolak') ? 'Menunggu Verifikasi' : $siswa['status_verifikasi'];

            $stmt_upd = mysqli_prepare($koneksi, "UPDATE `calon_penerima` SET 
                `nama`=?, `nama_ortu`=?, `no_hp`=?, `jenis_kelamin`=?, `sekolah_asal`=?, `alamat`=?, 
                `penghasilan`=?, `tanggungan`=?, `kondisi_rumah`=?, `prestasi`=?, `jarak`=?, 
                `status_verifikasi`=? 
                WHERE `id_siswa`=?");
            mysqli_stmt_bind_param($stmt_upd, "ssssssiiiiisi", $nama, $nama_ortu, $no_hp, $jenis_kelamin, $sekolah_asal, $alamat, $c1, $c2, $c3, $c4, $c5, $status_simpan, $id_siswa_sess);

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
        @media print {
            body { background: white !important; color: black !important; }
            .no-print { display: none !important; }
            .print-only { display: block !important; }
            .print-card { box-shadow: none !important; border: 1px solid #ccc !important; }
        }
        .print-only { display: none; }
    </style>
</head>
<body class="bg-slate-100 min-h-screen text-slate-800 font-sans flex flex-col justify-between">

    <!-- NAVBAR PENDAFTAR -->
    <header class="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-xs no-print">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 py-3 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-slate-50 border border-slate-200 p-1 flex items-center justify-center shrink-0">
                    <img src="<?= htmlspecialchars($logo_sekolah) ?>" alt="Logo Sekolah" class="max-h-full max-w-full object-contain">
                </div>
                <div>
                    <h1 class="text-sm font-extrabold text-slate-900 leading-tight">Portal Akun Pendaftar PIP</h1>
                    <p class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider"><?= htmlspecialchars($nama_sekolah) ?></p>
                </div>
            </div>

            <!-- USER INFO & LOGOUT -->
            <div class="flex items-center gap-2 sm:gap-3">
                <div class="hidden md:block text-right">
                    <div class="text-xs font-bold text-slate-900 leading-tight"><?= htmlspecialchars($siswa['nama']) ?></div>
                    <div class="text-[11px] text-slate-500 font-mono">NISN: <?= htmlspecialchars($siswa['nisn']) ?></div>
                </div>

                <button type="button" onclick="bukaModalGantiPin()" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-semibold transition-colors flex items-center gap-1.5 cursor-pointer" title="Ganti PIN Keamanan">
                    <i class="fa-solid fa-key text-xs"></i>
                    <span class="hidden sm:inline">Ganti PIN</span>
                </button>

                <a href="logout_siswa.php" onclick="return confirm('Apakah Anda yakin ingin keluar dari Akun Pendaftar?')" class="px-3.5 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-xl text-xs font-bold transition-colors flex items-center gap-1.5">
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
            <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-300 text-emerald-900 text-xs font-semibold shadow-xs flex items-start justify-between gap-3 no-print">
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
                <?= $msg_type === 'success' ? 'bg-emerald-50 border border-emerald-300 text-emerald-900' : 'bg-rose-50 border border-rose-300 text-rose-900' ?>">
                <div class="flex items-start gap-2.5">
                    <i class="fa-solid <?= $msg_type === 'success' ? 'fa-circle-check text-emerald-600' : 'fa-triangle-exclamation text-rose-500' ?> text-base mt-0.5 shrink-0"></i>
                    <div class="leading-relaxed"><?= $msg ?></div>
                </div>
                <button type="button" onclick="this.parentElement.remove()" class="text-slate-400 hover:text-slate-600 font-bold text-base leading-none">&times;</button>
            </div>
        <?php endif; ?>

        <!-- KARTU STATUS PENGAJUAN (HERO BANNER) -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 overflow-hidden">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-5 border-b border-slate-100">
                <div>
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Status Pengajuan PIP Siswa:</span>
                    <h2 class="text-xl font-extrabold text-slate-900 mt-1"><?= htmlspecialchars($siswa['nama']) ?></h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        NISN: <span class="font-mono font-bold text-slate-700"><?= htmlspecialchars($siswa['nisn']) ?></span> &bull; Asal: <?= htmlspecialchars($siswa['sekolah_asal'] ?: 'SD/MI') ?> &bull; Tingkat: <?= htmlspecialchars($siswa['kelas']) ?>
                    </p>
                </div>

                <!-- BADGE STATUS -->
                <div class="shrink-0">
                    <?php if ($siswa['status_verifikasi'] === 'Terverifikasi'): ?>
                        <div class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-100 text-emerald-800 rounded-xl text-xs font-extrabold border border-emerald-300 shadow-xs">
                            <i class="fa-solid fa-circle-check text-emerald-600 text-sm"></i>
                            <span>Terverifikasi Resmi</span>
                        </div>
                    <?php elseif ($siswa['status_verifikasi'] === 'Ditolak'): ?>
                        <div class="inline-flex items-center gap-2 px-4 py-2 bg-rose-100 text-rose-800 rounded-xl text-xs font-extrabold border border-rose-300 shadow-xs">
                            <i class="fa-solid fa-circle-xmark text-rose-600 text-sm"></i>
                            <span>Perlu Perbaikan Berkas</span>
                        </div>
                    <?php else: ?>
                        <div class="inline-flex items-center gap-2 px-4 py-2 bg-amber-100 text-amber-900 rounded-xl text-xs font-extrabold border border-amber-300 shadow-xs">
                            <i class="fa-solid fa-clock-rotate-left text-amber-600 text-sm"></i>
                            <span>Menunggu Verifikasi Berkas</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- INSTRUKSI TAHAPAN BERKAS -->
            <div class="mt-5 p-4 rounded-xl text-xs leading-relaxed
                <?php if ($siswa['status_verifikasi'] === 'Terverifikasi'): ?>
                    bg-emerald-50/80 border border-emerald-200 text-emerald-900
                <?php elseif ($siswa['status_verifikasi'] === 'Ditolak'): ?>
                    bg-rose-50/80 border border-rose-200 text-rose-900
                <?php else: ?>
                    bg-blue-50/80 border border-blue-200 text-blue-900
                <?php endif; ?>">
                
                <div class="flex items-start gap-2.5">
                    <i class="fa-solid fa-circle-info text-sm mt-0.5 shrink-0
                        <?= $siswa['status_verifikasi'] === 'Terverifikasi' ? 'text-emerald-600' : ($siswa['status_verifikasi'] === 'Ditolak' ? 'text-rose-600' : 'text-blue-600') ?>"></i>
                    <div class="space-y-1">
                        <?php if ($siswa['status_verifikasi'] === 'Terverifikasi'): ?>
                            <b class="font-bold text-xs block text-emerald-800">Berkas Telah Sah &amp; Masuk Tahap Seleksi AHP</b>
                            <span>Selamat! Berkas fisik dan data pengajuan Anda telah divalidasi oleh panitia sekolah. Data telah dikunci demi objektivitas pemeringkatan sistem SPK PIP dengan metode <em>Analytical Hierarchy Process</em>.</span>
                        <?php elseif ($siswa['status_verifikasi'] === 'Ditolak'): ?>
                            <b class="font-bold text-xs block text-rose-800">Pemberitahuan Perbaikan Berkas</b>
                            <span>Mohon maaf, berkas fisik yang Anda serahkan dinyatakan belum lengkap atau tidak sesuai kriteria. Silakan periksa kembali dan perbarui formulir di bawah ini serta temui Panitia PIP di ruang Tata Usaha.</span>
                        <?php else: ?>
                            <b class="font-bold text-xs block text-blue-800">Langkah Selanjutnya: Serahkan Berkas Fisik ke Sekolah</b>
                            <span>Data pendaftaran online Anda telah tersimpan. Segera serahkan fotokopi berkas pendukung (Fotokopi KK, KIP/KKS/PKH atau Surat Keterangan Tidak Mampu dari Desa) ke <b>Ruang Tata Usaha <?= htmlspecialchars($nama_sekolah) ?></b> untuk diverifikasi langsung oleh panitia.</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- ACTION BAR: CETAK BUKTI & EDIT BUTTON -->
            <div class="mt-5 pt-4 border-t border-slate-100 flex flex-wrap items-center justify-between gap-3 no-print">
                <button type="button" onclick="window.print()" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-bold shadow-xs transition-colors inline-flex items-center gap-2 cursor-pointer">
                    <i class="fa-solid fa-print text-xs"></i>
                    <span>Cetak Tanda Terima Pengajuan</span>
                </button>

                <div class="flex items-center gap-2 text-xs text-slate-500">
                    <?php if ($is_locked): ?>
                        <span class="inline-flex items-center gap-1.5 text-emerald-700 font-bold bg-emerald-50 px-3 py-1.5 rounded-lg border border-emerald-200">
                            <i class="fa-solid fa-lock text-xs"></i> Data Terkunci (Terverifikasi)
                        </span>
                    <?php elseif (!$is_registration_open): ?>
                        <span class="inline-flex items-center gap-1.5 text-slate-600 font-bold bg-slate-100 px-3 py-1.5 rounded-lg border border-slate-200">
                            <i class="fa-solid fa-lock text-xs"></i> Pendaftaran Telah Ditutup
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- FORM DETAIL DATA PENGAJUAN (BISA DIEDIT JIKA BELUM TERVERIFIKASI) -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-6">
                <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-sm">
                        <i class="fa-solid fa-address-card"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Rincian &amp; Formulir Pembaruan Data</h3>
                        <p class="text-[11px] text-slate-500">Kelola informasi pribadi dan kriteria sosial ekonomi pendaftar</p>
                    </div>
                </div>

                <?php if ($is_locked): ?>
                    <span class="text-[11px] text-slate-400 italic">Formulir Terkunci Resmi</span>
                <?php endif; ?>
            </div>

            <form action="portal_siswa.php" method="POST" class="space-y-6">
                <input type="hidden" name="action" value="update_mandiri">

                <!-- BAGIAN 1: BIODATA SISWA -->
                <div>
                    <h4 class="text-xs font-extrabold uppercase tracking-wider text-slate-700 mb-3 flex items-center gap-1.5">
                        <i class="fa-solid fa-user text-blue-600"></i> Biodata Siswa &amp; Wali Murid
                    </h4>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Nomor Induk Siswa Nasional (NISN)</label>
                            <input type="text" value="<?= htmlspecialchars($siswa['nisn']) ?>" disabled 
                                class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl bg-slate-100 text-slate-600 font-mono font-bold cursor-not-allowed">
                            <span class="text-[10px] text-slate-400 mt-1 block">NISN merupakan nomor identitas unik dan tidak dapat diubah.</span>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Nama Lengkap Siswa *</label>
                            <input type="text" name="nama" required value="<?= htmlspecialchars($siswa['nama']) ?>" <?= $is_locked ? 'disabled' : '' ?>
                                class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-600 focus:outline-none text-xs font-semibold text-slate-900 <?= $is_locked ? 'bg-slate-100 cursor-not-allowed' : 'bg-white' ?>">
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Sekolah Asal (SD / MI) *</label>
                            <input type="text" name="sekolah_asal" required value="<?= htmlspecialchars($siswa['sekolah_asal'] ?: 'SD/MI') ?>" <?= $is_locked ? 'disabled' : '' ?>
                                class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-600 focus:outline-none text-xs font-semibold text-slate-900 <?= $is_locked ? 'bg-slate-100 cursor-not-allowed' : 'bg-white' ?>">
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Jenis Kelamin</label>
                            <select name="jenis_kelamin" <?= $is_locked ? 'disabled' : '' ?>
                                class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-600 focus:outline-none text-xs font-semibold text-slate-900 <?= $is_locked ? 'bg-slate-100 cursor-not-allowed' : 'bg-white' ?>">
                                <option value="Laki-laki" <?= ($siswa['jenis_kelamin'] ?? '') === 'Laki-laki' ? 'selected' : '' ?>>Laki-laki</option>
                                <option value="Perempuan" <?= ($siswa['jenis_kelamin'] ?? '') === 'Perempuan' ? 'selected' : '' ?>>Perempuan</option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Nama Orang Tua / Wali *</label>
                            <input type="text" name="nama_ortu" required value="<?= htmlspecialchars($siswa['nama_ortu'] ?? '') ?>" <?= $is_locked ? 'disabled' : '' ?>
                                class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-600 focus:outline-none text-xs font-semibold text-slate-900 <?= $is_locked ? 'bg-slate-100 cursor-not-allowed' : 'bg-white' ?>">
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">No. WhatsApp / HP Wali Murid *</label>
                            <input type="tel" name="no_hp" required value="<?= htmlspecialchars($siswa['no_hp'] ?? '') ?>" <?= $is_locked ? 'disabled' : '' ?>
                                class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-600 focus:outline-none text-xs font-semibold text-slate-900 <?= $is_locked ? 'bg-slate-100 cursor-not-allowed' : 'bg-white' ?>">
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block font-bold text-slate-700 mb-1">Alamat Tempat Tinggal</label>
                            <textarea name="alamat" rows="2" <?= $is_locked ? 'disabled' : '' ?>
                                class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-600 focus:outline-none text-xs font-semibold text-slate-900 <?= $is_locked ? 'bg-slate-100 cursor-not-allowed' : 'bg-white' ?>"><?= htmlspecialchars($siswa['alamat'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- BAGIAN 2: KRITERIA SOSIAL EKONOMI -->
                <div class="pt-4 border-t border-slate-100">
                    <h4 class="text-xs font-extrabold uppercase tracking-wider text-slate-700 mb-3 flex items-center gap-1.5">
                        <i class="fa-solid fa-clipboard-list text-amber-500"></i> Kriteria Penilaian AHP
                    </h4>

                    <div class="space-y-4 text-xs">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">1. Penghasilan Rata-rata Orang Tua per Bulan *</label>
                            <?php $c1 = (int)($siswa['penghasilan'] ?? 5); ?>
                            <select name="penghasilan" <?= $is_locked ? 'disabled' : '' ?>
                                class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-600 focus:outline-none text-xs font-semibold text-slate-900 <?= $is_locked ? 'bg-slate-100 cursor-not-allowed' : 'bg-white' ?>">
                                <option value="5" <?= $c1 === 5 ? 'selected' : '' ?>>&lt; Rp 500.000</option>
                                <option value="4" <?= $c1 === 4 ? 'selected' : '' ?>>Rp 600.000 - Rp 1.000.000</option>
                                <option value="3" <?= $c1 === 3 ? 'selected' : '' ?>>Rp 1.000.000 - Rp 2.000.000</option>
                                <option value="2" <?= $c1 === 2 ? 'selected' : '' ?>>Rp 2.000.000 - Rp 3.000.000</option>
                                <option value="1" <?= $c1 === 1 ? 'selected' : '' ?>>&gt; Rp 4.000.000</option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">2. Jumlah Anggota Keluarga yang Ditanggung *</label>
                            <?php $c2 = (int)($siswa['tanggungan'] ?? 5); ?>
                            <select name="tanggungan" <?= $is_locked ? 'disabled' : '' ?>
                                class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-600 focus:outline-none text-xs font-semibold text-slate-900 <?= $is_locked ? 'bg-slate-100 cursor-not-allowed' : 'bg-white' ?>">
                                <option value="5" <?= $c2 === 5 ? 'selected' : '' ?>>&gt; 5 Orang</option>
                                <option value="4" <?= $c2 === 4 ? 'selected' : '' ?>>4 Orang</option>
                                <option value="3" <?= $c2 === 3 ? 'selected' : '' ?>>3 Orang</option>
                                <option value="2" <?= $c2 === 2 ? 'selected' : '' ?>>2 Orang</option>
                                <option value="1" <?= $c2 === 1 ? 'selected' : '' ?>>1 Orang</option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">3. Kondisi Fisik Tempat Tinggal / Rumah *</label>
                            <?php $c3 = (int)($siswa['kondisi_rumah'] ?? 5); ?>
                            <select name="kondisi_rumah" <?= $is_locked ? 'disabled' : '' ?>
                                class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-600 focus:outline-none text-xs font-semibold text-slate-900 <?= $is_locked ? 'bg-slate-100 cursor-not-allowed' : 'bg-white' ?>">
                                <option value="5" <?= $c3 === 5 ? 'selected' : '' ?>>Tidak Layak</option>
                                <option value="4" <?= $c3 === 4 ? 'selected' : '' ?>>Dinding Kayu</option>
                                <option value="3" <?= $c3 === 3 ? 'selected' : '' ?>>Dinding Batu Atap Seng</option>
                                <option value="2" <?= $c3 === 2 ? 'selected' : '' ?>>Dinding Batu Atap Genteng</option>
                                <option value="1" <?= $c3 === 1 ? 'selected' : '' ?>>Tembok Keramik</option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">4. Prestasi Akademik Tertinggi Siswa *</label>
                            <?php $c4 = (int)($siswa['prestasi'] ?? 3); ?>
                            <select name="prestasi" <?= $is_locked ? 'disabled' : '' ?>
                                class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-600 focus:outline-none text-xs font-semibold text-slate-900 <?= $is_locked ? 'bg-slate-100 cursor-not-allowed' : 'bg-white' ?>">
                                <option value="5" <?= $c4 === 5 ? 'selected' : '' ?>>Juara 1 - 3 Tingkat Kabupaten</option>
                                <option value="4" <?= $c4 === 4 ? 'selected' : '' ?>>Juara Harapan</option>
                                <option value="3" <?= $c4 === 3 ? 'selected' : '' ?>>Juara Kelas 1 - 3</option>
                                <option value="2" <?= $c4 === 2 ? 'selected' : '' ?>>Peringkat 10 Besar</option>
                                <option value="1" <?= $c4 === 1 ? 'selected' : '' ?>>Peringkat 20 Besar</option>
                            </select>
                        </div>

                        <div>
                            <label class="block font-bold text-slate-700 mb-1">5. Jarak Rumah Siswa ke Sekolah *</label>
                            <?php $c5 = (int)($siswa['jarak'] ?? 4); ?>
                            <select name="jarak" <?= $is_locked ? 'disabled' : '' ?>
                                class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-600 focus:outline-none text-xs font-semibold text-slate-900 <?= $is_locked ? 'bg-slate-100 cursor-not-allowed' : 'bg-white' ?>">
                                <option value="5" <?= $c5 === 5 ? 'selected' : '' ?>>&gt; 5 km</option>
                                <option value="4" <?= $c5 === 4 ? 'selected' : '' ?>>3 – 5 km</option>
                                <option value="3" <?= $c5 === 3 ? 'selected' : '' ?>>2 km</option>
                                <option value="2" <?= $c5 === 2 ? 'selected' : '' ?>>1 km</option>
                                <option value="1" <?= $c5 === 1 ? 'selected' : '' ?>>&lt; 1 km</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- SUBMIT PERUBAHAN -->
                <div class="pt-4 border-t border-slate-100 flex items-center justify-between no-print">
                    <?php if ($is_locked): ?>
                        <div class="w-full p-3 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-xs flex items-center gap-2">
                            <i class="fa-solid fa-lock text-emerald-600"></i>
                            <span>Data Anda telah resmi diverifikasi oleh panitia sekolah dan dikunci demi menjaga objektivitas pemeringkatan AHP.</span>
                        </div>
                    <?php elseif (!$is_registration_open): ?>
                        <div class="w-full p-3 bg-slate-100 border border-slate-200 text-slate-600 rounded-xl text-xs flex items-center gap-2">
                            <i class="fa-solid fa-calendar-xmark text-slate-500"></i>
                            <span>Periode pendaftaran telah ditutup, pembaruan data telah berakhir.</span>
                        </div>
                    <?php else: ?>
                        <span class="text-xs text-slate-500">Pastikan data yang diperbarui sudah sesuai dengan dokumen fisik.</span>
                        <button type="submit" class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl text-xs shadow-md transition-all cursor-pointer flex items-center gap-2">
                            <i class="fa-solid fa-floppy-disk text-xs"></i>
                            <span>Simpan Pembaruan Data</span>
                        </button>
                    <?php endif; ?>
                </div>

            </form>
        </div>

    </main>

    <!-- MODAL GANTI PIN -->
    <div id="modal-ganti-pin" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 hidden no-print">
        <div class="bg-white w-full max-w-md rounded-2xl shadow-2xl border border-slate-200 p-6 space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center text-sm">
                        <i class="fa-solid fa-key"></i>
                    </div>
                    <h3 class="font-bold text-slate-900 text-sm">Ganti PIN Keamanan Akun</h3>
                </div>
                <button type="button" onclick="tutupModalGantiPin()" class="text-slate-400 hover:text-slate-600 font-bold text-lg leading-none cursor-pointer">&times;</button>
            </div>

            <p class="text-xs text-slate-600 leading-relaxed">
                PIN ini digunakan untuk login ke portal akun pendaftar agar data anak Anda terlindungi dari pihak lain.
            </p>

            <form action="portal_siswa.php" method="POST" class="space-y-3.5 text-xs">
                <input type="hidden" name="action" value="ganti_pin">

                <div>
                    <label class="block font-bold text-slate-700 mb-1">PIN Lama <span class="text-rose-500">*</span></label>
                    <input type="password" name="pin_lama" required placeholder="Masukkan PIN saat ini"
                        class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-600 focus:outline-none font-semibold text-slate-900">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">PIN Baru (4 - 6 digit) <span class="text-rose-500">*</span></label>
                    <input type="password" name="pin_baru" required maxlength="10" placeholder="Ketik PIN baru"
                        class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-600 focus:outline-none font-semibold text-slate-900">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Konfirmasi PIN Baru <span class="text-rose-500">*</span></label>
                    <input type="password" name="pin_baru_konf" required maxlength="10" placeholder="Ulangi PIN baru"
                        class="w-full px-3.5 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-600 focus:outline-none font-semibold text-slate-900">
                </div>

                <div class="pt-2 flex items-center justify-end gap-2.5">
                    <button type="button" onclick="tutupModalGantiPin()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl font-semibold transition-colors cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold shadow-md transition-all cursor-pointer">
                        Simpan PIN Baru
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- TAMPILAN CETAK BUKTI PENDAFTARAN RESMI (PRINT ONLY) -->
    <div class="print-only p-8 text-black text-xs leading-relaxed max-w-2xl mx-auto">
        <div class="text-center pb-4 border-b-2 border-black mb-6">
            <h2 class="text-base font-extrabold uppercase"><?= htmlspecialchars($pengaturan['nama_yayasan'] ?? 'YAYASAN AL QODIRI LAMPUNG') ?></h2>
            <h1 class="text-lg font-black uppercase"><?= htmlspecialchars($nama_sekolah) ?></h1>
            <p class="text-[11px]"><?= htmlspecialchars($pengaturan['sub_instansi'] ?? 'KABUPATEN LAMPUNG TENGAH') ?></p>
            <p class="text-[10px] mt-0.5">Tanda Terima &amp; Bukti Pendaftaran Calon Penerima Bantuan PIP (Metode AHP) &bull; T.A. <?= htmlspecialchars($pengaturan['tahun_ajaran']) ?></p>
        </div>

        <div class="mb-4">
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
    <footer class="bg-white border-t border-slate-200 py-5 text-center text-xs text-slate-400 no-print">
        <div class="max-w-5xl mx-auto px-4 flex flex-col sm:flex-row items-center justify-between gap-2">
            <span>&copy; <?= date('Y') ?> <?= htmlspecialchars($nama_sekolah) ?> &bull; Sistem SPK PIP (Metode AHP)</span>
            <div class="flex items-center gap-3">
                <a href="logout_siswa.php" class="text-rose-600 hover:underline font-semibold">Logout Pendaftar</a>
                <span class="text-slate-300">|</span>
                <a href="login.php" class="text-slate-500 hover:text-slate-700">Login Administrator</a>
            </div>
        </div>
    </footer>

    <script>
        function bukaModalGantiPin() {
            document.getElementById('modal-ganti-pin').classList.remove('hidden');
        }
        function tutupModalGantiPin() {
            document.getElementById('modal-ganti-pin').classList.add('hidden');
        }
    </script>
</body>
</html>

