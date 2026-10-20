import docx
import re
from docx.oxml import parse_xml, OxmlElement
from docx.oxml.ns import nsdecls, qn

docx_path = r'D:\KULIAH\Joki\Annisa\(ok) reNEWok Proposal BAB I-III Annisa belum fix 2 (1) (1).docx'
doc = docx.Document(docx_path)

print(f"Loaded document. Paragraphs: {len(doc.paragraphs)}, Tables: {len(doc.tables)}")

# ==============================================================================
# 1. FIX BAB I: Paragraph 415 (Latar Belakang kuota 27 -> 20)
# ==============================================================================
for p in doc.paragraphs:
    if "27 siswa" in p.text and "kuota bantuan yang tersedia" in p.text:
        print("Fixing BAB I kuota in paragraph:", p.text[:80])
        p.text = p.text.replace("hanya sebanyak 27 siswa", "sebanyak 20 siswa").replace("sebanyak 27 siswa", "sebanyak 20 siswa")

# ==============================================================================
# 2. FIX BAB III: Sync weights percentages and calculation values
# ==============================================================================
for p in doc.paragraphs:
    if "Penghasilan Orang Tua = 41.65%" in p.text:
        p.text = p.text.replace("41.65%", "41.62%")
    if "Jumlah tanggungan keluarga = 26.19%" in p.text:
        p.text = p.text.replace("26.19%", "26.18%")
    if "Kondisi Tempat Tinggal/Rumah = 16.08%" in p.text:
        p.text = p.text.replace("16.08%", "16.11%")
    if "Prestasi akademik = 9.85%" in p.text:
        p.text = p.text.replace("9.85%", "9.86%")
    if "Jarak tempuh ke sekolah = 6.23%" in p.text:
        p.text = p.text.replace("6.23%", "6.24%")

# ==============================================================================
# 3. FIX BAB IV: Table Titles & Mentions of 24 / 10
# ==============================================================================
for p in doc.paragraphs:
    # Table 4.12 title
    if "Tabel 4. 10 Data Nilai Subkriteria 24 Alternatif Siswa" in p.text:
        print("Fixing Table 4.12 title...")
        p.text = "Tabel 4. 12 Data Nilai Subkriteria 30 Alternatif Siswa."
    
    # Table 4.13 title
    if "Tabel 4. 11 Matriks Perhitungan Evaluasi dan Skor Akhir Alternatif" in p.text:
        print("Fixing Table 4.13 title...")
        p.text = "Tabel 4. 13 Matriks Perhitungan Evaluasi dan Skor Akhir Alternatif."
        
    # Table 4.14 title
    if "Tabel 4. 12 Hasil Perangkingan dan Rekomendasi Penerima Kuota PIP" in p.text:
        print("Fixing Table 4.14 title...")
        p.text = "Tabel 4. 14 Hasil Perangkingan dan Rekomendasi Penerima Kuota PIP."

    # 4.3.8 Dashboard admin text
    if "Halaman Dashboard Administrator merupakan beranda kendali utama" in p.text:
        print("Fixing 4.3.8 Dashboard admin text...")
        p.text = p.text.replace("(24 siswa)", "(30 siswa)").replace("(10 siswa)", "(20 siswa)")

    # 4.3.9 Usulan Calon text
    if "Halaman Data Calon Penerima (Usulan Siswa) merupakan modul pengelolaan data master" in p.text:
        print("Fixing 4.3.9 Usulan calon text...")
        p.text = p.text.replace("(24 siswa)", "(30 siswa)")

    # Fix typo "Hampilan" to "Tampilan"
    if "Gambar 4. 7 Hampilan Halaman Data Kriteria" in p.text:
        p.text = p.text.replace("Hampilan", "Tampilan")

    # Fix consecutive Figure numbering in 4.3
    # P1000 is Gambar 4.1
    # P1010 -> Gambar 4.2
    if "Gambar 4. 1 Tampilan Halaman Pendaftaran Mandiri" in p.text:
        p.text = "Gambar 4. 2 Tampilan Halaman Pendaftaran Mandiri Calon Penerima PIP."
    elif "Gambar 4. 2 Tampilan Halaman Login Siswa" in p.text:
        p.text = "Gambar 4. 3 Tampilan Halaman Login Siswa."
    elif "Gambar 4. 3 Tampilan Halaman Pengumuman" in p.text:
        p.text = "Gambar 4. 4 Tampilan Halaman Pengumuman dan Bukti Pendaftaran Siswa."
    elif "Gambar 4. 4 Tampilan Halaman Login Administrator" in p.text:
        p.text = "Gambar 4. 5 Tampilan Halaman Login Administrator."
    elif "Gambar 4. 5 Tampilan Halaman Dashboard Administrator" in p.text:
        p.text = "Gambar 4. 6 Tampilan Halaman Dashboard Administrator."
    elif "Gambar 4. 6 Tampilan Halaman Data Calon Penerima" in p.text:
        p.text = "Gambar 4. 7 Tampilan Halaman Data Calon Penerima."
    elif "Gambar 4. 7 Tampilan Halaman Data Kriteria" in p.text or "Gambar 4. 7 Hampilan Halaman Data Kriteria" in p.text:
        p.text = "Gambar 4. 8 Tampilan Halaman Data Kriteria Penilaian AHP."
    elif "Gambar 4. 8 Tampilan Halaman Perhitungan Metode AHP" in p.text:
        p.text = "Gambar 4. 9 Tampilan Halaman Perhitungan Metode AHP."
    elif "Gambar 4. 9 Tampilan Halaman Hasil Ranking Penerima PIP" in p.text:
        p.text = "Gambar 4. 10 Tampilan Halaman Hasil Ranking Penerima PIP."
    elif "Gambar 4. 10 Tampilan Halaman Laporan" in p.text:
        p.text = "Gambar 4. 11 Tampilan Halaman Laporan dan Cetak Dokumen PIP."
    elif "Gambar 4. 11 Tampilan Halaman Profil" in p.text:
        p.text = "Gambar 4. 12 Tampilan Halaman Profil dan Identitas Sekolah."

