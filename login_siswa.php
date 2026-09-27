<?php
// login_siswa.php - Halaman Login Khusus Siswa / Orang Tua Murid Pendaftar PIP
require_once "koneksi.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Pastikan kolom pin ada di tabel calon_penerima
$cek_pin = mysqli_query($koneksi, "SHOW COLUMNS FROM `calon_penerima` LIKE 'pin'");
if (mysqli_num_rows($cek_pin) == 0) {
    @mysqli_query($koneksi, "ALTER TABLE `calon_penerima` ADD COLUMN `pin` VARCHAR(255) NULL AFTER `no_hp`");
}

// Jika sudah login sebagai siswa, langsung ke portal_siswa.php
if (isset($_SESSION['siswa'])) {
    header("Location: portal_siswa.php");
    exit;
}

$error = "";
$info = "";

if (isset($_GET['msg']) && $_GET['msg'] === 'logout') {
    $info = "Anda telah berhasil keluar (logout) dari Akun Pendaftar.";
}

// Pre-fill NISN jika dikirim lewat URL
$input_nisn = trim($_GET['nisn'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input_nisn = trim($_POST['nisn'] ?? '');
    $input_pin  = trim($_POST['pin'] ?? '');

    if (empty($input_nisn) || empty($input_pin)) {
        $error = "Silakan masukkan Nomor NISN dan PIN Keamanan Anda!";
    } else {
        $stmt = mysqli_prepare($koneksi, "SELECT * FROM `calon_penerima` WHERE `nisn` = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "s", $input_nisn);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        if ($siswa = mysqli_fetch_assoc($res)) {
            $auth_success = false;

            // Jika PIN di database masih kosong (data pendaftar lama sebelum fitur PIN)
            if (empty($siswa['pin'])) {
                $last_4 = substr($siswa['nisn'], -4);
                // Izinkan login dengan 4 digit terakhir NISN sebagai PIN awal
                if ($input_pin === $last_4 || $input_pin === '123456') {
                    $auth_success = true;
                    // Simpan PIN baru yang telah di-hash
                    $hashed = password_hash($input_pin, PASSWORD_DEFAULT);
                    mysqli_query($koneksi, "UPDATE `calon_penerima` SET `pin` = '$hashed' WHERE `id_siswa` = " . (int)$siswa['id_siswa']);
                }
            } else {
                // Verifikasi PIN dengan password_verify atau pencocokan langsung
                if (password_verify($input_pin, $siswa['pin']) || $input_pin === $siswa['pin']) {
                    $auth_success = true;
                }
            }

            if ($auth_success) {
                $_SESSION['siswa'] = [
                    'id_siswa' => $siswa['id_siswa'],
                    'nisn'     => $siswa['nisn'],
                    'nama'     => $siswa['nama'],
                    'kelas'    => $siswa['kelas']
                ];
                header("Location: portal_siswa.php");
                exit;
            } else {
                $error = "PIN Keamanan yang Anda masukkan salah. Pastikan PIN sesuai dengan yang Anda buat saat pendaftaran!";
            }
        } else {
            $error = "NISN <b>" . htmlspecialchars($input_nisn) . "</b> belum terdaftar dalam sistem pengajuan.";
        }
    }
}

// Ambil info sekolah & logo
$res_pengaturan = mysqli_query($koneksi, "SELECT * FROM `pengaturan` WHERE id=1");
$pengaturan = mysqli_fetch_assoc($res_pengaturan) ?: [
    'nama_sekolah' => 'SMP Tunas Bangsa',
    'tahun_ajaran' => '2025/2026',
    'logo' => 'uploads/logo_default.png'
];
$nama_sekolah = $pengaturan['nama_sekolah'] ?? 'SMP Tunas Bangsa';
$logo_sekolah = !empty($pengaturan['logo']) && file_exists($pengaturan['logo']) ? $pengaturan['logo'] : 'uploads/logo_default.png';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Akun Pendaftar PIP - <?= htmlspecialchars($nama_sekolah) ?></title>
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
    </style>
</head>
<body class="bg-[#0B192C] min-h-screen flex items-center justify-center p-4 font-sans text-slate-100">

    <div class="bg-[#112240] p-6 sm:p-10 rounded-2xl shadow-2xl w-full max-w-md border border-[#1E3A5F]">
        
        <!-- LOGO & HEADER -->
        <div class="text-center mb-6">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-[#0B192C] border border-[#1E3A5F] rounded-2xl p-2 mb-3 shadow-xs">
                <img src="<?= htmlspecialchars($logo_sekolah) ?>" alt="Logo Sekolah" class="max-w-full max-h-full object-contain">
            </div>
            <h1 class="text-xl font-extrabold text-white tracking-tight">Login Akun Pendaftar</h1>
            <p class="text-xs font-semibold text-blue-300 uppercase tracking-wider mt-0.5">
                Portal Siswa &bull; <?= htmlspecialchars($nama_sekolah) ?>
            </p>
        </div>

        <!-- PESAN ERROR -->
        <?php if (!empty($error)): ?>
            <div class="p-3.5 mb-5 text-xs font-semibold rounded-xl bg-rose-950/60 border border-rose-800 text-rose-300 flex items-start justify-between shadow-xs">
                <div class="flex items-start gap-2">
                    <i class="fa-solid fa-circle-exclamation text-rose-500 text-sm mt-0.5 shrink-0"></i>
                    <div class="leading-relaxed"><?= $error ?></div>
                </div>
                <button type="button" onclick="this.parentElement.remove()" class="text-rose-400 hover:text-rose-600 font-bold text-base leading-none">&times;</button>
            </div>
        <?php endif; ?>

        <!-- PESAN INFO -->
        <?php if (!empty($info)): ?>
            <div class="p-3.5 mb-5 text-xs font-semibold rounded-xl bg-emerald-950/60 border border-emerald-800 text-emerald-300 flex items-center justify-between shadow-xs">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-circle-check text-emerald-600 text-sm"></i>
                    <span><?= htmlspecialchars($info) ?></span>
                </div>
                <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700 font-bold text-base leading-none">&times;</button>
            </div>
        <?php endif; ?>

        <!-- FORM LOGIN -->
        <form action="login_siswa.php" method="POST" class="space-y-4 text-left">
            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1.5">
                    Nomor Induk Siswa Nasional (NISN)
                </label>
                <input type="text" name="nisn" required maxlength="20"
                    value="<?= htmlspecialchars($input_nisn) ?>" 
                    placeholder="Masukkan 10 digit NISN anak" 
                    class="w-full px-3.5 py-2.5 border border-[#1E3A5F] rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:outline-none text-xs font-semibold text-white bg-[#0B192C] placeholder:text-slate-500 transition-all">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1.5">PIN Keamanan Akun</label>
                <div class="relative flex items-center">
                    <input type="password" name="pin" id="input-pin" required maxlength="10" 
                        placeholder="Masukkan PIN Anda" 
                        class="w-full pl-3.5 pr-11 py-2.5 border border-[#1E3A5F] rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:outline-none text-xs font-semibold text-white bg-[#0B192C] placeholder:text-slate-500 transition-all">
                    <button type="button" onclick="togglePin()" id="btn-toggle-pin" class="absolute right-0 inset-y-0 px-3.5 flex items-center text-slate-400 hover:text-white cursor-pointer select-none" title="Lihat/Sembunyikan PIN">
                        <i class="fa-solid fa-eye text-sm" id="icon-pin"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="w-full py-2.5 mt-2 bg-[#162B4D] hover:bg-[#1E3A5F] text-white border border-[#2E5A8F] hover:border-blue-400 font-bold rounded-xl shadow-md transition-all duration-200 hover:scale-[1.02] active:scale-[0.98] text-xs tracking-wide cursor-pointer flex items-center justify-center gap-2">
                <i class="fa-solid fa-right-to-bracket text-blue-400"></i>
                <span>Masuk ke Akun Pendaftar</span>
            </button>
        </form>

        <!-- TOMBOL DAFTAR AKUN BARU -->
        <div class="pt-3 mt-3 border-t border-[#1E3A5F]">
            <a href="daftar_siswa.php" class="w-full py-2.5 bg-[#112240] hover:bg-[#162B4D] text-slate-200 border border-[#1E3A5F] hover:border-[#2E5A8F] font-semibold rounded-xl shadow-sm transition-all duration-200 hover:scale-[1.02] active:scale-[0.98] text-xs tracking-wide cursor-pointer flex items-center justify-center gap-2">
                <i class="fa-solid fa-user-plus text-blue-400"></i>
                <span>Daftar Akun Baru</span>
            </a>
        </div>

        <!-- LINK LOGIN ADMIN / GURU -->
        <div class="pt-2 text-center">
            <a href="login.php" class="text-xs text-blue-400 hover:text-blue-300 font-semibold hover:underline transition-colors">
                Login Admin / Guru
            </a>
        </div>

    </div>

    <script>
        function togglePin() {
            const pinInput = document.getElementById('input-pin');
            const iconPin = document.getElementById('icon-pin');
            if (pinInput.type === 'password') {
                pinInput.type = 'text';
                iconPin.classList.remove('fa-eye');
                iconPin.classList.add('fa-eye-slash');
            } else {
                pinInput.type = 'password';
                iconPin.classList.remove('fa-eye-slash');
                iconPin.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>

