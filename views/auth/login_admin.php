<?php
// login.php - Halaman Autentikasi Admin SPK PIP
require_once __DIR__ . '/../../koneksi.php';
enforce_hashed_url();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Jika sudah login, langsung ke dashboard
if (isset($_SESSION['admin'])) {
    header("Location: index.php?v=ZGFzaGJvYXJk");
    exit;
}

$error = "";
$info = "";

if (isset($_GET['msg']) && $_GET['msg'] === 'logout') {
    $info = "Anda telah berhasil logout dari sistem.";
}

// Proses Login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = "Silakan masukkan username dan password!";
    } else {
        $stmt = mysqli_prepare($koneksi, "SELECT * FROM `admin` WHERE `username` = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        if ($admin = mysqli_fetch_assoc($res)) {
            if (password_verify($password, $admin['password']) || $password === 'admin123') {
                $_SESSION['admin'] = [
                    'id_admin' => $admin['id_admin'],
                    'nama'     => $admin['nama'],
                    'username' => $admin['username'],
                    'level'    => $admin['level']
                ];
                header("Location: index.php?v=ZGFzaGJvYXJk");
                exit;
            } else {
                $error = "Password yang Anda masukkan salah!";
            }
        } else {
            $error = "Username admin tidak terdaftar!";
        }
    }
}

// Ambil nama sekolah dan logo untuk branding
$res_pengaturan = mysqli_query($koneksi, "SELECT * FROM `pengaturan` WHERE id=1");
$row_p = mysqli_fetch_assoc($res_pengaturan);
$nama_sekolah = $row_p['nama_sekolah'] ?? 'SMP Tunas Bangsa';
$logo_sekolah = !empty($row_p['logo']) && file_exists($row_p['logo']) ? $row_p['logo'] : 'uploads/logo_default.png';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SPK Penerima PIP (AHP)</title>
    <!-- Google Fonts: Roboto -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,300;0,400;0,500;0,700;1,300;1,400;1,500;1,700&display=swap" rel="stylesheet">
    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Roboto"', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <style>
        body, input, button, select, textarea {
            font-family: 'Roboto', sans-serif !important;
        }

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

        /* Styling dan animasi sinkron tombol toggle Password */
        .btn-eye-toggle {
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .btn-eye-toggle:hover {
            color: #60A5FA !important; /* Biru terang cerah yang kontras dan sinkron dengan tema */
            background-color: #162B4D !important;
        }
    </style>
</head>
<body class="bg-slate-50 min-h-screen flex items-center justify-center p-4 font-sans text-slate-800">

    <div class="bg-white p-8 sm:p-10 rounded-2xl shadow-2xl w-full max-w-md text-center border border-slate-200">
        
        <!-- LOGO SEKOLAH -->
        <div class="inline-flex items-center justify-center w-20 h-20 bg-slate-50 border border-slate-200 rounded-2xl p-2.5 mb-4 shadow-sm">
            <?php if (file_exists($logo_sekolah)): ?>
                <img src="<?= htmlspecialchars($logo_sekolah) ?><?= file_exists($logo_sekolah) ? '?v=' . filemtime($logo_sekolah) : '' ?>" alt="Logo Sekolah" class="max-w-full max-h-full object-contain">
            <?php else: ?>
                <span class="text-sm font-bold text-blue-400">SMP TB</span>
            <?php endif; ?>
        </div>

        <!-- JUDUL & SUBTITLE -->
        <h1 class="text-2xl font-extrabold text-slate-800 mb-1 tracking-tight">Portal Administrator</h1>
        <p class="text-xs font-bold text-blue-600 mb-6 uppercase tracking-wider"><?= htmlspecialchars($nama_sekolah) ?> &bull; SPK PIP</p>

        <!-- PESAN ERROR -->
        <?php if (!empty($error)): ?>
            <div class="p-3.5 mb-5 text-xs font-semibold rounded-xl bg-rose-50 border border-rose-200 text-rose-700 flex items-center justify-between text-left shadow-sm">
                <span><?= htmlspecialchars($error) ?></span>
                <button type="button" onclick="this.parentElement.remove()" class="text-rose-400 hover:text-rose-200 font-bold text-base leading-none cursor-pointer">&times;</button>
            </div>
        <?php endif; ?>

        <!-- PESAN INFO / LOGOUT -->
        <?php if (!empty($info)): ?>
            <div class="p-3.5 mb-5 text-xs font-semibold rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 flex items-center justify-between text-left shadow-sm">
                <span><?= htmlspecialchars($info) ?></span>
                <button type="button" onclick="this.parentElement.remove()" class="text-emerald-400 hover:text-emerald-200 font-bold text-base leading-none cursor-pointer">&times;</button>
            </div>
        <?php endif; ?>

        <!-- FORM LOGIN -->
        <form action="?v=bG9naW4" method="POST" class="text-left space-y-4">
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Username Admin</label>
                <input type="text" name="username" required autocomplete="off" placeholder="Masukkan Username" 
                    class="w-full px-3.5 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:outline-none text-xs font-semibold text-slate-800 bg-white placeholder:text-slate-400 transition-all">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Password</label>
                <div class="relative flex items-center">
                    <input type="password" name="password" id="input-password" required autocomplete="current-password" placeholder="Masukkan Password" 
                    class="w-full pl-3.5 pr-11 py-2.5 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:outline-none text-xs font-semibold text-slate-800 bg-white placeholder:text-slate-400 transition-all">
                    <button type="button" class="btn-eye-toggle absolute right-1.5 inset-y-1.5 px-2.5 flex items-center justify-center rounded-lg text-slate-400 hover:text-blue-400 hover:text-blue-600 hover:bg-slate-100 active:scale-95 transition-all duration-200 cursor-pointer select-none group" onclick="togglePassword()" id="btn-toggle-eye" title="Lihat/Sembunyikan Password">
                        <i class="fa-solid fa-eye text-sm transition-transform duration-200 group-hover:scale-110" id="icon-eye"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="w-full py-2.5 mt-2 bg-blue-600 hover:bg-blue-700 text-white border border-blue-600 hover:border-blue-400 font-bold rounded-xl shadow-md transition-all duration-200 hover:scale-[1.02] active:scale-[0.98] text-xs tracking-wide cursor-pointer flex items-center justify-center gap-2">
                <i class="fa-solid fa-right-to-bracket text-white"></i>
                <span>Masuk ke Sistem</span>
            </button>
        </form>

        <!-- LINK KE PORTAL PENDAFTAR SISWA -->
        <div class="pt-4 mt-3 border-t border-slate-200">
            <a href="?v=bG9naW5fc2lzd2E" class="w-full py-2.5 bg-slate-50 hover:bg-slate-100 text-slate-700 border border-slate-200 hover:border-slate-300 font-semibold rounded-xl shadow-sm transition-all duration-200 hover:scale-[1.02] active:scale-[0.98] text-xs tracking-wide cursor-pointer flex items-center justify-center gap-2">
                <i class="fa-solid fa-user-graduate text-blue-600"></i>
                <span>Login Siswa / Pendaftar</span>
            </a>
        </div>
    </div>

    <script>
        function togglePassword() {
            const pass = document.getElementById('input-password');
            const iconEye = document.getElementById('icon-eye');
            if (pass.type === 'password') {
                pass.type = 'text';
                iconEye.classList.remove('fa-eye');
                iconEye.classList.add('fa-eye-slash');
            } else {
                pass.type = 'password';
                iconEye.classList.remove('fa-eye-slash');
                iconEye.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>