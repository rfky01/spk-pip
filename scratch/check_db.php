<?php
require_once "koneksi.php";

$q = mysqli_query($koneksi, "SELECT count(*) as cnt FROM calon_penerima");
$r = mysqli_fetch_assoc($q);
echo "Total in DB: " . $r['cnt'] . "\n";

$q2 = mysqli_query($koneksi, "SELECT id_siswa, nisn, nama, no_hp, jenis_kelamin, sekolah_asal, alamat, tahun, penghasilan, kondisi_rumah, prestasi, jarak FROM calon_penerima");
$rows = [];
while ($row = mysqli_fetch_assoc($q2)) {
    $rows[] = $row;
}

echo "Top 10 in DB:\n";
foreach (array_slice($rows, 0, 10) as $row) {
    echo "{$row['id_siswa']}: {$row['nama']} (NISN: {$row['nisn']})\n";
}
