<?php
// app/models/Kriteria.php - Model Entitas Kriteria Evaluasi AHP (Class Diagram Skripsi)

class Kriteria {
    public static function getAll($conn) {
        $res = mysqli_query($conn, "SELECT * FROM `kriteria` ORDER BY id_kriteria ASC");
        $data = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $data[$row['kode_kriteria']] = $row;
        }
        return $data;
    }

    public static function updateBobot($conn, $kode, $bobot) {
        $kode = mysqli_real_escape_string($conn, $kode);
        $bobot = (float)$bobot;
        return mysqli_query($conn, "UPDATE `kriteria` SET `bobot` = $bobot WHERE `kode_kriteria` = '$kode'");
    }
}

