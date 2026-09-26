<?php
// profil_sekolah.php - Halaman Pengelolaan Profil Sekolah (Logo, Nama Sekolah, Kepala Sekolah, Tahun Ajaran)
$page_title = "Profil Sekolah";
require_once "header.php";
require_once "sidebar.php";

$today = date('Y-m-d');
$tgl_buka = !empty($pengaturan['tgl_buka_pengajuan']) ? $pengaturan['tgl_buka_pengajuan'] : '2026-09-01';
$tgl_tutup = !empty($pengaturan['tgl_tutup_pengajuan']) ? $pengaturan['tgl_tutup_pengajuan'] : '2026-10-31';

$is_open = ($today >= $tgl_buka && $today <= $tgl_tutup);
$status_pendaftaran_teks = 'Dibuka (Aktif)';
$status_badge_class = 'bg-emerald-100 text-emerald-800 border-emerald-300';

if ($today < $tgl_buka) {
    $status_pendaftaran_teks = 'Belum Dibuka';
    $status_badge_class = 'bg-amber-100 text-amber-800 border-amber-300';
} elseif ($today > $tgl_tutup) {
    $status_pendaftaran_teks = 'Telah Ditutup';
    $status_badge_class = 'bg-rose-100 text-rose-800 border-rose-300';
}

$logo_path = !empty($pengaturan['logo']) && file_exists($pengaturan['logo']) ? $pengaturan['logo'] : 'uploads/logo_default.png';
$logo_kiri_path = !empty($pengaturan['logo_kiri']) && file_exists($pengaturan['logo_kiri']) ? $pengaturan['logo_kiri'] : 'uploads/logo_lampung_tengah.png';
?>

