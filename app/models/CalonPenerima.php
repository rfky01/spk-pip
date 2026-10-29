<?php
// app/models/CalonPenerima.php - Model Entitas Calon Penerima PIP / Alternatif (Class Diagram Skripsi)

class CalonPenerima {
    public static function getAll($conn, $status = 'all', $tahun = 'all') {
        $where = [];
        if ($status !== 'all') {
            $status_esc = mysqli_real_escape_string($conn, $status);
            $where[] = "`status_verifikasi` = '$status_esc'";
        }
        if ($tahun !== 'all') {
            $tahun_esc = mysqli_real_escape_string($conn, $tahun);
            $where[] = "`tahun` = '$tahun_esc'";
        }

        $sql = "SELECT * FROM `calon_penerima`";
        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }
        $sql .= " ORDER BY ranking ASC, total_skor DESC, id_siswa ASC";

        $res = mysqli_query($conn, $sql);
        $data = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $data[] = $row;
        }
        return $data;
    }

    public static function getById($conn, $id) {
        $id = (int)$id;
        $res = mysqli_query($conn, "SELECT * FROM `calon_penerima` WHERE `id_siswa` = $id LIMIT 1");
        return mysqli_fetch_assoc($res);
    }

    public static function getByNisn($conn, $nisn) {
        $nisn = mysqli_real_escape_string($conn, trim($nisn));
        $res = mysqli_query($conn, "SELECT * FROM `calon_penerima` WHERE `nisn` = '$nisn' LIMIT 1");
        return mysqli_fetch_assoc($res);
    }

    public static function setVerification($conn, $id, $status) {
        $id = (int)$id;
        $status = mysqli_real_escape_string($conn, $status);
        return mysqli_query($conn, "UPDATE `calon_penerima` SET `status_verifikasi` = '$status' WHERE `id_siswa` = $id");
    }

    public static function resetPin($conn, $id, $newPin) {
        $id = (int)$id;
        $hashed = password_hash($newPin, PASSWORD_DEFAULT);
        return mysqli_query($conn, "UPDATE `calon_penerima` SET `pin` = '$hashed' WHERE `id_siswa` = $id");
    }

    public static function delete($conn, $id) {
        $id = (int)$id;
        return mysqli_query($conn, "DELETE FROM `calon_penerima` WHERE `id_siswa` = $id");
    }

    public static function getStats($conn, $tahun = 'all') {
        $where = ($tahun !== 'all') ? "WHERE `tahun` = '" . mysqli_real_escape_string($conn, $tahun) . "'" : "";
        $res = mysqli_query($conn, "SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN `status_verifikasi` = 'Terverifikasi' THEN 1 ELSE 0 END) as terverifikasi,
            SUM(CASE WHEN `status_verifikasi` = 'Menunggu Verifikasi' THEN 1 ELSE 0 END) as menunggu,
            SUM(CASE WHEN `status_verifikasi` = 'Ditolak' THEN 1 ELSE 0 END) as ditolak
            FROM `calon_penerima` $where");
        return mysqli_fetch_assoc($res) ?: ['total' => 0, 'terverifikasi' => 0, 'menunggu' => 0, 'ditolak' => 0];
    }
}

