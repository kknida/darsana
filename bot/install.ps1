$ErrorActionPreference = "Stop"
$PSScriptRoot = Split-Path -Parent -Path $MyInvocation.MyCommand.Definition

# 1. Wajib dijalankan sebagai Administrator.
$isAdmin = ([Security.Principal.WindowsPrincipal][Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
if (-not $isAdmin) {
    Write-Host "Pemasangan gagal: Skrip harus dijalankan sebagai Administrator." -ForegroundColor Red
    exit 1
}

# 2. Bila bot\bot.env belum ada, tanya pengguna secara interaktif
$envFile = Join-Path $PSScriptRoot "bot.env"
if (Test-Path $envFile) {
    Write-Host "Berkas bot.env sudah ada. Dilewati." -ForegroundColor Yellow
} else {
    $dashboardUrl = Read-Host "Masukkan DASHBOARD_URL (Tekan Enter untuk bawaan: https://darsana.airnavsub.id)"
    if ([string]::IsNullOrWhiteSpace($dashboardUrl)) {
        $dashboardUrl = "https://darsana.airnavsub.id"
    }
    
    $botToken = ""
    while ($botToken.Length -lt 32) {
        $botToken = Read-Host "Masukkan BOT_TOKEN (wajib, minimal 32 karakter)"
        if ($botToken.Length -lt 32) {
            Write-Host "Token harus minimal 32 karakter!" -ForegroundColor Red
        }
    }
    
    $envContent = "DASHBOARD_URL=$dashboardUrl`r`nBOT_TOKEN=$botToken"
    Set-Content -Path $envFile -Value $envContent -Encoding UTF8
    Write-Host "Berkas bot.env berhasil dibuat." -ForegroundColor Green
}

# 3. Jalankan bot\preflight.ps1 dan tampilkan seluruh keluarannya.
Write-Host "Menjalankan pemeriksaan kesiapan (preflight)..." -ForegroundColor Cyan
$preflightScript = Join-Path $PSScriptRoot "preflight.ps1"
if (Test-Path $preflightScript) {
    & $preflightScript
} else {
    Write-Host "Skrip preflight.ps1 tidak ditemukan!" -ForegroundColor Red
}

# 4. Daftarkan Scheduled Task bernama DarsanaSapBot
Write-Host "Mendaftarkan Scheduled Task..." -ForegroundColor Cyan
$taskName = "DarsanaSapBot"
$runnerBat = Join-Path $PSScriptRoot "runner.bat"

$cmd = 'schtasks /Create /TN "{0}" /TR "\"{1}\"" /SC MINUTE /MO 1 /IT /RU "{2}" /F' -f $taskName, $runnerBat, $env:USERNAME
cmd.exe /c $cmd | Out-Null

if ($LASTEXITCODE -eq 0) {
    Write-Host "Pendaftaran task berhasil." -ForegroundColor Green
    schtasks /Query /TN "DarsanaSapBot" /V /FO LIST
} else {
    Write-Host "Pendaftaran task gagal. Pastikan skrip dijalankan sebagai Administrator." -ForegroundColor Red
    exit 1
}

# 5. Di akhir, cetak daftar langkah manual yang TIDAK bisa dilakukan skrip
Write-Host "`nLangkah manual yang perlu Anda lakukan:" -ForegroundColor Cyan
Write-Host "1. Isi Folder Export di halaman Pengaturan Bot SAP dashboard."
Write-Host "2. Jalankan runner.ps1 sekali secara manual, lalu pada popup SAP GUI Security centang 'Remember My Decision' lalu tekan 'Allow'."
Write-Host "3. Pastikan sapgui/user_scripting = TRUE di sisi server SAP."
Write-Host "4. Atur Power Options ke Never dan screensaver None."
Write-Host "5. Aplikasi SAP Logon harus SELALU TERBUKA di layar daftar koneksi, tanpa login. Bot yang akan login sendiri."
Write-Host "`nPemasangan selesai." -ForegroundColor Green
