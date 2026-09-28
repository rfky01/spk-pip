<?php
// unduh_bukti_pdf.php - Generator & Pengunduh Berkas PDF Bukti Pendaftaran PIP Resmi 2 Lembar
require_once "koneksi.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek autentikasi pendaftar / admin
$id_siswa = 0;
if (isset($_SESSION['siswa']) && !empty($_SESSION['siswa']['id_siswa'])) {
    $id_siswa = (int)$_SESSION['siswa']['id_siswa'];
} elseif (isset($_SESSION['admin']) && !empty($_GET['id_siswa'])) {
    $id_siswa = (int)$_GET['id_siswa'];
} else {
    header("Location: login_siswa.php");
    exit;
}

// Ambil data calon penerima
$stmt = mysqli_prepare($koneksi, "SELECT * FROM `calon_penerima` WHERE `id_siswa` = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $id_siswa);
mysqli_stmt_execute($stmt);
$siswa = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$siswa) {
    die("Data pendaftar tidak ditemukan.");
}

// Ambil pengaturan sekolah
$res_pengaturan = mysqli_query($koneksi, "SELECT * FROM `pengaturan` WHERE id=1");
$pengaturan = mysqli_fetch_assoc($res_pengaturan) ?: [];
$nama_sekolah = $pengaturan['nama_sekolah'] ?? 'SMP Tunas Bangsa';

$label_penghasilan = [
    5 => '< Rp 500.000 (Sangat Rendah)',
    4 => 'Rp 600.000 - Rp 1.000.000 (Rendah)',
    3 => 'Rp 1.000.000 - Rp 2.000.000 (Sedang)',
    2 => 'Rp 2.000.000 - Rp 3.000.000 (Cukup)',
    1 => '> Rp 4.000.000 (Mampu)'
];
$label_tanggungan = [
    5 => '> 5 Orang',
    4 => '4 Orang',
    3 => '3 Orang',
    2 => '2 Orang',
    1 => '1 Orang'
];
$label_rumah = [
    5 => 'Tidak Layak Huni',
    4 => 'Dinding Kayu',
    3 => 'Dinding Batu Atap Seng',
    2 => 'Dinding Batu Atap Genteng',
    1 => 'Tembok Keramik (Layak)'
];
$label_prestasi = [
    5 => 'Juara 1 - 3 Tingkat Kabupaten',
    4 => 'Juara Harapan',
    3 => 'Juara Kelas 1 - 3',
    2 => 'Peringkat 10 Besar',
    1 => 'Peringkat 20 Besar'
];
$label_jarak = [
    5 => '> 5 km',
    4 => '3 – 5 km',
    3 => '2 km',
    2 => '1 km',
    1 => '< 1 km'
];

$foto_siswa_src = '';
if (!empty($siswa['foto'])) {
    $foto_file = __DIR__ . '/' . ltrim($siswa['foto'], '/\\');
    if (file_exists($foto_file)) {
        $img_data = @file_get_contents($foto_file);
        if ($img_data !== false) {
            $ext = strtolower(pathinfo($foto_file, PATHINFO_EXTENSION));
            $mime = ($ext === 'png') ? 'image/png' : (($ext === 'webp') ? 'image/webp' : 'image/jpeg');
            $foto_siswa_src = 'data:' . $mime . ';base64,' . base64_encode($img_data);
        }
    }
}

// Generate HTML Content 2 Lembar Lengkap
ob_start();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Bukti Pendaftaran PIP - <?= htmlspecialchars($siswa['nama']) ?></title>
    <style>
        @page {
            size: A4 portrait;
            margin: 8mm 12mm 8mm 12mm;
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'Roboto', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background: #ffffff;
            color: #0f172a;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        .page-sheet {
            width: 100%;
            height: 260mm;
            min-height: 260mm;
            max-height: 260mm;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            page-break-inside: avoid;
            break-inside: avoid;
            box-sizing: border-box;
            background: #ffffff;
        }
        .sheet-1 {
            border: 2px solid #0f172a;
            border-radius: 12px;
            padding: 16px 20px;
            page-break-after: always;
            break-after: page;
        }
        .sheet-2 {
            padding: 10px 14px;
            page-break-before: always;
            break-before: page;
            color: #000000;
        }
        .f-label {
            font-size: 11px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 3px;
            display: block;
        }
        .f-box {
            background: #f8fafc;
            border: 1.5px solid #475569;
            color: #0f172a;
            padding: 6px 11px;
            font-size: 11.5px;
            border-radius: 7px;
            min-height: 30px;
            line-height: 1.35;
        }
        .sec-title {
            font-size: 12.5px;
            font-weight: 800;
            color: #0f172a;
            border-bottom: 2px solid #1e293b;
            padding-bottom: 3px;
            margin: 8px 0 9px 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 11px;
        }
    </style>
