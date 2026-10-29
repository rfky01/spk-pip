<?php
// app/helpers/auth_helper.php - Helper Autentikasi & Sesi SPK PIP

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isAdminLoggedIn() {
    return isset($_SESSION['admin']) && !empty($_SESSION['admin']['id_admin']);
}

function isSiswaLoggedIn() {
    return isset($_SESSION['siswa']) && !empty($_SESSION['siswa']['id_siswa']);
}

function requireAdmin() {
    if (!isAdminLoggedIn()) {
        header("Location: index.php?v=" . enc_page('login'));
        exit;
    }
}

function requireSiswa() {
    if (!isSiswaLoggedIn()) {
        header("Location: index.php?v=" . enc_page('login_siswa'));
        exit;
    }
}

function setFlash($message, $type = 'success') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

function getFlash() {
    if (isset($_SESSION['flash_message'])) {
        $msg = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'info';
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
        return ['message' => $msg, 'type' => $type];
    }
    return null;
}

