with open('database.sql', 'rb') as f:
    text = f.read().decode('utf-16le', errors='ignore')

for line in text.splitlines():
    if line.startswith("INSERT INTO `calon_penerima`"):
        print("Calon penerima insert found! Length:", len(line))
        # print first few inserts
        parts = line.split("VALUES")
        if len(parts) > 1:
            values_str = parts[1]
            rows = values_str.split("),(")
            print(f"Total rows in DB dump: {len(rows)}")
            for r in rows[:15]:
                print("DB Row:", r[:120])
