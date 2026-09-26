<?php
// dashboard.php - Halaman Utama Dashboard SPK PIP
$page_title = "Dashboard";
require_once "header.php";

$msg = "";
$msg_type = "";

// A. Handle Update Kuota dari Popup Dashboard
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_kuota') {
    $kuota_baru = (int)($_POST['kuota_pip'] ?? 0);
    if ($kuota_baru > 0) {
        mysqli_query($koneksi, "UPDATE `pengaturan` SET `kuota_pip` = $kuota_baru WHERE `id` = 1");
        $pengaturan['kuota_pip'] = $kuota_baru;
        $msg = "Kuota resmi penerima PIP berhasil diperbarui menjadi $kuota_baru siswa!";
        $msg_type = "success";
    } else {
        $msg = "Jumlah kuota penerima PIP harus bernilai lebih dari 0!";
        $msg_type = "error";
    }
}

// B. Handle Verifikasi Cepat dari Popup Dashboard
if (isset($_GET['action']) && $_GET['action'] === 'verifikasi_siswa') {
    $id_v = (int)($_GET['id'] ?? 0);
    if ($id_v > 0) {
        mysqli_query($koneksi, "UPDATE `calon_penerima` SET `status_verifikasi` = 'Terverifikasi' WHERE `id_siswa` = $id_v");
        $msg = "Pengajuan berkas siswa berhasil diverifikasi dan disetujui!";
        $msg_type = "success";
    }
}

require_once "sidebar.php";

// Ambil bobot kriteria
$res_kriteria = mysqli_query($koneksi, "SELECT * FROM `kriteria` ORDER BY `kode_kriteria` ASC");
$kriteria_db = [];
while ($row = mysqli_fetch_assoc($res_kriteria)) {
    $kriteria_db[$row['kode_kriteria']] = (float)$row['bobot'];
}

// Ambil data siswa yang TERVERIFIKASI & hitung skor
$res_calon = mysqli_query($koneksi, "SELECT * FROM `calon_penerima` WHERE `status_verifikasi` = 'Terverifikasi' ORDER BY `id_siswa` ASC");
$siswa_terverifikasi = [];
while ($s = mysqli_fetch_assoc($res_calon)) {
    $skor = ($s['penghasilan'] * ($kriteria_db['C1'] ?? 0.4165)) +
            ($s['tanggungan']  * ($kriteria_db['C2'] ?? 0.2619)) +
            ($s['kondisi_rumah']* ($kriteria_db['C3'] ?? 0.1608)) +
            ($s['prestasi']    * ($kriteria_db['C4'] ?? 0.0985)) +
            ($s['jarak']       * ($kriteria_db['C5'] ?? 0.0623));
    $s['skor_akhir'] = round($skor, 4);
    $siswa_terverifikasi[] = $s;
}

// Urutkan ranking
usort($siswa_terverifikasi, function($a, $b) {
    return $b['skor_akhir'] <=> $a['skor_akhir'];
});

$total_terverifikasi = count($siswa_terverifikasi);
$skor_tertinggi = !empty($siswa_terverifikasi) ? $siswa_terverifikasi[0]['skor_akhir'] : 0.00;
$nama_tertinggi = !empty($siswa_terverifikasi) ? $siswa_terverifikasi[0]['nama'] : '-';

// Ambil daftar siswa MENUNGGU VERIFIKASI untuk popup
$res_menunggu_list = mysqli_query($koneksi, "SELECT * FROM `calon_penerima` WHERE `status_verifikasi` = 'Menunggu Verifikasi' ORDER BY `id_siswa` DESC");
$siswa_menunggu_list = [];
while ($m = mysqli_fetch_assoc($res_menunggu_list)) {
    $siswa_menunggu_list[] = $m;
}

// Hitung statistik pendaftaran
$count_all = mysqli_num_rows(mysqli_query($koneksi, "SELECT id_siswa FROM `calon_penerima`"));
$count_menunggu = count($siswa_menunggu_list);
$count_ditolak = mysqli_num_rows(mysqli_query($koneksi, "SELECT id_siswa FROM `calon_penerima` WHERE `status_verifikasi` = 'Ditolak'"));
$kuota = (int)$pengaturan['kuota_pip'];

// Definisi label subkriteria untuk rincian skor tertinggi
$subkriteria_detail = [
    'C1' => [
        'nama' => 'Penghasilan Orang Tua',
        'key'  => 'penghasilan',
        'bobot'=> $kriteria_db['C1'] ?? 0.4165,
        'label'=> [
            5 => '< Rp 500.000',
            4 => 'Rp 600.000 - Rp 1.000.000',
            3 => 'Rp 1.000.000 - Rp 2.000.000',
            2 => 'Rp 2.000.000 - Rp 3.000.000',
            1 => '> Rp 4.000.000'
        ]
    ],
    'C2' => [
        'nama' => 'Tanggungan Keluarga',
        'key'  => 'tanggungan',
        'bobot'=> $kriteria_db['C2'] ?? 0.2619,
        'label'=> [
            5 => '> 5 Orang',
            4 => '4 Orang',
            3 => '3 Orang',
            2 => '2 Orang',
            1 => '1 Orang'
        ]
    ],
    'C3' => [
        'nama' => 'Kondisi Rumah',
        'key'  => 'kondisi_rumah',
        'bobot'=> $kriteria_db['C3'] ?? 0.1608,
        'label'=> [
            5 => 'Tidak Layak',
            4 => 'Dinding Kayu',
            3 => 'Dinding Batu Atap Seng',
            2 => 'Dinding Batu Atap Genteng',
            1 => 'Tembok Keramik'
        ]
    ],
    'C4' => [
        'nama' => 'Prestasi Akademik',
        'key'  => 'prestasi',
        'bobot'=> $kriteria_db['C4'] ?? 0.0985,
        'label'=> [
            5 => 'Juara 1 - 3 Kabupaten',
            4 => 'Juara Harapan',
            3 => 'Juara Kelas 1 - 3',
            2 => 'Peringkat 10 Besar',
            1 => 'Peringkat 20 Besar'
        ]
    ],
    'C5' => [
        'nama' => 'Jarak ke Sekolah',
        'key'  => 'jarak',
        'bobot'=> $kriteria_db['C5'] ?? 0.0623,
        'label'=> [
            5 => '> 5 km',
            4 => '3 – 5 km',
            3 => '2 km',
            2 => '1 km',
            1 => '< 1 km'
        ]
    ],
];
?>

