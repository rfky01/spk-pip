<?php
// laporan.php - Halaman Laporan Seleksi Penerima PIP (AHP)
$page_title = "Laporan Penentuan PIP";
require_once "header.php";
require_once "sidebar.php";

// Ambil data tahun ajaran unik dari tabel calon_penerima
$tahun_aktif = $pengaturan['tahun_ajaran'] ?? '2025/2026';
$res_th = mysqli_query($koneksi, "SELECT DISTINCT `tahun` FROM `calon_penerima` WHERE `tahun` IS NOT NULL AND `tahun` != '' ORDER BY `tahun` DESC");
$list_tahun = [];
while ($t = mysqli_fetch_assoc($res_th)) {
    $list_tahun[] = $t['tahun'];
}
if (!in_array($tahun_aktif, $list_tahun)) {
    $list_tahun[] = $tahun_aktif;
}
foreach (['2026/2027', '2025/2026', '2024/2025'] as $to) {
    if (!in_array($to, $list_tahun)) {
        $list_tahun[] = $to;
    }
}
rsort($list_tahun);

// Filter tahun ajaran (default = tahun aktif sekolah)
$filter_tahun = $_GET['tahun'] ?? $tahun_aktif;

// Ambil bobot kriteria
$res_kriteria = mysqli_query($koneksi, "SELECT * FROM `kriteria` ORDER BY `kode_kriteria` ASC");
$kriteria_db = [];
while ($row = mysqli_fetch_assoc($res_kriteria)) {
    $kriteria_db[$row['kode_kriteria']] = (float)$row['bobot'];
}

// Kondisi filter tahun
$where_tahun = "";
if ($filter_tahun !== 'all') {
    $safe_th = mysqli_real_escape_string($koneksi, $filter_tahun);
    $where_tahun = " AND `tahun` = '$safe_th'";
}

// Ambil data siswa yang TERVERIFIKASI untuk laporan resmi sesuai filter tahun
$res_calon = mysqli_query($koneksi, "SELECT * FROM `calon_penerima` WHERE `status_verifikasi` = 'Terverifikasi' $where_tahun ORDER BY `id_siswa` ASC");
$semua_siswa = [];
while ($s = mysqli_fetch_assoc($res_calon)) {
    $skor = ($s['penghasilan'] * ($kriteria_db['C1'] ?? 0.4165)) +
            ($s['tanggungan']  * ($kriteria_db['C2'] ?? 0.2619)) +
            ($s['kondisi_rumah']* ($kriteria_db['C3'] ?? 0.1608)) +
            ($s['prestasi']    * ($kriteria_db['C4'] ?? 0.0985)) +
            ($s['jarak']       * ($kriteria_db['C5'] ?? 0.0623));
    $s['skor_akhir'] = round($skor, 4);
    $semua_siswa[] = $s;
}

usort($semua_siswa, function($a, $b) {
    return $b['skor_akhir'] <=> $a['skor_akhir'];
});

// Tetapkan peringkat global sebelum pagination
foreach ($semua_siswa as $idx => $s) {
    $semua_siswa[$idx]['global_rank'] = $idx + 1;
}

$total_terverifikasi = count($semua_siswa);
$kuota = (int)$pengaturan['kuota_pip'];
$count_lolos = min($kuota, $total_terverifikasi);
$count_cadangan = max(0, $total_terverifikasi - $kuota);
$rata_skor = $total_terverifikasi > 0 ? number_format(array_sum(array_column($semua_siswa, 'skor_akhir')) / $total_terverifikasi, 4) : '0.0000';

// Konfigurasi Pagination (10 data per halaman)
$limit_laporan = 10;
$total_pages_laporan = max(1, (int)ceil($total_terverifikasi / $limit_laporan));
$page_laporan = max(1, min((int)($_GET['page'] ?? 1), $total_pages_laporan));
$offset_laporan = ($page_laporan - 1) * $limit_laporan;
$halaman_siswa_laporan = array_slice($semua_siswa, $offset_laporan, $limit_laporan);

// Hitung total pendaftar berdasarkan filter tahun
$where_count = ($filter_tahun !== 'all') ? " WHERE `tahun` = '" . mysqli_real_escape_string($koneksi, $filter_tahun) . "'" : "";
$count_all = mysqli_num_rows(mysqli_query($koneksi, "SELECT id_siswa FROM `calon_penerima` $where_count"));

