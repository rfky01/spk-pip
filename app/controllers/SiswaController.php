<?php
// app/controllers/SiswaController.php - Controller Khusus Siswa & Pendaftaran Mandiri (Backend)

require_once __DIR__ . '/../models/CalonPenerima.php';
require_once __DIR__ . '/../models/Pengaturan.php';

class SiswaController {
    public static function login($conn) {
        global $koneksi;
        $koneksi = $conn ?: ($GLOBALS["koneksi"] ?? null);
        if (isset($_SESSION['siswa'])) {
            header("Location: index.php?v=" . enc_page('portal_siswa'));
            exit;
        }

        $error = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nisn = trim($_POST['nisn'] ?? '');
            $pin  = trim($_POST['pin'] ?? '');

            if (empty($nisn) || empty($pin)) {
                $error = "Silakan masukkan NISN dan PIN pendaftaran.";
            } else {
                $siswa = CalonPenerima::getByNisn($conn, $nisn);
                if ($siswa) {
                    $pin_valid = false;
                    if (!empty($siswa['pin']) && password_verify($pin, $siswa['pin'])) {
                        $pin_valid = true;
                    } elseif ($pin === $siswa['nisn']) {
                        $pin_valid = true;
                    }

                    if ($pin_valid) {
                        $_SESSION['siswa'] = [
                            'id_siswa' => $siswa['id_siswa'],
                            'nisn'     => $siswa['nisn'],
                            'nama'     => $siswa['nama'],
                            'kelas'    => $siswa['kelas']
                        ];
                        header("Location: index.php?v=" . enc_page('portal_siswa'));
                        exit;
                    } else {
                        $error = "PIN yang dimasukkan salah.";
                    }
                } else {
                    $error = "Data NISN belum terdaftar dalam sistem.";
                }
            }
        }

        $pengaturan = Pengaturan::get($conn);
        require_once __DIR__ . '/../../views/auth/login_siswa.php';
    }

    public static function logout() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        unset($_SESSION['siswa']);
        header("Location: index.php?v=" . enc_page('login_siswa') . "&msg=logout");
        exit;
    }

    public static function daftar($conn) {
        global $koneksi;
        $koneksi = $conn ?: ($GLOBALS["koneksi"] ?? null);
        $pengaturan = Pengaturan::get($conn);
        require_once __DIR__ . '/../../views/siswa/daftar_siswa.php';
    }

    public static function portal($conn) {
        global $koneksi;
        $koneksi = $conn ?: ($GLOBALS["koneksi"] ?? null);
        requireSiswa();
        $pengaturan = Pengaturan::get($conn);
        require_once __DIR__ . '/../../views/siswa/portal_siswa.php';
    }
}