<div class="space-y-6 w-full">
    <?php if (!empty($msg)): ?>
        <div class="p-4 rounded-xl text-xs flex items-center justify-between <?= $msg_type === 'success' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-rose-50 text-rose-800 border border-rose-200' ?> shadow-sm">
            <div class="flex items-center gap-2">
                <i class="fa-solid <?= $msg_type === 'success' ? 'fa-circle-check text-emerald-600' : 'fa-circle-exclamation text-rose-600' ?> text-sm"></i>
                <span class="font-semibold"><?= htmlspecialchars($msg) ?></span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark"></i></button>
        </div>
    <?php endif; ?>

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Dashboard Seleksi Siswa PIP</h1>
        </div>
        <div class="flex items-center gap-2">
            <a href="daftar_siswa.php" target="_blank" class="px-3 py-1.5 bg-white text-slate-700 hover:bg-slate-50 text-xs font-semibold rounded-lg border border-slate-200 flex items-center gap-1.5 transition-colors shadow-sm">
                <i class="fa-solid fa-arrow-up-right-from-square text-slate-400"></i> Pendaftaran Siswa (Publik)
            </a>
            <span class="px-3 py-1.5 bg-slate-100 text-slate-700 text-xs font-semibold rounded-lg border border-slate-200">
                T.A. <?= htmlspecialchars($pengaturan['tahun_ajaran']) ?>
            </span>
        </div>
    </div>

    <?php
    $today_dash = date('Y-m-d');
    $tgl_buka_dash = !empty($pengaturan['tgl_buka_pengajuan']) ? $pengaturan['tgl_buka_pengajuan'] : '2026-09-01';
    $tgl_tutup_dash = !empty($pengaturan['tgl_tutup_pengajuan']) ? $pengaturan['tgl_tutup_pengajuan'] : '2026-10-31';

    $is_pendaftaran_buka = ($today_dash >= $tgl_buka_dash && $today_dash <= $tgl_tutup_dash);
    $status_pendaftaran_teks = 'Dibuka (Aktif)';
    $status_badge_class = 'bg-emerald-100 text-emerald-800 border-emerald-300';
    $status_icon = 'fa-circle-check text-emerald-600';

    if ($today_dash < $tgl_buka_dash) {
        $status_pendaftaran_teks = 'Belum Dibuka';
        $status_badge_class = 'bg-amber-100 text-amber-800 border-amber-300';
        $status_icon = 'fa-clock text-amber-600';
    } elseif ($today_dash > $tgl_tutup_dash) {
        $status_pendaftaran_teks = 'Telah Ditutup';
        $status_badge_class = 'bg-rose-100 text-rose-800 border-rose-300';
        $status_icon = 'fa-calendar-xmark text-rose-600';
    }
    ?>

    <!-- Status Jadwal Pendaftaran PIP Mandiri -->
    <div class="p-4 bg-white border border-slate-200 rounded-xl text-xs flex flex-col md:flex-row md:items-center justify-between gap-3 shadow-sm">
        <div>
            <div class="flex items-center gap-2 flex-wrap">
                <span class="font-bold text-slate-800 text-sm">Status Pendaftaran PIP Mandiri:</span>
                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold border <?= $status_badge_class ?>">
                    <?= $status_pendaftaran_teks ?>
                </span>
            </div>
            <p class="text-slate-500 mt-1">
                Rentang Waktu: <strong><?= format_tgl_indo($tgl_buka_dash) ?></strong> s.d. <strong><?= format_tgl_indo($tgl_tutup_dash) ?></strong>
                <span class="text-slate-300 mx-2">|</span>
                Hari Ini: <span class="text-slate-700 font-medium"><?= format_tgl_indo($today_dash) ?></span>
            </p>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" onclick="openProfileModal()" class="px-3.5 py-2 bg-slate-800 hover:bg-slate-900 text-white rounded-lg font-semibold text-xs shadow transition-colors cursor-pointer">
                Atur Jadwal Buka / Tutup
            </button>
        </div>
    </div>

    
    <!-- Ringkasan Kartu Atas (4 Metrik Statistik Formal & Minimalis) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <!-- 1. KOTAK SISWA TERVERIFIKASI -->
        <div onclick="openModal('modalTerverifikasi')" 
             class="bg-white rounded-xl border border-slate-200 border-t-4 border-t-slate-800 p-5 shadow-sm hover:shadow-md hover:border-slate-400 transition-all cursor-pointer flex flex-col justify-between group">
            <div>
                <h3 class="text-base font-extrabold text-slate-900 tracking-tight">Peserta Terverifikasi</h3>
                <p class="text-xs text-slate-500 mt-0.5">Berkas pendaftaran sah</p>
                <div class="mt-4 flex items-baseline gap-2">
                    <span class="text-3xl font-extrabold text-slate-900 tracking-tight"><?= $total_terverifikasi ?></span>
                    <span class="text-xs font-semibold text-slate-500">Peserta Didik</span>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                <span class="text-slate-500 text-[11px]">Dari total <strong><?= $count_all ?></strong> pendaftar</span>
                <span class="text-[11px] font-semibold text-slate-700 group-hover:text-slate-900 transition-colors">
                    Daftar Siswa &rarr;
                </span>
            </div>
        </div>

        <!-- 2. KOTAK MENUNGGU VERIFIKASI -->
        <div onclick="openModal('modalMenunggu')" 
             class="bg-white rounded-xl border border-slate-200 border-t-4 border-t-slate-800 p-5 shadow-sm hover:shadow-md hover:border-slate-400 transition-all cursor-pointer flex flex-col justify-between group">
            <div>
                <h3 class="text-base font-extrabold text-slate-900 tracking-tight">Menunggu Verifikasi</h3>
                <p class="text-xs text-slate-500 mt-0.5">Pemeriksaan berkas fisik</p>
                <div class="mt-4 flex items-baseline gap-2">
                    <span class="text-3xl font-extrabold text-slate-900 tracking-tight"><?= $count_menunggu ?></span>
                    <span class="text-xs font-semibold text-slate-500">Berkas Antrean</span>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                <?php if ($count_menunggu > 0): ?>
                    <span class="font-semibold text-slate-800 text-[11px]">Perlu verifikasi fisik</span>
                    <span class="text-[11px] font-semibold text-slate-700 group-hover:text-slate-900 group-hover:underline transition-colors">
                        Periksa &rarr;
                    </span>
                <?php else: ?>
                    <span class="font-medium text-slate-600 text-[11px]">Antrean nihil</span>
                    <span class="text-[11px] font-medium text-slate-400">Tuntas</span>
                <?php endif; ?>
            </div>
        </div>

        <!-- 3. KOTAK SKOR TERTINGGI (AHP) -->
        <div onclick="openModal('modalSkorTertinggi')" 
             class="bg-white rounded-xl border border-slate-200 border-t-4 border-t-slate-800 p-5 shadow-sm hover:shadow-md hover:border-slate-400 transition-all cursor-pointer flex flex-col justify-between group">
            <div>
                <h3 class="text-base font-extrabold text-slate-900 tracking-tight">Skor Prioritas Utama</h3>
                <p class="text-xs text-slate-500 mt-0.5">Peringkat #1 Metode AHP</p>
                <div class="mt-4 flex items-baseline gap-2">
                    <span class="text-3xl font-extrabold text-slate-900 font-mono tracking-tight"><?= number_format($skor_tertinggi, 4) ?></span>
                    <span class="text-[10px] font-medium text-slate-400 uppercase">Maks 5.00</span>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                <span class="text-[11px] font-medium text-slate-600 truncate max-w-[150px] block" title="<?= htmlspecialchars($nama_tertinggi) ?>">
                    <?= htmlspecialchars($nama_tertinggi) ?>
                </span>
                <span class="text-[11px] font-semibold text-slate-700 group-hover:text-slate-900 transition-colors">
                    Rincian &rarr;
                </span>
            </div>
        </div>

        <!-- 4. KOTAK KUOTA RESMI PIP -->
        <div onclick="openModal('modalKuota')" 
             class="bg-white rounded-xl border border-slate-200 border-t-4 border-t-slate-800 p-5 shadow-sm hover:shadow-md hover:border-slate-400 transition-all cursor-pointer flex flex-col justify-between group">
            <div>
                <h3 class="text-base font-extrabold text-slate-900 tracking-tight">Alokasi Kuota Beasiswa</h3>
                <p class="text-xs text-slate-500 mt-0.5">Ketetapan Penerima PIP</p>
                <div class="mt-4 flex items-baseline gap-2">
                    <span class="text-3xl font-extrabold text-slate-900 tracking-tight"><?= $kuota ?></span>
                    <span class="text-xs font-semibold text-slate-500">Peserta Didik</span>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs">
                <span class="text-[11px] text-slate-500">T.A. <?= htmlspecialchars($pengaturan['tahun_ajaran']) ?></span>
                <span class="text-[11px] font-semibold text-slate-700 group-hover:text-slate-900 group-hover:underline transition-colors">
                    Atur Kuota &rarr;
                </span>
            </div>
        </div>
    </div>

    <!-- Grafik Sederhana & Tabel Ringkasan Top 5 -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Grafik Skor -->
        <div class="bg-white p-6 rounded-xl border border-slate-200/90 shadow-sm">
            <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
                <h3 class="text-sm font-bold text-slate-800">Distribusi Skor Terverifikasi (Top 5)</h3>
                <span class="text-[11px] text-slate-400 font-medium">Skala Maks: 5.00</span>
            </div>
            
            <div class="flex items-end justify-around h-56 pt-6 px-2 border-b border-l border-slate-200">
                <?php 
                $top5 = array_slice($siswa_terverifikasi, 0, 5);
                if (empty($top5)): ?>
                    <div class="text-center text-slate-400 text-xs py-10 w-full">Belum ada siswa dengan status Terverifikasi.</div>
                <?php else:
                    foreach ($top5 as $t): 
                        $height_pct = min(100, max(20, ($t['skor_akhir'] / 5.0) * 100));
                ?>
                <div class="flex flex-col items-center justify-end h-full w-14">
                    <div class="w-10 sm:w-11 bg-slate-800 hover:bg-slate-900 rounded-t text-center text-[11px] text-white font-bold py-1 flex items-center justify-center transition-all shadow-sm" style="height: <?= $height_pct ?>%;">
                        <span><?= number_format($t['skor_akhir'], 2) ?></span>
                    </div>
                    <div class="text-[10px] text-slate-600 font-medium truncate w-14 text-center mt-2" title="<?= htmlspecialchars($t['nama']) ?>">
                        <?= htmlspecialchars(substr($t['nama'], 0, 8)) ?>
                    </div>
                </div>
                <?php endforeach; endif; ?>
            </div>
            <div class="text-[11px] text-slate-400 text-right mt-6">Dihitung berdasarkan 5 kriteria berbobot AHP</div>
        </div>

        <!-- Tabel Ringkasan Top 5 -->
        <div class="bg-white p-6 rounded-xl border border-slate-200/90 shadow-sm">
            <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-100">
                <h3 class="text-sm font-bold text-slate-800">Peringkat Teratas Calon Penerima</h3>
                <a href="ranking.php" class="text-xs text-slate-600 hover:text-slate-900 hover:underline font-semibold">Lihat Semua &rarr;</a>
            </div>
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-slate-100 text-slate-400 uppercase text-[11px] font-semibold tracking-wider">
                        <th class="pb-2">Nama Siswa</th>
                        <th class="pb-2 text-center">Rank</th>
                        <th class="pb-2 text-right">Skor AHP</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                    <?php 
                    $top_list = array_slice($siswa_terverifikasi, 0, 5);
                    if (empty($top_list)): ?>
                        <tr><td colspan="3" class="py-6 text-center text-slate-400 text-xs">Belum ada siswa terverifikasi</td></tr>
                    <?php else:
                        $r = 1;
                        foreach ($top_list as $s): 
                            $is_lolos = ($r <= $kuota);
                    ?>
                    <tr class="hover:bg-slate-50/70">
                        <td class="py-2.5">
                            <span class="font-bold text-slate-800 text-xs block"><?= htmlspecialchars($s['nama']) ?></span>
                            <span class="text-[10px] text-slate-400 block"><?= htmlspecialchars($s['sekolah_asal'] ?: 'Kelas VII') ?> &bull; <?= htmlspecialchars($s['nisn']) ?></span>
                        </td>
                        <td class="py-2.5 text-center">
                            <?php if ($r === 1): ?>
                                <span class="px-2 py-0.5 bg-slate-900 text-white rounded text-[11px] font-bold">#1</span>
                            <?php elseif ($r === 2): ?>
                                <span class="px-2 py-0.5 bg-slate-200 text-slate-800 rounded text-[11px] font-bold">#2</span>
                            <?php elseif ($r === 3): ?>
                                <span class="px-2 py-0.5 bg-slate-100 text-slate-700 border border-slate-200 rounded text-[11px] font-bold">#3</span>
                            <?php else: ?>
                                <span class="text-slate-500 font-semibold text-xs">#<?= $r ?></span>
                            <?php endif; ?>
                            <?php if ($is_lolos): ?>
                                <span class="block text-[9px] text-emerald-700 font-semibold mt-0.5">Lolos</span>
                            <?php endif; ?>
                        </td>
                        <td class="py-2.5 text-right font-bold text-slate-900 text-xs font-mono"><?= number_format($s['skor_akhir'], 4) ?></td>
                    </tr>
                    <?php 
                        $r++;
                        endforeach; 
                    endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- 1. MODAL DAFTAR SISWA TERVERIFIKASI        -->
