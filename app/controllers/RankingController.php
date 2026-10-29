<?php
// app/controllers/RankingController.php - Controller Hasil Pemeringkatan & Kuota (Backend)

require_once __DIR__ . '/../models/CalonPenerima.php';
require_once __DIR__ . '/../models/Pengaturan.php';

class RankingController {
    public static function index($conn) {
        global $koneksi;
        $koneksi = $conn ?: ($GLOBALS["koneksi"] ?? null);
        requireAdmin();
        $pengaturan = Pengaturan::get($conn);
        require_once __DIR__ . '/../../views/admin/ranking.php';
    }
}

