<?php
// app/models/Laporan.php - Model Pelaporan & Filter Kuota SK PIP (Class Diagram Skripsi)

class Laporan {
    public static function getHasilSeleksi($conn, $tahun = 'all') {
        $where_tahun = ($tahun !== 'all') ? " AND c.`tahun` = '" . mysqli_real_escape_string($conn, $tahun) . "'" : "";
        
        $sql = "SELECT c.*, p.kuota_pip 
                FROM `calon_penerima` c
                CROSS JOIN `pengaturan` p
                WHERE c.`status_verifikasi` = 'Terverifikasi' $where_tahun
                ORDER BY c.ranking ASC, c.total_skor DESC";
        $res = mysqli_query($conn, $sql);
        $data = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $data[] = $row;
        }
        return $data;
    }

    public static function getKategoriLolos($siswa_list, $kuota) {
        $lolos = [];
        $cadangan = [];
        foreach ($siswa_list as $s) {
            if ($s['ranking'] > 0 && $s['ranking'] <= $kuota) {
                $lolos[] = $s;
            } else {
                $cadangan[] = $s;
            }
        }
        return ['lolos' => $lolos, 'cadangan' => $cadangan];
    }
}