</head>
<body>
    <!-- LEMBAR 1: FORMULIR PENDAFTARAN -->
    <div class="page-sheet sheet-1">
        <!-- Header -->
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #cbd5e1; padding-bottom: 8px; margin-bottom: 8px;">
            <div>
                <h3 style="margin: 0; font-size: 16px; font-weight: 900; color: #0f172a;">Rincian &amp; Formulir Pembaruan Data</h3>
                <p style="margin: 2px 0 0 0; font-size: 11px; font-weight: 600; color: #475569;"><?= htmlspecialchars($nama_sekolah) ?> &bull; Tahun Ajaran <?= htmlspecialchars($pengaturan['tahun_ajaran'] ?? '2025/2026') ?></p>
            </div>
            <span style="font-size: 11px; font-weight: bold; color: #0f172a; background: #f1f5f9; padding: 4px 10px; border-radius: 6px; border: 1px solid #cbd5e1;">Lembar 1: Formulir Pendaftaran</span>
        </div>

        <!-- Bagian 1: Biodata -->
        <div>
            <div class="sec-title">Biodata Siswa &amp; Wali Murid</div>
            <div class="grid-2">
                <div>
                    <span class="f-label">Nomor Induk Siswa Nasional (NISN)</span>
                    <div class="f-box" style="font-family: monospace; font-weight: bold;"><?= htmlspecialchars($siswa['nisn']) ?></div>
                </div>
                <div>
                    <span class="f-label">Nama Lengkap Siswa *</span>
                    <div class="f-box" style="font-weight: 600;"><?= htmlspecialchars($siswa['nama']) ?></div>
                </div>
                <div>
                    <span class="f-label">Sekolah Asal (SD / MI) *</span>
                    <div class="f-box"><?= htmlspecialchars($siswa['sekolah_asal'] ?: 'SD/MI') ?></div>
                </div>
                <div>
                    <span class="f-label">Jenis Kelamin</span>
                    <div class="f-box"><?= htmlspecialchars($siswa['jenis_kelamin'] ?? 'Laki-laki') ?></div>
                </div>
                <div>
                    <span class="f-label">Nama Orang Tua / Wali *</span>
                    <div class="f-box"><?= htmlspecialchars($siswa['nama_ortu'] ?? '-') ?></div>
                </div>
                <div>
                    <span class="f-label">No. WhatsApp / HP Wali Murid *</span>
                    <div class="f-box"><?= htmlspecialchars($siswa['no_hp'] ?? '-') ?></div>
                </div>
                <div>
                    <span class="f-label">Alamat Tempat Tinggal</span>
                    <div class="f-box" style="height: 80px; overflow: hidden;"><?= nl2br(htmlspecialchars($siswa['alamat'] ?? '-')) ?></div>
                </div>
                <div>
                    <span class="f-label">Pas Foto Siswa (3x4) Resmi</span>
                    <div class="f-box" style="height: 80px; display: flex; align-items: center; gap: 12px; padding: 5px 12px;">
                        <?php if (!empty($foto_siswa_src)): ?>
                            <img src="<?= $foto_siswa_src ?>" style="width: 50px; height: 68px; object-fit: cover; border-radius: 6px; border: 1px solid #94a3b8; flex-shrink: 0;">
                        <?php else: ?>
                            <div style="width: 50px; height: 68px; border: 1px dashed #94a3b8; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 9px; color: #64748b; flex-shrink: 0;">3x4</div>
                        <?php endif; ?>
                        <span style="font-size: 10.5px; color: #475569; font-weight: 500;">Pas foto resmi telah terverifikasi dalam sistem.</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bagian 2: Kriteria AHP -->
        <div>
            <div class="sec-title">Kriteria Penilaian AHP</div>
            <div class="grid-2">
                <div>
                    <span class="f-label">Penghasilan Rata-rata Orang Tua per Bulan *</span>
                    <div class="f-box"><?= $label_penghasilan[$siswa['penghasilan']] ?? '-' ?></div>
                </div>
                <div>
                    <span class="f-label">Jumlah Anggota Keluarga yang Ditanggung *</span>
                    <div class="f-box"><?= $label_tanggungan[$siswa['tanggungan']] ?? '-' ?></div>
                </div>
                <div>
                    <span class="f-label">Kondisi Fisik Tempat Tinggal / Rumah *</span>
                    <div class="f-box"><?= $label_rumah[$siswa['kondisi_rumah']] ?? '-' ?></div>
                </div>
                <div>
                    <span class="f-label">Prestasi Akademik Tertinggi Siswa *</span>
                    <div class="f-box"><?= $label_prestasi[$siswa['prestasi']] ?? '-' ?></div>
                </div>
                <div>
                    <span class="f-label">Jarak Rumah Siswa ke Sekolah *</span>
                    <div class="f-box"><?= $label_jarak[$siswa['jarak']] ?? '-' ?></div>
                </div>
                <div>
                    <span class="f-label">Status Verifikasi Berkas Pendaftaran</span>
                    <div class="f-box" style="font-weight: bold; color: <?= $siswa['status_verifikasi'] === 'Terverifikasi' ? '#047857' : '#b45309' ?>;">
                        <?= htmlspecialchars($siswa['status_verifikasi']) ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bagian 3: Pernyataan & TTD -->
        <div style="border-top: 1px solid #cbd5e1; padding-top: 8px; margin-top: 6px;">
            <div style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 7px; padding: 6px 11px; font-size: 9.5px; line-height: 1.35; color: #475569; margin-bottom: 8px;">
                <b>Pernyataan Kebenaran Data:</b> Saya menyatakan dengan sesungguhnya bahwa seluruh data yang tercantum dalam formulir ini adalah benar, sah, dan dapat dipertanggungjawabkan sesuai dokumen pendukung fisik calon penerima bantuan Program Indonesia Pintar (PIP).
            </div>
            <div style="display: flex; justify-content: space-between; align-items: flex-end; font-size: 11px;">
                <div>
                    <p style="margin: 0; color: #64748b;">Tanggal Cetak: <b><?= date('d F Y') ?></b></p>
                    <p style="margin: 2px 0 0 0; color: #475569; font-weight: 600;">Status Akun: Terdaftar Resmi &bull; NISN: <?= htmlspecialchars($siswa['nisn']) ?></p>
                </div>
                <div style="text-align: center; width: 200px;">
                    <p style="margin: 0;">Calon Penerima / Wali Murid,</p>
                    <div style="height: 38px; border-bottom: 1px dotted #475569; margin: 2px auto 4px auto; width: 160px;"></div>
                    <p style="margin: 0; font-weight: bold;">( <?= htmlspecialchars($siswa['nama_ortu'] ?: $siswa['nama']) ?> )</p>
                </div>
            </div>
        </div>

        <!-- Footer Lembar 1 -->
        <div style="border-top: 2px solid #0f172a; padding-top: 6px; margin-top: auto; display: flex; justify-content: space-between; font-size: 10px; color: #64748b; font-weight: 500;">
            <span>Salinan Resmi Formulir Pendaftaran PIP &bull; Dicetak Mandiri melalui Portal Siswa</span>
            <span style="font-weight: bold; background: #f1f5f9; padding: 1px 6px; border-radius: 4px; border: 1px solid #cbd5e1; color: #0f172a;">Halaman 1 dari 2</span>
        </div>
    </div>

    <!-- LEMBAR 2: TANDA TERIMA RESMI -->
    <div class="page-sheet sheet-2">
        <div>
            <div style="display: flex; justify-content: space-between; font-size: 10px; color: #64748b; border-bottom: 1px solid #e2e8f0; padding-bottom: 3px; font-style: italic; margin-bottom: 10px;">
                <span>Lembar 2: Tanda Terima &amp; Bukti Pendaftaran Resmi</span>
                <span>No. NISN: <?= htmlspecialchars($siswa['nisn']) ?> &bull; Tanggal Cetak: <?= date('d/m/Y') ?></span>
            </div>

            <div style="text-align: center; border-bottom: 2px solid #000; padding-bottom: 8px; margin-bottom: 14px;">
                <h3 style="margin: 0; font-size: 14px; text-transform: uppercase;"><?= htmlspecialchars($pengaturan['nama_yayasan'] ?? 'YAYASAN AL QODIRI LAMPUNG') ?></h3>
                <h1 style="margin: 2px 0; font-size: 17px; text-transform: uppercase;"><?= htmlspecialchars($nama_sekolah) ?></h1>
                <p style="margin: 0; font-size: 11px;"><?= htmlspecialchars($pengaturan['sub_instansi'] ?? 'KABUPATEN LAMPUNG TENGAH') ?></p>
                <p style="margin: 2px 0 0 0; font-size: 10px; color: #334155;">Tanda Terima &amp; Bukti Pendaftaran Calon Penerima Bantuan PIP &bull; T.A. <?= htmlspecialchars($pengaturan['tahun_ajaran'] ?? '2025/2026') ?></p>
            </div>

            <div style="display: flex; justify-content: space-between; gap: 16px; margin-bottom: 14px;">
                <div style="flex: 1;">
                    <div class="sec-title" style="margin-top: 0;">I. Data Calon Penerima</div>
                    <table style="width: 100%; font-size: 11.5px; border-collapse: collapse;">
                        <tr><td style="width: 150px; padding: 2.5px 0; font-weight: 600;">Nomor NISN</td><td style="width: 12px;">:</td><td style="font-family: monospace; font-weight: bold;"><?= htmlspecialchars($siswa['nisn']) ?></td></tr>
                        <tr><td style="padding: 2.5px 0; font-weight: 600;">Nama Siswa</td><td>:</td><td style="font-weight: bold;"><?= htmlspecialchars($siswa['nama']) ?></td></tr>
                        <tr><td style="padding: 2.5px 0; font-weight: 600;">Jenis Kelamin</td><td>:</td><td><?= htmlspecialchars($siswa['jenis_kelamin'] ?? 'Laki-laki') ?></td></tr>
                        <tr><td style="padding: 2.5px 0; font-weight: 600;">Sekolah Asal</td><td>:</td><td><?= htmlspecialchars($siswa['sekolah_asal'] ?: 'SD/MI') ?></td></tr>
                        <tr><td style="padding: 2.5px 0; font-weight: 600;">Tingkat Kelas</td><td>:</td><td><?= htmlspecialchars($siswa['kelas']) ?></td></tr>
                        <tr><td style="padding: 2.5px 0; font-weight: 600;">Nama Wali Murid</td><td>:</td><td><?= htmlspecialchars($siswa['nama_ortu'] ?? '-') ?></td></tr>
                        <tr><td style="padding: 2.5px 0; font-weight: 600;">Nomor Kontak / HP</td><td>:</td><td><?= htmlspecialchars($siswa['no_hp'] ?? '-') ?></td></tr>
                        <tr><td style="padding: 2.5px 0; font-weight: 600;">Alamat</td><td>:</td><td><?= htmlspecialchars($siswa['alamat'] ?? '-') ?></td></tr>
                    </table>
                </div>
                <div style="width: 96px; height: 128px; border: 2px solid #94a3b8; display: flex; align-items: center; justify-content: center; background: #f8fafc; overflow: hidden; flex-shrink: 0;">
                    <?php if (!empty($foto_siswa_src)): ?>
                        <img src="<?= $foto_siswa_src ?>" style="width: 100%; height: 100%; object-fit: cover;">
                    <?php else: ?>
                        <span style="font-size: 10px; font-weight: bold; color: #94a3b8;">FOTO 3x4</span>
                    <?php endif; ?>
                </div>
            </div>

            <div style="margin-bottom: 16px;">
                <div class="sec-title">II. Rincian Kriteria Sosial Ekonomi</div>
                <table style="width: 100%; font-size: 11.5px; border-collapse: collapse;">
                    <tr><td style="width: 150px; padding: 2.5px 0; font-weight: 600;">Penghasilan Orang Tua</td><td style="width: 12px;">:</td><td><?= $label_penghasilan[$siswa['penghasilan']] ?? '-' ?></td></tr>
                    <tr><td style="padding: 2.5px 0; font-weight: 600;">Tanggungan Keluarga</td><td>:</td><td><?= $label_tanggungan[$siswa['tanggungan']] ?? '-' ?></td></tr>
                    <tr><td style="padding: 2.5px 0; font-weight: 600;">Kondisi Rumah</td><td>:</td><td><?= $label_rumah[$siswa['kondisi_rumah']] ?? '-' ?></td></tr>
                    <tr><td style="padding: 2.5px 0; font-weight: 600;">Prestasi Siswa</td><td>:</td><td><?= $label_prestasi[$siswa['prestasi']] ?? '-' ?></td></tr>
                    <tr><td style="padding: 2.5px 0; font-weight: 600;">Jarak ke Sekolah</td><td>:</td><td><?= $label_jarak[$siswa['jarak']] ?? '-' ?></td></tr>
                    <tr><td style="padding: 2.5px 0; font-weight: 600;">Status Berkas</td><td>:</td><td style="font-weight: bold; color: <?= $siswa['status_verifikasi'] === 'Terverifikasi' ? '#047857' : '#b45309' ?>;"><?= htmlspecialchars($siswa['status_verifikasi']) ?></td></tr>
                </table>
            </div>
        </div>

        <div style="margin-top: auto; display: flex; justify-content: space-around; text-align: center; font-size: 11.5px;">
            <div>
                <p style="margin: 0;">Orang Tua / Wali Murid,</p>
                <div style="height: 52px;"></div>
                <p style="margin: 0; font-weight: bold; text-decoration: underline;"><?= htmlspecialchars($siswa['nama_ortu'] ?: $siswa['nama']) ?></p>
            </div>
            <div>
                <p style="margin: 0;">Panitia Seleksi PIP Sekolah,</p>
                <div style="height: 52px;"></div>
                <p style="margin: 0; font-weight: bold; text-decoration: underline;"><?= htmlspecialchars($pengaturan['kepala_sekolah'] ?? 'Panitia TU') ?></p>
            </div>
        </div>
    </div>
