<?php
// kriteria.php - Halaman Manajemen & Data Kriteria Penilaian AHP
$page_title = "Data Kriteria";
require_once "header.php";

$msg = "";
$msg_type = "";

// Ambil data kriteria dari database
$res_kriteria = mysqli_query($koneksi, "SELECT * FROM `kriteria` ORDER BY `kode_kriteria` ASC");
$kriteria_list = [];
$total_bobot = 0;
while ($k = mysqli_fetch_assoc($res_kriteria)) {
    $kriteria_list[] = $k;
    $total_bobot += (float)$k['bobot'];
}

require_once "sidebar.php";
?>

<div class="space-y-6 w-full">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Data Kriteria Penilaian AHP</h1>
            <p class="text-xs text-slate-500 mt-1">Daftar 5 kriteria penentuan prioritas penerima Program Indonesia Pintar (PIP)</p>
        </div>
        <a href="perhitungan_ahp.php" class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white rounded-lg text-xs font-semibold shadow-sm transition-colors flex items-center gap-2 self-start">
            <i class="fa-solid fa-calculator"></i> Ke Matriks Perhitungan AHP &rarr;
        </a>
    </div>

    <!-- 1. TABEL 5 KRITERIA UTAMA -->
    <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm w-full space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <h3 class="text-sm font-bold text-slate-800">Kriteria Utama</h3>
            <span class="text-xs text-slate-500 font-medium">Total Bobot: <b class="text-slate-900 font-bold"><?= round($total_bobot * 100, 2) ?>% (<?= round($total_bobot, 4) ?>)</b></span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm min-w-[600px]">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold text-xs uppercase tracking-wider">
                        <th class="p-3 text-center w-14">No</th>
                        <th class="p-3 w-24">Kode</th>
                        <th class="p-3">Nama Kriteria</th>
                        <th class="p-3 text-center w-36">Sifat / Atribut</th>
                        <th class="p-3 text-center w-32">Bobot AHP</th>
                        <th class="p-3 text-center w-32">Persentase</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700 text-xs font-medium">
                    <?php 
                    $no = 1;
                    foreach ($kriteria_list as $k): 
                        $pct = round((float)$k['bobot'] * 100, 2);
                    ?>
                    <tr class="hover:bg-slate-50/70">
                        <td class="p-3 text-center text-slate-400"><?= $no++ ?></td>
                        <td class="p-3 font-bold text-slate-900 font-mono"><?= htmlspecialchars($k['kode_kriteria']) ?></td>
                        <td class="p-3 font-semibold text-slate-800"><?= htmlspecialchars($k['nama_kriteria']) ?></td>
                        <td class="p-3 text-center">
                            <?php if ($k['atribut'] === 'cost'): ?>
                                <span class="px-2.5 py-0.5 bg-slate-100 text-slate-700 border border-slate-200 rounded text-[11px] font-medium">
                                    Cost
                                </span>
                            <?php else: ?>
                                <span class="px-2.5 py-0.5 bg-slate-100 text-slate-700 border border-slate-200 rounded text-[11px] font-medium">
                                    Benefit
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="p-3 text-center font-bold text-slate-900 font-mono"><?= number_format($k['bobot'], 4) ?></td>
                        <td class="p-3 text-center">
                            <span class="font-bold text-slate-800 bg-slate-100 px-2.5 py-0.5 rounded text-xs"><?= $pct ?>%</span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- 2. PEDOMAN SUBKRITERIA & SKALA NILAI 1 - 5 (Tabel 3.4 Skripsi) -->
    <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm w-full space-y-4">
        <div class="pb-2 border-b border-slate-100">
            <h3 class="text-sm font-bold text-slate-800">Pedoman Penilaian Subkriteria</h3>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 text-xs">
            <!-- C1 -->
            <div class="p-4 bg-slate-50/70 border border-slate-200 rounded-xl space-y-2.5">
                <div class="flex items-center justify-between font-bold text-slate-900 border-b border-slate-200 pb-2">
                    <span>C1: Penghasilan Orang Tua</span>
                    <span class="text-[10px] bg-slate-200 text-slate-700 font-medium px-1.5 py-0.5 rounded">Cost</span>
                </div>
                <ul class="space-y-1.5 text-slate-600">
                    <li class="flex justify-between items-center gap-2"><span>&lt; Rp 500.000</span> <b class="text-slate-800 bg-white border border-slate-200 px-1.5 py-0.5 rounded text-[11px] whitespace-nowrap shrink-0">Skor 5</b></li>
                    <li class="flex justify-between items-center gap-2"><span>Rp 600.000 - Rp 1.000.000</span> <b class="text-slate-800 bg-white border border-slate-200 px-1.5 py-0.5 rounded text-[11px] whitespace-nowrap shrink-0">Skor 4</b></li>
                    <li class="flex justify-between items-center gap-2"><span>Rp 1.000.000 - Rp 2.000.000</span> <b class="text-slate-800 bg-white border border-slate-200 px-1.5 py-0.5 rounded text-[11px] whitespace-nowrap shrink-0">Skor 3</b></li>
                    <li class="flex justify-between items-center gap-2"><span>Rp 2.000.000 - Rp 3.000.000</span> <b class="text-slate-800 bg-white border border-slate-200 px-1.5 py-0.5 rounded text-[11px] whitespace-nowrap shrink-0">Skor 2</b></li>
                    <li class="flex justify-between items-center gap-2"><span>&gt; Rp 4.000.000</span> <b class="text-slate-800 bg-white border border-slate-200 px-1.5 py-0.5 rounded text-[11px] whitespace-nowrap shrink-0">Skor 1</b></li>
                </ul>
            </div>

            <!-- C2 -->
            <div class="p-4 bg-slate-50/70 border border-slate-200 rounded-xl space-y-2.5">
                <div class="flex items-center justify-between font-bold text-slate-900 border-b border-slate-200 pb-2">
                    <span>C2: Tanggungan Keluarga</span>
                    <span class="text-[10px] bg-slate-200 text-slate-700 font-medium px-1.5 py-0.5 rounded">Benefit</span>
                </div>
                <ul class="space-y-1.5 text-slate-600">
                    <li class="flex justify-between items-center gap-2"><span>&gt; 5 Orang</span> <b class="text-slate-800 bg-white border border-slate-200 px-1.5 py-0.5 rounded text-[11px] whitespace-nowrap shrink-0">Skor 5</b></li>
                    <li class="flex justify-between items-center gap-2"><span>4 Orang</span> <b class="text-slate-800 bg-white border border-slate-200 px-1.5 py-0.5 rounded text-[11px] whitespace-nowrap shrink-0">Skor 4</b></li>
                    <li class="flex justify-between items-center gap-2"><span>3 Orang</span> <b class="text-slate-800 bg-white border border-slate-200 px-1.5 py-0.5 rounded text-[11px] whitespace-nowrap shrink-0">Skor 3</b></li>
                    <li class="flex justify-between items-center gap-2"><span>2 Orang</span> <b class="text-slate-800 bg-white border border-slate-200 px-1.5 py-0.5 rounded text-[11px] whitespace-nowrap shrink-0">Skor 2</b></li>
                    <li class="flex justify-between items-center gap-2"><span>1 Orang</span> <b class="text-slate-800 bg-white border border-slate-200 px-1.5 py-0.5 rounded text-[11px] whitespace-nowrap shrink-0">Skor 1</b></li>
                </ul>
            </div>

            <!-- C3 -->
            <div class="p-4 bg-slate-50/70 border border-slate-200 rounded-xl space-y-2.5">
                <div class="flex items-center justify-between font-bold text-slate-900 border-b border-slate-200 pb-2">
                    <span>C3: Kondisi Rumah</span>
                    <span class="text-[10px] bg-slate-200 text-slate-700 font-medium px-1.5 py-0.5 rounded">Benefit</span>
                </div>
                <ul class="space-y-1.5 text-slate-600">
                    <li class="flex justify-between items-center gap-2"><span>Tidak Layak</span> <b class="text-slate-800 bg-white border border-slate-200 px-1.5 py-0.5 rounded text-[11px] whitespace-nowrap shrink-0">Skor 5</b></li>
                    <li class="flex justify-between items-center gap-2"><span>Dinding Kayu</span> <b class="text-slate-800 bg-white border border-slate-200 px-1.5 py-0.5 rounded text-[11px] whitespace-nowrap shrink-0">Skor 4</b></li>
                    <li class="flex justify-between items-center gap-2"><span>Dinding Batu Atap Seng</span> <b class="text-slate-800 bg-white border border-slate-200 px-1.5 py-0.5 rounded text-[11px] whitespace-nowrap shrink-0">Skor 3</b></li>
                    <li class="flex justify-between items-center gap-2"><span>Dinding Batu Atap Genteng</span> <b class="text-slate-800 bg-white border border-slate-200 px-1.5 py-0.5 rounded text-[11px] whitespace-nowrap shrink-0">Skor 2</b></li>
                    <li class="flex justify-between items-center gap-2"><span>Tembok Keramik</span> <b class="text-slate-800 bg-white border border-slate-200 px-1.5 py-0.5 rounded text-[11px] whitespace-nowrap shrink-0">Skor 1</b></li>
                </ul>
            </div>

            <!-- C4 -->
            <div class="p-4 bg-slate-50/70 border border-slate-200 rounded-xl space-y-2.5">
                <div class="flex items-center justify-between font-bold text-slate-900 border-b border-slate-200 pb-2">
                    <span>C4: Prestasi Akademik</span>
                    <span class="text-[10px] bg-slate-200 text-slate-700 font-medium px-1.5 py-0.5 rounded">Benefit</span>
                </div>
                <ul class="space-y-1.5 text-slate-600">
                    <li class="flex justify-between items-center gap-2"><span>Juara 1 - 3 Kabupaten</span> <b class="text-slate-800 bg-white border border-slate-200 px-1.5 py-0.5 rounded text-[11px] whitespace-nowrap shrink-0">Skor 5</b></li>
                    <li class="flex justify-between items-center gap-2"><span>Juara Harapan</span> <b class="text-slate-800 bg-white border border-slate-200 px-1.5 py-0.5 rounded text-[11px] whitespace-nowrap shrink-0">Skor 4</b></li>
                    <li class="flex justify-between items-center gap-2"><span>Juara Kelas 1 - 3</span> <b class="text-slate-800 bg-white border border-slate-200 px-1.5 py-0.5 rounded text-[11px] whitespace-nowrap shrink-0">Skor 3</b></li>
                    <li class="flex justify-between items-center gap-2"><span>Peringkat 10 Besar</span> <b class="text-slate-800 bg-white border border-slate-200 px-1.5 py-0.5 rounded text-[11px] whitespace-nowrap shrink-0">Skor 2</b></li>
                    <li class="flex justify-between items-center gap-2"><span>Peringkat 20 Besar</span> <b class="text-slate-800 bg-white border border-slate-200 px-1.5 py-0.5 rounded text-[11px] whitespace-nowrap shrink-0">Skor 1</b></li>
                </ul>
            </div>

            <!-- C5 -->
            <div class="p-4 bg-slate-50/70 border border-slate-200 rounded-xl space-y-2.5">
                <div class="flex items-center justify-between font-bold text-slate-900 border-b border-slate-200 pb-2">
                    <span>C5: Jarak ke Sekolah</span>
                    <span class="text-[10px] bg-slate-200 text-slate-700 font-medium px-1.5 py-0.5 rounded">Benefit</span>
                </div>
                <ul class="space-y-1.5 text-slate-600">
                    <li class="flex justify-between items-center gap-2"><span>&gt; 5 km</span> <b class="text-slate-800 bg-white border border-slate-200 px-1.5 py-0.5 rounded text-[11px] whitespace-nowrap shrink-0">Skor 5</b></li>
                    <li class="flex justify-between items-center gap-2"><span>3 – 5 km</span> <b class="text-slate-800 bg-white border border-slate-200 px-1.5 py-0.5 rounded text-[11px] whitespace-nowrap shrink-0">Skor 4</b></li>
                    <li class="flex justify-between items-center gap-2"><span>2 km</span> <b class="text-slate-800 bg-white border border-slate-200 px-1.5 py-0.5 rounded text-[11px] whitespace-nowrap shrink-0">Skor 3</b></li>
                    <li class="flex justify-between items-center gap-2"><span>1 km</span> <b class="text-slate-800 bg-white border border-slate-200 px-1.5 py-0.5 rounded text-[11px] whitespace-nowrap shrink-0">Skor 2</b></li>
                    <li class="flex justify-between items-center gap-2"><span>&lt; 1 km</span> <b class="text-slate-800 bg-white border border-slate-200 px-1.5 py-0.5 rounded text-[11px] whitespace-nowrap shrink-0">Skor 1</b></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php require_once "footer.php"; ?>