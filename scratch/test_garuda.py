import urllib.request
import json
import re

# Search Garuda API or fetch open papers
test_garuda = [
    # Article: Rancang Bangun Sistem Informasi... Waterfall
    "http://download.garuda.kemdikbud.go.id/article.php?article=1883832&val=14436&title=PENERAPAN%20METODE%20WATERFALL%20DALAM%20PERANCANGAN%20SISTEM%20INFORMASI",
    "http://download.garuda.kemdikbud.go.id/article.php?article=2584102&val=4738&title=Penerapan%20Model%20Waterfall%20Pada%20Pengembangan%20Sistem%20Informasi",
    "http://download.garuda.kemdikbud.go.id/article.php?article=1187425&val=1086&title=Analisis%20Metode%20Waterfall%20Untuk%20Pengembangan%20Sistem%20Informasi",
]

for url in test_garuda:
    try:
        req = urllib.request.Request(url, headers={'User-Agent': 'Mozilla/5.0'})
        with urllib.request.urlopen(req, timeout=10) as resp:
            data = resp.read()
            is_pdf = data.startswith(b"%PDF")
            print(f"URL: {url[:70]}... -> Status: {resp.status}, is_pdf: {is_pdf}, size: {len(data)}")
            if is_pdf:
                txt = data.decode('latin1', errors='ignore')
                # find title or authors
                print("   Found PDF! First 300 chars:", txt[:300].replace('\n', ' '))
    except Exception as e:
        print(f"Failed {url[:60]}: {e}")

