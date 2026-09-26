<?php
// data_calon_penerima.php - Halaman Pengelolaan Data Calon Penerima PIP (CRUD & Verifikasi Pengajuan)
$page_title = "Data Calon Penerima";
require_once "header.php";

$msg = "";
$msg_type = "";

// A. Tambah Siswa Baru oleh Admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'tambah_siswa') {
    $nisn          = trim($_POST['nisn'] ?? '');
    $nama          = trim($_POST['nama'] ?? '');
    $nama_ortu     = trim($_POST['nama_ortu'] ?? '');
    $no_hp         = trim($_POST['no_hp'] ?? '');
    $jenis_kelamin = $_POST['jenis_kelamin'] ?? 'Laki-laki';
    $sekolah_asal  = trim($_POST['sekolah_asal'] ?? '');
    $kelas         = 'Kelas VII';
    $alamat        = trim($_POST['alamat'] ?? '');
    $c1            = (int)($_POST['penghasilan'] ?? 1);
    $c2            = (int)($_POST['tanggungan'] ?? 1);
    $c3            = (int)($_POST['kondisi_rumah'] ?? 1);
    $c4            = (int)($_POST['prestasi'] ?? 1);
    $c5            = (int)($_POST['jarak'] ?? 1);

    $tahun         = trim($_POST['tahun'] ?? ($pengaturan['tahun_ajaran'] ?? '2025/2026'));
    if (empty($tahun)) $tahun = '2025/2026';

    if (empty($nisn) || empty($nama)) {
        $msg = "NISN dan Nama Lengkap siswa wajib diisi!";
        $msg_type = "error";
    } else {
        $pin_default = password_hash(substr($nisn, -4) ?: '123456', PASSWORD_DEFAULT);
        $stmt = mysqli_prepare($koneksi, "INSERT INTO `calon_penerima` 
            (`nisn`, `nama`, `nama_ortu`, `no_hp`, `pin`, `jenis_kelamin`, `kelas`, `sekolah_asal`, `alamat`, `penghasilan`, `tanggungan`, `kondisi_rumah`, `prestasi`, `jarak`, `status_verifikasi`, `tahun`) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Terverifikasi', ?)");
        mysqli_stmt_bind_param($stmt, "sssssssssiiiiis", $nisn, $nama, $nama_ortu, $no_hp, $pin_default, $jenis_kelamin, $kelas, $sekolah_asal, $alamat, $c1, $c2, $c3, $c4, $c5, $tahun);
        if (mysqli_stmt_execute($stmt)) {
            $msg = "Data calon penerima ($nama) berhasil disimpan untuk Tahun Ajaran $tahun dan langsung Terverifikasi! (PIN Akun Default: 4 digit terakhir NISN)";
            $msg_type = "success";
        } else {
            $msg = "Gagal menyimpan data siswa (NISN mungkin sudah terdaftar).";
            $msg_type = "error";
        }
    }
}

// B. Edit Data Siswa
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_siswa') {
    $id_siswa          = (int)($_POST['id_siswa'] ?? 0);
    $nisn              = trim($_POST['nisn'] ?? '');
    $nama              = trim($_POST['nama'] ?? '');
    $nama_ortu         = trim($_POST['nama_ortu'] ?? '');
    $no_hp             = trim($_POST['no_hp'] ?? '');
    $jenis_kelamin     = $_POST['jenis_kelamin'] ?? 'Laki-laki';
    $sekolah_asal      = trim($_POST['sekolah_asal'] ?? '');
    $kelas             = 'Kelas VII';
    $alamat            = trim($_POST['alamat'] ?? '');
    $c1                = (int)($_POST['penghasilan'] ?? 1);
    $c2                = (int)($_POST['tanggungan'] ?? 1);
    $c3                = (int)($_POST['kondisi_rumah'] ?? 1);
    $c4                = (int)($_POST['prestasi'] ?? 1);
    $c5                = (int)($_POST['jarak'] ?? 1);
    $status_verifikasi = $_POST['status_verifikasi'] ?? 'Menunggu Verifikasi';
    $tahun             = trim($_POST['tahun'] ?? ($pengaturan['tahun_ajaran'] ?? '2025/2026'));
    if (empty($tahun)) $tahun = '2025/2026';

    $stmt = mysqli_prepare($koneksi, "UPDATE `calon_penerima` SET 
        `nisn`=?, `nama`=?, `nama_ortu`=?, `no_hp`=?, `jenis_kelamin`=?, `kelas`=?, `sekolah_asal`=?, `alamat`=?, 
        `penghasilan`=?, `tanggungan`=?, `kondisi_rumah`=?, `prestasi`=?, `jarak`=?, `status_verifikasi`=?, `tahun`=? 
        WHERE `id_siswa`=?");
    mysqli_stmt_bind_param($stmt, "ssssssssiiiiissi", $nisn, $nama, $nama_ortu, $no_hp, $jenis_kelamin, $kelas, $sekolah_asal, $alamat, $c1, $c2, $c3, $c4, $c5, $status_verifikasi, $tahun, $id_siswa);
    if (mysqli_stmt_execute($stmt)) {
        $msg = "Data siswa ($nama) berhasil diperbarui!";
        $msg_type = "success";
    } else {
        $msg = "Gagal memperbarui data siswa.";
        $msg_type = "error";
    }
}

// C. Verifikasi Pengajuan Siswa (1-Klik)
if (isset($_GET['action']) && $_GET['action'] === 'verifikasi_siswa') {
    $id_v = (int)($_GET['id'] ?? 0);
    if ($id_v > 0) {
        mysqli_query($koneksi, "UPDATE `calon_penerima` SET `status_verifikasi` = 'Terverifikasi' WHERE `id_siswa` = $id_v");
        $msg = "Pengajuan siswa berhasil diverifikasi dan disetujui!";
        $msg_type = "success";
    }
}

// D. Tolak Pengajuan Siswa
if (isset($_GET['action']) && $_GET['action'] === 'tolak_siswa') {
    $id_t = (int)($_GET['id'] ?? 0);
    if ($id_t > 0) {
        mysqli_query($koneksi, "UPDATE `calon_penerima` SET `status_verifikasi` = 'Ditolak' WHERE `id_siswa` = $id_t");
        $msg = "Pengajuan siswa telah ditandai Ditolak.";
        $msg_type = "error";
    }
}

// E. Hapus Data Siswa
if (isset($_GET['action']) && $_GET['action'] === 'hapus_siswa') {
    $id_del = (int)($_GET['id'] ?? 0);
    if ($id_del > 0) {
        mysqli_query($koneksi, "DELETE FROM `calon_penerima` WHERE `id_siswa` = $id_del");
        $msg = "Data siswa berhasil dihapus!";
        $msg_type = "success";
    }
}

