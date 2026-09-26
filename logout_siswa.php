<?php
// logout_siswa.php - Keluar dari Akun Pendaftar PIP
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

unset($_SESSION['siswa']);
header("Location: login_siswa.php?msg=logout");
exit;

