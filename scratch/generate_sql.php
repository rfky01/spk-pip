<?php
$json_str = file_get_contents(__DIR__ . "/students_data.json");
$students = json_decode($json_str, true);

$w1 = 0.4162;
$w2 = 0.2618;
$w3 = 0.1608;
$w4 = 0.0985;
$w5 = 0.0623;

// Compute scores
foreach ($students as &$s) {
    $c1 = (int)($s['c1_val'] ?? 3);
    $c2 = (int)($s['c2_val'] ?? 3);
    $c3 = (int)($s['c3_val'] ?? 3);
    $c4 = (int)($s['c4_val'] ?? 2);
    $c5 = (int)($s['c5_val'] ?? 2);
    
    $skor = ($c1 * $w1) + ($c2 * $w2) + ($c3 * $w3) + ($c4 * $w4) + ($c5 * $w5);
    $s['total_skor'] = round($skor, 4);
    
    // PIN = full NISN
    $pin_raw = trim((string)$s['nisn']);
    $s['pin_raw'] = $pin_raw;
    $s['pin_hashed'] = password_hash($pin_raw, PASSWORD_DEFAULT);
}
unset($s);

// Sort descending by total_skor, then preserve initial no order if equal
usort($students, function($a, $b) {
    if (abs($b['total_skor'] - $a['total_skor']) > 0.00001) {
        return ($b['total_skor'] > $a['total_skor']) ? 1 : -1;
    }
    return $a['no'] <=> $b['no'];
});

// Assign ranking
$rank = 1;
foreach ($students as &$s) {
    $s['ranking'] = $rank++;
}
unset($s);

echo "Verifying Top 10 rankings (PIN = NISN):\n";
for ($i = 0; $i < 10; $i++) {
    $st = $students[$i];
    echo "Rank {$st['ranking']}: {$st['nama_final']} (NISN: {$st['nisn']} -> PIN: {$st['pin_raw']}) | Skor: {$st['total_skor']}\n";
}

// Generate SQL
$sql_lines = [];
$sql_lines[] = "-- =============================================================================";
$sql_lines[] = "-- DATA CALON PENERIMA PIP (109 SISWA) - SMP TUNAS BANGSA";
$sql_lines[] = "-- Target Website: pangkalmart.my.id";
$sql_lines[] = "-- Keterangan PIN Akun Siswa: PIN disamakan dengan nomor NISN";
$sql_lines[] = "-- Tanggal Generate: " . date('Y-m-d H:i:s');
$sql_lines[] = "-- =============================================================================\n";

$sql_lines[] = "-- 1. Pastikan kolom sekolah_asal, pin, dan foto tersedia di tabel calon_penerima";
$sql_lines[] = "ALTER TABLE `calon_penerima` ADD COLUMN IF NOT EXISTS `foto` VARCHAR(255) NULL AFTER `no_hp`;";
$sql_lines[] = "ALTER TABLE `calon_penerima` ADD COLUMN IF NOT EXISTS `pin` VARCHAR(255) NULL AFTER `foto`;";
$sql_lines[] = "ALTER TABLE `calon_penerima` ADD COLUMN IF NOT EXISTS `sekolah_asal` VARCHAR(100) NULL AFTER `kelas`;\n";

$sql_lines[] = "-- 2. Kosongkan data lama di tabel calon_penerima agar tidak terjadi duplikasi NISN / ID";
$sql_lines[] = "TRUNCATE TABLE `calon_penerima`;\n";


$sql_lines[] = "-- 3. Masukkan 109 Data Calon Penerima PIP";
$sql_lines[] = "INSERT INTO `calon_penerima` (`id_siswa`, `nisn`, `nama`, `nama_ortu`, `no_hp`, `foto`, `pin`, `jenis_kelamin`, `kelas`, `sekolah_asal`, `alamat`, `penghasilan`, `tanggungan`, `kondisi_rumah`, `prestasi`, `jarak`, `total_skor`, `ranking`, `tahun`, `status_verifikasi`, `created_at`) VALUES";

$val_rows = [];
$id_counter = 1;
foreach ($students as $st) {
    $nisn = addslashes($st['nisn']);
    $nama = addslashes($st['nama_final']);
    $ortu = addslashes($st['nama_ortu']);
    $hp = addslashes($st['hp']);
    $pin = addslashes($st['pin_hashed']);
    $jk = addslashes($st['jk']);
    $kelas = 'Kelas VII';
    $asal = addslashes($st['sekolah_asal']);
    $alamat = addslashes($st['alamat']);
    $c1 = (int)$st['c1_val'];
    $c2 = (int)$st['c2_val'];
    $c3 = (int)$st['c3_val'];
    $c4 = (int)$st['c4_val'];
    $c5 = (int)$st['c5_val'];
    $skor = $st['total_skor'];
    $ranking = $st['ranking'];
    $tahun = '2025/2026';
    
    $val_rows[] = "($id_counter, '$nisn', '$nama', '$ortu', '$hp', NULL, '$pin', '$jk', '$kelas', '$asal', '$alamat', $c1, $c2, $c3, $c4, $c5, $skor, $ranking, '$tahun', 'Terverifikasi', NOW())";
    $id_counter++;
}

$sql_lines[] = implode(",\n", $val_rows) . ";\n";

$final_sql = implode("\n", $sql_lines);

$path1 = "D:\\KULIAH\\Joki\\Annisa\\skripsi\\BAB IV\\import_data_calon_penerima.sql";
$path2 = "C:\\laragon\\www\\spk-pip\\import_data_calon_penerima.sql";

file_put_contents($path1, $final_sql);
file_put_contents($path2, $final_sql);

echo "\nSUCCESS! Generated clean SQL files with PIN = NISN:\n";
echo "1. $path1 (" . strlen($final_sql) . " bytes)\n";
echo "2. $path2 (" . strlen($final_sql) . " bytes)\n";
