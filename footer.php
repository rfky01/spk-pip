<?php
// footer.php - Penutup Halaman, Modal Kelola Akun & Pengaturan Sistem, dan Script JavaScript
?>
        </div> <!-- Penutup padding konten p-8 -->
    </main>
</div> <!-- Penutup min-h-screen flex -->

<!-- ========================================== -->
<!-- MODAL KELOLA AKUN & PENGATURAN SISTEM      -->
<!-- ========================================== -->
<div id="modal-profile" class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-xl p-6 border border-slate-200 max-h-[90vh] overflow-y-auto">
        
        <!-- HEADER MODAL -->
        <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-5">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-slate-900 text-white flex items-center justify-center text-sm shadow-sm">
                    <i class="fa-solid fa-sliders"></i>
                </div>
                <div>
                    <h3 class="font-bold text-slate-900 text-base leading-snug">Kelola Akun & Pengaturan Sistem</h3>
                    <p class="text-xs text-slate-400">Atur profil sekolah, jadwal pendaftaran PIP, dan kredensial akun admin</p>
                </div>
            </div>
            <button onclick="closeProfileModal()" type="button" class="w-8 h-8 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 flex items-center justify-center transition-colors">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <!-- FORM PENGATURAN -->
        <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" method="POST" enctype="multipart/form-data" class="space-y-5">
            <input type="hidden" name="action_profile" value="update_profile">
            
            <!-- SEKSI 1: PROFIL & IDENTITAS SEKOLAH -->
            <div class="bg-slate-50/80 p-4 rounded-xl border border-slate-200 space-y-3.5">
                <div class="flex items-center justify-between pb-2 border-b border-slate-200/80">
                    <span class="text-slate-800 font-bold text-xs uppercase tracking-wider flex items-center gap-2">
                        <i class="fa-solid fa-school text-slate-600 text-sm"></i> Profil & Identitas Sekolah
                    </span>
                    <a href="profil_sekolah.php" class="text-[11px] text-blue-600 hover:text-blue-800 hover:underline font-semibold inline-flex items-center gap-1">
                        Menu Lengkap <i class="fa-solid fa-arrow-right text-[10px]"></i>
                    </a>
                </div>

                <!-- Nama Resmi Sekolah -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Resmi Sekolah</label>
                    <input type="text" name="nama_sekolah" required value="<?= htmlspecialchars($pengaturan['nama_sekolah'] ?? 'SMP Tunas Bangsa') ?>" 
                        placeholder="Contoh: SMP Tunas Bangsa"
                        class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs font-bold text-slate-900 bg-white focus:outline-none focus:ring-2 focus:ring-slate-800 focus:border-slate-800 transition-all">
                </div>

                <!-- Logo Sekolah -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Logo Sekolah</label>
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-xl border border-slate-200 bg-white p-1.5 flex items-center justify-center shrink-0 shadow-sm">
                            <img src="<?= !empty($pengaturan['logo']) && file_exists($pengaturan['logo']) ? htmlspecialchars($pengaturan['logo']) : 'uploads/logo_default.png' ?>" alt="Logo" class="max-w-full max-h-full object-contain">
                        </div>
                        <div class="flex-1">
                            <input type="file" name="logo" accept="image/png,image/jpeg,image/jpg,image/webp" 
                                class="w-full text-xs text-slate-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-slate-900 file:text-white hover:file:bg-slate-800 file:cursor-pointer border border-slate-200 rounded-lg p-1 bg-white focus:outline-none">
                            <span class="text-[10px] text-slate-400 mt-1 block">Format gambar: PNG, JPG, JPEG, atau WEBP (Maksimal 5 MB).</span>
                        </div>
                    </div>
                </div>

                <!-- Kepala Sekolah & NIP (Grid 2 Kolom Sejajar) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Kepala Sekolah (Tanda Tangan SK)</label>
                        <input type="text" name="kepala_sekolah" required value="<?= htmlspecialchars($pengaturan['kepala_sekolah'] ?? 'Fitri Wiyatni, S.Pd.I') ?>" 
                            placeholder="Contoh: Fitri Wiyatni, S.Pd.I"
                            class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs font-bold text-slate-900 bg-white focus:outline-none focus:ring-2 focus:ring-slate-800 focus:border-slate-800 transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">NIP Kepala Sekolah</label>
                        <input type="text" name="nip_kepala_sekolah" value="<?= htmlspecialchars($pengaturan['nip_kepala_sekolah'] ?? '-') ?>" 
                            placeholder="Contoh: 19750812 200501 2 004 atau -"
                            class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs font-bold text-slate-900 bg-white focus:outline-none focus:ring-2 focus:ring-slate-800 focus:border-slate-800 transition-all">
                    </div>
                </div>

                <!-- Tahun Ajaran Aktif Pendaftaran -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Tahun Ajaran Aktif Pendaftaran</label>
                    <input type="text" name="tahun_ajaran" required value="<?= htmlspecialchars($pengaturan['tahun_ajaran'] ?? '2025/2026') ?>" 
                        placeholder="Contoh: 2025/2026"
                        class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs font-bold text-slate-900 bg-white focus:outline-none focus:ring-2 focus:ring-slate-800 focus:border-slate-800 transition-all">
                </div>
            </div>

            <!-- SEKSI 2: JADWAL PENDAFTARAN PIP -->
            <div class="bg-slate-50/80 p-4 rounded-xl border border-slate-200 space-y-3">
                <div class="flex items-center gap-2 pb-2 border-b border-slate-200/80">
                    <span class="text-slate-800 font-bold text-xs uppercase tracking-wider flex items-center gap-2">
                        <i class="fa-solid fa-calendar-check text-slate-600 text-sm"></i> Pengaturan Jadwal Pendaftaran PIP
                    </span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">
                            <i class="fa-regular fa-calendar-plus text-emerald-600 mr-1"></i> Tanggal Buka
                        </label>
                        <input type="date" name="tgl_buka_pengajuan" required value="<?= htmlspecialchars($pengaturan['tgl_buka_pengajuan'] ?? '2026-09-01') ?>" 
                            class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs font-bold text-slate-900 bg-white focus:outline-none focus:ring-2 focus:ring-slate-800 focus:border-slate-800 transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">
                            <i class="fa-regular fa-calendar-xmark text-rose-600 mr-1"></i> Tanggal Tutup
                        </label>
                        <input type="date" name="tgl_tutup_pengajuan" required value="<?= htmlspecialchars($pengaturan['tgl_tutup_pengajuan'] ?? '2026-10-31') ?>" 
                            class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs font-bold text-slate-900 bg-white focus:outline-none focus:ring-2 focus:ring-slate-800 focus:border-slate-800 transition-all">
                    </div>
                </div>
                <p class="text-[11px] text-slate-500 leading-normal">
                    <i class="fa-solid fa-circle-info text-blue-500 mr-1"></i> Formulir pendaftaran publik hanya dapat diakses & diajukan oleh orang tua pada rentang tanggal di atas. Di luar tanggal ini, formulir otomatis terkunci.
                </p>
            </div>

            <!-- SEKSI 3: AKUN LOGIN GURU / ADMIN -->
            <div class="bg-slate-50/80 p-4 rounded-xl border border-slate-200 space-y-3">
                <div class="flex items-center gap-2 pb-2 border-b border-slate-200/80">
                    <span class="text-slate-800 font-bold text-xs uppercase tracking-wider flex items-center gap-2">
                        <i class="fa-solid fa-user-shield text-slate-600 text-sm"></i> Akun Login Guru / Admin
                    </span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Lengkap Admin</label>
                        <input type="text" name="nama_admin" required value="<?= htmlspecialchars($_SESSION['admin']['nama'] ?? '') ?>" 
                            class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs font-medium text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-slate-800 focus:border-slate-800 transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Username (Tetap)</label>
                        <input type="text" disabled value="<?= htmlspecialchars($_SESSION['admin']['username'] ?? '') ?>" 
                            class="w-full px-3 py-2 bg-slate-100 border border-slate-200 rounded-lg text-xs text-slate-500 cursor-not-allowed">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Ganti Password Baru</label>
                    <input type="password" name="password_baru" placeholder="Kosongkan jika tidak ingin mengubah password" 
                        class="w-full px-3 py-2 border border-slate-300 rounded-lg text-xs text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-slate-800 focus:border-slate-800 transition-all">
                    <span class="text-[10px] text-slate-400 mt-1 block">Minimal 6 karakter jika ingin memperbarui kata sandi akun sekolah.</span>
                </div>
            </div>

            <!-- FOOTER TOMBOL AKSI -->
            <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-100">
                <button type="button" onclick="closeProfileModal()" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-xl text-xs transition-colors">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2.5 bg-slate-900 hover:bg-slate-800 text-white font-semibold rounded-xl text-xs shadow-md hover:shadow transition-all flex items-center gap-2">
                    <i class="fa-solid fa-check text-xs"></i> Simpan Pengaturan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openProfileModal() {
        const modal = document.getElementById('modal-profile');
        if (modal) {
            modal.classList.remove('hidden');
        }
    }
    function closeProfileModal() {
        const modal = document.getElementById('modal-profile');
        if (modal) {
            modal.classList.add('hidden');
        }
    }
    // Tutup saat backdrop diklik
    document.getElementById('modal-profile')?.addEventListener('click', function(e) {
        if (e.target === this) {
            closeProfileModal();
        }
    });
    // Tutup saat menekan tombol Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeProfileModal();
        }
    });
</script>

</body>
</html>
