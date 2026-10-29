<?php
// app/controllers/DashboardController.php - Controller Halaman Utama Admin (Backend)

require_once __DIR__ . '/../models/CalonPenerima.php';
require_once __DIR__ . '/../models/Kriteria.php';
require_once __DIR__ . '/../models/Pengaturan.php';

class DashboardController {
    public static function index($conn) {
        global $koneksi;
        $koneksi = $conn ?: ($GLOBALS["koneksi"] ?? null);
        requireAdmin();
        $pengaturan = Pengaturan::get($conn);
        require_once __DIR__ . '/../../views/admin/dashboard.php';
    }
}

