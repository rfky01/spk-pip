import zipfile
import xml.etree.ElementTree as ET
import os

file_path = r"D:\KULIAH\Joki\Annisa\skripsi\BAB IV\daftar_pd-SMP TUNAS BANGSA-2026-10-17 05_49_36.xlsx"

print("Checking file:", file_path)
print("File size:", os.path.getsize(file_path))

with zipfile.ZipFile(file_path, "r") as z:
    for name in z.namelist():
        data = z.read(name)
        if name.endswith(".xml") or name.endswith(".rels"):
            try:
                root = ET.fromstring(data)
            except Exception as e:
                print(f"FAILED on {name}: {e}")

# Let's inspect sheet1.xml details
with zipfile.ZipFile(file_path, "r") as z:
    sheet1_xml = z.read("xl/worksheets/sheet1.xml")
    root = ET.fromstring(sheet1_xml)
    ns = "{http://schemas.openxmlformats.org/spreadsheetml/2006/main}"
    
    # check sheetData
    sheetData = root.find(f"{ns}sheetData")
    rows = sheetData.findall(f"{ns}row")
    print(f"Total rows in sheetData: {len(rows)}")
    
    # Check mergeCells
    mergeCells = root.find(f"{ns}mergeCells")
    if mergeCells is not None:
        merges = mergeCells.findall(f"{ns}mergeCell")
        print(f"Total mergeCells: {len(merges)}, count attr: {mergeCells.get('count')}")
    
    # Check sheetProtection
    sp = root.find(f"{ns}sheetProtection")
    if sp is not None:
        print("sheetProtection:", sp.attrib)

    # Check sharedStrings
    ss_xml = z.read("xl/sharedStrings.xml")
    ss_root = ET.fromstring(ss_xml)
    print("sharedStrings attr:", ss_root.attrib)
