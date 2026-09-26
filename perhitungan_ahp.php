<?php
// perhitungan_ahp.php - Matriks Perbandingan Berpasangan & Perhitungan Bobot AHP 5x5
$page_title = "Perhitungan AHP";
require_once "header.php";

$msg = "";
$msg_type = "";

// A. Proses Perhitungan AHP Saat Tombol Ditekan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'hitung_ahp') {
    $kriteria_list = ['C1', 'C2', 'C3', 'C4', 'C5'];
    $matriks_input = [];

    for ($i = 0; $i < 5; $i++) {
        for ($j = 0; $j < 5; $j++) {
            $k1 = $kriteria_list[$i];
            $k2 = $kriteria_list[$j];

            if ($i === $j) {
                $val = 1.0;
            } elseif ($i < $j) {
                $input_key = "nilai_{$k1}_{$k2}";
                $val = (float)($_POST[$input_key] ?? 1.0);
                if ($val <= 0) $val = 1.0;
            } else {
                $input_recip_key = "nilai_{$k2}_{$k1}";
                $upper_val = (float)($_POST[$input_recip_key] ?? 1.0);
                if ($upper_val <= 0) $upper_val = 1.0;
                $val = 1.0 / $upper_val;
            }
            $matriks_input[$i][$j] = $val;

            mysqli_query($koneksi, "REPLACE INTO `matriks_kriteria` (`kriteria_1`, `kriteria_2`, `nilai`) 
                VALUES ('$k1', '$k2', $val)");
        }
    }

    // 1. Total Kolom
    $col_sums = array_fill(0, 5, 0.0);
    for ($j = 0; $j < 5; $j++) {
        for ($i = 0; $i < 5; $i++) {
            $col_sums[$j] += $matriks_input[$i][$j];
        }
    }

    // 2. Normalisasi & Bobot Prioritas
    $bobot_baru = [];
    for ($i = 0; $i < 5; $i++) {
        $row_sum = 0.0;
        for ($j = 0; $j < 5; $j++) {
            $normalized_val = $matriks_input[$i][$j] / ($col_sums[$j] > 0 ? $col_sums[$j] : 1);
            $row_sum += $normalized_val;
        }
        $w = round($row_sum / 5.0, 4);
        $bobot_baru[$kriteria_list[$i]] = $w;

        $k_code = $kriteria_list[$i];
        mysqli_query($koneksi, "UPDATE `kriteria` SET `bobot` = $w WHERE `kode_kriteria` = '$k_code'");
    }

    // 3. Uji Konsistensi (Lambda Max, CI, CR)
    $lambda_estimates = [];
    for ($i = 0; $i < 5; $i++) {
        $axw = 0.0;
        for ($j = 0; $j < 5; $j++) {
            $axw += ($matriks_input[$i][$j] * $bobot_baru[$kriteria_list[$j]]);
        }
        $w_i = $bobot_baru[$kriteria_list[$i]];
        $lambda_estimates[] = ($w_i > 0) ? ($axw / $w_i) : 5.0;
    }
    $lambda_max = round(array_sum($lambda_estimates) / 5.0, 4);
    $ci = round(($lambda_max - 5.0) / 4.0, 4);
    if ($ci < 0) $ci = 0.0000;

    $ri = 1.12; // Skala Saaty untuk n=5
    $cr = round($ci / $ri, 4);
    $status_konsistensi = ($cr <= 0.10) ? 'Konsisten' : 'Tidak Konsisten';

    mysqli_query($koneksi, "UPDATE `pengaturan` SET 
        `lambda_max` = $lambda_max, `ci` = $ci, `cr` = $cr, `status_konsistensi` = '$status_konsistensi' 
        WHERE `id` = 1");

    // Perbarui skor seluruh siswa berdasarkan bobot baru
    $q_siswa = mysqli_query($koneksi, "SELECT id_siswa, penghasilan, tanggungan, kondisi_rumah, prestasi, jarak FROM `calon_penerima`");
    while ($s = mysqli_fetch_assoc($q_siswa)) {
        $skor = ($s['penghasilan'] * $bobot_baru['C1']) +
                ($s['tanggungan']  * $bobot_baru['C2']) +
                ($s['kondisi_rumah']* $bobot_baru['C3']) +
                ($s['prestasi']    * $bobot_baru['C4']) +
                ($s['jarak']       * $bobot_baru['C5']);
        $id_s = $s['id_siswa'];
        $skor_rnd = round($skor, 4);
        mysqli_query($koneksi, "UPDATE `calon_penerima` SET `total_skor` = $skor_rnd WHERE `id_siswa` = $id_s");
    }

    $msg = "Perhitungan bobot kriteria AHP berhasil! Nilai CR: $cr (" . strtoupper($status_konsistensi) . ").";
    $msg_type = ($cr <= 0.10) ? 'success' : 'error';
}

require_once "sidebar.php";

// Ambil bobot kriteria terkini
$res_kriteria = mysqli_query($koneksi, "SELECT * FROM `kriteria` ORDER BY `kode_kriteria` ASC");
$kriteria_db = [];
while ($row = mysqli_fetch_assoc($res_kriteria)) {
    $kriteria_db[$row['kode_kriteria']] = (float)$row['bobot'];
}

// Ambil matriks tersimpan
$res_matriks = mysqli_query($koneksi, "SELECT * FROM `matriks_kriteria`");
$matriks_stored = [];
while ($m = mysqli_fetch_assoc($res_matriks)) {
    $matriks_stored[$m['kriteria_1']][$m['kriteria_2']] = (float)$m['nilai'];
}

// Ambil data pengaturan terbaru
$res_p = mysqli_query($koneksi, "SELECT * FROM `pengaturan` WHERE id=1");
$pengaturan = mysqli_fetch_assoc($res_p);
?>

<div class="space-y-6 w-full">
    <?php if (!empty($msg)): ?>
        <div class="p-4 rounded-xl flex items-center justify-between shadow-sm <?= $msg_type === 'success' ? 'bg-emerald-50 border border-emerald-200 text-emerald-800' : 'bg-red-50 border border-red-200 text-red-800' ?>">
            <div class="flex items-center gap-3">
                <i class="fa-solid <?= $msg_type === 'success' ? 'fa-circle-check text-emerald-600' : 'fa-circle-exclamation text-red-600' ?> text-lg"></i>
                <span class="text-sm font-medium"><?= htmlspecialchars($msg) ?></span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-gray-400 hover:text-gray-600"><i class="fa-solid fa-xmark"></i></button>
        </div>
    <?php endif; ?>

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Perhitungan Metode AHP (5x5)</h1>
            <p class="text-xs text-slate-500 mt-1">Matriks Perbandingan Berpasangan, Normalisasi, & Uji Konsistensi</p>
        </div>
        <div>
            <span class="px-3 py-1.5 font-semibold text-xs rounded-lg whitespace-nowrap shrink-0 inline-flex items-center gap-1.5 <?= ($pengaturan['cr'] <= 0.10) ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200' ?>">
                <i class="fa-solid <?= ($pengaturan['cr'] <= 0.10) ? 'fa-circle-check' : 'fa-circle-xmark' ?>"></i>
                <span>Nilai CR: <strong><?= number_format($pengaturan['cr'], 4) ?></strong> (<?= htmlspecialchars($pengaturan['status_konsistensi']) ?>)</span>
            </span>
        </div>
    </div>

    <!-- 1. MATRIKS PERBANDINGAN BERPASANGAN -->
    <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm w-full">
        <div class="mb-4">
            <h3 class="text-sm font-bold text-slate-800">Matriks Perbandingan Berpasangan Kriteria</h3>
            <p class="text-xs text-slate-500 mt-0.5">Masukkan perbandingan kepentingan kriteria (1–9)</p>
        </div>

        <form action="perhitungan_ahp.php" method="POST">
            <input type="hidden" name="action" value="hitung_ahp">

            <div class="overflow-x-auto">
                <table class="w-full text-center border-collapse text-sm min-w-[650px]">
                    <thead>
                        <tr class="bg-slate-50 text-slate-700 font-semibold border-b border-slate-200 text-xs">
                            <th class="p-3 bg-slate-100/70 text-left">Kriteria</th>
                            <th class="p-3">C1 (Penghasilan)</th>
                            <th class="p-3">C2 (Tanggungan)</th>
                            <th class="p-3">C3 (Kondisi Rumah)</th>
                            <th class="p-3">C4 (Prestasi)</th>
                            <th class="p-3">C5 (Jarak)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700 font-medium text-xs">
                        <?php
                        $k_keys = ['C1', 'C2', 'C3', 'C4', 'C5'];
                        $k_labels = [
                            'C1' => 'C1: Penghasilan',
                            'C2' => 'C2: Tanggungan',
                            'C3' => 'C3: Kondisi Rumah',
                            'C4' => 'C4: Prestasi',
                            'C5' => 'C5: Jarak Sekolah'
                        ];

                        foreach ($k_keys as $i_idx => $k1):
                        ?>
                        <tr>
                            <td class="p-3 font-bold bg-slate-50/70 text-left text-xs text-slate-800 whitespace-nowrap"><?= $k_labels[$k1] ?></td>
                            <?php 
                            foreach ($k_keys as $j_idx => $k2): 
                                $stored_val = $matriks_stored[$k1][$k2] ?? ($i_idx === $j_idx ? 1.0 : 1.0);
                            ?>
                            <td class="p-2">
                                <?php if ($i_idx === $j_idx): ?>
                                    <input type="text" disabled value="1" 
                                        class="w-16 mx-auto text-center py-1.5 bg-slate-100 border border-slate-200 rounded-lg text-slate-400 font-bold text-xs">
                                <?php elseif ($i_idx < $j_idx): ?>
                                    <input type="number" step="0.01" min="0.11" max="9" 
                                        name="nilai_<?= $k1 ?>_<?= $k2 ?>" 
                                        id="cell_<?= $k1 ?>_<?= $k2 ?>"
                                        value="<?= number_format($stored_val, 2, '.', '') ?>" 
                                        onchange="updateReciprocal('<?= $k1 ?>', '<?= $k2 ?>')"
                                        class="w-16 mx-auto text-center py-1.5 border border-slate-300 rounded-lg focus:outline-none focus:border-slate-800 focus:ring-1 focus:ring-slate-800 font-bold text-xs text-slate-900 bg-white">
                                <?php else: ?>
                                    <input type="text" readonly 
                                        id="recip_<?= $k1 ?>_<?= $k2 ?>"
                                        value="<?= number_format($stored_val, 2, '.', '') ?>" 
                                        class="w-16 mx-auto text-center py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-slate-600 text-xs font-semibold cursor-not-allowed">
                                <?php endif; ?>
                            </td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="mt-6 flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-4 border-t border-slate-100">
                <button type="submit" class="px-5 py-2.5 bg-slate-900 hover:bg-slate-800 text-white font-semibold rounded-lg text-xs transition-colors shadow flex items-center justify-center gap-2 whitespace-nowrap">
                    <i class="fa-solid fa-calculator"></i> Hitung Bobot Kriteria & Uji Konsistensi
                </button>
                <span class="text-xs text-slate-400">Parameter Saaty: Random Index RI (n=5) = 1.12</span>
            </div>
        </form>
    </div>

    <!-- 2. HASIL BOBOT PRIORITAS KRITERIA -->
    <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm w-full space-y-4">
        <h3 class="text-sm font-bold text-slate-800">Hasil Bobot Prioritas Kriteria</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-4">
            <?php 
            $kriteria_desc = [
                'C1' => 'Penghasilan Orang Tua',
                'C2' => 'Tanggungan Keluarga',
                'C3' => 'Kondisi Rumah',
                'C4' => 'Prestasi Akademik',
                'C5' => 'Jarak ke Sekolah'
            ];
            foreach ($kriteria_desc as $k_code => $label):
                $b_val = $kriteria_db[$k_code] ?? 0.0;
                $b_pct = round($b_val * 100, 2);
            ?>
            <div class="p-4 bg-slate-50/70 border border-slate-200 rounded-xl text-center">
                <span class="text-xs font-bold text-slate-800 bg-white px-2 py-0.5 rounded border border-slate-200 inline-block mb-1"><?= $k_code ?></span>
                <p class="text-[11px] text-slate-500 truncate mb-2" title="<?= $label ?>"><?= $label ?></p>
                <p class="text-2xl font-extrabold text-slate-900"><?= number_format($b_val, 4) ?></p>
                <span class="text-[11px] text-slate-600 font-semibold mt-1 inline-block"><?= $b_pct ?>%</span>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- 3. UJI KONSISTENSI LOGIS -->
        <div class="pt-3">
            <h3 class="text-sm font-bold text-slate-800 mb-1">Hasil Uji Konsistensi Matriks</h3>
            <p class="text-xs text-slate-500 mb-3">Berdasarkan rasio konsistensi (CR) metode Saaty dengan Random Index (RI) = 1.12 untuk matriks ordo 5&times;5</p>
        </div>
        <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white p-3 rounded-lg border border-slate-200">
                <span class="text-slate-500 block text-[11px] mb-1">&lambda; Maksimum (&lambda;<sub>max</sub>):</span>
                <strong class="text-slate-900 font-mono text-base"><?= number_format($pengaturan['lambda_max'] ?? 5.0, 4) ?></strong>
            </div>
            <div class="bg-white p-3 rounded-lg border border-slate-200">
                <span class="text-slate-500 block text-[11px] mb-1">CI (Consistency Index):</span>
                <strong class="text-slate-900 font-mono text-base"><?= number_format($pengaturan['ci'] ?? 0.0, 4) ?></strong>
            </div>
            <div class="bg-white p-3 rounded-lg border border-slate-200">
                <span class="text-slate-500 block text-[11px] mb-1">CR (Consistency Ratio):</span>
                <strong class="text-indigo-600 font-mono text-base"><?= number_format($pengaturan['cr'] ?? 0.0, 4) ?></strong>
            </div>
            <div class="bg-white p-3 rounded-lg border border-slate-200">
                <span class="text-slate-500 block text-[11px] mb-1">Status Konsistensi:</span>
                <?php $is_konsisten = (($pengaturan['cr'] ?? 0) <= 0.10); ?>
                <span class="font-bold px-2.5 py-0.5 rounded border inline-flex items-center gap-1.5 <?= $is_konsisten ? 'text-emerald-700 bg-emerald-50 border-emerald-200' : 'text-rose-700 bg-rose-50 border-rose-200' ?>">
                    <i class="fa-solid <?= $is_konsisten ? 'fa-circle-check' : 'fa-circle-xmark' ?>"></i>
                    <?= htmlspecialchars($pengaturan['status_konsistensi'] ?? 'Konsisten') ?>
                </span>
                <span class="text-[10px] text-slate-400 block mt-1">Syarat: Nilai CR &le; 0.10 (10%)</span>
            </div>
        </div>
    </div>
</div>

<script>
    function updateReciprocal(k1, k2) {
        const input = document.getElementById(`cell_${k1}_${k2}`);
        const recip = document.getElementById(`recip_${k2}_${k1}`);
        if (input && recip) {
            let val = parseFloat(input.value);
            if (val > 0) {
                recip.value = (1.0 / val).toFixed(2);
            }
        }
    }
</script>

<?php require_once "footer.php"; ?>