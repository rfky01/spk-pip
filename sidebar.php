<?php
// sidebar.php - Navigasi Menu Samping Aplikasi SPK PIP
$current_file = basename($_SERVER['PHP_SELF']);
?>
<!-- SIDEBAR NAVIGASI (TETAP DIAM / FIXED DI KIRI, MENDUKUNG COLLAPSE MINI) -->
<aside id="app-sidebar" class="w-64 bg-[#0B192C] border-r border-[#1E3A5F] flex flex-col justify-between shrink-0 h-screen select-none z-30 transition-all duration-300">
    <script>
        // Terapkan status tersimpan langsung sebelum render agar bebas flicker
        if (localStorage.getItem('sidebar_collapsed') === 'true') {
            document.getElementById('app-sidebar').classList.add('sidebar-collapsed');
        }
    </script>
    <div class="p-6 overflow-y-auto flex-1 sidebar-body">
        <!-- HEADER SIDEBAR & TOMBOL TOGGLE NAVIGASI -->
        <div class="flex items-center justify-between pb-5 border-b border-[#1E3A5F] sidebar-header">
            <div class="flex items-center gap-3 min-w-0">
                <div onclick="toggleSidebar()" title="<?= htmlspecialchars($pengaturan['nama_sekolah'] ?? 'SMP Tunas Bangsa') ?> - SPK PIP (Klik untuk Ciutkan/Buka)" class="bg-[#112240] border border-[#1E3A5F] w-10 h-10 rounded-xl flex items-center justify-center shadow-sm shrink-0 cursor-pointer transition-colors overflow-hidden p-1">
                    <?php if (!empty($pengaturan['logo']) && file_exists($pengaturan['logo'])): ?>
                        <img src="<?= htmlspecialchars($pengaturan['logo']) ?>" alt="Logo" class="w-full h-full object-contain">
                    <?php else: ?>
                        <div class="bg-slate-900 text-white w-full h-full rounded-lg flex items-center justify-center text-sm">
                            <i class="fa-solid fa-graduation-cap"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="sidebar-text truncate">
                    <span class="font-bold text-white text-sm leading-tight block truncate"><?= htmlspecialchars($pengaturan['nama_sekolah'] ?? 'SMP Tunas Bangsa') ?></span>
                    <span class="text-[11px] text-blue-300 font-medium block truncate">Sistem SPK PIP (AHP)</span>
                </div>
            </div>
            <button type="button" onclick="toggleSidebar()" id="sidebar-toggle-btn" title="Sembunyikan Navigasi" class="p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-[#112240] transition-colors shrink-0 cursor-pointer">
                <i id="sidebar-toggle-icon" class="fa-solid fa-angles-left text-xs"></i>
            </button>
        </div>
        
        <!-- DAFTAR MENU NAVIGASI (HANYA LOGO/ICON KETIKA DI-HIDE) -->
        <nav class="mt-6 space-y-1.5 sidebar-nav">
            <a href="dashboard.php" title="Dashboard" class="sidebar-nav-link w-full flex items-center gap-3 px-4 py-2.5 text-xs font-medium rounded-lg transition-colors whitespace-nowrap <?= $current_file === 'dashboard.php' ? 'active-nav' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' ?>">
                <i class="fa-solid fa-chart-pie w-4 text-slate-400 shrink-0 text-center text-sm"></i> <span class="sidebar-text">Dashboard</span>
            </a>
            <a href="data_calon_penerima.php" title="Usulan Calon PIP" class="sidebar-nav-link w-full flex items-center gap-3 px-4 py-2.5 text-xs font-medium rounded-lg transition-colors whitespace-nowrap <?= in_array($current_file, ['data_calon_penerima.php', 'calon.php']) ? 'active-nav' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' ?>">
                <i class="fa-solid fa-users w-4 text-slate-400 shrink-0 text-center text-sm"></i> <span class="sidebar-text">Usulan Calon PIP</span>
            </a>
            <a href="kriteria.php" title="Data Kriteria" class="sidebar-nav-link w-full flex items-center gap-3 px-4 py-2.5 text-xs font-medium rounded-lg transition-colors whitespace-nowrap <?= $current_file === 'kriteria.php' ? 'active-nav' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' ?>">
                <i class="fa-solid fa-list-check w-4 text-slate-400 shrink-0 text-center text-sm"></i> <span class="sidebar-text">Data Kriteria</span>
            </a>
            <a href="perhitungan_ahp.php" title="Perhitungan AHP" class="sidebar-nav-link w-full flex items-center gap-3 px-4 py-2.5 text-xs font-medium rounded-lg transition-colors whitespace-nowrap <?= in_array($current_file, ['perhitungan_ahp.php', 'matriks.php']) ? 'active-nav' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' ?>">
                <i class="fa-solid fa-calculator w-4 text-slate-400 shrink-0 text-center text-sm"></i> <span class="sidebar-text">Perhitungan AHP</span>
            </a>
            <a href="ranking.php" title="Hasil Ranking" class="sidebar-nav-link w-full flex items-center gap-3 px-4 py-2.5 text-xs font-medium rounded-lg transition-colors whitespace-nowrap <?= $current_file === 'ranking.php' ? 'active-nav' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' ?>">
                <i class="fa-solid fa-award w-4 text-slate-400 shrink-0 text-center text-sm"></i> <span class="sidebar-text">Hasil Ranking</span>
            </a>
            <a href="laporan.php" title="Laporan" class="sidebar-nav-link w-full flex items-center gap-3 px-4 py-2.5 text-xs font-medium rounded-lg transition-colors whitespace-nowrap <?= $current_file === 'laporan.php' ? 'active-nav' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' ?>">
                <i class="fa-solid fa-file-invoice w-4 text-slate-400 shrink-0 text-center text-sm"></i> <span class="sidebar-text">Laporan</span>
            </a>
            <a href="profil_sekolah.php" title="Profil Sekolah" class="sidebar-nav-link w-full flex items-center gap-3 px-4 py-2.5 text-xs font-medium rounded-lg transition-colors whitespace-nowrap <?= $current_file === 'profil_sekolah.php' ? 'active-nav' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-50' ?>">
                <i class="fa-solid fa-school w-4 text-slate-400 shrink-0 text-center text-sm"></i> <span class="sidebar-text">Profil Sekolah</span>
            </a>
        </nav>
    </div>
    
    <!-- FOOTER PROFIL ADMIN & LOGOUT -->
    <div class="p-6 border-t border-[#1E3A5F] space-y-3 shrink-0 bg-[#0B192C] sidebar-footer">
        <div onclick="openProfileModal()" title="Kelola Akun (<?= htmlspecialchars($_SESSION['admin']['nama'] ?? 'Admin') ?>)" class="sidebar-profile-box flex items-center justify-between p-2 -mx-2 rounded-xl text-xs text-slate-400 hover:bg-[#112240] border border-transparent hover:border-[#1E3A5F] cursor-pointer transition-all group">
            <div class="flex items-center gap-2.5 truncate min-w-0">
                <div class="w-8 h-8 rounded-full bg-[#1E3A5F] border border-[#274872] group-hover:border-blue-500 flex items-center justify-center text-blue-300 font-bold text-xs shrink-0 transition-colors">
                    <?= strtoupper(substr($_SESSION['admin']['nama'] ?? 'A', 0, 1)) ?>
                </div>
                <div class="sidebar-text truncate text-left">
                    <span class="font-bold text-white block truncate group-hover:text-blue-300"><?= htmlspecialchars($_SESSION['admin']['nama'] ?? 'Admin') ?></span>
                    <span class="text-[10px] text-slate-400 block truncate">@<?= htmlspecialchars($_SESSION['admin']['username'] ?? 'admin') ?></span>
                </div>
            </div>
            <i class="sidebar-text fa-solid fa-gear text-slate-300 group-hover:text-slate-600 text-xs transition-colors shrink-0 ml-1"></i>
        </div>
        <a href="logout.php" onclick="return konfirmasiAksi(event, this, 'Apakah Anda yakin ingin keluar dari sistem?')" title="Logout dari Sistem" class="sidebar-logout-btn w-full py-2 bg-[#112240] hover:bg-rose-950/50 text-rose-300 hover:text-rose-200 border border-[#1E3A5F] hover:border-rose-800/80 font-semibold rounded-lg transition-colors flex items-center justify-center gap-2 text-[11px] uppercase tracking-wider whitespace-nowrap">
            <i class="fa-solid fa-right-from-bracket text-xs"></i> <span class="sidebar-text">Logout</span>
        </a>
    </div>
