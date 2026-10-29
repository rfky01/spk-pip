<?php
// app/models/Admin.php - Model Entitas Data Admin (Class Diagram Skripsi)

class Admin {
    public static function findByUsername($conn, $username) {
        $username = mysqli_real_escape_string($conn, $username);
        $res = mysqli_query($conn, "SELECT * FROM `admin` WHERE `username` = '$username' LIMIT 1");
        return mysqli_fetch_assoc($res);
    }

    public static function getById($conn, $id) {
        $id = (int)$id;
        $res = mysqli_query($conn, "SELECT * FROM `admin` WHERE `id_admin` = $id LIMIT 1");
        return mysqli_fetch_assoc($res);
    }

    public static function updateProfile($conn, $id, $nama, $password = null) {
        $id = (int)$id;
        $nama = mysqli_real_escape_string($conn, trim($nama));
        if (!empty($password)) {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $sql = "UPDATE `admin` SET `nama` = '$nama', `password` = '$hashed' WHERE `id_admin` = $id";
        } else {
            $sql = "UPDATE `admin` SET `nama` = '$nama' WHERE `id_admin` = $id";
        }
        return mysqli_query($conn, $sql);
    }
}

