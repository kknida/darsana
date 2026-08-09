import pyautogui
import time
import sys

print("Menampilkan posisi mouse secara real-time.")
print("Tekan Ctrl+C untuk berhenti.")
print("-" * 40)

try:
    while True:
        x, y = pyautogui.position()
        position_str = f"X: {str(x).rjust(4)} Y: {str(y).rjust(4)}"
        print(position_str, end='')
        print('\b' * len(position_str), end='', flush=True)
        time.sleep(0.1)
except KeyboardInterrupt:
    print("\n" + "-" * 40)
    print("Selesai. Skrip dihentikan.")
    sys.exit(0)
