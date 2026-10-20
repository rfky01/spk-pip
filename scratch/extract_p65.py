import fitz

doc = fitz.open(r"D:\KULIAH\Joki\Annisa\skripsi\(ok) reNEWok Proposal BAB I-III Annisa belum fix 2 (1) (1).pdf")
with open(r"c:\laragon\www\spk-pip\scratch\proposal_page65.txt", "w", encoding="utf-8") as f:
    f.write(doc[64].get_text())
    f.write("\n=== PAGE 66 ===\n")
    f.write(doc[65].get_text())
print("Page 65-66 saved to file.")
