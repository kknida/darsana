<#
.SYNOPSIS
Script Jembatan API untuk Bot SAP Darsana

.DESCRIPTION
Script ini berjalan di PC Windows lokal setiap 1 menit via Task Scheduler.
Tugasnya adalah "menelepon" API di cPanel, mengambil konfigurasi jadwal & kredensial terbaru,
lalu membandingkannya dengan jam komputer lokal. Jika jamnya cocok, script ini akan 
memperbarui file config_sap.ini lokal dan menyalakan Bot VBS SAP.
#>

# ====================================================================
# PENGATURAN UTAMA (WAJIB DIUBAH SESUAI DOMAIN CPANEL ANDA)
# ====================================================================
$ApiUrl = "http://localhost/darsana/public/api/sap-config" # GANTI DENGAN URL CPANEL ANDA, contoh: "https://darsana-airnav.com/api/sap-config"
# ====================================================================

$ScriptDir = Split-Path -Parent $MyInvocation.MyCommand.Definition
$IniFile = Join-Path $ScriptDir "config_sap.ini"
$BatFile = Join-Path $ScriptDir "run_export_sap.bat"
$LogFile = Join-Path $ScriptDir "sync_log.txt"

Function Write-Log {
    Param([string]$Message)
    $Timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    Add-Content -Path $LogFile -Value "[$Timestamp] $Message"
}

Write-Log "Memeriksa jadwal dari API: $ApiUrl ..."

try {
    # Ambil data JSON dari cPanel
    $Response = Invoke-RestMethod -Uri $ApiUrl -Method Get -ErrorAction Stop

    if ($Response.status -ne 'success') {
        Write-Log "Gagal mengambil data. Status bukan success."
        exit
    }

    $Data = $Response.data
    $ScheduledHour = $Data.hour
    $ScheduledMinute = $Data.minute
    $SapUser = $Data.sapUser
    $SapPass = $Data.sapPass
    $ExportFolder = $Data.exportFolder

    # Validasi apakah jadwal sudah diset (tidak kosong)
    if ([string]::IsNullOrWhiteSpace($ScheduledHour) -or [string]::IsNullOrWhiteSpace($ScheduledMinute)) {
        Write-Log "Jadwal belum dikonfigurasi di Web Dashboard. Mengabaikan eksekusi."
        exit
    }

    $CurrentHour = (Get-Date).Hour
    $CurrentMinute = (Get-Date).Minute

    # Cek kecocokan waktu
    if ([int]$ScheduledHour -eq $CurrentHour -and [int]$ScheduledMinute -eq $CurrentMinute) {
        Write-Log "WAKTU COCOK! Menyimpan kredensial terbaru ke config_sap.ini lokal..."

        # Update file config_sap.ini lokal
        $IniContent = @"
[SAP]
sapUser=$SapUser
sapPass=$SapPass
exportFolder=$ExportFolder
hour=$ScheduledHour
minute=$ScheduledMinute
"@
        Set-Content -Path $IniFile -Value $IniContent -Force

        Write-Log "Config diperbarui. Menjalankan run_export_sap.bat..."
        
        # Jalankan Batch File secara terpisah (jangan blokir PowerShell)
        Start-Process -FilePath $BatFile -WorkingDirectory $ScriptDir
    } else {
        Write-Log "Waktu belum cocok. Jadwal: $ScheduledHour:$ScheduledMinute, Sekarang: $CurrentHour:$CurrentMinute."
    }

} catch {
    Write-Log "ERROR KONEKSI API: $($_.Exception.Message). Pastikan PC ini terkoneksi ke internet dan URL API benar."
}
