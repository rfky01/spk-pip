<?php
// ekspor_excel.php - Unduh Laporan Ranking AHP format Excel
require_once "koneksi.php";

session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit;
}

$res_pengaturan = mysqli_query($koneksi, "SELECT * FROM pengaturan WHERE id=1");
$pengaturan = mysqli_fetch_assoc($res_pengaturan);
$kuota = $pengaturan['kuota_pip'] ?? 27;
$nama_sekolah = $pengaturan['nama_sekolah'] ?? 'SMP Tunas Bangsa';
$tahun_aktif = $pengaturan['tahun_ajaran'] ?? '2025/2026';

// Ambil parameter tahun dan kategori dari URL
$filter_tahun = $_GET['tahun'] ?? $tahun_aktif;
$tahun_label = ($filter_tahun === 'all') ? 'Semua Tahun' : $filter_tahun;
$tahun_ajaran = $tahun_label;

$filter_kategori = $_GET['kategori'] ?? $_GET['tab'] ?? 'terverifikasi';

// Ambil bobot kriteria
$res_kriteria = mysqli_query($koneksi, "SELECT * FROM kriteria ORDER BY kode_kriteria ASC");
$kriteria = [];
while ($row = mysqli_fetch_assoc($res_kriteria)) {
    $kriteria[$row['kode_kriteria']] = (float)$row['bobot'];
}

// Konfigurasi Judul & Filename berdasarkan Kategori
$kategori_config = [
    'lolos' => [
        'judul' => 'LAPORAN PENETAPAN PENERIMA BANTUAN PIP (PRIORITAS LOLOS)',
        'sub' => 'Kategori: Siswa Prioritas Lolos Kuota Resmi (Maks. ' . $kuota . ' Siswa)',
        'file_prefix' => 'Laporan_PIP_Prioritas_Lolos'
    ],
    'cadangan' => [
        'judul' => 'DAFTAR PESERTA CADANGAN PENERIMA PROGRAM INDONESIA PINTAR (PIP)',
        'sub' => 'Kategori: Siswa Cadangan (Di Luar Kuota Utama)',
        'file_prefix' => 'Laporan_PIP_Cadangan'
    ],
    'tidak_lolos' => [
        'judul' => 'DAFTAR PESERTA TIDAK LOLOS KUOTA UTAMA (CADANGAN) PENERIMA PIP',
        'sub' => 'Kategori: Siswa Tidak Lolos Kuota Utama (Cadangan)',
        'file_prefix' => 'Laporan_PIP_Tidak_Lolos_Cadangan'
    ],
    'terverifikasi' => [
        'judul' => 'LAPORAN HASIL SELEKSI SISWA TERVERIFIKASI PENERIMA PIP',
        'sub' => 'Kategori: Seluruh Siswa Terverifikasi (Lolos & Cadangan)',
        'file_prefix' => 'Laporan_PIP_Terverifikasi'
    ],
    'menunggu' => [
        'judul' => 'DAFTAR USULAN PENGAJUAN PIP STATUS MENUNGGU VERIFIKASI',
        'sub' => 'Kategori: Berkas Usulan Menunggu Verifikasi',
        'file_prefix' => 'Laporan_PIP_Menunggu_Verifikasi'
    ],
    'ditolak' => [
        'judul' => 'DAFTAR PENGAJUAN BANTUAN PIP DITOLAK (GUGUR)',
        'sub' => 'Kategori: Berkas Pengajuan Ditolak',
        'file_prefix' => 'Laporan_PIP_Ditolak'
    ],
    'all' => [
        'judul' => 'REKAPITULASI PENDAFTARAN BANTUAN PROGRAM INDONESIA PINTAR (PIP)',
        'sub' => 'Kategori: Seluruh Calon Pendaftar',
        'file_prefix' => 'Laporan_PIP_Semua_Pendaftar'
    ]
];

$info_lap = $kategori_config[$filter_kategori] ?? $kategori_config['terverifikasi'];

// Query data siswa sesuai filter tahun dan status
$where_tahun = ($filter_tahun !== 'all') ? " AND tahun = '" . mysqli_real_escape_string($koneksi, $filter_tahun) . "'" : "";

if (in_array($filter_kategori, ['lolos', 'cadangan', 'tidak_lolos', 'terverifikasi'])) {
    $where_status = " AND status_verifikasi = 'Terverifikasi'";
} elseif ($filter_kategori === 'menunggu') {
    $where_status = " AND status_verifikasi = 'Menunggu Verifikasi'";
} elseif ($filter_kategori === 'ditolak') {
    $where_status = " AND status_verifikasi = 'Ditolak'";
} else {
    $where_status = "";
}

$res_siswa = mysqli_query($koneksi, "SELECT * FROM calon_penerima WHERE 1=1 $where_status $where_tahun ORDER BY id_siswa ASC");
$raw_siswa = [];
while ($s = mysqli_fetch_assoc($res_siswa)) {
    $skor = ($s['penghasilan'] * ($kriteria['C1'] ?? 0.4165)) +
            ($s['tanggungan']  * ($kriteria['C2'] ?? 0.2619)) +
            ($s['kondisi_rumah']* ($kriteria['C3'] ?? 0.1608)) +
            ($s['prestasi']    * ($kriteria['C4'] ?? 0.0985)) +
            ($s['jarak']       * ($kriteria['C5'] ?? 0.0623));
    $s['skor_hitung'] = round($skor, 4);
    $raw_siswa[] = $s;
}

