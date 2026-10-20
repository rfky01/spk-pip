weights = [0.4162, 0.2618, 0.1611, 0.0986, 0.0624]
data = [
    (1, '3132175348', 'Rahmad Padil Peratama', 5, 5, 5, 5, 5),
    (2, '3132091349', 'Abizar Nur Ramadhan', 5, 5, 5, 4, 5),
    (3, '3130216362', 'Yusup Baihaqi', 5, 5, 4, 5, 5),
    (4, '0146954594', 'Ketut Evi Yani', 5, 5, 4, 4, 5),
    (5, '0129671884', 'Mita Sari', 4, 5, 5, 4, 5),
    (6, '3147818599', 'Bima Syarif Al Bukhory', 4, 5, 4, 4, 5),
    (7, '3137905151', "Abi Ibnu Mas'ud", 4, 4, 4, 3, 4),
    (8, '0139819967', 'Gede Radit Saputra', 4, 4, 3, 3, 4),
    (9, '3134079047', 'Reza Agus Pertama', 3, 4, 4, 3, 4),
    (10, '0136812422', 'Nyoman Mahesa Purna', 3, 4, 3, 3, 4),
    (11, '3130024465', 'Sri Wulandari', 3, 3, 2, 2, 1),
    (12, '3136954181', 'Reza Aditia', 3, 3, 2, 1, 1),
    (13, '3139409785', 'Gea Larassati', 3, 2, 3, 2, 1),
    (14, '3134692987', 'Eka Rohma Wati', 3, 2, 2, 1, 1),
    (15, '0122286262', 'Julia Mulyasari', 3, 2, 1, 1, 2),
    (16, '3144384403', 'Dimas Prayogi', 3, 1, 3, 1, 1),
    (17, '3128344130', 'Wildan Alip Fratama', 1, 3, 3, 2, 2),
    (18, '0121251246', 'Muhammad Khian Romadhoni', 2, 3, 1, 1, 2),
    (19, '3138053286', 'Leka Agustina', 2, 2, 3, 1, 1),
    (20, '3133101864', 'I Komang Mangku Suteja', 3, 1, 1, 2, 2),
    (21, '0133213389', 'Nengah Galih Wijaya', 1, 3, 3, 1, 2),
    (22, '0131523834', 'Rustam', 2, 1, 3, 1, 2),
    (23, '3136924904', 'Nita Sari', 2, 2, 1, 1, 2),
    (24, '3135949607', 'Arif Bastiar', 2, 1, 2, 1, 2),
    (25, '3136199779', 'Mutiara Agung Hasanah', 2, 1, 1, 2, 2),
    (26, '3134241016', 'Alif Fadhil Muzaqi', 1, 3, 1, 1, 1),
    (27, '3137997069', 'Riyan Nofendi', 2, 1, 1, 1, 2),
    (28, '0136323974', 'M Faiq Nur Zaman', 1, 2, 1, 2, 1),
    (29, '3138747444', 'Alfian Nova Pratama', 1, 2, 1, 1, 2),
    (30, '0129044009', 'M. Riski Julian', 1, 1, 1, 2, 1)
]

print("=== NILAI SUBKRITERIA ===")
for item in data:
    i, nisn, nama, c1, c2, c3, c4, c5 = item
    print(f"| $A_{{{i}}}$ | {nisn} | {nama} | {c1} | {c2} | {c3} | {c4} | {c5} |")

print("\n=== PERKALIAN BOBOT & SKOR AKHIR ===")
for item in data:
    i, nisn, nama, c1, c2, c3, c4, c5 = item
    v1 = c1 * weights[0]
    v2 = c2 * weights[1]
    v3 = c3 * weights[2]
    v4 = c4 * weights[3]
    v5 = c5 * weights[4]
    total = v1 + v2 + v3 + v4 + v5
    print(f"| $A_{{{i}}}$ | {nama} | {v1:.4f} | {v2:.4f} | {v3:.4f} | {v4:.4f} | {v5:.4f} | {total:.4f} |")

