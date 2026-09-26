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
        <div class="p-4 rounded-xl flex items-center justify-between shadow-sm <?= $msg_type === 'success' ? 'bg-emerald-50 border border-emerald-200 text-emerald-800' : 'bg-red-50 border border-red-200 text-red-800' ?>">
            <div class="flex items-center gap-3">
                <i class="fa-solid <?= $msg_type === 'success' ? 'fa-circle-check text-emerald-600' : 'fa-circle-exclamation text-red-600' ?> text-lg"></i>
                <span class="text-sm font-medium"><?= htmlspecialchars($msg) ?></span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-gray-400 hover:text-gray-600"><i class="fa-solid fa-xmark"></i></button>
        </div>
    <?php endif; ?>

    <!-- Banner Peringatan jika ada berkas Menunggu Verifikasi -->
    <?php if ($count_menunggu > 0): ?>
        <div class="p-4 bg-amber-50/80 border border-amber-200/80 text-amber-900 rounded-xl text-xs flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-sm">
            <div class="flex items-center gap-2.5">
                <i class="fa-solid fa-clock text-amber-600 text-base"></i>
                <span>Terdapat <b><?= $count_menunggu ?> berkas pengajuan</b> berstatus <b>Menunggu Verifikasi</b>. Siswa yang belum diverifikasi belum dimasukkan ke kuota resmi.</span>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <a href="ranking.php?tab=menunggu" class="px-3 py-1.5 bg-slate-900 hover:bg-slate-800 text-white rounded-lg font-bold text-xs transition-colors">
                    Lihat Berkas (<?= $count_menunggu ?>)
                </a>
                <a href="data_calon_penerima.php?status=menunggu" class="px-3 py-1.5 bg-white border border-amber-300 text-amber-900 hover:bg-amber-100 rounded-lg font-bold text-xs transition-colors">
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
            <button type="submit" class="px-3 py-1 bg-slate-900 hover:bg-slate-800 text-white rounded-lg text-xs font-medium transition-colors">Ubah</button>
        </form>
    </div>

    <!-- Filter Tab Status Verifikasi & Kelolosan Kuota -->
    <div class="flex items-center gap-2 overflow-x-auto pb-1">
        <a href="ranking.php?tab=lolos" class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-colors whitespace-nowrap inline-flex items-center <?= $tab_filter === 'lolos' ? 'bg-slate-900 text-white shadow-sm' : 'bg-slate-50 border border-slate-200 text-slate-700 hover:bg-slate-100 hover:text-slate-900' ?>">
            <span>Prioritas Lolos</span>
            <span class="ml-2 px-2 py-0.5 rounded-full text-[11px] font-bold transition-colors <?= $tab_filter === 'lolos' ? 'bg-white text-slate-900 shadow-sm' : 'bg-slate-200/90 text-slate-800' ?>">
                <?= $count_lolos ?>
            </span>
        </a>
        <a href="ranking.php?tab=cadangan" class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-colors whitespace-nowrap inline-flex items-center <?= $tab_filter === 'cadangan' ? 'bg-slate-900 text-white shadow-sm' : 'bg-slate-50 border border-slate-200 text-slate-700 hover:bg-slate-100 hover:text-slate-900' ?>">
            <span>Cadangan</span>
            <span class="ml-2 px-2 py-0.5 rounded-full text-[11px] font-bold transition-colors <?= $tab_filter === 'cadangan' ? 'bg-white text-slate-900 shadow-sm' : 'bg-slate-200/90 text-slate-800' ?>">
                <?= $count_cadangan ?>
            </span>
        </a>
        <a href="ranking.php?tab=terverifikasi" class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-colors whitespace-nowrap inline-flex items-center <?= $tab_filter === 'terverifikasi' ? 'bg-slate-900 text-white shadow-sm' : 'bg-slate-50 border border-slate-200 text-slate-700 hover:bg-slate-100 hover:text-slate-900' ?>">
            <span>Terverifikasi</span>
            <span class="ml-2 px-2 py-0.5 rounded-full text-[11px] font-bold transition-colors <?= $tab_filter === 'terverifikasi' ? 'bg-white text-slate-900 shadow-sm' : 'bg-slate-200/90 text-slate-800' ?>">
                <?= $count_terverifikasi ?>
            </span>
        </a>
        <a href="ranking.php?tab=menunggu" class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-colors whitespace-nowrap inline-flex items-center <?= $tab_filter === 'menunggu' ? 'bg-slate-900 text-white shadow-sm' : 'bg-slate-50 border border-slate-200 text-slate-700 hover:bg-slate-100 hover:text-slate-900' ?>">
            <span>Menunggu Verifikasi</span>
            <span class="ml-2 px-2 py-0.5 rounded-full text-[11px] font-bold transition-colors <?= $tab_filter === 'menunggu' ? 'bg-white text-slate-900 shadow-sm' : 'bg-slate-200/90 text-slate-800' ?>">
                <?= $count_menunggu ?>
            </span>
        </a>
        <a href="ranking.php?tab=ditolak" class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-colors whitespace-nowrap inline-flex items-center <?= $tab_filter === 'ditolak' ? 'bg-slate-900 text-white shadow-sm' : 'bg-slate-50 border border-slate-200 text-slate-700 hover:bg-slate-100 hover:text-slate-900' ?>">
            <span>Ditolak</span>
            <span class="ml-2 px-2 py-0.5 rounded-full text-[11px] font-bold transition-colors <?= $tab_filter === 'ditolak' ? 'bg-white text-slate-900 shadow-sm' : 'bg-slate-200/90 text-slate-800' ?>">
                <?= $count_ditolak ?>
            </span>
        </a>
        <a href="ranking.php?tab=all" class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-colors whitespace-nowrap inline-flex items-center <?= $tab_filter === 'all' ? 'bg-slate-900 text-white shadow-sm' : 'bg-slate-50 border border-slate-200 text-slate-700 hover:bg-slate-100 hover:text-slate-900' ?>">
            <span>Semua Calon</span>
            <span class="ml-2 px-2 py-0.5 rounded-full text-[11px] font-bold transition-colors <?= $tab_filter === 'all' ? 'bg-white text-slate-900 shadow-sm' : 'bg-slate-200/90 text-slate-800' ?>">
                <?= $count_all ?>
            </span>
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
        <!-- Tabel Skor AHP & Status Kelolosan (Kiri / Lebar 2 Kolom) -->
        <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm lg:col-span-2 overflow-x-auto">
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
                    <a href="cetak_laporan.php?kategori=<?= urlencode($tab_filter) ?>&tahun=<?= urlencode($pengaturan['tahun_ajaran'] ?? '2025/2026') ?>" target="_blank" class="px-3.5 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-lg text-xs font-medium shadow-sm transition-colors flex items-center gap-2 whitespace-nowrap" title="Cetak data yang saat ini tampil di layar (PDF)">
                        <i class="fa-solid fa-print"></i> Cetak Laporan PDF
                    </a>
                    <a href="ekspor_excel.php?kategori=<?= urlencode($tab_filter) ?>&tahun=<?= urlencode($pengaturan['tahun_ajaran'] ?? '2025/2026') ?>" class="px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-lg text-xs font-medium shadow-sm transition-colors flex items-center gap-2 whitespace-nowrap" title="Ekspor data yang saat ini tampil di layar ke Excel (.xls)">
                        <i class="fa-solid fa-file-excel text-emerald-600"></i> Ekspor Excel
                    </a>
                </div>
            </div>

            <table class="w-full text-left text-sm min-w-[620px]">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold text-xs">
                        <th class="p-2.5 text-center w-14">Rank</th>
                        <th class="p-2.5">NISN</th>
                        <th class="p-2.5">Nama Siswa</th>
                        <th class="p-2.5 text-center">Sekolah Asal</th>
                        <th class="p-2.5 text-center">Skor AHP</th>
                        <th class="p-2.5 text-center">Status Berkas</th>
                        <th class="p-2.5 text-center">Hasil Rekomendasi</th>
                        <?php if ($tab_filter === 'menunggu'): ?>
                            <th class="p-2.5 text-center">Aksi</th>
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
                        <td class="p-2.5 text-center font-bold">
                            <?php if ($is_terverif && $siswa['ranking'] !== null): ?>
                                <?php if ($siswa['ranking'] === 1): ?>
                                    <span class="inline-flex items-center justify-center w-6 h-6 bg-slate-900 text-white rounded-full text-xs font-bold shadow-sm">1</span>
                                <?php elseif ($siswa['ranking'] === 2): ?>
                                    <span class="inline-flex items-center justify-center w-6 h-6 bg-slate-200 text-slate-800 rounded-full text-xs font-bold">2</span>
                                <?php elseif ($siswa['ranking'] === 3): ?>
                                    <span class="inline-flex items-center justify-center w-6 h-6 bg-slate-100 border border-slate-300 text-slate-700 rounded-full text-xs font-bold">3</span>
                                <?php else: ?>
                                    <span class="text-slate-600 font-mono"><?= $siswa['ranking'] ?>.</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-slate-300">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="p-2.5 font-semibold text-slate-600 font-mono"><?= htmlspecialchars($siswa['nisn']) ?></td>
                        <td class="p-2.5 font-bold text-slate-900">
                            <?= htmlspecialchars($siswa['nama']) ?>
                            <?php if (!empty($siswa['nama_ortu'])): ?>
                                <span class="block text-[10px] text-slate-400 font-normal">Wali: <?= htmlspecialchars($siswa['nama_ortu']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="p-2.5 text-center">
                            <span class="font-bold text-slate-700 block"><?= htmlspecialchars($siswa['sekolah_asal'] ?: '-') ?></span>
                            <div class="flex items-center justify-center gap-1">
                                <span class="text-[10px] text-slate-600 font-semibold">Kelas VII</span>
                                <span class="text-[10px] text-slate-400 font-medium">&bull; <?= htmlspecialchars($siswa['tahun'] ?? '2025/2026') ?></span>
                            </div>
                        </td>
                        <td class="p-2.5 text-center font-mono font-extrabold text-slate-900 text-sm"><?= number_format($siswa['skor_akhir'], 4) ?></td>
                        <td class="p-2.5 text-center">
                            <?php if ($is_terverif): ?>
                                <span class="px-2 py-0.5 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded text-[10px] font-bold">Terverifikasi</span>
                            <?php elseif ($is_ditolak): ?>
                                <span class="px-2 py-0.5 bg-slate-100 text-slate-600 border border-slate-200 rounded text-[10px] font-bold">Ditolak</span>
                            <?php else: ?>
                                <span class="px-2 py-0.5 bg-amber-50 text-amber-700 border border-amber-200 rounded text-[10px] font-bold">Menunggu Verif</span>
                            <?php endif; ?>
                        </td>
                        <td class="p-2.5 text-center">
                            <?php if ($is_terverif): ?>
                                <?php if ($is_lolos): ?>
                                    <span class="px-2.5 py-1 bg-emerald-50 text-emerald-800 border border-emerald-200 rounded-full text-[10px] font-bold inline-flex items-center gap-1">
                                        <i class="fa-solid fa-check text-emerald-600"></i> Prioritas Lolos
                                    </span>
                                <?php else: ?>
                                    <span class="px-2.5 py-1 bg-slate-100 text-slate-600 rounded-full text-[10px] font-medium">
                                        Cadangan
                                    </span>
                                <?php endif; ?>
                            <?php elseif ($is_ditolak): ?>
                                <span class="px-2.5 py-1 bg-slate-100 text-slate-500 rounded-full text-[10px] font-medium">
                                    Gugur / Ditolak
                                </span>
                            <?php else: ?>
                                <span class="px-2.5 py-1 bg-amber-50/60 text-amber-700 rounded-full text-[10px] font-medium">
                                    Belum Diverifikasi
                                </span>
                            <?php endif; ?>
                        </td>
                        <?php if ($tab_filter === 'menunggu'): ?>
                            <td class="p-2.5 text-center">
                                <a href="ranking.php?tab=menunggu&action=verifikasi_siswa&id=<?= $siswa['id_siswa'] ?>" 
                                   onclick="return confirm('Verifikasi & setujui berkas <?= htmlspecialchars($siswa['nama']) ?>?')"
                                   class="px-2.5 py-1 bg-slate-900 hover:bg-slate-800 text-white rounded text-xs font-bold shadow inline-flex items-center gap-1 transition-colors">
                                    <i class="fa-solid fa-check"></i> Setujui
                                </a>
                            </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>

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

        <!-- Distribusi Visual Skor AHP (Kanan) -->
        <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm space-y-4">
            <h3 class="text-sm font-bold text-slate-800 mb-1">Distribusi Skor Resmi</h3>
            <p class="text-xs text-slate-400">Sebaran nilai total AHP siswa yang telah <b>Terverifikasi</b> (Top 7).</p>
            
            <div class="space-y-3 pt-2">
                <?php 
                $preview_chart = array_slice($siswa_terverifikasi, 0, 7);
                if (empty($preview_chart)): ?>
                    <div class="text-xs text-slate-400 text-center py-6 border border-dashed border-slate-200 rounded-lg">
                        Belum ada data siswa terverifikasi untuk ditampilkan di grafik.
                    </div>
                <?php else:
                    foreach ($preview_chart as $s): 
                        $pct = round(($s['skor_akhir'] / 5.0) * 100);
                ?>
                <div>
                    <div class="flex justify-between text-xs mb-1">
                        <span class="font-medium text-slate-700 truncate flex-1 min-w-0 pr-2"><?= htmlspecialchars($s['nama']) ?></span>
                        <span class="font-bold text-slate-900 font-mono shrink-0"><?= number_format($s['skor_akhir'], 4) ?></span>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                        <div class="bg-slate-800 h-2 rounded-full transition-all duration-300" style="width: <?= $pct ?>%"></div>
                    </div>
                </div>
                <?php endforeach; endif; ?>
            </div>

            <div class="pt-4 border-t border-slate-100 space-y-2">
                <button type="button" onclick="openModalPilihanCetak()" class="w-full py-2.5 bg-slate-900 hover:bg-slate-800 text-white font-medium rounded-lg text-xs transition-colors shadow flex items-center justify-center gap-2 cursor-pointer whitespace-nowrap">
                    <i class="fa-solid fa-file-pdf"></i> Cetak PDF (Pilih Kategori)
                </button>
                <button type="button" onclick="openModalPilihanExcel()" class="w-full py-2.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 font-medium rounded-lg text-xs transition-colors shadow-sm flex items-center justify-center gap-2 cursor-pointer whitespace-nowrap">
                    <i class="fa-solid fa-file-excel text-emerald-600"></i> Ekspor Excel (Pilih Kategori)
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL POPUP PILIHAN KATEGORI CETAK LAPORAN -->
<!-- ========================================== -->
<div id="modal-pilihan-cetak" class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6 border border-slate-200 max-h-[90vh] overflow-y-auto" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
            <div class="flex items-center gap-2.5 text-slate-900">
                <div class="w-9 h-9 rounded-xl bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-700 text-sm shrink-0">
                    <i class="fa-solid fa-print"></i>
                </div>
                <div>
                    <h3 class="font-bold text-slate-900 text-base leading-tight">Pilih Kategori Cetak Laporan</h3>
                    <p class="text-[11px] text-slate-400">Tentukan data yang ingin dicetak ke dokumen dinas resmi format PDF</p>
                </div>
            </div>
            <button onclick="closeModalPilihanCetak()" class="text-slate-400 hover:text-slate-600 text-lg transition-colors cursor-pointer">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="space-y-2.5">
            <!-- 1. Prioritas Lolos -->
            <a href="cetak_laporan.php?kategori=lolos&tahun=<?= urlencode($pengaturan['tahun_ajaran'] ?? '2025/2026') ?>" target="_blank" onclick="closeModalPilihanCetak()" 
               class="p-3.5 rounded-xl border border-slate-200 hover:border-slate-800 hover:bg-slate-50 transition-all flex items-center justify-between group cursor-pointer">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center justify-center text-sm shrink-0">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                    <div class="text-left">
                        <span class="font-bold text-slate-800 text-xs block group-hover:text-slate-900">Prioritas Lolos Saja (Penerima Kuota)</span>
                        <span class="text-[11px] text-slate-400 block">Mencetak khusus siswa penerima kuota resmi sekolah (Maks. <?= $kuota ?> Siswa)</span>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200"><?= $count_lolos ?> Siswa</span>
                    <i class="fa-solid fa-chevron-right text-slate-300 group-hover:text-slate-700 text-xs"></i>
                </div>
            </a>

            <!-- 2. Peserta Cadangan -->
            <a href="cetak_laporan.php?kategori=cadangan&tahun=<?= urlencode($pengaturan['tahun_ajaran'] ?? '2025/2026') ?>" target="_blank" onclick="closeModalPilihanCetak()" 
               class="p-3.5 rounded-xl border border-slate-200 hover:border-slate-800 hover:bg-slate-50 transition-all flex items-center justify-between group cursor-pointer">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-700 border border-amber-200 flex items-center justify-center text-sm shrink-0">
                        <i class="fa-solid fa-user-clock"></i>
                    </div>
                    <div class="text-left">
                        <span class="font-bold text-slate-800 text-xs block group-hover:text-slate-900">Peserta Cadangan Saja</span>
                        <span class="text-[11px] text-slate-400 block">Mencetak daftar siswa cadangan di luar kuota utama</span>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200"><?= $count_cadangan ?> Siswa</span>
                    <i class="fa-solid fa-chevron-right text-slate-300 group-hover:text-slate-700 text-xs"></i>
                </div>
            </a>

            <!-- 3. Semua Terverifikasi -->
            <a href="cetak_laporan.php?kategori=terverifikasi&tahun=<?= urlencode($pengaturan['tahun_ajaran'] ?? '2025/2026') ?>" target="_blank" onclick="closeModalPilihanCetak()" 
               class="p-3.5 rounded-xl border border-slate-200 hover:border-slate-800 hover:bg-slate-50 transition-all flex items-center justify-between group cursor-pointer">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-slate-100 text-slate-700 border border-slate-200 flex items-center justify-center text-sm shrink-0">
                        <i class="fa-solid fa-list-check"></i>
                    </div>
                    <div class="text-left">
                        <span class="font-bold text-slate-800 text-xs block group-hover:text-slate-900">Semua Terverifikasi (Lolos & Cadangan)</span>
                        <span class="text-[11px] text-slate-400 block">Mencetak seluruh peserta terverifikasi berurutan sesuai ranking skor AHP</span>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700 border border-slate-200"><?= $count_terverifikasi ?> Siswa</span>
                    <i class="fa-solid fa-chevron-right text-slate-300 group-hover:text-slate-700 text-xs"></i>
                </div>
            </a>

            <!-- 4. Menunggu Verifikasi -->
            <a href="cetak_laporan.php?kategori=menunggu&tahun=<?= urlencode($pengaturan['tahun_ajaran'] ?? '2025/2026') ?>" target="_blank" onclick="closeModalPilihanCetak()" 
               class="p-3.5 rounded-xl border border-slate-200 hover:border-slate-800 hover:bg-slate-50 transition-all flex items-center justify-between group cursor-pointer">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-700 border border-amber-200 flex items-center justify-center text-sm shrink-0">
                        <i class="fa-solid fa-clock"></i>
                    </div>
                    <div class="text-left">
                        <span class="font-bold text-slate-800 text-xs block group-hover:text-slate-900">Menunggu Verifikasi Berkas</span>
                        <span class="text-[11px] text-slate-400 block">Mencetak daftar berkas pendaftaran yang belum diverifikasi</span>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200"><?= $count_menunggu ?> Siswa</span>
                    <i class="fa-solid fa-chevron-right text-slate-300 group-hover:text-slate-700 text-xs"></i>
                </div>
            </a>

            <!-- 5. Berkas Ditolak -->
            <a href="cetak_laporan.php?kategori=ditolak&tahun=<?= urlencode($pengaturan['tahun_ajaran'] ?? '2025/2026') ?>" target="_blank" onclick="closeModalPilihanCetak()" 
               class="p-3.5 rounded-xl border border-slate-200 hover:border-slate-800 hover:bg-slate-50 transition-all flex items-center justify-between group cursor-pointer">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-rose-50 text-rose-700 border border-rose-200 flex items-center justify-center text-sm shrink-0">
                        <i class="fa-solid fa-circle-xmark"></i>
                    </div>
                    <div class="text-left">
                        <span class="font-bold text-slate-800 text-xs block group-hover:text-slate-900">Berkas Ditolak (Gugur)</span>
                        <span class="text-[11px] text-slate-400 block">Mencetak daftar calon siswa yang tidak memenuhi kriteria</span>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200"><?= $count_ditolak ?> Siswa</span>
                    <i class="fa-solid fa-chevron-right text-slate-300 group-hover:text-slate-700 text-xs"></i>
                </div>
            </a>

            <!-- 6. Semua Calon -->
            <a href="cetak_laporan.php?kategori=all&tahun=<?= urlencode($pengaturan['tahun_ajaran'] ?? '2025/2026') ?>" target="_blank" onclick="closeModalPilihanCetak()" 
               class="p-3.5 rounded-xl border border-slate-200 hover:border-slate-800 hover:bg-slate-50 transition-all flex items-center justify-between group cursor-pointer">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-slate-100 text-slate-700 border border-slate-200 flex items-center justify-center text-sm shrink-0">
                        <i class="fa-solid fa-users"></i>
                    </div>
                    <div class="text-left">
                        <span class="font-bold text-slate-800 text-xs block group-hover:text-slate-900">Seluruh Calon Pendaftar (Semua Data)</span>
                        <span class="text-[11px] text-slate-400 block">Rekapitulasi lengkap seluruh pengajuan pendaftaran siswa</span>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700 border border-slate-200"><?= $count_all ?> Siswa</span>
                    <i class="fa-solid fa-chevron-right text-slate-300 group-hover:text-slate-700 text-xs"></i>
                </div>
            </a>
        </div>

        <div class="pt-4 mt-4 border-t border-slate-100 flex justify-end">
            <button type="button" onclick="closeModalPilihanCetak()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-lg text-xs transition-colors cursor-pointer">
                Batal
            </button>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL POPUP PILIHAN KATEGORI EKSPOR EXCEL  -->
<!-- ========================================== -->
<div id="modal-pilihan-excel" class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6 border border-slate-200 max-h-[90vh] overflow-y-auto" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
            <div class="flex items-center gap-2.5 text-slate-900">
                <div class="w-9 h-9 rounded-xl bg-emerald-50 border border-emerald-200 flex items-center justify-center text-emerald-700 text-sm shrink-0">
                    <i class="fa-solid fa-file-excel"></i>
                </div>
                <div>
                    <h3 class="font-bold text-slate-900 text-base leading-tight">Pilih Kategori Ekspor Excel</h3>
                    <p class="text-[11px] text-slate-400">Unduh data perankingan dalam format spreadsheet Excel (.xls)</p>
                </div>
            </div>
            <button onclick="closeModalPilihanExcel()" class="text-slate-400 hover:text-slate-600 text-lg transition-colors cursor-pointer">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="space-y-2.5">
            <!-- 1. Prioritas Lolos -->
            <a href="ekspor_excel.php?kategori=lolos&tahun=<?= urlencode($pengaturan['tahun_ajaran'] ?? '2025/2026') ?>" onclick="closeModalPilihanExcel()" 
               class="p-3.5 rounded-xl border border-slate-200 hover:border-emerald-600 hover:bg-emerald-50/40 transition-all flex items-center justify-between group cursor-pointer">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center justify-center text-sm shrink-0">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                    <div class="text-left">
                        <span class="font-bold text-slate-800 text-xs block group-hover:text-emerald-950">Prioritas Lolos Saja (Penerima Kuota)</span>
                        <span class="text-[11px] text-slate-400 block">Unduh khusus siswa penerima kuota resmi sekolah (Maks. <?= $kuota ?> Siswa)</span>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200"><?= $count_lolos ?> Siswa</span>
                    <i class="fa-solid fa-download text-slate-300 group-hover:text-emerald-700 text-xs"></i>
                </div>
            </a>

            <!-- 2. Peserta Cadangan -->
            <a href="ekspor_excel.php?kategori=cadangan&tahun=<?= urlencode($pengaturan['tahun_ajaran'] ?? '2025/2026') ?>" onclick="closeModalPilihanExcel()" 
               class="p-3.5 rounded-xl border border-slate-200 hover:border-emerald-600 hover:bg-emerald-50/40 transition-all flex items-center justify-between group cursor-pointer">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-700 border border-amber-200 flex items-center justify-center text-sm shrink-0">
                        <i class="fa-solid fa-user-clock"></i>
                    </div>
                    <div class="text-left">
                        <span class="font-bold text-slate-800 text-xs block group-hover:text-emerald-950">Peserta Cadangan Saja</span>
                        <span class="text-[11px] text-slate-400 block">Unduh daftar siswa cadangan di luar kuota utama</span>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200"><?= $count_cadangan ?> Siswa</span>
                    <i class="fa-solid fa-download text-slate-300 group-hover:text-emerald-700 text-xs"></i>
                </div>
            </a>

            <!-- 3. Semua Terverifikasi -->
            <a href="ekspor_excel.php?kategori=terverifikasi&tahun=<?= urlencode($pengaturan['tahun_ajaran'] ?? '2025/2026') ?>" onclick="closeModalPilihanExcel()" 
               class="p-3.5 rounded-xl border border-slate-200 hover:border-emerald-600 hover:bg-emerald-50/40 transition-all flex items-center justify-between group cursor-pointer">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-slate-100 text-slate-700 border border-slate-200 flex items-center justify-center text-sm shrink-0">
                        <i class="fa-solid fa-list-check"></i>
                    </div>
                    <div class="text-left">
                        <span class="font-bold text-slate-800 text-xs block group-hover:text-emerald-950">Semua Terverifikasi (Lolos & Cadangan)</span>
                        <span class="text-[11px] text-slate-400 block">Unduh seluruh peserta terverifikasi berurutan sesuai ranking skor AHP</span>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700 border border-slate-200"><?= $count_terverifikasi ?> Siswa</span>
                    <i class="fa-solid fa-download text-slate-300 group-hover:text-emerald-700 text-xs"></i>
                </div>
            </a>

            <!-- 4. Menunggu Verifikasi -->
            <a href="ekspor_excel.php?kategori=menunggu&tahun=<?= urlencode($pengaturan['tahun_ajaran'] ?? '2025/2026') ?>" onclick="closeModalPilihanExcel()" 
               class="p-3.5 rounded-xl border border-slate-200 hover:border-emerald-600 hover:bg-emerald-50/40 transition-all flex items-center justify-between group cursor-pointer">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-700 border border-amber-200 flex items-center justify-center text-sm shrink-0">
                        <i class="fa-solid fa-clock"></i>
                    </div>
                    <div class="text-left">
                        <span class="font-bold text-slate-800 text-xs block group-hover:text-emerald-950">Menunggu Verifikasi Berkas</span>
                        <span class="text-[11px] text-slate-400 block">Unduh daftar berkas pendaftaran yang belum diverifikasi</span>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200"><?= $count_menunggu ?> Siswa</span>
                    <i class="fa-solid fa-download text-slate-300 group-hover:text-emerald-700 text-xs"></i>
                </div>
            </a>

            <!-- 5. Berkas Ditolak -->
            <a href="ekspor_excel.php?kategori=ditolak&tahun=<?= urlencode($pengaturan['tahun_ajaran'] ?? '2025/2026') ?>" onclick="closeModalPilihanExcel()" 
               class="p-3.5 rounded-xl border border-slate-200 hover:border-emerald-600 hover:bg-emerald-50/40 transition-all flex items-center justify-between group cursor-pointer">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-rose-50 text-rose-700 border border-rose-200 flex items-center justify-center text-sm shrink-0">
                        <i class="fa-solid fa-circle-xmark"></i>
                    </div>
                    <div class="text-left">
                        <span class="font-bold text-slate-800 text-xs block group-hover:text-emerald-950">Berkas Ditolak (Gugur)</span>
                        <span class="text-[11px] text-slate-400 block">Unduh daftar calon siswa yang ditolak / tidak memenuhi kriteria</span>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200"><?= $count_ditolak ?> Siswa</span>
                    <i class="fa-solid fa-download text-slate-300 group-hover:text-emerald-700 text-xs"></i>
                </div>
            </a>

            <!-- 6. Semua Calon -->
            <a href="ekspor_excel.php?kategori=all&tahun=<?= urlencode($pengaturan['tahun_ajaran'] ?? '2025/2026') ?>" onclick="closeModalPilihanExcel()" 
               class="p-3.5 rounded-xl border border-slate-200 hover:border-emerald-600 hover:bg-emerald-50/40 transition-all flex items-center justify-between group cursor-pointer">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-slate-100 text-slate-700 border border-slate-200 flex items-center justify-center text-sm shrink-0">
                        <i class="fa-solid fa-users"></i>
                    </div>
                    <div class="text-left">
                        <span class="font-bold text-slate-800 text-xs block group-hover:text-emerald-950">Seluruh Calon Pendaftar (Semua Data)</span>
                        <span class="text-[11px] text-slate-400 block">Rekapitulasi lengkap seluruh pengajuan pendaftaran siswa</span>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700 border border-slate-200"><?= $count_all ?> Siswa</span>
                    <i class="fa-solid fa-download text-slate-300 group-hover:text-emerald-700 text-xs"></i>
                </div>
            </a>
        </div>

        <div class="pt-4 mt-4 border-t border-slate-100 flex justify-end">
            <button type="button" onclick="closeModalPilihanExcel()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-lg text-xs transition-colors cursor-pointer">
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
