<?php
// app/helpers/url_helper.php - Helper URL & Obfuscator Hashing SPK PIP

if (!defined('SPK_PIP_APP')) {
    define('SPK_PIP_APP', true);
}

// Peta rute halaman ke token Base64 URL-safe
$GLOBAL_ROUTE_MAP = [
    'dashboard'           => 'ZGFzaGJvYXJk',
    'data_calon_penerima' => 'ZGF0YV9jYWxvbl9wZW5lcmltYQ',
    'calon'               => 'ZGF0YV9jYWxvbl9wZW5lcmltYQ',
    'kriteria'            => 'a3JpdGVyaWE',
    'perhitungan_ahp'     => 'cGVyaGl0dW5nYW5fYWhw',
    'matriks'             => 'cGVyaGl0dW5nYW5fYWhw',
    'ranking'             => 'cmFua2luZw',
    'laporan'             => 'bGFwb3Jhbg',
    'profil_sekolah'      => 'cHJvZmlsX3Nla29sYWg',
    'notifikasi'          => 'bm90aWZpa2FzaQ',
    'portal_siswa'        => 'cG9ydGFsX3Npc3dh',
    'daftar_siswa'        => 'ZGFmdGFyX3Npc3dh',
    'pengajuan'           => 'ZGFmdGFyX3Npc3dh',
    'login'               => 'bG9naW4',
    'login_siswa'         => 'bG9naW5fc2lzd2E',
    'logout'              => 'bG9nb3V0',
    'logout_siswa'        => 'bG9nb3V0X3Npc3dh',
    'cetak_laporan'       => 'Y2V0YWtfbGFwb3Jhbg',
    'ekspor_excel'        => 'ZWtzcG9yX2V4Y2Vs',
    'unduh_bukti_pdf'     => 'dW5kdWhfYnVrdGlfcGRm',
];

// Peta token kembali ke file PHP fisik
$GLOBAL_TOKEN_MAP = [
    'ZGFzaGJvYXJk'                => 'dashboard.php',
    'ZGF0YV9jYWxvbl9wZW5lcmltYQ'    => 'data_calon_penerima.php',
    'a3JpdGVyaWE'                 => 'kriteria.php',
    'cGVyaGl0dW5nYW5fYWhw'        => 'perhitungan_ahp.php',
    'cmFua2luZw'                  => 'ranking.php',
    'bGFwb3Jhbg'                  => 'laporan.php',
    'cHJvZmlsX3Nla29sYWg'         => 'profil_sekolah.php',
    'bm90aWZpa2FzaQ'              => 'notifikasi.php',
    'cG9ydGFsX3Npc3dh'            => 'portal_siswa.php',
    'ZGFmdGFyX3Npc3dh'            => 'daftar_siswa.php',
    'bG9naW4'                     => 'login.php',
    'bG9naW5fc2lzd2E'             => 'login_siswa.php',
    'bG9nb3V0'                    => 'logout.php',
    'bG9nb3V0X3Npc3dh'            => 'logout_siswa.php',
    'Y2V0YWtfbGFwb3Jhbg'          => 'cetak_laporan.php',
    'ZWtzcG9yX2V4Y2Vs'            => 'ekspor_excel.php',
    'dW5kdWhfYnVrdGlfcGRm'        => 'unduh_bukti_pdf.php',
];

function enc_page($page) {
    global $GLOBAL_ROUTE_MAP;
    $clean = pathinfo($page, PATHINFO_FILENAME);
    if (isset($GLOBAL_ROUTE_MAP[$clean])) {
        return $GLOBAL_ROUTE_MAP[$clean];
    }
    return rtrim(strtr(base64_encode($clean), '+/', '-_'), '=');
}

function dec_page($token) {
    global $GLOBAL_TOKEN_MAP;
    if (isset($GLOBAL_TOKEN_MAP[$token])) {
        return $GLOBAL_TOKEN_MAP[$token];
    }
    $decoded = base64_decode(strtr($token, '-_', '+/'));
    if ($decoded && file_exists(__DIR__ . '/../../' . $decoded . '.php')) {
        return $decoded . '.php';
    }
    return null;
}

function route_url($target) {
    if (empty($target) || $target === '#' || strpos($target, 'javascript:') === 0) {
        return $target;
    }
    if (strpos($target, '?v=') !== false || strpos($target, '&v=') !== false) {
        return $target;
    }
    $parts = parse_url($target);
    $path = $parts['path'] ?? '';
    $file = basename($path);
    if (!empty($file) && substr($file, -4) === '.php') {
        $token = enc_page($file);
        $qs = !empty($parts['query']) ? '&' . $parts['query'] : '';
        $hash = !empty($parts['fragment']) ? '#' . $parts['fragment'] : '';
        return 'index.php?v=' . $token . $qs . $hash;
    }
    return $target;
}

function enforce_hashed_url() {
    $script = basename($_SERVER['SCRIPT_NAME'] ?? '');
    if ($script !== 'index.php' && substr($script, -4) === '.php') {
        $token = enc_page($script);
        $qs = !empty($_SERVER['QUERY_STRING']) ? '&' . $_SERVER['QUERY_STRING'] : '';
        header("Location: index.php?v=" . $token . $qs);
        exit;
    }
}