// F. Reset PIN Akun Siswa (oleh Admin jika siswa lupa PIN)
if (isset($_GET['action']) && $_GET['action'] === 'reset_pin_siswa') {
    $id_rp = (int)($_GET['id'] ?? 0);
    if ($id_rp > 0) {
        $q_s = mysqli_query($koneksi, "SELECT nisn, nama FROM `calon_penerima` WHERE `id_siswa` = $id_rp LIMIT 1");
        if ($s_row = mysqli_fetch_assoc($q_s)) {
            $default_pin = substr($s_row['nisn'], -4);
            if (empty($default_pin)) $default_pin = '123456';
            $h_pin = password_hash($default_pin, PASSWORD_DEFAULT);
            mysqli_query($koneksi, "UPDATE `calon_penerima` SET `pin` = '$h_pin' WHERE `id_siswa` = $id_rp");
            $msg = "PIN Akun Pendaftar untuk siswa <b>" . htmlspecialchars($s_row['nama']) . "</b> (NISN: <b>{$s_row['nisn']}</b>) berhasil direset menjadi: <b>$default_pin</b> (4 digit terakhir NISN).";
            $msg_type = "success";
        }
    }
}

require_once "sidebar.php";

// Filter status jika ada
$filter_status = $_GET['status'] ?? 'all';
$where_calon = "";
if ($filter_status === 'menunggu') {
    $where_calon = " WHERE `status_verifikasi` = 'Menunggu Verifikasi'";
} elseif ($filter_status === 'terverifikasi') {
    $where_calon = " WHERE `status_verifikasi` = 'Terverifikasi'";
} elseif ($filter_status === 'ditolak') {
    $where_calon = " WHERE `status_verifikasi` = 'Ditolak'";
}

// Konfigurasi Pagination (10 data per halaman)
$limit_calon = 10;
$res_count_calon = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM `calon_penerima` $where_calon");
$total_tampil = (int)(mysqli_fetch_assoc($res_count_calon)['total'] ?? 0);
$total_pages_calon = max(1, (int)ceil($total_tampil / $limit_calon));
$page_calon = max(1, min((int)($_GET['page'] ?? 1), $total_pages_calon));
$offset_calon = ($page_calon - 1) * $limit_calon;

$query_calon = "SELECT * FROM `calon_penerima` $where_calon ORDER BY `id_siswa` DESC LIMIT $limit_calon OFFSET $offset_calon";
$res_calon = mysqli_query($koneksi, $query_calon);

