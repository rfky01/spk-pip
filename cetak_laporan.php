<?php
// cetak_laporan.php - Cetak Dokumen Resmi Hasil Seleksi PIP (PDF/Print View)
require_once "koneksi.php";

session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit;
}

// Ambil data pengaturan
$res_pengaturan = mysqli_query($koneksi, "SELECT * FROM pengaturan WHERE id=1");
$pengaturan = mysqli_fetch_assoc($res_pengaturan);
$kuota = $pengaturan['kuota_pip'] ?? 27;
$nama_yayasan = !empty($pengaturan['nama_yayasan']) ? $pengaturan['nama_yayasan'] : 'YAYASAN AL QODIRI LAMPUNG';
$nama_sekolah = !empty($pengaturan['nama_sekolah']) ? $pengaturan['nama_sekolah'] : 'SMP TUNAS BANGSA';
$sub_instansi = !empty($pengaturan['sub_instansi']) ? $pengaturan['sub_instansi'] : 'BANDAR MATARAM LAMPUNG TENGAH';
$alamat_sekolah = !empty($pengaturan['alamat_sekolah']) ? $pengaturan['alamat_sekolah'] : 'Jln. Lapangan Merdeka Raman Agung Mataram Udik Kec. Bandar mataram Lampung Tengah';
$kepala_sekolah = $pengaturan['kepala_sekolah'] ?? 'Fitri Wiyatni, S.Pd.I';
$nip_kepala_sekolah = $pengaturan['nip_kepala_sekolah'] ?? '-';
$tahun_aktif = $pengaturan['tahun_ajaran'] ?? '2025/2026';

// Ambil parameter tahun dan kategori dari URL
$filter_tahun = $_GET['tahun'] ?? $tahun_aktif;
$tahun_label = ($filter_tahun === 'all') ? 'Semua Tahun Ajaran' : $filter_tahun;
$tahun_ajaran = $tahun_label;

$filter_kategori = $_GET['kategori'] ?? $_GET['tab'] ?? 'terverifikasi';

// 1. Siapkan Logo Kiri (Kabupaten Lampung Tengah) dalam format Base64
$logo_kiri_path = !empty($pengaturan['logo_kiri']) && file_exists($pengaturan['logo_kiri']) ? $pengaturan['logo_kiri'] : 'uploads/logo_lampung_tengah.png';
$logo_kiri_base64 = '';
if (file_exists($logo_kiri_path)) {
    $ext = strtolower(pathinfo($logo_kiri_path, PATHINFO_EXTENSION));
    $mime = ($ext === 'png') ? 'image/png' : (($ext === 'webp') ? 'image/webp' : 'image/jpeg');
    $data_kiri = @file_get_contents($logo_kiri_path);
    if ($data_kiri !== false) {
        $logo_kiri_base64 = 'data:' . $mime . ';base64,' . base64_encode($data_kiri);
    }
}
$logo_kiri_src = !empty($logo_kiri_base64) ? $logo_kiri_base64 : $logo_kiri_path;

// 2. Siapkan Logo Kanan dalam format Base64
$logo_kanan_path = !empty($pengaturan['logo']) && file_exists($pengaturan['logo']) ? $pengaturan['logo'] : 'uploads/logo_tut_wuri_handayani.png';
$logo_kanan_base64 = '';
if (file_exists($logo_kanan_path)) {
    $ext = strtolower(pathinfo($logo_kanan_path, PATHINFO_EXTENSION));
    $mime = ($ext === 'png') ? 'image/png' : (($ext === 'webp') ? 'image/webp' : 'image/jpeg');
    $data_kanan = @file_get_contents($logo_kanan_path);
    if ($data_kanan !== false) {
        $logo_kanan_base64 = 'data:' . $mime . ';base64,' . base64_encode($data_kanan);
    }
}
$logo_kanan_src = !empty($logo_kanan_base64) ? $logo_kanan_base64 : $logo_kanan_path;

