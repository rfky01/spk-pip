import pypdf
import os
import sys

sys.stdout.reconfigure(encoding='utf-8')

folder = r"D:\KULIAH\@SEMESTER 8\SKRIPSI\BAB II\Landasan Teori\E Metode Pengembangan Sistem Waterval"
files = os.listdir(folder)

for fname in files:
    if not fname.endswith('.pdf'):
        continue
    fpath = os.path.join(folder, fname)
    reader = pypdf.PdfReader(fpath)
    print("=" * 80)
    print("FILE:", fname)
    print("TOTAL PAGES:", len(reader.pages))
    
    for idx, page in enumerate(reader.pages):
        text = page.extract_text()
        if "waterfall" in text.lower():
            print(f"\n--- PAGE {idx+1} EXCERPT ---")
            lines = text.split("\n")
            for i, l in enumerate(lines):
                if any(k in l.lower() for k in ["waterfall", "sdlc", "tahapan"]):
                    # print context
                    start = max(0, i-2)
                    end = min(len(lines), i+3)
                    print(" ".join(lines[start:end]))

