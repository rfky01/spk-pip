<?php
// app/models/PerhitunganAHP.php - Model Logika Matematis Algoritma AHP (Class Diagram Skripsi)

class PerhitunganAHP {
    // Indeks Random Konsistensi Saaty (RI) untuk n=5
    const RI_5 = 1.12;

    public static function hitung($conn) {
        $kode_kriteria = ['C1', 'C2', 'C3', 'C4', 'C5'];
        $n = count($kode_kriteria);

        // 1. Ambil Matriks Perbandingan Berpasangan
        $matriks = [];
        $res = mysqli_query($conn, "SELECT * FROM `matriks_kriteria`");
        while ($r = mysqli_fetch_assoc($res)) {
            $matriks[$r['kriteria_1']][$r['kriteria_2']] = (float)$r['nilai'];
        }

        // Pastikan diagonal 1 dan simetris
        foreach ($kode_kriteria as $i) {
            foreach ($kode_kriteria as $j) {
                if ($i === $j) {
                    $matriks[$i][$j] = 1.0;
                } elseif (!isset($matriks[$i][$j]) || $matriks[$i][$j] <= 0) {
                    $matriks[$i][$j] = isset($matriks[$j][$i]) && $matriks[$j][$i] > 0 ? 1 / $matriks[$j][$i] : 1.0;
                }
            }
        }

        // 2. Hitung Total Kolom
        $total_kolom = [];
        foreach ($kode_kriteria as $j) {
            $sum = 0;
            foreach ($kode_kriteria as $i) {
                $sum += $matriks[$i][$j];
            }
            $total_kolom[$j] = $sum;
        }

        // 3. Normalisasi Matriks & Hitung Bobot Prioritas (Eigen Vector)
        $matriks_norm = [];
        $bobot_prioritas = [];
        foreach ($kode_kriteria as $i) {
            $sum_row = 0;
            foreach ($kode_kriteria as $j) {
                $norm_val = ($total_kolom[$j] > 0) ? $matriks[$i][$j] / $total_kolom[$j] : 0;
                $matriks_norm[$i][$j] = $norm_val;
                $sum_row += $norm_val;
            }
            $bobot_prioritas[$i] = $sum_row / $n;
            // Update bobot ke tabel kriteria
            mysqli_query($conn, "UPDATE `kriteria` SET `bobot` = " . $bobot_prioritas[$i] . " WHERE `kode_kriteria` = '$i'");
        }

        // 4. Pengujian Rasio Konsistensi (CR)
        // Perkalian Matriks Awal dengan Bobot Prioritas (Weighted Sum Vector)
        $weighted_sum = [];
        foreach ($kode_kriteria as $i) {
            $sum = 0;
            foreach ($kode_kriteria as $j) {
                $sum += $matriks[$i][$j] * $bobot_prioritas[$j];
            }
            $weighted_sum[$i] = $sum;
        }

        // Hitung Rasio Konsistensi per Kriteria (Vector / Bobot) & Rata-rata (Lambda Max)
        $lambda_sum = 0;
        foreach ($kode_kriteria as $i) {
            if ($bobot_prioritas[$i] > 0) {
                $lambda_sum += $weighted_sum[$i] / $bobot_prioritas[$i];
            }
        }
        $lambda_max = $lambda_sum / $n;

        // Consistency Index (CI)
        $ci = ($n > 1) ? ($lambda_max - $n) / ($n - 1) : 0;

        // Consistency Ratio (CR)
        $cr = ($ci / self::RI_5);
        $status_konsistensi = ($cr <= 0.10) ? 'Konsisten' : 'Tidak Konsisten';

        // Simpan log konsistensi ke pengaturan
        mysqli_query($conn, "UPDATE `pengaturan` SET 
            `lambda_max` = $lambda_max, 
            `ci` = $ci, 
            `cr` = $cr, 
            `status_konsistensi` = '$status_konsistensi' 
            WHERE id=1");

        // 5. Perangkingan Calon Penerima Terverifikasi
        $res_calon = mysqli_query($conn, "SELECT * FROM `calon_penerima` WHERE `status_verifikasi` = 'Terverifikasi'");
        $siswa_scores = [];

        while ($s = mysqli_fetch_assoc($res_calon)) {
            // Nilai kriteria (1-5 skala) dikalikan dengan bobot prioritas
            $skor = ($s['penghasilan'] * $bobot_prioritas['C1']) +
                    ($s['tanggungan'] * $bobot_prioritas['C2']) +
                    ($s['kondisi_rumah'] * $bobot_prioritas['C3']) +
                    ($s['prestasi'] * $bobot_prioritas['C4']) +
                    ($s['jarak'] * $bobot_prioritas['C5']);
            
            $siswa_scores[] = [
                'id_siswa' => $s['id_siswa'],
                'skor' => round($skor, 4)
            ];
        }

        // Urutkan skor tertinggi ke terendah
        usort($siswa_scores, function($a, $b) {
            if ($a['skor'] == $b['skor']) return 0;
            return ($a['skor'] > $b['skor']) ? -1 : 1;
        });

        // Update skor dan ranking ke tabel calon_penerima
        $rank = 1;
        foreach ($siswa_scores as $item) {
            $id = $item['id_siswa'];
            $skor = $item['skor'];
            mysqli_query($conn, "UPDATE `calon_penerima` SET `total_skor` = $skor, `ranking` = $rank WHERE `id_siswa` = $id");
            $rank++;
        }

        return [
            'bobot' => $bobot_prioritas,
            'lambda_max' => round($lambda_max, 4),
            'ci' => round($ci, 4),
            'cr' => round($cr, 4),
            'status' => $status_konsistensi,
            'total_siswa_dihitung' => count($siswa_scores)
        ];
    }
}

