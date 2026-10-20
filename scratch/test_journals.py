import urllib.request
import re

test_urls = [
    ("BSI Khatulistiwa view", "https://ejournal.bsi.ac.id/ejurnal/index.php/khatulistiwa/article/view/8333"),
    ("BSI Khatulistiwa download", "https://ejournal.bsi.ac.id/ejurnal/index.php/khatulistiwa/article/download/8333/4231"),
    ("BSI JI view", "https://ejournal.bsi.ac.id/ejurnal/index.php/ji/article/view/304"),
    ("BSI JI download", "https://ejournal.bsi.ac.id/ejurnal/index.php/ji/article/download/304/232"),
]

for label, u in test_urls:
    try:
        req = urllib.request.Request(u, headers={'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'})
        with urllib.request.urlopen(req, timeout=10) as resp:
            data = resp.read()
            is_pdf = data.startswith(b"%PDF")
            print(f"{label}: status={resp.status}, is_pdf={is_pdf}, len={len(data)}")
            if is_pdf:
                txt = data.decode('latin1', errors='ignore')
                wf = len(re.findall(r'waterfall', txt, re.I))
                print(f"   contains 'waterfall': {wf} times")
    except Exception as e:
        print(f"{label}: ERROR {e}")