<!-- ========================================== -->
<div id="modalTerverifikasi" class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden flex items-center justify-center z-50 p-4 transition-all">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl border border-slate-200 max-h-[90vh] flex flex-col overflow-hidden">
        <!-- Header Modal -->
        <div class="flex items-center justify-between p-5 border-b border-slate-100 bg-slate-50/70">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-slate-900 text-white flex items-center justify-center text-base shadow-sm">
                    <i class="fa-solid fa-user-check"></i>
                </div>
                <div>
                    <h3 class="font-bold text-slate-900 text-base flex items-center gap-2">
                        Daftar Siswa Terverifikasi PIP
                        <span class="px-2.5 py-0.5 bg-emerald-50 text-emerald-700 rounded-full text-xs font-semibold border border-emerald-200">
                            <?= $total_terverifikasi ?> Siswa
                        </span>
                    </h3>
                    <p class="text-xs text-slate-500 mt-0.5">Siswa yang berkas fisiknya telah disetujui dan masuk dalam pemeringkatan AHP</p>
                </div>
            </div>
            <button type="button" onclick="closeModal('modalTerverifikasi')" class="w-8 h-8 rounded-lg hover:bg-slate-200/70 text-slate-400 hover:text-slate-700 flex items-center justify-center transition-colors cursor-pointer">
                <i class="fa-solid fa-xmark text-base"></i>
            </button>
        </div>

        <!-- Toolbar Pencarian di dalam Modal -->
        <div class="px-5 py-3 border-b border-slate-100 bg-white flex items-center justify-between gap-3">
            <div class="relative flex-1 max-w-sm">
                <input type="text" id="searchTerverifikasi" onkeyup="filterModalTable('searchTerverifikasi', 'tableModalTerverifikasi')" 
                    placeholder="Cari nama siswa atau NISN..." 
                    class="w-full pl-8 pr-3 py-1.5 border border-slate-200 rounded-lg text-xs bg-slate-50 focus:bg-white focus:outline-none focus:ring-1 focus:ring-slate-400">
                <i class="fa-solid fa-magnifying-glass absolute left-2.5 top-2.5 text-slate-400 text-xs"></i>
            </div>
            <span class="text-xs text-slate-500 font-medium hidden sm:inline">
                Kuota Resmi PIP: <strong class="text-slate-800"><?= $kuota ?> Siswa</strong>
            </span>
        </div>

        <!-- Body Modal: Tabel Siswa Terverifikasi -->
        <div class="p-5 overflow-y-auto flex-1">
            <?php if (empty($siswa_terverifikasi)): ?>
                <div class="py-12 text-center text-slate-400 text-xs">
                    <i class="fa-solid fa-users-slash text-3xl mb-2 text-slate-300 block"></i>
                    Belum ada siswa dengan status Terverifikasi.
                </div>
            <?php else: ?>
                <table class="w-full text-left text-xs min-w-[650px]" id="tableModalTerverifikasi">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold uppercase text-[11px] tracking-wider">
                            <th class="p-3 text-center w-14">Rank</th>
                            <th class="p-3">NISN & Nama Siswa</th>
                            <th class="p-3">Sekolah Asal / Kelas</th>
                            <th class="p-3 text-center">Skor AHP</th>
                            <th class="p-3 text-center">Status Kelolosan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                        <?php 
                        $r = 1;
                        foreach ($siswa_terverifikasi as $s): 
                            $is_lolos = ($r <= $kuota);
                        ?>
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <td class="p-3 text-center font-bold">
                                <?php if ($r === 1): ?>
                                    <span class="px-2 py-0.5 bg-slate-900 text-white rounded text-[11px]">#1</span>
                                <?php elseif ($r === 2): ?>
                                    <span class="px-2 py-0.5 bg-slate-200 text-slate-800 rounded text-[11px]">#2</span>
                                <?php elseif ($r === 3): ?>
                                    <span class="px-2 py-0.5 bg-slate-100 text-slate-700 border border-slate-200 rounded text-[11px]">#3</span>
                                <?php else: ?>
                                    <span class="text-slate-500 text-xs">#<?= $r ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="p-3">
                                <span class="font-bold text-slate-900 block"><?= htmlspecialchars($s['nama']) ?></span>
                                <span class="text-[10px] text-slate-400 font-mono">NISN: <?= htmlspecialchars($s['nisn']) ?></span>
                            </td>
                            <td class="p-3 text-slate-600">
                                <?= htmlspecialchars($s['sekolah_asal'] ?: 'SMP Tunas Bangsa') ?>
                                <span class="text-[10px] text-slate-400 block"><?= htmlspecialchars($s['kelas'] ?: 'Kelas VII') ?></span>
                            </td>
                            <td class="p-3 text-center font-mono font-bold text-slate-900 text-sm">
                                <?= number_format($s['skor_akhir'], 4) ?>
                            </td>
                            <td class="p-3 text-center whitespace-nowrap">
                                <?php if ($is_lolos): ?>
                                    <span class="px-2.5 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-lg text-[11px] font-bold inline-flex items-center gap-1">
                                        <i class="fa-solid fa-circle-check text-[10px]"></i> Lolos Kuota
                                    </span>
                                <?php else: ?>
                                    <span class="px-2.5 py-1 bg-slate-100 text-slate-600 border border-slate-200 rounded-lg text-[11px] font-medium inline-flex items-center gap-1">
                                        Cadangan
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php 
                        $r++;
                        endforeach; 
                        ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- 2. MODAL DAFTAR SISWA MENUNGGU VERIFIKASI  -->