usort($raw_siswa, function($a, $b) {
    return $b['skor_hitung'] <=> $a['skor_hitung'];
});

// Saring kategori lolos vs cadangan
$daftar_siswa = [];
$rank_counter = 1;
foreach ($raw_siswa as $s) {
    $s['rank_display'] = $rank_counter;
    $is_pass = ($rank_counter <= $kuota && ($s['status_verifikasi'] ?? '') === 'Terverifikasi');
    $s['is_pass'] = $is_pass;

    if ($filter_kategori === 'lolos') {
        if ($is_pass) {
            $daftar_siswa[] = $s;
        }
    } elseif ($filter_kategori === 'cadangan' || $filter_kategori === 'tidak_lolos') {
        if (!$is_pass && ($s['status_verifikasi'] ?? '') === 'Terverifikasi') {
            $daftar_siswa[] = $s;
        }
    } else {
        $daftar_siswa[] = $s;
    }
    $rank_counter++;
}

// Set Headers untuk file Excel
$filename = ($info_lap['file_prefix'] ?? 'Laporan_SPK_PIP') . "_" . date('Ymd_His') . ".xls";
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");
?>
<table border="1">
    <tr>
        <th colspan="12" style="background-color: #0f172a; color: #ffffff; font-size: 13pt; text-align: center; height: 35px;">
            <?= strtoupper(htmlspecialchars($info_lap['judul'])) ?> - <?= strtoupper(htmlspecialchars($nama_sekolah)) ?>
        </th>
    </tr>
    <tr>
        <th colspan="12" style="text-align: center; font-size: 10pt; background-color: #f8fafc; color: #334155; height: 25px;">
            <?= htmlspecialchars($info_lap['sub']) ?> | Tahun Ajaran: <?= htmlspecialchars($tahun_ajaran) ?> | Kuota: <?= $kuota ?> Siswa | Tanggal Ekspor: <?= date('d/m/Y H:i') ?>
        </th>
    </tr>
    <tr style="background-color: #e2e8f0; font-weight: bold; text-align: center;">
        <th style="width: 60px;">Peringkat</th>
        <th style="width: 120px;">NISN</th>
        <th style="width: 200px;">Nama Siswa</th>
        <th style="width: 110px;">Jenis Kelamin</th>
        <th style="width: 160px;">Sekolah Asal</th>
        <th style="width: 80px;">C1 (Ekonomi)</th>
        <th style="width: 90px;">C2 (Tanggungan)</th>
        <th style="width: 80px;">C3 (Rumah)</th>
        <th style="width: 80px;">C4 (Prestasi)</th>
        <th style="width: 80px;">C5 (Jarak)</th>
        <th style="width: 100px;">Total Skor AHP</th>
        <th style="width: 160px;">Status Keputusan</th>
    </tr>
    <?php 
    if (empty($daftar_siswa)):
    ?>
    <tr>
        <td colspan="12" style="text-align: center; padding: 15px; color: #64748b;">
            <em>Tidak ada data peserta didik pada kategori laporan ini.</em>
        </td>
    </tr>
    <?php
    else:
    foreach ($daftar_siswa as $siswa): 
        $st = $siswa['status_verifikasi'] ?? 'Menunggu Verifikasi';
        $is_lolos = ($siswa['is_pass'] ?? false);
        
        if ($st === 'Terverifikasi') {
            $status_text = $is_lolos ? 'PRIORITAS PENERIMA' : 'CADANGAN';
            $bg = $is_lolos ? '#dcfce7' : '#fef3c7';
            $color = $is_lolos ? '#166534' : '#92400e';
        } elseif ($st === 'Ditolak') {
            $status_text = 'DITOLAK / GUGUR';
            $bg = '#fee2e2';
            $color = '#991b1b';
        } else {
            $status_text = 'MENUNGGU VERIFIKASI';
            $bg = '#fef9c3';
            $color = '#854d0e';
        }
    ?>
    <tr style="background-color: <?= $bg ?>;">
        <td style="text-align: center; font-weight: bold;"><?= $siswa['rank_display'] ?></td>
        <td style="text-align: center; mso-number-format:'\@';"><?= htmlspecialchars($siswa['nisn']) ?></td>
        <td><?= htmlspecialchars($siswa['nama']) ?></td>
        <td style="text-align: center;"><?= htmlspecialchars($siswa['jenis_kelamin'] ?? '-') ?></td>
        <td style="text-align: center;"><?= htmlspecialchars($siswa['sekolah_asal'] ?: '-') ?></td>
        <td style="text-align: center;"><?= $siswa['penghasilan'] ?></td>
        <td style="text-align: center;"><?= $siswa['tanggungan'] ?></td>
        <td style="text-align: center;"><?= $siswa['kondisi_rumah'] ?></td>
        <td style="text-align: center;"><?= $siswa['prestasi'] ?></td>
        <td style="text-align: center;"><?= $siswa['jarak'] ?></td>
        <td style="text-align: center; font-weight: bold;"><?= number_format($siswa['skor_hitung'], 4) ?></td>
        <td style="text-align: center; font-weight: bold; color: <?= $color ?>;"><?= $status_text ?></td>
    </tr>
    <?php 
    endforeach; 
    endif;
    ?>
</table>