</body>
</html>
<?php
$html_content = ob_get_clean();

// Cari binary Chrome atau Edge
$chrome_path = "C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe";
if (!file_exists($chrome_path)) {
    $chrome_path = "C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe";
}

$clean_nama = preg_replace('/[^a-zA-Z0-9_-]/', '_', $siswa['nama']);
$clean_nisn = preg_replace('/[^a-zA-Z0-9_-]/', '_', $siswa['nisn']);
$pdf_filename = "Bukti_Pendaftaran_PIP_{$clean_nama}_{$clean_nisn}.pdf";

$temp_id = uniqid("spk_pdf_");
$temp_html = sys_get_temp_dir() . "\\{$temp_id}.html";
$temp_pdf  = sys_get_temp_dir() . "\\{$temp_id}.pdf";

file_put_contents($temp_html, $html_content);

if (file_exists($chrome_path)) {
    $cmd = "\"$chrome_path\" --headless --disable-gpu --no-pdf-header-footer --print-to-pdf=\"$temp_pdf\" \"file:///$temp_html\" 2>&1";
    $output = [];
    $ret = 0;
    exec($cmd, $output, $ret);

    if (file_exists($temp_pdf) && filesize($temp_pdf) > 1000) {
        // Bersihkan seluruh output buffer agar header & byte biner PDF murni dari offset 0
        while (ob_get_level()) {
            ob_end_clean();
        }

        // Berhasil! Kirim file PDF ke browser
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $pdf_filename . '"');
        header('Content-Length: ' . filesize($temp_pdf));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        readfile($temp_pdf);

        @unlink($temp_html);
        @unlink($temp_pdf);
        exit;
    }
}

// Fallback jika headless browser gagal: tampilkan HTML dan langsung panggil window.print()
@unlink($temp_html);
@unlink($temp_pdf);
echo $html_content;
echo "<script>window.onload = function() { window.print(); };</script>";
exit;
