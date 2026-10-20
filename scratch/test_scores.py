# Bobot AHP standar skripsi Annisa:
# C1: 0.4162, C2: 0.2618, C3: 0.1608, C4: 0.0985, C5: 0.0623
w1, w3, w4, w5 = 0.4162, 0.1608, 0.0985, 0.0623

test_profiles = [
    # (Nama, C1_val, C3_val, C4_val, C5_val)
    ("1. RAHMAD VADIL PRATAMA", 5, 5, 5, 5),
    ("2. ABIZHAR NURRAKHMADAN", 5, 5, 4, 5),
    ("3. YUSUF BAIHAQI", 5, 4, 5, 5),
    ("4. Ketut Evi Yani", 5, 4, 3, 4),
    ("5. Mita Sari", 4, 5, 4, 4),
    ("6. BIMA SYARIF AL BUKHORI", 4, 4, 4, 4),
    ("7. ABI IBNU MAS'UD", 4, 4, 3, 3),
    ("8. Gede Radit Saputra", 4, 3, 3, 3),
    ("9. REZA AGUS PRATAMA", 3, 4, 3, 3),
    ("10. Nyoman Mahesa Purna", 3, 3, 3, 3),
    ("11. Siswa Lain Max", 3, 2, 2, 2)
]

prev_score = 999
for name, c1, c3, c4, c5 in test_profiles:
    score = c1*w1 + c3*w3 + c4*w4 + c5*w5
    assert score < prev_score, f"Score not decreasing: {score} >= {prev_score} for {name}"
    print(f"{name}: Skor = {score:.4f} (C1={c1}, C3={c3}, C4={c4}, C5={c5})")
    prev_score = score

print("\nALL SCORES STRICTLY DECREASING PERFECTLY!")
