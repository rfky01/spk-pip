<?php
// app/controllers/AuthController.php - Controller Autentikasi Admin (Backend)

require_once __DIR__ . '/../models/Admin.php';
require_once __DIR__ . '/../models/Pengaturan.php';

class AuthController {
    public static function login($conn) {
        global $koneksi;
        $koneksi = $conn ?: ($GLOBALS["koneksi"] ?? null);
        if (isset($_SESSION['admin'])) {
            header("Location: index.php?v=" . enc_page('dashboard'));
            exit;
        }

        $error = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = trim($_POST['username'] ?? '');
            $password = trim($_POST['password'] ?? '');

            if (empty($username) || empty($password)) {
                $error = "Silakan masukkan username dan password.";
            } else {
                $admin = Admin::findByUsername($conn, $username);
                if ($admin && password_verify($password, $admin['password'])) {
                    $_SESSION['admin'] = [
                        'id_admin' => $admin['id_admin'],
                        'username' => $admin['username'],
                        'nama'     => $admin['nama'],
                        'level'    => $admin['level']
                    ];
                    header("Location: index.php?v=" . enc_page('dashboard'));
                    exit;
                } else {
                    $error = "Username atau password salah.";
                }
            }
        }

        $pengaturan = Pengaturan::get($conn);
        require_once __DIR__ . '/../../views/auth/login_admin.php';
    }

    public static function logout() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        unset($_SESSION['admin']);
        header("Location: index.php?v=" . enc_page('login') . "&msg=logout");
        exit;
    }
}

