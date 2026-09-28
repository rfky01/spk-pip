<?php
// footer.php - Penutup Halaman, Modal Kelola Akun & Pengaturan Sistem, dan Script JavaScript
?>
        </div> <!-- Penutup padding konten p-8 -->
    </main>
</div> <!-- Penutup min-h-screen flex -->

<!-- ========================================== -->
<!-- MODAL KELOLA AKUN LOGIN GURU / ADMIN       -->
<!-- ========================================== -->
<div id="modal-profile" class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden flex items-center justify-center z-50 p-4">
    <div class="bg-[#112240] rounded-2xl shadow-2xl w-full max-w-lg p-6 border border-[#1E3A5F] max-h-[90vh] overflow-y-auto text-white">
        
        <!-- HEADER MODAL -->
        <div class="flex items-center justify-between pb-4 border-b border-[#1E3A5F] mb-5">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-[#1E3A5F] text-blue-400 border border-[#274872] flex items-center justify-center text-sm shadow-sm">
                    <i class="fa-solid fa-user-shield"></i>
                </div>
                <div>
                    <h3 class="font-bold text-white text-base leading-snug">Akun Login Guru / Admin</h3>
                    <p class="text-xs text-slate-400">Perbarui nama lengkap admin dan kata sandi login sistem</p>
                </div>
            </div>
            <button onclick="closeProfileModal()" type="button" class="w-8 h-8 rounded-lg text-slate-400 hover:text-white hover:bg-[#1E3A5F] flex items-center justify-center transition-colors">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <!-- FORM PENGATURAN AKUN -->
        <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" method="POST" class="space-y-4">
            <input type="hidden" name="action_profile" value="update_akun">
            
            <div class="bg-[#0B192C] p-4 rounded-xl border border-[#1E3A5F] space-y-3.5">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Nama Lengkap Admin</label>
                        <input type="text" name="nama_admin" required value="<?= htmlspecialchars($_SESSION['admin']['nama'] ?? '') ?>" 
                            class="w-full px-3 py-2 border border-[#1E3A5F] rounded-lg text-xs font-medium text-white bg-[#07101E] focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Username (Tetap)</label>
                        <input type="text" disabled value="<?= htmlspecialchars($_SESSION['admin']['username'] ?? '') ?>" 
                            class="w-full px-3 py-2 bg-[#07101E] border border-[#1E3A5F] rounded-lg text-xs text-slate-500 cursor-not-allowed">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Ganti Password Baru</label>
                    <input type="password" name="password_baru" placeholder="Kosongkan jika tidak ingin mengubah password" 
                        class="w-full px-3 py-2 border border-[#1E3A5F] rounded-lg text-xs text-white bg-[#07101E] focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                    <span class="text-[10px] text-slate-400 mt-1 block">Minimal 6 karakter jika ingin memperbarui kata sandi akun sekolah.</span>
                </div>
            </div>

            <!-- FOOTER TOMBOL AKSI -->
            <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-[#1E3A5F]">
                <button type="button" onclick="closeProfileModal()" class="px-4 py-2 bg-[#112240] hover:bg-[#162B4D] text-slate-300 border border-[#1E3A5F] hover:border-[#2E5A8F] font-semibold rounded-xl text-xs transition-all duration-200 hover:scale-[1.02] active:scale-[0.98] cursor-pointer">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2 bg-[#162B4D] hover:bg-[#1E3A5F] text-white border border-[#2E5A8F] hover:border-blue-400 font-semibold rounded-xl text-xs shadow-sm transition-all duration-200 hover:scale-[1.02] active:scale-[0.98] flex items-center gap-2 cursor-pointer">
                    <i class="fa-solid fa-check text-blue-400 text-xs"></i> Simpan Akun
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL PENGATURAN JADWAL PENDAFTARAN PIP   -->
<!-- ========================================== -->
<div id="modal-jadwal" class="fixed inset-0 bg-black/50 backdrop-blur-sm hidden flex items-center justify-center z-50 p-4">
    <div class="bg-[#112240] rounded-2xl shadow-2xl w-full max-w-lg p-6 border border-[#1E3A5F] max-h-[90vh] overflow-y-auto text-white">
        
        <!-- HEADER MODAL -->
        <div class="flex items-center justify-between pb-4 border-b border-[#1E3A5F] mb-5">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-[#1E3A5F] text-blue-400 border border-[#274872] flex items-center justify-center text-sm shadow-sm">
                    <i class="fa-solid fa-calendar-days"></i>
                </div>
                <div>
                    <h3 class="font-bold text-white text-base leading-snug">Pengaturan Jadwal Pendaftaran PIP</h3>
                    <p class="text-xs text-slate-400">Atur periode tanggal buka dan tutup pendaftaran mandiri</p>
                </div>
            </div>
            <button onclick="closeJadwalModal()" type="button" class="w-8 h-8 rounded-lg text-slate-400 hover:text-white hover:bg-[#1E3A5F] flex items-center justify-center transition-colors">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <!-- FORM JADWAL -->
        <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" method="POST" class="space-y-5">
            <input type="hidden" name="action_profile" value="update_jadwal">
            
            <div class="bg-[#0B192C] p-4 rounded-xl border border-[#1E3A5F] space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5 flex items-center gap-1.5">
                            <i class="fa-regular fa-calendar-plus text-emerald-400"></i> Tanggal Buka
                        </label>
                        <input type="date" name="tgl_buka_pengajuan" required value="<?= htmlspecialchars($pengaturan['tgl_buka_pengajuan'] ?? '2026-09-01') ?>" 
                            class="w-full px-3 py-2.5 border border-[#1E3A5F] rounded-lg text-xs font-bold text-white bg-[#07101E] focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all cursor-pointer">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5 flex items-center gap-1.5">
                            <i class="fa-regular fa-calendar-xmark text-rose-400"></i> Tanggal Tutup
                        </label>
                        <input type="date" name="tgl_tutup_pengajuan" required value="<?= htmlspecialchars($pengaturan['tgl_tutup_pengajuan'] ?? '2026-10-31') ?>" 
                            class="w-full px-3 py-2.5 border border-[#1E3A5F] rounded-lg text-xs font-bold text-white bg-[#07101E] focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all cursor-pointer">
                    </div>
                </div>

                <div class="p-3 bg-[#112240]/80 rounded-lg border border-[#1E3A5F] flex items-start gap-2.5">
                    <i class="fa-solid fa-circle-info text-blue-400 text-xs mt-0.5 shrink-0"></i>
                    <p class="text-[11px] text-slate-400 leading-relaxed">
                        Formulir pendaftaran publik hanya dapat diakses &amp; diajukan oleh orang tua pada rentang tanggal di atas. Di luar tanggal ini, formulir otomatis terkunci.
                    </p>
                </div>
            </div>

            <!-- FOOTER TOMBOL AKSI -->
            <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-[#1E3A5F]">
                <button type="button" onclick="closeJadwalModal()" class="px-4 py-2.5 bg-[#112240] hover:bg-[#162B4D] text-slate-300 border border-[#1E3A5F] hover:border-[#2E5A8F] font-semibold rounded-xl text-xs transition-all duration-200 hover:scale-[1.02] active:scale-[0.98] cursor-pointer">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2.5 bg-[#162B4D] hover:bg-[#1E3A5F] text-white border border-[#2E5A8F] hover:border-blue-400 font-semibold rounded-xl text-xs shadow-sm transition-all duration-200 hover:scale-[1.02] active:scale-[0.98] flex items-center gap-2 cursor-pointer">
                    <i class="fa-solid fa-check text-blue-400 text-xs"></i> Simpan Jadwal
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
    function openJadwalModal() {
        const modal = document.getElementById('modal-jadwal');
        if (modal) {
            modal.classList.remove('hidden');
        }
    }
    function closeJadwalModal() {
        const modal = document.getElementById('modal-jadwal');
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
    document.getElementById('modal-jadwal')?.addEventListener('click', function(e) {
        if (e.target === this) {
            closeJadwalModal();
        }
    });
    // Tutup saat menekan tombol Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeProfileModal();
            closeJadwalModal();
        }
    });
</script>

</body>
</html>
