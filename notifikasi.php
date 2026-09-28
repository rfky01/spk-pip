<?php
// notifikasi.php - Komponen Universal Popup Notifikasi Toast (Pojok Kanan Atas) & Dialog Konfirmasi (Tengah)
// Dapat di-include di berbagai halaman atau dipanggil langsung melalui PHP / JavaScript.

if (!function_exists('toast_notifikasi')) {
    /**
     * Helper PHP untuk memicu popup notifikasi toast dari sisi server.
     * @param string $pesan Pesan notifikasi yang ingin ditampilkan
     * @param string $tipe Tipe notifikasi: 'success' | 'error' | 'warning' | 'info'
     * @param string $judul Judul notifikasi (opsional, default: 'Perubahan Berhasil Disimpan')
     * @param int $durasi Durasi tampilan dalam milidetik (opsional, default: 3500 ms)
     */
    function toast_notifikasi($pesan, $tipe = 'success', $judul = '', $durasi = 3500) {
        if (empty($pesan)) return;
        $pesan_json  = json_encode($pesan);
        $tipe_json   = json_encode($tipe ?: 'success');
        $judul_def   = ($tipe === 'success') ? 'Perubahan Berhasil Disimpan' : (($tipe === 'error') ? 'Gagal Menyimpan' : 'Pemberitahuan Sistem');
        $judul_json  = json_encode(!empty($judul) ? $judul : $judul_def);
        $durasi_json = (int)$durasi > 0 ? (int)$durasi : 3500;

        echo "<script>
            (function() {
                function runToast() {
                    if (typeof window.tampilkanToast === 'function') {
                        window.tampilkanToast($pesan_json, $tipe_json, $judul_json, $durasi_json);
                    } else {
                        setTimeout(runToast, 50);
                    }
                }
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', runToast);
                } else {
                    runToast();
                }
            })();
        </script>\n";
    }
}

// Cegah duplikasi deklarasi CSS dan Script jika file ini di-include berulang kali
if (!defined('TOAST_NOTIFIKASI_LOADED')):
    define('TOAST_NOTIFIKASI_LOADED', true);
?>

<!-- STYLE CSS POPUP NOTIFIKASI TOAST & MODAL KONFIRMASI -->
<style id="spk-toast-styles">
    @keyframes toastSlideIn {
        0% {
            opacity: 0;
            transform: translateX(110%) scale(0.95);
        }
        70% {
            transform: translateX(-4px) scale(1.01);
        }
        100% {
            opacity: 1;
            transform: translateX(0) scale(1);
        }
    }

    @keyframes toastSlideOut {
        0% {
            opacity: 1;
            transform: translateX(0) scale(1);
            max-height: 120px;
            margin-bottom: 0.625rem;
        }
        100% {
            opacity: 0;
            transform: translateX(110%) scale(0.9);
            max-height: 0;
            margin-bottom: 0;
            padding-top: 0;
            padding-bottom: 0;
            border-width: 0;
        }
    }

    .toast-item {
        animation: toastSlideIn 0.35s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        will-change: transform, opacity;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.5), 0 8px 10px -6px rgba(0, 0, 0, 0.5);
    }

    .toast-item.toast-exit {
        animation: toastSlideOut 0.35s cubic-bezier(0.16, 1, 0.3, 1) forwards !important;
    }
</style>

<!-- WADAH POPUP NOTIFIKASI DI POJOK KANAN ATAS -->
<div id="toast-container" class="fixed top-5 right-5 z-[99999] pointer-events-none flex flex-col gap-2.5 max-w-sm sm:max-w-md w-full px-4 sm:px-0"></div>

<!-- ========================================================
     MODAL POPUP KONFIRMASI TENGAH (CENTER CONFIRMATION MODAL)
     ======================================================== -->
