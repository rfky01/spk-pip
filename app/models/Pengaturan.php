<?php
// app/models/Pengaturan.php - Model Entitas Pengaturan Institusi, Kuota & Status AHP (Class Diagram Skripsi)

class Pengaturan {
    public static function get($conn) {
        $res = mysqli_query($conn, "SELECT * FROM `pengaturan` WHERE id=1 LIMIT 1");
        return mysqli_fetch_assoc($res) ?: [
            'nama_yayasan' => 'YAYASAN AL QODIRI LAMPUNG',
            'nama_sekolah' => 'SMP Tunas Bangsa',
            'sub_instansi' => 'BANDAR MATARAM LAMPUNG TENGAH',
            'alamat_sekolah' => 'Jln. Lapangan Merdeka Raman Agung Mataram Udik Kec. Bandar mataram Lampung Tengah',
            'logo_kiri' => 'uploads/logo_lampung_tengah.png',
            'logo' => 'uploads/logo_smp_tunas_bangsa.png',
            'kepala_sekolah' => 'Fitri Wiyatni, S.Pd.I',
            'nip_kepala_sekolah' => '-',
            'kuota_pip' => 27,
            'tahun_ajaran' => '2025/2026',
            'tgl_buka_pengajuan' => '2026-09-01',
            'tgl_tutup_pengajuan' => '2026-10-31',
            'lambda_max' => 5.2300,
            'ci' => 0.0570,
            'cr' => 0.0510,
            'status_konsistensi' => 'Konsisten'
        ];
    }

    public static function update($conn, $data) {
        $nama_yayasan = mysqli_real_escape_string($conn, $data['nama_yayasan'] ?? '');
        $nama_sekolah = mysqli_real_escape_string($conn, $data['nama_sekolah'] ?? '');
        $sub_instansi = mysqli_real_escape_string($conn, $data['sub_instansi'] ?? '');
        $alamat_sekolah = mysqli_real_escape_string($conn, $data['alamat_sekolah'] ?? '');
        $kepala_sekolah = mysqli_real_escape_string($conn, $data['kepala_sekolah'] ?? '');
        $nip = mysqli_real_escape_string($conn, $data['nip_kepala_sekolah'] ?? '-');
        $kuota = (int)($data['kuota_pip'] ?? 27);
        $tahun = mysqli_real_escape_string($conn, $data['tahun_ajaran'] ?? '2025/2026');
        $tgl_buka = !empty($data['tgl_buka_pengajuan']) ? "'" . mysqli_real_escape_string($conn, $data['tgl_buka_pengajuan']) . "'" : "NULL";
        $tgl_tutup = !empty($data['tgl_tutup_pengajuan']) ? "'" . mysqli_real_escape_string($conn, $data['tgl_tutup_pengajuan']) . "'" : "NULL";

        $logo_sql = "";
        if (!empty($data['logo'])) {
            $logo = mysqli_real_escape_string($conn, $data['logo']);
            $logo_sql .= ", `logo` = '$logo'";
        }
        if (!empty($data['logo_kiri'])) {
            $logo_kiri = mysqli_real_escape_string($conn, $data['logo_kiri']);
            $logo_sql .= ", `logo_kiri` = '$logo_kiri'";
        }

        $sql = "UPDATE `pengaturan` SET 
            `nama_yayasan` = '$nama_yayasan',
            `nama_sekolah` = '$nama_sekolah',
            `sub_instansi` = '$sub_instansi',
            `alamat_sekolah` = '$alamat_sekolah',
            `kepala_sekolah` = '$kepala_sekolah',
            `nip_kepala_sekolah` = '$nip',
            `kuota_pip` = $kuota,
            `tahun_ajaran` = '$tahun',
            `tgl_buka_pengajuan` = $tgl_buka,
            `tgl_tutup_pengajuan` = $tgl_tutup
            $logo_sql
            WHERE id=1";
        return mysqli_query($conn, $sql);
    }

    public static function updateKonsistensi($conn, $lambda_max, $ci, $cr, $status) {
        $lambda_max = (float)$lambda_max;
        $ci = (float)$ci;
        $cr = (float)$cr;
        $status = mysqli_real_escape_string($conn, $status);
        $sql = "UPDATE `pengaturan` SET `lambda_max` = $lambda_max, `ci` = $ci, `cr` = $cr, `status_konsistensi` = '$status' WHERE id=1";
        return mysqli_query($conn, $sql);
    }
}

