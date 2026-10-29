<?php
// app/config/routes.php - Konfigurasi Routing MVC SPK PIP

$ROUTES = [
    // Dashboard Admin
    'ZGFzaGJvYXJk' => [
        'controller' => 'DashboardController',
        'action'     => 'index'
    ],
    // Usulan Calon PIP
    'ZGF0YV9jYWxvbl9wZW5lcmltYQ' => [
        'controller' => 'CalonController',
        'action'     => 'index'
    ],
    // Data Kriteria
    'a3JpdGVyaWE' => [
        'controller' => 'KriteriaController',
        'action'     => 'index'
    ],
    // Perhitungan AHP
    'cGVyaGl0dW5nYW5fYWhw' => [
        'controller' => 'AhpController',
        'action'     => 'index'
    ],
    // Hasil Ranking
    'cmFua2luZw' => [
        'controller' => 'RankingController',
        'action'     => 'index'
    ],
    // Laporan Seleksi
    'bGFwb3Jhbg' => [
        'controller' => 'LaporanController',
        'action'     => 'index'
    ],
    // Profil Sekolah
    'cHJvZmlsX3Nla29sYWg' => [
        'controller' => 'ProfilController',
        'action'     => 'index'
    ],
    // Autentikasi Admin
    'bG9naW4' => [
        'controller' => 'AuthController',
        'action'     => 'login'
    ],
    'bG9nb3V0' => [
        'controller' => 'AuthController',
        'action'     => 'logout'
    ],
    // Autentikasi Siswa
    'bG9naW5fc2lzd2E' => [
        'controller' => 'SiswaController',
        'action'     => 'login'
    ],
    'bG9nb3V0X3Npc3dh' => [
        'controller' => 'SiswaController',
        'action'     => 'logout'
    ],
    // Portal & Pendaftaran Mandiri Siswa
    'ZGFmdGFyX3Npc3dh' => [
        'controller' => 'SiswaController',
        'action'     => 'daftar'
    ],
    'cG9ydGFsX3Npc3dh' => [
        'controller' => 'SiswaController',
        'action'     => 'portal'
    ],
    // Pelaporan, Cetak PDF & Ekspor Excel
    'Y2V0YWtfbGFwb3Jhbg' => [
        'controller' => 'LaporanController',
        'action'     => 'cetakPdf'
    ],
    'ZWtzcG9yX2V4Y2Vs' => [
        'controller' => 'LaporanController',
        'action'     => 'eksporExcel'
    ],
    'dW5kdWhfYnVrdGlfcGRm' => [
        'controller' => 'LaporanController',
        'action'     => 'unduhBukti'
    ],
];