<!-- ========================================== -->
<div id="modalMenunggu" class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden flex items-center justify-center z-50 p-4 transition-all">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl border border-slate-200 max-h-[90vh] flex flex-col overflow-hidden">
        <!-- Header Modal -->
        <div class="flex items-center justify-between p-5 border-b border-slate-100 bg-slate-50/70">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-slate-900 text-white flex items-center justify-center text-base shadow-sm">
                    <i class="fa-solid fa-clipboard-question"></i>
                </div>
                <div>
                    <h3 class="font-bold text-slate-900 text-base flex items-center gap-2">
                        Daftar Siswa Menunggu Verifikasi
                        <span class="px-2.5 py-0.5 bg-amber-50 text-amber-700 rounded-full text-xs font-semibold border border-amber-200">
                            <?= $count_menunggu ?> Berkas
                        </span>
                    </h3>
                    <p class="text-xs text-slate-500 mt-0.5">Berkas pendaftaran orang tua yang perlu diverifikasi fisik sebelum diperingkatkan</p>
                </div>
            </div>
            <button type="button" onclick="closeModal('modalMenunggu')" class="w-8 h-8 rounded-lg hover:bg-slate-200/70 text-slate-400 hover:text-slate-700 flex items-center justify-center transition-colors cursor-pointer">
                <i class="fa-solid fa-xmark text-base"></i>
            </button>
        </div>

        <!-- Toolbar Pencarian di dalam Modal -->
        <div class="px-5 py-3 border-b border-slate-100 bg-white flex items-center justify-between gap-3">
            <div class="relative flex-1 max-w-sm">
                <input type="text" id="searchMenunggu" onkeyup="filterModalTable('searchMenunggu', 'tableModalMenunggu')" 
                    placeholder="Cari nama, NISN, atau sekolah asal..." 
                    class="w-full pl-8 pr-3 py-1.5 border border-slate-200 rounded-lg text-xs bg-slate-50 focus:bg-white focus:outline-none focus:ring-1 focus:ring-slate-400">
                <i class="fa-solid fa-magnifying-glass absolute left-2.5 top-2.5 text-slate-400 text-xs"></i>
            </div>
            <a href="data_calon_penerima.php?status=menunggu" class="text-xs text-amber-700 hover:text-amber-800 font-semibold flex items-center gap-1.5 transition-colors">
                <i class="fa-solid fa-list-check"></i> Kelola di Data Calon
            </a>
        </div>

        <!-- Body Modal: Tabel Siswa Menunggu -->
        <div class="p-5 overflow-y-auto flex-1">
            <?php if (empty($siswa_menunggu_list)): ?>
                <div class="py-12 text-center">
                    <div class="w-14 h-14 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center text-2xl mx-auto mb-3 border border-emerald-100 shadow-sm">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                    <h4 class="font-bold text-slate-800 text-sm">Tidak Ada Berkas Tertunda</h4>
                    <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">Semua berkas pengajuan pendaftar telah berhasil diverifikasi oleh panitia tata usaha.</p>
                </div>
            <?php else: ?>
                <table class="w-full text-left text-xs min-w-[650px]" id="tableModalMenunggu">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold uppercase text-[11px] tracking-wider">
                            <th class="p-3 text-center w-12">No</th>
                            <th class="p-3">NISN & Nama Siswa</th>
                            <th class="p-3">Sekolah Asal / Kelas</th>
                            <th class="p-3">Nama Wali & WhatsApp</th>
                            <th class="p-3 text-center w-36">Aksi Verifikasi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                        <?php 
                        $no_m = 1;
                        foreach ($siswa_menunggu_list as $m): 
                        ?>
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <td class="p-3 text-center text-slate-400"><?= $no_m++ ?></td>
                            <td class="p-3">
                                <span class="font-bold text-slate-900 block"><?= htmlspecialchars($m['nama']) ?></span>
                                <span class="text-[10px] text-slate-400 font-mono">NISN: <?= htmlspecialchars($m['nisn']) ?></span>
                            </td>
                            <td class="p-3 text-slate-600">
                                <?= htmlspecialchars($m['sekolah_asal'] ?: 'SMP Tunas Bangsa') ?>
                                <span class="text-[10px] text-slate-400 block"><?= htmlspecialchars($m['kelas'] ?: 'Kelas VII') ?></span>
                            </td>
                            <td class="p-3">
                                <span class="font-medium text-slate-800 block"><?= htmlspecialchars($m['nama_ortu'] ?: '-') ?></span>
                                <?php if (!empty($m['no_hp'])): ?>
                                    <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $m['no_hp']) ?>" target="_blank" class="text-[10px] text-emerald-600 hover:text-emerald-700 font-semibold flex items-center gap-1 mt-0.5">
                                        <i class="fa-brands fa-whatsapp text-xs"></i> <?= htmlspecialchars($m['no_hp']) ?>
                                    </a>
                                <?php else: ?>
                                    <span class="text-[10px] text-slate-400">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-3 text-center whitespace-nowrap">
                                <div class="flex items-center justify-center gap-1.5">
                                    <a href="dashboard.php?action=verifikasi_siswa&id=<?= $m['id_siswa'] ?>" onclick="return confirm('Setujui dan verifikasi berkas <?= addslashes($m['nama']) ?> sekarang?')" 
                                        class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-semibold shadow-sm inline-flex items-center gap-1 transition-colors">
                                        <i class="fa-solid fa-check text-[10px]"></i> Setujui
                                    </a>
                                    <a href="data_calon_penerima.php?status=menunggu" 
                                        class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200 rounded-lg text-xs font-medium inline-flex items-center gap-1 transition-colors" title="Periksa berkas di halaman Data Calon">
                                        <i class="fa-solid fa-eye text-[10px]"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- 3. MODAL DETAIL SISWA SKOR TERTINGGI (AHP) -->