<div id="modal-konfirmasi-backdrop" class="fixed inset-0 z-[999999] bg-black/75 backdrop-blur-sm hidden flex items-center justify-center p-4 transition-opacity duration-200">
    <div id="modal-konfirmasi-card" class="bg-[#112240] border border-[#1E3A5F] rounded-2xl p-6 sm:p-7 shadow-2xl max-w-sm sm:max-w-md w-full text-center relative transform transition-all duration-200 scale-95 opacity-0">
        
        <!-- Tombol Silang Pojok Kanan Atas -->
        <button type="button" onclick="tutupKonfirmasiModal()" class="absolute top-4 right-4 text-slate-400 hover:text-white transition-colors cursor-pointer text-base leading-none p-1" title="Tutup">
            <i class="fa-solid fa-xmark text-sm"></i>
        </button>

        <!-- Ikon Tengah -->
        <div id="modal-konfirmasi-icon-wrapper" class="w-14 h-14 rounded-2xl mx-auto flex items-center justify-center text-2xl shadow-inner mb-4 bg-amber-500/15 border border-amber-500/30 text-amber-400">
            <i id="modal-konfirmasi-icon" class="fa-solid fa-key"></i>
        </div>

        <!-- Judul & Pesan -->
        <h3 id="modal-konfirmasi-judul" class="text-base sm:text-lg font-bold text-white mb-2 leading-snug">Konfirmasi Tindakan</h3>
        <p id="modal-konfirmasi-pesan" class="text-xs sm:text-sm text-slate-300 leading-relaxed mb-5 font-normal"></p>

        <!-- Subtext / Note Info (opsional) -->
        <div id="modal-konfirmasi-subtext" class="text-[11px] text-slate-400 bg-[#0B192C] border border-[#1E3A5F] rounded-xl p-3 mb-5 hidden leading-relaxed text-left"></div>

        <!-- Tombol Aksi (Batal & Ya) -->
        <div class="flex items-center justify-center gap-3">
            <button type="button" id="modal-konfirmasi-btn-batal" onclick="tutupKonfirmasiModal()" class="flex-1 py-2.5 px-4 bg-[#0B192C] hover:bg-[#162B4D] text-slate-300 border border-[#1E3A5F] hover:border-slate-500 font-semibold rounded-xl text-xs sm:text-sm transition-all duration-150 cursor-pointer">
                Batal
            </button>
            <button type="button" id="modal-konfirmasi-btn-ya" class="flex-1 py-2.5 px-4 bg-amber-600 hover:bg-amber-500 text-white font-semibold rounded-xl text-xs sm:text-sm shadow-md transition-all duration-150 cursor-pointer flex items-center justify-center gap-2">
                <span>Ya, Lanjutkan</span>
            </button>
        </div>
    </div>
</div>

