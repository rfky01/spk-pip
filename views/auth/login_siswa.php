<?php
// login_siswa.php - Halaman Login Khusus Siswa / Orang Tua Murid Pendaftar PIP
require_once __DIR__ . '/../../koneksi.php';
enforce_hashed_url();

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
    header("Location: index.php?v=cG9ydGFsX3Npc3dh");
    exit;
}

$error = "";
$info = "";

if (isset($_GET['msg']) && $_GET['msg'] === 'logout') {
    $info = "Anda telah berhasil keluar (logout) dari Akun Siswa.";
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
                header("Location: index.php?v=cG9ydGFsX3Npc3dh");
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
    <title>Login Akun Siswa PIP - <?= htmlspecialchars($nama_sekolah) ?></title>
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

        /* Pertahankan tampilan gelap elegan saat browser melakukan autofill akun/password */
        input:-webkit-autofill,
        input:-webkit-autofill:hover, 
        input:-webkit-autofill:focus, 
        input:-webkit-autofill:active {
            -webkit-box-shadow: 0 0 0 1000px #FFFFFF inset !important;
            -webkit-text-fill-color: #0F172A !important;
            caret-color: #0F172A !important;
            transition: background-color 5000s ease-in-out 0s;
        }

        /* Styling dan animasi sinkron tombol toggle PIN/Password */
        .btn-eye-toggle {
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .btn-eye-toggle:hover {
            color: #2563EB !important;
            background-color: #F1F5F9 !important;
        }
    </style>
</head>
<body class="bg-slate-50 min-h-screen flex items-center justify-center p-4 font-sans text-slate-800">

    <div class="bg-white p-6 sm:p-10 rounded-2xl shadow-2xl w-full max-w-md border border-slate-200">
        
        <!-- LOGO & HEADER -->
        <div class="text-center mb-6">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-slate-50 border border-slate-200 rounded-2xl p-2 mb-3 shadow-xs">
                <img src="<?= htmlspecialchars($logo_sekolah) ?><?= file_exists($logo_sekolah) ? '?v=' . filemtime($logo_sekolah) : '' ?>" alt="Logo Sekolah" class="max-w-full max-h-full object-contain">
            </div>
            <h1 class="text-xl font-extrabold text-slate-800 tracking-tight">Login Akun Siswa</h1>
            <p class="text-xs font-semibold text-blue-600 uppercase tracking-wider mt-0.5">
                Login Siswa &bull; <?= htmlspecialchars($nama_sekolah) ?>
            </p>
        </div>

        <!-- PESAN ERROR -->
        <?php if (!empty($error)): ?>
            <div class="p-3.5 mb-5 text-xs font-semibold rounded-xl bg-rose-50 border border-rose-200 text-rose-700 flex items-start justify-between shadow-xs">
                <div class="flex items-start gap-2">
                    <i class="fa-solid fa-circle-exclamation text-rose-500 text-sm mt-0.5 shrink-0"></i>
                    <div class="leading-relaxed"><?= $error ?></div>
                </div>
                <button type="button" onclick="this.parentElement.remove()" class="text-rose-400 hover:text-rose-600 font-bold text-base leading-none">&times;</button>
            </div>
        <?php endif; ?>

        <!-- PESAN INFO -->
        <?php if (!empty($info)): ?>
            <div class="p-3.5 mb-5 text-xs font-semibold rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 flex items-center justify-between shadow-xs">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-circle-check text-emerald-600 text-sm"></i>
                    <span><?= htmlspecialchars($info) ?></span>
                </div>
                <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700 font-bold text-base leading-none">&times;</button>
            </div>
        <?php endif; ?>

        <!-- FORM LOGIN -->
        <form action="?v=bG9naW5fc2lzd2E" method="POST" class="space-y-4 text-left">
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">
                    Nomor Induk Siswa Nasional (NISN)
                </label>
                <input type="text" name="nisn" required maxlength="20"
                    value="<?= htmlspecialchars($input_nisn) ?>" 
                    placeholder="Masukkan 10 digit NISN anak" 
                    class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:outline-none text-xs font-semibold text-slate-800 bg-white placeholder:text-slate-400 transition-all">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">PIN Keamanan Akun</label>
                <div class="relative flex items-center">
                    <input type="password" name="pin" id="input-pin" required maxlength="20" 
                        placeholder="Masukkan PIN (Nomor NISN)" 
                        class="w-full pl-3.5 pr-11 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:outline-none text-xs font-semibold text-slate-800 bg-white placeholder:text-slate-400 transition-all">
                    <button type="button" onclick="togglePin()" id="btn-toggle-pin" 
                        class="btn-eye-toggle absolute right-1.5 inset-y-1.5 px-2.5 flex items-center justify-center rounded-lg text-slate-400 hover:text-blue-600 hover:bg-slate-100 active:scale-95 transition-all duration-200 cursor-pointer select-none group" 
                        title="Lihat/Sembunyikan PIN">
                        <i class="fa-solid fa-eye text-sm transition-transform duration-200 group-hover:scale-110" id="icon-pin"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="w-full py-2.5 mt-2 bg-blue-600 hover:bg-blue-700 text-white border border-blue-600 hover:bg-blue-700 font-bold rounded-xl shadow-md transition-all duration-200 hover:scale-[1.02] active:scale-[0.98] text-xs tracking-wide cursor-pointer flex items-center justify-center gap-2">
                <i class="fa-solid fa-right-to-bracket text-white"></i>
                <span>Masuk ke Akun Siswa</span>
            </button>
        </form>

        <!-- TOMBOL DAFTAR AKUN BARU -->
        <div class="pt-3 mt-3 border-t border-slate-200">
            <a href="?v=ZGFmdGFyX3Npc3dh" class="w-full py-2.5 bg-slate-50 hover:bg-slate-100 text-slate-700 border border-slate-200 hover:border-slate-300 font-semibold rounded-xl shadow-sm transition-all duration-200 hover:scale-[1.02] active:scale-[0.98] text-xs tracking-wide cursor-pointer flex items-center justify-center gap-2">
                <i class="fa-solid fa-user-plus text-blue-600"></i>
                <span>Daftar Akun Baru</span>
            </a>
        </div>

        <!-- LINK LOGIN ADMIN / GURU -->
        <div class="pt-2 text-center">
            <a href="?v=bG9naW4" class="text-xs text-blue-600 hover:text-blue-800 font-semibold hover:underline transition-colors">
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

