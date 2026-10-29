<?php
// app/controllers/LaporanController.php - Controller Laporan, Cetak PDF & Ekspor Excel (Backend)

require_once __DIR__ . '/../models/Laporan.php';
require_once __DIR__ . '/../models/CalonPenerima.php';
require_once __DIR__ . '/../models/Pengaturan.php';

class LaporanController {
    public static function index($conn) {
        global $koneksi;
        $koneksi = $conn ?: ($GLOBALS["koneksi"] ?? null);
        requireAdmin();
        $pengaturan = Pengaturan::get($conn);
        require_once __DIR__ . '/../../views/admin/laporan.php';
    }

    public static function cetakPdf($conn) {
        global $koneksi;
        $koneksi = $conn ?: ($GLOBALS["koneksi"] ?? null);
        requireAdmin();
        $pengaturan = Pengaturan::get($conn);
        require_once __DIR__ . '/../../views/reports/cetak_laporan.php';
    }

    public static function eksporExcel($conn) {
        global $koneksi;
        $koneksi = $conn ?: ($GLOBALS["koneksi"] ?? null);
        requireAdmin();
        $pengaturan = Pengaturan::get($conn);
        require_once __DIR__ . '/../../views/reports/ekspor_excel.php';
    }

    public static function unduhBukti($conn) {
        global $koneksi;
        $koneksi = $conn ?: ($GLOBALS["koneksi"] ?? null);
        require_once __DIR__ . '/../../views/reports/unduh_bukti_pdf.php';
    }
}

