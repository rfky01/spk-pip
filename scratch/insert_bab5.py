import docx
import sys
from docx.shared import Pt, Inches
from docx.enum.text import WD_ALIGN_PARAGRAPH

sys.stdout.reconfigure(encoding='utf-8')

docx_path = r'D:\KULIAH\Joki\Annisa\(ok) reNEWok Proposal BAB I-III Annisa belum fix 2 (1) (1) - UPDATED.docx'
doc = docx.Document(docx_path)

print(f"Loaded document. Paragraphs: {len(doc.paragraphs)}, Tables: {len(doc.tables)}")

# Locate BAB V elements
p_bab5_heading = None
p_kesimpulan_heading = None
p_saran_heading = None
p_daftar_pustaka = None

for i, p in enumerate(doc.paragraphs):
    if p.style.name == 'Heading 1' and 'KESIMPULAN DAN SARAN' in p.text:
        p_bab5_heading = i
    elif p.style.name == 'Heading 2' and 'Kesimpulan' in p.text and i > 1000:
        p_kesimpulan_heading = i
    elif p.style.name == 'Heading 2' and 'Saran' in p.text and i > 1000:
        p_saran_heading = i
    elif 'DAFTAR PUSTAKA' in p.text and i > 1000:
        p_daftar_pustaka = i

print(f"Found indices: BAB V={p_bab5_heading}, Kesimpulan={p_kesimpulan_heading}, Saran={p_saran_heading}, Daftar Pustaka={p_daftar_pustaka}")

kesimpulan_paras = [
    "Berdasarkan seluruh tahapan penelitian, perancangan arsitektur sistem, implementasi program, serta pengujian yang telah dilaksanakan, dapat disimpulkan bahwa penelitian ini telah berhasil merancang dan membangun Sistem Pendukung Keputusan (SPK) Penentuan Prioritas Penerima Program Indonesia Pintar (PIP) menggunakan metode Analytical Hierarchy Process (AHP) berbasis web pada SMP Tunas Bangsa Kecamatan Bandar Mataram. Sistem ini berhasil memberikan solusi komprehensif terhadap kendala proses penyeleksian calon penerima bantuan yang sebelumnya masih dilakukan secara konvensional, rentan terhadap subjektivitas tim penilai, serta memerlukan waktu verifikasi yang relatif lama di tengah keterbatasan kuota beasiswa yang tersedia.",
    
    "Penerapan metode Analytical Hierarchy Process (AHP) terbukti efektif dan adaptif dalam menstrukturkan permasalahan multikriteria yang kompleks ke dalam tingkatan hierarki keputusan yang sistematis. Melalui proses pembobotan perbandingan berpasangan (pairwise comparison) berpedoman pada skala 1–9 Saaty, diperoleh bobot prioritas vektor eigen (w) untuk lima kriteria penilaian, yaitu Penghasilan Orang Tua (C1) sebesar 0,4162 (41,62%), Jumlah Tanggungan Keluarga (C2) sebesar 0,2618 (26,18%), Kondisi Fisik Rumah (C3) sebesar 0,1611 (16,11%), Prestasi Akademik Siswa (C4) sebesar 0,0986 (9,86%), dan Jarak Tempat Tinggal ke Sekolah (C5) sebesar 0,0624 (6,24%). Pengujian matematis menunjukkan nilai principal eigenvalue (λ_max) sebesar 5,0683, Consistency Index (CI) sebesar 0,0171, dan Consistency Ratio (CR) sebesar 0,0153. Nilai CR tersebut memenuhi syarat konsistensi Saaty (CR ≤ 0,10), sehingga pertimbangan bobot kriteria dinyatakan sepenuhnya konsisten, valid, dan dapat dipertanggungjawabkan secara ilmiah, dengan faktor kondisi ekonomi keluarga memegang peranan dominan sebesar 83,91% terhadap penetapan prioritas penerima bantuan.",
    
    "Sistem pendukung keputusan yang dikembangkan mampu melakukan komputasi evaluasi nilai subkriteria dan perankingan terhadap 30 data riil peserta didik calon penerima PIP kelas VII secara objektif, akurat, dan transparan. Berdasarkan kebijakan alokasi kuota resmi SMP Tunas Bangsa sebanyak 20 siswa, sistem secara otomatis menetapkan rekomendasi status Prioritas Lolos Kuota PIP bagi kandidat peringkat 1 sampai dengan 20 dengan perolehan skor preferensi akhir antara 5,0005 hingga 1,9935, yang dipuncaki oleh Rahmad Padil Peratama (skor 5,0005), Abizar Nur Ramadhan (skor 4,9019), dan Yusup Baihaqi (skor 4,8394). Sementara itu, 10 kandidat pada peringkat 21 sampai dengan 30 dengan perolehan skor antara 1,9083 hingga 1,0987 secara sistematis ditetapkan menempati status Cadangan. Hasil pemeringkatan ini memberikan transparansi penuh serta mempermudah pihak sekolah dalam mempertanggungjawabkan ketetapan penerima bantuan kepada komite sekolah dan yayasan.",
    
    "Hasil pengujian fungsional perangkat lunak menggunakan metode Black Box Testing menunjukkan bahwa seluruh modul aplikasi berjalan dengan sangat baik dan bebas dari galat logika maupun fungsional. Seluruh alur kerja yang mencakup modul pendaftaran mandiri siswa dengan pembatasan jadwal otomatis, autentikasi keamanan hak akses berbasis enkripsi, manajemen verifikasi data administratif, kalkulasi dinamis matriks algoritma AHP, visualisasi pemeringkatan kelayakan, hingga penerbitan dokumen cetak Surat Keputusan (SK) penetapan penerima PIP berformat resmi telah teruji andal. Dengan demikian, sistem pendukung keputusan berbasis web ini dinyatakan sangat layak dan siap dioperasikan guna meningkatkan akuntabilitas, efisiensi birokrasi, dan ketepatan sasaran penyaluran beasiswa Program Indonesia Pintar di SMP Tunas Bangsa Kecamatan Bandar Mataram."
]