// Ambil bobot kriteria
$res_kriteria = mysqli_query($koneksi, "SELECT * FROM kriteria ORDER BY kode_kriteria ASC");
$kriteria = [];
while ($row = mysqli_fetch_assoc($res_kriteria)) {
    $kriteria[$row['kode_kriteria']] = (float)$row['bobot'];
}

// Konfigurasi Judul Laporan berdasarkan Kategori
$kategori_titles = [
    'lolos' => [
        'judul' => 'LAPORAN PENETAPAN PENERIMA BANTUAN PIP (PRIORITAS LOLOS)',
        'sub' => 'Kategori: Peserta Prioritas Lolos Kuota Utama (Maks. ' . $kuota . ' Siswa)',
        'ket_status' => 'Daftar Peserta Didik Yang Memenuhi Kuota Utama Bantuan PIP (' . $kuota . ' Siswa)'
    ],
    'cadangan' => [
        'judul' => 'DAFTAR PESERTA CADANGAN PENERIMA PROGRAM INDONESIA PINTAR (PIP)',
        'sub' => 'Kategori: Peserta Didik Cadangan (Di Luar Kuota Utama)',
        'ket_status' => 'Daftar Cadangan Pengganti Apabila Ada Peserta Mengundurkan Diri atau Terjadi Penambahan Kuota'
    ],
    'tidak_lolos' => [
        'judul' => 'DAFTAR PESERTA TIDAK LOLOS KUOTA UTAMA (CADANGAN) PENERIMA PIP',
        'sub' => 'Kategori: Peserta Tidak Lolos Kuota Utama (Cadangan)',
        'ket_status' => 'Daftar Peserta Didik Yang Belum Memenuhi Kuota Utama Bantuan PIP (Status Cadangan)'
    ],
    'terverifikasi' => [
        'judul' => 'LAPORAN HASIL SELEKSI SISWA TERVERIFIKASI PENERIMA PIP',
        'sub' => 'Kategori: Seluruh Peserta Terverifikasi (Lolos & Cadangan)',
        'ket_status' => 'Rekapitulasi Lengkap Peserta Didik Berstatus Terverifikasi Sesuai Pemeringkatan Skor AHP'
    ],
    'menunggu' => [
        'judul' => 'DAFTAR USULAN PENGAJUAN PIP STATUS MENUNGGU VERIFIKASI',
        'sub' => 'Kategori: Berkas Usulan Menunggu Verifikasi',
        'ket_status' => 'Daftar Pengajuan Peserta Didik Yang Masih Dalam Proses Verifikasi Berkas Persyaratan'
    ],
    'ditolak' => [
        'judul' => 'DAFTAR PENGAJUAN BANTUAN PIP DITOLAK (GUGUR)',
        'sub' => 'Kategori: Berkas Pengajuan Ditolak',
        'ket_status' => 'Daftar Pengajuan Peserta Didik Yang Ditolak / Tidak Memenuhi Kriteria Bantuan PIP'
    ],
    'all' => [
        'judul' => 'REKAPITULASI PENDAFTARAN BANTUAN PROGRAM INDONESIA PINTAR (PIP)',
        'sub' => 'Kategori: Seluruh Calon Pendaftar',
        'ket_status' => 'Rekapitulasi Seluruh Data Pengajuan Calon Peserta Didik Tanpa Pengecualian'
    ]
];

$info_lap = $kategori_titles[$filter_kategori] ?? $kategori_titles['terverifikasi'];

// Query data siswa sesuai filter
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
    // Hitung skor AHP jika belum terhitung
    $skor = ($s['penghasilan'] * ($kriteria['C1'] ?? 0.4165)) +
            ($s['tanggungan']  * ($kriteria['C2'] ?? 0.2619)) +
            ($s['kondisi_rumah']* ($kriteria['C3'] ?? 0.1608)) +
            ($s['prestasi']    * ($kriteria['C4'] ?? 0.0985)) +
            ($s['jarak']       * ($kriteria['C5'] ?? 0.0623));
    $s['skor_hitung'] = round($skor, 4);
    $raw_siswa[] = $s;
}