$where_menunggu = ($filter_tahun !== 'all') ? " WHERE `status_verifikasi` = 'Menunggu Verifikasi' AND `tahun` = '" . mysqli_real_escape_string($koneksi, $filter_tahun) . "'" : " WHERE `status_verifikasi` = 'Menunggu Verifikasi'";
$count_menunggu = mysqli_num_rows(mysqli_query($koneksi, "SELECT id_siswa FROM `calon_penerima` $where_menunggu"));

$where_ditolak = ($filter_tahun !== 'all') ? " WHERE `status_verifikasi` = 'Ditolak' AND `tahun` = '" . mysqli_real_escape_string($koneksi, $filter_tahun) . "'" : " WHERE `status_verifikasi` = 'Ditolak'";
$count_ditolak = mysqli_num_rows(mysqli_query($koneksi, "SELECT id_siswa FROM `calon_penerima` $where_ditolak"));
?>

<div class="space-y-6 w-full">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Halaman Laporan PIP (AHP)</h1>
            <p class="text-xs text-slate-500 mt-1">Dokumentasi dan Pelaporan Penetapan Penerima Bantuan PIP Berdasarkan Verifikasi Berkas</p>
        </div>
    </div>

    <?php if ($count_menunggu > 0): ?>
        <div class="p-3.5 bg-amber-50/80 border border-amber-200/80 text-amber-900 rounded-xl text-xs flex items-center justify-between shadow-sm">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-triangle-exclamation text-amber-600"></i>
                <span>Perhatian: Terdapat <b><?= $count_menunggu ?> siswa</b> masih menunggu verifikasi berkas dan belum dimasukkan ke laporan resmi ini.</span>
            </div>
            <a href="data_calon_penerima.php?status=menunggu" class="text-amber-800 font-bold underline hover:text-amber-950">Verifikasi Berkas</a>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
        <!-- Sisi Kontrol / Parameter (Kiri) -->
        <div class="space-y-4">
            <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-700 text-xl shrink-0">
                    <i class="fa-solid fa-file-invoice"></i>
                </div>
                <div class="text-xs text-slate-500 font-medium space-y-1">
                    <p>Siswa Terverifikasi: <span class="font-bold text-slate-900 text-sm"><?= $total_terverifikasi ?></span></p>
                    <p>Kuota Resmi: <span class="font-bold text-slate-900 text-sm"><?= $kuota ?> Siswa</span></p>
                    <p>Rata-rata Skor: <span class="font-bold text-slate-900 font-mono text-sm"><?= $rata_skor ?></span></p>
                    <p class="text-[11px] text-slate-400 pt-1 border-t border-slate-100">Total Pendaftar <?= ($filter_tahun !== 'all') ? "($filter_tahun)" : "(Semua)" ?>: <?= $count_all ?> (Pending: <?= $count_menunggu ?>, Tolak: <?= $count_ditolak ?>)</p>
                </div>
            </div>

            <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Status Data Laporan</label>
                    <div class="p-2.5 bg-slate-50 text-slate-700 border border-slate-200 rounded-lg text-xs font-semibold flex items-center gap-2">
                        <i class="fa-solid fa-shield-halved text-slate-500"></i>
                        Khusus Data Berkas Terverifikasi
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1 flex items-center justify-between">
                        <span>Pilih Tahun Ajaran</span>
                        <?php if ($filter_tahun !== 'all' && $filter_tahun === $tahun_aktif): ?>
                            <span class="text-[10px] bg-slate-100 text-slate-700 border border-slate-200 px-2 py-0.5 rounded font-bold">Tahun Aktif</span>
                        <?php endif; ?>
                    </label>
                    <form action="laporan.php" method="GET" id="form-filter-tahun">
                        <select name="tahun" onchange="this.form.submit()" class="w-full px-3 py-2 border border-slate-300 rounded-lg bg-white text-slate-800 text-xs font-bold focus:ring-1 focus:ring-slate-800 focus:border-slate-800 focus:outline-none shadow-sm cursor-pointer">
                            <option value="all" <?= $filter_tahun === 'all' ? 'selected' : '' ?>>-- Semua Tahun Ajaran --</option>
                            <?php foreach ($list_tahun as $th): ?>
                                <option value="<?= htmlspecialchars($th) ?>" <?= $filter_tahun === $th ? 'selected' : '' ?>>
                                    Tahun Ajaran <?= htmlspecialchars($th) ?> <?= ($th === $tahun_aktif) ? '★ (Aktif)' : '' ?>
                                    Tahun Ajaran <?= htmlspecialchars($th) ?> <?= ($th === $tahun_aktif) ? '(Aktif)' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                    <p class="text-[10px] text-slate-400 mt-1">Ubah pilihan untuk memilah rekapitulasi data per angkatan.</p>
                </div>
                <a href="cetak_laporan.php?kategori=terverifikasi&tahun=<?= urlencode($filter_tahun) ?>" target="_blank" class="w-full py-2.5 bg-slate-900 hover:bg-slate-800 text-white font-medium rounded-lg text-xs transition-colors shadow flex items-center justify-center gap-2 whitespace-nowrap" title="Cetak seluruh data resmi penetapan untuk tahun ajaran <?= htmlspecialchars($filter_tahun === 'all' ? 'Semua Tahun' : $filter_tahun) ?>">
                    <i class="fa-solid fa-file-pdf"></i> Cetak Semua Laporan (PDF)
                </a>
                <a href="ekspor_excel.php?kategori=terverifikasi&tahun=<?= urlencode($filter_tahun) ?>" class="w-full py-2.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 font-medium rounded-lg text-xs transition-colors shadow-sm flex items-center justify-center gap-2 whitespace-nowrap" title="Unduh file Excel seluruh data penetapan untuk tahun ajaran <?= htmlspecialchars($filter_tahun === 'all' ? 'Semua Tahun' : $filter_tahun) ?>">
                    <i class="fa-solid fa-file-excel text-emerald-600"></i> Ekspor Semua ke Excel
                </a>
            </div>
        </div>

        <!-- Sisi Detail Hasil Laporan (Kanan) -->
        <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm lg:col-span-2 space-y-6">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-bold text-slate-800">Preview Daftar Penetapan Penerima PIP (Terverifikasi)</h3>
                <span class="text-xs text-slate-400 font-medium"><?= $total_terverifikasi ?> Peserta</span>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm min-w-[500px]">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-bold text-xs">
                            <th class="p-2.5 text-center w-12">Rank</th>
                            <th class="p-2.5">NISN</th>
                            <th class="p-2.5">Nama Siswa</th>
                            <th class="p-2.5 text-center">Sekolah Asal</th>
                            <th class="p-2.5 text-center">Total Skor AHP</th>
                            <th class="p-2.5 text-center">Status Penetapan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700 font-medium text-xs">
                        <?php if (empty($semua_siswa)): ?>
                            <tr><td colspan="6" class="p-8 text-center text-slate-400">Belum ada calon siswa berstatus Terverifikasi untuk dilaporkan.</td></tr>
                        <?php else:
                            foreach ($halaman_siswa_laporan as $siswa): 
                                $r = $siswa['global_rank'];
                                $is_lolos = ($r <= $kuota);
                        ?>
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <td class="p-2.5 font-bold text-center">
                                <?php if ($r === 1): ?>
                                    <span class="inline-flex items-center justify-center w-6 h-6 bg-slate-900 text-white rounded-full text-xs font-bold shadow-sm">1</span>
                                <?php elseif ($r === 2): ?>
                                    <span class="inline-flex items-center justify-center w-6 h-6 bg-slate-200 text-slate-800 rounded-full text-xs font-bold">2</span>
                                <?php elseif ($r === 3): ?>
                                    <span class="inline-flex items-center justify-center w-6 h-6 bg-slate-100 border border-slate-300 text-slate-700 rounded-full text-xs font-bold">3</span>
                                <?php else: ?>
                                    <span class="text-slate-600 font-mono"><?= $r ?>.</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-2.5 font-semibold text-slate-600 font-mono"><?= htmlspecialchars($siswa['nisn']) ?></td>
                            <td class="p-2.5 font-bold text-slate-900"><?= htmlspecialchars($siswa['nama']) ?></td>
                            <td class="p-2.5 text-center">
                                <span class="font-bold text-slate-700 block"><?= htmlspecialchars($siswa['sekolah_asal'] ?: '-') ?></span>
                                <span class="text-[10px] text-slate-500 font-semibold">Kelas VII</span>
                            </td>
                            <td class="p-2.5 text-center font-bold text-slate-900 font-mono"><?= number_format($siswa['skor_akhir'], 4) ?></td>
                            <td class="p-2.5 text-center">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold <?= $is_lolos ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-slate-100 text-slate-600 border border-slate-200' ?>">
                                    <?= $is_lolos ? 'PRIORITAS PENERIMA' : 'CADANGAN' ?>
                                </span>
                            </td>
                        </tr>
                        <?php 
                            endforeach; 
                        endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Halaman Laporan -->
            <?php if ($total_terverifikasi > 0): 
                $start_display = $offset_laporan + 1;
                $end_display = min($offset_laporan + $limit_laporan, $total_terverifikasi);
            ?>
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-4 border-t border-slate-100 text-xs">
                <div class="text-slate-500">
                    Menampilkan <span class="font-bold text-slate-800"><?= $start_display ?></span> - <span class="font-bold text-slate-800"><?= $end_display ?></span> dari <span class="font-bold text-slate-800"><?= $total_terverifikasi ?></span> siswa terverifikasi
                </div>
                <?php if ($total_pages_laporan > 1): ?>
                <div class="flex items-center gap-1">
                    <?php
                    $url_prev = '?' . http_build_query(array_merge($_GET, ['page' => max(1, $page_laporan - 1)]));
                    $url_next = '?' . http_build_query(array_merge($_GET, ['page' => min($total_pages_laporan, $page_laporan + 1)]));
                    ?>
                    <!-- Prev Button -->
                    <?php if ($page_laporan > 1): ?>
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

                        for ($p = 1; $p <= $total_pages_laporan; $p++):
                            if ($p == 1 || $p == $total_pages_laporan || ($p >= $page_laporan - $range && $p <= $page_laporan + $range)):
                                $url_page = '?' . http_build_query(array_merge($_GET, ['page' => $p]));
                        ?>
                            <a href="<?= htmlspecialchars($url_page) ?>" class="w-8 h-8 rounded-lg flex items-center justify-center font-bold transition-all <?= $p == $page_laporan ? 'bg-slate-900 text-white shadow-sm' : 'bg-white hover:bg-slate-50 border border-slate-200 text-slate-700' ?>">
                                <?= $p ?>
                            </a>
                        <?php
                            elseif ($p < $page_laporan - $range && !$show_dots_left):
                                $show_dots_left = true;
                                echo '<span class="w-6 text-center text-slate-400 font-bold">...</span>';
                            elseif ($p > $page_laporan + $range && !$show_dots_right):
                                $show_dots_right = true;
                                echo '<span class="w-6 text-center text-slate-400 font-bold">...</span>';
                            endif;
                        endfor;
                        ?>
                    </div>

                    <!-- Next Button -->
                    <?php if ($page_laporan < $total_pages_laporan): ?>
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

            <div class="flex flex-wrap justify-end items-center gap-2.5 pt-4 border-t border-slate-100">
                <!-- Dropdown Cetak Dokumen Resmi -->
                <div class="relative inline-block text-left" id="dropdown-cetak-wrapper">
                    <button type="button" id="btn-dropdown-cetak" onclick="toggleDropdownCetak(event)" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white font-medium rounded-lg text-xs transition-colors shadow flex items-center gap-2 cursor-pointer focus:outline-none whitespace-nowrap shrink-0" title="Pilih opsi cetak laporan">
                        <i class="fa-solid fa-print"></i>
                        <span>Cetak Dokumen Resmi</span>
                        <i class="fa-solid fa-chevron-down text-[10px] ml-0.5 transition-transform duration-200" id="arrow-dropdown-cetak"></i>
                    </button>
                    
                    <!-- Dropdown Menu (Muncul ke atas agar tidak terpotong) -->
                    <div id="menu-dropdown-cetak" class="hidden absolute right-0 bottom-full mb-2 w-64 bg-white rounded-xl shadow-xl border border-slate-200 z-50 py-1.5 divide-y divide-slate-100">
                        <div class="px-3.5 py-1.5">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Pilih Opsi Cetak Laporan</p>
                        </div>
                        <div class="py-1">
                            <a href="cetak_laporan.php?kategori=terverifikasi&tahun=<?= urlencode($filter_tahun) ?>" target="_blank" onclick="closeDropdownCetak()" class="flex items-center gap-3 px-3.5 py-2.5 text-xs text-slate-700 hover:bg-slate-50 hover:text-slate-900 transition-colors group">
                                <div class="w-7 h-7 rounded-lg bg-slate-100 group-hover:bg-slate-200 flex items-center justify-center text-slate-700 text-xs shrink-0">
                                    <i class="fa-solid fa-list-check"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="font-bold text-slate-800 flex items-center justify-between">
                                        <span>Cetak Semua</span>
                                        <span class="text-[10px] text-slate-500 bg-slate-100 px-1.5 py-0.5 rounded font-medium"><?= $total_terverifikasi ?> Siswa</span>
                                    </div>
                                    <div class="text-[10px] text-slate-400 truncate">Seluruh siswa terverifikasi</div>
                                </div>
                            </a>
                            <a href="cetak_laporan.php?kategori=lolos&tahun=<?= urlencode($filter_tahun) ?>" target="_blank" onclick="closeDropdownCetak()" class="flex items-center gap-3 px-3.5 py-2.5 text-xs text-slate-700 hover:bg-slate-50 hover:text-slate-900 transition-colors group">
                                <div class="w-7 h-7 rounded-lg bg-emerald-50 group-hover:bg-emerald-100 flex items-center justify-center text-emerald-600 text-xs shrink-0">
                                    <i class="fa-solid fa-circle-check"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="font-bold text-slate-800 flex items-center justify-between">
                                        <span>Cetak Yang Lolos</span>
                                        <span class="text-[10px] text-emerald-700 bg-emerald-50 border border-emerald-200 px-1.5 py-0.5 rounded font-bold"><?= $count_lolos ?> Siswa</span>
                                    </div>
                                    <div class="text-[10px] text-slate-400 truncate">Prioritas penerima kuota resmi</div>
                                </div>
                            </a>
                            <a href="cetak_laporan.php?kategori=tidak_lolos&tahun=<?= urlencode($filter_tahun) ?>" target="_blank" onclick="closeDropdownCetak()" class="flex items-center gap-3 px-3.5 py-2.5 text-xs text-slate-700 hover:bg-slate-50 hover:text-slate-900 transition-colors group">
                                <div class="w-7 h-7 rounded-lg bg-amber-50 group-hover:bg-amber-100 flex items-center justify-center text-amber-600 text-xs shrink-0">
                                    <i class="fa-solid fa-user-clock"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="font-bold text-slate-800 flex items-center justify-between">
                                        <span>Cetak Yang Tidak Lolos</span>
                                        <span class="text-[10px] text-amber-700 bg-amber-50 border border-amber-200 px-1.5 py-0.5 rounded font-bold"><?= $count_cadangan ?> Siswa</span>
                                    </div>
                                    <div class="text-[10px] text-slate-400 truncate">Peserta cadangan di luar kuota</div>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Dropdown Unduh Excel -->
                <div class="relative inline-block text-left" id="dropdown-excel-wrapper">
                    <button type="button" id="btn-dropdown-excel" onclick="toggleDropdownExcel(event)" class="px-4 py-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 font-medium rounded-lg text-xs transition-colors shadow-sm flex items-center gap-2 cursor-pointer focus:outline-none whitespace-nowrap shrink-0" title="Pilih opsi unduh file Excel">
                        <i class="fa-solid fa-file-excel text-emerald-600"></i>
                        <span>Unduh Excel</span>
                        <i class="fa-solid fa-chevron-down text-[10px] ml-0.5 transition-transform duration-200" id="arrow-dropdown-excel"></i>
                    </button>
                    
                    <!-- Dropdown Menu (Muncul ke atas) -->
                    <div id="menu-dropdown-excel" class="hidden absolute right-0 bottom-full mb-2 w-64 bg-white rounded-xl shadow-xl border border-slate-200 z-50 py-1.5 divide-y divide-slate-100">
                        <div class="px-3.5 py-1.5">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Pilih Opsi Ekspor Excel</p>
                        </div>
                        <div class="py-1">
                            <a href="ekspor_excel.php?kategori=terverifikasi&tahun=<?= urlencode($filter_tahun) ?>" onclick="closeDropdownExcel()" class="flex items-center gap-3 px-3.5 py-2.5 text-xs text-slate-700 hover:bg-slate-50 hover:text-slate-900 transition-colors group">
                                <div class="w-7 h-7 rounded-lg bg-slate-100 group-hover:bg-slate-200 flex items-center justify-center text-slate-700 text-xs shrink-0">
                                    <i class="fa-solid fa-list-check"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="font-bold text-slate-800 flex items-center justify-between">
                                        <span>Unduh Semua</span>
                                        <span class="text-[10px] text-slate-500 bg-slate-100 px-1.5 py-0.5 rounded font-medium"><?= $total_terverifikasi ?> Siswa</span>
                                    </div>
                                    <div class="text-[10px] text-slate-400 truncate">Semua data siswa terverifikasi</div>
                                </div>
                            </a>
                            <a href="ekspor_excel.php?kategori=lolos&tahun=<?= urlencode($filter_tahun) ?>" onclick="closeDropdownExcel()" class="flex items-center gap-3 px-3.5 py-2.5 text-xs text-slate-700 hover:bg-slate-50 hover:text-slate-900 transition-colors group">
                                <div class="w-7 h-7 rounded-lg bg-emerald-50 group-hover:bg-emerald-100 flex items-center justify-center text-emerald-600 text-xs shrink-0">
                                    <i class="fa-solid fa-circle-check"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="font-bold text-slate-800 flex items-center justify-between">
                                        <span>Unduh Yang Lolos</span>
                                        <span class="text-[10px] text-emerald-700 bg-emerald-50 border border-emerald-200 px-1.5 py-0.5 rounded font-bold"><?= $count_lolos ?> Siswa</span>
                                    </div>
                                    <div class="text-[10px] text-slate-400 truncate">Prioritas penerima kuota resmi</div>
                                </div>
                            </a>
                            <a href="ekspor_excel.php?kategori=tidak_lolos&tahun=<?= urlencode($filter_tahun) ?>" onclick="closeDropdownExcel()" class="flex items-center gap-3 px-3.5 py-2.5 text-xs text-slate-700 hover:bg-slate-50 hover:text-slate-900 transition-colors group">
                                <div class="w-7 h-7 rounded-lg bg-amber-50 group-hover:bg-amber-100 flex items-center justify-center text-amber-600 text-xs shrink-0">
                                    <i class="fa-solid fa-user-clock"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="font-bold text-slate-800 flex items-center justify-between">
                                        <span>Unduh Yang Tidak Lolos</span>
                                        <span class="text-[10px] text-amber-700 bg-amber-50 border border-amber-200 px-1.5 py-0.5 rounded font-bold"><?= $count_cadangan ?> Siswa</span>
                                    </div>
                                    <div class="text-[10px] text-slate-400 truncate">Peserta cadangan di luar kuota</div>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Toggle Dropdown Cetak PDF
