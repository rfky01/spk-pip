<?php
// ranking.php - Halaman Hasil Perankingan Calon Penerima PIP (AHP)
$page_title = "Hasil Ranking AHP";
require_once "header.php";

$msg = "";
$msg_type = "";

// A. Ubah Kuota Penerima PIP
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_kuota') {
    $kuota_baru = (int)($_POST['kuota_pip'] ?? 27);
    if ($kuota_baru > 0) {
        mysqli_query($koneksi, "UPDATE `pengaturan` SET `kuota_pip` = $kuota_baru WHERE `id` = 1");
        $pengaturan['kuota_pip'] = $kuota_baru;
        $msg = "Kuota penerima PIP berhasil diubah menjadi $kuota_baru siswa!";
        $msg_type = "success";
    }
}

// B. Verifikasi Cepat Langsung dari Halaman Ranking
if (isset($_GET['action']) && $_GET['action'] === 'verifikasi_siswa') {
    $id_v = (int)($_GET['id'] ?? 0);
    if ($id_v > 0) {
        mysqli_query($koneksi, "UPDATE `calon_penerima` SET `status_verifikasi` = 'Terverifikasi' WHERE `id_siswa` = $id_v");
        $msg = "Pengajuan siswa berhasil disetujui & langsung masuk pemeringkatan resmi!";
        $msg_type = "success";
    }
}

require_once "sidebar.php";

// Ambil bobot kriteria AHP dari database
$res_kriteria = mysqli_query($koneksi, "SELECT * FROM `kriteria` ORDER BY `kode_kriteria` ASC");
$kriteria_db = [];
while ($row = mysqli_fetch_assoc($res_kriteria)) {
    $kriteria_db[$row['kode_kriteria']] = (float)$row['bobot'];
}

// Ambil seluruh siswa dan hitung skor AHP masing-masing
$res_semua = mysqli_query($koneksi, "SELECT * FROM `calon_penerima` ORDER BY `id_siswa` ASC");
$semua_siswa = [];
while ($s = mysqli_fetch_assoc($res_semua)) {
    $skor = ($s['penghasilan'] * ($kriteria_db['C1'] ?? 0.4165)) +
            ($s['tanggungan']  * ($kriteria_db['C2'] ?? 0.2619)) +
            ($s['kondisi_rumah']* ($kriteria_db['C3'] ?? 0.1608)) +
            ($s['prestasi']    * ($kriteria_db['C4'] ?? 0.0985)) +
            ($s['jarak']       * ($kriteria_db['C5'] ?? 0.0623));
    $s['skor_akhir'] = round($skor, 4);
    
    // Simpan total_skor ke DB
    $id_s = $s['id_siswa'];
    mysqli_query($koneksi, "UPDATE `calon_penerima` SET `total_skor` = {$s['skor_akhir']} WHERE `id_siswa` = $id_s");
    
    $semua_siswa[] = $s;
}

// Urutkan siswa berdasarkan skor tertinggi
usort($semua_siswa, function($a, $b) {
    return $b['skor_akhir'] <=> $a['skor_akhir'];
});

// Pisahkan berdasarkan status verifikasi
$siswa_terverifikasi = [];
$siswa_menunggu = [];
$siswa_ditolak = [];
$rank_counter = 1;

foreach ($semua_siswa as $s) {
    $id_s = $s['id_siswa'];
    if ($s['status_verifikasi'] === 'Terverifikasi') {
        $s['ranking'] = $rank_counter;
        mysqli_query($koneksi, "UPDATE `calon_penerima` SET `ranking` = $rank_counter WHERE `id_siswa` = $id_s");
        $rank_counter++;
        $siswa_terverifikasi[] = $s;
    } elseif ($s['status_verifikasi'] === 'Ditolak') {
        $s['ranking'] = null;
        mysqli_query($koneksi, "UPDATE `calon_penerima` SET `ranking` = NULL WHERE `id_siswa` = $id_s");
        $siswa_ditolak[] = $s;
    } else {
        // Menunggu Verifikasi
        $s['ranking'] = null;
        mysqli_query($koneksi, "UPDATE `calon_penerima` SET `ranking` = NULL WHERE `id_siswa` = $id_s");
        $siswa_menunggu[] = $s;
    }
}

$count_terverifikasi = count($siswa_terverifikasi);
$count_menunggu = count($siswa_menunggu);
$count_ditolak = count($siswa_ditolak);
$count_all = count($semua_siswa);

$kuota = (int)($pengaturan['kuota_pip'] ?? 27);

// Pisahkan siswa terverifikasi menjadi Prioritas Lolos dan Cadangan berdasarkan Kuota
$siswa_lolos = [];
$siswa_cadangan = [];
foreach ($siswa_terverifikasi as $st) {
    if ($st['ranking'] !== null && $st['ranking'] <= $kuota) {
        $siswa_lolos[] = $st;
    } else {
        $siswa_cadangan[] = $st;
    }
}
$count_lolos = count($siswa_lolos);
$count_cadangan = count($siswa_cadangan);

// Filter Tab (default: lolos)
$tab_filter = $_GET['tab'] ?? 'lolos';
if ($tab_filter === 'cadangan') {
    $tampil_siswa = $siswa_cadangan;
} elseif ($tab_filter === 'terverifikasi') {
    $tampil_siswa = $siswa_terverifikasi;
} elseif ($tab_filter === 'menunggu') {
    $tampil_siswa = $siswa_menunggu;
} elseif ($tab_filter === 'ditolak') {
    $tampil_siswa = $siswa_ditolak;
} elseif ($tab_filter === 'all') {
    $tampil_siswa = $semua_siswa;
} else {
    $tab_filter = 'lolos';
    $tampil_siswa = $siswa_lolos;
}

// Konfigurasi Pagination (10 data per halaman)
$limit_ranking = 10;
$total_tampil_ranking = count($tampil_siswa);
$total_pages_ranking = max(1, (int)ceil($total_tampil_ranking / $limit_ranking));
$page_ranking = max(1, min((int)($_GET['page'] ?? 1), $total_pages_ranking));
$offset_ranking = ($page_ranking - 1) * $limit_ranking;
$halaman_siswa = array_slice($tampil_siswa, $offset_ranking, $limit_ranking);
?>

