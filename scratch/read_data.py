import zipfile
import xml.etree.ElementTree as ET

file_path = r"D:\KULIAH\Joki\Annisa\skripsi\BAB IV\daftar_pd-SMP TUNAS BANGSA-2026-10-17 05_49_36.xlsx"

with zipfile.ZipFile(file_path, "r") as z:
    # Read shared strings
    ss_root = ET.fromstring(z.read("xl/sharedStrings.xml"))
    ns = "{http://schemas.openxmlformats.org/spreadsheetml/2006/main}"
    shared_strings = []
    for si in ss_root.findall(f"{ns}si"):
        t = si.find(f"{ns}t")
        if t is not None:
            shared_strings.append(t.text or "")
        else:
            # might have r/t elements (formatted text)
            full_t = "".join([t_elem.text or "" for t_elem in si.findall(f".//{ns}t")])
            shared_strings.append(full_t)
            
    print(f"Total shared strings loaded: {len(shared_strings)}")
    
    # Read sheet1
    s_root = ET.fromstring(z.read("xl/worksheets/sheet1.xml"))
    sheetData = s_root.find(f"{ns}sheetData")
    rows = sheetData.findall(f"{ns}row")
    print(f"Total rows in sheet: {len(rows)}")
    
    # Print first 10 rows
    for r in rows[:10]:
        r_num = r.get("r")
        cells = []
        for c in r.findall(f"{ns}c"):
            c_ref = c.get("r")
            t_attr = c.get("t")
            v_elem = c.find(f"{ns}v")
            val = ""
            if v_elem is not None and v_elem.text:
                if t_attr == "s":
                    idx = int(v_elem.text)
                    val = shared_strings[idx] if idx < len(shared_strings) else f"ERR_IDX_{idx}"
                else:
                    val = v_elem.text
            cells.append(f"{c_ref}:{val}")
        print(f"Row {r_num} ({len(cells)} cells): {cells[:6]}")