</aside>

<script>
    function toggleSidebar() {
        const sidebar = document.getElementById('app-sidebar');
        const icon = document.getElementById('sidebar-toggle-icon');
        const btn = document.getElementById('sidebar-toggle-btn');
        if (!sidebar) return;

        const isCollapsed = sidebar.classList.toggle('sidebar-collapsed');
        localStorage.setItem('sidebar_collapsed', isCollapsed ? 'true' : 'false');
        
        if (icon) {
            if (isCollapsed) {
                icon.className = 'fa-solid fa-angles-right text-xs';
                if (btn) btn.title = 'Tampilkan Navigasi';
            } else {
                icon.className = 'fa-solid fa-angles-left text-xs';
                if (btn) btn.title = 'Sembunyikan Navigasi';
            }
        }
    }

    // Sesuaikan ikon tombol saat halaman dimuat
    document.addEventListener('DOMContentLoaded', function() {
        const isCollapsed = document.getElementById('app-sidebar')?.classList.contains('sidebar-collapsed');
        const icon = document.getElementById('sidebar-toggle-icon');
        const btn = document.getElementById('sidebar-toggle-btn');
        if (isCollapsed && icon) {
            icon.className = 'fa-solid fa-angles-right text-xs';
            if (btn) btn.title = 'Tampilkan Navigasi';
        }
    });
</script>

<!-- AREA KONTEN UTAMA (TERISOLASI & BERGULIR SECARA INDEPENDEN) -->
<main class="flex-1 flex flex-col min-w-0 h-screen overflow-hidden bg-[#0B192C] w-full">
    <div class="p-8 pb-16 overflow-y-auto flex-1 h-full scroll-smooth w-full">
        <?php if (!empty($flash_message)): ?>
            <?php toast_notifikasi($flash_message, $flash_type); ?>
        <?php endif; ?>