// Hitung statistik verifikasi
$count_all = mysqli_num_rows(mysqli_query($koneksi, "SELECT id_siswa FROM `calon_penerima`"));
$count_menunggu = mysqli_num_rows(mysqli_query($koneksi, "SELECT id_siswa FROM `calon_penerima` WHERE `status_verifikasi` = 'Menunggu Verifikasi'"));
$count_terverifikasi = mysqli_num_rows(mysqli_query($koneksi, "SELECT id_siswa FROM `calon_penerima` WHERE `status_verifikasi` = 'Terverifikasi'"));
$count_ditolak = mysqli_num_rows(mysqli_query($koneksi, "SELECT id_siswa FROM `calon_penerima` WHERE `status_verifikasi` = 'Ditolak'"));
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

    <!-- HEADER HALAMAN -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Daftar Usulan Calon Penerima PIP</h1>
            <p class="text-xs text-slate-500 mt-1">Pengelolaan berkas usulan pendaftar murid baru kelas VII SMP Tunas Bangsa</p>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            <button type="button" onclick="openTambahModal()" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white font-medium rounded-lg text-xs shadow-sm flex items-center gap-2 transition-colors cursor-pointer whitespace-nowrap">
                <i class="fa-solid fa-user-plus text-xs"></i>
                <span>Tambah Siswa Manual</span>
            </button>
        </div>
    </div>
    
    <!-- TABEL UTAMA DAFTAR USULAN CALON PENERIMA PIP (FULL-WIDTH) -->
    <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm w-full space-y-4">
        <!-- Toolbar: Filter Status & Pencarian -->
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-4 border-b border-slate-100">
            <!-- Filter Status Seragam & Profesional -->
            <div class="flex items-center gap-2 overflow-x-auto pb-1 sm:pb-0">
                <a href="data_calon_penerima.php?status=all" class="px-3.5 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap transition-colors inline-flex items-center <?= $filter_status === 'all' ? 'bg-slate-900 text-white shadow-sm' : 'bg-slate-50 text-slate-700 hover:bg-slate-100 hover:text-slate-900 border border-slate-200' ?>">
                    <span>Semua Usulan</span>
                    <span class="ml-2 px-2 py-0.5 rounded-full text-[11px] font-bold transition-colors <?= $filter_status === 'all' ? 'bg-white text-slate-900 shadow-sm' : 'bg-slate-200/90 text-slate-800' ?>">
                        <?= $count_all ?>
                    </span>
                </a>
                <a href="data_calon_penerima.php?status=menunggu" class="px-3.5 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap transition-colors inline-flex items-center <?= $filter_status === 'menunggu' ? 'bg-slate-900 text-white shadow-sm' : 'bg-slate-50 text-slate-700 hover:bg-slate-100 hover:text-slate-900 border border-slate-200' ?>">
                    <span>Menunggu</span>
                    <span class="ml-2 px-2 py-0.5 rounded-full text-[11px] font-bold transition-colors <?= $filter_status === 'menunggu' ? 'bg-white text-slate-900 shadow-sm' : 'bg-slate-200/90 text-slate-800' ?>">
                        <?= $count_menunggu ?>
                    </span>
                </a>
                <a href="data_calon_penerima.php?status=terverifikasi" class="px-3.5 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap transition-colors inline-flex items-center <?= $filter_status === 'terverifikasi' ? 'bg-slate-900 text-white shadow-sm' : 'bg-slate-50 text-slate-700 hover:bg-slate-100 hover:text-slate-900 border border-slate-200' ?>">
                    <span>Terverifikasi</span>
                    <span class="ml-2 px-2 py-0.5 rounded-full text-[11px] font-bold transition-colors <?= $filter_status === 'terverifikasi' ? 'bg-white text-slate-900 shadow-sm' : 'bg-slate-200/90 text-slate-800' ?>">
                        <?= $count_terverifikasi ?>
                    </span>
                </a>
                <a href="data_calon_penerima.php?status=ditolak" class="px-3.5 py-1.5 rounded-lg text-xs font-semibold whitespace-nowrap transition-colors inline-flex items-center <?= $filter_status === 'ditolak' ? 'bg-slate-900 text-white shadow-sm' : 'bg-slate-50 text-slate-700 hover:bg-slate-100 hover:text-slate-900 border border-slate-200' ?>">
                    <span>Ditolak</span>
                    <span class="ml-2 px-2 py-0.5 rounded-full text-[11px] font-bold transition-colors <?= $filter_status === 'ditolak' ? 'bg-white text-slate-900 shadow-sm' : 'bg-slate-200/90 text-slate-800' ?>">
                        <?= $count_ditolak ?>
                    </span>
                </a>
            </div>

            <!-- Pencarian Cepat -->
            <div class="relative w-full sm:w-72">
                <input type="text" id="filter-calon" onkeyup="filterTableCalon()" placeholder="Cari NISN, Siswa, Sekolah Asal, Ortu..." 
                    class="w-full pl-9 pr-3 py-1.5 border border-slate-200 rounded-lg text-xs bg-slate-50/50 focus:bg-white focus:outline-none focus:ring-1 focus:ring-slate-400 transition-all">
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-2.5 text-slate-400 text-xs"></i>
            </div>
        </div>

        <!-- Tabel Responsive -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs min-w-[850px]" id="table-calon">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold uppercase text-[11px] tracking-wider">
                        <th class="p-3 w-10 text-center">No</th>
                        <th class="p-3">NISN & Siswa</th>
                        <th class="p-3">Sekolah Asal</th>
                        <th class="p-3">Orang Tua / Kontak</th>
                        <th class="p-3 text-center" title="Penghasilan Orang Tua">C1</th>
                        <th class="p-3 text-center" title="Jumlah Tanggungan">C2</th>
                        <th class="p-3 text-center" title="Kondisi Rumah">C3</th>
                        <th class="p-3 text-center" title="Prestasi Akademik">C4</th>
                        <th class="p-3 text-center" title="Jarak ke Sekolah">C5</th>
                        <th class="p-3 text-center">Status Verifikasi</th>
                        <th class="p-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-gray-700">
                    <?php if ($total_tampil == 0): ?>
                        <tr>
                            <td colspan="11" class="p-8 text-center text-gray-400">
                                <div class="flex flex-col items-center justify-center space-y-2">
                                    <i class="fa-solid fa-inbox text-3xl text-gray-300"></i>
                                    <span>Belum ada data usulan calon siswa sesuai filter saat ini.</span>
                                    <button type="button" onclick="openTambahModal()" class="text-xs text-blue-600 hover:underline font-semibold mt-1">
                                        + Tambah Siswa Manual Sekarang
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php else:
                        $no = $offset_calon + 1;
                        while ($siswa = mysqli_fetch_assoc($res_calon)): 
                            $status_v = $siswa['status_verifikasi'] ?? 'Menunggu Verifikasi';
                    ?>
                    <tr class="hover:bg-gray-50/80 transition-colors">
                        <td class="p-3 text-center font-medium"><?= $no++ ?></td>
                        <td class="p-3 font-medium">
                            <button type="button" onclick='openDetailModal(<?= htmlspecialchars(json_encode($siswa), ENT_QUOTES, 'UTF-8') ?>)' 
                                class="font-bold text-gray-800 hover:text-blue-600 text-sm text-left transition-colors flex items-center gap-1.5 group cursor-pointer" 
                                title="Klik untuk melihat detail lengkap <?= htmlspecialchars($siswa['nama']) ?>">
                                <span class="group-hover:underline underline-offset-2"><?= htmlspecialchars($siswa['nama']) ?></span>
                                <i class="fa-solid fa-circle-info text-[11px] text-gray-400 group-hover:text-blue-600 transition-colors"></i>
                            </button>
                            <span class="font-semibold text-blue-600 text-[11px] block"><?= htmlspecialchars($siswa['nisn']) ?></span>
                            <span class="text-[10px] text-gray-400 block"><?= htmlspecialchars($siswa['jenis_kelamin']) ?></span>
                        </td>
                        <td class="p-3">
                            <span class="font-semibold text-gray-800 block"><?= htmlspecialchars($siswa['sekolah_asal'] ?: '-') ?></span>
                            <div class="flex items-center gap-1 mt-0.5">
                                <span class="text-[10px] text-blue-600 font-semibold bg-blue-50 px-1.5 py-0.5 rounded">Kelas VII</span>
                                <span class="text-[10px] text-slate-500 font-medium bg-slate-100 px-1.5 py-0.5 rounded"><?= htmlspecialchars($siswa['tahun'] ?? '2025/2026') ?></span>
                            </div>
                        </td>
                        <td class="p-3">
                            <span class="font-medium text-gray-700 block"><?= htmlspecialchars($siswa['nama_ortu'] ?: '-') ?></span>
                            <?php if (!empty($siswa['no_hp'])): ?>
                                <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $siswa['no_hp']) ?>" target="_blank" class="text-[11px] text-emerald-600 hover:underline flex items-center gap-1 mt-0.5">
                                    <i class="fa-brands fa-whatsapp text-xs"></i> <?= htmlspecialchars($siswa['no_hp']) ?>
                                </a>
                            <?php else: ?>
                                <span class="text-[10px] text-gray-400">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="p-3 text-center text-slate-700 font-semibold font-mono"><?= $siswa['penghasilan'] ?></td>
                        <td class="p-3 text-center text-slate-700 font-semibold font-mono"><?= $siswa['tanggungan'] ?></td>
                        <td class="p-3 text-center text-slate-700 font-semibold font-mono"><?= $siswa['kondisi_rumah'] ?></td>
                        <td class="p-3 text-center text-slate-700 font-semibold font-mono"><?= $siswa['prestasi'] ?></td>
                        <td class="p-3 text-center text-slate-700 font-semibold font-mono"><?= $siswa['jarak'] ?></td>
                        
                        <!-- Status Verifikasi -->
                        <td class="p-3 text-center whitespace-nowrap">
                            <?php if ($status_v === 'Terverifikasi'): ?>
                                <span class="px-2.5 py-0.5 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-full text-[10px] font-semibold inline-flex items-center gap-1 whitespace-nowrap">
                                    <i class="fa-solid fa-check text-[9px]"></i> Terverifikasi
                                </span>
                            <?php elseif ($status_v === 'Ditolak'): ?>
                                <span class="px-2.5 py-0.5 bg-slate-100 text-slate-600 border border-slate-200 rounded-full text-[10px] font-semibold inline-flex items-center gap-1 whitespace-nowrap">
                                    <i class="fa-solid fa-xmark text-[9px]"></i> Ditolak
                                </span>
                            <?php else: ?>
                                <span class="px-2.5 py-0.5 bg-amber-50 text-amber-700 border border-amber-200 rounded-full text-[10px] font-semibold inline-flex items-center gap-1 whitespace-nowrap">
                                    <i class="fa-solid fa-clock text-[9px]"></i> Menunggu
                                </span>
                            <?php endif; ?>
                        </td>

                        <!-- Tombol Aksi -->
                        <td class="p-3 text-center space-x-1 whitespace-nowrap">
                            <?php if ($status_v === 'Menunggu Verifikasi'): ?>
                                <a href="data_calon_penerima.php?action=verifikasi_siswa&id=<?= $siswa['id_siswa'] ?>" 
                                    class="px-2.5 py-1.5 bg-slate-900 hover:bg-slate-800 text-white rounded-lg text-[11px] font-medium transition-colors shadow-sm inline-flex items-center gap-1" title="Setujui Berkas">
                                    <i class="fa-solid fa-check"></i> Setujui
                                </a>
                                <a href="data_calon_penerima.php?action=tolak_siswa&id=<?= $siswa['id_siswa'] ?>" 
                                    onclick="return confirm('Apakah berkas siswa <?= htmlspecialchars($siswa['nama']) ?> ditolak?')"
                                    class="px-2.5 py-1.5 bg-white border border-slate-200 hover:bg-slate-50 text-slate-600 rounded-lg text-[11px] font-medium transition-colors inline-flex items-center gap-1" title="Tolak">
                                    <i class="fa-solid fa-xmark"></i> Tolak
                                </a>
                            <?php endif; ?>

                            <a href="data_calon_penerima.php?action=reset_pin_siswa&id=<?= $siswa['id_siswa'] ?>" 
                                onclick="return confirm('Reset PIN akun pendaftar <?= htmlspecialchars(addslashes($siswa['nama'])) ?> menjadi 4 digit terakhir NISN?')" 
                                class="px-2.5 py-1.5 bg-white hover:bg-amber-50 border border-slate-200 text-slate-500 hover:text-amber-700 rounded-lg text-[11px] transition-colors inline-block" title="Reset PIN Akun Siswa">
                                <i class="fa-solid fa-key"></i>
                            </a>
                            <button type="button" onclick='openEditModal(<?= json_encode($siswa) ?>)' class="px-2.5 py-1.5 bg-white hover:bg-slate-50 border border-slate-200 text-slate-600 rounded-lg text-[11px] transition-colors cursor-pointer" title="Edit Data Siswa">
                                <i class="fa-solid fa-pen"></i>
                            </button>
                            <a href="data_calon_penerima.php?action=hapus_siswa&id=<?= $siswa['id_siswa'] ?>" 
                                onclick="return confirm('Apakah Anda yakin ingin menghapus data <?= htmlspecialchars($siswa['nama']) ?>?')"
                                class="px-2.5 py-1.5 bg-white hover:bg-rose-50 border border-slate-200 text-slate-400 hover:text-rose-600 rounded-lg text-[11px] transition-colors" title="Hapus">
                                <i class="fa-solid fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination Data Calon Penerima -->
        <?php if ($total_tampil > 0): 
            $start_display = $offset_calon + 1;
            $end_display = min($offset_calon + $limit_calon, $total_tampil);
        ?>
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-4 border-t border-slate-100 text-xs">
            <div class="text-slate-500">
                Menampilkan <span class="font-bold text-slate-800"><?= $start_display ?></span> - <span class="font-bold text-slate-800"><?= $end_display ?></span> dari <span class="font-bold text-slate-800"><?= $total_tampil ?></span> usulan siswa
            </div>
            <?php if ($total_pages_calon > 1): ?>
            <div class="flex items-center gap-1">
                <?php
                $url_prev = '?' . http_build_query(array_merge($_GET, ['page' => max(1, $page_calon - 1)]));
                $url_next = '?' . http_build_query(array_merge($_GET, ['page' => min($total_pages_calon, $page_calon + 1)]));
                ?>
                <!-- Prev Button -->
                <?php if ($page_calon > 1): ?>
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

                    for ($p = 1; $p <= $total_pages_calon; $p++):
                        if ($p == 1 || $p == $total_pages_calon || ($p >= $page_calon - $range && $p <= $page_calon + $range)):
                            $url_page = '?' . http_build_query(array_merge($_GET, ['page' => $p]));
                    ?>
                        <a href="<?= htmlspecialchars($url_page) ?>" class="w-8 h-8 rounded-lg flex items-center justify-center font-bold transition-all <?= $p == $page_calon ? 'bg-slate-900 text-white shadow-sm' : 'bg-white hover:bg-slate-50 border border-slate-200 text-slate-700' ?>">
                            <?= $p ?>
                        </a>
                    <?php
                        elseif ($p < $page_calon - $range && !$show_dots_left):
                            $show_dots_left = true;
                            echo '<span class="w-6 text-center text-slate-400 font-bold">...</span>';
                        elseif ($p > $page_calon + $range && !$show_dots_right):
                            $show_dots_right = true;
                            echo '<span class="w-6 text-center text-slate-400 font-bold">...</span>';
                        endif;
                    endfor;
                    ?>
                </div>

                <!-- Next Button -->
                <?php if ($page_calon < $total_pages_calon): ?>
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
</div>

