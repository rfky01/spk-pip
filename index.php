<?php
// index.php - Router Utama Sistem SPK PIP SMP Tunas Bangsa
session_start();

if (isset($_SESSION['admin'])) {
    header("Location: dashboard.php");
} elseif (isset($_SESSION['siswa'])) {
    header("Location: portal_siswa.php");
} else {
    header("Location: daftar_siswa.php");
}
exit;