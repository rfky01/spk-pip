import docx, sys
sys.stdout.reconfigure(encoding='utf-8')
doc = docx.Document(r'D:\KULIAH\Joki\Annisa\(ok) reNEWok Proposal BAB I-III Annisa belum fix 2 (1) (1) - UPDATED.docx')
p = doc.paragraphs[881]
print("p.style:", p.style.name)
print("p.alignment:", p.alignment)
print("p.paragraph_format.line_spacing:", p.paragraph_format.line_spacing)
print("p.paragraph_format.first_line_indent:", p.paragraph_format.first_line_indent)
print("p.paragraph_format.space_after:", p.paragraph_format.space_after)
print("p.paragraph_format.space_before:", p.paragraph_format.space_before)
if p.runs:
    r = p.runs[0]
    print(f"run font: name={r.font.name}, size={r.font.size}")