<!-- MODAL TAMBAH SISWA MANUAL -->
<div id="modal-tambah-siswa" class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden flex items-center justify-center z-50 p-4 transition-all">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-xl p-6 border border-gray-100 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between pb-3 border-b border-gray-100 mb-4">
            <div>
                <h3 class="font-bold text-gray-800 text-base flex items-center gap-2">
                    <i class="fa-solid fa-user-plus text-blue-600"></i> Form Tambah Calon Siswa (Manual)
                </h3>
                <p class="text-[11px] text-gray-400 mt-0.5">Input data calon siswa oleh pihak sekolah (Otomatis Terverifikasi)</p>
            </div>
            <button type="button" onclick="closeTambahModal()" class="text-gray-400 hover:text-gray-600 p-1.5 rounded-lg hover:bg-gray-100">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form action="data_calon_penerima.php" method="POST" class="space-y-3" id="form-tambah-siswa">
            <input type="hidden" name="action" value="tambah_siswa">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">NISN Siswa *</label>
                    <input type="text" name="nisn" id="tambah-nisn" required placeholder="Contoh: 100503001" 
                        class="w-full px-3 py-1.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-500 text-sm font-medium">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Nama Lengkap Siswa *</label>
                    <input type="text" name="nama" required placeholder="Nama Lengkap Siswa" 
                        class="w-full px-3 py-1.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-500 text-sm font-medium">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Nama Orang Tua</label>
                    <input type="text" name="nama_ortu" placeholder="Nama Ayah/Ibu" 
                        class="w-full px-3 py-1.5 border border-gray-300 rounded-lg text-xs">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">No. WhatsApp/HP</label>
                    <input type="text" name="no_hp" placeholder="0812..." 
                        class="w-full px-3 py-1.5 border border-gray-300 rounded-lg text-xs">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Jenis Kelamin</label>
                    <select name="jenis_kelamin" class="w-full px-2 py-1.5 border border-gray-300 rounded-lg text-xs bg-white">
                        <option value="Laki-laki">Laki-laki</option>
                        <option value="Perempuan">Perempuan</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Sekolah Asal (SD/MI) *</label>
                    <input type="text" name="sekolah_asal" required placeholder="Contoh: SDN 1 Bandar Mataram" 
                        class="w-full px-3 py-1.5 border border-gray-300 rounded-lg text-xs">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Alamat Domisili</label>
                    <input type="text" name="alamat" placeholder="Desa Bandar Mataram, RT 01 / RW 02" 
                        class="w-full px-3 py-1.5 border border-gray-300 rounded-lg text-xs">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Tahun Ajaran</label>
                    <input type="text" name="tahun" value="<?= htmlspecialchars($pengaturan['tahun_ajaran'] ?? '2025/2026') ?>" 
                        class="w-full px-3 py-1.5 border border-gray-300 rounded-lg text-xs font-semibold text-gray-700 bg-gray-50">
                </div>
            </div>

            <div class="border-t border-gray-100 pt-3">
                <h4 class="text-xs font-bold text-gray-700 uppercase tracking-wide mb-2 flex items-center gap-1.5">
                    <i class="fa-solid fa-list-ol text-blue-600"></i> Penilaian 5 Kriteria (Tabel 3.4 Skripsi):
                </h4>

                <div class="space-y-2.5 bg-slate-50 p-3 rounded-xl border border-slate-100">
                    <div>
                        <label class="block text-[11px] font-semibold text-gray-600 mb-0.5">Penghasilan Orang Tua (C1)</label>
                        <select name="penghasilan" class="w-full px-2 py-1.5 border border-gray-300 rounded-lg text-xs bg-white">
                            <option value="5">&lt; Rp 500.000</option>
                            <option value="4">Rp 600.000 - Rp 1.000.000</option>
                            <option value="3">Rp 1.000.000 - Rp 2.000.000</option>
                            <option value="2">Rp 2.000.000 - Rp 3.000.000</option>
                            <option value="1">&gt; Rp 4.000.000</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-gray-600 mb-0.5">Jumlah Tanggungan Keluarga (C2)</label>
                        <select name="tanggungan" class="w-full px-2 py-1.5 border border-gray-300 rounded-lg text-xs bg-white">
                            <option value="5">&gt; 5 Orang</option>
                            <option value="4">4 Orang</option>
                            <option value="3">3 Orang</option>
                            <option value="2">2 Orang</option>
                            <option value="1">1 Orang</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-gray-600 mb-0.5">Kondisi Tempat Tinggal (C3)</label>
                        <select name="kondisi_rumah" class="w-full px-2 py-1.5 border border-gray-300 rounded-lg text-xs bg-white">
                            <option value="5">Tidak Layak</option>
                            <option value="4">Dinding Kayu</option>
                            <option value="3">Dinding Batu Atap Seng</option>
                            <option value="2">Dinding Batu Atap Genteng</option>
                            <option value="1">Tembok Keramik</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-gray-600 mb-0.5">Prestasi Akademik (C4)</label>
                        <select name="prestasi" class="w-full px-2 py-1.5 border border-gray-300 rounded-lg text-xs bg-white">
                            <option value="5">Juara 1 - 3 Tingkat Kabupaten</option>
                            <option value="4">Juara Harapan</option>
                            <option value="3">Juara Kelas 1 - 3</option>
                            <option value="2">Peringkat 10 Besar</option>
                            <option value="1">Peringkat 20 Besar</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-gray-600 mb-0.5">Jarak Rumah ke Sekolah (C5)</label>
                        <select name="jarak" class="w-full px-2 py-1.5 border border-gray-300 rounded-lg text-xs bg-white">
                            <option value="5">&gt; 5 km</option>
                            <option value="4">3 – 5 km</option>
                            <option value="3">2 km</option>
                            <option value="2">1 km</option>
                            <option value="1">&lt; 1 km</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="flex gap-2 pt-3">
                <button type="submit" class="flex-1 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg text-xs shadow flex items-center justify-center gap-1.5">
                    <i class="fa-solid fa-floppy-disk"></i> Simpan Data Siswa
                </button>
                <button type="button" onclick="closeTambahModal()" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 font-semibold rounded-lg text-xs">
                    Batal
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL EDIT SISWA -->
<div id="modal-edit-siswa" class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden flex items-center justify-center z-50 p-4 transition-all">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6 border border-gray-100 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between pb-3 border-b border-gray-100 mb-4">
            <h3 class="font-bold text-gray-800 text-base flex items-center gap-2">
                <i class="fa-solid fa-user-pen text-amber-500"></i> Edit Data & Status Calon Siswa
            </h3>
            <button type="button" onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600 p-1.5 rounded-lg hover:bg-gray-100"><i class="fa-solid fa-xmark text-lg"></i></button>
        </div>

        <form action="data_calon_penerima.php" method="POST" class="space-y-3" id="form-edit-siswa">
            <input type="hidden" name="action" value="edit_siswa">
            <input type="hidden" name="id_siswa" id="edit-id-siswa">

            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">NISN Siswa *</label>
                <input type="text" name="nisn" id="edit-nisn" required class="w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Nama Lengkap Siswa *</label>
                <input type="text" name="nama" id="edit-nama" required class="w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm">
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Nama Orang Tua</label>
                    <input type="text" name="nama_ortu" id="edit-nama-ortu" class="w-full px-3 py-1.5 border border-gray-300 rounded-lg text-xs">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">No. WhatsApp/HP</label>
                    <input type="text" name="no_hp" id="edit-no-hp" class="w-full px-3 py-1.5 border border-gray-300 rounded-lg text-xs">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Jenis Kelamin</label>
                    <select name="jenis_kelamin" id="edit-jk" class="w-full px-2 py-1.5 border border-gray-300 rounded-lg text-xs bg-white">
                        <option value="Laki-laki">Laki-laki</option>
                        <option value="Perempuan">Perempuan</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Sekolah Asal (SD/MI)</label>
                    <input type="text" name="sekolah_asal" id="edit-sekolah-asal" required class="w-full px-3 py-1.5 border border-gray-300 rounded-lg text-xs">
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Alamat</label>
                <input type="text" name="alamat" id="edit-alamat" class="w-full px-3 py-1.5 border border-gray-300 rounded-lg text-xs">
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Status Verifikasi</label>
                    <select name="status_verifikasi" id="edit-status-v" class="w-full px-2 py-1.5 border border-gray-300 rounded-lg text-xs bg-white font-bold text-gray-700">
                        <option value="Menunggu Verifikasi">Menunggu Verifikasi</option>
                        <option value="Terverifikasi">Terverifikasi</option>
                        <option value="Ditolak">Ditolak</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Tahun Ajaran</label>
                    <input type="text" name="tahun" id="edit-tahun" class="w-full px-3 py-1.5 border border-gray-300 rounded-lg text-xs font-semibold text-gray-700">
                </div>
            </div>

            <hr class="border-gray-100 my-2">

            <div>
                <label class="block text-[11px] font-semibold text-gray-600 mb-0.5">Penghasilan Orang Tua</label>
                <select name="penghasilan" id="edit-c1" class="w-full px-2 py-1.5 border border-gray-300 rounded-lg text-xs bg-white">
                    <option value="5">&lt; Rp 500.000</option>
                    <option value="4">Rp 600.000 - Rp 1.000.000</option>
                    <option value="3">Rp 1.000.000 - Rp 2.000.000</option>
                    <option value="2">Rp 2.000.000 - Rp 3.000.000</option>
                    <option value="1">&gt; Rp 4.000.000</option>
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-semibold text-gray-600 mb-0.5">Jumlah Tanggungan Keluarga</label>
                <select name="tanggungan" id="edit-c2" class="w-full px-2 py-1.5 border border-gray-300 rounded-lg text-xs bg-white">
                    <option value="5">&gt; 5 Orang</option>
                    <option value="4">4 Orang</option>
                    <option value="3">3 Orang</option>
                    <option value="2">2 Orang</option>
                    <option value="1">1 Orang</option>
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-semibold text-gray-600 mb-0.5">Kondisi Tempat Tinggal</label>
                <select name="kondisi_rumah" id="edit-c3" class="w-full px-2 py-1.5 border border-gray-300 rounded-lg text-xs bg-white">
                    <option value="5">Tidak Layak</option>
                    <option value="4">Dinding Kayu</option>
                    <option value="3">Dinding Batu Atap Seng</option>
                    <option value="2">Dinding Batu Atap Genteng</option>
                    <option value="1">Tembok Keramik</option>
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-semibold text-gray-600 mb-0.5">Prestasi Akademik</label>
                <select name="prestasi" id="edit-c4" class="w-full px-2 py-1.5 border border-gray-300 rounded-lg text-xs bg-white">
                    <option value="5">Juara 1 - 3 Tingkat Kabupaten</option>
                    <option value="4">Juara Harapan</option>
                    <option value="3">Juara Kelas 1 - 3</option>
                    <option value="2">Peringkat 10 Besar</option>
                    <option value="1">Peringkat 20 Besar</option>
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-semibold text-gray-600 mb-0.5">Jarak Rumah ke Sekolah</label>
                <select name="jarak" id="edit-c5" class="w-full px-2 py-1.5 border border-gray-300 rounded-lg text-xs bg-white">
                    <option value="5">&gt; 5 km</option>
                    <option value="4">3 – 5 km</option>
                    <option value="3">2 km</option>
                    <option value="2">1 km</option>
                    <option value="1">&lt; 1 km</option>
                </select>
            </div>

            <div class="flex gap-2 pt-3">
                <button type="submit" class="flex-1 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg text-xs shadow">
                    Simpan Perubahan
                </button>
                <button type="button" onclick="closeEditModal()" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 font-semibold rounded-lg text-xs">
                    Batal
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL DETAIL SISWA -->
<div id="modal-detail-siswa" class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden flex items-center justify-center z-50 p-4 transition-all">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-xl p-6 border border-gray-100 max-h-[90vh] overflow-y-auto">
        <!-- Header Modal -->
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
            <div class="flex items-center gap-2.5 text-slate-800">
                <i class="fa-solid fa-id-badge text-lg text-slate-600"></i>
                <div>
                    <h3 class="font-bold text-slate-900 text-base leading-none">Detail Calon Penerima PIP</h3>
                    <p class="text-[11px] text-slate-400 mt-1">Informasi lengkap data identitas, orang tua, dan nilai kriteria AHP</p>
                </div>
            </div>
            <button type="button" onclick="closeDetailModal()" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-100 transition-colors cursor-pointer">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <div class="space-y-4">
            <!-- Profil Singkat Header Banner -->
            <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl flex items-start gap-4">
                <div class="w-12 h-12 rounded-xl bg-slate-900 text-white flex items-center justify-center text-lg font-bold shrink-0 shadow-sm" id="detail-avatar">
                    S
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <h4 class="font-bold text-slate-900 text-base leading-snug" id="detail-nama">-</h4>
                        <span id="detail-status-badge" class="px-2.5 py-0.5 rounded-full text-[10px] font-semibold inline-flex items-center gap-1"></span>
                    </div>
                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1 mt-1 text-xs text-slate-600">
                        <span class="inline-flex items-center gap-1 text-slate-800 font-semibold font-mono">
                            <i class="fa-solid fa-id-card text-[11px] text-slate-400"></i> NISN: <span id="detail-nisn">-</span>
                        </span>
                        <span class="inline-flex items-center gap-1 text-slate-500">
                            <i class="fa-solid fa-venus-mars text-[11px] text-slate-400"></i> <span id="detail-jk">-</span>
                        </span>
                        <span class="inline-flex items-center gap-1 text-slate-700 bg-white px-2 py-0.5 rounded border border-slate-200 text-[10px] font-medium">
                            <i class="fa-solid fa-calendar-check text-[10px] text-slate-400"></i> T.A. <span id="detail-tahun">-</span>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Tab / Informasi Siswa & Sekolah -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 space-y-1">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Sekolah Asal (SD/MI)</p>
                    <p class="font-bold text-slate-800 text-xs truncate" id="detail-sekolah-asal">-</p>
                    <span class="inline-block px-1.5 py-0.5 bg-slate-200 text-slate-700 rounded text-[10px] font-medium">Tingkat Kelas VII</span>
                </div>
                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 space-y-1">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Waktu Registrasi</p>
                    <p class="font-semibold text-slate-700 text-xs flex items-center gap-1.5" id="detail-created-at">
                        <i class="fa-regular fa-clock text-slate-400"></i> -
                    </p>
                    <p class="text-[10px] text-slate-400">Terekam di database</p>
                </div>
            </div>

            <!-- Informasi Orang Tua & Kontak -->
            <div class="p-4 bg-white rounded-xl border border-slate-200 space-y-2.5">
                <h5 class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-1.5 border-b border-slate-100 pb-2">
                    <i class="fa-solid fa-users text-slate-500"></i> Data Orang Tua / Wali & Kontak
                </h5>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                    <div>
                        <span class="text-slate-400 block text-[11px]">Nama Orang Tua / Wali:</span>
                        <span class="font-semibold text-slate-800" id="detail-nama-ortu">-</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[11px]">No. WhatsApp / HP:</span>
                        <div id="detail-kontak-wrapper" class="mt-0.5">
                            <span class="font-semibold text-slate-800" id="detail-no-hp">-</span>
                        </div>
                    </div>
                    <div class="sm:col-span-2">
                        <span class="text-slate-400 block text-[11px]">Alamat Tempat Tinggal:</span>
                        <span class="font-medium text-slate-700" id="detail-alamat">-</span>
                    </div>
                </div>
            </div>

            <!-- Rincian 5 Kriteria Penilaian AHP -->
            <div class="p-4 bg-white rounded-xl border border-slate-200 space-y-3">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                    <h5 class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-1.5">
                        <i class="fa-solid fa-list-check text-slate-500"></i> Penilaian 5 Kriteria AHP (Kondisi Nyata)
                    </h5>
                    <span class="text-[10px] text-slate-400 font-medium">Tabel 3.4 Skripsi</span>
                </div>
                
                <div class="space-y-2 text-xs">
                    <!-- C1 -->
                    <div class="p-2.5 bg-slate-50/70 rounded-lg border border-slate-200 flex items-center justify-between gap-2">
                        <div>
                            <span class="font-bold text-slate-800 block text-[11px]">C1: Penghasilan Orang Tua</span>
                            <span class="text-slate-600 font-medium" id="detail-c1-desc">-</span>
                        </div>
                        <span class="px-2 py-0.5 bg-white border border-slate-200 text-slate-800 font-bold rounded text-[11px] shrink-0" id="detail-c1-badge">Skor -</span>
                    </div>
                    <!-- C2 -->
                    <div class="p-2.5 bg-slate-50/70 rounded-lg border border-slate-200 flex items-center justify-between gap-2">
                        <div>
                            <span class="font-bold text-slate-800 block text-[11px]">C2: Jumlah Tanggungan Keluarga</span>
                            <span class="text-slate-600 font-medium" id="detail-c2-desc">-</span>
                        </div>
                        <span class="px-2 py-0.5 bg-white border border-slate-200 text-slate-800 font-bold rounded text-[11px] shrink-0" id="detail-c2-badge">Skor -</span>
                    </div>
                    <!-- C3 -->
                    <div class="p-2.5 bg-slate-50/70 rounded-lg border border-slate-200 flex items-center justify-between gap-2">
                        <div>
                            <span class="font-bold text-slate-800 block text-[11px]">C3: Kondisi Tempat Tinggal</span>
                            <span class="text-slate-600 font-medium" id="detail-c3-desc">-</span>
                        </div>
                        <span class="px-2 py-0.5 bg-white border border-slate-200 text-slate-800 font-bold rounded text-[11px] shrink-0" id="detail-c3-badge">Skor -</span>
                    </div>
                    <!-- C4 -->
                    <div class="p-2.5 bg-slate-50/70 rounded-lg border border-slate-200 flex items-center justify-between gap-2">
                        <div>
                            <span class="font-bold text-slate-800 block text-[11px]">C4: Prestasi Akademik</span>
                            <span class="text-slate-600 font-medium" id="detail-c4-desc">-</span>
                        </div>
                        <span class="px-2 py-0.5 bg-white border border-slate-200 text-slate-800 font-bold rounded text-[11px] shrink-0" id="detail-c4-badge">Skor -</span>
                    </div>
                    <!-- C5 -->
                    <div class="p-2.5 bg-slate-50/70 rounded-lg border border-slate-200 flex items-center justify-between gap-2">
                        <div>
                            <span class="font-bold text-slate-800 block text-[11px]">C5: Jarak Rumah ke Sekolah</span>
                            <span class="text-slate-600 font-medium" id="detail-c5-desc">-</span>
                        </div>
                        <span class="px-2 py-0.5 bg-white border border-slate-200 text-slate-800 font-bold rounded text-[11px] shrink-0" id="detail-c5-badge">Skor -</span>
                    </div>
                </div>
            </div>

            <!-- Hasil Skor & Ranking AHP -->
            <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200 flex items-center justify-between text-xs">
                <div>
                    <span class="text-slate-400 block text-[10px] font-bold uppercase tracking-wider">Skor Total AHP</span>
                    <span class="font-bold text-slate-900 text-base font-mono" id="detail-skor-ahp">-</span>
                </div>
                <div class="text-right">
                    <span class="text-slate-400 block text-[10px] font-bold uppercase tracking-wider">Peringkat Sistem</span>
                    <span class="font-bold text-slate-800 text-xs" id="detail-ranking-status">-</span>
                </div>
            </div>

            <!-- Tombol Aksi di Modal Detail -->
            <div class="flex flex-wrap items-center justify-between gap-2 pt-3 border-t border-slate-100">
                <div id="detail-verif-actions" class="flex items-center gap-2">
                    <!-- Tombol Setujui / Tolak akan muncul di sini jika status menunggu -->
                </div>
                <div class="flex items-center gap-2 ml-auto">
                    <button type="button" id="btn-detail-edit" class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white font-medium rounded-lg text-xs shadow-sm flex items-center gap-1.5 transition-colors cursor-pointer">
                        <i class="fa-solid fa-pen-to-square text-xs"></i> Edit Data
                    </button>
                    <button type="button" onclick="closeDetailModal()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium rounded-lg text-xs transition-colors cursor-pointer">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    let currentDetailSiswa = null;

    const subkriteriaLabels = {
        c1: {
            5: '< Rp 500.000',
            4: 'Rp 600.000 - Rp 1.000.000',
            3: 'Rp 1.000.000 - Rp 2.000.000',
            2: 'Rp 2.000.000 - Rp 3.000.000',
            1: '> Rp 4.000.000'
        },
        c2: {
            5: '> 5 Orang',
            4: '4 Orang',
            3: '3 Orang',
            2: '2 Orang',
            1: '1 Orang'
        },
        c3: {
            5: 'Tidak Layak Huni',
            4: 'Dinding Kayu / Papan',
            3: 'Dinding Batu Atap Seng',
            2: 'Dinding Batu Atap Genteng',
            1: 'Tembok Keramik'
        },
        c4: {
            5: 'Juara 1 - 3 Tingkat Kabupaten',
            4: 'Juara Harapan',
            3: 'Juara Kelas 1 - 3',
            2: 'Peringkat 10 Besar',
            1: 'Peringkat 20 Besar'
        },
        c5: {
            5: '> 5 km',
            4: '3 – 5 km',
            3: '2 km',
            2: '1 km',
            1: '< 1 km'
        }
    };

    function openDetailModal(siswa) {
        currentDetailSiswa = siswa;

        // Avatar & Nama
        const inisial = siswa.nama ? siswa.nama.trim().charAt(0).toUpperCase() : 'S';
        document.getElementById('detail-avatar').innerText = inisial;
        document.getElementById('detail-nama').innerText = siswa.nama || '-';
        document.getElementById('detail-nisn').innerText = siswa.nisn || '-';
        document.getElementById('detail-jk').innerText = siswa.jenis_kelamin || 'Laki-laki';
        document.getElementById('detail-tahun').innerText = siswa.tahun || '2025/2026';
        document.getElementById('detail-sekolah-asal').innerText = siswa.sekolah_asal || siswa.kelas || '-';

        // Status Verifikasi Badge
        const statusEl = document.getElementById('detail-status-badge');
        const statusV = siswa.status_verifikasi || 'Menunggu Verifikasi';
        if (statusV === 'Terverifikasi') {
            statusEl.className = 'px-2.5 py-0.5 rounded-full text-[10px] font-bold inline-flex items-center gap-1 bg-emerald-100 text-emerald-800';
            statusEl.innerHTML = '<i class="fa-solid fa-circle-check"></i> Terverifikasi';
        } else if (statusV === 'Ditolak') {
            statusEl.className = 'px-2.5 py-0.5 rounded-full text-[10px] font-bold inline-flex items-center gap-1 bg-red-100 text-red-800';
            statusEl.innerHTML = '<i class="fa-solid fa-circle-xmark"></i> Ditolak';
        } else {
            statusEl.className = 'px-2.5 py-0.5 rounded-full text-[10px] font-bold inline-flex items-center gap-1 bg-amber-100 text-amber-800';
            statusEl.innerHTML = '<i class="fa-solid fa-clock"></i> Menunggu Verifikasi';
        }

        // Tanggal
        if (siswa.created_at) {
            try {
                const d = new Date(siswa.created_at);
                document.getElementById('detail-created-at').innerHTML = '<i class="fa-regular fa-clock text-gray-400"></i> ' + d.toLocaleDateString('id-ID', {day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit'});
            } catch(e) {
                document.getElementById('detail-created-at').innerText = siswa.created_at;
            }
        } else {
            document.getElementById('detail-created-at').innerText = '-';
        }

        // Ortu & Kontak
        document.getElementById('detail-nama-ortu').innerText = siswa.nama_ortu || '-';
        document.getElementById('detail-alamat').innerText = siswa.alamat || '-';
        
        const kontakWrapper = document.getElementById('detail-kontak-wrapper');
        if (siswa.no_hp && siswa.no_hp.trim() !== '') {
            const cleanPhone = siswa.no_hp.replace(/[^0-9]/g, '');
            kontakWrapper.innerHTML = `
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="font-semibold text-gray-800">${siswa.no_hp}</span>
                    <a href="https://wa.me/${cleanPhone}" target="_blank" class="inline-flex items-center gap-1 text-[11px] text-emerald-700 hover:text-emerald-800 font-semibold bg-emerald-50 hover:bg-emerald-100 px-2 py-0.5 rounded border border-emerald-200 transition-colors">
                        <i class="fa-brands fa-whatsapp text-emerald-600"></i> Chat WA
                    </a>
                </div>
            `;
        } else {
            kontakWrapper.innerHTML = '<span class="text-gray-400">-</span>';
        }

        // 5 Kriteria AHP
        const c1 = parseInt(siswa.penghasilan) || 1;
        const c2 = parseInt(siswa.tanggungan) || 1;
        const c3 = parseInt(siswa.kondisi_rumah) || 1;
        const c4 = parseInt(siswa.prestasi) || 1;
        const c5 = parseInt(siswa.jarak) || 1;

        document.getElementById('detail-c1-desc').innerText = subkriteriaLabels.c1[c1] || '-';
        document.getElementById('detail-c1-badge').innerText = 'Skor ' + c1;

        document.getElementById('detail-c2-desc').innerText = subkriteriaLabels.c2[c2] || '-';
        document.getElementById('detail-c2-badge').innerText = 'Skor ' + c2;

        document.getElementById('detail-c3-desc').innerText = subkriteriaLabels.c3[c3] || '-';
        document.getElementById('detail-c3-badge').innerText = 'Skor ' + c3;

        document.getElementById('detail-c4-desc').innerText = subkriteriaLabels.c4[c4] || '-';
        document.getElementById('detail-c4-badge').innerText = 'Skor ' + c4;

        document.getElementById('detail-c5-desc').innerText = subkriteriaLabels.c5[c5] || '-';
        document.getElementById('detail-c5-badge').innerText = 'Skor ' + c5;

        // Skor AHP & Ranking
        const totalSkor = parseFloat(siswa.total_skor || 0);
        document.getElementById('detail-skor-ahp').innerText = totalSkor > 0 ? totalSkor.toFixed(4) : '(Belum Dihitung)';
        
        const rankEl = document.getElementById('detail-ranking-status');
        if (siswa.ranking && parseInt(siswa.ranking) > 0) {
            rankEl.innerHTML = `<span class="px-2.5 py-0.5 bg-blue-100 text-blue-800 rounded font-bold">Peringkat #${siswa.ranking}</span>`;
        } else if (statusV === 'Terverifikasi') {
            rankEl.innerHTML = '<span class="text-emerald-600 font-semibold">Masuk Perhitungan AHP</span>';
        } else {
            rankEl.innerHTML = '<span class="text-gray-400">Tidak Memiliki Ranking</span>';
        }

        // Tombol Verifikasi Cepat di modal
        const verifActions = document.getElementById('detail-verif-actions');
        if (statusV === 'Menunggu Verifikasi') {
            verifActions.innerHTML = `
                <a href="data_calon_penerima.php?action=verifikasi_siswa&id=${siswa.id_siswa}" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-lg text-xs shadow inline-flex items-center gap-1 transition-colors">
                    <i class="fa-solid fa-check"></i> Setujui Berkas
                </a>
                <a href="data_calon_penerima.php?action=tolak_siswa&id=${siswa.id_siswa}" onclick="return confirm('Apakah berkas siswa ini ditolak?')" class="px-3 py-1.5 bg-red-100 hover:bg-red-200 text-red-700 font-semibold rounded-lg text-xs inline-flex items-center gap-1 transition-colors">
                    <i class="fa-solid fa-xmark"></i> Tolak
                </a>
            `;
        } else {
            verifActions.innerHTML = '';
        }

        // Action tombol edit dari modal detail
        document.getElementById('btn-detail-edit').onclick = function() {
            closeDetailModal();
            openEditModal(currentDetailSiswa);
        };

        document.getElementById('modal-detail-siswa').classList.remove('hidden');
    }

    function closeDetailModal() {
        const modal = document.getElementById('modal-detail-siswa');
        if (modal) modal.classList.add('hidden');
    }

    function openTambahModal() {
        document.getElementById('modal-tambah-siswa').classList.remove('hidden');
        setTimeout(() => {
            const nisnInput = document.getElementById('tambah-nisn');
            if (nisnInput) nisnInput.focus();
        }, 100);
    }
    function closeTambahModal() {
        document.getElementById('modal-tambah-siswa').classList.add('hidden');
    }

    function openEditModal(siswa) {
        document.getElementById('edit-id-siswa').value = siswa.id_siswa;
        document.getElementById('edit-nisn').value = siswa.nisn;
        document.getElementById('edit-nama').value = siswa.nama;
        document.getElementById('edit-nama-ortu').value = siswa.nama_ortu || '';
        document.getElementById('edit-no-hp').value = siswa.no_hp || '';
        document.getElementById('edit-jk').value = siswa.jenis_kelamin;
        document.getElementById('edit-sekolah-asal').value = siswa.sekolah_asal || siswa.kelas || '';
        document.getElementById('edit-alamat').value = siswa.alamat || '';
        document.getElementById('edit-status-v').value = siswa.status_verifikasi || 'Menunggu Verifikasi';
        document.getElementById('edit-tahun').value = siswa.tahun || '2025/2026';
        document.getElementById('edit-c1').value = siswa.penghasilan;
        document.getElementById('edit-c2').value = siswa.tanggungan;
        document.getElementById('edit-c3').value = siswa.kondisi_rumah;
        document.getElementById('edit-c4').value = siswa.prestasi;
        document.getElementById('edit-c5').value = siswa.jarak;
        document.getElementById('modal-edit-siswa').classList.remove('hidden');
    }
    function closeEditModal() {
        document.getElementById('modal-edit-siswa').classList.add('hidden');
    }

    // Tutup modal jika klik di luar area dialog
    window.addEventListener('click', function(e) {
        const modalTambah = document.getElementById('modal-tambah-siswa');
        const modalEdit = document.getElementById('modal-edit-siswa');
        const modalDetail = document.getElementById('modal-detail-siswa');
        if (e.target === modalTambah) closeTambahModal();
        if (e.target === modalEdit) closeEditModal();
        if (e.target === modalDetail) closeDetailModal();
    });

    // Tutup modal dengan tombol Escape
    window.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeTambahModal();
            closeEditModal();
            closeDetailModal();
        }
    });

    // Otomatis buka modal tambah jika dipanggil via parameter ?tambah=1
    if (new URLSearchParams(window.location.search).get('tambah') === '1') {
        openTambahModal();
    }

    function filterTableCalon() {
        const query = document.getElementById('filter-calon').value.toLowerCase();
        const rows = document.querySelectorAll('#table-calon tbody tr');
        rows.forEach(row => {
            const text = row.innerText.toLowerCase();
            row.style.display = text.includes(query) ? '' : 'none';
        });
    }
</script>

<?php require_once "footer.php"; ?>