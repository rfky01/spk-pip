<?php
// app/controllers/AhpController.php - Controller Pemrosesan Algoritma AHP (Backend)

require_once __DIR__ . '/../models/Kriteria.php';
require_once __DIR__ . '/../models/MatriksKriteria.php';
require_once __DIR__ . '/../models/PerhitunganAHP.php';
require_once __DIR__ . '/../models/Pengaturan.php';

class AhpController {
    public static function index($conn) {
        global $koneksi;
        $koneksi = $conn ?: ($GLOBALS["koneksi"] ?? null);
        requireAdmin();
        $pengaturan = Pengaturan::get($conn);
        require_once __DIR__ . '/../../views/admin/perhitungan_ahp.php';
    }
}

