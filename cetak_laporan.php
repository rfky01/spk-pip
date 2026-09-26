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

// 2. Siapkan Logo Kanan (SMP Tunas Bangsa) dalam format Base64
$logo_kanan_path = !empty($pengaturan['logo']) && file_exists($pengaturan['logo']) ? $pengaturan['logo'] : 'uploads/logo_default.png';
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
    <style>
        body {
            font-family: 'Times New Roman', Times, serif;
            color: #000;
            background: #fff;
            margin: 20px 40px;
            font-size: 12pt;
        }

        /* TOOLBAR AKSI (HANYA MUNCUL DI LAYAR MONITOR, TIDAK DICETAK KE KERTAS/PDF) */
        .action-bar {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px 20px;
            margin-bottom: 25px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.04);
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        .action-buttons {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
        }
        .btn-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        .btn-download {
            background: #059669;
            color: #ffffff;
            box-shadow: 0 1px 2px rgba(5,150,105,0.2);
        }
        .btn-download:hover {
            background: #047857;
        }
        .btn-print {
            background: #0f172a;
            color: #ffffff;
        }
        .btn-print:hover {
            background: #1e293b;
        }
        .btn-back {
            background: #ffffff;
            color: #334155;
            border: 1px solid #cbd5e1;
        }
        .btn-back:hover {
            background: #f1f5f9;
        }
        .tips-box {
            font-size: 12px;
            line-height: 1.6;
            color: #475569;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 8px;
            padding: 10px 14px;
        }

        /* FORMAT DOKUMEN CETAK RESMI */
        .dokumen-kertas {
            background: #fff;
            padding: 5px;
        }
        .header-kop {
            text-align: center;
            border-bottom: 3px double #000;
            padding-bottom: 8px;
            margin-bottom: 20px;
            position: relative;
            min-height: 92px;
            padding-left: 95px;
            padding-right: 95px;
        }
        .header-kop img.kop-logo-kiri {
            position: absolute;
            left: 5px;
            top: 0;
            width: 75px;
            height: 86px;
            object-fit: contain;
        }
        .header-kop img.kop-logo-kanan {
            position: absolute;
            right: 5px;
            top: 0;
            width: 80px;
            height: 86px;
            object-fit: contain;
        }
        .header-kop .kop-teks {
            text-align: center;
        }
        .header-kop h2 {
            margin: 0;
            font-size: 13pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            line-height: 1.25;
            color: #000;
        }
        .header-kop h1 {
            margin: 2px 0;
            font-size: 16pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            line-height: 1.25;
            color: #000;
        }
        .header-kop h3 {
            margin: 2px 0 3px 0;
            font-size: 12.5pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            line-height: 1.25;
            color: #000;
        }
        .header-kop p {
            margin: 3px 0 0 0;
            font-size: 9.5pt;
            font-style: italic;
            line-height: 1.35;
            color: #000;
        }
        .judul-laporan {
            text-align: center;
            margin-bottom: 18px;
        }
        .judul-laporan h4 {
            margin: 0;
            font-size: 13pt;
            text-decoration: underline;
            text-transform: uppercase;
        }
        .judul-laporan span {
            font-size: 10.5pt;
        }
        .info-meta {
            margin-bottom: 15px;
            font-size: 11pt;
            line-height: 1.5;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
            font-size: 10.5pt;
        }
        table.data-table th, table.data-table td {
            border: 1px solid #000;
            padding: 6px 8px;
            text-align: left;
        }
        table.data-table th {
            background-color: #f2f2f2;
            text-align: center;
            font-weight: bold;
        }
        .text-center { text-align: center !important; }
        .badge-lolos {
            font-weight: bold;
            color: #0d6832;
        }
        .badge-cadangan {
            color: #666;
        }
        .ttd-box {
            float: right;
            width: 260px;
            text-align: center;
            margin-top: 30px;
            page-break-inside: avoid;
        }
        .ttd-space {
            height: 75px;
        }

        /* PRINT STYLES */
        @media print {
            .no-print { display: none !important; }
            body { margin: 0; padding: 0; }
        }
    </style>
</head>
<body>

    <!-- TOOLBAR AKSI (TIDAK AKAN DICETAK KE KERTAS / PDF) -->
    <div class="no-print action-bar">
        <div class="action-buttons">
            <button type="button" id="btn-download-pdf" onclick="unduhPDFLangsung()" class="btn-btn btn-download" title="Unduh dan simpan dokumen sebagai file .PDF ke komputer Anda">
                <i class="fa-solid fa-file-pdf"></i> Unduh File PDF Langsung
            </button>
            <button type="button" onclick="window.print()" class="btn-btn btn-print" title="Buka dialog cetak printer / Simpan via dialog cetak browser">
                <i class="fa-solid fa-print"></i> Cetak ke Printer
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
                    <th style="width: 35px;">Peringkat</th>
                    <th style="width: 85px;">NISN</th>
                    <th>Nama Peserta Didik</th>
                    <th style="width: 140px;">Sekolah Asal (SD/MI)</th>
                    <th style="width: 30px;">C1</th>
                    <th style="width: 30px;">C2</th>
                    <th style="width: 30px;">C3</th>
                    <th style="width: 30px;">C4</th>
                    <th style="width: 30px;">C5</th>
                    <th style="width: 75px;">Skor AHP</th>
                    <th style="width: 140px;">Status Keputusan</th>
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
                                <?= $is_lolos ? 'PRIORITAS PENERIMA' : 'CADANGAN' ?>
                            </span>
                        <?php elseif ($st === 'Ditolak'): ?>
                            <span style="color: #b91c1c; font-weight: bold;">DITOLAK / GUGUR</span>
                        <?php else: ?>
                            <span style="color: #b45309; font-weight: bold;">MENUNGGU VERIF</span>
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
        function unduhPDFLangsung() {
            const btn = document.getElementById('btn-download-pdf');
            const originalText = btn.innerHTML;

            // Jika library html2pdf gagal dimuat (misal perangkat sedang offline)
            if (typeof html2pdf === 'undefined') {
                alert('Modul unduh PDF otomatis sedang memuat atau perangkat offline.\nAnda akan dialihkan ke dialog cetak browser: silakan ubah "Tujuan (Destination)" menjadi "Simpan sebagai PDF".');
                window.print();
                return;
            }

            btn.innerHTML = '⏳ Menyiapkan File PDF...';
            btn.disabled = true;

            const element = document.getElementById('dokumen-cetak');
            const filename = '<?= $nama_file_download ?>' + '.pdf';

            const opt = {
                margin:       [10, 10, 10, 10], // Margin atas, kiri, bawah, kanan (mm)
                filename:     filename,
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2, useCORS: true, logging: false },
                jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };

            html2pdf().set(opt).from(element).save().then(function() {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }).catch(function(err) {
                console.error('Gagal generate PDF:', err);
                btn.innerHTML = originalText;
                btn.disabled = false;
                alert('Terjadi kendala saat menyimpan PDF otomatis.\nSilakan gunakan tombol "Cetak ke Printer" dan pilih opsi "Simpan sebagai PDF" pada Tujuan (Destination).');
            });
        }
    </script>

</body>
</html>
