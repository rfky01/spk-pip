<?php
// pengajuan.php - Dialihkan ke Portal Pendaftaran / Siswa Mandiri
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['admin'])) {
    header("Location: dashboard.php");
} elseif (isset($_SESSION['siswa'])) {
    header("Location: portal_siswa.php");
} else {
    header("Location: daftar_siswa.php");
}
exit;