<div class="space-y-6 w-full">
    <?php if (!empty($msg)): ?>
        <?php toast_notifikasi($msg, $msg_type); ?>
    <?php endif; ?>


    <!-- Banner Peringatan jika ada berkas Menunggu Verifikasi -->
    <?php if ($count_menunggu > 0): ?>
        <div class="p-4 bg-amber-950/60 border border-amber-600/70 text-amber-200 rounded-xl text-xs flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-md">
            <div class="flex items-center gap-2.5">
                <i class="fa-solid fa-clock text-amber-400 text-base"></i>
                <span>Terdapat <b class="text-white"><?= $count_menunggu ?> berkas pengajuan</b> berstatus <b class="text-white">Menunggu Verifikasi</b>. Siswa yang belum diverifikasi belum dimasukkan ke kuota resmi.</span>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <a href="ranking.php?tab=menunggu" class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-bold text-xs shadow-sm transition-colors">
                    Lihat Berkas (<?= $count_menunggu ?>)
                </a>
                <a href="data_calon_penerima.php?status=menunggu" class="px-3 py-1.5 bg-[#1E3A5F] hover:bg-[#274872] border border-[#2E5A8F] text-amber-200 rounded-lg font-bold text-xs transition-colors">
                    Kelola di Data Siswa
                </a>
            </div>
        </div>
    <?php endif; ?>

    <!-- Header & Form Kuota -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Hasil Ranking Penerima PIP (AHP)</h1>
            <p class="text-xs text-slate-500 mt-1">Perangkingan Nilai Alternatif Siswa Berdasarkan Bobot Analytical Hierarchy Process</p>
        </div>
        <!-- Form Ubah Kuota Bantuan -->
        <form action="ranking.php?tab=<?= htmlspecialchars($tab_filter) ?>" method="POST" class="flex items-center gap-2 bg-white px-3.5 py-1.5 rounded-xl border border-slate-200 shadow-sm">
            <input type="hidden" name="action" value="update_kuota">
            <label class="text-xs font-semibold text-slate-600 whitespace-nowrap">Kuota Bantuan:</label>
            <input type="number" name="kuota_pip" min="1" max="500" value="<?= $kuota ?>" 
                class="w-16 px-2 py-1 text-xs border border-slate-300 rounded-lg text-center font-bold text-slate-900 focus:outline-none focus:border-slate-800 focus:ring-1 focus:ring-slate-800">
            <button type="submit" class="px-3 py-1 bg-[#162B4D] hover:bg-[#1E3A5F] text-white border border-[#2E5A8F] hover:border-blue-400 rounded-lg text-xs font-semibold shadow-sm transition-all duration-200 hover:scale-[1.02] active:scale-[0.98]">Ubah</button>
        </form>
    </div>

    <div class="flex flex-col lg:flex-row gap-5 items-start w-full">
        <!-- Kolom Kiri: Menu Filter Tabs & Tabel Skor AHP (Lebar Selaras Sempurna) -->
        <div class="flex-1 min-w-0 w-full space-y-3">
            
            <!-- Filter Tab Status Verifikasi & Kelolosan Kuota (Ukuran Diselaraskan dengan Tabel) -->
            <div class="flex items-center gap-1.5 overflow-x-auto pb-1 w-full">
                <a href="ranking.php?tab=lolos" class="flex-1 min-w-fit px-2.5 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap transition-colors inline-flex items-center justify-center gap-1.5 <?= $tab_filter === 'lolos' ? 'bg-[#162B4D] text-white border border-[#3B82F6] shadow-sm' : 'bg-[#112240] text-slate-300 hover:bg-[#162B4D] hover:text-white border border-[#1E3A5F]' ?>">
                    <span>Prioritas Lolos</span>
                    <span class="px-1.5 py-0.5 rounded-full text-[11px] font-bold transition-colors <?= $tab_filter === 'lolos' ? 'bg-[#1E3A5F] text-blue-200 border border-[#2E5A8F]' : 'bg-[#07101E] text-slate-400 border border-[#1E3A5F]' ?>">
                        <?= $count_lolos ?>
                    </span>
                </a>
                <a href="ranking.php?tab=cadangan" class="flex-1 min-w-fit px-2.5 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap transition-colors inline-flex items-center justify-center gap-1.5 <?= $tab_filter === 'cadangan' ? 'bg-[#162B4D] text-white border border-[#3B82F6] shadow-sm' : 'bg-[#112240] text-slate-300 hover:bg-[#162B4D] hover:text-white border border-[#1E3A5F]' ?>">
                    <span>Cadangan</span>
                    <span class="px-1.5 py-0.5 rounded-full text-[11px] font-bold transition-colors <?= $tab_filter === 'cadangan' ? 'bg-[#1E3A5F] text-blue-200 border border-[#2E5A8F]' : 'bg-[#07101E] text-slate-400 border border-[#1E3A5F]' ?>">
                        <?= $count_cadangan ?>
                    </span>
                </a>
                <a href="ranking.php?tab=terverifikasi" class="flex-1 min-w-fit px-2.5 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap transition-colors inline-flex items-center justify-center gap-1.5 <?= $tab_filter === 'terverifikasi' ? 'bg-[#162B4D] text-white border border-[#3B82F6] shadow-sm' : 'bg-[#112240] text-slate-300 hover:bg-[#162B4D] hover:text-white border border-[#1E3A5F]' ?>">
                    <span>Terverifikasi</span>
                    <span class="px-1.5 py-0.5 rounded-full text-[11px] font-bold transition-colors <?= $tab_filter === 'terverifikasi' ? 'bg-[#1E3A5F] text-blue-200 border border-[#2E5A8F]' : 'bg-[#07101E] text-slate-400 border border-[#1E3A5F]' ?>">
                        <?= $count_terverifikasi ?>
                    </span>
                </a>
                <a href="ranking.php?tab=menunggu" class="flex-1 min-w-fit px-2.5 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap transition-colors inline-flex items-center justify-center gap-1.5 <?= $tab_filter === 'menunggu' ? 'bg-[#162B4D] text-white border border-[#3B82F6] shadow-sm' : 'bg-[#112240] text-slate-300 hover:bg-[#162B4D] hover:text-white border border-[#1E3A5F]' ?>">
                    <span>Menunggu <span class="hidden 2xl:inline">Verifikasi</span><span class="2xl:hidden">Verif</span></span>
                    <span class="px-1.5 py-0.5 rounded-full text-[11px] font-bold transition-colors <?= $tab_filter === 'menunggu' ? 'bg-[#1E3A5F] text-blue-200 border border-[#2E5A8F]' : 'bg-[#07101E] text-slate-400 border border-[#1E3A5F]' ?>">
                        <?= $count_menunggu ?>
                    </span>
                </a>
                <a href="ranking.php?tab=ditolak" class="flex-1 min-w-fit px-2.5 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap transition-colors inline-flex items-center justify-center gap-1.5 <?= $tab_filter === 'ditolak' ? 'bg-[#162B4D] text-white border border-[#3B82F6] shadow-sm' : 'bg-[#112240] text-slate-300 hover:bg-[#162B4D] hover:text-white border border-[#1E3A5F]' ?>">
                    <span>Ditolak</span>
                    <span class="px-1.5 py-0.5 rounded-full text-[11px] font-bold transition-colors <?= $tab_filter === 'ditolak' ? 'bg-[#1E3A5F] text-blue-200 border border-[#2E5A8F]' : 'bg-[#07101E] text-slate-400 border border-[#1E3A5F]' ?>">
                        <?= $count_ditolak ?>
                    </span>
                </a>
                <a href="ranking.php?tab=all" class="flex-1 min-w-fit px-2.5 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap transition-colors inline-flex items-center justify-center gap-1.5 <?= $tab_filter === 'all' ? 'bg-[#162B4D] text-white border border-[#3B82F6] shadow-sm' : 'bg-[#112240] text-slate-300 hover:bg-[#162B4D] hover:text-white border border-[#1E3A5F]' ?>">
                    <span>Semua Calon</span>
                    <span class="px-1.5 py-0.5 rounded-full text-[11px] font-bold transition-colors <?= $tab_filter === 'all' ? 'bg-[#1E3A5F] text-blue-200 border border-[#2E5A8F]' : 'bg-[#07101E] text-slate-400 border border-[#1E3A5F]' ?>">
                        <?= $count_all ?>
                    </span>
                </a>
            </div>

            <!-- Tabel Skor AHP & Status Kelolosan (Kiri / Lebar Fleksibel) -->
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm w-full">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
                <div>
                    <h3 class="text-sm font-bold text-slate-800">
                            <?php if ($tab_filter === 'lolos'): ?>
                                Daftar Siswa Prioritas Lolos (Penerima Kuota)
                            <?php elseif ($tab_filter === 'cadangan'): ?>
                                Daftar Siswa Cadangan (Peringkat di Luar Kuota)
                            <?php elseif ($tab_filter === 'terverifikasi'): ?>
                                Daftar Seluruh Siswa Terverifikasi Resmi (Lolos & Cadangan)
                            <?php elseif ($tab_filter === 'menunggu'): ?>
                                Daftar Pengajuan Menunggu Verifikasi Berkas
                            <?php elseif ($tab_filter === 'ditolak'): ?>
                                Daftar Pengajuan Ditolak (Gugur)
                            <?php else: ?>
                                Seluruh Calon Peserta Didik
                            <?php endif; ?>
                        </h3>
                        <p class="text-xs text-slate-400">
                            <?php if ($tab_filter === 'lolos'): ?>
                                Menampilkan penerima kuota (<span class="font-bold text-slate-700"><?= $count_lolos ?> dari <?= $kuota ?> Kuota</span>)
                            <?php elseif ($tab_filter === 'cadangan'): ?>
                                Menampilkan siswa berstatus cadangan (<span class="font-bold text-slate-700"><?= $count_cadangan ?> Siswa</span>)
                            <?php else: ?>
                                Kuota Resmi Sekolah: <span class="font-bold text-slate-700"><?= $kuota ?> Siswa</span>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                    <a href="cetak_laporan.php?kategori=<?= urlencode($tab_filter) ?>&tahun=<?= urlencode($pengaturan['tahun_ajaran'] ?? '2025/2026') ?>" target="_blank" class="px-3.5 py-2 bg-[#162B4D] hover:bg-[#1E3A5F] text-white border border-[#2E5A8F] hover:border-blue-400 rounded-lg text-xs font-semibold shadow-sm transition-all duration-200 hover:scale-[1.02] active:scale-[0.98] flex items-center gap-2 whitespace-nowrap" title="Cetak data yang saat ini tampil di layar (PDF)">
                        <i class="fa-solid fa-print text-blue-400"></i> Cetak Laporan PDF
                    </a>
                    <a href="ekspor_excel.php?kategori=<?= urlencode($tab_filter) ?>&tahun=<?= urlencode($pengaturan['tahun_ajaran'] ?? '2025/2026') ?>" class="px-3.5 py-2 bg-[#112240] hover:bg-[#162B4D] text-slate-200 border border-[#1E3A5F] hover:border-[#2E5A8F] rounded-lg text-xs font-medium shadow-sm transition-all duration-200 hover:scale-[1.02] active:scale-[0.98] flex items-center gap-2 whitespace-nowrap" title="Ekspor data yang saat ini tampil di layar ke Excel (.xls)">
                        <i class="fa-solid fa-file-excel text-emerald-400"></i> Ekspor Excel
                    </a>
                </div>
            </div>

            <div class="overflow-x-auto w-full">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold text-xs">
                            <th class="py-2.5 px-2 text-center w-12">Rank</th>
                            <th class="py-2.5 px-2 text-center w-24">NISN</th>
                            <th class="py-2.5 px-2">Nama Siswa</th>
                            <th class="py-2.5 px-2 text-center">Sekolah Asal</th>
                            <th class="py-2.5 px-2 text-center w-20">Skor AHP</th>
                            <th class="py-2.5 px-2 text-center w-24">Status Berkas</th>
                            <th class="py-2.5 px-2 text-center w-28">Hasil Rekomendasi</th>
                            <?php if ($tab_filter === 'menunggu'): ?>
                                <th class="py-2.5 px-2 text-center w-20">Aksi</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700 font-medium text-xs">
                        <?php if (empty($tampil_siswa)): ?>
                            <tr>
                                <td colspan="<?= $tab_filter === 'menunggu' ? '8' : '7' ?>" class="p-8 text-center text-slate-400">
                                    <i class="fa-solid fa-inbox text-3xl mb-2 text-slate-300 block"></i>
                                    <?php if ($tab_filter === 'lolos'): ?>
                                        Belum ada siswa yang masuk kuota Prioritas Lolos.
                                    <?php elseif ($tab_filter === 'cadangan'): ?>
                                        Tidak ada siswa berstatus cadangan (seluruh siswa terverifikasi masuk kuota).
                                    <?php else: ?>
                                        Tidak ada data pada kategori ini.
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php else:
                            foreach ($halaman_siswa as $siswa): 
                                $st = $siswa['status_verifikasi'] ?? 'Menunggu Verifikasi';
                                $is_terverif = ($st === 'Terverifikasi');
                                $is_ditolak = ($st === 'Ditolak');
                                $is_lolos = ($is_terverif && $siswa['ranking'] !== null && $siswa['ranking'] <= $kuota);
                        ?>
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <td class="py-2.5 px-2 text-center font-bold">
                                <?php if ($is_terverif && $siswa['ranking'] !== null): ?>
                                    <?php if ($siswa['ranking'] === 1): ?>
                                        <span class="inline-flex items-center justify-center w-5 h-5 bg-slate-900 text-white rounded-full text-[11px] font-bold shadow-sm">1</span>
                                    <?php elseif ($siswa['ranking'] === 2): ?>
                                        <span class="inline-flex items-center justify-center w-5 h-5 bg-slate-200 text-slate-800 rounded-full text-[11px] font-bold">2</span>
                                    <?php elseif ($siswa['ranking'] === 3): ?>
                                        <span class="inline-flex items-center justify-center w-5 h-5 bg-slate-100 border border-slate-300 text-slate-700 rounded-full text-[11px] font-bold">3</span>
                                    <?php else: ?>
                                        <span class="text-slate-600 font-mono text-xs"><?= $siswa['ranking'] ?>.</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-slate-400 font-mono">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-2.5 px-2 text-center font-semibold text-slate-600 font-mono text-xs whitespace-nowrap">
                                <?= htmlspecialchars($siswa['nisn']) ?>
                            </td>
                            <td class="py-2.5 px-2 font-bold text-slate-900 min-w-0">
                                <span class="block text-xs font-bold text-slate-900 break-words" style="word-break: break-word; overflow-wrap: anywhere;">
                                    <?= htmlspecialchars($siswa['nama']) ?>
                                </span>
                                <?php if (!empty($siswa['nama_ortu'])): ?>
                                    <span class="block text-[10px] text-slate-400 font-normal break-words" style="word-break: break-word; overflow-wrap: anywhere;">
                                        Wali: <?= htmlspecialchars($siswa['nama_ortu']) ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="py-2.5 px-2 text-center min-w-0">
                                <span class="font-bold text-slate-700 block text-xs break-words" style="word-break: break-word; overflow-wrap: anywhere;">
                                    <?= htmlspecialchars($siswa['sekolah_asal'] ?: '-') ?>
                                </span>
                                <div class="flex items-center justify-center gap-1 text-[10px] text-slate-400">
                                    <span class="font-semibold text-slate-600">Kelas VII</span>
                                    <span>&bull; <?= htmlspecialchars($siswa['tahun'] ?? '2025/2026') ?></span>
                                </div>
                            </td>
                            <td class="py-2.5 px-2 text-center font-mono font-extrabold text-slate-900 text-xs whitespace-nowrap">
                                <?= number_format($siswa['skor_akhir'], 4) ?>
                            </td>
                            <td class="py-2.5 px-2 text-center whitespace-nowrap">
                                <?php if ($is_terverif): ?>
                                    <span class="px-2 py-0.5 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded text-[10px] font-bold">Terverifikasi</span>
                                <?php elseif ($is_ditolak): ?>
                                    <span class="px-2 py-0.5 bg-slate-100 text-slate-600 border border-slate-200 rounded text-[10px] font-bold">Ditolak</span>
                                <?php else: ?>
                                    <span class="px-2 py-0.5 bg-amber-50 text-amber-700 border border-amber-200 rounded text-[10px] font-bold">Menunggu Verif</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-2.5 px-2 text-center whitespace-nowrap">
                                <?php if ($is_terverif): ?>
                                    <?php if ($is_lolos): ?>
                                        <span class="px-2 py-0.5 bg-emerald-50 text-emerald-800 border border-emerald-200 rounded-full text-[10px] font-bold inline-flex items-center gap-1">
                                            <i class="fa-solid fa-check text-emerald-600 text-[9px]"></i> Prioritas Lolos
                                        </span>
                                    <?php else: ?>
                                        <span class="px-2 py-0.5 bg-slate-100 text-slate-600 rounded-full text-[10px] font-medium">
                                            Cadangan
                                        </span>
                                    <?php endif; ?>
                                <?php elseif ($is_ditolak): ?>
                                    <span class="px-2 py-0.5 bg-slate-100 text-slate-500 rounded-full text-[10px] font-medium">
                                        Gugur / Ditolak
                                    </span>
                                <?php else: ?>
                                    <span class="px-2 py-0.5 bg-amber-50/60 text-amber-700 rounded-full text-[10px] font-medium">
                                        Belum Diverifikasi
                                    </span>
                                <?php endif; ?>
                            </td>
                            <?php if ($tab_filter === 'menunggu'): ?>
                                <td class="py-2.5 px-2 text-center whitespace-nowrap">
                                    <a href="ranking.php?tab=menunggu&action=verifikasi_siswa&id=<?= $siswa['id_siswa'] ?>" 
                                       onclick="return konfirmasiAksi(event, this, 'Verifikasi & setujui berkas <?= htmlspecialchars($siswa['nama']) ?>?')"
                                       class="px-2 py-1 bg-slate-900 hover:bg-slate-800 text-white rounded text-[11px] font-bold shadow inline-flex items-center gap-1 transition-colors">
                                        <i class="fa-solid fa-check text-[9px]"></i> Setujui
                                    </a>
                                </td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Hasil Ranking -->
            <?php if ($total_tampil_ranking > 0): 
                $start_display = $offset_ranking + 1;
                $end_display = min($offset_ranking + $limit_ranking, $total_tampil_ranking);
            ?>
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-4 border-t border-slate-100 text-xs">
                <div class="text-slate-500">
                    Menampilkan <span class="font-bold text-slate-800"><?= $start_display ?></span> - <span class="font-bold text-slate-800"><?= $end_display ?></span> dari <span class="font-bold text-slate-800"><?= $total_tampil_ranking ?></span> data siswa
                </div>
                <?php if ($total_pages_ranking > 1): ?>
                <div class="flex items-center gap-1">
                    <?php
                    $url_prev = '?' . http_build_query(array_merge($_GET, ['page' => max(1, $page_ranking - 1)]));
                    $url_next = '?' . http_build_query(array_merge($_GET, ['page' => min($total_pages_ranking, $page_ranking + 1)]));
                    ?>
                    <!-- Prev Button -->
                    <?php if ($page_ranking > 1): ?>
                        <a href="<?= htmlspecialchars($url_prev) ?>" class="px-2.5 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-medium transition-colors flex items-center gap-1">
                            <i class="fa-solid fa-chevron-left text-[10px]"></i> Sebelumnya
                        </a>
                    <?php else: ?>
                        <span class="px-2.5 py-1.5 rounded-lg border border-slate-100 bg-slate-50 text-slate-300 cursor-not-allowed flex items-center gap-1 font-medium">
                            <i class="fa-solid fa-chevron-left text-[10px]"></i> Sebelumnya
                        </span>
                    <?php endif; ?>

                    <!-- Page Numbers -->
                    <div class="flex items-center gap-1">
                        <?php
                        $range = 2;
                        $show_dots_left = false;
                        $show_dots_right = false;

                        for ($p = 1; $p <= $total_pages_ranking; $p++):
                            if ($p == 1 || $p == $total_pages_ranking || ($p >= $page_ranking - $range && $p <= $page_ranking + $range)):
                                $url_page = '?' . http_build_query(array_merge($_GET, ['page' => $p]));
                        ?>
                            <a href="<?= htmlspecialchars($url_page) ?>" class="w-8 h-8 rounded-lg flex items-center justify-center font-bold transition-all <?= $p == $page_ranking ? 'bg-slate-900 text-white shadow-sm' : 'bg-white hover:bg-slate-50 border border-slate-200 text-slate-700' ?>">
                                <?= $p ?>
                            </a>
                        <?php
                            elseif ($p < $page_ranking - $range && !$show_dots_left):
                                $show_dots_left = true;
                                echo '<span class="w-6 text-center text-slate-400 font-bold">...</span>';
                            elseif ($p > $page_ranking + $range && !$show_dots_right):
                                $show_dots_right = true;
                                echo '<span class="w-6 text-center text-slate-400 font-bold">...</span>';
                            endif;
                        endfor;
                        ?>
                    </div>

                    <!-- Next Button -->
                    <?php if ($page_ranking < $total_pages_ranking): ?>
                        <a href="<?= htmlspecialchars($url_next) ?>" class="px-2.5 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-medium transition-colors flex items-center gap-1">
                            Selanjutnya <i class="fa-solid fa-chevron-right text-[10px]"></i>
                        </a>
                    <?php else: ?>
                        <span class="px-2.5 py-1.5 rounded-lg border border-slate-100 bg-slate-50 text-slate-300 cursor-not-allowed flex items-center gap-1 font-medium">
                            Selanjutnya <i class="fa-solid fa-chevron-right text-[10px]"></i>
                        </span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        <!-- Akhir Kolom Kiri -->
    </div>

    <!-- Distribusi Visual Skor AHP (Kanan - Dipersempit & Sejajar Kartu Tabel) -->
    <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-sm w-full lg:w-48 xl:w-52 shrink-0 space-y-3 lg:mt-[42px]">
        <div>
            <h3 class="text-xs font-bold text-slate-800 tracking-tight">Distribusi Skor Resmi</h3>
        </div>
            
            <div class="space-y-2.5 pt-0.5">
                <?php 
                $preview_chart = array_slice($siswa_terverifikasi, 0, 7);
                if (empty($preview_chart)): ?>
                    <div class="text-[11px] text-slate-400 text-center py-5 border border-dashed border-slate-200 rounded-lg">
                        Belum ada data siswa terverifikasi untuk ditampilkan di grafik.
                    </div>
                <?php else:
                    foreach ($preview_chart as $s): 
                        $pct = round(($s['skor_akhir'] / 5.0) * 100);
                ?>
                <div>
                    <div class="flex justify-between items-center text-[11px] mb-1">
                        <span class="font-medium text-slate-700 truncate flex-1 min-w-0 pr-1.5" title="<?= htmlspecialchars($s['nama']) ?>"><?= htmlspecialchars($s['nama']) ?></span>
                        <span class="font-bold text-slate-900 font-mono text-[10px] shrink-0"><?= number_format($s['skor_akhir'], 4) ?></span>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                        <div class="bg-slate-800 h-1.5 rounded-full transition-all duration-300" style="width: <?= $pct ?>%"></div>
                    </div>
                </div>
                <?php endforeach; endif; ?>
            </div>

            <div class="pt-2.5 border-t border-slate-100 space-y-2">
                <button type="button" onclick="openModalPilihanCetak()" class="w-full py-1.5 px-2 bg-[#162B4D] hover:bg-[#1E3A5F] text-white border border-[#2E5A8F] hover:border-blue-400 font-semibold rounded-lg text-[11px] transition-all duration-200 hover:scale-[1.02] active:scale-[0.98] shadow-sm flex items-center justify-center gap-1.5 cursor-pointer whitespace-nowrap">
                    <i class="fa-solid fa-file-pdf text-blue-400 text-xs"></i> Cetak PDF
                </button>
                <button type="button" onclick="openModalPilihanExcel()" class="w-full py-1.5 px-2 bg-[#112240] hover:bg-[#162B4D] text-slate-200 border border-[#1E3A5F] hover:border-[#2E5A8F] font-medium rounded-lg text-[11px] transition-all duration-200 hover:scale-[1.02] active:scale-[0.98] shadow-sm flex items-center justify-center gap-1.5 cursor-pointer whitespace-nowrap">
                    <i class="fa-solid fa-file-excel text-emerald-400 text-xs"></i> Ekspor Excel
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL POPUP PILIHAN KATEGORI CETAK LAPORAN -->
<!-- ========================================== -->
<div id="modal-pilihan-cetak" class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden flex items-center justify-center z-50 p-4">
    <div class="bg-[#112240] rounded-2xl shadow-2xl w-full max-w-lg p-6 border border-[#1E3A5F] max-h-[90vh] overflow-y-auto text-white" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between pb-3 border-b border-[#1E3A5F] mb-4">
            <div>
                <h3 class="font-bold text-white text-base leading-tight">Pilih Kategori Cetak Laporan</h3>
                <p class="text-[11px] text-slate-400 mt-0.5">Tentukan data yang ingin dicetak ke dokumen dinas resmi format PDF</p>
            </div>
            <button onclick="closeModalPilihanCetak()" class="text-slate-400 hover:text-white text-lg transition-colors cursor-pointer w-8 h-8 rounded-lg hover:bg-[#1E3A5F] flex items-center justify-center">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="space-y-2.5">
            <!-- 1. Prioritas Lolos -->
            <a href="cetak_laporan.php?kategori=lolos&tahun=<?= urlencode($pengaturan['tahun_ajaran'] ?? '2025/2026') ?>" target="_blank" onclick="closeModalPilihanCetak()" 
               class="w-full p-3.5 rounded-xl bg-[#162B4D] hover:bg-[#1E3A5F] text-white border border-[#2E5A8F] hover:border-blue-400 transition-all duration-200 hover:scale-[1.01] active:scale-[0.99] shadow-sm flex items-center justify-between group cursor-pointer">
                <div class="text-left">
                    <span class="font-bold text-white text-xs block group-hover:text-blue-200 transition-colors">Prioritas Lolos Saja (Penerima Kuota)</span>
                    <span class="text-[11px] text-slate-300 block mt-0.5">Mencetak khusus siswa penerima kuota resmi sekolah (Maks. <?= $kuota ?> Siswa)</span>
                </div>
                <div class="shrink-0 ml-3">
                    <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-[#112240] text-blue-300 border border-[#1E3A5F] group-hover:border-[#2E5A8F] transition-colors whitespace-nowrap">
                        <?= $count_lolos ?> Siswa
                    </span>
                </div>
            </a>

            <!-- 2. Peserta Cadangan -->
            <a href="cetak_laporan.php?kategori=cadangan&tahun=<?= urlencode($pengaturan['tahun_ajaran'] ?? '2025/2026') ?>" target="_blank" onclick="closeModalPilihanCetak()" 
               class="w-full p-3.5 rounded-xl bg-[#162B4D] hover:bg-[#1E3A5F] text-white border border-[#2E5A8F] hover:border-blue-400 transition-all duration-200 hover:scale-[1.01] active:scale-[0.99] shadow-sm flex items-center justify-between group cursor-pointer">
                <div class="text-left">
                    <span class="font-bold text-white text-xs block group-hover:text-blue-200 transition-colors">Peserta Cadangan Saja</span>
                    <span class="text-[11px] text-slate-300 block mt-0.5">Mencetak daftar siswa cadangan di luar kuota utama</span>
                </div>
                <div class="shrink-0 ml-3">
                    <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-[#112240] text-blue-300 border border-[#1E3A5F] group-hover:border-[#2E5A8F] transition-colors whitespace-nowrap">
                        <?= $count_cadangan ?> Siswa
                    </span>
                </div>
            </a>

            <!-- 3. Semua Terverifikasi -->
            <a href="cetak_laporan.php?kategori=terverifikasi&tahun=<?= urlencode($pengaturan['tahun_ajaran'] ?? '2025/2026') ?>" target="_blank" onclick="closeModalPilihanCetak()" 
               class="w-full p-3.5 rounded-xl bg-[#162B4D] hover:bg-[#1E3A5F] text-white border border-[#2E5A8F] hover:border-blue-400 transition-all duration-200 hover:scale-[1.01] active:scale-[0.99] shadow-sm flex items-center justify-between group cursor-pointer">
                <div class="text-left">
                    <span class="font-bold text-white text-xs block group-hover:text-blue-200 transition-colors">Semua Terverifikasi (Lolos & Cadangan)</span>
                    <span class="text-[11px] text-slate-300 block mt-0.5">Mencetak seluruh peserta terverifikasi berurutan sesuai ranking skor AHP</span>
                </div>
                <div class="shrink-0 ml-3">
                    <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-[#112240] text-blue-300 border border-[#1E3A5F] group-hover:border-[#2E5A8F] transition-colors whitespace-nowrap">
                        <?= $count_terverifikasi ?> Siswa
                    </span>
                </div>
            </a>

            <!-- 4. Menunggu Verifikasi -->
            <a href="cetak_laporan.php?kategori=menunggu&tahun=<?= urlencode($pengaturan['tahun_ajaran'] ?? '2025/2026') ?>" target="_blank" onclick="closeModalPilihanCetak()" 
               class="w-full p-3.5 rounded-xl bg-[#162B4D] hover:bg-[#1E3A5F] text-white border border-[#2E5A8F] hover:border-blue-400 transition-all duration-200 hover:scale-[1.01] active:scale-[0.99] shadow-sm flex items-center justify-between group cursor-pointer">
                <div class="text-left">
                    <span class="font-bold text-white text-xs block group-hover:text-blue-200 transition-colors">Menunggu Verifikasi Berkas</span>
                    <span class="text-[11px] text-slate-300 block mt-0.5">Mencetak daftar berkas pendaftaran yang belum diverifikasi</span>
                </div>
                <div class="shrink-0 ml-3">
                    <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-[#112240] text-blue-300 border border-[#1E3A5F] group-hover:border-[#2E5A8F] transition-colors whitespace-nowrap">
                        <?= $count_menunggu ?> Siswa
                    </span>
                </div>
            </a>

            <!-- 5. Berkas Ditolak -->
            <a href="cetak_laporan.php?kategori=ditolak&tahun=<?= urlencode($pengaturan['tahun_ajaran'] ?? '2025/2026') ?>" target="_blank" onclick="closeModalPilihanCetak()" 
               class="w-full p-3.5 rounded-xl bg-[#162B4D] hover:bg-[#1E3A5F] text-white border border-[#2E5A8F] hover:border-blue-400 transition-all duration-200 hover:scale-[1.01] active:scale-[0.99] shadow-sm flex items-center justify-between group cursor-pointer">
                <div class="text-left">
                    <span class="font-bold text-white text-xs block group-hover:text-blue-200 transition-colors">Berkas Ditolak (Gugur)</span>
                    <span class="text-[11px] text-slate-300 block mt-0.5">Mencetak daftar calon siswa yang tidak memenuhi kriteria</span>
                </div>
                <div class="shrink-0 ml-3">
                    <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-[#112240] text-blue-300 border border-[#1E3A5F] group-hover:border-[#2E5A8F] transition-colors whitespace-nowrap">
                        <?= $count_ditolak ?> Siswa
                    </span>
                </div>
            </a>

            <!-- 6. Semua Calon -->
            <a href="cetak_laporan.php?kategori=all&tahun=<?= urlencode($pengaturan['tahun_ajaran'] ?? '2025/2026') ?>" target="_blank" onclick="closeModalPilihanCetak()" 
               class="w-full p-3.5 rounded-xl bg-[#162B4D] hover:bg-[#1E3A5F] text-white border border-[#2E5A8F] hover:border-blue-400 transition-all duration-200 hover:scale-[1.01] active:scale-[0.99] shadow-sm flex items-center justify-between group cursor-pointer">
                <div class="text-left">
                    <span class="font-bold text-white text-xs block group-hover:text-blue-200 transition-colors">Seluruh Calon Pendaftar (Semua Data)</span>
                    <span class="text-[11px] text-slate-300 block mt-0.5">Rekapitulasi lengkap seluruh pengajuan pendaftaran siswa</span>
                </div>
                <div class="shrink-0 ml-3">
                    <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-[#112240] text-blue-300 border border-[#1E3A5F] group-hover:border-[#2E5A8F] transition-colors whitespace-nowrap">
                        <?= $count_all ?> Siswa
                    </span>
                </div>
            </a>
        </div>

        <div class="pt-4 mt-4 border-t border-[#1E3A5F] flex justify-end">
            <button type="button" onclick="closeModalPilihanCetak()" class="px-4 py-2 bg-[#112240] hover:bg-[#162B4D] text-slate-300 border border-[#1E3A5F] hover:border-[#2E5A8F] font-semibold rounded-xl text-xs transition-all duration-200 hover:scale-[1.02] active:scale-[0.98] cursor-pointer">
                Batal
            </button>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL POPUP PILIHAN KATEGORI EKSPOR EXCEL  -->
<!-- ========================================== -->
<div id="modal-pilihan-excel" class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden flex items-center justify-center z-50 p-4">
    <div class="bg-[#112240] rounded-2xl shadow-2xl w-full max-w-lg p-6 border border-[#1E3A5F] max-h-[90vh] overflow-y-auto text-white" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between pb-3 border-b border-[#1E3A5F] mb-4">
            <div>
                <h3 class="font-bold text-white text-base leading-tight">Pilih Kategori Ekspor Excel</h3>
                <p class="text-[11px] text-slate-400 mt-0.5">Unduh data perankingan dalam format spreadsheet Excel (.xls)</p>
            </div>
            <button onclick="closeModalPilihanExcel()" class="text-slate-400 hover:text-white text-lg transition-colors cursor-pointer w-8 h-8 rounded-lg hover:bg-[#1E3A5F] flex items-center justify-center">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="space-y-2.5">
            <!-- 1. Prioritas Lolos -->
            <a href="ekspor_excel.php?kategori=lolos&tahun=<?= urlencode($pengaturan['tahun_ajaran'] ?? '2025/2026') ?>" onclick="closeModalPilihanExcel()" 
               class="w-full p-3.5 rounded-xl bg-[#162B4D] hover:bg-[#1E3A5F] text-white border border-[#2E5A8F] hover:border-emerald-400 transition-all duration-200 hover:scale-[1.01] active:scale-[0.99] shadow-sm flex items-center justify-between group cursor-pointer">
                <div class="text-left">
                    <span class="font-bold text-white text-xs block group-hover:text-emerald-200 transition-colors">Prioritas Lolos Saja (Penerima Kuota)</span>
                    <span class="text-[11px] text-slate-300 block mt-0.5">Unduh khusus siswa penerima kuota resmi sekolah (Maks. <?= $kuota ?> Siswa)</span>
                </div>
                <div class="shrink-0 ml-3">
                    <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-[#112240] text-emerald-300 border border-[#1E3A5F] group-hover:border-[#2E5A8F] transition-colors whitespace-nowrap">
                        <?= $count_lolos ?> Siswa
                    </span>
                </div>
            </a>

            <!-- 2. Peserta Cadangan -->
            <a href="ekspor_excel.php?kategori=cadangan&tahun=<?= urlencode($pengaturan['tahun_ajaran'] ?? '2025/2026') ?>" onclick="closeModalPilihanExcel()" 
               class="w-full p-3.5 rounded-xl bg-[#162B4D] hover:bg-[#1E3A5F] text-white border border-[#2E5A8F] hover:border-emerald-400 transition-all duration-200 hover:scale-[1.01] active:scale-[0.99] shadow-sm flex items-center justify-between group cursor-pointer">
                <div class="text-left">
                    <span class="font-bold text-white text-xs block group-hover:text-emerald-200 transition-colors">Peserta Cadangan Saja</span>
                    <span class="text-[11px] text-slate-300 block mt-0.5">Unduh daftar siswa cadangan di luar kuota utama</span>
                </div>
                <div class="shrink-0 ml-3">
                    <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-[#112240] text-emerald-300 border border-[#1E3A5F] group-hover:border-[#2E5A8F] transition-colors whitespace-nowrap">
                        <?= $count_cadangan ?> Siswa
                    </span>
                </div>
            </a>

            <!-- 3. Semua Terverifikasi -->
            <a href="ekspor_excel.php?kategori=terverifikasi&tahun=<?= urlencode($pengaturan['tahun_ajaran'] ?? '2025/2026') ?>" onclick="closeModalPilihanExcel()" 
               class="w-full p-3.5 rounded-xl bg-[#162B4D] hover:bg-[#1E3A5F] text-white border border-[#2E5A8F] hover:border-emerald-400 transition-all duration-200 hover:scale-[1.01] active:scale-[0.99] shadow-sm flex items-center justify-between group cursor-pointer">
                <div class="text-left">
                    <span class="font-bold text-white text-xs block group-hover:text-emerald-200 transition-colors">Semua Terverifikasi (Lolos & Cadangan)</span>
                    <span class="text-[11px] text-slate-300 block mt-0.5">Unduh seluruh peserta terverifikasi berurutan sesuai ranking skor AHP</span>
                </div>
                <div class="shrink-0 ml-3">
                    <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-[#112240] text-emerald-300 border border-[#1E3A5F] group-hover:border-[#2E5A8F] transition-colors whitespace-nowrap">
                        <?= $count_terverifikasi ?> Siswa
                    </span>
                </div>
            </a>

            <!-- 4. Menunggu Verifikasi -->
            <a href="ekspor_excel.php?kategori=menunggu&tahun=<?= urlencode($pengaturan['tahun_ajaran'] ?? '2025/2026') ?>" onclick="closeModalPilihanExcel()" 
               class="w-full p-3.5 rounded-xl bg-[#162B4D] hover:bg-[#1E3A5F] text-white border border-[#2E5A8F] hover:border-emerald-400 transition-all duration-200 hover:scale-[1.01] active:scale-[0.99] shadow-sm flex items-center justify-between group cursor-pointer">
                <div class="text-left">
                    <span class="font-bold text-white text-xs block group-hover:text-emerald-200 transition-colors">Menunggu Verifikasi Berkas</span>
                    <span class="text-[11px] text-slate-300 block mt-0.5">Unduh daftar berkas pendaftaran yang belum diverifikasi</span>
                </div>
                <div class="shrink-0 ml-3">
                    <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-[#112240] text-emerald-300 border border-[#1E3A5F] group-hover:border-[#2E5A8F] transition-colors whitespace-nowrap">
                        <?= $count_menunggu ?> Siswa
                    </span>
                </div>
            </a>

            <!-- 5. Berkas Ditolak -->
            <a href="ekspor_excel.php?kategori=ditolak&tahun=<?= urlencode($pengaturan['tahun_ajaran'] ?? '2025/2026') ?>" onclick="closeModalPilihanExcel()" 
               class="w-full p-3.5 rounded-xl bg-[#162B4D] hover:bg-[#1E3A5F] text-white border border-[#2E5A8F] hover:border-emerald-400 transition-all duration-200 hover:scale-[1.01] active:scale-[0.99] shadow-sm flex items-center justify-between group cursor-pointer">
                <div class="text-left">
                    <span class="font-bold text-white text-xs block group-hover:text-emerald-200 transition-colors">Berkas Ditolak (Gugur)</span>
                    <span class="text-[11px] text-slate-300 block mt-0.5">Unduh daftar calon siswa yang ditolak / tidak memenuhi kriteria</span>
                </div>
                <div class="shrink-0 ml-3">
                    <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-[#112240] text-emerald-300 border border-[#1E3A5F] group-hover:border-[#2E5A8F] transition-colors whitespace-nowrap">
                        <?= $count_ditolak ?> Siswa
                    </span>
                </div>
            </a>

            <!-- 6. Semua Calon -->
            <a href="ekspor_excel.php?kategori=all&tahun=<?= urlencode($pengaturan['tahun_ajaran'] ?? '2025/2026') ?>" onclick="closeModalPilihanExcel()" 
               class="w-full p-3.5 rounded-xl bg-[#162B4D] hover:bg-[#1E3A5F] text-white border border-[#2E5A8F] hover:border-emerald-400 transition-all duration-200 hover:scale-[1.01] active:scale-[0.99] shadow-sm flex items-center justify-between group cursor-pointer">
                <div class="text-left">
                    <span class="font-bold text-white text-xs block group-hover:text-emerald-200 transition-colors">Seluruh Calon Pendaftar (Semua Data)</span>
                    <span class="text-[11px] text-slate-300 block mt-0.5">Rekapitulasi lengkap seluruh pengajuan pendaftaran siswa</span>
                </div>
                <div class="shrink-0 ml-3">
                    <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-[#112240] text-emerald-300 border border-[#1E3A5F] group-hover:border-[#2E5A8F] transition-colors whitespace-nowrap">
                        <?= $count_all ?> Siswa
                    </span>
                </div>
            </a>
        </div>

        <div class="pt-4 mt-4 border-t border-[#1E3A5F] flex justify-end">
            <button type="button" onclick="closeModalPilihanExcel()" class="px-4 py-2 bg-[#112240] hover:bg-[#162B4D] text-slate-300 border border-[#1E3A5F] hover:border-[#2E5A8F] font-semibold rounded-xl text-xs transition-all duration-200 hover:scale-[1.02] active:scale-[0.98] cursor-pointer">
                Batal
            </button>
        </div>
    </div>
</div>

<script>
    // Modal PDF
    function openModalPilihanCetak() {
        document.getElementById('modal-pilihan-cetak').classList.remove('hidden');
    }
    function closeModalPilihanCetak() {
        document.getElementById('modal-pilihan-cetak').classList.add('hidden');
    }

    // Modal Excel
    function openModalPilihanExcel() {
        document.getElementById('modal-pilihan-excel').classList.remove('hidden');
    }
    function closeModalPilihanExcel() {
        document.getElementById('modal-pilihan-excel').classList.add('hidden');
    }

    // Tutup saat klik backdrop
    document.getElementById('modal-pilihan-cetak').addEventListener('click', function(e) {
        if (e.target === this) {
            closeModalPilihanCetak();
        }
    });
    document.getElementById('modal-pilihan-excel').addEventListener('click', function(e) {
        if (e.target === this) {
            closeModalPilihanExcel();
        }
    });

    // Tutup dengan Escape
    window.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeModalPilihanCetak();
            closeModalPilihanExcel();
        }
    });
</script>

<?php require_once "footer.php"; ?>