<!-- FUNGSI JAVASCRIPT GLOBAL TOAST NOTIFIKASI & POPUP KONFIRMASI -->
<script>
    // 1. FUNGSI TOAST NOTIFIKASI (POJOK KANAN ATAS)
    if (typeof window.tampilkanToast !== 'function') {
        window.tampilkanToast = function(pesan, tipe = 'success', judul = '', durasi = 3500) {
            let container = document.getElementById('toast-container');
            if (!container) {
                container = document.createElement('div');
                container.id = 'toast-container';
                container.className = 'fixed top-5 right-5 z-[99999] pointer-events-none flex flex-col gap-2.5 max-w-sm sm:max-w-md w-full px-4 sm:px-0';
                document.body.appendChild(container);
            }

            if (!judul) {
                judul = (tipe === 'success') ? 'Perubahan Berhasil Disimpan' : ((tipe === 'error') ? 'Gagal Menyimpan' : 'Pemberitahuan Sistem');
            }

            const isSuccess = (tipe === 'success');
            const isError   = (tipe === 'error');
            const iconClass = isSuccess ? 'fa-solid fa-circle-check text-emerald-400' : (isError ? 'fa-solid fa-circle-exclamation text-rose-400' : 'fa-solid fa-circle-info text-blue-400');
            const iconBg    = isSuccess ? 'bg-emerald-500/15 border-emerald-500/30' : (isError ? 'bg-rose-500/15 border-rose-500/30' : 'bg-blue-500/15 border-blue-500/30');
            const borderColor = isSuccess ? 'border-emerald-500/40' : (isError ? 'border-rose-500/40' : 'border-blue-500/40');

            const toast = document.createElement('div');
            toast.className = `toast-item pointer-events-auto relative overflow-hidden rounded-xl bg-[#112240] border ${borderColor} text-white shadow-2xl p-3.5 flex items-start gap-3 transition-all duration-300 backdrop-blur-md`;

            const safeJudul = document.createElement('div');
            safeJudul.textContent = judul;
            const safePesan = document.createElement('div');
            safePesan.textContent = pesan;

            // Catatan: Bilah waktu berjalan (progress bar) dihapus sesuai permintaan
            toast.innerHTML = `
                <div class="w-8 h-8 rounded-lg ${iconBg} border flex items-center justify-center shrink-0 mt-0.5 shadow-sm">
                    <i class="${iconClass} text-sm"></i>
                </div>
                <div class="flex-1 min-w-0 pr-1">
                    <h4 class="text-xs font-bold text-white leading-tight">${safeJudul.innerHTML}</h4>
                    <p class="text-[11px] text-slate-300 mt-0.5 leading-relaxed font-normal">${safePesan.innerHTML}</p>
                </div>
                <button type="button" class="toast-close-btn text-slate-400 hover:text-white transition-colors cursor-pointer p-1 -mr-1 -mt-1 leading-none text-base" title="Tutup Notifikasi">
                    <i class="fa-solid fa-xmark text-xs"></i>
                </button>
            `;

            container.appendChild(toast);

            let isDismissed = false;
            const dismissToast = () => {
                if (isDismissed) return;
                isDismissed = true;
                toast.classList.add('toast-exit');
                setTimeout(() => {
                    if (toast.parentElement) toast.remove();
                }, 360);
            };

            const closeBtn = toast.querySelector('.toast-close-btn');
            if (closeBtn) {
                closeBtn.addEventListener('click', dismissToast);
            }

            // Durasi tampil otomatis (dapat dikustomisasi per halaman, default: 3500ms)
            const timeoutDuration = (typeof durasi === 'number' && durasi > 0) ? durasi : 3500;
            let dismissTimeout = setTimeout(dismissToast, timeoutDuration);

            // Jeda jika mouse diarahkan ke notifikasi
            toast.addEventListener('mouseenter', () => {
                clearTimeout(dismissTimeout);
            });

            toast.addEventListener('mouseleave', () => {
                dismissTimeout = setTimeout(dismissToast, 1500);
            });
        };

        window.showToast = window.tampilkanToast;
    }

    // 2. FUNGSI MODAL POPUP KONFIRMASI (TENGAH)
    let konfirmasiCallbackAction = null;

    window.tutupKonfirmasiModal = function() {
        const backdrop = document.getElementById('modal-konfirmasi-backdrop');
        const card = document.getElementById('modal-konfirmasi-card');
        if (card) {
            card.classList.remove('scale-100', 'opacity-100');
            card.classList.add('scale-95', 'opacity-0');
        }
        setTimeout(() => {
            if (backdrop) backdrop.classList.add('hidden');
            konfirmasiCallbackAction = null;
        }, 180);
    };

    window.tampilkanKonfirmasi = function(options = {}) {
        const backdrop = document.getElementById('modal-konfirmasi-backdrop');
        const card = document.getElementById('modal-konfirmasi-card');
        const iconWrapper = document.getElementById('modal-konfirmasi-icon-wrapper');
        const iconEl = document.getElementById('modal-konfirmasi-icon');
        const judulEl = document.getElementById('modal-konfirmasi-judul');
        const pesanEl = document.getElementById('modal-konfirmasi-pesan');
        const subtextEl = document.getElementById('modal-konfirmasi-subtext');
        const btnYa = document.getElementById('modal-konfirmasi-btn-ya');
        const btnBatal = document.getElementById('modal-konfirmasi-btn-batal');

        if (!backdrop || !card) return;

        const tipe = options.tipe || 'amber'; // 'amber' | 'rose' | 'emerald' | 'blue'
        const judul = options.judul || 'Konfirmasi Tindakan';
        const pesan = options.pesan || 'Apakah Anda yakin ingin melanjutkan?';
        const subtext = options.subtext || '';
        const teksYa = options.teksYa || 'Ya, Lanjutkan';
        const teksBatal = options.teksBatal || 'Batal';
        const icon = options.icon || (tipe === 'amber' ? 'fa-solid fa-key' : (tipe === 'rose' ? 'fa-solid fa-triangle-exclamation' : 'fa-solid fa-circle-question'));

        if (judulEl) judulEl.textContent = judul;
        if (pesanEl) pesanEl.innerHTML = pesan;

        if (subtextEl) {
            if (subtext) {
                subtextEl.innerHTML = subtext;
                subtextEl.classList.remove('hidden');
            } else {
                subtextEl.classList.add('hidden');
            }
        }

        if (btnBatal) btnBatal.textContent = teksBatal;
        if (btnYa) {
            btnYa.innerHTML = `<span>${teksYa}</span>`;
            if (tipe === 'amber') {
                btnYa.className = 'flex-1 py-2.5 px-4 bg-amber-600 hover:bg-amber-500 text-white font-semibold rounded-xl text-xs sm:text-sm shadow-md transition-all duration-150 cursor-pointer flex items-center justify-center gap-2';
                if (iconWrapper) iconWrapper.className = 'w-14 h-14 rounded-2xl mx-auto flex items-center justify-center text-2xl shadow-inner mb-4 bg-amber-500/15 border border-amber-500/30 text-amber-400';
            } else if (tipe === 'rose') {
                btnYa.className = 'flex-1 py-2.5 px-4 bg-rose-600 hover:bg-rose-500 text-white font-semibold rounded-xl text-xs sm:text-sm shadow-md transition-all duration-150 cursor-pointer flex items-center justify-center gap-2';
                if (iconWrapper) iconWrapper.className = 'w-14 h-14 rounded-2xl mx-auto flex items-center justify-center text-2xl shadow-inner mb-4 bg-rose-500/15 border border-rose-500/30 text-rose-400';
            } else if (tipe === 'emerald') {
                btnYa.className = 'flex-1 py-2.5 px-4 bg-emerald-600 hover:bg-emerald-500 text-white font-semibold rounded-xl text-xs sm:text-sm shadow-md transition-all duration-150 cursor-pointer flex items-center justify-center gap-2';
                if (iconWrapper) iconWrapper.className = 'w-14 h-14 rounded-2xl mx-auto flex items-center justify-center text-2xl shadow-inner mb-4 bg-emerald-500/15 border border-emerald-500/30 text-emerald-400';
            } else {
                btnYa.className = 'flex-1 py-2.5 px-4 bg-blue-600 hover:bg-blue-500 text-white font-semibold rounded-xl text-xs sm:text-sm shadow-md transition-all duration-150 cursor-pointer flex items-center justify-center gap-2';
                if (iconWrapper) iconWrapper.className = 'w-14 h-14 rounded-2xl mx-auto flex items-center justify-center text-2xl shadow-inner mb-4 bg-blue-500/15 border border-blue-500/30 text-blue-400';
            }
        }

        if (iconEl) iconEl.className = icon;

        konfirmasiCallbackAction = options.onConfirm || null;

        backdrop.classList.remove('hidden');
        requestAnimationFrame(() => {
            card.classList.remove('scale-95', 'opacity-0');
            card.classList.add('scale-100', 'opacity-100');
        });
    };

    /**
     * Helper universal untuk link (tag <a>) atau tombol aksi yang memerlukan konfirmasi popup tengah
     */
    window.konfirmasiAksi = function(event, elementOrUrl, pesan, options = {}) {
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }

        let targetUrl = '';
        if (typeof elementOrUrl === 'string') {
            targetUrl = elementOrUrl;
        } else if (elementOrUrl && elementOrUrl.href) {
            targetUrl = elementOrUrl.href;
        }

        const lowerPesan = (pesan || '').toLowerCase();
        let tipe = options.tipe || 'amber';
        let icon = options.icon || 'fa-solid fa-key';
        let judul = options.judul || 'Konfirmasi Tindakan';
        let teksYa = options.teksYa || 'Ya, Lanjutkan';
        let subtext = options.subtext || '';

        if (lowerPesan.includes('reset pin')) {
            tipe = options.tipe || 'amber';
            icon = options.icon || 'fa-solid fa-key';
            judul = options.judul || 'Konfirmasi Reset PIN Siswa';
            teksYa = options.teksYa || 'Ya, Reset PIN';
            subtext = options.subtext || '<i class="fa-solid fa-circle-info text-amber-400 mr-1"></i> PIN akun siswa akan diubah kembali menjadi <b>4 digit terakhir NISN</b>.';
        } else if (lowerPesan.includes('hapus')) {
            tipe = options.tipe || 'rose';
            icon = options.icon || 'fa-solid fa-trash-can';
            judul = options.judul || 'Konfirmasi Hapus Data';
            teksYa = options.teksYa || 'Ya, Hapus Data';
            subtext = options.subtext || '<i class="fa-solid fa-triangle-exclamation text-rose-400 mr-1"></i> Data yang dihapus tidak dapat dipulihkan kembali dari sistem.';
        } else if (lowerPesan.includes('tolak')) {
            tipe = options.tipe || 'rose';
            icon = options.icon || 'fa-solid fa-circle-xmark';
            judul = options.judul || 'Konfirmasi Tolak Berkas';
            teksYa = options.teksYa || 'Ya, Tolak Berkas';
            subtext = options.subtext || '<i class="fa-solid fa-circle-info text-rose-400 mr-1"></i> Status pengajuan siswa akan ditandai sebagai <b>Ditolak</b>.';
        } else if (lowerPesan.includes('verifikasi') || lowerPesan.includes('setujui')) {
            tipe = options.tipe || 'emerald';
            icon = options.icon || 'fa-solid fa-circle-check';
            judul = options.judul || 'Konfirmasi Setujui Berkas';
            teksYa = options.teksYa || 'Ya, Setujui Berkas';
            subtext = options.subtext || '<i class="fa-solid fa-circle-check text-emerald-400 mr-1"></i> Berkas siswa akan disetujui dan langsung masuk pemeringkatan resmi.';
        } else if (lowerPesan.includes('keluar') || lowerPesan.includes('logout')) {
            tipe = options.tipe || 'rose';
            icon = options.icon || 'fa-solid fa-right-from-bracket';
            judul = options.judul || 'Konfirmasi Keluar Sistem';
            teksYa = options.teksYa || 'Ya, Logout';
            subtext = options.subtext || 'Sesi login admin Anda akan diakhiri.';
        }

        window.tampilkanKonfirmasi({
            judul: judul,
            pesan: pesan,
            subtext: subtext,
            tipe: tipe,
            icon: icon,
            teksYa: teksYa,
            teksBatal: options.teksBatal || 'Batal',
            onConfirm: function() {
                if (typeof options.onConfirm === 'function') {
                    options.onConfirm();
                } else if (targetUrl) {
                    window.location.href = targetUrl;
                }
            }
        });

        return false;
    };

    // Binding event tombol Ya dan klik backdrop di modal konfirmasi tengah
    document.addEventListener('DOMContentLoaded', function() {
        const btnYa = document.getElementById('modal-konfirmasi-btn-ya');
        if (btnYa) {
            btnYa.addEventListener('click', function() {
                const action = konfirmasiCallbackAction;
                tutupKonfirmasiModal();
                if (typeof action === 'function') {
                    action();
                }
            });
        }

        const backdrop = document.getElementById('modal-konfirmasi-backdrop');
        if (backdrop) {
            backdrop.addEventListener('click', function(e) {
                if (e.target === this) {
                    tutupKonfirmasiModal();
                }
            });
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && backdrop && !backdrop.classList.contains('hidden')) {
                tutupKonfirmasiModal();
            }
        });
    });
</script>
<?php endif; ?>