saran_paras = [
    "Pengembangan Sistem Pendukung Keputusan Penentuan Prioritas Penerima Program Indonesia Pintar (PIP) ini ke depan disarankan untuk memperluas kapabilitas fungsionalnya melalui integrasi Application Programming Interface (API) secara langsung terhadap pangkalan Data Pokok Pendidikan (Dapodik) Kementerian Pendidikan, Kebudayaan, Riset, dan Teknologi. Melalui integrasi tersebut, proses validasi Nomor Induk Siswa Nasional (NISN), status keaktifan akademik, dan kelengkapan identitas kependudukan peserta didik dapat diverifikasi secara otomatis dan tersinkronisasi tanpa memerlukan entri manual berulang. Di samping itu, integrasi layanan notifikasi otomatis berbasis pesan instan (WhatsApp Gateway atau SMS Gateway) perlu dipertimbangkan guna menyampaikan informasi tanda terima pendaftaran, progres verifikasi berkas, dan pengumuman penetapan hasil seleksi secara real-time langsung ke nomor telepon seluler orang tua atau wali murid.",
    
    "Pada aspek metodologis dan rekayasa kecerdasan keputusan, penelitian selanjutnya disarankan untuk mengeksplorasi penggabungan metode hibrida (hybrid decision making methods), seperti mengombinasikan metode Analytical Hierarchy Process (AHP) untuk penentuan pembobotan kriteria dengan metode pemeringkatan lain seperti Technique for Order Preference by Similarity to Ideal Solution (TOPSIS) atau Simple Additive Weighting (SAW). Pendekatan komparatif ini bermanfaat untuk menguji tingkat stabilitas dan sensitivitas luaran ranking pada berbagai skenario persebaran data. Selain itu, pihak pengambil keputusan di sekolah disarankan untuk melakukan peninjauan dan kalibrasi nilai perbandingan berpasangan antar kriteria secara berkala guna menyesuaikan dinamika pedoman teknis kementerian serta perubahan kondisi sosio-ekonomi masyarakat di lingkungan sekitar sekolah.",
    
    "Dari sudut pandang tata kelola operasional dan keberlanjutan institusi, pihak manajemen SMP Tunas Bangsa disarankan untuk menyelenggarakan pelatihan teknis dan pendampingan berkelanjutan bagi staf tata usaha serta panitia penerimaan bantuan PIP dalam mengoperasikan sistem dan memahami prinsip perhitungan algoritma AHP. Pihak sekolah juga perlu menetapkan prosedur operasional standar (SOP) terkait pemeliharaan rutin pangkalan data, pencadangan basis data secara berkala (regular database backup), serta pemutakhiran sertifikat keamanan peladen web guna memastikan keandalan sistem jangka panjang, kerahasiaan informasi data pribadi siswa, dan kelancaran operasional seleksi beasiswa pada setiap tahun ajaran baru."
]

