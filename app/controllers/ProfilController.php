<?php
// app/controllers/ProfilController.php - Controller Profil Sekolah & Kuota (Backend)

require_once __DIR__ . '/../models/Pengaturan.php';

class ProfilController {
    public static function index($conn) {
        global $koneksi;
        $koneksi = $conn ?: ($GLOBALS["koneksi"] ?? null);
        requireAdmin();
        $pengaturan = Pengaturan::get($conn);
        require_once __DIR__ . '/../../views/admin/profil_sekolah.php';
    }
}

