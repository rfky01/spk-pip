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

// Filter tahun ajaran (default = semua tahun agar seluruh penetapan terverifikasi langsung tampil)
$filter_tahun = $_GET['tahun'] ?? 'all';

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
            <h1 class="text-2xl font-bold text-white">Halaman Laporan PIP (AHP)</h1>
            <p class="text-xs text-slate-400 mt-1">Dokumentasi dan Pelaporan Penetapan Penerima Bantuan PIP Berdasarkan Verifikasi Berkas</p>
        </div>
    </div>

    <?php if ($count_menunggu > 0): ?>
        <div class="p-3.5 bg-amber-950/60 border border-amber-600/70 text-amber-200 rounded-xl text-xs flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-md">
            <div class="flex items-center gap-2.5">
                <i class="fa-solid fa-triangle-exclamation text-amber-400 text-sm shrink-0"></i>
                <span>Perhatian: Terdapat <b class="text-white"><?= $count_menunggu ?> siswa</b> masih menunggu verifikasi berkas <?= ($filter_tahun !== 'all') ? "pada TA $filter_tahun" : "" ?> dan belum dimasukkan ke laporan resmi ini.</span>
            </div>
            <a href="data_calon_penerima.php?status=menunggu" class="px-3 py-1 bg-[#162B4D] hover:bg-[#1E3A5F] text-amber-300 hover:text-amber-200 border border-amber-500/50 rounded-lg text-xs font-semibold whitespace-nowrap transition-all duration-200 hover:scale-[1.02] shadow-sm inline-flex items-center gap-1.5 self-start sm:self-auto">
                <i class="fa-solid fa-arrow-right text-[10px]"></i> Verifikasi Berkas
            </a>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
        <!-- Sisi Kontrol / Parameter (Kiri) -->
        <div class="space-y-4">
            <div class="bg-[#112240] p-5 rounded-xl border border-[#1E3A5F] shadow-sm flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-[#162B4D] border border-[#1E3A5F] flex items-center justify-center text-blue-400 text-xl shrink-0">
                    <i class="fa-solid fa-file-invoice"></i>
                </div>
                <div class="text-xs text-slate-300 font-medium space-y-1">
                    <p>Siswa Terverifikasi: <span class="font-bold text-white text-sm"><?= $total_terverifikasi ?></span></p>
                    <p>Kuota Resmi: <span class="font-bold text-white text-sm"><?= $kuota ?> Siswa</span></p>
                    <p>Rata-rata Skor: <span class="font-bold text-white font-mono text-sm"><?= $rata_skor ?></span></p>
                    <p class="text-[11px] text-slate-400 pt-1.5 border-t border-[#1E3A5F]">Total Pendaftar <?= ($filter_tahun !== 'all') ? "($filter_tahun)" : "(Semua)" ?>: <?= $count_all ?> (Pending: <?= $count_menunggu ?>, Tolak: <?= $count_ditolak ?>)</p>
                </div>
            </div>

            <div class="bg-[#112240] p-6 rounded-xl border border-[#1E3A5F] shadow-sm space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Status Data Laporan</label>
                    <div class="p-2.5 bg-[#162B4D]/70 text-emerald-400 border border-emerald-500/30 rounded-lg text-xs font-semibold flex items-center gap-2">
                        <i class="fa-solid fa-shield-halved text-emerald-400"></i>
                        Khusus Data Berkas Terverifikasi
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1 flex items-center justify-between">
                        <span>Pilih Tahun Ajaran</span>
                        <?php if ($filter_tahun !== 'all' && $filter_tahun === $tahun_aktif): ?>
                            <span class="text-[10px] bg-blue-900/60 text-blue-300 border border-blue-500/40 px-2 py-0.5 rounded font-bold">Tahun Aktif</span>
                        <?php endif; ?>
                    </label>
                    <form action="laporan.php" method="GET" id="form-filter-tahun">
                        <select name="tahun" onchange="this.form.submit()" class="w-full px-3 py-2 border border-[#1E3A5F] rounded-lg bg-[#0B192C] text-white text-xs font-bold focus:ring-1 focus:ring-blue-500 focus:border-blue-500 focus:outline-none shadow-sm cursor-pointer">
                            <option value="all" <?= $filter_tahun === 'all' ? 'selected' : '' ?>>-- Semua Tahun Ajaran --</option>
                            <?php foreach ($list_tahun as $th): ?>
                                <option value="<?= htmlspecialchars($th) ?>" <?= $filter_tahun === $th ? 'selected' : '' ?>>
                                    Tahun Ajaran <?= htmlspecialchars($th) ?> <?= ($th === $tahun_aktif) ? '★ (Aktif)' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                    <p class="text-[10px] text-slate-400 mt-1">Ubah pilihan untuk memilah rekapitulasi data per angkatan.</p>
                </div>
                <a href="cetak_laporan.php?kategori=terverifikasi&tahun=<?= urlencode($filter_tahun) ?>" target="_blank" class="w-full py-2.5 bg-[#162B4D] hover:bg-[#1E3A5F] text-white border border-[#2E5A8F] hover:border-blue-400 font-semibold rounded-lg text-xs transition-all duration-200 hover:scale-[1.02] active:scale-[0.98] shadow-sm flex items-center justify-center gap-2 whitespace-nowrap" title="Cetak seluruh data resmi penetapan untuk tahun ajaran <?= htmlspecialchars($filter_tahun === 'all' ? 'Semua Tahun' : $filter_tahun) ?>">
                    <i class="fa-solid fa-file-pdf text-blue-400"></i> Cetak Semua Laporan (PDF)
                </a>
                <a href="ekspor_excel.php?kategori=terverifikasi&tahun=<?= urlencode($filter_tahun) ?>" class="w-full py-2.5 bg-[#112240] hover:bg-[#162B4D] text-slate-200 border border-[#1E3A5F] hover:border-[#2E5A8F] font-medium rounded-lg text-xs transition-all duration-200 hover:scale-[1.02] active:scale-[0.98] shadow-sm flex items-center justify-center gap-2 whitespace-nowrap" title="Unduh file Excel seluruh data penetapan untuk tahun ajaran <?= htmlspecialchars($filter_tahun === 'all' ? 'Semua Tahun' : $filter_tahun) ?>">
                    <i class="fa-solid fa-file-excel text-emerald-400"></i> Ekspor Semua ke Excel
                </a>
            </div>
        </div>

        <!-- Sisi Detail Hasil Laporan (Kanan) -->
        <div class="bg-[#112240] p-6 rounded-xl border border-[#1E3A5F] shadow-sm lg:col-span-2 space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-white">Preview Daftar Penetapan Penerima PIP (Terverifikasi)</h3>
                    <p class="text-xs text-slate-400 mt-0.5">Tahun Ajaran: <span class="text-blue-300 font-bold"><?= htmlspecialchars($filter_tahun === 'all' ? 'Semua Tahun' : $filter_tahun) ?></span></p>
                </div>
                <span class="text-xs text-slate-400 font-medium bg-[#162B4D] border border-[#1E3A5F] px-2.5 py-1 rounded-lg"><?= $total_terverifikasi ?> Peserta</span>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm min-w-[500px]">
                    <thead>
                        <tr class="bg-[#162B4D]/60 border-b border-[#1E3A5F] text-slate-300 font-bold text-xs">
                            <th class="p-2.5 text-center w-12">Rank</th>
                            <th class="p-2.5">NISN</th>
                            <th class="p-2.5">Nama Siswa</th>
                            <th class="p-2.5 text-center">Sekolah Asal</th>
                            <th class="p-2.5 text-center">Total Skor AHP</th>
                            <th class="p-2.5 text-center">Status Penetapan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#1E3A5F]/60 text-slate-300 font-medium text-xs">
                        <?php if (empty($semua_siswa)): ?>
                            <tr>
                                <td colspan="6" class="p-8 text-center text-slate-400">
                                    <div class="flex flex-col items-center justify-center gap-2">
                                        <i class="fa-solid fa-inbox text-3xl text-slate-500"></i>
                                        <p class="font-semibold text-slate-300">Belum ada calon siswa berstatus Terverifikasi pada Tahun Ajaran <?= htmlspecialchars($filter_tahun === 'all' ? 'ini' : $filter_tahun) ?>.</p>
                                        <?php if ($filter_tahun !== 'all'): ?>
                                            <p class="text-xs text-slate-400 max-w-md">Data terverifikasi berada di tahun ajaran lain (misal: <b>2025/2026</b>). Silakan pilih <b>-- Semua Tahun Ajaran --</b> atau ganti tahun ajaran pada menu pilihan di sebelah kiri.</p>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php else:
                            foreach ($halaman_siswa_laporan as $siswa): 
                                $r = $siswa['global_rank'];
                                $is_lolos = ($r <= $kuota);
                        ?>
                        <tr class="hover:bg-[#162B4D]/40 transition-colors">
                            <td class="p-2.5 font-bold text-center">
                                <?php if ($r === 1): ?>
                                    <span class="inline-flex items-center justify-center w-6 h-6 bg-blue-600 text-white rounded-full text-xs font-bold shadow-sm">1</span>
                                <?php elseif ($r === 2): ?>
                                    <span class="inline-flex items-center justify-center w-6 h-6 bg-slate-700 text-white rounded-full text-xs font-bold">2</span>
                                <?php elseif ($r === 3): ?>
                                    <span class="inline-flex items-center justify-center w-6 h-6 bg-amber-800/80 text-amber-200 border border-amber-600/40 rounded-full text-xs font-bold">3</span>
                                <?php else: ?>
                                    <span class="text-slate-400 font-mono"><?= $r ?>.</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-2.5 font-semibold text-slate-300 font-mono"><?= htmlspecialchars($siswa['nisn']) ?></td>
                            <td class="p-2.5 font-bold text-white">
                                <div class="flex items-center gap-2.5">
                                    <?php if (!empty($siswa['foto']) && file_exists($siswa['foto'])): ?>
                                        <img src="<?= htmlspecialchars($siswa['foto']) ?>" alt="Foto <?= htmlspecialchars($siswa['nama']) ?>" class="w-8 h-8 rounded-lg object-cover border border-[#1E3A5F] shrink-0 shadow-xs">
                                    <?php else: ?>
                                        <div class="w-8 h-8 rounded-lg bg-[#07101E] border border-[#1E3A5F] text-blue-300 flex items-center justify-center font-bold text-xs shrink-0">
                                            <?= strtoupper(substr($siswa['nama'], 0, 1)) ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="min-w-0">
                                        <span class="block truncate font-bold text-white"><?= htmlspecialchars($siswa['nama']) ?></span>
                                        <span class="text-[10px] text-slate-400 font-normal">Kelas VII &bull; TA <?= htmlspecialchars($siswa['tahun'] ?? '-') ?></span>
                                    </div>
                                </div>
                            </td>
                            <td class="p-2.5 text-center">
                                <span class="font-bold text-slate-200 block"><?= htmlspecialchars($siswa['sekolah_asal'] ?: '-') ?></span>
                                <span class="text-[10px] text-slate-400 font-normal">Siswa Baru</span>
                            </td>
                            <td class="p-2.5 text-center font-bold text-blue-300 font-mono"><?= number_format($siswa['skor_akhir'], 4) ?></td>
                            <td class="p-2.5 text-center">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold <?= $is_lolos ? 'bg-emerald-950/80 text-emerald-300 border border-emerald-500/40' : 'bg-slate-800/80 text-slate-300 border border-slate-600/40' ?>">
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
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-4 border-t border-[#1E3A5F] text-xs">
                <div class="text-slate-400">
                    Menampilkan <span class="font-bold text-white"><?= $start_display ?></span> - <span class="font-bold text-white"><?= $end_display ?></span> dari <span class="font-bold text-white"><?= $total_terverifikasi ?></span> siswa terverifikasi
                </div>
                <?php if ($total_pages_laporan > 1): ?>
                <div class="flex items-center gap-1">
                    <?php
                    $url_prev = '?' . http_build_query(array_merge($_GET, ['page' => max(1, $page_laporan - 1)]));
                    $url_next = '?' . http_build_query(array_merge($_GET, ['page' => min($total_pages_laporan, $page_laporan + 1)]));
                    ?>
                    <!-- Prev Button -->
                    <?php if ($page_laporan > 1): ?>
                        <a href="<?= htmlspecialchars($url_prev) ?>" class="px-2.5 py-1.5 rounded-lg border border-[#1E3A5F] bg-[#0B192C] hover:bg-[#162B4D] text-slate-300 font-medium transition-colors flex items-center gap-1">
                            <i class="fa-solid fa-chevron-left text-[10px]"></i> Sebelumnya
                        </a>
                    <?php else: ?>
                        <span class="px-2.5 py-1.5 rounded-lg border border-[#1E3A5F]/40 bg-[#0B192C]/40 text-slate-600 cursor-not-allowed flex items-center gap-1 font-medium">
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
                            <a href="<?= htmlspecialchars($url_page) ?>" class="w-8 h-8 rounded-lg flex items-center justify-center font-bold transition-all <?= $p == $page_laporan ? 'bg-[#162B4D] text-white border border-[#2E5A8F] shadow-sm' : 'bg-[#0B192C] hover:bg-[#162B4D] border border-[#1E3A5F] text-slate-300' ?>">
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
                        <a href="<?= htmlspecialchars($url_next) ?>" class="px-2.5 py-1.5 rounded-lg border border-[#1E3A5F] bg-[#0B192C] hover:bg-[#162B4D] text-slate-300 font-medium transition-colors flex items-center gap-1">
                            Selanjutnya <i class="fa-solid fa-chevron-right text-[10px]"></i>
                        </a>
                    <?php else: ?>
                        <span class="px-2.5 py-1.5 rounded-lg border border-[#1E3A5F]/40 bg-[#0B192C]/40 text-slate-600 cursor-not-allowed flex items-center gap-1 font-medium">
                            Selanjutnya <i class="fa-solid fa-chevron-right text-[10px]"></i>
                        </span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <div class="flex flex-wrap justify-end items-center gap-2.5 pt-4 border-t border-[#1E3A5F]">
                <!-- Dropdown Cetak Dokumen Resmi -->
                <div class="relative inline-block text-left" id="dropdown-cetak-wrapper">
                    <button type="button" id="btn-dropdown-cetak" onclick="toggleDropdownCetak(event)" class="px-4 py-2 bg-[#162B4D] hover:bg-[#1E3A5F] text-white border border-[#2E5A8F] hover:border-blue-400 font-semibold rounded-lg text-xs transition-all duration-200 hover:scale-[1.02] active:scale-[0.98] shadow-sm flex items-center gap-2 cursor-pointer focus:outline-none whitespace-nowrap shrink-0" title="Pilih opsi cetak laporan">
                        <i class="fa-solid fa-print text-blue-400"></i>
                        <span>Cetak Dokumen Resmi</span>
                        <i class="fa-solid fa-chevron-down text-[10px] ml-0.5 transition-transform duration-200" id="arrow-dropdown-cetak"></i>
                    </button>
                    
                    <!-- Dropdown Menu (Muncul ke atas agar tidak terpotong) -->
                    <div id="menu-dropdown-cetak" class="hidden absolute right-0 bottom-full mb-2 w-72 bg-[#112240] rounded-xl shadow-2xl border border-[#1E3A5F] z-50 p-2.5 space-y-2">
                        <div class="px-1 pb-1 border-b border-[#1E3A5F]">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Pilih Opsi Cetak Laporan</p>
                        </div>
                        <div class="space-y-1.5 pt-0.5">
                            <a href="cetak_laporan.php?kategori=terverifikasi&tahun=<?= urlencode($filter_tahun) ?>" target="_blank" onclick="closeDropdownCetak()" 
                               class="w-full p-2.5 rounded-xl bg-[#162B4D] hover:bg-[#1E3A5F] text-white border border-[#2E5A8F] hover:border-blue-400 transition-all duration-200 hover:scale-[1.01] active:scale-[0.99] shadow-sm flex items-center justify-between group cursor-pointer">
                                <div class="text-left">
                                    <span class="font-bold text-white text-xs block group-hover:text-blue-200 transition-colors">Cetak Semua</span>
                                    <span class="text-[10px] text-slate-300 block mt-0.5">Seluruh siswa terverifikasi</span>
                                </div>
                                <div class="shrink-0 ml-2">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-[#112240] text-blue-300 border border-[#1E3A5F] group-hover:border-[#2E5A8F] transition-colors whitespace-nowrap">
                                        <?= $total_terverifikasi ?> Siswa
                                    </span>
                                </div>
                            </a>
                            <a href="cetak_laporan.php?kategori=lolos&tahun=<?= urlencode($filter_tahun) ?>" target="_blank" onclick="closeDropdownCetak()" 
                               class="w-full p-2.5 rounded-xl bg-[#162B4D] hover:bg-[#1E3A5F] text-white border border-[#2E5A8F] hover:border-blue-400 transition-all duration-200 hover:scale-[1.01] active:scale-[0.99] shadow-sm flex items-center justify-between group cursor-pointer">
                                <div class="text-left">
                                    <span class="font-bold text-white text-xs block group-hover:text-blue-200 transition-colors">Cetak Yang Lolos</span>
                                    <span class="text-[10px] text-slate-300 block mt-0.5">Prioritas penerima kuota resmi</span>
                                </div>
                                <div class="shrink-0 ml-2">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-[#112240] text-blue-300 border border-[#1E3A5F] group-hover:border-[#2E5A8F] transition-colors whitespace-nowrap">
                                        <?= $count_lolos ?> Siswa
                                    </span>
                                </div>
                            </a>
                            <a href="cetak_laporan.php?kategori=tidak_lolos&tahun=<?= urlencode($filter_tahun) ?>" target="_blank" onclick="closeDropdownCetak()" 
                               class="w-full p-2.5 rounded-xl bg-[#162B4D] hover:bg-[#1E3A5F] text-white border border-[#2E5A8F] hover:border-blue-400 transition-all duration-200 hover:scale-[1.01] active:scale-[0.99] shadow-sm flex items-center justify-between group cursor-pointer">
                                <div class="text-left">
                                    <span class="font-bold text-white text-xs block group-hover:text-blue-200 transition-colors">Cetak Yang Tidak Lolos</span>
                                    <span class="text-[10px] text-slate-300 block mt-0.5">Peserta cadangan di luar kuota</span>
                                </div>
                                <div class="shrink-0 ml-2">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-[#112240] text-blue-300 border border-[#1E3A5F] group-hover:border-[#2E5A8F] transition-colors whitespace-nowrap">
                                        <?= $count_cadangan ?> Siswa
                                    </span>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Dropdown Unduh Excel -->
                <div class="relative inline-block text-left" id="dropdown-excel-wrapper">
                    <button type="button" id="btn-dropdown-excel" onclick="toggleDropdownExcel(event)" class="px-4 py-2 bg-[#112240] hover:bg-[#162B4D] text-slate-200 border border-[#1E3A5F] hover:border-[#2E5A8F] font-medium rounded-lg text-xs transition-all duration-200 hover:scale-[1.02] active:scale-[0.98] shadow-sm flex items-center gap-2 cursor-pointer focus:outline-none whitespace-nowrap shrink-0" title="Pilih opsi unduh file Excel">
                        <i class="fa-solid fa-file-excel text-emerald-400"></i>
                        <span>Unduh Excel</span>
                        <i class="fa-solid fa-chevron-down text-[10px] ml-0.5 transition-transform duration-200" id="arrow-dropdown-excel"></i>
                    </button>
                    
                    <!-- Dropdown Menu (Muncul ke atas) -->
                    <div id="menu-dropdown-excel" class="hidden absolute right-0 bottom-full mb-2 w-72 bg-[#112240] rounded-xl shadow-2xl border border-[#1E3A5F] z-50 p-2.5 space-y-2">
                        <div class="px-1 pb-1 border-b border-[#1E3A5F]">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Pilih Opsi Ekspor Excel</p>
                        </div>
                        <div class="space-y-1.5 pt-0.5">
                            <a href="ekspor_excel.php?kategori=terverifikasi&tahun=<?= urlencode($filter_tahun) ?>" onclick="closeDropdownExcel()" 
                               class="w-full p-2.5 rounded-xl bg-[#162B4D] hover:bg-[#1E3A5F] text-white border border-[#2E5A8F] hover:border-emerald-400 transition-all duration-200 hover:scale-[1.01] active:scale-[0.99] shadow-sm flex items-center justify-between group cursor-pointer">
                                <div class="text-left">
                                    <span class="font-bold text-white text-xs block group-hover:text-emerald-200 transition-colors">Unduh Semua</span>
                                    <span class="text-[10px] text-slate-300 block mt-0.5">Semua data siswa terverifikasi</span>
                                </div>
                                <div class="shrink-0 ml-2">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-[#112240] text-emerald-300 border border-[#1E3A5F] group-hover:border-[#2E5A8F] transition-colors whitespace-nowrap">
                                        <?= $total_terverifikasi ?> Siswa
                                    </span>
                                </div>
                            </a>
                            <a href="ekspor_excel.php?kategori=lolos&tahun=<?= urlencode($filter_tahun) ?>" onclick="closeDropdownExcel()" 
                               class="w-full p-2.5 rounded-xl bg-[#162B4D] hover:bg-[#1E3A5F] text-white border border-[#2E5A8F] hover:border-emerald-400 transition-all duration-200 hover:scale-[1.01] active:scale-[0.99] shadow-sm flex items-center justify-between group cursor-pointer">
                                <div class="text-left">
                                    <span class="font-bold text-white text-xs block group-hover:text-emerald-200 transition-colors">Unduh Yang Lolos</span>
                                    <span class="text-[10px] text-slate-300 block mt-0.5">Prioritas penerima kuota resmi</span>
                                </div>
                                <div class="shrink-0 ml-2">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-[#112240] text-emerald-300 border border-[#1E3A5F] group-hover:border-[#2E5A8F] transition-colors whitespace-nowrap">
                                        <?= $count_lolos ?> Siswa
                                    </span>
                                </div>
                            </a>
                            <a href="ekspor_excel.php?kategori=tidak_lolos&tahun=<?= urlencode($filter_tahun) ?>" onclick="closeDropdownExcel()" 
                               class="w-full p-2.5 rounded-xl bg-[#162B4D] hover:bg-[#1E3A5F] text-white border border-[#2E5A8F] hover:border-emerald-400 transition-all duration-200 hover:scale-[1.01] active:scale-[0.99] shadow-sm flex items-center justify-between group cursor-pointer">
                                <div class="text-left">
                                    <span class="font-bold text-white text-xs block group-hover:text-emerald-200 transition-colors">Unduh Yang Tidak Lolos</span>
                                    <span class="text-[10px] text-slate-300 block mt-0.5">Peserta cadangan di luar kuota</span>
                                </div>
                                <div class="shrink-0 ml-2">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-[#112240] text-emerald-300 border border-[#1E3A5F] group-hover:border-[#2E5A8F] transition-colors whitespace-nowrap">
                                        <?= $count_cadangan ?> Siswa
                                    </span>
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
