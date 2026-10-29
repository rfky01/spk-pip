<?php
// app/controllers/KriteriaController.php - Controller Parameter Kriteria AHP (Backend)

require_once __DIR__ . '/../models/Kriteria.php';
require_once __DIR__ . '/../models/Pengaturan.php';

class KriteriaController {
    public static function index($conn) {
        global $koneksi;
        $koneksi = $conn ?: ($GLOBALS["koneksi"] ?? null);
        requireAdmin();
        $pengaturan = Pengaturan::get($conn);
        require_once __DIR__ . '/../../views/admin/kriteria.php';
    }
}

