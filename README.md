# Sistem Pendukung Keputusan (SPK) Penerima Program Indonesia Pintar (PIP)
### Metode Analytical Hierarchy Process (AHP)

Aplikasi web Sistem Pendukung Keputusan (SPK) berbasis PHP Native & MySQL dengan metode **Analytical Hierarchy Process (AHP)** untuk menentukan prioritas penerima bantuan **Program Indonesia Pintar (PIP)** secara objektif, transparan, dan akurat.

---

## 🚀 Fitur Utama

### 1. Panel Administrator & Guru
- **Dashboard Ringkasan:** Statistik kuota, total pendaftar, calon penerima, dan ringkasan skor AHP.
- **Manajemen Kriteria AHP:** Pengaturan bobot perbandingan berpasangan antarkriteria lengkap dengan perhitungan otomatis nilai Eigen, $\lambda_{\text{max}}$, *Consistency Index* (CI), dan *Consistency Ratio* (CR) $\le 0.10$.
- **Verifikasi & Data Calon:** Validasi berkas bukti dokumen pendaftar (SKTM, KIP, KKS, Slip Gaji, dll.).
- **Perhitungan & Perankingan AHP:** Matriks normalisasi dan perangkingan siswa penerima bantuan.
- **Cetak Laporan & Ekspor:** Laporan PDF siap cetak dengan tanda tangan kepala sekolah serta ekspor data ke Excel.
- **Pengaturan Profil Sekolah:** Pengaturan dinamis nama sekolah, NPSN, logo sekolah, kuota penerima, dan informasi pimpinan.

### 2. Portal Mandiri Siswa / Pendaftar
- **Pendaftaran Mandiri:** Pendaftaran akun dan pengisian berkas persyaratan secara daring.
- **Keamanan Akun Berbasis PIN:** Proteksi data pendaftar menggunakan kombinasi unik NISN dan PIN keamanan.
- **Dashboard Status Siswa:** Memantau tahapan verifikasi pengajuan berkas (Pending, Diverifikasi, Ditolak).
- **Edit & Perbarui Berkas:** Siswa dapat memperbarui data jika terdapat catatan perbaikan dari verifikator.

---

## 🛠️ Teknologi yang Digunakan
- **Bahasa Pemrograman:** PHP (Native / Procedural)
- **Database:** MySQL / MariaDB
- **User Interface:** HTML5, Tailwind CSS (CDN), FontAwesome 6
- **Server:** Apache (Laragon / XAMPP / cPanel Web Hosting)

---

## 💻 Panduan Instalasi Lokal (Laragon / XAMPP)

1. **Clone Repositori:**
   ```bash
   git clone https://github.com/USERNAME_ANDA/spk-pip.git
   ```
2. **Pindahkan Folder:**
   Pastikan folder project berada di direktori web server lokal, misalnya:
   - Laragon: `C:\laragon\www\spk-pip`
   - XAMPP: `C:\xampp\htdocs\spk-pip`

3. **Database:**
   - Nyalakan layanan MySQL di Laragon / XAMPP.
   - Database `spk_pip` akan dibuat secara otomatis saat pertama kali aplikasi dibuka berkat otomasi migrasi di `koneksi.php`.
   - Atau Anda dapat mengimpor file `database.sql` secara manual melalui phpMyAdmin.

4. **Akses Aplikasi:**
   - **Halaman Utama / Pendaftaran:** `http://localhost/spk-pip/`
   - **Login Administrator:** `http://localhost/spk-pip/login.php`
   - **Login Portal Siswa:** `http://localhost/spk-pip/login_siswa.php`

