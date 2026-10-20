with open('database.sql', 'rb') as f:
    text = f.read().decode('utf-16le', errors='ignore')

idx = text.find('CREATE TABLE `calon_penerima`')
with open(r'c:\laragon\www\spk-pip\scratch\calon_schema.txt', 'w', encoding='utf-8') as out:
    out.write(text[idx:idx+1200])
print("Schema written to scratch/calon_schema.txt")