# ==============================================================================
# 4. FIX TABLE 22 (Tabel 4.13): Replace 24 old rows with 30 accurate rows
# ==============================================================================
tbl22 = doc.tables[21]
print(f"Table 22 currently has {len(tbl22.rows)} rows. Updating to 30 students...")

# 30 students data: (i, nama, v1, v2, v3, v4, v5, total)
weights = [0.4162, 0.2618, 0.1611, 0.0986, 0.0624]
students_30 = [
    (1, 'Rahmad Padil Peratama', 5, 5, 5, 5, 5),
    (2, 'Abizar Nur Ramadhan', 5, 5, 5, 4, 5),
    (3, 'Yusup Baihaqi', 5, 5, 4, 5, 5),
    (4, 'Ketut Evi Yani', 5, 5, 4, 4, 5),
    (5, 'Mita Sari', 4, 5, 5, 4, 5),
    (6, 'Bima Syarif Al Bukhory', 4, 5, 4, 4, 5),
    (7, "Abi Ibnu Mas'ud", 4, 4, 4, 3, 4),
    (8, 'Gede Radit Saputra', 4, 4, 3, 3, 4),
    (9, 'Reza Agus Pertama', 3, 4, 4, 3, 4),
    (10, 'Nyoman Mahesa Purna', 3, 4, 3, 3, 4),
    (11, 'Sri Wulandari', 3, 3, 2, 2, 1),
    (12, 'Reza Aditia', 3, 3, 2, 1, 1),
    (13, 'Gea Larassati', 3, 2, 3, 2, 1),
    (14, 'Eka Rohma Wati', 3, 2, 2, 1, 1),
    (15, 'Julia Mulyasari', 3, 2, 1, 1, 2),
    (16, 'Dimas Prayogi', 3, 1, 3, 1, 1),
    (17, 'Wildan Alip Fratama', 1, 3, 3, 2, 2),
    (18, 'Muhammad Khian Romadhoni', 2, 3, 1, 1, 2),
    (19, 'Leka Agustina', 2, 2, 3, 1, 1),
    (20, 'I Komang Mangku Suteja', 3, 1, 1, 2, 2),
    (21, 'Nengah Galih Wijaya', 1, 3, 3, 1, 2),
    (22, 'Rustam', 2, 1, 3, 1, 2),
    (23, 'Nita Sari', 2, 2, 1, 1, 2),
    (24, 'Arif Bastiar', 2, 1, 2, 1, 2),
    (25, 'Mutiara Agung Hasanah', 2, 1, 1, 2, 2),
    (26, 'Alif Fadhil Muzaqi', 1, 3, 1, 1, 1),
    (27, 'Riyan Nofendi', 2, 1, 1, 1, 2),
    (28, 'M Faiq Nur Zaman', 1, 2, 1, 2, 1),
    (29, 'Alfian Nova Pratama', 1, 2, 1, 1, 2),
    (30, 'M. Riski Julian', 1, 1, 1, 2, 1)
]

