import platform
import subprocess
import os

print("OS:", platform.platform())
print("OS System:", platform.system(), platform.release(), platform.version())
print("Architecture:", platform.architecture()[0])
print("Machine:", platform.machine())
print("Processor:", platform.processor())

try:
    import psutil
    print("CPU Count (physical):", psutil.cpu_count(logical=False))
    print("CPU Count (logical):", psutil.cpu_count(logical=True))
    ram_gb = round(psutil.virtual_memory().total / (1024**3), 2)
    print(f"Total RAM: {ram_gb} GB")
    disk = psutil.disk_usage('C:')
    print(f"Drive C: Total {round(disk.total / (1024**3), 2)} GB, Free {round(disk.free / (1024**3), 2)} GB")
except Exception as e:
    print("psutil not available:", e)

# Check PHP
try:
    res = subprocess.run(["php", "-v"], capture_output=True, text=True, check=True)
    print("PHP Version:", res.stdout.splitlines()[0])
except Exception as e:
    print("PHP check error:", e)

# Check MySQL
try:
    res = subprocess.run(["mysql", "--version"], capture_output=True, text=True, check=True)
    print("MySQL Version:", res.stdout.splitlines()[0])
except Exception as e:
    print("MySQL check error:", e)

# Check Apache
for p in [r"C:\laragon\bin\apache", r"C:\xampp\apache\bin\httpd.exe"]:
    if os.path.exists(p):
        print("Apache path:", p)

