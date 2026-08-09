param([switch]$MockExport)
$ErrorActionPreference = "Stop"

$BotVersion = "1.0.0"
$MachineName = $env:COMPUTERNAME
$ScriptDir = $PSScriptRoot
$ConfigFile = Join-Path $ScriptDir "bot.env"
$IniFile = Join-Path $ScriptDir "runtime_config.ini"
$LockFile = Join-Path $ScriptDir "bot.lock"
$LogsDir = Join-Path $ScriptDir "logs"
$LogFile = Join-Path $LogsDir "bot_$(Get-Date -Format 'yyyyMMdd').log"

if (-not (Test-Path $LogsDir)) {
    New-Item -ItemType Directory -Force -Path $LogsDir | Out-Null
}

function Write-Log {
    param([string]$Message)
    $Stamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    "$Stamp - $Message" | Out-File -FilePath $LogFile -Append
}

if ($MockExport) {
    Write-Log "MODE MOCK AKTIF: Melewati pemanggilan SAP asli."
}

if (Test-Path $LockFile) {
    $LockAge = (New-TimeSpan -Start (Get-Item $LockFile).LastWriteTime -End (Get-Date)).TotalMinutes
    if ($LockAge -lt 30) {
        Write-Log "Bot sedang berjalan (lock < 30 menit). Keluar."
        exit 0
    } else {
        Write-Log "Lock kadaluarsa. Menghapus lock lama."
        Remove-Item -Path $LockFile -Force
    }
}

if (-not (Test-Path $ConfigFile)) {
    Write-Log "ERROR: bot.env tidak ditemukan."
    exit 1
}

$BotEnv = @{}
Get-Content $ConfigFile | ForEach-Object {
    if ($_ -match '^\s*([^#=\s]+)\s*=\s*(.*)$') {
        $BotEnv[$Matches[1]] = $Matches[2].Trim()
    }
}
$ServerUrl = $BotEnv["DASHBOARD_URL"].TrimEnd('/')
$Token = $BotEnv["BOT_TOKEN"]

$Headers = @{
    "Authorization" = "Bearer $Token"
    "Accept"        = "application/json"
}

function Send-Heartbeat {
    param([string]$Status, [string]$Message)
    try {
        $Body = @{
            status = $Status
            message = $Message
            machineName = $MachineName
            botVersion = $BotVersion
        }
        Invoke-RestMethod -Uri "$ServerUrl/api/sap/heartbeat" -Method Post -Headers $Headers -Body $Body -ErrorAction SilentlyContinue | Out-Null
        Write-Log "Heartbeat dikirim: $Status ($Message)"
    } catch {
        Write-Log "Gagal mengirim heartbeat: $($_.Exception.Message)"
    }
}

