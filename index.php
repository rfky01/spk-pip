<?php
// index.php - Master Router & Front Controller SPK PIP (Pola MVC & Hashed URL)

require_once __DIR__ . '/koneksi.php';
require_once __DIR__ . '/app/config/routes.php';

// Muat Controller-Controller Backend
require_once __DIR__ . '/app/controllers/AuthController.php';
require_once __DIR__ . '/app/controllers/SiswaController.php';
require_once __DIR__ . '/app/controllers/DashboardController.php';
require_once __DIR__ . '/app/controllers/CalonController.php';
require_once __DIR__ . '/app/controllers/KriteriaController.php';
require_once __DIR__ . '/app/controllers/AhpController.php';
require_once __DIR__ . '/app/controllers/RankingController.php';
require_once __DIR__ . '/app/controllers/LaporanController.php';
require_once __DIR__ . '/app/controllers/ProfilController.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$token = $_GET['v'] ?? '';

// 1. Jika URL root dibuka tanpa parameter (?v=)
if (empty($token)) {
    if (isset($_SESSION['admin'])) {
        header("Location: index.php?v=" . enc_page('dashboard'));
    } elseif (isset($_SESSION['siswa'])) {
        header("Location: index.php?v=" . enc_page('portal_siswa'));
    } else {
        header("Location: index.php?v=" . enc_page('login_siswa'));
    }
    exit;
}

// 2. Dispatching Request ke Controller (Pola MVC Backend)
if (isset($ROUTES[$token])) {
    $route = $ROUTES[$token];
    $controllerName = $route['controller'];
    $actionName = $route['action'];

    // Set nama file halaman aktif untuk navigasi menu sidebar
    $CURRENT_PAGE = dec_page($token);

    if (class_exists($controllerName) && method_exists($controllerName, $actionName)) {
        $controllerName::$actionName($koneksi);
        exit;
    }
}

// 3. Fallback Dispatcher jika dipanggil dengan token kustom
$target_file = dec_page($token);
if ($target_file && file_exists(__DIR__ . '/' . $target_file)) {
    $CURRENT_PAGE = $target_file;
    require_once __DIR__ . '/' . $target_file;
    exit;
}

// 4. Default fallback jika token salah / tidak valid
if (isset($_SESSION['admin'])) {
    header("Location: index.php?v=" . enc_page('dashboard'));
} else {
    header("Location: index.php?v=" . enc_page('login_siswa'));
}
exit;