<div class="space-y-6 w-full">
    <!-- FLASH MESSAGE -->
    <?php if (!empty($flash_message)): ?>
        <div class="p-4 rounded-xl text-xs flex items-center justify-between <?= $flash_type === 'success' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-rose-50 text-rose-800 border border-rose-200' ?> shadow-sm">
            <div>
                <span class="font-semibold"><?= htmlspecialchars($flash_message) ?></span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-slate-400 hover:text-slate-600 font-bold text-base leading-none cursor-pointer">
                &times;
            </button>
        </div>
    <?php endif; ?>

    <!-- HEADER HALAMAN -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">
                Profil & Identitas Sekolah
            </h1>
            <p class="text-xs text-slate-500 mt-1">
                Kelola logo resmi sekolah, nama lembaga, nama kepala sekolah penandatangan SK, dan tahun ajaran aktif sistem SPK PIP.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="cetak_laporan.php?kategori=terverifikasi" target="_blank" class="px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-lg text-xs font-semibold shadow-sm transition-colors">
                Pratinjau KOP Surat
            </a>
            <a href="daftar_siswa.php" target="_blank" class="px-3.5 py-2 bg-slate-800 hover:bg-slate-900 text-white rounded-lg text-xs font-semibold shadow-sm transition-colors">
                Pendaftaran Siswa
            </a>
        </div>
    </div>

    <!-- GRID LAYOUT 2 KOLOM -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- KOLOM KIRI: KARTU PRATINJAU IDENTITAS SEKOLAH -->
        <div class="lg:col-span-1 space-y-5">
            <!-- KARTU LOGO RESMI -->
            <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm text-center">
                <div class="inline-flex flex-col items-center">
                    <div class="w-36 h-36 rounded-2xl border-2 border-slate-200 p-2 bg-slate-50/50 shadow-inner flex items-center justify-center relative group overflow-hidden">
                        <img id="preview-logo-display" src="<?= htmlspecialchars($logo_path) ?>" alt="Logo Sekolah" class="max-w-full max-h-full object-contain drop-shadow-sm transition-transform duration-300 group-hover:scale-105">
                    </div>
                    <span class="mt-3 px-3 py-1 bg-slate-100 text-slate-700 border border-slate-200 rounded-full text-[11px] font-bold">
                        Logo Resmi Terpasang
                    </span>
                </div>

                <div class="mt-4 pt-4 border-t border-slate-100 text-left space-y-2.5 text-xs">
                    <div class="flex items-start justify-between">
                        <span class="text-slate-400">Nama Lembaga:</span>
                        <strong class="text-slate-800 text-right font-bold"><?= htmlspecialchars($pengaturan['nama_sekolah']) ?></strong>
                    </div>
                    <div class="flex items-start justify-between">
                        <span class="text-slate-400">Kepala Sekolah:</span>
                        <span class="text-slate-700 text-right font-medium"><?= htmlspecialchars($pengaturan['kepala_sekolah']) ?></span>
                    </div>
                    <div class="flex items-start justify-between">
                        <span class="text-slate-400">NIP Kepala Sekolah:</span>
                        <span class="text-slate-700 text-right font-medium"><?= htmlspecialchars($pengaturan['nip_kepala_sekolah'] ?? '-') ?></span>
                    </div>
                    <div class="flex items-start justify-between">
                        <span class="text-slate-400">Tahun Ajaran:</span>
                        <span class="text-slate-800 text-right font-bold bg-slate-100 px-2 py-0.5 rounded border border-slate-200"><?= htmlspecialchars($pengaturan['tahun_ajaran']) ?></span>
                    </div>
                    <div class="flex items-start justify-between">
                        <span class="text-slate-400">Kuota Resmi PIP:</span>
                        <span class="text-slate-900 text-right font-bold bg-blue-50 text-blue-700 px-2 py-0.5 rounded border border-blue-200"><?= (int)$pengaturan['kuota_pip'] ?> Siswa</span>
                    </div>
                </div>
            </div>

            <!-- PRATINJAU KOP SURAT RESMI -->
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm space-y-3">
                <div class="font-bold text-slate-800 text-xs pb-2 border-b border-slate-100">
                    <span>Tampilan Pada Dokumen SK Resmi:</span>
                </div>
                <!-- Miniatur Kop Surat -->
                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 text-[10px] space-y-1 font-serif text-slate-800">
                    <div class="flex items-center justify-between gap-1.5 pb-2 border-b-2 border-slate-800">
                        <img src="<?= htmlspecialchars($logo_kiri_path) ?>" alt="Logo Daerah" class="w-8 h-9 object-contain shrink-0" title="Logo Kab. Lampung Tengah">
                        <div class="min-w-0 text-center leading-tight flex-1">
                            <div class="font-bold uppercase text-[8px] text-slate-800"><?= htmlspecialchars($pengaturan['nama_yayasan'] ?? 'YAYASAN AL QODIRI LAMPUNG') ?></div>
                            <div class="font-bold uppercase text-[10px] text-slate-900"><?= htmlspecialchars($pengaturan['nama_sekolah'] ?? 'SMP TUNAS BANGSA') ?></div>
                            <div class="font-bold uppercase text-[8px] text-slate-800"><?= htmlspecialchars($pengaturan['sub_instansi'] ?? 'BANDAR MATARAM LAMPUNG TENGAH') ?></div>
                            <div class="text-[7px] text-slate-600 italic mt-0.5 leading-tight"><?= htmlspecialchars($pengaturan['alamat_sekolah'] ?? 'Jln. Lapangan Merdeka Raman Agung Mataram Udik Kec. Bandar mataram Lampung Tengah') ?></div>
                        </div>
                        <img src="<?= htmlspecialchars($logo_path) ?>" alt="Logo Sekolah" class="w-8 h-9 object-contain shrink-0" title="Logo SMP Tunas Bangsa">
                    </div>
                    <div class="pt-2 text-center text-[9px] text-slate-600">
                        Tertanda Kepala Sekolah:<br>
                        <b class="text-slate-900 underline block mt-3"><?= htmlspecialchars($pengaturan['kepala_sekolah']) ?></b>
                        <span class="text-[8px] text-slate-500 block mt-0.5">NIP. <?= htmlspecialchars($pengaturan['nip_kepala_sekolah'] ?? '-') ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- KOLOM KANAN: FORMULIR PENGATURAN PROFIL SEKOLAH -->
        <div class="lg:col-span-2">
            <form id="form-profil" action="profil_sekolah.php" method="POST" enctype="multipart/form-data" class="bg-white p-6 sm:p-8 rounded-2xl border border-slate-200 shadow-sm space-y-6">
                <input type="hidden" name="action_profile" value="update_profil_sekolah">

                <!-- HEADER FORM: KETERANGAN STATUS MODE -->
                <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                    <div>
                        <h2 class="text-slate-900 font-bold text-sm">Formulir Data Profil Sekolah</h2>
                        <p class="text-[11px] text-slate-500 mt-0.5">Informasi resmi identitas sekolah, kepala sekolah, dan kuota PIP.</p>
                    </div>
                    <span id="status-mode" class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200">
                        Mode Terkunci (Pratinjau)
                    </span>
                </div>

                <!-- BAGIAN 1: UPLOAD / GANTI LOGO -->
                <div class="space-y-3 pb-6 border-b border-slate-100">
                    <h2 class="text-slate-900 font-bold text-sm">Logo Resmi Sekolah</h2>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-center pt-1">
                        <div class="sm:col-span-1 flex flex-col items-center justify-center p-3 bg-slate-50 rounded-xl border border-dashed border-slate-300 text-center">
                            <div class="w-20 h-20 flex items-center justify-center bg-white rounded-lg border border-slate-200 p-1 mb-2">
                                <img id="live-preview" src="<?= htmlspecialchars($logo_path) ?>" alt="Live Preview" class="max-w-full max-h-full object-contain">
                            </div>
                            <span class="text-[10px] text-slate-400 font-medium">Pratinjau Logo</span>
                        </div>

                        <div id="wrapper-upload-logo" class="sm:col-span-2 space-y-3 opacity-50 pointer-events-none transition-opacity">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Unggah Logo Baru</label>
                                <input type="file" name="logo" id="input-logo" accept="image/png,image/jpeg,image/jpg,image/webp" disabled
                                    class="block w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-slate-900 file:text-white hover:file:bg-slate-800 file:cursor-pointer border border-slate-200 rounded-xl p-1 bg-slate-50 focus:outline-none">
                                <p class="text-[11px] text-slate-400 mt-1">
                                    Mendukung format <b>PNG</b> (transparan disarankan), <b>JPG</b>, atau <b>WEBP</b>. Ukuran maksimal 5 MB.
                                </p>
                            </div>

                            <div class="flex items-center gap-2 pt-1">
                                <label class="inline-flex items-center gap-2 text-xs text-slate-600 cursor-pointer">
                                    <input type="checkbox" name="reset_logo" value="1" id="check-reset-logo" disabled class="w-4 h-4 rounded border-slate-300 text-slate-900 focus:ring-slate-800">
                                    <span>Reset ke Logo Standar Sistem</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- BAGIAN 2: IDENTITAS LEMBAGA SEKOLAH -->
                <div class="space-y-4 pb-6 border-b border-slate-100">
                    <h2 class="text-slate-900 font-bold text-sm">Identitas Lembaga & Kepala Sekolah</h2>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Nama Yayasan -->
                        <div class="sm:col-span-1">
                            <label class="block text-xs font-semibold text-slate-700 mb-1">
                                Nama Yayasan Penyelenggara <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" name="nama_yayasan" required disabled value="<?= htmlspecialchars($pengaturan['nama_yayasan'] ?? 'YAYASAN AL QODIRI LAMPUNG') ?>" 
                                placeholder="Contoh: YAYASAN AL QODIRI LAMPUNG"
                                class="profil-input w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-xs font-bold text-slate-800 bg-slate-50/70 cursor-not-allowed transition-all">
                        </div>

                        <!-- Nama Sekolah -->
                        <div class="sm:col-span-1">
                            <label class="block text-xs font-semibold text-slate-700 mb-1">
                                Nama Resmi Sekolah <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" name="nama_sekolah" required disabled value="<?= htmlspecialchars($pengaturan['nama_sekolah'] ?? 'SMP TUNAS BANGSA') ?>" 
                                placeholder="Contoh: SMP TUNAS BANGSA"
                                class="profil-input w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-xs font-bold text-slate-800 bg-slate-50/70 cursor-not-allowed transition-all">
                        </div>

                        <!-- Sub Instansi / Wilayah -->
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-slate-700 mb-1">
                                Wilayah / Sub-Instansi Kop Surat <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" name="sub_instansi" required disabled value="<?= htmlspecialchars($pengaturan['sub_instansi'] ?? 'BANDAR MATARAM LAMPUNG TENGAH') ?>" 
                                placeholder="Contoh: BANDAR MATARAM LAMPUNG TENGAH"
                                class="profil-input w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-xs font-bold text-slate-800 bg-slate-50/70 cursor-not-allowed transition-all">
                        </div>

                        <!-- Alamat Lengkap Sekolah -->
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-slate-700 mb-1">
                                Alamat Lengkap Lembaga Sekolah <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" name="alamat_sekolah" required disabled value="<?= htmlspecialchars($pengaturan['alamat_sekolah'] ?? 'Jln. Lapangan Merdeka Raman Agung Mataram Udik Kec. Bandar mataram Lampung Tengah') ?>" 
                                placeholder="Contoh: Jln. Lapangan Merdeka Raman Agung Mataram Udik Kec. Bandar mataram Lampung Tengah"
                                class="profil-input w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-xs font-medium text-slate-800 bg-slate-50/70 cursor-not-allowed transition-all">
                        </div>

                        <!-- Kepala Sekolah -->
                        <div class="sm:col-span-1">
                            <label class="block text-xs font-semibold text-slate-700 mb-1">
                                Nama Kepala Sekolah (Tanda Tangan SK) <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" name="kepala_sekolah" required disabled value="<?= htmlspecialchars($pengaturan['kepala_sekolah']) ?>" 
                                placeholder="Contoh: Fitri Wiyatni, S.Pd.I"
                                class="profil-input w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-xs font-bold text-slate-800 bg-slate-50/70 cursor-not-allowed transition-all">
                            <span class="text-[10px] text-slate-400 mt-1 block">Nama lengkap beserta gelar akademik.</span>
                        </div>

                        <!-- NIP Kepala Sekolah -->
                        <div class="sm:col-span-1">
                            <label class="block text-xs font-semibold text-slate-700 mb-1">
                                NIP Kepala Sekolah
                            </label>
                            <input type="text" name="nip_kepala_sekolah" disabled value="<?= htmlspecialchars($pengaturan['nip_kepala_sekolah'] ?? '-') ?>" 
                                placeholder="Contoh: 19750812 atau (-)"
                                class="profil-input w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-xs font-bold text-slate-800 bg-slate-50/70 cursor-not-allowed transition-all">
                            <span class="text-[10px] text-slate-400 mt-1 block">Nomor Induk Pegawai</span>
                        </div>
                    </div>
                </div>

                <!-- BAGIAN 3: TAHUN AJARAN & KUOTA PIP -->
                <div class="space-y-4 pb-6 border-b border-slate-100">
                    <h2 class="text-slate-900 font-bold text-sm">Periode Tahun Ajaran & Kuota PIP</h2>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">
                                Tahun Ajaran Aktif <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" name="tahun_ajaran" required disabled value="<?= htmlspecialchars($pengaturan['tahun_ajaran']) ?>" 
                                placeholder="Contoh: 2025/2026"
                                class="profil-input w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-xs font-bold text-slate-800 bg-slate-50/70 cursor-not-allowed transition-all">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">
                                Kuota Resmi Penerima PIP <span class="text-rose-500">*</span>
                            </label>
                            <input type="number" min="1" name="kuota_pip" required disabled value="<?= (int)$pengaturan['kuota_pip'] ?>" 
                                placeholder="Contoh: 27"
                                class="profil-input w-full px-3.5 py-2.5 border border-slate-200 rounded-xl text-xs font-bold text-slate-800 bg-slate-50/70 cursor-not-allowed transition-all">
                        </div>
                    </div>
                </div>

                <!-- BAGIAN 4: JADWAL PENDAFTARAN PIP MANDIRI -->
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <h2 class="text-slate-900 font-bold text-sm">Jadwal Pendaftaran PIP Mandiri</h2>
                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold border <?= $status_badge_class ?>">
                            <?= $status_pendaftaran_teks ?>
                        </span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">
                                Tanggal Buka Pendaftaran
                            </label>
                            <input type="date" name="tgl_buka_pengajuan" required disabled value="<?= htmlspecialchars($pengaturan['tgl_buka_pengajuan'] ?? '2026-09-01') ?>" 
                                class="profil-input w-full px-3 py-2.5 border border-slate-200 rounded-xl text-xs font-bold text-slate-800 bg-slate-50/70 cursor-not-allowed transition-all">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">
                                Tanggal Tutup Pendaftaran
                            </label>
                            <input type="date" name="tgl_tutup_pengajuan" required disabled value="<?= htmlspecialchars($pengaturan['tgl_tutup_pengajuan'] ?? '2026-10-31') ?>" 
                                class="profil-input w-full px-3 py-2.5 border border-slate-200 rounded-xl text-xs font-bold text-slate-800 bg-slate-50/70 cursor-not-allowed transition-all">
                        </div>
                    </div>
                </div>

                <!-- AREA TOMBOL AKSI POJOK KANAN BAWAH -->
                <!-- 1. Kondisi Default: Mode Terkunci (Hanya Tombol Edit) -->
                <div id="btn-view-mode" class="pt-4 border-t border-slate-100 flex items-center justify-end">
                    <button type="button" onclick="aktifkanModeEdit()" class="px-6 py-2.5 bg-slate-900 hover:bg-slate-800 text-white font-bold rounded-xl text-xs shadow-md transition-all cursor-pointer">
                        Edit Profil Sekolah
                    </button>
                </div>

                <!-- 2. Kondisi Saat Tombol Edit Diklik: Mode Edit (Tombol Batal & Simpan) -->
                <div id="btn-edit-mode" class="hidden pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                    <button type="button" onclick="batalkanModeEdit()" class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-xl text-xs transition-colors cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" class="px-6 py-2.5 bg-slate-900 hover:bg-slate-800 text-white font-bold rounded-xl text-xs shadow-md transition-all cursor-pointer">
                        Simpan Perubahan Profil Sekolah
                    </button>
                </div>
            </form>
        </div>

    </div>