# Formatting function for academic paragraph
def format_academic_paragraph(p):
    p.alignment = WD_ALIGN_PARAGRAPH.JUSTIFY
    p.paragraph_format.line_spacing = 1.5
    p.paragraph_format.first_line_indent = Inches(0.4) # approx 1 cm indent
    p.paragraph_format.space_after = Pt(0)
    p.paragraph_format.space_before = Pt(0)
    for r in p.runs:
        r.font.name = "Times New Roman"
        r.font.size = Pt(12)

# Insert paragraphs under Kesimpulan
# We insert paragraphs before p_saran_heading
p_saran_elem = doc.paragraphs[p_saran_heading]._p

for text in kesimpulan_paras:
    new_p = doc.add_paragraph(text)
    format_academic_paragraph(new_p)
    # Move before p_saran_elem
    p_saran_elem.addprevious(new_p._p)

print("Kesimpulan paragraphs inserted successfully.")

# Re-locate Daftar Pustaka
for i, p in enumerate(doc.paragraphs):
    if 'DAFTAR PUSTAKA' in p.text and i > 1000:
        p_daftar_pustaka = i
        break

p_dp_elem = doc.paragraphs[p_daftar_pustaka]._p

# Insert paragraphs under Saran (before Daftar Pustaka)
for text in saran_paras:
    new_p = doc.add_paragraph(text)
    format_academic_paragraph(new_p)
    p_dp_elem.addprevious(new_p._p)

print("Saran paragraphs inserted successfully.")

# Clean up empty paragraphs in BAB V area
# Find BAB V start again
bab5_idx = None
dp_idx = None
for i, p in enumerate(doc.paragraphs):
    if p.style.name == 'Heading 1' and 'KESIMPULAN DAN SARAN' in p.text:
        bab5_idx = i
    if 'DAFTAR PUSTAKA' in p.text and i > 1000:
        dp_idx = i

print(f"BAB V is from P{bab5_idx} to P{dp_idx}")

# Remove empty paragraphs between bab5_idx and dp_idx
# Collect them in reverse
to_delete = []
for i in range(bab5_idx + 1, dp_idx):
    p = doc.paragraphs[i]
    if not p.text.strip():
        to_delete.append(p)

for p in to_delete:
    p._p.getparent().remove(p._p)

print(f"Removed {len(to_delete)} empty paragraphs in BAB V.")

# Save updated document to final complete skripsi file
output_final = r'D:\KULIAH\Joki\Annisa\(ok) Skripsi BAB I-V Annisa (LENGKAP).docx'
doc.save(output_final)
print(f"SUCCESS: BAB V successfully inserted and saved to: {output_final}")

# Also try saving to UPDATED and original if unlocked
for pth in [
    r'D:\KULIAH\Joki\Annisa\(ok) reNEWok Proposal BAB I-III Annisa belum fix 2 (1) (1) - UPDATED.docx',
    r'D:\KULIAH\Joki\Annisa\(ok) reNEWok Proposal BAB I-III Annisa belum fix 2 (1) (1).docx'
]:
    try:
        doc.save(pth)
        print(f"SUCCESS: Also saved to: {pth}")
    except Exception as e:
        print(f"Note: {pth} is currently open in Word.")


