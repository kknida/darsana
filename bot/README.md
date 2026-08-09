# Panduan Pemasangan Bot SAP Darsana

## 1. Apa yang dilakukan bot ini
Bot ini bertugas membaca pengaturan jadwal dari dashboard, lalu secara otomatis mengendalikan aplikasi SAP GUI di komputer ini untuk mengekspor data laporan ke dalam berkas CSV. Setelah diekspor, bot akan mengunggah berkas tersebut kembali ke dashboard Darsana.

## 2. Syarat sebelum memasang
- Komputer menggunakan sistem operasi Windows.
- Aplikasi SAP GUI sudah terpasang dan dapat dijalankan.
- Anda memiliki hak akses Administrator di komputer ini.
- Komputer selalu menyala dan terhubung ke internet atau jaringan internal.

## 3. Cara memasang
1. Buka folder tempat skrip bot ini berada (`bot`).
2. Klik kanan pada berkas `install.ps1`, lalu pilih **Run with PowerShell**.
3. Jika muncul peringatan keamanan, tekan tombol **Yes** atau **Allow**.
4. Skrip akan meminta Anda memasukkan `DASHBOARD_URL` (tekan Enter untuk memakai nilai bawaan) dan `BOT_TOKEN` (minimal 32 karakter yang bisa didapatkan dari dashboard).
5. Tunggu proses instalasi dan pengecekan otomatis selesai.

## 4. Cara memeriksa bot sehat atau tidak
Untuk memeriksa apakah bot siap bekerja tanpa harus melakukan pengaturan ulang, Anda cukup:
1. Klik kanan pada berkas `preflight.ps1` dan pilih **Run with PowerShell**.
2. Perhatikan hasil keluaran di layar. Semua indikator harus tertulis `[LULUS]`. Jika ada yang `[GAGAL]`, baca keterangan peringatannya.

## 5. Cara mengubah jadwal dan folder
Semua perubahan jadwal (jam/menit) dan letak folder tujuan ekspor (Folder Export) **tidak** dilakukan melalui berkas konfigurasi di komputer ini. 
Anda harus melakukan perubahan tersebut secara langsung melalui halaman **Pengaturan Bot SAP** yang tersedia di aplikasi Darsana (dashboard web).

## 6. Masalah yang sering terjadi dan cara menanganinya
- **[GAGAL] SAP GUI Scripting belum aktif di sisi klien**: Anda perlu meminta bagian IT untuk mengaktifkan dukungan Scripting pada instalasi SAP GUI.
- **[GAGAL] Token salah atau belum di-clear di server**: Token yang dimasukkan saat instalasi salah. Hapus berkas `bot.env` lalu jalankan `install.ps1` kembali untuk memasukkan token yang benar.
- **Popup konfirmasi berulang kali muncul dari SAP**: Jalankan `runner.ps1` secara manual satu kali, lalu pada jendela *SAP GUI Security*, centang opsi "Remember My Decision" lalu tekan tombol "Allow".

## 7. Tabel exit code
Bot akan melaporkan status kode (exit code) ke dalam sistem:
- **0**: Sukses
- **1**: Export gagal atau berkas CSV kosong
- **2**: Logout gagal, tetapi export sukses
- **3**: Konfigurasi invalid (belum diatur)
- **4**: Kredensial (username/password SAP) kosong
- **5**: Folder tidak dapat dibuat

## 8. Cara mencabut pemasangan
Jika Anda tidak lagi ingin menjalankan bot di komputer ini:
1. Klik kanan pada berkas `uninstall.ps1`, lalu pilih **Run with PowerShell**.
2. Tugas otomatis (Scheduled Task) akan dihapus, namun berkas skrip, log, dan konfigurasi akan tetap dipertahankan.
