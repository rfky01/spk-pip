<?php
// koneksi.php - Koneksi Database MySQL & Inisialisasi Environment SPK PIP

$root_dir = __DIR__;

// Daftarkan jalur include path agar komponen MVC dapat saling mengenali
set_include_path(
    get_include_path() . PATH_SEPARATOR .
    $root_dir . PATH_SEPARATOR .
    $root_dir . '/views/layouts' . PATH_SEPARATOR .
    $root_dir . '/views/admin' . PATH_SEPARATOR .
    $root_dir . '/views/auth' . PATH_SEPARATOR .
    $root_dir . '/views/siswa' . PATH_SEPARATOR .
    $root_dir . '/views/reports'
);

require_once $root_dir . '/app/config/database.php';
global $koneksi;
$GLOBALS['koneksi'] = $koneksi;

require_once $root_dir . '/app/helpers/url_helper.php';
require_once $root_dir . '/app/helpers/auth_helper.php';

// Fungsi pembantu format tanggal Bahasa Indonesia (contoh: 20 September 2026)
if (!function_exists('format_tgl_indo')) {
    function format_tgl_indo($tanggal) {
        if (empty($tanggal) || $tanggal === '0000-00-00') return '-';
        $bulan = [
            1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
        ];
        $time = strtotime($tanggal);
        if (!$time) return $tanggal;
        $d = (int)date('d', $time);
        $m = (int)date('m', $time);
        $y = date('Y', $time);
        return sprintf('%02d', $d) . ' ' . ($bulan[$m] ?? '') . ' ' . $y;
    }
}

// Muat Model-Model Inti
require_once $root_dir . '/app/models/Admin.php';
require_once $root_dir . '/app/models/Kriteria.php';
require_once $root_dir . '/app/models/MatriksKriteria.php';
require_once $root_dir . '/app/models/CalonPenerima.php';
require_once $root_dir . '/app/models/Pengaturan.php';
require_once $root_dir . '/app/models/PerhitunganAHP.php';
require_once $root_dir . '/app/models/Laporan.php';