def make_math_subscript(idx):
    xml_str = f'''<m:oMath xmlns:m="http://schemas.openxmlformats.org/officeDocument/2006/math" xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
        <m:sSub>
            <m:e><m:r><w:rPr><w:rFonts w:ascii="Cambria Math" w:hAnsi="Cambria Math"/><w:sz w:val="20"/></w:rPr><m:t>A</m:t></m:r></m:e>
            <m:sub><m:r><w:rPr><w:rFonts w:ascii="Cambria Math" w:hAnsi="Cambria Math"/><w:sz w:val="20"/></w:rPr><m:t>{idx}</m:t></m:r></m:sub>
        </m:sSub>
    </m:oMath>'''
    return parse_xml(xml_str)

# Ensure table has 31 rows (1 header + 30 data)
while len(tbl22.rows) < 31:
    tbl22.add_row()

# Remove excess rows if any
while len(tbl22.rows) > 31:
    tbl22._tbl.remove(tbl22.rows[-1]._tr)

# Populate rows 1 to 30
for r_idx, s in enumerate(students_30):
    row = tbl22.rows[r_idx + 1]
    idx, nama, c1, c2, c3, c4, c5 = s
    v1 = c1 * weights[0]
    v2 = c2 * weights[1]
    v3 = c3 * weights[2]
    v4 = c4 * weights[3]
    v5 = c5 * weights[4]
    tot = v1 + v2 + v3 + v4 + v5
    
    # Cell 0: A_idx
    c0 = row.cells[0]
    c0.text = ""
    p0 = c0.paragraphs[0]
    p0.alignment = docx.enum.text.WD_ALIGN_PARAGRAPH.CENTER
    p0._p.append(make_math_subscript(idx))
    
    # Cell 1: Nama
    c1_cell = row.cells[1]
    c1_cell.text = nama
    c1_cell.paragraphs[0].alignment = docx.enum.text.WD_ALIGN_PARAGRAPH.LEFT
    for run in c1_cell.paragraphs[0].runs:
        run.font.name = "Times New Roman"
        run.font.size = docx.shared.Pt(10)
        
    # Cells 2-6: Values
    vals = [f"{v1:.4f}".replace('.', ','), f"{v2:.4f}".replace('.', ','), f"{v3:.4f}".replace('.', ','), f"{v4:.4f}".replace('.', ','), f"{v5:.4f}".replace('.', ',')]
    for col_i, val in enumerate(vals):
        cell = row.cells[col_i + 2]
        cell.text = val
        cell.paragraphs[0].alignment = docx.enum.text.WD_ALIGN_PARAGRAPH.CENTER
        for run in cell.paragraphs[0].runs:
            run.font.name = "Times New Roman"
            run.font.size = docx.shared.Pt(10)
            
    # Cell 7: Total Skor
    c7 = row.cells[7]
    c7.text = f"{tot:.4f}".replace('.', ',')
    c7.paragraphs[0].alignment = docx.enum.text.WD_ALIGN_PARAGRAPH.CENTER
    for run in c7.paragraphs[0].runs:
        run.font.name = "Times New Roman"
        run.font.size = docx.shared.Pt(10)
        run.font.bold = True

print(f"Table 22 successfully updated to {len(tbl22.rows)} rows.")

# Save modified document
doc.save(docx_path)
print("Document saved successfully!")