<!-- ========================================== -->
<div id="modalSkorTertinggi" class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden flex items-center justify-center z-50 p-4 transition-all">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl border border-slate-200 max-h-[90vh] flex flex-col overflow-hidden">
        <!-- Header Modal -->
        <div class="flex items-center justify-between p-5 border-b border-slate-100 bg-slate-50/70">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-slate-900 text-white flex items-center justify-center text-base shadow-sm">
                    <i class="fa-solid fa-ranking-star"></i>
                </div>
                <div>
                    <h3 class="font-bold text-slate-900 text-base">Detail Siswa Skor Tertinggi (Rank #1 AHP)</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Siswa dengan perolehan skor prioritas tertinggi berdasarkan metode AHP</p>
                </div>
            </div>
            <button type="button" onclick="closeModal('modalSkorTertinggi')" class="w-8 h-8 rounded-lg hover:bg-slate-200/70 text-slate-400 hover:text-slate-700 flex items-center justify-center transition-colors cursor-pointer">
                <i class="fa-solid fa-xmark text-base"></i>
            </button>
        </div>

        <!-- Body Modal -->
        <div class="p-6 overflow-y-auto flex-1 space-y-5">
            <?php if (empty($siswa_terverifikasi)): ?>
                <div class="py-10 text-center text-slate-400 text-xs">
                    <i class="fa-solid fa-user-slash text-3xl mb-2 text-slate-300 block"></i>
                    Belum ada data siswa terverifikasi sehingga belum ada perhitungan skor tertinggi.
                </div>
            <?php else: 
                $top_s = $siswa_terverifikasi[0];
            ?>
                <!-- Kartu Profil Siswa Rank 1 -->
                <div class="p-5 bg-gradient-to-br from-slate-900 via-slate-800 to-indigo-950 text-white rounded-2xl shadow-md border border-slate-700">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div>
                            <div class="flex items-center gap-2 mb-1.5">
                                <span class="px-2.5 py-0.5 bg-amber-400 text-slate-950 font-extrabold text-[11px] rounded shadow-sm">
                                    PERINGKAT #1
                                </span>
                                <span class="px-2.5 py-0.5 bg-emerald-500 text-white font-bold text-[11px] rounded shadow-sm">
                                    Lolos Kuota PIP
                                </span>
                            </div>
                            <h4 class="text-xl font-bold tracking-tight text-white"><?= htmlspecialchars($top_s['nama']) ?></h4>
                            <p class="text-xs text-slate-300 mt-1">
                                NISN: <span class="font-mono font-semibold text-white"><?= htmlspecialchars($top_s['nisn']) ?></span> &bull; 
                                <?= htmlspecialchars($top_s['sekolah_asal'] ?: 'SMP Tunas Bangsa') ?>
                            </p>
                            <p class="text-xs text-slate-400 mt-1">
                                Nama Orang Tua / Wali: <strong class="text-slate-200"><?= htmlspecialchars($top_s['nama_ortu'] ?: '-') ?></strong>
                                <?php if (!empty($top_s['no_hp'])): ?>
                                    &bull; Telp/WA: <span class="text-emerald-400 font-mono"><?= htmlspecialchars($top_s['no_hp']) ?></span>
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="bg-white/10 p-4 rounded-xl border border-white/10 text-left sm:text-right shrink-0">
                            <span class="text-[10px] text-slate-300 uppercase font-bold tracking-wider block">Total Skor Akhir</span>
                            <span class="text-3xl font-black font-mono text-emerald-300"><?= number_format($skor_tertinggi, 4) ?></span>
                            <span class="text-[10px] text-slate-400 block mt-0.5">Skala Maksimum: 5.00</span>
                        </div>
                    </div>
                </div>

                <!-- Rincian 5 Kriteria AHP Siswa -->
                <div class="space-y-2.5">
                    <div class="flex items-center justify-between">
                        <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Rincian Penilaian 5 Kriteria AHP</h4>
                        <span class="text-[11px] text-slate-400">Metode Penjumlahan Berbobot (AHP)</span>
                    </div>
                    <div class="border border-slate-200 rounded-xl overflow-hidden shadow-sm">
                        <table class="w-full text-xs text-left">
                            <thead class="bg-slate-50 text-slate-600 font-semibold border-b border-slate-200">
                                <tr>
                                    <th class="p-3">Kriteria</th>
                                    <th class="p-3">Kondisi Siswa</th>
                                    <th class="p-3 text-center">Nilai</th>
                                    <th class="p-3 text-center">Bobot AHP</th>
                                    <th class="p-3 text-right">Kontribusi Skor</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                                <?php 
                                foreach ($subkriteria_detail as $k_code => $info):
                                    $val = (int)($top_s[$info['key']] ?? 1);
                                    $kondisi = $info['label'][$val] ?? "Skor $val";
                                    $bobot = (float)$info['bobot'];
                                    $kontribusi = round($val * $bobot, 4);
                                ?>
                                <tr class="hover:bg-slate-50/70 transition-colors">
                                    <td class="p-3 font-semibold text-slate-900">
                                        <span class="font-mono font-bold text-slate-700 bg-slate-100 px-1.5 py-0.5 rounded mr-1"><?= $k_code ?></span>
                                        <?= htmlspecialchars($info['nama']) ?>
                                    </td>
                                    <td class="p-3 text-slate-600"><?= $kondisi ?></td>
                                    <td class="p-3 text-center font-bold text-slate-900 font-mono"><?= $val ?></td>
                                    <td class="p-3 text-center text-slate-500 font-mono"><?= number_format($bobot, 4) ?> <span class="text-[10px] text-slate-400">(<?= round($bobot * 100, 2) ?>%)</span></td>
                                    <td class="p-3 text-right font-mono font-bold text-slate-900"><?= number_format($kontribusi, 4) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="bg-slate-50 font-bold border-t border-slate-200 text-slate-900">
                                <tr>
                                    <td colspan="4" class="p-3 text-right text-xs uppercase tracking-wider text-slate-600">Total Skor Prioritas AHP:</td>
                                    <td class="p-3 text-right font-mono text-base text-indigo-700 font-extrabold"><?= number_format($skor_tertinggi, 4) ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Footer Modal -->
        <div class="flex items-center justify-end p-4 border-t border-slate-100 bg-slate-50/50">
            <a href="ranking.php" class="text-xs text-slate-700 hover:text-slate-900 font-semibold flex items-center gap-1.5 transition-colors">
                <span>Lihat Siswa Lain di Hasil Ranking</span>
                <i class="fa-solid fa-arrow-right text-[11px]"></i>
            </a>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- 4. MODAL UBAH KUOTA RESMI PENERIMA PIP     -->
<!-- ========================================== -->
<div id="modalKuota" class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden flex items-center justify-center z-50 p-4 transition-all">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md border border-slate-200 max-h-[90vh] flex flex-col overflow-hidden">
        <!-- Header Modal -->
        <div class="flex items-center justify-between p-5 border-b border-slate-100 bg-slate-50/70">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-slate-900 text-white flex items-center justify-center text-base shadow-sm">
                    <i class="fa-solid fa-graduation-cap"></i>
                </div>
                <div>
                    <h3 class="font-bold text-slate-900 text-base">Ubah Kuota Resmi PIP</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Penetapan kuota beasiswa Tahun Ajaran <?= htmlspecialchars($pengaturan['tahun_ajaran']) ?></p>
                </div>
            </div>
            <button type="button" onclick="closeModal('modalKuota')" class="w-8 h-8 rounded-lg hover:bg-slate-200/70 text-slate-400 hover:text-slate-700 flex items-center justify-center transition-colors cursor-pointer">
                <i class="fa-solid fa-xmark text-base"></i>
            </button>
        </div>

        <!-- Form Ubah Kuota -->
        <form action="dashboard.php" method="POST" class="p-6 space-y-4">
            <input type="hidden" name="action" value="update_kuota">

            <!-- Ringkasan Kondisi Saat Ini -->
            <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 space-y-2 text-xs">
                <div class="flex items-center justify-between text-slate-600">
                    <span>Tahun Ajaran Aktif:</span>
                    <strong class="text-slate-800 font-bold bg-white px-2 py-0.5 rounded border border-slate-200">
                        <?= htmlspecialchars($pengaturan['tahun_ajaran']) ?>
                    </strong>
                </div>
                <div class="flex items-center justify-between text-slate-600">
                    <span>Kuota Saat Ini:</span>
                    <strong class="text-slate-900 font-bold"><?= $kuota ?> Siswa</strong>
                </div>
                <div class="flex items-center justify-between text-slate-600">
                    <span>Siswa Terverifikasi:</span>
                    <strong class="text-slate-900 font-bold"><?= $total_terverifikasi ?> Siswa</strong>
                </div>
                <div class="flex items-center justify-between text-slate-600 pt-1 border-t border-slate-200">
                    <span>Estimasi Siswa Lolos:</span>
                    <strong class="text-emerald-700 font-bold"><?= min($kuota, $total_terverifikasi) ?> Siswa Lolos</strong>
                </div>
            </div>

            <!-- Input Kuota Baru -->
            <div>
                <label for="input_kuota_pip" class="block text-xs font-semibold text-slate-800 mb-1.5">
                    Jumlah Kuota Penerima Bantuan (Siswa):
                </label>
                <div class="relative">
                    <input type="number" name="kuota_pip" id="input_kuota_pip" min="1" max="1000" required value="<?= $kuota ?>" 
                        class="w-full pl-10 pr-4 py-2.5 border border-slate-300 rounded-xl text-base font-bold text-slate-900 bg-white focus:outline-none focus:ring-2 focus:ring-slate-900 focus:border-slate-900 shadow-sm transition-all">
                    <i class="fa-solid fa-graduation-cap absolute left-3.5 top-3.5 text-slate-400 text-sm"></i>
                </div>
                <p class="text-[11px] text-slate-500 mt-2 leading-relaxed">
                    Perubahan kuota ini akan langsung memperbarui status <strong>Lolos Kuota</strong> dan <strong>Cadangan</strong> di seluruh sistem secara otomatis
                </p>
            </div>

            <!-- Tombol Aksi -->
            <div class="flex gap-2 pt-3 border-t border-slate-100">
                <button type="submit" class="flex-1 py-2.5 bg-slate-900 hover:bg-slate-800 text-white font-semibold rounded-lg text-xs shadow transition-colors flex items-center justify-center gap-1.5 cursor-pointer">
                    <i class="fa-solid fa-floppy-disk text-xs"></i> Simpan Perubahan Kuota
                </button>
                <button type="button" onclick="closeModal('modalKuota')" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-lg text-xs transition-colors cursor-pointer">
                    Batal
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(id) {
    const modal = document.getElementById(id);
    if (modal) {
        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }
}

function closeModal(id) {
    const modal = document.getElementById(id);
    if (modal) {
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }
}

// Pencarian cepat di dalam tabel modal
function filterModalTable(inputId, tableId) {
    const input = document.getElementById(inputId);
    if (!input) return;
    const filter = input.value.toLowerCase();
    const table = document.getElementById(tableId);
    if (!table) return;
    const tr = table.getElementsByTagName('tr');
    for (let i = 1; i < tr.length; i++) {
        const text = tr[i].textContent || tr[i].innerText;
        if (text.toLowerCase().indexOf(filter) > -1) {
            tr[i].style.display = "";
        } else {
            tr[i].style.display = "none";
        }
    }
}

// Tutup modal ketika tombol Escape ditekan
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        ['modalTerverifikasi', 'modalMenunggu', 'modalSkorTertinggi', 'modalKuota'].forEach(closeModal);
    }
});

// Tutup modal ketika klik di luar area konten (backdrop)
window.addEventListener('click', function(e) {
    ['modalTerverifikasi', 'modalMenunggu', 'modalSkorTertinggi', 'modalKuota'].forEach(function(id) {
        const modal = document.getElementById(id);
        if (modal && e.target === modal) {
            closeModal(id);
        }
    });
});
</script>

<?php require_once "footer.php"; ?>