<?php
// app/controllers/CalonController.php - Controller Usulan Data Calon Siswa PIP (Backend)

require_once __DIR__ . '/../models/CalonPenerima.php';
require_once __DIR__ . '/../models/Pengaturan.php';

class CalonController {
    public static function index($conn) {
        global $koneksi;
        $koneksi = $conn ?: ($GLOBALS["koneksi"] ?? null);
        requireAdmin();
        $pengaturan = Pengaturan::get($conn);
        require_once __DIR__ . '/../../views/admin/calon_penerima.php';
    }
}