function toggleDropdownCetak(e) {
    e.stopPropagation();
    closeDropdownExcel();
    const menu = document.getElementById('menu-dropdown-cetak');
    const arrow = document.getElementById('arrow-dropdown-cetak');
    if (menu) {
        menu.classList.toggle('hidden');
        if (arrow) {
            arrow.classList.toggle('rotate-180');
        }
    }
}

function closeDropdownCetak() {
    const menu = document.getElementById('menu-dropdown-cetak');
    const arrow = document.getElementById('arrow-dropdown-cetak');
    if (menu) {
        menu.classList.add('hidden');
    }
    if (arrow) {
        arrow.classList.remove('rotate-180');
    }
}

// Toggle Dropdown Unduh Excel
function toggleDropdownExcel(e) {
    e.stopPropagation();
    closeDropdownCetak();
    const menu = document.getElementById('menu-dropdown-excel');
    const arrow = document.getElementById('arrow-dropdown-excel');
    if (menu) {
        menu.classList.toggle('hidden');
        if (arrow) {
            arrow.classList.toggle('rotate-180');
        }
    }
}

function closeDropdownExcel() {
    const menu = document.getElementById('menu-dropdown-excel');
    const arrow = document.getElementById('arrow-dropdown-excel');
    if (menu) {
        menu.classList.add('hidden');
    }
    if (arrow) {
        arrow.classList.remove('rotate-180');
    }
}

// Click outside & Escape handler
document.addEventListener('click', function(e) {
    const wrapperCetak = document.getElementById('dropdown-cetak-wrapper');
    if (wrapperCetak && !wrapperCetak.contains(e.target)) {
        closeDropdownCetak();
    }
    const wrapperExcel = document.getElementById('dropdown-excel-wrapper');
    if (wrapperExcel && !wrapperExcel.contains(e.target)) {
        closeDropdownExcel();
    }
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeDropdownCetak();
        closeDropdownExcel();
    }
});
</script>

<?php require_once "footer.php"; ?>