// Urutkan berdasarkan skor tertinggi
usort($raw_siswa, function($a, $b) {
    return $b['skor_hitung'] <=> $a['skor_hitung'];
});

// Tetapkan peringkat & saring kategori lolos vs cadangan
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

// Nama berkas saat diunduh / disimpan sebagai PDF
$safe_tahun = preg_replace('/[^a-zA-Z0-9_-]/', '_', $tahun_ajaran);
$safe_kategori = preg_replace('/[^a-zA-Z0-9_-]/', '_', $filter_kategori);
$nama_file_download = "Laporan_PIP_{$safe_kategori}_{$safe_tahun}";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($nama_file_download) ?></title>
    <!-- FontAwesome & Library html2pdf.js -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style id="dynamic-print-style">
        @page {
            size: A4 portrait;
            margin: 10mm 10mm 10mm 10mm;
        }
    </style>
    <style>
        *, *::before, *::after {
            box-sizing: border-box;
        }

        body, .dokumen-kertas, .dokumen-kertas * {
            font-family: 'Times New Roman', Times, serif !important;
        }

        body {
            color: #000;
            background: #0B192C;
            margin: 0;
            padding: 24px 0;
            font-size: 11pt;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        /* TOOLBAR AKSI (HANYA MUNCUL DI LAYAR MONITOR, TIDAK DICETAK KE KERTAS/PDF) */
        .action-bar {
            background: #112240;
            border: 1px solid #1E3A5F;
            border-radius: 12px;
            padding: 12px 18px;
            margin: 0 auto 20px auto;
            max-width: 194mm;
            box-shadow: 0 4px 14px rgba(0,0,0,0.3);
            font-family: 'Roboto', system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            transition: max-width 0.2s ease;
            box-sizing: border-box;
        }
        .action-buttons {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
        }
        .btn-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 14px;
            background: #162B4D;
            color: #ffffff;
            border: 1px solid #2E5A8F;
            border-radius: 8px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
            transition: all 0.2s ease;
            white-space: nowrap;
        }
        .btn-btn:hover {
            background: #1E3A5F;
            border-color: #3B82F6;
            color: #ffffff;
            transform: scale(1.02);
        }
        .btn-btn:active {
            transform: scale(0.98);
        }
        .btn-btn i {
            font-size: 12px;
        }
        .btn-download i,
        .btn-print i,
        .btn-orientasi i {
            color: #60a5fa;
        }
        .btn-back {
            background: #112240;
            color: #cbd5e1;
            border: 1px solid #1E3A5F;
        }
        .btn-back:hover {
            background: #162B4D;
            border-color: #2E5A8F;
            color: #ffffff;
        }
        .btn-back i {
            color: #94a3b8;
        }
        .btn-back:hover i {
            color: #cbd5e1;
        }

        /* FORMAT DOKUMEN CETAK RESMI (PAS UKURAN A4 TANPA TERPOTONG) */
        .dokumen-kertas {
            background: #fff;
            width: 100%;
            max-width: 194mm;
            margin: 0 auto;
            padding: 10mm 12mm;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            box-sizing: border-box;
            transition: max-width 0.2s ease;
        }

        .dokumen-kertas.mode-landscape {
            max-width: 277mm;
        }
        .action-bar.mode-landscape {
            max-width: 277mm;
        }

        .header-kop {
            text-align: center;
            border-bottom: 3px double #000;
            padding-bottom: 8px;
            margin-bottom: 16px;
            position: relative;
            min-height: 88px;
            padding-left: 85px;
            padding-right: 85px;
            box-sizing: border-box;
        }
        .header-kop img.kop-logo-kiri {
            position: absolute;
            left: 0;
            top: 0;
            width: 70px;
            height: 82px;
            object-fit: contain;
        }
        .header-kop img.kop-logo-kanan {
            position: absolute;
            right: 0;
            top: 0;
            width: 75px;
            height: 82px;
            object-fit: contain;
        }
        .header-kop .kop-teks {
            text-align: center;
        }
        .header-kop h2 {
            margin: 0;
            font-size: 12pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            line-height: 1.25;
            color: #000;
        }
        .header-kop h1 {
            margin: 2px 0;
            font-size: 15pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            line-height: 1.25;
            color: #000;
        }
        .header-kop h3 {
            margin: 2px 0 3px 0;
            font-size: 11.5pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            line-height: 1.25;
            color: #000;
        }
        .header-kop p {
            margin: 3px 0 0 0;
            font-size: 9pt;
            font-style: italic;
            line-height: 1.35;
            color: #000;
        }

        .judul-laporan {
            text-align: center;
            margin-bottom: 14px;
        }
        .judul-laporan h4 {
            margin: 0;
            font-size: 12pt;
            font-weight: bold;
            text-decoration: underline;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .judul-laporan span {
            font-size: 9.5pt;
            color: #222;
        }

        .info-meta {
            margin-bottom: 12px;
            font-size: 9.5pt;
            line-height: 1.45;
        }

        /* TABEL DATA HASIL SELEKSI (TIDAK AKAN MELEBIHI KERTAS) */
        table.data-table {
            width: 100% !important;
            table-layout: fixed !important;
            border-collapse: collapse !important;
            margin-bottom: 20px;
            box-sizing: border-box;
        }
        table.data-table th, table.data-table td {
            border: 1px solid #000;
            padding: 5px 3px;
            text-align: left;
            vertical-align: middle;
            font-size: 8.5pt;
            box-sizing: border-box;
            word-wrap: break-word;
            overflow-wrap: break-word;
        }
        table.data-table th {
            background-color: #f2f2f2 !important;
            text-align: center;
            font-weight: bold;
            font-size: 8pt;
            line-height: 1.2;
            padding: 6px 2px;
        }
        .text-center { text-align: center !important; }
        .badge-lolos {
            font-weight: bold;
            color: #0d6832;
            display: block;
            line-height: 1.2;
            font-size: 8pt;
        }
        .badge-cadangan {
            color: #475569;
            display: block;
            line-height: 1.2;
            font-size: 8pt;
        }

        .ttd-box {
            float: right;
            width: 240px;
            text-align: center;
            margin-top: 20px;
            page-break-inside: avoid;
            font-size: 9.5pt;
            line-height: 1.35;
        }
        .ttd-space {
            height: 60px;
        }

        /* PRINT STYLES */
        @media print {
            .no-print { display: none !important; }
            *, *::before, *::after, html, body, .dokumen-kertas, .dokumen-kertas * {
                font-family: 'Times New Roman', Times, serif !important;
            }
            body { 
                margin: 0 !important; 
                padding: 0 !important; 
                background: #fff !important;
            }
            .dokumen-kertas {
                width: 100% !important;
                max-width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                box-shadow: none !important;
                border: none !important;
            }
        }
    </style>
</head>
<body>

    <!-- TOOLBAR AKSI (TIDAK AKAN DICETAK KE KERTAS / PDF) -->
    <div class="no-print action-bar" id="toolbar-bar">
        <div class="action-buttons">
            <button type="button" id="btn-download-pdf" onclick="unduhPDFLangsung()" class="btn-btn btn-download" title="Unduh dan simpan dokumen sebagai file .PDF ke komputer Anda">
                <i class="fa-solid fa-file-pdf"></i> Unduh PDF
            </button>
            <button type="button" onclick="window.print()" class="btn-btn btn-print" title="Buka dialog cetak printer / Simpan via dialog cetak browser (Ctrl + P)">
                <i class="fa-solid fa-print"></i> Cetak
            </button>
            <button type="button" onclick="toggleOrientasi()" class="btn-btn btn-orientasi" id="btn-toggle-orientasi" title="Ubah orientasi kertas antara Portrait (Tegak) dan Landscape (Melebar)">
                <i class="fa-solid fa-arrows-rotate"></i> <span id="text-orientasi">Landscape</span>
            </button>
            <a href="laporan.php?tahun=<?= urlencode($filter_tahun) ?>" class="btn-btn btn-back" title="Kembali ke halaman rekap laporan">
                <i class="fa-solid fa-arrow-left"></i> Kembali ke Menu Laporan
            </a>
        </div>
    </div>

    <!-- AREA DOKUMEN CETAK RESMI -->
    <div id="dokumen-cetak" class="dokumen-kertas">
        <div class="header-kop">
            <?php if (!empty($logo_kiri_src)): ?>
                <img src="<?= htmlspecialchars($logo_kiri_src) ?>" alt="Logo Kabupaten Lampung Tengah" class="kop-logo-kiri">
            <?php endif; ?>

            <div class="kop-teks">
                <h2><?= htmlspecialchars($nama_yayasan) ?></h2>
                <h1><?= htmlspecialchars($nama_sekolah) ?></h1>
                <h3><?= htmlspecialchars($sub_instansi) ?></h3>
                <p>Alamat : <?= htmlspecialchars($alamat_sekolah) ?></p>
            </div>

            <?php if (!empty($logo_kanan_src)): ?>
                <img src="<?= htmlspecialchars($logo_kanan_src) ?>" alt="Logo SMP Tunas Bangsa" class="kop-logo-kanan">
            <?php endif; ?>
        </div>

        <div class="judul-laporan">
            <h4><?= htmlspecialchars($info_lap['judul']) ?></h4>
            <span><?= htmlspecialchars($info_lap['sub']) ?> &bull; Metode: AHP &bull; Tahun Ajaran: <?= htmlspecialchars($tahun_ajaran) ?></span>
        </div>

        <div class="info-meta">
            <strong>Ketentuan Kuota:</strong> <?= $kuota ?> Peserta Didik<br>
            <strong>Status Dokumen:</strong> <?= htmlspecialchars($info_lap['ket_status']) ?><br>
            <strong>Kriteria Penilaian:</strong> C1: Penghasilan, C2: Tanggungan, C3: Kondisi Rumah, C4: Prestasi, C5: Jarak
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 6%;">Peringkat</th>
                    <th style="width: 13%;">NISN</th>
                    <th style="width: 21%;">Nama Peserta Didik</th>
                    <th style="width: 14%;">Sekolah Asal</th>
                    <th style="width: 4.5%;">C1</th>
                    <th style="width: 4.5%;">C2</th>
                    <th style="width: 4.5%;">C3</th>
                    <th style="width: 4.5%;">C4</th>
                    <th style="width: 4.5%;">C5</th>
                    <th style="width: 10.5%;">Skor AHP</th>
                    <th style="width: 13%;">Status Keputusan</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                if (empty($daftar_siswa)):
                ?>
                <tr>
                    <td colspan="11" class="text-center" style="padding: 25px; color: #777;">
                        <em>Tidak ada data peserta didik pada kategori laporan ini.</em>
                    </td>
                </tr>
                <?php
                else:
                foreach ($daftar_siswa as $siswa): 
                    $st = $siswa['status_verifikasi'] ?? 'Menunggu Verifikasi';
                    $is_lolos = ($siswa['is_pass'] ?? false);
                ?>
                <tr>
                    <td class="text-center"><strong><?= $siswa['rank_display'] ?></strong></td>
                    <td class="text-center"><?= htmlspecialchars($siswa['nisn']) ?></td>
                    <td><?= htmlspecialchars($siswa['nama']) ?></td>
                    <td class="text-center"><?= htmlspecialchars($siswa['sekolah_asal'] ?: '-') ?></td>
                    <td class="text-center"><?= $siswa['penghasilan'] ?></td>
                    <td class="text-center"><?= $siswa['tanggungan'] ?></td>
                    <td class="text-center"><?= $siswa['kondisi_rumah'] ?></td>
                    <td class="text-center"><?= $siswa['prestasi'] ?></td>
                    <td class="text-center"><?= $siswa['jarak'] ?></td>
                    <td class="text-center"><strong><?= number_format($siswa['skor_hitung'], 4) ?></strong></td>
                    <td class="text-center">
                        <?php if ($st === 'Terverifikasi'): ?>
                            <span class="<?= $is_lolos ? 'badge-lolos' : 'badge-cadangan' ?>">
                                <?= $is_lolos ? 'PRIORITAS<br>PENERIMA' : 'CADANGAN' ?>
                            </span>
                        <?php elseif ($st === 'Ditolak'): ?>
                            <span style="color: #b91c1c; font-weight: bold; font-size: 7.5pt; line-height: 1.1; display: block;">DITOLAK /<br>GUGUR</span>
                        <?php else: ?>
                            <span style="color: #b45309; font-weight: bold; font-size: 7.5pt; line-height: 1.1; display: block;">MENUNGGU<br>VERIFIKASI</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php 
                endforeach; 
                endif;
                ?>
            </tbody>
        </table>

        <div class="ttd-box">
            <p>Bandar Mataram, <?= date('d F Y') ?><br>Kepala <?= htmlspecialchars($nama_sekolah) ?></p>
            <div class="ttd-space"></div>
            <p><strong><u><?= htmlspecialchars($kepala_sekolah) ?></u></strong><br>NIP. <?= !empty($nip_kepala_sekolah) ? htmlspecialchars($nip_kepala_sekolah) : '-' ?></p>
        </div>
        <div style="clear: both;"></div>
    </div>

    <!-- SCRIPT UNDUH PDF LANGSUNG DENGAN HTML2PDF.JS -->
    <script>
        let currentOrientation = 'portrait';

        function toggleOrientasi() {
            const styleEl = document.getElementById('dynamic-print-style');
            const docEl = document.getElementById('dokumen-cetak');
            const toolbarEl = document.getElementById('toolbar-bar');
            const textEl = document.getElementById('text-orientasi');
            
            if (currentOrientation === 'portrait') {
                currentOrientation = 'landscape';
                styleEl.innerHTML = '@page { size: A4 landscape; margin: 10mm 10mm 10mm 10mm; }';
                docEl.classList.add('mode-landscape');
                if (toolbarEl) toolbarEl.classList.add('mode-landscape');
                textEl.innerText = 'Portrait';
            } else {
                currentOrientation = 'portrait';
                styleEl.innerHTML = '@page { size: A4 portrait; margin: 10mm 10mm 10mm 10mm; }';
                docEl.classList.remove('mode-landscape');
                if (toolbarEl) toolbarEl.classList.remove('mode-landscape');
                textEl.innerText = 'Landscape';
            }
        }

        function unduhPDFLangsung() {
            const btn = document.getElementById('btn-download-pdf');
            const originalText = btn.innerHTML;

            // Jika library html2pdf gagal dimuat (misal perangkat sedang offline)
            if (typeof html2pdf === 'undefined') {
                alert('Modul unduh PDF otomatis sedang memuat atau perangkat offline.\nAnda akan dialihkan ke dialog cetak browser: silakan ubah "Tujuan (Destination)" menjadi "Simpan sebagai PDF".');
                window.print();
                return;
            }

            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Menyiapkan File PDF...';
            btn.disabled = true;

            const element = document.getElementById('dokumen-cetak');
            const suffixOrientasi = currentOrientation === 'landscape' ? '_landscape' : '';
            const filename = '<?= $nama_file_download ?>' + suffixOrientasi + '.pdf';

            const opt = {
                margin:       [8, 6, 8, 6], // Margin atas, kiri, bawah, kanan (mm)
                filename:     filename,
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2, useCORS: true, logging: false, scrollY: 0 },
                jsPDF:        { unit: 'mm', format: 'a4', orientation: currentOrientation }
            };

            html2pdf().set(opt).from(element).save().then(function() {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }).catch(function(err) {
                console.error('Gagal generate PDF:', err);
                btn.innerHTML = originalText;
                btn.disabled = false;
                alert('Terjadi kendala saat menyimpan PDF otomatis.\nSilakan gunakan tombol "Cetak" dan pilih opsi "Simpan sebagai PDF" pada Tujuan (Destination).');
            });
        }
    </script>

</body>
</html>