</div>

<script>
    const formProfil = document.getElementById('form-profil');
    const profilInputs = document.querySelectorAll('.profil-input');
    const wrapperUpload = document.getElementById('wrapper-upload-logo');
    const inputLogo = document.getElementById('input-logo');
    const checkReset = document.getElementById('check-reset-logo');
    const livePreview = document.getElementById('live-preview');
    const btnViewMode = document.getElementById('btn-view-mode');
    const btnEditMode = document.getElementById('btn-edit-mode');
    const statusMode = document.getElementById('status-mode');
    const originalLogoUrl = <?= json_encode($logo_path) ?>;
    const defaultLogoUrl = 'uploads/logo_default.png';

    function aktifkanModeEdit() {
        profilInputs.forEach(input => {
            input.disabled = false;
            input.classList.remove('bg-slate-50/70', 'cursor-not-allowed', 'border-slate-200');
            input.classList.add('bg-white', 'border-slate-300', 'focus:ring-2', 'focus:ring-slate-800');
        });

        if (wrapperUpload) {
            wrapperUpload.classList.remove('opacity-50', 'pointer-events-none');
        }
        if (inputLogo) inputLogo.disabled = false;
        if (checkReset) checkReset.disabled = false;

        if (btnViewMode) btnViewMode.classList.add('hidden');
        if (btnEditMode) btnEditMode.classList.remove('hidden');

        if (statusMode) {
            statusMode.textContent = 'Mode Pengeditan Aktif';
            statusMode.className = 'px-2.5 py-1 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800 border border-blue-300';
        }

        const firstInput = document.querySelector('input[name="nama_yayasan"]');
        if (firstInput) firstInput.focus();
    }

    function batalkanModeEdit() {
        if (formProfil) formProfil.reset();

        profilInputs.forEach(input => {
            input.disabled = true;
            input.classList.add('bg-slate-50/70', 'cursor-not-allowed', 'border-slate-200');
            input.classList.remove('bg-white', 'border-slate-300', 'focus:ring-2', 'focus:ring-slate-800');
        });

        if (wrapperUpload) {
            wrapperUpload.classList.add('opacity-50', 'pointer-events-none');
        }
        if (inputLogo) {
            inputLogo.value = '';
            inputLogo.disabled = true;
        }
        if (checkReset) {
            checkReset.checked = false;
            checkReset.disabled = true;
        }

        if (livePreview) livePreview.src = originalLogoUrl;

        if (btnEditMode) btnEditMode.classList.add('hidden');
        if (btnViewMode) btnViewMode.classList.remove('hidden');

        if (statusMode) {
            statusMode.textContent = 'Mode Terkunci (Pratinjau)';
            statusMode.className = 'px-2.5 py-1 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200';
        }
    }

    // Pastikan saat submit semua input aktif agar terkirim ke server via POST
    if (formProfil) {
        formProfil.addEventListener('submit', function() {
            profilInputs.forEach(input => input.disabled = false);
            if (inputLogo) inputLogo.disabled = false;
            if (checkReset) checkReset.disabled = false;
        });
    }

    // Live image preview script
    if (inputLogo) {
        inputLogo.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(evt) {
                    if (livePreview) livePreview.src = evt.target.result;
                    if (checkReset) checkReset.checked = false;
                }
                reader.readAsDataURL(file);
            }
        });
    }

    if (checkReset) {
        checkReset.addEventListener('change', function() {
            if (this.checked) {
                if (inputLogo) inputLogo.value = '';
                if (livePreview) livePreview.src = defaultLogoUrl;
            } else {
                if (livePreview) livePreview.src = originalLogoUrl;
            }
        });
    }
</script>

<?php
require_once "footer.php";
?>

