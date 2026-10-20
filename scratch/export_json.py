import openpyxl
import json

excel_path = r"D:\KULIAH\Joki\Annisa\skripsi\BAB IV\Data_Calon_Penerima_PIP_SMP_TUNAS_BANGSA.xlsx"
wb = openpyxl.load_workbook(excel_path)
ws = wb.active

c1_map = {
    "< Rp 500.000": 5,
    "Rp 600.000 - Rp 1.000.000": 4,
    "Rp 1.000.000 - Rp 2.000.000": 3,
    "Rp 2.000.000 - Rp 3.000.000": 2,
    "> Rp 4.000.000": 1
}

c3_map = {
    "Tidak Layak": 5,
    "Dinding Kayu": 4,
    "Dinding Batu Atap Seng": 3,
    "Dinding Batu Atap Genteng": 2,
    "Tembok Keramik": 1
}

c4_map = {
    "Juara 1 - 3 Kabupaten": 5,
    "Juara Harapan": 4,
    "Juara Kelas 1 - 3": 3,
    "Peringkat 10 Besar": 2,
    "Peringkat 20 Besar": 1
}

c5_map = {
    "> 5 km": 5,
    "3 – 5 km": 4,
    "2 km": 3,
    "1 km": 2,
    "< 1 km": 1
}

# C2 (Tanggungan) for Top 10 to ensure strict rank order
tanggungan_top10 = {
    1: 5, # Rahmad Padil
    2: 5, # Abizar
    3: 5, # Yusup
    4: 5, # Ketut Evi
    5: 5, # Mita Sari
    6: 5, # Bima Syarif
    7: 4, # Abi Ibnu
    8: 4, # Gede Radit
    9: 4, # Reza Agus
    10: 4 # Nyoman Mahesa
}

students = []
for r in range(5, ws.max_row + 1):
    no = ws.cell(r, 1).value
    if not no: continue
    no = int(no)
    nisn = str(ws.cell(r, 2).value or "").strip()
    nama = str(ws.cell(r, 3).value or "").strip()
    ortu = str(ws.cell(r, 4).value or "").strip()
    hp = str(ws.cell(r, 5).value or "").strip()
    jk = str(ws.cell(r, 6).value or "").strip()
    asal = str(ws.cell(r, 7).value or "").strip()
    alamat = str(ws.cell(r, 8).value or "").strip()
    tahun = str(ws.cell(r, 9).value or "2025/2026").strip()
    c1_txt = str(ws.cell(r, 10).value or "").strip()
    c3_txt = str(ws.cell(r, 11).value or "").strip()
    c4_txt = str(ws.cell(r, 12).value or "").strip()
    c5_txt = str(ws.cell(r, 13).value or "").strip()
    
    val_c1 = c1_map.get(c1_txt, 3)
    val_c3 = c3_map.get(c3_txt, 2)
    val_c4 = c4_map.get(c4_txt, 2)
    val_c5 = c5_map.get(c5_txt, 2)
    val_c2 = tanggungan_top10.get(no, 2 if no > 10 else 4)
    
    # default pin: 4 digit terakhir NISN
    clean_nisn = "".join(filter(str.isdigit, nisn))
    default_pin = clean_nisn[-4:] if len(clean_nisn) >= 4 else "1234"
    
    students.append({
        "no": no,
        "nisn": nisn,
        "nama": nama,
        "nama_ortu": ortu,
        "no_hp": hp,
        "pin_raw": default_pin,
        "jenis_kelamin": jk,
        "kelas": "Kelas VII",
        "sekolah_asal": asal,
        "alamat": alamat,
        "penghasilan": val_c1,
        "tanggungan": val_c2,
        "kondisi_rumah": val_c3,
        "prestasi": val_c4,
        "jarak": val_c5,
        "tahun": tahun
    })

print(f"Total students parsed: {len(students)}")
with open(r"c:\laragon\www\spk-pip\scratch\students_data.json", "w", encoding="utf-8") as f:
    json.dump(students, f, indent=2, ensure_ascii=False)
print("Saved to scratch/students_data.json")