try {
    # Buat lock
    "" | Out-File -FilePath $LockFile
    Write-Log "Mulai sesi bot."
    
    # 1. Cek Settings
    $Settings = Invoke-RestMethod -Uri "$ServerUrl/api/sap/settings" -Method Get -Headers $Headers
    
    if ("$($Settings.shouldRunNow)".Trim().ToLower() -in @("1","true","yes")) {
        Send-Heartbeat -Status "running" -Message "Mulai eksekusi sinkronisasi."
        
        # 2. Ambil Kredensial
        $Creds = Invoke-RestMethod -Uri "$ServerUrl/api/sap/credentials" -Method Get -Headers $Headers
        
        # Validasi exportFolder dan filePrefix
        $exportFolderVal = "$($Settings.exportFolder)".Trim()
        if ($exportFolderVal -eq "") {
            $Msg = "Folder Export belum diisi. Buka halaman Pengaturan Bot SAP di dashboard dan isi Folder Export."
            Send-Heartbeat -Status "error" -Message $Msg
            Write-Log $Msg
            exit 3
        }

        if (-not (Test-Path $exportFolderVal)) {
            try {
                New-Item -ItemType Directory -Force -Path $exportFolderVal -ErrorAction Stop | Out-Null
            } catch {
                $Msg = "Folder Export tidak dapat dibuat: $exportFolderVal - $($_.Exception.Message)"
                Send-Heartbeat -Status "error" -Message $Msg
                Write-Log $Msg
                exit 5
            }
        }

        $filePrefixVal = "$($Settings.filePrefix)".Trim()
        if ($filePrefixVal -eq "") {
            $filePrefixVal = "realisasi_"
            Write-Log "Peringatan: filePrefix kosong. Menggunakan bawaan '$filePrefixVal'."
        }

        $Settings.exportFolder = $exportFolderVal
        $Settings.filePrefix = $filePrefixVal

        # 3. Tulis runtime_config.ini
        $LogoutAfterVal = if ("$($Settings.logoutAfter)".Trim().ToLower() -in @("1","true","yes")) { "1" } else { "0" }
        $IniContent = @"
[SAP]
sapSystem=$($Settings.sapSystem)
sapClient=$($Settings.sapClient)
sapUser=$($Creds.sapUser)
sapPass=$($Creds.sapPass)
sapLang=$($Settings.sapLang)
logoutAfter=$LogoutAfterVal

[Export]
exportFolder=$($Settings.exportFolder)
filePrefix=$($Settings.filePrefix)
reportTx=$($Settings.reportTx)
fmArea=$($Settings.fmArea)
fundCenterLow=$($Settings.fundCenterLow)
fundCenterHigh=$($Settings.fundCenterHigh)
"@
        Set-Content -Path $IniFile -Value $IniContent
        Write-Log "runtime_config.ini berhasil dibuat."
        
        # Jalankan sap_security_watcher.exe bila ada
        $WatcherExe = Join-Path $ScriptDir "sap_security_watcher.exe"
        $WatcherProc = $null
        if (Test-Path $WatcherExe) {
            Write-Log "Menjalankan sap_security_watcher.exe di latar belakang."
            $WatcherProc = Start-Process -FilePath $WatcherExe -PassThru -NoNewWindow
        }
        
        # 4. Jalankan VBS atau Mock
        if ($MockExport) {
            Write-Log "Menghasilkan CSV mock..."
            $ExitCode = 0
            $Stamp = Get-Date -Format "yyyyMMdd_HHmm"
            $MockPath = Join-Path $Settings.exportFolder "$($Settings.filePrefix)$Stamp.csv"
            $SamplePath = Join-Path $ScriptDir "sample_export.csv"
            if (Test-Path $SamplePath) {
                Copy-Item $SamplePath $MockPath -Force
                $VbsOut = "EXPORTED_FILE=$MockPath"
                Write-Log "VBS Output (Mock): $VbsOut"
            } else {
                $ExitCode = 1
                $VbsOut = "GAGAL 1: sample_export.csv tidak ditemukan di bot folder."
                Write-Log "VBS Output (Mock Fail): $VbsOut"
            }
        } else {
            $VbsPath = Join-Path $ScriptDir "export_sap.vbs"
            Write-Log "Mengeksekusi $VbsPath..."
            $Process = Start-Process -FilePath "cscript.exe" -ArgumentList "//nologo", "`"$VbsPath`"" -Wait -NoNewWindow -PassThru -RedirectStandardOutput (Join-Path $ScriptDir "vbs_out.log") -RedirectStandardError (Join-Path $ScriptDir "vbs_err.log")
            
            $ExitCode = $Process.ExitCode
            $VbsOut = Get-Content (Join-Path $ScriptDir "vbs_out.log") -Raw
            Write-Log "VBS Exit Code: $ExitCode"
            Write-Log "VBS Output: $VbsOut"
        }
        
        # Matikan watcher jika jalan
        if ($WatcherProc -ne $null -and -not $WatcherProc.HasExited) {
            Stop-Process -Id $WatcherProc.Id -Force -ErrorAction SilentlyContinue
        }
        
        if ($ExitCode -eq 0 -or $ExitCode -eq 2) {
            $CsvPath = $null
            if ($VbsOut -match "EXPORTED_FILE=(.*)") {
                $CsvPath = $Matches[1].Trim()
            }
            
            if ($CsvPath -and (Test-Path $CsvPath)) {
                Write-Log "File ditemukan di $CsvPath. Memulai unggahan."
                
                # Multipart upload
                $Boundary = [System.Guid]::NewGuid().ToString()
                $FileBytes = [System.IO.File]::ReadAllBytes($CsvPath)
                $FileName = Split-Path $CsvPath -Leaf
                
                $BodyLines = (
                    "--$Boundary",
                    "Content-Disposition: form-data; name=`"file`"; filename=`"$FileName`"",
                    "Content-Type: text/csv",
                    "",
                    [System.Text.Encoding]::GetEncoding("iso-8859-1").GetString($FileBytes),
                    "--$Boundary--",
                    ""
                ) -join "`r`n"
                
                $UploadHeaders = @{
                    "Authorization" = "Bearer $Token"
                    "Accept"        = "application/json"
                    "Content-Type"  = "multipart/form-data; boundary=$Boundary"
                }
                $UploadBody = [System.Text.Encoding]::GetEncoding("iso-8859-1").GetBytes($BodyLines)
                
                $Response = Invoke-RestMethod -Uri "$ServerUrl/api/sap/import" -Method Post -Headers $UploadHeaders -Body $UploadBody
                $Msg = "Selesai: $($Response.rows_imported) baris diimpor."
                if ($ExitCode -eq 2) {
                    $Msg = "Logout gagal, namun " + $Msg
                }
                Send-Heartbeat -Status "idle" -Message $Msg
                Write-Log $Msg
            } else {
                Send-Heartbeat -Status "error" -Message "File export sukses (Exit $ExitCode) tapi path EXPORTED_FILE tidak ditemukan / valid."
                Write-Log "ERROR: File tidak ditemukan di $CsvPath"
            }
        } elseif ($ExitCode -eq 3 -and $VbsOut -match "ERROR_CONFIG=(.*)") {
            $Msg = "Konfigurasi salah: $($Matches[1].Trim())"
            Send-Heartbeat -Status "error" -Message $Msg
            Write-Log $Msg
        } elseif ($ExitCode -eq 5 -and $VbsOut -match "ERROR_FOLDER=(.*)") {
            $Msg = "Folder gagal dibuat: $($Matches[1].Trim())"
            Send-Heartbeat -Status "error" -Message $Msg
            Write-Log $Msg
        } else {
            Send-Heartbeat -Status "error" -Message "Gagal eksekusi SAP (Exit $ExitCode)"
            Write-Log "ERROR SAP (Exit $ExitCode)"
        }
    } else {
        Send-Heartbeat -Status "idle" -Message "Menunggu jadwal."
        Write-Log "Menunggu jadwal."
    }
} catch {
    Send-Heartbeat -Status "error" -Message "Error koneksi / proses: $($_.Exception.Message)"
    Write-Log "ERROR: $($_.Exception.Message)"
} finally {
    if (Test-Path $IniFile) {
        Remove-Item -Path $IniFile -Force
        Write-Log "Dihapus: runtime_config.ini"
    }
    if (Test-Path $LockFile) {
        Remove-Item -Path $LockFile -Force
        Write-Log "Dihapus: bot.lock"
    }
    if (Test-Path (Join-Path $ScriptDir "vbs_out.log")) {
        Remove-Item -Path (Join-Path $ScriptDir "vbs_out.log") -Force -ErrorAction SilentlyContinue
    }
    if (Test-Path (Join-Path $ScriptDir "vbs_err.log")) {
        Remove-Item -Path (Join-Path $ScriptDir "vbs_err.log") -Force -ErrorAction SilentlyContinue
    }
}
