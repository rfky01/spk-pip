<?php
// app/models/MatriksKriteria.php - Model Matriks Perbandingan Berpasangan Saaty (Class Diagram Skripsi)

class MatriksKriteria {
    public static function getAll($conn) {
        $res = mysqli_query($conn, "SELECT * FROM `matriks_kriteria`");
        $matriks = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $matriks[$row['kriteria_1']][$row['kriteria_2']] = (float)$row['nilai'];
        }
        return $matriks;
    }

    public static function savePair($conn, $k1, $k2, $nilai) {
        $k1 = mysqli_real_escape_string($conn, $k1);
        $k2 = mysqli_real_escape_string($conn, $k2);
        $nilai = (float)$nilai;
        $sql = "INSERT INTO `matriks_kriteria` (`kriteria_1`, `kriteria_2`, `nilai`) 
                VALUES ('$k1', '$k2', $nilai) 
                ON DUPLICATE KEY UPDATE `nilai` = $nilai";
        return mysqli_query($conn, $sql);
    }
}

